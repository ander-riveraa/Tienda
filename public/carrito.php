<?php
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

        // Obtener imagen del producto
        $stmt = $conn->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $imgResult = $stmt->get_result();
        $imgData = $imgResult->fetch_assoc();
        $imagen = '';
        if (!empty($imgData['imagen1'])) {
            $imagen = '../uploads/productos/' . $imgData['imagen1'];
        } elseif (!empty($imgData['imagenes'])) {
            $parts = explode(',', $imgData['imagenes']);
            if (!empty($parts[0])) {
                $imagen = '../uploads/productos/' . trim($parts[0]);
            }
        } elseif (!empty($imgData['imagen'])) {
            $imagen = '../uploads/productos/' . $imgData['imagen'];
        }

        // Clave única para diferenciar variaciones
        $key = $id . '_' . $color . '_' . $talla;
        $stock = isset($imgData['stock']) ? (int)$imgData['stock'] : 0;
        $existing = isset($_SESSION['carrito'][$key]) ? (int)$_SESSION['carrito'][$key]['cantidad'] : 0;
        $finalQty = $stock > 0 ? min($existing + $cantidad, $stock) : ($existing + $cantidad);
        if (isset($_SESSION['carrito'][$key])) {
            $_SESSION['carrito'][$key]['cantidad'] = $finalQty;
            $_SESSION['carrito'][$key]['stock'] = $stock;
        } else {
            $_SESSION['carrito'][$key] = [
                'id' => $id,
                'nombre' => $nombre,
                'precio' => $precio,
                'color' => $color,
                'talla' => $talla,
                'cantidad' => $finalQty,
                'imagen' => $imagen,
                'stock' => $stock
            ];
        }
        if ($stock > 0 && ($existing + $cantidad) > $stock) {
            $_SESSION['cart_notice'] = 'Cantidad ajustada al stock disponible (' . $stock . ').';
        }
    } elseif ($action === 'remove') {
        $key = $_POST['key'] ?? '';
        unset($_SESSION['carrito'][$key]);
    } elseif ($action === 'update') {
        $key = $_POST['key'] ?? '';
        $cantidad = max(1, (int)($_POST['cantidad'] ?? 1));
        if (isset($_SESSION['carrito'][$key])) {
            $parts = explode('_', (string)$key);
            $pid = (int)($parts[0] ?? 0);
            $stock = 0;
            $st = $conn->prepare("SELECT stock FROM productos WHERE id = ?");
            if ($st) {
                $st->bind_param("i", $pid);
                $st->execute();
                $rs = $st->get_result();
                $stock = (int)($rs->fetch_assoc()['stock'] ?? 0);
                $st->close();
            }
            $newQty = ($stock > 0) ? min($cantidad, $stock) : $cantidad;
            $_SESSION['carrito'][$key]['cantidad'] = $newQty;
            $_SESSION['carrito'][$key]['stock'] = $stock;
            if ($stock > 0 && $cantidad > $stock) {
                $_SESSION['cart_notice'] = 'Cantidad ajustada al stock disponible (' . $stock . ').';
            }
        }
    }

    // Redirigir para evitar reenvío de formulario
    header("Location: carrito.php");
    exit;
}

// Calcular totales
$total = 0;
foreach ($_SESSION['carrito'] as $item) {
    $total += $item['precio'] * $item['cantidad'];
}

