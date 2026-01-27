const input = document.querySelector("[data-search]");
const rows = Array.from(document.querySelectorAll("tbody tr"));

if (input) {
  input.addEventListener("input", () => {
    const q = input.value.trim().toLowerCase();
    rows.forEach((row) => {
      const text = row.innerText.toLowerCase();
      row.style.display = text.includes(q) ? "" : "none";
    });
  });
}
