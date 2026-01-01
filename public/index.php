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
    // Fallback seguro si prepare falla (escapar valores manualmente)
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

// Consulta de productos (solo columnas necesarias) con paginación
// Nota: algunos servidores MySQL no permiten placeholders en LIMIT/OFFSET.
// Usamos enteros validados directamente en la consulta para evitar errores.
$sql = "SELECT * FROM productos WHERE $whereSql ORDER BY id DESC LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    // Fallback seguro si prepare falla (escapar manualmente los parámetros)
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
    <title>LUNA - Moda Online</title>
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/shop.css">
    <!-- Iconos -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<body>

    <?php include __DIR__ . '/partials/header.php'; ?>


    <!-- Hero Section -->
    <header class="hero" style="
        background: url('../uploads/productos/ropatienda.jpg') center/cover no-repeat;
        min-height: 48vh;
        display: flex; align-items: center; justify-content: center;
        text-align: center;
    ">
        <div class="hero-content" style="padding: 40px 16px;">
                        <h2 style="color:#fff; text-shadow: 0 2px 10px rgba(0,0,0,0.25);">Colección 2025</h2>
            <p style="color:#f3f4f6; text-shadow: 0 1px 6px rgba(0,0,0,0.2);">Descubre tu estilo único</p>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <h3 class="section-title">Nuevos Ingresos</h3>

        <div class="grid">
            <?php if ($res && $res instanceof mysqli_result && $res->num_rows > 0): ?>
            <?php while ($p = $res->fetch_assoc()): ?>
                <div class="product-card" onclick="window.location.href='producto.php?id=<?= $p['id'] ?>'">
                    <div class="image-container">
                        <?php
                        // Lógica de imagen (revisar múltiples campos)
                        $img = 'https://via.placeholder.com/300x400?text=No+Image'; // Default
                    
                        if (!empty($p['imagen1'])) {
                            $img = '../uploads/productos/' . $p['imagen1'];
                        } elseif (!empty($p['imagenes'])) {
                            $parts = explode(',', $p['imagenes']);
                            if (!empty($parts[0]))
                                $img = '../uploads/productos/' . trim($parts[0]);
                        } elseif (!empty($p['imagen'])) {
                            $img = '../uploads/productos/' . $p['imagen'];
                        }
                        ?>
                        <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>" loading="lazy" decoding="async" referrerpolicy="no-referrer">
                    </div>

                    <div class="product-info">
                        <?php if (!empty($p['categoria'])): ?>
                            <span class="category"><?= htmlspecialchars($p['categoria']) ?></span>
                        <?php endif; ?>

                        <h4 class="product-title"><?= htmlspecialchars($p['nombre']) ?></h4>

                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div class="product-price">S/ <?= number_format($p['precio'], 2) ?></div>
                            <?php if (!empty($p['talla'])): ?>
                                <span style="font-size: 12px; color: #888;">Talla: <?= htmlspecialchars($p['talla']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align:center; color:#666; padding:40px 0;">
                    No se encontraron productos que coincidan.
                </div>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <nav class="pagination" style="display:flex; gap:8px; justify-content:center; align-items:center; margin: 24px 0;">
            <?php
            // Construir URL base conservando filtros
            $baseParams = $_GET;
            unset($baseParams['page']);
            $baseQuery = http_build_query($baseParams);
            $baseUrl = 'index.php' . ($baseQuery ? ('?' . $baseQuery . '&') : '?');
            ?>

            <?php if ($page > 1): ?>
                <a href="<?= $baseUrl . 'page=' . ($page - 1) ?>" style="padding:8px 12px; border:1px solid #e5e7eb; border-radius:8px; color:#111; text-decoration:none;">&laquo; Anterior</a>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
                <a href="<?= $baseUrl . 'page=' . $i ?>" style="padding:8px 12px; border:1px solid <?= $i === $page ? '#111827' : '#e5e7eb' ?>; background: <?= $i === $page ? '#111827' : '#fff' ?>; color: <?= $i === $page ? '#fff' : '#111' ?>; border-radius:8px; text-decoration:none;">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?= $baseUrl . 'page=' . ($page + 1) ?>" style="padding:8px 12px; border:1px solid #e5e7eb; border-radius:8px; color:#111; text-decoration:none;">Siguiente &raquo;</a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
    </div>

    <!-- WhatsApp Button (Left Side) -->
    <a href="https://wa.me/51999999999" class="whatsapp-btn" target="_blank" title="Contáctanos por WhatsApp">
        <i class="bi bi-whatsapp"></i>
    </a>

    <?php include __DIR__ . '/partials/footer.php'; ?>
    <script src="../assets/js/shop.js" defer></script>
</body>

</html>