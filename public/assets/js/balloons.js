const root = document.querySelector(".letter-tool");
const csrf = root.dataset.csrf;
let pageId = "";

const zones = [
  ["top-left", "top-center", "top-right"],
  ["middle-left", "middle-center", "middle-right"],
  ["bottom-left", "bottom-center", "bottom-right"],
];

document.getElementById("page-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const status = document.getElementById("upload-status");
  status.textContent = "Uploading…";
  const body = new FormData(event.currentTarget);
  const response = await fetch("/balloons/upload", {
    method: "POST",
    headers: { "X-CSRF-Token": csrf },
    body,
  });
  const data = await response.json();
  if (!response.ok) {
    status.textContent = data.error || "Upload failed.";
    return;
  }
  pageId = data.pageId;
  const img = document.getElementById("page-img");
  img.src = data.pageUrl;
  document.getElementById("preview").hidden = false;
  document.getElementById("mapper").hidden = false;
  status.textContent = `${data.width}×${data.height}`;
});

document.getElementById("build-lines").addEventListener("click", () => {
  const rows = document.getElementById("script").value.split("\n").map((line) => line.trim()).filter(Boolean);
  const host = document.getElementById("lines");
  host.innerHTML = "";
  rows.forEach((text, index) => host.appendChild(lineCard(text, index)));
});

document.getElementById("generate").addEventListener("click", async () => {
  const status = document.getElementById("gen-status");
  if (!pageId) {
    status.textContent = "Upload a page first.";
    return;
  }
  const lines = [...document.querySelectorAll(".line-card")].map((card) => ({
    text: card.querySelector("textarea").value,
    style: card.querySelector("[name=style]").value,
    panel: Number(card.querySelector("[name=panel]").value),
    balloonZone: card.querySelector("[data-kind=balloon] [aria-pressed=true]").dataset.zone,
    speakerZone: card.querySelector("[data-kind=speaker] [aria-pressed=true]")?.dataset.zone || "off-panel",
  }));
  status.textContent = "Drawing…";
  const response = await fetch("/balloons/generate", {
    method: "POST",
    headers: { "Content-Type": "application/json", "X-CSRF-Token": csrf },
    body: JSON.stringify({
      pageId,
      panelCount: Number(document.getElementById("panel-count").value),
      lines,
    }),
  });
  const data = await response.json();
  if (!response.ok) {
    status.textContent = data.error || "Could not draw balloons.";
    return;
  }
  document.getElementById("overlay-img").src = data.overlayUrl + "?t=" + Date.now();
  status.textContent = "Overlay ready.";
});

function lineCard(text, index) {
  const card = document.createElement("article");
  card.className = "line-card card";
  card.innerHTML = `
    <label>Line ${index + 1}
      <textarea rows="2"></textarea>
    </label>
    <label>Style
      <select name="style">
        <option>Standard</option>
        <option>Burst</option>
        <option>Wavy</option>
        <option>Whisper</option>
        <option>SFX</option>
      </select>
    </label>
    <label>Panel
      <input name="panel" type="number" min="1" max="9" value="${index + 1}">
    </label>
    <p>Balloon zone</p>
    <div class="zone-grid" data-kind="balloon"></div>
    <p>Speaker</p>
    <div class="zone-grid" data-kind="speaker"></div>
    <button type="button" class="linkish off-panel" aria-pressed="true">Off-panel</button>
  `;
  card.querySelector("textarea").value = text;
  fillGrid(card.querySelector('[data-kind=balloon]'), "top-left");
  fillGrid(card.querySelector('[data-kind=speaker]'), null);
  card.querySelector(".off-panel").addEventListener("click", (event) => {
    card.querySelectorAll('[data-kind=speaker] button').forEach((button) => button.setAttribute("aria-pressed", "false"));
    event.currentTarget.setAttribute("aria-pressed", "true");
  });
  return card;
}

function fillGrid(grid, selected) {
  zones.flat().forEach((zone) => {
    const button = document.createElement("button");
    button.type = "button";
    button.dataset.zone = zone;
    button.setAttribute("aria-pressed", zone === selected ? "true" : "false");
    button.setAttribute("aria-label", zone);
    button.addEventListener("click", () => {
      grid.querySelectorAll("button").forEach((item) => item.setAttribute("aria-pressed", "false"));
      button.setAttribute("aria-pressed", "true");
      const off = grid.parentElement.querySelector(".off-panel");
      if (off && grid.dataset.kind === "speaker") off.setAttribute("aria-pressed", "false");
    });
    grid.appendChild(button);
  });
}
