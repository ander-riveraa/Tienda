<?php
// session_start(); // Eliminado porque database.php ya inicia la sesión
require __DIR__ . "/../app/config/database.php";

// Inicializar carrito
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Lógica de Acciones (Agregar, Eliminar, Actualizar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $id = (int) $_POST['id'];
        $nombre = $_POST['nombre'];
        $precio = (float) $_POST['precio'];
        $color = $_POST['color'] ?? 'N/A';
        $talla = $_POST['talla'] ?? 'N/A';
        $cantidad = (int) ($_POST['cantidad'] ?? 1);
        if ($cantidad < 1) $cantidad = 1;

        // Clave única para diferenciar variaciones
        $key = $id . '_' . $color . '_' . $talla;

        if (isset($_SESSION['carrito'][$key])) {
            $_SESSION['carrito'][$key]['cantidad'] += $cantidad;
        } else {
            $_SESSION['carrito'][$key] = [
                'id' => $id,
                'nombre' => $nombre,
                'precio' => $precio,
                'color' => $color,
                'talla' => $talla,
                'cantidad' => $cantidad,
                'imagen' => '' // Podría pasarse también pero por simplicidad lo omitimos
            ];
        }
    } elseif ($action === 'remove') {
        $key = $_POST['key'] ?? '';
        unset($_SESSION['carrito'][$key]);
    } elseif ($action === 'update') {
        $key = $_POST['key'] ?? '';
        $cantidad = max(1, (int)($_POST['cantidad'] ?? 1));
        if (isset($_SESSION['carrito'][$key])) {
            $_SESSION['carrito'][$key]['cantidad'] = $cantidad;
        }
    }

    // Redirigir para evitar reenvío de formulario
    header("Location: carrito.php");
    exit;
}

$total = 0;
foreach ($_SESSION['carrito'] as $item) {
    $total += $item['precio'] * $item['cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu Carrito - LUNA</title>
    <link rel="stylesheet" href="../assets/css/shop.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<body>

    <?php include __DIR__ . '/partials/header.php'; ?>

    <div class="container" style="margin-top: 50px;">
        <h1 class="section-title">Tu Bolsa de Compras</h1>

        <?php if (empty($_SESSION['carrito'])): ?>
            <div style="text-align: center; margin: 100px 0;">
                <p style="color: #999; margin-bottom: 20px;">Tu bolsa está vacía.</p>
                <a href="index.php" class="add-to-cart-btn"
                    style="display: inline-block; width: auto; padding: 10px 30px;">Explorar Colección</a>
            </div>
        <?php else: ?>
            <div style="max-width: 800px; margin: 0 auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid #eee; text-align: left;">
                            <th style="padding: 15px 0;">Producto</th>
                            <th style="padding: 15px 0;">Precio</th>
                            <th style="padding: 15px 0;">Cant.</th>
                            <th style="padding: 15px 0; text-align: right;">Total</th>
                            <th style="padding: 15px 0;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['carrito'] as $key => $item): ?>
                            <tr style="border-bottom: 1px solid #f9f9f9;">
                                <td style="padding: 20px 0;">
                                    <strong><?= htmlspecialchars($item['nombre']) ?></strong><br>
                                    <small style="color: #888;">Color: <?= $item['color'] ?> | Talla:
                                        <?= $item['talla'] ?></small>
                                </td>
                                <td style="padding: 20px 0;">S/ <?= number_format($item['precio'], 2) ?></td>
                                <td style="padding: 20px 0;">
                                    <form method="POST" style="display:inline-flex; align-items:center; gap:8px;" data-autosubmit="true">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="key" value="<?= htmlspecialchars($key) ?>">
                                        <input type="number" name="cantidad" value="<?= (int)$item['cantidad'] ?>" min="1" max="99" data-cart-qty style="width:64px; padding:6px 8px; border:1px solid #e5e7eb; border-radius:8px;">
                                        <button type="submit" class="add-to-cart-btn" style="width:auto; padding:6px 10px;">Actualizar</button>
                                    </form>
                                </td>
                                <td style="padding: 20px 0; text-align: right;">S/
                                    <?= number_format($item['precio'] * $item['cantidad'], 2) ?>
                                </td>
                                <td style="padding: 20px 0; text-align: right;">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="key" value="<?= htmlspecialchars($key) ?>">
                                        <button type="submit"
                                            style="background: none; border: none; color: red; cursor: pointer; font-size: 18px;">&times;</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin-top: 40px; text-align: right;">
                    <h3 style="font-family: var(--font-serif); font-size: 24px;">Total: S/ <?= number_format($total, 2) ?>
                    </h3>
                    <p style="color: #999; font-size: 12px; margin-bottom: 20px;">Impuestos incluidos.</p>

                    <?php
                    // Generar mensaje para WhatsApp
                    $msg = "Hola LUNA, quiero procesar mi pedido: \n";
                    foreach ($_SESSION['carrito'] as $item) {
                        $msg .= "- {$item['cantidad']} x {$item['nombre']} ({$item['color']}, {$item['talla']})\n";
                    }
                    $msg .= "Total: S/ " . number_format($total, 2);
                    $waLink = "https://wa.me/51999999999?text=" . urlencode($msg);
                    ?>

                    <a href="<?= $waLink ?>" target="_blank" class="add-to-cart-btn"
                        style="display: inline-block; width: auto; background-color: #25D366; margin-right: 10px;">
                        <i class="bi bi-whatsapp"></i> Pedir por WhatsApp
                    </a>

                    <!-- Botón Stripe Simulado -->
                    <button class="add-to-cart-btn" style="display: inline-block; width: auto; background-color: #635bff;"
                        onclick="checkoutStripe()">
                        <i class="bi bi-credit-card"></i> Pagar con Stripe
                    </button>

                    <script src="https://js.stripe.com/v3/"></script>
                    <script>
                        function checkoutStripe() {
                            // Simulación de Checkout
                            alert('Redirigiendo a Stripe Checkout... (Simulación)');
                        }
                    </script>
                </div>
            </div>
        <?php endif; ?>
    </div>

<?php include __DIR__ . '/partials/footer.php'; ?>
<script src="../assets/js/shop.js" defer></script>
</body>

</html>