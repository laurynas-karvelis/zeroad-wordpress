document.querySelectorAll(".za-header").forEach((header) => {
  header.addEventListener("click", () => {
    const body = header.nextElementSibling;
    body.classList.toggle("open");
  });
});
