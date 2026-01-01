<?php
require __DIR__ . "/../app/config/database.php";

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = $conn->prepare("SELECT * FROM productos WHERE id = ? AND (estado = 'activo' OR estado IS NULL)");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: index.php");
    exit;
}

// Procesar imágenes
$mainImage = 'https://via.placeholder.com/600x800?text=No+Image';
if (!empty($product['imagen1'])) {
    $mainImage = '../uploads/productos/' . $product['imagen1'];
} elseif (!empty($product['imagenes'])) {
    $parts = explode(',', $product['imagenes']);
    if (!empty($parts[0]))
        $mainImage = '../uploads/productos/' . trim($parts[0]);
} elseif (!empty($product['imagen'])) {
    $mainImage = '../uploads/productos/' . $product['imagen'];
}

// Colores y Tallas (Si están separados por comas o son únicos)
// Asumimos que si hay comas es una lista, si no, es único.
// Para este ejemplo simple, mostramos lo que hay en la BD.

// Galería de imágenes (prioriza imagen1, luego imagenes, luego imagen2/3 si existen)
$gallery = [];
$pushImage = function($img) use (&$gallery) {
    $img = trim((string)$img);
    if ($img !== '') {
        $full = (strpos($img, '../uploads/productos/') === 0) ? $img : ('../uploads/productos/' . $img);
        if (!in_array($full, $gallery, true)) { $gallery[] = $full; }
    }
};
if (!empty($product['imagen1'])) { $pushImage($product['imagen1']); }
if (!empty($product['imagenes'])) { foreach (explode(',', $product['imagenes']) as $im) { $pushImage($im); } }
if (!empty($product['imagen2'])) { $pushImage($product['imagen2']); }
if (!empty($product['imagen3'])) { $pushImage($product['imagen3']); }
if (empty($gallery)) { $gallery[] = $mainImage; }
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['nombre']) ?> - LUNA</title>
    <link rel="stylesheet" href="../assets/css/shop.css">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* Layout del detalle */
        .product-detail-container{max-width:1400px;margin:40px auto;display:grid;grid-template-columns:1.5fr 1fr;gap:60px;padding:0 20px}
        @media (max-width: 992px){.product-detail-container{grid-template-columns:1fr;gap:24px;margin:24px auto}}

        /* Imagen principal sin cajas blancas y miniaturas verticales */
        .detail-image{background:transparent;border:0;border-radius:0;padding:0;box-shadow:none}
        .image-layout{display:flex;gap:24px;align-items:flex-start}
        .main-image{flex:1}
        .main-image img{width:100%;height:auto;display:block;border-radius:12px;max-height:750px;object-fit:contain}
        @media (max-width: 992px){.image-layout{flex-direction:column-reverse}}

        /* Miniaturas verticales */
        .thumbs-vertical{display:flex;flex-direction:column;gap:14px;max-height:750px;overflow:auto;padding-right:4px}
        .thumb{cursor:pointer;border:2px solid transparent;border-radius:12px;object-fit:cover;width:100px!important;height:130px!important;flex:0 0 auto;background:transparent;transition:all .2s ease}
        .thumb:hover{border-color:#d1d5db;transform:scale(1.02)}
        .thumb.active{border-color:#111827;box-shadow:0 0 0 3px rgba(17,24,39,0.2)}

        /* Info */
        .detail-info{padding-top:8px}
        .detail-info .detail-title{font-size:44px;line-height:1.2;margin:8px 0 12px;font-weight:900;letter-spacing:-0.5px}
        .detail-info .detail-price{font-weight:900;font-size:28px;margin:8px 0 24px;color:#111827}
        .detail-info .category{color:#9ca3af;font-size:11px;letter-spacing:1.5px;text-transform:uppercase;font-weight:600}
        .detail-description{color:#6b7280;margin-bottom:24px;line-height:1.6;font-size:15px}

        /* Controles */
        .detail-info label{font-weight:700;color:#111827;margin-bottom:8px;display:block;font-size:14px}
        .form-control{appearance:none;-webkit-appearance:none;-moz-appearance:none;height:48px;padding:12px 14px;border:1px solid #e5e7eb;border-radius:12px;background:#fff;min-width:160px;font-size:15px}
        .form-control:focus{outline:none;border-color:#111827;box-shadow:0 0 0 3px rgba(17,24,39,0.1)}
        .add-to-cart-btn{display:block;width:100%;height:56px;border-radius:14px;background:#111827;color:#fff;border:0;letter-spacing:.08em;font-weight:700;font-size:15px;cursor:pointer;transition:background .2s ease}
        .add-to-cart-btn:hover{background:#0b1220}
        .actions{display:flex;gap:14px;flex-wrap:wrap;margin-top:16px}
        .btn-secondary{display:inline-block;height:56px;line-height:56px;padding:0 24px;border-radius:14px;border:1.5px solid #e5e7eb;background:#fff;color:#111827;text-align:center;font-weight:700;font-size:15px;cursor:pointer;transition:all .2s ease;text-decoration:none}
        .btn-secondary:hover{background:#f9fafb;border-color:#111827}
        .stock-hint{color:#9ca3af;font-size:13px;font-weight:500}
        .form-group{margin-bottom:24px}
    </style>
</head>

<body>

    <?php include __DIR__ . '/partials/header.php'; ?>

    <div class="container product-detail-container">
        <!-- Imagen -->
        <div class="detail-image">
            <div class="image-layout">
                <?php if (count($gallery) > 1): ?>
                <div class="thumbs-vertical">
                    <?php $i=0; foreach ($gallery as $g): ?>
                        <img class="thumb <?= $i===0 ? 'active' : '' ?>" src="<?= htmlspecialchars($g) ?>" alt="<?= htmlspecialchars($product['nombre']) ?>" width="72" height="90" loading="lazy" decoding="async" data-large="<?= htmlspecialchars($g) ?>">
                    <?php $i++; endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="main-image">
                    <img id="product-main-img" src="<?= htmlspecialchars($gallery[0]) ?>" alt="<?= htmlspecialchars($product['nombre']) ?>" width="600" height="800">
                </div>
            </div>
        </div>

        <!-- Info -->
        <div class="detail-info">
            <p class="category"><?= htmlspecialchars($product['categoria'] ?? 'General') ?></p>
            <h1 class="detail-title"><?= htmlspecialchars($product['nombre']) ?></h1>
            <div class="detail-price">S/ <?= number_format($product['precio'], 2) ?></div>

            <div class="detail-description">
                <p><?= nl2br(htmlspecialchars($product['descripcion'])) ?></p>
            </div>

            <form action="carrito.php" method="POST">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="id" value="<?= $product['id'] ?>">
                <input type="hidden" name="nombre" value="<?= htmlspecialchars($product['nombre']) ?>">
                <input type="hidden" name="precio" value="<?= $product['precio'] ?>">

                <?php if (!empty($product['color'])): ?>
                    <div style="margin-bottom: 20px;">
                        <label style="font-weight: 500; display: block; margin-bottom: 5px;">Color</label>
                        <select name="color" class="form-control" style="width: auto; min-width: 150px;">
                            <?php
                            // Intento de separar por comas si el usuario metió varios
                            $colors = explode(',', $product['color']);
                            foreach ($colors as $c): ?>
                                <option value="<?= trim($c) ?>"><?= trim($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if (!empty($product['talla'])): ?>
                    <div style="margin-bottom: 30px;">
                        <label style="font-weight: 500; display: block; margin-bottom: 5px;">Talla</label>
                        <select name="talla" class="form-control" style="width: auto; min-width: 150px;">
                            <?php
                            $sizes = explode(',', $product['talla']);
                            foreach ($sizes as $s): ?>
                                <option value="<?= trim($s) ?>"><?= trim($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 500; display: block; margin-bottom: 5px;">Cantidad</label>
                    <input type="number" name="cantidad" class="form-control" value="1" min="1"
                        max="<?= max(1, $product['stock']) ?>"
                        style="width: 80px; display: inline-block; margin-right: 10px;">
                    <span style="color: #666; font-size: 14px;">(Stock disponible: <?= $product['stock'] ?>)</span>
                </div>

                <div class="actions">
                    <button type="submit" class="add-to-cart-btn" <?= $product['stock'] < 1 ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>
                        <?= $product['stock'] < 1 ? 'Agotado' : 'Agregar al Carrito' ?>
                    </button>
                    <a href="index.php" class="btn-secondary" aria-label="Seguir comprando">Seguir comprando</a>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function(){
        var main = document.getElementById('product-main-img');
        var thumbs = document.querySelectorAll('.thumbs-vertical .thumb, .thumbs .thumb');
        if (thumbs.length){
            if (!document.querySelector('.thumbs-vertical .thumb.active, .thumbs .thumb.active')) thumbs[0].classList.add('active');
            thumbs.forEach(function(t){
                t.addEventListener('click', function(){
                    thumbs.forEach(function(o){o.classList.remove('active');});
                    this.classList.add('active');
                    var src = this.getAttribute('data-large') || this.src;
                    if (main) main.src = src;
                });
            });
        }
    });
    </script>
    <?php include __DIR__ . '/partials/footer.php'; ?>
    <script src="../assets/js/shop.js" defer></script>

</body>

</html>