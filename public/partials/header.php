<?php
// Asegurar sesión para mostrar el contador del carrito
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$carritoCount = 0;
if (!empty($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $carritoCount += (int)($item['cantidad'] ?? 1);
    }
}

$categoriaActual = $_GET['categoria'] ?? '';
$busqueda = htmlspecialchars($_GET['busqueda'] ?? '', ENT_QUOTES, 'UTF-8');
?>

<nav class="navbar">
    <a href="index.php" class="logo-container" aria-label="Ir al inicio">
        <span class="logo-main">Lun</span>
        <span class="logo-sub">FASHION</span>
    </a>

    <div class="nav-links">
        <a href="index.php" <?= $categoriaActual === '' ? 'style="font-weight:600; color:#000;"' : '' ?>>Inicio</a>
        <a href="?categoria=Hombre" <?= $categoriaActual === 'Hombre' ? 'style="font-weight:600; color:#000;"' : '' ?>>Hombre</a>
        <a href="?categoria=Mujer" <?= $categoriaActual === 'Mujer' ? 'style="font-weight:600; color:#000;"' : '' ?>>Mujer</a>
        <a href="?categoria=Niño" <?= $categoriaActual === 'Niño' ? 'style="font-weight:600; color:#000;"' : '' ?>>Niño</a>
    </div>

    <div class="nav-icons d-flex align-items-center gap-3">
        <form action="index.php" method="GET" style="display:inline-flex; align-items:center; border-bottom:1px solid #ccc;">
            <input type="text" name="busqueda" placeholder="Buscar..." value="<?= $busqueda ?>" style="border:none; outline:none; padding:5px; font-family: inherit; font-size: 14px;" aria-label="Buscar productos">
            <button type="submit" style="background:none; border:none; cursor:pointer;" aria-label="Buscar">
                <i class="bi bi-search"></i>
            </button>
        </form>
        <a href="login.php" aria-label="Iniciar sesión"><i class="bi bi-person"></i></a>
        <a href="carrito.php" class="cart-link" aria-label="Ver carrito">
            <i class="bi bi-bag"></i>
            <?php if ($carritoCount > 0): ?>
            <span class="cart-count" aria-label="Artículos en el carrito"><?= $carritoCount ?></span>
            <?php endif; ?>
        </a>
    </div>
</nav>
