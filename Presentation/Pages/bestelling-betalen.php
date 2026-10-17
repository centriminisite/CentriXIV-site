<?php
session_start();

require_once "../../Logic/Serverside/functies.php";

$bestellingID = (int) $_SESSION['bestellingID'];
$bestelnummer = $_SESSION['bestelnummer'];

$conn = openConn();

// Haal de bestelling en klantgegevens op.
$sql = "SELECT * FROM tblbestellingen WHERE bestellingsID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $bestellingID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$bestelling = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$bestelling) {
    closeConn($conn);
    exit("Bestelling niet gevonden.");
}

// Haal het product en de gekozen opties van de bestelling op.
$sql = "SELECT pb.productid, pb.optiesomschrijving
        FROM tblproductbestellingen pb
        WHERE pb.bestellingsid = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $bestellingID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$productBestelling = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$productBestelling) {
    closeConn($conn);
    exit("Product van de bestelling niet gevonden.");
}

$product = haalProductOp((int) $productBestelling['productid'], $conn);

if (!$product) {
    closeConn($conn);
    exit("Product niet gevonden.");
}

// Zet de opgeslagen opties om naar regels tekst.
$gekozenOpties = [];
$regels = explode("\n", $productBestelling['optiesomschrijving'] ?? '');

foreach ($regels as $regel) {
    $delen = explode(': ', $regel, 2);

    if (count($delen) === 2) {
        $gekozenOpties[$delen[0]] = $delen[1];
    }
}

$voornaam = $bestelling['klantvoornaam'];
$familienaam = $bestelling['klantfamilienaam'];
$email = $bestelling['klantemail'];

closeConn($conn);
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Betaling · CentriXIV</title>
    <link rel="stylesheet" href="../Stylesheets/style.css">
</head>
<body>

    <h1>Overzicht van je bestelling</h1>

    <p>Bestelnummer: <?php echo htmlspecialchars($bestelnummer); ?></p>

    <h2><?php echo htmlspecialchars($product['Productnaam']); ?></h2>

    <?php if (!empty($product['imgnaam'])): ?>
        <img src="../Images/producten/<?php echo htmlspecialchars($product['imgnaam']); ?>">
    <?php endif; ?>

    <p>
        Prijs: € <?php echo number_format((float) $product['prijs'], 2, ',', '.'); ?>
    </p>

    <h3>Jouw gekozen opties</h3>

    <?php if (empty($gekozenOpties)): ?>
        <p>Geen opties gekozen.</p>
    <?php else: ?>
        <?php foreach ($gekozenOpties as $optienaam => $waarde): ?>
            <p>
                <strong><?php echo htmlspecialchars($optienaam); ?>:</strong>
                <?php echo htmlspecialchars($waarde); ?>
            </p>
        <?php endforeach; ?>
    <?php endif; ?>

    <h3>Klantgegevens</h3>

    <p>
        Naam:
        <?php echo htmlspecialchars($voornaam . ' ' . $familienaam); ?>
    </p>
    <p>E-mail: <?php echo htmlspecialchars($email); ?></p>

    <h3>Betaling via overschrijving</h3>

    <p>
        Bedrag: €
        <?php echo number_format((float) $product['prijs'], 2, ',', '.'); ?>
    </p>
    <p>Rekeningnummer: Hier moet het rekeningnummer komen</p>
    <p>Mededeling: <?php echo htmlspecialchars($bestelnummer); ?></p>
    <a href="producten.html">Terug naar producten</a>

</body>
</html>