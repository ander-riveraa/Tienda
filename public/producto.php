<?php
require __DIR__ . "/../app/config/database.php";

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = $conn->prepare("SELECT * FROM productos WHERE id = ? AND (estado = 'activo' OR estado IS NULL)");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header("Location: index.php");
    exit;
}

// Calcular descuento si existe precio_original
$precio_actual = (float) $product['precio'];
$precio_original = isset($product['precio_original']) && $product['precio_original'] > 0 ? (float) $product['precio_original'] : null;
$descuento = null;
if ($precio_original && $precio_original > $precio_actual) {
    $descuento = round((($precio_original - $precio_actual) / $precio_original) * 100);
}

// Procesar imágenes
$mainImage = 'https://via.placeholder.com/600x800?text=No+Image';
if (!empty($product['imagen1'])) {
    $mainImage = '../uploads/productos/' . $product['imagen1'];
} elseif (!empty($product['imagenes'])) {
    $parts = explode(',', $product['imagenes']);
    if (!empty($parts[0])) {
        $mainImage = '../uploads/productos/' . trim($parts[0]);
    }
} elseif (!empty($product['imagen'])) {
    $mainImage = '../uploads/productos/' . $product['imagen'];
}

// Galería de imágenes
$gallery = [];
$pushImage = function($img) use (&$gallery) {
    $img = trim((string)$img);
    if ($img !== '') {
        $full = (strpos($img, '../uploads/productos/') === 0) ? $img : ('../uploads/productos/' . $img);
        if (!in_array($full, $gallery, true)) { 
            $gallery[] = $full; 
        }
    }
};
if (!empty($product['imagen1'])) { $pushImage($product['imagen1']); }
if (!empty($product['imagenes'])) { 
    foreach (explode(',', $product['imagenes']) as $im) { 
        $pushImage($im); 
    } 
}
if (!empty($product['imagen2'])) { $pushImage($product['imagen2']); }
if (!empty($product['imagen3'])) { $pushImage($product['imagen3']); }
if (empty($gallery)) { $gallery[] = $mainImage; }

// Procesar colores y tallas
$colors = !empty($product['color']) ? array_map('trim', explode(',', $product['color'])) : [];
$sizes = !empty($product['talla']) ? array_map('trim', explode(',', $product['talla'])) : [];

