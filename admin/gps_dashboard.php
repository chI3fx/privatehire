<?php
include "../auth/check_auth.php";
include "../config/db.php";
include "../navbar.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}
?>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-1">GPS Dispatch Dashboard (Prototype)</h2>
            <small class="text-muted">Simulated hardcoded telemetry for Sprint story demo</small>
        </div>
        <div class="text-end">
            <div><strong>Next Refresh In:</strong> <span id="refreshCountdown">30</span>s</div>
            <button class="btn btn-sm btn-outline-primary mt-1" id="manualRefreshBtn">Refresh Now</button>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <label class="form-label">Vehicle Type</label>
            <select class="form-select" id="typeFilter"></select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Size</label>
            <select class="form-select" id="sizeFilter"></select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Location</label>
            <select class="form-select" id="locationFilter"></select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Availability</label>
            <select class="form-select" id="statusFilter">
                <option value="all">All</option>
                <option value="available">Available</option>
                <option value="on_trip">On Trip</option>
                <option value="maintenance">Maintenance</option>
                <option value="suspended">Suspended</option>
            </select>
        </div>
    </div>

    <div class="row g-3 mb-3" id="summaryCards"></div>

    <div class="card mb-3">
        <div class="card-header"><strong>Live Fleet Grid (Simulated)</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Vehicle</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Driver</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>ETA</th>
                            <th>Last Update</th>
                        </tr>
                    </thead>
                    <tbody id="fleetTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Map View (UI Placeholder)</strong></div>
        <div class="card-body">
            <div class="border rounded p-4 bg-light">
                <p class="mb-1"><strong>Dispatch Heat Zones:</strong> Westlands, CBD, Kilimani, Kileleshwa, Karen, JKIA</p>
                <p class="mb-0 text-muted">For this sprint demo, location markers are represented in the table and summaries. API map integration can plug in later.</p>
            </div>
        </div>
    </div>
</div>

<script>
const hardcodedFleet = [
  { id: 1, vehicle: "Toyota Prius - KDA 120A", type: "Sedan", size: "4-seater", driver: "John Brown", location: "Westlands", status: "available", eta: "5 min", lastUpdate: "Just now" },
  { id: 2, vehicle: "Mercedes Vito - KDB 220B", type: "Van", size: "7-seater", driver: "David Clark", location: "CBD", status: "on_trip", eta: "18 min", lastUpdate: "Just now" },
  { id: 3, vehicle: "BMW Executive - KDC 320C", type: "Executive", size: "4-seater", driver: "Michael White", location: "Kilimani", status: "available", eta: "7 min", lastUpdate: "Just now" },
  { id: 4, vehicle: "Cadillac Escalade - KDD 420D", type: "SUV", size: "6-seater", driver: "James Green", location: "JKIA", status: "maintenance", eta: "N/A", lastUpdate: "Just now" },
  { id: 5, vehicle: "Toyota Noah - KDE 520E", type: "Van", size: "7-seater", driver: "Daniel Hill", location: "Kileleshwa", status: "available", eta: "10 min", lastUpdate: "Just now" },
  { id: 6, vehicle: "Nissan Note - KDF 620F", type: "Hatchback", size: "4-seater", driver: "Jet Jefferson", location: "Karen", status: "suspended", eta: "N/A", lastUpdate: "Just now" },
  { id: 7, vehicle: "Toyota Premio - KDG 720G", type: "Sedan", size: "4-seater", driver: "Anne Mwangi", location: "CBD", status: "on_trip", eta: "22 min", lastUpdate: "Just now" },
  { id: 8, vehicle: "Mazda CX-5 - KDH 820H", type: "SUV", size: "5-seater", driver: "Peter Maina", location: "Westlands", status: "available", eta: "6 min", lastUpdate: "Just now" }
];

const state = {
  rows: JSON.parse(JSON.stringify(hardcodedFleet)),
  refreshSeconds: 30
};

const statusLabel = {
  available: { text: "Available", badge: "bg-success" },
  on_trip: { text: "On Trip", badge: "bg-primary" },
  maintenance: { text: "Maintenance", badge: "bg-warning text-dark" },
  suspended: { text: "Suspended", badge: "bg-secondary" }
};

