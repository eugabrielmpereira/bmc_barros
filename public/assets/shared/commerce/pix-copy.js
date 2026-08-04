/**
 * Botão "Copiar" do Pix copia e cola (pedido recebido).
 */
(function () {
  document.querySelectorAll('[data-copy-target]').forEach(function (button) {
    button.addEventListener('click', function () {
      var targetId = button.getAttribute('data-copy-target');
      var field = targetId ? document.getElementById(targetId) : null;
      if (!field) {
        return;
      }
      field.select();
      field.setSelectionRange(0, field.value.length);
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(field.value).catch(function () {
          document.execCommand('copy');
        });
      } else {
        document.execCommand('copy');
      }
      button.textContent = 'Copiado!';
      setTimeout(function () {
        button.textContent = 'Copiar';
      }, 1800);
    });
  });
})();
