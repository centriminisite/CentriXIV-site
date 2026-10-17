<?php
require_once "functies.php";

session_start();

$conn = openConn();

if (!isset($_POST['product_id'])) {
    header("Location: producten.html");
    exit;
}

$productID = (int) $_POST['product_id'];
$product = haalProductOp($productID, $conn);

if (!$product) {
    closeConn($conn);
    exit("Product niet gevonden.");
}

$voornaam = trim($_POST['voornaam'] ?? '');
$familienaam = trim($_POST['familienaam'] ?? '');
$email = trim($_POST['email'] ?? '');
$gekozenOpties = $_POST['opties'] ?? [];

if ($voornaam === '' || $familienaam === '' ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    closeConn($conn);
    exit("Vul alle gegevens correct in.");
}

// Controleer de gekozen opties tegen de database.
$beschikbareOpties = haalProductOptiesOp($productID, $conn);
$optiesPerID = [];

foreach ($beschikbareOpties as $optie) {
    $optiesPerID[$optie['optieID']] = $optie;
}

foreach ($gekozenOpties as $optieID => $waarde) {
    if (!isset($optiesPerID[$optieID])) {
        closeConn($conn);
        exit("Ongeldige productoptie.");
    }
}

// Controleer of verplichte opties ingevuld zijn.
foreach ($beschikbareOpties as $optie) {
    if ($optie['verplicht'] &&
        trim($gekozenOpties[$optie['optieID']] ?? '') === '') {
        closeConn($conn);
        exit("Vul alle verplichte productopties in.");
    }
}

$resultaat = voegBestellingToe($conn, $productID, $voornaam, $familienaam, $email, $gekozenOpties);

if ($resultaat === false) {
    closeConn($conn);
    exit("De bestelling kon niet worden opgeslagen.");
}

$_SESSION['bestellingID'] = $resultaat['bestellingsID'];
$_SESSION['bestelnummer'] = $resultaat['bestelnummer'];

closeConn($conn);

header("Location: ../../Presentation/Pages/bestelling-betalen.php");
exit;
?>