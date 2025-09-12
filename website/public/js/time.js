  function updateTime() {
    const options = {
      timeZone: "Asia/Manila",
      year: "numeric",
      month: "long",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
      hour12: true
    };

    const now = new Date().toLocaleString("en-US", options);
    document.getElementById("ph-time").textContent = now.replace(",", " -");
  }

  // Update every second
  setInterval(updateTime, 1000);

  // Run once on page load
  updateTime();