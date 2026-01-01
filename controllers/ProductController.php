<?php
require __DIR__ . "/../app/config/database.php";
require "../models/Product.php";

$product = new Product($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['crear'])) {
        $product->create($_POST);
        header("Location: admin.php");
    }

    if (isset($_POST['actualizar'])) {
        $product->update($_POST['id'], $_POST);
        header("Location: admin.php");
    }

    if (isset($_POST['eliminar'])) {
        $product->delete($_POST['id']);
        header("Location: admin.php");
    }
}
