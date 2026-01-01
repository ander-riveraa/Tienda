// Funcionalidad de búsqueda y filtros
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterCategoria = document.getElementById('filterCategoria');
    const filterEstado = document.getElementById('filterEstado');
    const filterPrecio = document.getElementById('filterPrecio');
    const tableView = document.getElementById('tableView');
    const noResults = document.getElementById('noResults');
    const tbody = tableView ? tableView.querySelector('tbody') : null;
    
    // Función para filtrar productos
    function filterProducts() {
        if (!tableView || !tbody) return;
        
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const categoriaFilter = filterCategoria ? filterCategoria.value.toLowerCase() : '';
        const estadoFilter = filterEstado ? filterEstado.value.toLowerCase() : 'activo';
        const precioOrder = filterPrecio ? filterPrecio.value : '';
        
        // Obtener todas las filas como array para poder ordenarlas
        const rows = Array.from(tbody.querySelectorAll('tr'));
        let visibleRows = [];
        
        // Solo considerar filtro activo si hay búsqueda de texto, categoría específica, estado diferente a activo, o orden de precio
        const isFiltering = query || categoriaFilter || (estadoFilter && estadoFilter !== '' && estadoFilter !== 'activo') || precioOrder;
        
        rows.forEach(row => {
            const name = row.dataset.productName || '';
            const category = row.dataset.productCategory || '';
            const price = parseFloat(row.dataset.productPrice || 0);
            const estado = row.dataset.productEstado || '';
            
            // Filtrar por texto de búsqueda
            let matchesText = true;
            if (query) {
                matchesText = name.includes(query) || price.toString().includes(query);
            }
            
            // Filtrar por categoría
            let matchesCategory = true;
            if (categoriaFilter) {
                matchesCategory = category === categoriaFilter;
            }
            
            // Filtrar por estado
            let matchesEstado = true;
            if (estadoFilter && estadoFilter !== '') {
                matchesEstado = estado === estadoFilter;
            }
            
            const matches = matchesText && matchesCategory && matchesEstado;
            
            if (matches) {
                row.style.display = '';
                visibleRows.push({ row: row, price: price });
            } else {
                row.style.display = 'none';
            }
        });
        
        // Ordenar por precio si está seleccionado
        if (precioOrder && visibleRows.length > 0) {
            // Primero, ocultar todas las filas visibles temporalmente
            visibleRows.forEach(item => {
                item.row.style.display = 'none';
            });
            
            // Ordenar el array
            visibleRows.sort((a, b) => {
                if (precioOrder === 'asc') {
                    return a.price - b.price;
                } else if (precioOrder === 'desc') {
                    return b.price - a.price;
                }
                return 0;
            });
            
            // Reinsertar las filas en el orden correcto
            visibleRows.forEach(item => {
                tbody.appendChild(item.row);
                item.row.style.display = '';
            });
        }
        
        // Mostrar/ocultar mensaje de "no resultados" solo si hay búsqueda activa Y no hay resultados
        const hasResults = visibleRows.length > 0;
        if (noResults && tableView) {
            if (isFiltering && !hasResults) {
                // Solo mostrar si hay un filtro activo Y no hay resultados
                noResults.classList.remove('hidden');
                tableView.style.display = 'none';
            } else {
                // Ocultar si no hay filtro activo O si hay resultados
                noResults.classList.add('hidden');
                tableView.style.display = '';
            }
        }
    }
    
    // Event listeners
    if (searchInput) {
        searchInput.addEventListener('input', filterProducts);
    }
    
    if (filterCategoria) {
        filterCategoria.addEventListener('change', filterProducts);
    }
    
    if (filterEstado) {
        filterEstado.addEventListener('change', filterProducts);
    }
    
    if (filterPrecio) {
        filterPrecio.addEventListener('change', filterProducts);
    }
    
    // Asegurarse de que el mensaje de "no resultados" esté oculto por defecto
    if (noResults) {
        noResults.classList.add('hidden');
    }
    
    // Aplicar filtro inicial al cargar la página (mostrar solo activos por defecto)
    // Ocultar productos inactivos sin activar el mensaje de "no resultados"
    if (tbody) {
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const estadoFilter = filterEstado ? filterEstado.value.toLowerCase() : 'activo';
        rows.forEach(row => {
            const estado = row.dataset.productEstado || '';
            if (estadoFilter === 'activo' && estado !== 'activo') {
                row.style.display = 'none';
            } else if (estadoFilter === 'inactivo' && estado !== 'inactivo') {
                row.style.display = 'none';
            } else {
                row.style.display = '';
            }
        });
    }
    
    // Manejar enlace "Nuevo Producto"
    const newProductLink = document.querySelector('a[href="#new"]');
    if (newProductLink) {
        newProductLink.addEventListener('click', function(e) {
            e.preventDefault();
            const form = document.getElementById('formAgregar');
            if (form) {
                form.style.display = form.style.display === 'none' ? 'block' : 'none';
                if (form.style.display === 'block') {
                    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    form.querySelector('input[name="nombre"]')?.focus();
                }
            }
        });
    }
    
    // Resetear formulario después de enviar
    const formAgregar = document.getElementById('formAgregar');
    if (formAgregar) {
        formAgregar.addEventListener('submit', function() {
            // El formulario se enviará normalmente, pero podemos agregar validación aquí si es necesario
        });
    }
});