$itemCount = count($_SESSION['carrito']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu Carrito - LUNA</title>
    <link rel="stylesheet" href="../assets/css/shop.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .container {
            max-width: 900px;
            margin: 50px auto;
            padding: 0 20px;
        }
        
        h1.section-title {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 40px;
            color: #111;
        }
        
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        
        .cart-table thead th {
            text-align: left;
            padding: 15px 0;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            font-size: 14px;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .cart-table tbody td {
            padding: 20px 0;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }
        
        .product-name {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 5px;
            color: #111;
        }
        
        .product-attributes {
            font-size: 13px;
            color: #888;
        }
        
        .product-attributes span {
            margin-right: 15px;
        }
        
        .price-cell {
            font-weight: 600;
            color: #111;
        }
        
        .quantity-form {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-input {
            width: 60px;
            padding: 8px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            text-align: center;
            font-size: 14px;
        }
        
        .btn-update {
            padding: 8px 16px;
            background: #111;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-update:hover {
            background: #374151;
        }
        
        .total-cell {
            font-weight: 700;
            font-size: 16px;
            color: #111;
        }
        
        .remove-btn {
            background: none;
            border: none;
            color: #dc2626;
            font-size: 20px;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .remove-btn:hover {
            opacity: 0.7;
        }
        
        .cart-summary {
            text-align: right;
            margin-bottom: 30px;
        }
        
        .total-amount {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 600;
            color: #111;
            margin-bottom: 8px;
        }
        
        .tax-info {
            font-size: 12px;
            color: #999;
            margin-bottom: 30px;
        }
        
        .payment-buttons {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        
        .btn-payment {
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-whatsapp {
            background: #111;
            color: #fff;
        }

        .btn-whatsapp:hover {
            background: #333;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-stripe {
            background: #222;
            color: white;
        }

        .btn-stripe:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.25);
        }
        
        .empty-cart {
            text-align: center;
            padding: 100px 20px;
        }
        
        .empty-cart h2 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #374151;
        }
        
        .empty-cart p {
            color: #6b7280;
            margin-bottom: 30px;
        }
        
        .btn-explore {
            display: inline-block;
            padding: 12px 24px;
            background: #111;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .cart-table {
                font-size: 14px;
            }
            
            .cart-table thead {
                display: none;
            }
            
            .cart-table tbody td {
                display: block;
                padding: 10px 0;
                text-align: left !important;
            }
            
            .cart-table tbody tr {
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 15px;
                margin-bottom: 15px;
                display: block;
            }
            
            .payment-buttons {
                flex-direction: column;
            }
            
            .btn-payment {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/partials/header.php'; ?>
    
    <div class="container">
        <h1 class="section-title">Tu Bolsa de Compras</h1>
        
        <?php if (empty($_SESSION['carrito'])): ?>
            <div class="empty-cart">
                <h2>Tu bolsa está vacía</h2>
                <p>Agrega productos a tu carrito para comenzar tu compra</p>
                <a href="index.php" class="btn-explore">Explorar Colección</a>
            </div>
        <?php else: ?>
            <?php if (!empty($_SESSION['cart_notice'])): ?>
                <div style="background:#f3f4f6; color:#111; padding:10px 12px; border-radius:8px; margin-bottom:15px; font-size:13px;">
                    <?= htmlspecialchars($_SESSION['cart_notice']) ?>
                </div>
                <?php unset($_SESSION['cart_notice']); ?>
            <?php endif; ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th>Cant.</th>
                        <th style="text-align: right;">Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['carrito'] as $key => $item): ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <?php if (!empty($item['imagen'])): ?>
                                        <img src="<?= htmlspecialchars($item['imagen']) ?>" alt="<?= htmlspecialchars($item['nombre']) ?>" style="width:64px; height:80px; object-fit:cover; border:1px solid #e5e7eb; border-radius:6px; background:#f5f5f5;">
                                    <?php else: ?>
                                        <div style="width:64px; height:80px; border:1px solid #e5e7eb; border-radius:6px; background:#f5f5f5;"></div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="product-name"><?= htmlspecialchars($item['nombre']) ?></div>
                                        <div class="product-attributes">
                                            <?php if ($item['color'] !== 'N/A'): ?>
                                                <span>Color: <?= htmlspecialchars($item['color']) ?></span>
                                            <?php endif; ?>
                                            <?php if ($item['talla'] !== 'N/A'): ?>
                                                <span>Talla: <?= htmlspecialchars($item['talla']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="price-cell">S/ <?= number_format($item['precio'], 2) ?></td>
                            <td>
                                <form method="POST" class="quantity-form">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="key" value="<?= htmlspecialchars($key) ?>">
                                    <?php 
                                        $itemStock = isset($item['stock']) ? (int)$item['stock'] : 0; 
                                        if (!$itemStock && isset($item['id'])) { 
                                            $st2 = $conn->prepare("SELECT stock FROM productos WHERE id = ?"); 
                                            if ($st2) { 
                                                $iid = (int)$item['id']; 
                                                $st2->bind_param("i", $iid); 
                                                $st2->execute(); 
                                                $rs2 = $st2->get_result(); 
                                                $itemStock = (int)($rs2->fetch_assoc()['stock'] ?? 0); 
                                                $st2->close(); 
                                            } 
                                        } 
                                        $maxAttr = ($itemStock > 0) ? $itemStock : 99; 
                                    ?>
                                    <input type="number" name="cantidad" value="<?= (int)$item['cantidad'] ?>" min="1" max="<?= $maxAttr ?>" class="quantity-input" onchange="this.form.submit();">
                                    <button type="submit" class="btn-update">ACTUALIZAR</button>
                                </form>
                            </td>
                            <td class="total-cell" style="text-align: right;">
                                S/ <?= number_format($item['precio'] * $item['cantidad'], 2) ?>
                            </td>
                            <td style="text-align: right;">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="key" value="<?= htmlspecialchars($key) ?>">
                                    <button type="submit" class="remove-btn" title="Eliminar">&times;</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="cart-summary">
                <div class="total-amount">Total: S/ <?= number_format($total, 2) ?></div>
                <div class="tax-info">Impuestos incluidos.</div>
                
                <?php
                // Generar mensaje para WhatsApp
                $msg = "Hola LUNA, quiero procesar mi pedido:\n\n";
                foreach ($_SESSION['carrito'] as $item) {
                    $msg .= "• {$item['cantidad']}x {$item['nombre']}";
                    if ($item['color'] !== 'N/A') $msg .= " (Color: {$item['color']})";
                    if ($item['talla'] !== 'N/A') $msg .= " (Talla: {$item['talla']})";
                    $msg .= "\n   S/ " . number_format($item['precio'] * $item['cantidad'], 2) . "\n\n";
                }
                $msg .= "Total: S/ " . number_format($total, 2);
                $waLink = "https://wa.me/51999999999?text=" . urlencode($msg);
                ?>
                
                <div class="payment-buttons">
                    <a href="<?= $waLink ?>" target="_blank" class="btn-payment btn-whatsapp">
                        <i class="bi bi-whatsapp"></i>
                        PEDIR POR WHATSAPP
                    </a>
                    <button type="button" class="btn-payment btn-stripe" onclick="checkoutStripe()">
                        <i class="bi bi-credit-card"></i>
                        PAGAR CON STRIPE
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function checkoutStripe() {
            alert('Redirigiendo a Stripe Checkout...\n(Simulación - Integra Stripe aquí)');
        }
    </script>
    
    <?php include __DIR__ . '/partials/footer.php'; ?>
    <script src="../assets/js/shop.js" defer></script>
    <!-- Updated: 2025-12-31 -->
</body>
</html>
