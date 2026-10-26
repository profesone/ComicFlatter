const input = document.getElementById("lineart");
const preview = document.getElementById("preview");
if (input && preview) {
  input.addEventListener("change", () => {
    const file = input.files && input.files[0];
    if (!file) return;
    preview.hidden = false;
    preview.alt = "Upload preview";
    preview.src = URL.createObjectURL(file);
  });
}
