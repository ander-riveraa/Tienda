/**
 * Panel de Administración - Gestión de Productos
 * Funcionalidades: búsqueda, filtrado, ordenamiento y gestión de formularios
 */

(function() {
    'use strict';

    // ============================================
    // Utilidades
    // ============================================

    /**
     * Debounce para optimizar eventos frecuentes
     * @param {Function} func - Función a ejecutar
     * @param {number} wait - Tiempo de espera en ms
     * @returns {Function} Función con debounce aplicado
     */
    function debounce(func, wait = 300) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Obtiene un elemento del DOM de forma segura
     * @param {string} selector - Selector CSS
     * @returns {HTMLElement|null}
     */
    function $(selector) {
        return document.querySelector(selector);
    }

    /**
     * Obtiene múltiples elementos del DOM
     * @param {string} selector - Selector CSS
     * @returns {NodeList}
     */
    function $$(selector) {
        return document.querySelectorAll(selector);
    }

    // ============================================
    // Módulo de Filtrado de Productos
    // ============================================

    const ProductFilter = {
        elements: {},
        currentFilters: {
            search: '',
            categoria: '',
            estado: 'activo',
            precio: ''
        },

        /**
         * Inicializa el módulo de filtrado
         */
        init() {
            this.elements = {
                searchInput: $('#searchInput'),
                filterCategoria: $('#filterCategoria'),
                filterEstado: $('#filterEstado'),
                filterPrecio: $('#filterPrecio'),
                tableView: $('#tableView'),
                noResults: $('#noResults'),
                tbody: $('#tableView')?.querySelector('tbody')
            };

            if (!this.elements.tableView || !this.elements.tbody) {
                return; // No hay tabla de productos, salir
            }

            this.setupEventListeners();
            this.applyInitialFilter();
            this.hideNoResultsMessage();
        },

        /**
         * Configura los event listeners
         */
        setupEventListeners() {
            // Búsqueda con debounce para mejor rendimiento
            if (this.elements.searchInput) {
                const debouncedFilter = debounce(() => this.filter(), 300);
                this.elements.searchInput.addEventListener('input', (e) => {
                    this.currentFilters.search = e.target.value.toLowerCase().trim();
                    debouncedFilter();
                });
            }

            // Filtros con cambio inmediato
            if (this.elements.filterCategoria) {
                this.elements.filterCategoria.addEventListener('change', (e) => {
                    this.currentFilters.categoria = e.target.value.toLowerCase();
                    this.filter();
                });
            }

            if (this.elements.filterEstado) {
                this.elements.filterEstado.addEventListener('change', (e) => {
                    this.currentFilters.estado = e.target.value.toLowerCase();
                    this.filter();
                });
            }

            if (this.elements.filterPrecio) {
                this.elements.filterPrecio.addEventListener('change', (e) => {
                    this.currentFilters.precio = e.target.value;
                    this.filter();
                });
            }
        },

        /**
         * Obtiene los datos de un producto desde una fila
         * @param {HTMLElement} row - Fila de la tabla
         * @returns {Object} Datos del producto
         */
        getProductData(row) {
            return {
                name: (row.dataset.productName || '').toLowerCase(),
                category: (row.dataset.productCategory || '').toLowerCase(),
                price: parseFloat(row.dataset.productPrice || 0),
                estado: (row.dataset.productEstado || '').toLowerCase()
            };
        },

        /**
         * Verifica si un producto coincide con los filtros de texto y categoría
         * @param {Object} productData - Datos del producto
         * @returns {boolean}
         */
        matchesFilters(productData) {
            // Filtro de búsqueda por texto
            if (this.currentFilters.search) {
                const searchTerm = this.currentFilters.search;
                const matchesName = productData.name.includes(searchTerm);
                const matchesPrice = productData.price.toString().includes(searchTerm);
                if (!matchesName && !matchesPrice) {
                    return false;
                }
            }

            // Filtro de categoría
            if (this.currentFilters.categoria) {
                if (productData.category !== this.currentFilters.categoria) {
                    return false;
                }
            }

            // Filtro de estado
            if (this.currentFilters.estado) {
                if (productData.estado !== this.currentFilters.estado) {
                    return false;
                }
            }

            return true;
        },

        /**
         * Verifica si hay filtros activos
         * @returns {boolean}
         */
        hasActiveFilters() {
            return !!(
                this.currentFilters.search ||
                this.currentFilters.categoria ||
                (this.currentFilters.estado && this.currentFilters.estado !== 'activo') ||
                this.currentFilters.precio
            );
        },

        /**
         * Ordena las filas visibles por precio
         * @param {Array} visibleRows - Array de objetos {row, price}
         */
        sortByPrice(visibleRows) {
            if (!this.currentFilters.precio || visibleRows.length === 0) {
                return visibleRows;
            }

            const sorted = [...visibleRows].sort((a, b) => {
                if (this.currentFilters.precio === 'asc') {
                    return a.price - b.price;
                } else if (this.currentFilters.precio === 'desc') {
                    return b.price - a.price;
                }
                return 0;
            });

            return sorted;
        },

        /**
         * Aplica el filtrado y ordenamiento de productos
         */
        filter() {
            const rows = Array.from(this.elements.tbody.querySelectorAll('tr'));
            const visibleRows = [];

            // Filtrar filas
            rows.forEach(row => {
                const productData = this.getProductData(row);
                const matches = this.matchesFilters(productData);

                if (matches) {
                    row.style.display = '';
                    visibleRows.push({ row, price: productData.price });
                } else {
                    row.style.display = 'none';
                }
            });

            // Ordenar por precio si es necesario
            if (this.currentFilters.precio && visibleRows.length > 0) {
                // Ocultar temporalmente todas las filas visibles
                visibleRows.forEach(item => {
                    item.row.style.display = 'none';
                });

                // Ordenar
                const sortedRows = this.sortByPrice(visibleRows);

                // Reinsertar en orden
                sortedRows.forEach(item => {
                    this.elements.tbody.appendChild(item.row);
                    item.row.style.display = '';
                });
            }

            // Mostrar/ocultar mensaje de "no resultados"
            this.updateNoResultsMessage(visibleRows.length);
        },

        /**
         * Actualiza el mensaje de "no resultados"
         * @param {number} visibleCount - Número de filas visibles
         */
        updateNoResultsMessage(visibleCount) {
            if (!this.elements.noResults || !this.elements.tableView) {
                return;
            }

            const hasResults = visibleCount > 0;
            const isFiltering = this.hasActiveFilters();

            if (isFiltering && !hasResults) {
                this.elements.noResults.classList.remove('hidden');
                this.elements.noResults.style.display = '';
                this.elements.tableView.style.display = 'none';
            } else {
                this.elements.noResults.classList.add('hidden');
                this.elements.noResults.style.display = 'none';
                this.elements.tableView.style.display = '';
            }
        },

        /**
         * Oculta el mensaje de "no resultados"
         */
        hideNoResultsMessage() {
            if (this.elements.noResults) {
                this.elements.noResults.classList.add('hidden');
                this.elements.noResults.style.display = 'none';
            }
        },

        /**
         * Aplica el filtro inicial al cargar la página
         */
        applyInitialFilter() {
            if (!this.elements.tbody || !this.elements.filterEstado) {
                return;
            }

            const estadoFilter = this.elements.filterEstado.value.toLowerCase();
            const rows = Array.from(this.elements.tbody.querySelectorAll('tr'));

            rows.forEach(row => {
                const productData = this.getProductData(row);
                const shouldShow = !estadoFilter || productData.estado === estadoFilter;

                row.style.display = shouldShow ? '' : 'none';
            });
        }
    };

    // ============================================
    // Módulo de Gestión de Formularios
    // ============================================

    const FormManager = {
        /**
         * Inicializa el módulo de formularios
         */
        init() {
            this.handleNewProductLink();
            this.handleFormReset();
        },

        /**
         * Maneja el enlace "Nuevo Producto"
         */
        handleNewProductLink() {
            const newProductLink = $('a[href="#new"]');
            if (!newProductLink) return;

            newProductLink.addEventListener('click', (e) => {
                e.preventDefault();
                const form = $('#formAgregar');
                if (!form) return;

                const isHidden = form.style.display === 'none';
                form.style.display = isHidden ? 'block' : 'none';

                if (isHidden) {
                    // Scroll suave y enfoque en el primer campo
                    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    const firstInput = form.querySelector('input[name="nombre"]');
                    if (firstInput) {
                        setTimeout(() => firstInput.focus(), 100);
                    }
                }
            });
        },

        /**
         * Maneja el reseteo del formulario (placeholder para validaciones futuras)
         */
        handleFormReset() {
            const formAgregar = $('#formAgregar');
            if (!formAgregar) return;

            formAgregar.addEventListener('submit', () => {
                // Aquí se pueden agregar validaciones adicionales antes del envío
                // Por ahora, el formulario se envía normalmente
            });
        }
    };

    // ============================================
    // Inicialización Principal
    // ============================================

    /**
     * Inicializa todos los módulos cuando el DOM está listo
     */
    function init() {
        ProductFilter.init();
        FormManager.init();
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        // DOM ya está listo
        init();
    }

})();