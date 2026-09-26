;(() => {
  document.readyState === "loading" ? document.addEventListener("DOMContentLoaded", init) : init()

  function init() {
    initWelcomeNotice()
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
})()
