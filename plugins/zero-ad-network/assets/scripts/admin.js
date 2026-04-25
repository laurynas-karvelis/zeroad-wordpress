;(function () {
  "use strict"

  document.readyState === "loading" ? document.addEventListener("DOMContentLoaded", init) : init()

  function init() {
    initAccordions()
    initWelcomeNotice()
    initFeatureSelection()
  }

  function initAccordions() {
    document.querySelectorAll(".za-header").forEach((header) => {
      header.setAttribute("role", "button")
      header.setAttribute("tabindex", "0")
      header.setAttribute("aria-expanded", "false")

      const toggle = () => {
        const item = header.parentElement
        const isOpen = item.classList.toggle("open")
        header.nextElementSibling.classList.toggle("open", isOpen)
        header.setAttribute("aria-expanded", String(isOpen))

        if (isOpen) {
          setTimeout(() => {
            const r = item.getBoundingClientRect()
            if (r.top < 0 || r.bottom > window.innerHeight) {
              item.scrollIntoView({ behavior: "smooth", block: "nearest" })
            }
          }, 200)
        }
      }

      header.addEventListener("click", toggle)
      header.addEventListener("keydown", (e) => {
        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault()
          toggle()
        }
      })
    })
  }

  function initWelcomeNotice() {
    const notice = document.querySelector(".zeroad-welcome-notice")
    if (!notice || zeroadAdmin === undefined) return

    const nonce = notice.dataset.dismissNonce

    notice.addEventListener("click", (e) => {
      if (e.target.classList.contains("notice-dismiss")) {
        fetch(zeroadAdmin.ajaxurl, {
          body: new URLSearchParams({ action: "zeroad_dismiss_welcome", nonce }),
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          method: "POST",
        })
      }
    })
  }

  function initFeatureSelection() {
    document.querySelectorAll(".zeroad-feature-box").forEach((box) => {
      const cb = box.querySelector('input[type="checkbox"]')
      if (!cb) return
      box.classList.toggle("selected", cb.checked)
      cb.addEventListener("change", () => box.classList.toggle("selected", cb.checked))
    })
  }

  document.querySelectorAll('a[href^="#"]').forEach((a) => {
    a.addEventListener("click", function (e) {
      const target = document.querySelector(this.getAttribute("href"))
      if (!target) return
      e.preventDefault()
      target.scrollIntoView({ behavior: "smooth", block: "start" })
      history.pushState?.(null, "", this.getAttribute("href"))
    })
  })

  document.querySelectorAll("form").forEach((form) => {
    form.addEventListener("submit", function () {
      const btn = this.querySelector('input[type="submit"], button[type="submit"]')
      if (!btn || btn.classList.contains("disabled")) return
      btn.classList.add("disabled")
      setTimeout(() => btn.classList.remove("disabled"), 5000)
    })
  })
})()