function uniqueOptions(rows, key) {
  return ["all", ...new Set(rows.map(r => r[key]))];
}

function populateFilter(selectId, values) {
  const select = document.getElementById(selectId);
  select.innerHTML = "";
  values.forEach(v => {
    const o = document.createElement("option");
    o.value = v;
    o.textContent = v === "all" ? "All" : v;
    select.appendChild(o);
  });
}

function filteredRows() {
  const type = document.getElementById("typeFilter").value;
  const size = document.getElementById("sizeFilter").value;
  const location = document.getElementById("locationFilter").value;
  const status = document.getElementById("statusFilter").value;

  return state.rows.filter(r =>
    (type === "all" || r.type === type) &&
    (size === "all" || r.size === size) &&
    (location === "all" || r.location === location) &&
    (status === "all" || r.status === status)
  );
}

function renderSummary(rows) {
  const total = rows.length;
  const available = rows.filter(r => r.status === "available").length;
  const onTrip = rows.filter(r => r.status === "on_trip").length;
  const unavailable = rows.filter(r => ["maintenance", "suspended"].includes(r.status)).length;

  const cards = [
    { title: "Visible Vehicles", value: total, style: "dark" },
    { title: "Available", value: available, style: "success" },
    { title: "On Trip", value: onTrip, style: "primary" },
    { title: "Unavailable", value: unavailable, style: "warning" }
  ];

  document.getElementById("summaryCards").innerHTML = cards.map(c => `
    <div class="col-md-3">
      <div class="card text-bg-${c.style}">
        <div class="card-body">
          <h6 class="card-title mb-1">${c.title}</h6>
          <div class="fs-3 fw-bold">${c.value}</div>
        </div>
      </div>
    </div>
  `).join("");
}

function renderTable(rows) {
  const tbody = document.getElementById("fleetTableBody");
  if (!rows.length) {
    tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-4">No vehicles match current filters.</td></tr>`;
    return;
  }

  tbody.innerHTML = rows.map(r => `
    <tr>
      <td>${r.vehicle}</td>
      <td>${r.type}</td>
      <td>${r.size}</td>
      <td>${r.driver}</td>
      <td>${r.location}</td>
      <td><span class="badge ${statusLabel[r.status].badge}">${statusLabel[r.status].text}</span></td>
      <td>${r.eta}</td>
      <td>${r.lastUpdate}</td>
    </tr>
  `).join("");
}

function rerender() {
  const rows = filteredRows();
  renderSummary(rows);
  renderTable(rows);
}

function simulateRefresh() {
  const now = new Date();
  state.rows = state.rows.map(r => {
    const next = { ...r, lastUpdate: now.toLocaleTimeString() };
    if (r.status === "on_trip") {
      const etaNum = parseInt(r.eta, 10);
      if (!isNaN(etaNum)) {
        next.eta = `${Math.max(5, etaNum - 1)} min`;
      }
    }
    return next;
  });
  rerender();
}

function setupFilters() {
  populateFilter("typeFilter", uniqueOptions(state.rows, "type"));
  populateFilter("sizeFilter", uniqueOptions(state.rows, "size"));
  populateFilter("locationFilter", uniqueOptions(state.rows, "location"));

  ["typeFilter", "sizeFilter", "locationFilter", "statusFilter"].forEach(id => {
    document.getElementById(id).addEventListener("change", rerender);
  });
}

function startAutoRefresh() {
  const countdownEl = document.getElementById("refreshCountdown");
  setInterval(() => {
    state.refreshSeconds -= 1;
    if (state.refreshSeconds <= 0) {
      simulateRefresh();
      state.refreshSeconds = 30;
    }
    countdownEl.textContent = state.refreshSeconds;
  }, 1000);
}

document.getElementById("manualRefreshBtn").addEventListener("click", () => {
  simulateRefresh();
  state.refreshSeconds = 30;
  document.getElementById("refreshCountdown").textContent = state.refreshSeconds;
});

setupFilters();
rerender();
startAutoRefresh();
</script>

