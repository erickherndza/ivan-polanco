document.addEventListener("DOMContentLoaded", function () {
  var header = document.getElementById("site-header");
  var nav = document.querySelector(".main-nav");
  if (header) {
    var lastScrollY = window.scrollY;
    var onScroll = function () {
      var y = window.scrollY;
      if (y > 40) header.classList.add("is-fixed");
      else header.classList.remove("is-fixed");

      var navOpen = nav && nav.classList.contains("open");
      if (!navOpen && y > 220 && y > lastScrollY + 4) {
        header.classList.add("header-hidden");
      } else if (y < lastScrollY - 4 || y <= 220) {
        header.classList.remove("header-hidden");
      }
      lastScrollY = y;
    };
    window.addEventListener("scroll", onScroll);
    onScroll();
  }

  var toggle = document.querySelector(".nav-toggle");

  if (toggle && nav) {
    toggle.addEventListener("click", function () {
      var isOpen = nav.classList.toggle("open");
      toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });

    nav.querySelectorAll("a").forEach(function (link) {
      link.addEventListener("click", function () {
        nav.classList.remove("open");
        toggle.setAttribute("aria-expanded", "false");
      });
    });
  }

  var openFaq = document.querySelector(".faq-item.open .faq-answer");
  if (openFaq) openFaq.style.maxHeight = openFaq.scrollHeight + "px";

  document.querySelectorAll(".faq-question").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var item = btn.closest(".faq-item");
      var answer = item.querySelector(".faq-answer");
      var isOpen = item.classList.contains("open");

      document.querySelectorAll(".faq-item.open").forEach(function (openItem) {
        if (openItem !== item) {
          openItem.classList.remove("open");
          openItem.querySelector(".faq-answer").style.maxHeight = null;
        }
      });

      if (isOpen) {
        item.classList.remove("open");
        answer.style.maxHeight = null;
      } else {
        item.classList.add("open");
        answer.style.maxHeight = answer.scrollHeight + "px";
      }
    });
  });

  if ("IntersectionObserver" in window) {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add("in-view");
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15 });

    document.querySelectorAll(".reveal").forEach(function (el) {
      observer.observe(el);
    });
  } else {
    document.querySelectorAll(".reveal").forEach(function (el) {
      el.classList.add("in-view");
    });
  }
});
