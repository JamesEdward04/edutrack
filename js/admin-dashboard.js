document.addEventListener("DOMContentLoaded", () => {
  // Hamburger toggle 
  const hamburger = document.getElementById("hamburger");
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("page-overlay");

  if (hamburger) {
    hamburger.addEventListener("click", () => {
      sidebar.classList.toggle("open");
      overlay.classList.toggle("show");
    });
  }
  if (overlay) {
    overlay.addEventListener("click", () => {
      sidebar.classList.remove("open");
      overlay.classList.remove("show");
    });
  }

  // === LIVE COUNTS ===
  const studentEl = document.querySelector(".card:nth-child(1) .card-stat");
  const gradeEl = document.querySelector(".card:nth-child(2) .card-stat");
  const attEl = document.querySelector(".card:nth-child(3) .card-stat");

  async function fetchCounts() {
    try {
      const res = await fetch("counts.php", { cache: "no-store" });
      if (!res.ok) throw new Error("Network response was not ok");
      const data = await res.json();
      if (data.error) throw new Error(data.error);

      // Update DOM
      studentEl.textContent = data.students ?? "0";
      gradeEl.textContent = data.grades ?? "0";
      attEl.textContent = data.attendance ?? "0";
    } catch (err) {
      console.error("Error fetching counts:", err);
    }
  }

  // Fetch immediately + every 10 seconds
  fetchCounts();
  setInterval(fetchCounts, 10000);
});
