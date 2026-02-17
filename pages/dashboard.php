<?php
include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid">
            <h1 class="mt-4 mb-4 text-center">WiFi Routers Dashboard</h1>

            <!-- Routers List -->
            <div id="routersList" class="routers-grid">
                <div class="col-12 text-center text-muted">Loading routers...</div>
            </div>

            <!-- Devices Table (hidden initially) -->
            <div id="devicesSection" style="display:none;">
                <div class="mb-3">
                    <button class="btn btn-secondary" onclick="backToRouters()">← Back to Routers</button>
                </div>
                <h3 id="routerNameHeading" class="mb-3" data-router-id=""></h3>
                <div class="card shadow">
                    <div class="card-body p-0">
                        <table class="table table-bordered table-striped mb-0" id="devicesTable">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Hostname</th>
                                    <th>IP</th>
                                    <th>MAC</th>
                                    <th>Connection</th>
                                    <th>Plans</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="5" class="text-center text-muted">Select a router to view devices</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<style>
/* Routers grid container */
.routers-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
}

/* Professional router cards */
.router-card {
    background: linear-gradient(135deg, #ffffff, #4e73df);
    color: #1a1a1a;
    font-weight: 600;
    border-radius: 16px;
    padding: 25px 20px;
    text-align: center;
    cursor: pointer;
    transition: transform 0.3s, box-shadow 0.3s;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
    height: 200px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.router-card h4 {
    margin-bottom: 8px;
    color: #224abe;
}
.router-card p {
    margin: 3px 0;
    font-size: 0.9rem;
}
.router-card .device-info {
    margin-top: 10px;
    font-size: 0.85rem;
    color: #555;
}
.router-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.2);
}

/* Status Dot */
.status-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 5px;
}
.online { background:#1cc88a; }
.offline { background:#e74a3b; }

.plan-badge {
    display: inline-block;
    background: #4e73df;
    color: #fff;
    padding: 6px 12px;
    border-radius: 16px;
    margin: 3px 2px;
    cursor: pointer;
    font-size: 0.85rem;
    transition: all 0.2s;
}
.plan-badge:hover {
    background: #224abe;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .routers-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
    .routers-grid { grid-template-columns: 1fr; }
}
</style>

<script>
const routersApi = '/api/control.php';
const loginApi   = '/auth/login.php';
const plansApi   = '/api/plans.php';

// Load routers with device counts
async function loadRouters() {
    const res = await fetch(routersApi);
    const json = await res.json();
    const container = document.getElementById('routersList');
    container.innerHTML = '';

    if (!json.success || !json.routers.length) {
        container.innerHTML = `<div class="col-12 text-center text-danger">No routers found</div>`;
        return;
    }

    for (const router of json.routers) {
        // Fetch devices for counts
        const devicesRes = await fetch(`${loginApi}?id=${router.id}`);
        const devicesJson = await devicesRes.json();

        const totalDevices = devicesJson.devices ? devicesJson.devices.length : 0;
        const onlineDevices = devicesJson.devices 
            ? devicesJson.devices.filter(d => d.online).length 
            : 0;

        const status = router.online
            ? `<span class="status-dot online"></span>Online`
            : `<span class="status-dot offline"></span>Offline`;

        const cardDiv = document.createElement('div');
        cardDiv.className = 'router-card';
        cardDiv.innerHTML = `
            <h4>${router.name}</h4>
            <p>${status}</p>
            <div class="device-info">
                Devices: ${totalDevices}<br>
                Online: ${onlineDevices}
            </div>
        `;
        cardDiv.onclick = () => showDevices(router.id, router.name);
        container.appendChild(cardDiv);
    }
}

// Show devices and plans
async function showDevices(routerId, routerName) {
    document.getElementById('routersList').style.display = 'none';
    const section = document.getElementById('devicesSection');
    section.style.display = 'block';
    const heading = document.getElementById('routerNameHeading');
    heading.textContent = routerName;
    heading.dataset.routerId = routerId; // store router ID for applyPlan

    const tbody = document.querySelector('#devicesTable tbody');
    tbody.innerHTML = `<tr><td colspan="5" class="text-center text-info">Loading devices...</td></tr>`;

    try {
        const res = await fetch(`${loginApi}?id=${routerId}`);
        const json = await res.json();

        if (!json.devices || !json.devices.length) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">No devices connected</td></tr>`;
            return;
        }

        const plansRes = await fetch(`${plansApi}?router_id=${routerId}`);
        const plansJson = await plansRes.json();

        tbody.innerHTML = '';
        json.devices.forEach(dev => {
            let plansHTML = '';
            if (plansJson.success && plansJson.plans.length) {
                plansJson.plans.forEach(plan => {
                    let parts = [];
                    if (plan.days) parts.push(`${plan.days}d`);
                    if (plan.hours) parts.push(`${plan.hours}h`);
                    if (plan.minutes) parts.push(`${plan.minutes}m`);
                    let duration = parts.join(' ') || '0m';
                    plansHTML += `<span class="plan-badge" onclick="redirectToAddUser('${dev.mac}', ${plan.id})">
                        ${plan.name} (${duration})
                    </span>`;
                });
            } else {
                plansHTML = '<span class="text-muted">No plans</span>';
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${dev.hostname}</td>
                <td>${dev.ip}</td>
                <td>${dev.mac}</td>
                <td>${dev.type}</td>
                <td>${plansHTML}</td>
            `;
            tbody.appendChild(tr);
        });
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Failed to fetch devices</td></tr>`;
        console.error(err);
    }
}

// Redirect to add_user.php with plan data in URL
function redirectToAddUser(mac, planId) {
    const routerId = document.getElementById('routerNameHeading').dataset.routerId;
    const url = `/add_user?router_id=${routerId}&paid_mac=${mac}&plan_id=${planId}`;
    window.location.href = url;
}

// Back to routers
function backToRouters() {
    document.getElementById('devicesSection').style.display = 'none';
    document.getElementById('routersList').style.display = 'grid';
}

// Initial load
loadRouters();
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
