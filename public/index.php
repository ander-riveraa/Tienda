<?php
require __DIR__ . "/../app/config/database.php";

// Parámetros de paginación
$perPage = 12;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Lógica de Filtrado
$whereClauses = ["(estado = 'activo' OR estado IS NULL)"];
$params = [];
$types = "";

if (isset($_GET['categoria']) && $_GET['categoria'] !== '') {
    $whereClauses[] = "categoria = ?";
    $params[] = $_GET['categoria'];
    $types .= "s";
}

if (isset($_GET['busqueda']) && $_GET['busqueda'] !== '') {
    $whereClauses[] = "(nombre LIKE ? OR descripcion LIKE ?)";
    $term = "%" . $_GET['busqueda'] . "%";
    $params[] = $term;
    $params[] = $term;
    $types .= "ss";
}

$whereSql = implode(" AND ", $whereClauses);

// Total de productos para paginación
$countSql = "SELECT COUNT(*) AS total FROM productos WHERE $whereSql";
$stmtCount = $conn->prepare($countSql);
if ($stmtCount) {
    if ($types !== '') {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $total = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
} else {
    // Fallback seguro si prepare falla
    $safeWhere = $whereSql;
    if (isset($_GET['categoria']) && $_GET['categoria'] !== '') {
        $cat = $conn->real_escape_string($_GET['categoria']);
        $safeWhere = str_replace("categoria = ?", "categoria = '" . $cat . "'", $safeWhere);
    }
    if (isset($_GET['busqueda']) && $_GET['busqueda'] !== '') {
        $term = "%" . $_GET['busqueda'] . "%";
        $term = $conn->real_escape_string($term);
        $safeWhere = str_replace("(nombre LIKE ? OR descripcion LIKE ?)", "(nombre LIKE '" . $term . "' OR descripcion LIKE '" . $term . "')", $safeWhere);
    }
    $fallbackSql = "SELECT COUNT(*) AS total FROM productos WHERE $safeWhere";
    $fallbackRes = $conn->query($fallbackSql);
    $total = (int)($fallbackRes ? ($fallbackRes->fetch_assoc()['total'] ?? 0) : 0);
}
$totalPages = max(1, (int)ceil($total / $perPage));

// Consulta de productos con paginación
$sql = "SELECT * FROM productos WHERE $whereSql ORDER BY id DESC LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    // Fallback seguro
    $safeWhere = $whereSql;
    if (isset($_GET['categoria']) && $_GET['categoria'] !== '') {
        $cat = $conn->real_escape_string($_GET['categoria']);
        $safeWhere = str_replace("categoria = ?", "categoria = '" . $cat . "'", $safeWhere);
    }
    if (isset($_GET['busqueda']) && $_GET['busqueda'] !== '') {
        $term = "%" . $_GET['busqueda'] . "%";
        $term = $conn->real_escape_string($term);
        $safeWhere = str_replace("(nombre LIKE ? OR descripcion LIKE ?)", "(nombre LIKE '" . $term . "' OR descripcion LIKE '" . $term . "')", $safeWhere);
    }
    $fallbackSql = "SELECT * FROM productos WHERE $safeWhere ORDER BY id DESC LIMIT $perPage OFFSET $offset";
    $res = $conn->query($fallbackSql);
}

