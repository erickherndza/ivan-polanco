document.addEventListener("DOMContentLoaded", function () {
  var loginForm = document.getElementById("login-form");
  var apptList = document.getElementById("appt-list");

  if (loginForm) initLoginPage();
  if (apptList) initDashboard();

  function initLoginPage() {
    var errorBox = document.getElementById("login-error");
    var btn = document.getElementById("login-btn");

    loginForm.addEventListener("submit", function (e) {
      e.preventDefault();
      errorBox.classList.remove("visible");
      btn.disabled = true;
      btn.textContent = "Entrando…";

      fetch("../api/admin/login.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          username: document.getElementById("username").value.trim(),
          password: document.getElementById("password").value,
        }),
      })
        .then(function (r) { return r.json().then(function (data) { return { status: r.status, data: data }; }); })
        .then(function (res) {
          btn.disabled = false;
          btn.textContent = "Entrar";
          if (res.status !== 200 || !res.data.ok) {
            errorBox.textContent = res.data.error || "No se pudo iniciar sesión";
            errorBox.classList.add("visible");
            return;
          }
          window.location.href = "index.html";
        })
        .catch(function () {
          btn.disabled = false;
          btn.textContent = "Entrar";
          errorBox.textContent = "No se pudo conectar con el servidor.";
          errorBox.classList.add("visible");
        });
    });
  }

  function initDashboard() {
    var csrfToken = null;
    var currentFilter = "upcoming";
    var rowTemplate = document.getElementById("appt-row-template");

    var DIAS = ["dom", "lun", "mar", "mié", "jue", "vie", "sáb"];
    var MESES = ["ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];

    function formatDate(isoDatetime) {
      var d = new Date(isoDatetime.replace(" ", "T"));
      return DIAS[d.getDay()] + " " + d.getDate() + " " + MESES[d.getMonth()];
    }
    function formatTime(isoDatetime) {
      var d = new Date(isoDatetime.replace(" ", "T"));
      var h = d.getHours();
      var m = String(d.getMinutes()).padStart(2, "0");
      var ampm = h < 12 ? "a.m." : "p.m.";
      var h12 = h % 12 === 0 ? 12 : h % 12;
      return h12 + ":" + m + " " + ampm;
    }

    fetch("../api/admin/session.php")
      .then(function (r) {
        if (r.status === 401) { window.location.href = "login.html"; return null; }
        return r.json();
      })
      .then(function (data) {
        if (!data) return;
        csrfToken = data.csrf;
        document.getElementById("admin-username").textContent = data.username || "";
        loadGoogleStatus();
        loadAppointments();
      });

    document.getElementById("logout-btn").addEventListener("click", function () {
      fetch("../api/admin/logout.php", { method: "POST" }).finally(function () {
        window.location.href = "login.html";
      });
    });

    function loadGoogleStatus() {
      fetch("../api/google/status.php")
        .then(function (r) { return r.json(); })
        .then(function (data) {
          var text = document.getElementById("google-status-text");
          var disconnectBtn = document.getElementById("google-disconnect-btn");
          if (data.connected) {
            text.innerHTML = "Conectado con <strong>" + escapeHtml(data.email) + "</strong>";
            disconnectBtn.style.display = "";
          } else {
            text.textContent = "No hay ninguna cuenta de Google Calendar conectada todavía.";
            disconnectBtn.style.display = "none";
          }
        });
    }

    document.getElementById("google-disconnect-btn").addEventListener("click", function () {
      if (!confirm("¿Desconectar la cuenta de Google Calendar? Las citas nuevas dejarán de sincronizarse hasta que conectes otra cuenta.")) return;
      fetch("../api/google/disconnect.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ csrf: csrfToken }),
      }).then(function () { loadGoogleStatus(); });
    });

    document.querySelectorAll(".filter-btn").forEach(function (btn) {
      btn.addEventListener("click", function () {
        document.querySelectorAll(".filter-btn").forEach(function (b) { b.classList.remove("active"); });
        btn.classList.add("active");
        currentFilter = btn.dataset.filter;
        loadAppointments();
      });
    });

    function loadAppointments() {
      var errorBox = document.getElementById("appt-error");
      errorBox.classList.remove("visible");
      apptList.innerHTML = '<p class="hint">Cargando citas…</p>';

      fetch("../api/admin/appointments.php?filter=" + encodeURIComponent(currentFilter))
        .then(function (r) { return r.json(); })
        .then(function (data) {
          var appts = data.appointments || [];
          if (appts.length === 0) {
            apptList.innerHTML = '<p class="hint">No hay citas en esta vista.</p>';
            return;
          }
          apptList.innerHTML = "";
          appts.forEach(function (appt) { apptList.appendChild(renderRow(appt)); });
        })
        .catch(function () {
          errorBox.textContent = "No se pudieron cargar las citas.";
          errorBox.classList.add("visible");
          apptList.innerHTML = "";
        });
    }

    function renderRow(appt) {
      var node = rowTemplate.content.cloneNode(true);

      node.querySelector(".appt-date").textContent = formatDate(appt.start_datetime);
      node.querySelector(".appt-time").textContent = formatTime(appt.start_datetime);
      node.querySelector(".appt-name").textContent = appt.patient_name;
      node.querySelector(".appt-contact").textContent = appt.patient_phone + " · " + appt.patient_email;
      node.querySelector(".appt-motivo").textContent = appt.motivo;

      var statusEl = node.querySelector(".appt-status");
      statusEl.textContent = appt.status === "cancelled" ? "Cancelada" : "Confirmada";
      statusEl.classList.add(appt.status === "cancelled" ? "cancelled" : "confirmed");

      var reschedulePanel = node.querySelector(".appt-reschedule-panel");
      var rescheduleBtn = node.querySelector(".appt-reschedule-btn");
      var cancelBtn = node.querySelector(".appt-cancel-btn");

      if (appt.status === "cancelled") {
        rescheduleBtn.style.display = "none";
        cancelBtn.style.display = "none";
      }

      rescheduleBtn.addEventListener("click", function () {
        var isOpen = reschedulePanel.style.display !== "none";
        reschedulePanel.style.display = isOpen ? "none" : "flex";
        if (!isOpen) {
          var d = new Date(appt.start_datetime.replace(" ", "T"));
          node.querySelector(".reschedule-date").value = d.toISOString().slice(0, 10);
          node.querySelector(".reschedule-time").value = String(d.getHours()).padStart(2, "0") + ":" + String(d.getMinutes()).padStart(2, "0");
        }
      });

      node.querySelector(".reschedule-cancel-btn").addEventListener("click", function () {
        reschedulePanel.style.display = "none";
      });

      node.querySelector(".reschedule-confirm-btn").addEventListener("click", function () {
        var date = node.querySelector(".reschedule-date").value;
        var time = node.querySelector(".reschedule-time").value;
        if (!date || !time) return;

        fetch("../api/admin/reschedule.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id: appt.id, date: date, time: time, csrf: csrfToken }),
        })
          .then(function (r) { return r.json().then(function (data) { return { status: r.status, data: data }; }); })
          .then(function (res) {
            if (res.status !== 200 || !res.data.ok) {
              alert(res.data.error || "No se pudo reagendar la cita.");
              return;
            }
            loadAppointments();
          });
      });

      cancelBtn.addEventListener("click", function () {
        if (!confirm("¿Cancelar esta cita? Se notificará al paciente por correo.")) return;
        fetch("../api/admin/cancel.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id: appt.id, csrf: csrfToken }),
        })
          .then(function (r) { return r.json().then(function (data) { return { status: r.status, data: data }; }); })
          .then(function (res) {
            if (res.status !== 200 || !res.data.ok) {
              alert(res.data.error || "No se pudo cancelar la cita.");
              return;
            }
            loadAppointments();
          });
      });

      return node;
    }

    function escapeHtml(str) {
      var div = document.createElement("div");
      div.textContent = str || "";
      return div.innerHTML;
    }
  }
});
