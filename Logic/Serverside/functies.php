<?php
function maakBestelnummer($id, $datum) {
    $datumFormaat = date('Ymd', strtotime($datum));

    return $datumFormaat . $id;
}

function haalProductOp($productID, $conn) {
    $sql = "SELECT * FROM tblProducten WHERE ProductID = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $productID);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $product;
}

function openConn() {
    $conn = mysqli_connect(
        "localhost",
        "root",
        "",
        "centrixiv"
    );

    if (!$conn) {
        die("Verbinding mislukt: " . mysqli_connect_error());
    }

    mysqli_set_charset($conn, "utf8mb4");

    return $conn;
}

function closeConn($conn) {
    mysqli_close($conn);
}

function haalProductOptiesOp($productID, $conn) {
    $sql = "SELECT * FROM tblProductOpties WHERE ProductID = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $productID);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $opties = mysqli_fetch_all($result, MYSQLI_ASSOC);

    mysqli_stmt_close($stmt);

    return $opties;
}

function toonProductOpties($opties) {
    foreach ($opties as $optie) {
        echo '<label for="optie_' . $optie['optieID'] . '">';
        echo htmlspecialchars($optie['optienaam']) . ':';
        echo '</label>';

        echo '<input
            type="text"
            id="optie_' . $optie['optieID'] . '"
            name="opties[' . $optie['optieID'] . ']"
            ' . ($optie['verplicht'] ? 'required' : '') . '
        >';

        echo '<br><br>';
    }
}

function voegBestellingToe($conn, $productID, $voornaam, $familienaam, $email, $gekozenOpties) {
    mysqli_begin_transaction($conn);

    try {
        // 1. Controleer of het product bestaat.
        $product = haalProductOp($productID, $conn);

        if (!$product) {
            throw new Exception("Product bestaat niet.");
        }

        // 2. Haal de beschikbare opties van dit product op.
        $beschikbareOpties = haalProductOptiesOp($productID, $conn);
        $optiesPerID = [];

        foreach ($beschikbareOpties as $optie) {
            $optiesPerID[$optie['optieID']] = $optie;
        }

        // 3. Controleer en verzamel de gekozen opties.
        $optiesTekst = [];

        foreach ($gekozenOpties as $optieID => $waarde) {
            if (!isset($optiesPerID[$optieID])) {
                throw new Exception("Ongeldige productoptie.");
            }

            if (!is_string($waarde)) {
                throw new Exception("Ongeldige optiewaarde.");
            }

            $waarde = trim($waarde);

            if ($optiesPerID[$optieID]['verplicht'] && $waarde === '') {
                throw new Exception("Een verplichte optie ontbreekt.");
            }

            if ($waarde !== '') {
                $optienaam = $optiesPerID[$optieID]['optienaam'];
                $optiesTekst[] = $optienaam . ': ' . $waarde;
            }
        }

        // Controleer of alle verplichte opties aanwezig zijn.
        foreach ($beschikbareOpties as $optie) {
            if (
                $optie['verplicht'] &&
                trim($gekozenOpties[$optie['optieID']] ?? '') === ''
            ) {
                throw new Exception("Een verplichte optie ontbreekt.");
            }
        }

        $optiesOmschrijving = implode("\n", $optiesTekst);

        // 4. Bepaal datum, tijd, betalingsstatus.

        $datum = date('Y-m-d');
        $tijd = date('H:i:s');
        $betalingsstatus = 'Open';

        // 5. Voeg de bestelling toe aan tblbestellingen.
        $sql = "INSERT INTO tblbestellingen
                (klantvoornaam, klantfamilienaam,
                 klantemail, datum, tijd, betalingsstatus)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "ssssss",
            $voornaam,
            $familienaam,
            $email,
            $datum,
            $tijd,
            $betalingsstatus
        );

        mysqli_stmt_execute($stmt);

        // Haal de ID op die MySQL heeft aangemaakt.
        $bestellingID = mysqli_insert_id($conn);

        mysqli_stmt_close($stmt);

        // 7. Voeg product en opties toe aan tblproductbestellingen.
        $sql = "INSERT INTO tblproductbestellingen
                (bestellingsid, productid, optiesomschrijving)
                VALUES (?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "iis",
            $bestellingID,
            $productID,
            $optiesOmschrijving
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $bestelnummer = maakBestelnummer($bestellingID, $datum);

        // 8. Alles is gelukt: sla de wijzigingen definitief op.
        mysqli_commit($conn);

        return [
            'bestellingsID' => $bestellingID,
            'bestelnummer' => $bestelnummer
        ];

    } catch (Throwable $e) {
        mysqli_rollback($conn);

        error_log("Bestelling opslaan mislukt: " . $e->getMessage());

        return false;
    }
}
?>