document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".copy-btn").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var value = btn.getAttribute("data-copy");
      if (!value) return;

      var restoreIcon = btn.innerHTML;
      var markCopied = function () {
        btn.classList.add("copied");
        btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
        setTimeout(function () {
          btn.classList.remove("copied");
          btn.innerHTML = restoreIcon;
        }, 1500);
      };

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(value).then(markCopied).catch(function () {
          fallbackCopy(value, markCopied);
        });
      } else {
        fallbackCopy(value, markCopied);
      }
    });
  });

  function fallbackCopy(value, done) {
    var textarea = document.createElement("textarea");
    textarea.value = value;
    textarea.style.position = "fixed";
    textarea.style.left = "-9999px";
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();
    try {
      document.execCommand("copy");
      done();
    } catch (e) {
      // silencioso: el usuario puede seleccionar y copiar manualmente
    }
    document.body.removeChild(textarea);
  }
});
