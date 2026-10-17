<?php
    require_once "../../Logic/Serverside/functies.php";

    $conn = openConn();
    $besteld_product_id = $_POST["product_id"];
    $product = haalProductOp($besteld_product_id, $conn);
    $opties = haalProductOptiesOp($besteld_product_id, $conn);
?>

<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bestellen · CentriXIV</title>
    <link rel="stylesheet" href="../Stylesheets/style.css">
</head>
<body>
    <h1>Bestelling plaatsen</h1>
    <h2><?php echo htmlspecialchars($product['Productnaam']); ?></h2>
    <img src="../Images/producten/<?php echo htmlspecialchars($product['imgnaam']); ?>">
    <p><?php echo htmlspecialchars($product['productomschrijving']); ?></p>
    <p>Prijs: € <?php echo number_format((float)$product['prijs'], 2, ',', '.'); ?></p>
    <hr>

    <form action="../../Logic/Serverside/bestelling-verwerken.php" method="POST">
        <input  type="hidden" name="product_id" value="<?php echo (int)$product['ProductID']; ?>"   >

        <h2>Jouw gegevens</h2>
        <label for="voornaam">Voornaam:</label>
        <input type="text" id="voornaam" name="voornaam" required>
        <br><br>
        <label for="familienaam">Familienaam:</label>
        <input type="text" id="familienaam" name="familienaam" required>
        <br><br>
        <label for="email">E-mailadres:</label>
        <input type="email" id="email" name="email" required>
        <br><br>

        <h2>Productopties</h2>
        <?php toonProductOpties($opties); ?>

        <button type="submit">Bestelling bevestigen</button>
    </form>
</body>
</html>