$categoriaActual = $_GET['categoria'] ?? 'Todas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LUNA - Moda Online | Tu estilo, tu elección</title>
    <meta name="description" content="Descubre la mejor colección de moda en LUNA. Ropa para hombre, mujer y niños. Estilo único y calidad excepcional.">
    <link rel="stylesheet" href="../assets/css/shop.css">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* Mejoras profesionales para el index */
        .hero {
            background: linear-gradient(135deg, rgba(245, 245, 245, 0.95) 0%, rgba(230, 230, 230, 0.95) 100%),
                        url('../uploads/productos/ropatienda.jpg') center/cover no-repeat;
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            z-index: 1;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            padding: 40px 20px;
            max-width: 800px;
        }
        
        .hero-content h2 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(36px, 6vw, 64px);
            font-weight: 600;
            color: #fff;
            text-shadow: 0 4px 20px rgba(0,0,0,0.3);
            margin-bottom: 20px;
            letter-spacing: -1px;
        }
        
        .hero-content p {
            font-size: clamp(16px, 2.5vw, 22px);
            color: #f3f4f6;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            margin-bottom: 30px;
            font-weight: 300;
        }
        
        .hero-cta {
            display: inline-block;
            padding: 16px 40px;
            background: white;
            color: #111;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .hero-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
            background: #f9fafb;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px;
        }
        
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(28px, 4vw, 40px);
            font-weight: 600;
            text-align: center;
            margin-bottom: 50px;
            color: #111;
            letter-spacing: -0.5px;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 50px;
        }
        
        .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
            border: 1px solid #ececec;
            position: relative;
        }
        
        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.02) 100%);
            opacity: 0;
            transition: opacity 0.4s ease;
            pointer-events: none;
            z-index: 1;
        }
        
        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 18px rgba(0,0,0,0.10);
            border-color: #e5e5e5;
        }
        
        .product-card:hover::before {
            opacity: 1;
        }
        
        .image-container {
            position: relative;
            width: 100%;
            padding-top: 95%;
            overflow: hidden;
            background: #f5f5f5;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .image-container img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .product-card:hover .image-container img {
            transform: scale(1.08);
        }
        
        .product-info {
            padding: 14px;
            position: relative;
            z-index: 2;
        }
        
        .category {
            display: inline-block;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #888;
            margin-bottom: 6px;
        }
        
        .product-title {
            font-size: 13px;
            font-weight: 500;
            color: #1a1a1a;
            margin-bottom: 6px;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 36px;
        }
        
        .product-price {
            font-size: 16px;
            font-weight: 600;
            color: #111;
            letter-spacing: -0.2px;
        }
        
        .pagination {
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
            margin: 60px 0 40px;
            flex-wrap: wrap;
        }
        
        .pagination a {
            padding: 12px 18px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            color: #374151;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            background: white;
        }
        
        .pagination a:hover {
            border-color: #333;
            color: #111;
            transform: translateY(-2px);
        }
        
        .pagination a[style*="background: #111827"] {
            background: #111827 !important;
            border-color: #111827;
            color: white;
        }
        
        .empty-state {
            grid-column: 1/-1;
            text-align: center;
            padding: 80px 20px;
            color: #6b7280;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #374151;
        }
        
        @media (max-width: 768px) {
            .hero {
                min-height: 50vh;
            }
            
            .grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 16px;
            }
            
            .container {
                padding: 40px 15px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/partials/header.php'; ?>

    <!-- Hero Section Mejorado -->
    <header class="hero">
        <div class="hero-content">
            <h2>Colección 2025</h2>
            <p>Descubre tu estilo único con las últimas tendencias en moda</p>
            <a href="#productos" class="hero-cta">Explorar Colección</a>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container" id="productos">
        <h3 class="section-title">Nuevos Ingresos</h3>

        <div class="grid">
            <?php if ($res && $res instanceof mysqli_result && $res->num_rows > 0): ?>
            <?php while ($p = $res->fetch_assoc()): ?>
                <div class="product-card" onclick="window.location.href='producto.php?id=<?= $p['id'] ?>'">
                    <div class="image-container">
                        <?php
                        // Lógica de imagen
                        $img = 'https://via.placeholder.com/300x400?text=Sin+Imagen';
                    
                        if (!empty($p['imagen1'])) {
                            $img = '../uploads/productos/' . $p['imagen1'];
                        } elseif (!empty($p['imagenes'])) {
                            $parts = explode(',', $p['imagenes']);
                            if (!empty($parts[0])) {
                                $img = '../uploads/productos/' . trim($parts[0]);
                            }
                        } elseif (!empty($p['imagen'])) {
                            $img = '../uploads/productos/' . $p['imagen'];
                        }
                        ?>
                        <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>" loading="lazy" decoding="async">
                    </div>

                    <div class="product-info">
                        <?php if (!empty($p['categoria'])): ?>
                            <span class="category"><?= htmlspecialchars($p['categoria']) ?></span>
                        <?php endif; ?>

                        <h4 class="product-title"><?= htmlspecialchars($p['nombre']) ?></h4>

                        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 12px;">
                            <div>
                                <div class="product-price">S/ <?= number_format($p['precio'], 2) ?></div>
                                <?php if (isset($p['stock']) && $p['stock'] > 0): ?>
                                    <span style="font-size: 11px; color: #111; font-weight: 500; margin-top: 4px; display: block;">
                                        En stock
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($p['talla'])): ?>
                                <span style="font-size: 11px; color: #666; background: #f5f5f5; padding: 4px 8px; border-radius: 4px; font-weight: 500;">
                                    <?= htmlspecialchars($p['talla']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-search"></i>
                    <h3>No se encontraron productos</h3>
                    <p>Intenta con otros términos de búsqueda o selecciona una categoría diferente</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <nav class="pagination">
            <?php
            $baseParams = $_GET;
            unset($baseParams['page']);
            $baseQuery = http_build_query($baseParams);
            $baseUrl = 'index.php' . ($baseQuery ? ('?' . $baseQuery . '&') : '?');
            ?>

            <?php if ($page > 1): ?>
                <a href="<?= $baseUrl . 'page=' . ($page - 1) ?>">
                    <i class="bi bi-chevron-left"></i> Anterior
                </a>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
                <a href="<?= $baseUrl . 'page=' . $i ?>" style="<?= $i === $page ? 'background: #111827; border-color: #111827; color: white;' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?= $baseUrl . 'page=' . ($page + 1) ?>">
                    Siguiente <i class="bi bi-chevron-right"></i>
                </a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
    </div>

    <!-- WhatsApp Button -->
    <a href="https://wa.me/51999999999" class="whatsapp-btn" target="_blank" title="Contáctanos por WhatsApp" aria-label="Contactar por WhatsApp">
        <i class="bi bi-whatsapp"></i>
    </a>

    <?php include __DIR__ . '/partials/footer.php'; ?>
    <script src="../assets/js/shop.js" defer></script>
    <script>
        // Smooth scroll para el botón del hero
        document.querySelector('.hero-cta')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('productos').scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    </script>
</body>
</html>
