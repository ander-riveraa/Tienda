// JS ligero para la tienda (sin dependencias)
// Cargado con defer para no bloquear el render

(function(){
  // Evitar doble envío accidental en botones principales
  document.addEventListener('click', function(e){
    const btn = e.target.closest('button[type="submit"], .add-to-cart-btn');
    if (!btn) return;
    if (btn.dataset.busy) { e.preventDefault(); return; }
    btn.dataset.busy = '1';
    setTimeout(() => { delete btn.dataset.busy; }, 2000);
  }, {passive:true});

  // Manejo de actualización de cantidades en carrito por data-action="update-qty"
  document.addEventListener('change', function(e){
    const input = e.target.closest('input[data-cart-qty]');
    if (!input) return;
    const form = input.closest('form');
    if (!form) return;
    // Opcional: auto-submit
    if (form.dataset.autosubmit === 'true') {
      form.requestSubmit();
    }
  });
})();
