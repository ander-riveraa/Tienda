<?php
/**
 * Cerrar sesión
 * Funciona para usuarios y administradores
 */
session_start();
session_destroy();
header("Location: index.php");
exit;