// Mapeo de colores a códigos hex (para los swatches)
$colorMap = [
    'negro' => '#1a1a1a',
    'blanco' => '#ffffff',
    'gris' => '#808080',
    'azul' => '#0066cc',
    'rojo' => '#cc0000',
    'verde' => '#009900',
    'amarillo' => '#ffcc00',
    'rosa' => '#ff99cc',
    'beige' => '#f5f5dc',
    'marron' => '#8b4513',
];
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        .product-page {
            display: grid;
            grid-template-columns: 1fr 0.9fr;
            gap: 32px;
            max-width: 1000px;
            margin: 0 auto;
            padding: 28px 16px;
            min-height: calc(100vh - 220px);
        }
        
        /* Panel Izquierdo - Imágenes */
        .product-images {
            position: relative;
            padding: 0;
            background: transparent;
            display: grid;
            grid-template-columns: 84px 1fr;
            gap: 12px;
            align-items: start;
        }
        
        .main-image-wrapper {
            grid-column: 2;
            grid-row: 1;
            position: relative;
            background: transparent;
            border: none;
            border-radius: 0;
            overflow: hidden;
            aspect-ratio: 4/5;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0;
            max-height: 75vh;
        }
        
        .main-image-wrapper img {
            width: 100%;
            height: auto;
            max-height: 92%;
            object-fit: contain;
        }
        
        .product-tags {
            position: absolute;
            top: 12px;
            left: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            z-index: 10;
        }
        
        .tag {
            padding: 6px 12px;
            border-radius: 18px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        
        .tag-new {
            background: #111;
            color: white;
        }
        
        .tag-discount {
            background: #333;
            color: white;
        }
        
        .thumbnails {
            grid-column: 1;
            grid-row: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
            overflow: visible;
            max-height: none;
            padding-right: 0;
        }
        
        .thumbnail {
            width: 84px;
            height: 84px;
            border-radius: 6px;
            object-fit: cover;
            cursor: pointer;
            border: 1px solid #e5e7eb;
            transition: all 0.25s ease;
            flex-shrink: 0;
            background: transparent;
        }
        .product-tabs { max-width: 1000px; margin: 20px auto 60px; padding: 0 16px; }
        .tab-nav { display:flex; gap:12px; border-bottom:1px solid #e5e7eb; }
        .tab-btn { padding:10px 16px; border:1px solid #e5e7eb; border-bottom:none; background:#fff; color:#111; font-weight:600; font-size:13px; }
        .tab-btn.active { border-color:#111; }
        .tab-content { display:none; padding:18px 0; color:#374151; }
        .tab-content.active { display:block; }
        .tab-grid { display:grid; grid-template-columns: 1fr 1fr; gap:24px; }
        
        .thumbnail:hover,
        .thumbnail.active {
            border-color: #111;
            transform: scale(1.02);
        }
        
        /* Panel Derecho - Información */
        .product-info {
            padding-top: 0;
            position: sticky;
            top: 90px;
            align-self: start;
        }
        
        .product-category {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #666;
            margin-bottom: 12px;
        }
        
        .product-title {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 600;
            line-height: 1.2;
            margin-bottom: 12px;
            color: #111;
        }
        
        .product-price-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }
        
        .product-price {
            font-size: 28px;
            font-weight: 700;
            color: #111;
        }
        
        .product-price-old {
            font-size: 24px;
            color: #9ca3af;
            text-decoration: line-through;
        }
        
        .product-discount {
            background: #111;
            color: #fff;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 700;
        }
        
        .product-description {
            color: #6b7280;
            line-height: 1.7;
            font-size: 14px;
            margin-bottom: 28px;
        }
        
        /* Selectores */
        .selector-group {
            margin-bottom: 20px;
        }
        
        .selector-label {
            display: block;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
            color: #111;
        }
        
        .color-selector {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .color-option {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            border: 3px solid transparent;
            position: relative;
            transition: all 0.3s ease;
            background: #f3f4f6;
        }
        
        .color-option:hover {
            transform: scale(1.1);
        }
        
        .color-option.selected {
            border-color: #111;
        }
        
        .color-option.selected::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-weight: 700;
            font-size: 18px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .color-name {
            margin-top: 8px;
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
        }
        
        .size-selector {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .size-option {
            min-width: 42px;
            padding: 10px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            text-align: center;
            transition: all 0.3s ease;
            color: #374151;
        }
        
        .size-option:hover {
            border-color: #111;
            color: #111;
        }
        
        .size-option.selected {
            background: #111;
            color: white;
            border-color: #111;
        }
        
        .size-guide-link {
            margin-left: auto;
            font-size: 13px;
            color: #333;
            text-decoration: none;
            font-weight: 500;
        }
        
        .size-guide-link:hover {
            text-decoration: underline;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .quantity-btn {
            width: 36px;
            height: 36px;
            border: none;
            background: #f9fafb;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            color: #374151;
            transition: background 0.2s;
        }
        
        .quantity-btn:hover {
            background: #e5e7eb;
        }
        
        .quantity-input {
            width: 54px;
            height: 36px;
            border: none;
            text-align: center;
            font-size: 15px;
            font-weight: 600;
        }
        
        .stock-info {
            font-size: 14px;
            color: #111;
        }
        
        /* Botones de Acción */
        .action-buttons {
            display: flex;
            flex-direction: row;
            gap: 10px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        
        .btn-primary {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .btn-add-cart {
            background: #111;
            color: white;
            flex: 1;
        }
        
        .btn-add-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
        }
        
        .btn-buy-now {
            background: white;
            color: #111;
            border: 2px solid #111;
        }
        
        .btn-buy-now:hover {
            background: #f3f4f6;
        }
        
        .btn-secondary {
            background: white;
            color: #374151;
            border: 2px solid #e5e7eb;
        }
        
        .btn-secondary:hover {
            border-color: #111;
            background: #f9fafb;
        }
        .wa-link { display:inline-block; color:#111; font-size:13px; text-decoration:none; }
        .wa-link:hover { text-decoration: underline; }
        
        /* Información Adicional */
        .product-features {
            border-top: 1px solid #e5e7eb;
            padding-top: 30px;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            color: #374151;
        }
        
        .feature-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            border-radius: 8px;
            font-size: 18px;
        }
        
        .feature-text {
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .product-page {
                grid-template-columns: 1fr;
                gap: 24px;
                padding: 18px 14px;
                max-width: 820px;
            }
            .main-image-wrapper {
                max-height: 55vh;
            }
            .product-title {
                font-size: 24px;
            }
            .thumbnails {
                justify-content: center;
            }
        }
        
        input[type="hidden"] {
            display: none;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/partials/header.php'; ?>
    
    <div class="product-page">
        <!-- Panel Izquierdo - Imágenes -->
        <div class="product-images">
            <div class="main-image-wrapper">
                <img id="main-image" src="<?= htmlspecialchars($gallery[0]) ?>" alt="<?= htmlspecialchars($product['nombre']) ?>">
                
                <div class="product-tags">
                    <?php if ($descuento): ?>
                        <span class="tag tag-discount">-<?= $descuento ?>%</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (count($gallery) > 1): ?>
            <div class="thumbnails">
                <?php $thumbs = array_slice($gallery, 0, 3); foreach ($thumbs as $index => $img): ?>
                    <img class="thumbnail <?= $index === 0 ? 'active' : '' ?>" 
                         src="<?= htmlspecialchars($img) ?>" 
                         alt="Vista <?= $index + 1 ?>"
                         data-image="<?= htmlspecialchars($img) ?>">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Panel Derecho - Información -->
        <div class="product-info">
            <span class="product-category"><?= htmlspecialchars(strtoupper($product['categoria'] ?? 'GENERAL')) ?></span>
            <?php if ($product['estado'] === 'activo'): ?>
                <span class="tag tag-new" style="display: inline-block; margin-left: 10px;">Nuevo</span>
            <?php endif; ?>
            
            <h1 class="product-title"><?= htmlspecialchars($product['nombre']) ?></h1>
            
            <div class="product-price-wrapper">
                <span class="product-price">S/ <?= number_format($precio_actual, 2) ?></span>
                <?php if ($precio_original): ?>
                    <span class="product-price-old">S/ <?= number_format($precio_original, 2) ?></span>
                    <?php if ($descuento): ?>
                        <span class="product-discount">-<?= $descuento ?>%</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <div class="product-description">
                <?= nl2br(htmlspecialchars($product['descripcion'] ?? 'Producto de alta calidad con diseño moderno y versátil.')) ?>
            </div>
            
            <form action="carrito.php" method="POST" id="product-form">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="id" value="<?= $product['id'] ?>">
                <input type="hidden" name="nombre" value="<?= htmlspecialchars($product['nombre']) ?>">
                <input type="hidden" name="precio" value="<?= $product['precio'] ?>">
                <input type="hidden" name="color" id="selected-color" value="<?= !empty($colors) ? htmlspecialchars(trim($colors[0])) : 'N/A' ?>">
                <input type="hidden" name="talla" id="selected-size" value="<?= !empty($sizes) ? htmlspecialchars(trim($sizes[0])) : 'N/A' ?>">
                <input type="hidden" name="cantidad" id="selected-quantity" value="1">
                
                <?php if (!empty($colors)): ?>
                <div class="selector-group">
                    <label class="selector-label">Color</label>
                    <div class="color-selector">
                        <?php foreach ($colors as $index => $color): 
                            $colorLower = strtolower(trim($color));
                            $colorHex = $colorMap[$colorLower] ?? '#e5e7eb';
                            $isSelected = $index === 0;
                        ?>
                            <div>
                                <div class="color-option <?= $isSelected ? 'selected' : '' ?>" 
                                     style="background-color: <?= $colorHex ?>;"
                                     data-color="<?= htmlspecialchars(trim($color)) ?>"
                                     onclick="selectColor(this, '<?= htmlspecialchars(trim($color)) ?>')">
                                </div>
                                <div class="color-name"><?= htmlspecialchars(trim($color)) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($sizes)): ?>
                <div class="selector-group">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <label class="selector-label">Talla</label>
                        <a href="#" class="size-guide-link">Guía de tallas</a>
                    </div>
                    <div class="size-selector">
                        <?php foreach ($sizes as $index => $size): 
                            $isSelected = $index === 0;
                        ?>
                            <button type="button" 
                                    class="size-option <?= $isSelected ? 'selected' : '' ?>"
                                    data-size="<?= htmlspecialchars(trim($size)) ?>"
                                    onclick="selectSize(this, '<?= htmlspecialchars(trim($size)) ?>')">
                                <?= htmlspecialchars(trim($size)) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="quantity-selector">
                    <div class="selector-label" style="margin-bottom: 0;">Cantidad</div>
                    <div class="quantity-controls">
                        <button type="button" class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                        <input type="number" class="quantity-input" id="quantity-input" value="1" min="1" max="<?= max(1, $product['stock'] ?? 99) ?>" readonly>
                        <button type="button" class="quantity-btn" onclick="changeQuantity(1)">+</button>
                    </div>
                    <span class="stock-info"><?= ($product['stock'] ?? 0) ?> disponibles</span>
                </div>
                
                <div class="action-buttons">
                    <button type="submit" class="btn-primary btn-add-cart" <?= ($product['stock'] ?? 0) < 1 ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>
                        <i class="bi bi-cart-plus"></i>
                        AGREGAR AL CARRITO
                    </button>
                    <button type="button" class="btn-primary btn-buy-now" onclick="buyNow()" <?= ($product['stock'] ?? 0) < 1 ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>
                        <i class="bi bi-lightning-charge"></i>
                        COMPRAR AHORA
                    </button>
                    <a href="index.php" class="btn-primary btn-secondary">
                        <i class="bi bi-arrow-left"></i>
                        Continuar Comprando
                    </a>
                </div>
                <a href="#" class="wa-link" onclick="waProduct(); return false;">Pedir por WhatsApp</a>
            </form>
            
            <div class="product-features">
                <div class="feature-item">
                    <div class="feature-icon"><i class="bi bi-truck"></i></div>
                    <div class="feature-text">Envío gratis sobre S/200</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="bi bi-arrow-repeat"></i></div>
                    <div class="feature-text">Devoluciones en 30 días</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="bi bi-check-circle"></i></div>
                    <div class="feature-text">Garantía de calidad</div>
                </div>
            </div>
        </div>
    </div>
    <div class="product-tabs">
        <div class="tab-nav">
            <button class="tab-btn active" data-tab="desc">Descripción</button>
            <button class="tab-btn" data-tab="det">Detalles</button>
            <button class="tab-btn" data-tab="rev">Reseñas</button>
        </div>
        <div id="tab-desc" class="tab-content active">
            <h3 style="font-family:'Playfair Display', serif; font-size:20px; color:#111; margin-bottom:10px;">Descripción del Producto</h3>
            <p><?= nl2br(htmlspecialchars($product['descripcion'] ?? 'Diseño moderno y versátil con materiales de calidad.')) ?></p>
            <div class="tab-grid" style="margin-top:16px;">
                <div>
                    <h4 style="font-size:14px; color:#111; margin-bottom:8px;">Características principales</h4>
                    <ul style="font-size:14px; color:#374151; line-height:1.8;">
                        <li>Alta calidad y resistencia</li>
                        <li>Estilo moderno y cómodo</li>
                        <li>Ajuste versátil</li>
                        <li>Opciones de color</li>
                    </ul>
                </div>
                <div>
                    <h4 style="font-size:14px; color:#111; margin-bottom:8px;">Cuidado del producto</h4>
                    <ul style="font-size:14px; color:#374151; line-height:1.8;">
                        <li>Lavar a máquina ciclo suave</li>
                        <li>Usar agua fría</li>
                        <li>No usar blanqueador</li>
                        <li>Secar a baja temperatura</li>
                    </ul>
                </div>
            </div>
        </div>
        <div id="tab-det" class="tab-content">
            <div class="tab-grid">
                <div>
                    <div style="font-size:14px; color:#111; margin-bottom:6px;">Categoría</div>
                    <div style="color:#374151; font-size:14px;"><?= htmlspecialchars($product['categoria'] ?? 'General') ?></div>
                </div>
                <div>
                    <div style="font-size:14px; color:#111; margin-bottom:6px;">Stock</div>
                    <div style="color:#374151; font-size:14px;"><?= (int)($product['stock'] ?? 0) ?> disponibles</div>
                </div>
                <div>
                    <div style="font-size:14px; color:#111; margin-bottom:6px;">Colores</div>
                    <div style="color:#374151; font-size:14px;"><?= htmlspecialchars(implode(', ', $colors) ?: 'N/A') ?></div>
                </div>
                <div>
                    <div style="font-size:14px; color:#111; margin-bottom:6px;">Tallas</div>
                    <div style="color:#374151; font-size:14px;"><?= htmlspecialchars(implode(', ', $sizes) ?: 'N/A') ?></div>
                </div>
            </div>
        </div>
        <div id="tab-rev" class="tab-content">
            <p style="color:#374151;">Aún no hay reseñas.</p>
        </div>
    </div>
    
    <script>
        // Cambiar imagen principal al hacer click en thumbnail
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.addEventListener('click', function() {
                document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('main-image').src = this.dataset.image;
            });
        });
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function(){
                document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
                this.classList.add('active');
                const t = this.dataset.tab;
                document.querySelectorAll('.tab-content').forEach(c=>c.classList.remove('active'));
                document.getElementById('tab-' + t).classList.add('active');
            });
        });
        
        // Seleccionar color
        function selectColor(element, color) {
            document.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('selected-color').value = color || 'N/A';
        }
        
        // Seleccionar talla
        function selectSize(element, size) {
            document.querySelectorAll('.size-option').forEach(opt => opt.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('selected-size').value = size || 'N/A';
        }
        
        // Cambiar cantidad
        function changeQuantity(change) {
            const input = document.getElementById('quantity-input');
            const max = parseInt(input.max);
            const current = parseInt(input.value);
            const newValue = Math.max(1, Math.min(max, current + change));
            input.value = newValue;
            document.getElementById('selected-quantity').value = newValue;
        }
        
        // Comprar ahora (ir directo al carrito y luego a checkout)
        function buyNow() {
            // Asegurar que los valores estén establecidos
            const colorInput = document.getElementById('selected-color');
            const sizeInput = document.getElementById('selected-size');
            if (!colorInput.value) colorInput.value = 'N/A';
            if (!sizeInput.value) sizeInput.value = 'N/A';
            
            document.getElementById('product-form').submit();
            // Redirigir al carrito después de agregar
            setTimeout(() => {
                window.location.href = 'carrito.php';
            }, 100);
        }
        function waProduct() {
            var nombre = <?= json_encode($product['nombre']) ?>;
            var precio = <?= json_encode(number_format($precio_actual, 2)) ?>;
            var color = document.getElementById('selected-color').value || 'N/A';
            var talla = document.getElementById('selected-size').value || 'N/A';
            var cantidad = document.getElementById('selected-quantity').value || '1';
            var msg = 'Hola LUNA, quiero este producto:\n' + nombre + '\n' + 'Color: ' + color + ' | Talla: ' + talla + '\nCantidad: ' + cantidad + '\nPrecio: S/ ' + precio;
            var link = 'https://wa.me/51999999999?text=' + encodeURIComponent(msg);
            window.open(link, '_blank');
        }
        
        // Validar formulario antes de enviar
        document.getElementById('product-form').addEventListener('submit', function(e) {
            const colorInput = document.getElementById('selected-color');
            const sizeInput = document.getElementById('selected-size');
            if (!colorInput.value) colorInput.value = 'N/A';
            if (!sizeInput.value) sizeInput.value = 'N/A';
        });
    </script>
    
    <?php include __DIR__ . '/partials/footer.php'; ?>
    <script src="../assets/js/shop.js" defer></script>
</body>
</html>
