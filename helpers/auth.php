<?php
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

function soloAdmin() {
    if ($_SESSION['rol'] !== 'admin') {
        http_response_code(403);
        exit("Acceso denegado");
    }
}
