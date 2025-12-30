/**
 * Zero Ad Network - Admin JavaScript
 * Handles accordion interactions, copy-to-clipboard, and other admin UI functionality
 */

(function () {
  "use strict";

  // Wait for DOM to be ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }

  function init() {
    initAccordions();
    initCopyButtons();
    initWelcomeNotice();
    initFeatureSelection();
  }

  /**
   * Initialize accordion functionality for cache config page
   */
  function initAccordions() {
    const headers = document.querySelectorAll(".za-header");

    headers.forEach((header) => {
      header.addEventListener("click", function () {
        const item = this.parentElement;
        const body = this.nextElementSibling;

        // Toggle current accordion
        const isOpen = item.classList.contains("open");

        if (isOpen) {
          // Close
          item.classList.remove("open");
          body.classList.remove("open");
        } else {
          // Open
          item.classList.add("open");
          body.classList.add("open");

          // Scroll into view if opening and not fully visible
          setTimeout(() => {
            const rect = item.getBoundingClientRect();
            const isFullyVisible = rect.top >= 0 && rect.bottom <= window.innerHeight;

            if (!isFullyVisible) {
              item.scrollIntoView({ behavior: "smooth", block: "nearest" });
            }
          }, 200);
        }
      });

      // Make keyboard accessible
      header.setAttribute("role", "button");
      header.setAttribute("tabindex", "0");
      header.setAttribute("aria-expanded", "false");

      header.addEventListener("keydown", function (e) {
        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault();
          this.click();
        }
      });
    });

    // Update aria-expanded on open/close
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.attributeName === "class") {
          const item = mutation.target;
          const header = item.querySelector(".za-header");
          if (header) {
            const isOpen = item.classList.contains("open");
            header.setAttribute("aria-expanded", isOpen.toString());
          }
        }
      });
    });

    document.querySelectorAll(".za-item").forEach((item) => {
      observer.observe(item, { attributes: true });
    });
  }

  /**
   * Initialize copy-to-clipboard buttons for code blocks
   */
  function initCopyButtons() {
    // Find all pre code blocks and add copy buttons
    const codeBlocks = document.querySelectorAll(".za-body pre");

    codeBlocks.forEach((pre) => {
      // Skip if already has a copy button
      if (pre.parentElement.classList.contains("zeroad-copy-wrapper")) {
        return;
      }

      // Create wrapper
      const wrapper = document.createElement("div");
      wrapper.className = "zeroad-copy-wrapper";
      wrapper.style.position = "relative";

      // Create copy button
      const button = document.createElement("button");
      button.className = "button button-secondary zeroad-copy-button";
      button.textContent = "Copy";
      button.type = "button";

      // Wrap pre element
      pre.parentNode.insertBefore(wrapper, pre);
      wrapper.appendChild(pre);
      wrapper.appendChild(button);

      // Add click handler
      button.addEventListener("click", async function () {
        const code = pre.querySelector("code");
        const text = code ? code.textContent : pre.textContent;

        try {
          await navigator.clipboard.writeText(text);

          // Update button state
          button.classList.add("copied");
          button.textContent = "Copied!";

          // Reset after 2 seconds
          setTimeout(() => {
            button.classList.remove("copied");
            button.textContent = "Copy";
          }, 2000);
        } catch (err) {
          console.error("Failed to copy:", err);

          // Fallback for older browsers
          fallbackCopy(text);
          button.textContent = "Copied!";
          setTimeout(() => {
            button.textContent = "Copy";
          }, 2000);
        }
      });
    });
  }

  /**
   * Fallback copy method for browsers without clipboard API
   */
  function fallbackCopy(text) {
    const textarea = document.createElement("textarea");
    textarea.value = text;
    textarea.style.position = "fixed";
    textarea.style.opacity = "0";
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand("copy");
    document.body.removeChild(textarea);
  }

  /**
   * Handle welcome notice dismissal
   */
  function initWelcomeNotice() {
    const welcomeNotice = document.querySelector(".zeroad-welcome-notice");

    if (welcomeNotice && typeof zeroadAdmin !== "undefined") {
      // WordPress automatically handles the dismiss button click for is-dismissible notices
      // We just need to save the state when the notice is dismissed

      // Observe when the notice is removed from DOM
      const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
          mutation.removedNodes.forEach((node) => {
            if (node === welcomeNotice) {
              // Notice was dismissed, send AJAX to save state
              const nonce = welcomeNotice.dataset.dismissNonce;

              if (nonce && zeroadAdmin.ajaxurl) {
                fetch(zeroadAdmin.ajaxurl, {
                  method: "POST",
                  headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                  },
                  body: new URLSearchParams({
                    action: "zeroad_dismiss_welcome",
                    nonce: nonce,
                  }),
                })
                  .then((response) => response.json())
                  .then((data) => {
                    if (window.console && data.success) {
                      console.log("Zero Ad Network: Welcome notice dismissed");
                    }
                  })
                  .catch((error) => {
                    if (window.console) {
                      console.error("Zero Ad Network: Failed to dismiss notice", error);
                    }
                  });
              }

              observer.disconnect();
            }
          });
        });
      });

      // Start observing the parent for removals
      if (welcomeNotice.parentNode) {
        observer.observe(welcomeNotice.parentNode, { childList: true });
      }
    }
  }

  /**
   * Enhance feature selection boxes
   */
  function initFeatureSelection() {
    const featureBoxes = document.querySelectorAll(".zeroad-feature-box");

    featureBoxes.forEach((box) => {
      const checkbox = box.querySelector('input[type="checkbox"]');

      if (checkbox) {
        // Initial state
        updateBoxState(box, checkbox.checked);

        // Update on change
        checkbox.addEventListener("change", function () {
          updateBoxState(box, this.checked);
          calculateTotalRevenue();
        });
      }
    });

    // Calculate initial revenue
    calculateTotalRevenue();
  }

  /**
   * Update feature box visual state
   */
  function updateBoxState(box, isSelected) {
    if (isSelected) {
      box.classList.add("selected");
    } else {
      box.classList.remove("selected");
    }
  }

  /**
   * Calculate and display total potential revenue
   */
  function calculateTotalRevenue() {
    const revenueDisplay = document.querySelector(".zeroad-total-revenue");
    if (!revenueDisplay) return;

    const checkboxes = document.querySelectorAll('.zeroad-feature-box input[type="checkbox"]:checked');
    let total = 0;

    checkboxes.forEach((checkbox) => {
      const value = parseInt(checkbox.dataset.revenue || "0");
      total += value;
    });

    // Update display
    revenueDisplay.textContent = "$" + total.toFixed(0);

    // Add animation
    revenueDisplay.style.transform = "scale(1.1)";
    setTimeout(() => {
      revenueDisplay.style.transform = "scale(1)";
    }, 200);
  }

  /**
   * Add smooth scroll to links with hash
   */
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      const href = this.getAttribute("href");
      if (href === "#") return;

      const target = document.querySelector(href);
      if (target) {
        e.preventDefault();
        target.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });

        // Update URL without jumping
        if (history.pushState) {
          history.pushState(null, null, href);
        }
      }
    });
  });

  /**
   * Add loading state to forms
   */
  document.querySelectorAll("form").forEach((form) => {
    form.addEventListener("submit", function () {
      const submitButton = this.querySelector('input[type="submit"], button[type="submit"]');

      if (submitButton && !submitButton.classList.contains("disabled")) {
        submitButton.classList.add("disabled");
        submitButton.value = submitButton.value || "Saving...";

        // Re-enable after 5 seconds as fallback
        setTimeout(() => {
          submitButton.classList.remove("disabled");
        }, 5000);
      }
    });
  });

  /**
   * Initialize tooltips (if needed in future)
   */
  function initTooltips() {
    // Add tooltip functionality here if needed
  }

  /**
   * Debug helper (remove in production)
   */
  if (window.location.search.includes("zeroad_debug=1")) {
    console.log("Zero Ad Network Admin JS loaded");
    window.zeroadDebug = {
      initAccordions,
      initCopyButtons,
      calculateTotalRevenue,
    };
  }
})();
