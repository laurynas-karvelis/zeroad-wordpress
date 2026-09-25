;(() => {
  document.readyState === "loading" ? document.addEventListener("DOMContentLoaded", init) : init()

  function init() {
    initWelcomeNotice()
    initFeatureSelection()
  }

  function initWelcomeNotice() {
    const notice = document.querySelector(".zeroad-welcome-notice")

    if (!notice || typeof zeroadAdmin === "undefined") return

    const nonce = notice.dataset.dismissNonce

    notice.addEventListener("click", (e) => {
      if (e.target.classList.contains("notice-dismiss")) {
        fetch(zeroadAdmin.ajaxurl, {
          body: new URLSearchParams({
            action: "zeroad_dismiss_welcome",
            nonce,
          }),
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
})()
