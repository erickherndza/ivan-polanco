document.addEventListener("DOMContentLoaded", function () {
  var dateInput = document.getElementById("date-input");
  var slotsWrap = document.getElementById("slots-wrap");
  var toStep2Btn = document.getElementById("to-step-2");
  var backTo1Btn = document.getElementById("back-to-1");
  var changeSlotBtn = document.getElementById("change-slot");
  var bookingForm = document.getElementById("booking-form");
  var submitBtn = document.getElementById("submit-booking");
  var errorBox = document.getElementById("form-error");
  var summaryText = document.getElementById("summary-text");
  var successText = document.getElementById("success-text");
  var steps = document.querySelectorAll(".booking-step");

  if (!dateInput) return;

  var todayStr = new Date().toISOString().slice(0, 10);
  dateInput.min = todayStr;
  dateInput.value = todayStr;

  var selectedDate = null;
  var selectedTime = null;

  var DIAS = ["domingo", "lunes", "martes", "miércoles", "jueves", "viernes", "sábado"];
  var MESES = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];

  function formatDateTime(dateStr, timeStr) {
    var parts = dateStr.split("-").map(Number);
    var d = new Date(parts[0], parts[1] - 1, parts[2]);
    var dia = DIAS[d.getDay()];
    var mes = MESES[d.getMonth()];
    var [h, m] = timeStr.split(":").map(Number);
    var hour12 = h % 12 === 0 ? 12 : h % 12;
    var ampm = h < 12 ? "a. m." : "p. m.";
    return dia.charAt(0).toUpperCase() + dia.slice(1) + " " + d.getDate() + " de " + mes + ", " + hour12 + ":" + String(m).padStart(2, "0") + " " + ampm;
  }

  function showError(msg) {
    errorBox.textContent = msg;
    errorBox.classList.add("visible");
  }
  function hideError() {
    errorBox.classList.remove("visible");
  }

  function goToStep(n) {
    document.getElementById("panel-1").style.display = n === 1 ? "" : "none";
    document.getElementById("panel-2").style.display = n === 2 ? "" : "none";
    document.getElementById("panel-3").style.display = n === 3 ? "" : "none";
    steps.forEach(function (step) {
      var stepNum = parseInt(step.dataset.step, 10);
      step.classList.toggle("active", stepNum === n);
      step.classList.toggle("done", stepNum < n);
    });
    hideError();
  }

  function loadSlots(date) {
    slotsWrap.innerHTML = '<p class="slots-loading">Cargando horarios disponibles…</p>';
    toStep2Btn.disabled = true;
    selectedTime = null;

    fetch("api/citas/slots.php?date=" + encodeURIComponent(date))
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var slots = data.slots || [];
        if (slots.length === 0) {
          slotsWrap.innerHTML = '<p class="slots-empty">No hay horarios disponibles ese día. Prueba con otra fecha.</p>';
          return;
        }
        var grid = document.createElement("div");
        grid.className = "slots-grid";
        slots.forEach(function (time) {
          var btn = document.createElement("button");
          btn.type = "button";
          btn.className = "slot-btn";
          btn.textContent = time;
          btn.addEventListener("click", function () {
            grid.querySelectorAll(".slot-btn.selected").forEach(function (b) { b.classList.remove("selected"); });
            btn.classList.add("selected");
            selectedTime = time;
            toStep2Btn.disabled = false;
          });
          grid.appendChild(btn);
        });
        slotsWrap.innerHTML = "";
        slotsWrap.appendChild(grid);
      })
      .catch(function () {
        slotsWrap.innerHTML = '<p class="slots-empty">No se pudieron cargar los horarios. Intenta de nuevo.</p>';
      });
  }

  dateInput.addEventListener("change", function () {
    selectedDate = dateInput.value;
    if (selectedDate) loadSlots(selectedDate);
  });
  loadSlots(dateInput.value);
  selectedDate = dateInput.value;

  toStep2Btn.addEventListener("click", function () {
    if (!selectedDate || !selectedTime) return;
    summaryText.innerHTML = "<strong>" + formatDateTime(selectedDate, selectedTime) + "</strong>";
    goToStep(2);
  });

  backTo1Btn.addEventListener("click", function () { goToStep(1); });
  changeSlotBtn.addEventListener("click", function () { goToStep(1); });

  bookingForm.addEventListener("submit", function (e) {
    e.preventDefault();
    hideError();

    var payload = {
      name: document.getElementById("name").value.trim(),
      email: document.getElementById("email").value.trim(),
      phone: document.getElementById("phone").value.trim(),
      motivo: document.getElementById("motivo").value.trim(),
      date: selectedDate,
      time: selectedTime,
      website: document.getElementById("website-hp").value,
    };

    submitBtn.disabled = true;
    submitBtn.textContent = "Enviando…";

    fetch("api/citas/create.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    })
      .then(function (r) { return r.json().then(function (data) { return { status: r.status, data: data }; }); })
      .then(function (res) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Confirmar cita <span class="btn-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>';

        if (res.status !== 200 || !res.data.ok) {
          showError(res.data.error || "No se pudo agendar la cita. Intenta de nuevo.");
          return;
        }

        successText.textContent = "Tu cita quedó agendada para " + formatDateTime(selectedDate, selectedTime) + ". Te enviamos los detalles a tu correo.";
        goToStep(3);
      })
      .catch(function () {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Confirmar cita <span class="btn-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>';
        showError("No se pudo conectar con el servidor. Intenta de nuevo.");
      });
  });
});
