<?php
include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid">

            <h1 class="mt-4 mb-4">Router Management</h1>

            <!-- Add Router -->
            <div class="card mb-4">
                <div class="card-header">Add / Update Router</div>
                <div class="card-body">
                    <form id="routerForm" class="form-inline">
                        <input type="hidden" id="routerId">
                        <input type="text" id="routerName" class="form-control mr-2 mb-2" placeholder="Router Name" required>
                        <input type="text" id="routerIP" class="form-control mr-2 mb-2" placeholder="IP Address" required>
                        <input type="number" id="routerPort" class="form-control mr-2 mb-2" value="80" placeholder="Port">
                        <input type="password" id="routerPassword" class="form-control mr-2 mb-2" placeholder="Password" required>
                        <button class="btn btn-success mb-2">Save Router</button>
                    </form>
                    <div id="routerMessage" class="mt-2"></div>
                </div>
            </div>

            <!-- Router List -->
            <div class="card">
                <div class="card-header">Current Routers</div>
                <div class="card-body">
                    <table class="table table-bordered" id="routerTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>IP</th>
                                <th>Port</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<style>
.status-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 6px;
}
.online { background:#28a745; }
.offline { background:#dc3545; }
</style>

<script>
// API URL for CRUD operations
const apiUrl = '/api/control.php';

// Load routers from the backend and dynamically check status
async function loadRouters() {
    const res = await fetch(apiUrl);
    const json = await res.json();
    const tbody = document.querySelector('#routerTable tbody');
    tbody.innerHTML = '';

    if (!json.success) return;

    json.routers.forEach(r => {
        // Use the status directly from the backend response
        const status = r.online
            ? `<span class="status-dot online"></span>Online`
            : `<span class="status-dot offline"></span>Offline`;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${r.id}</td>
            <td>${r.name}</td>
            <td>${r.ip}</td>
            <td>${r.port || 80}</td>
            <td>${status}</td>
            <td>
                <button class="btn btn-sm btn-primary"
                    onclick="editRouter(${r.id}, '${r.name}', '${r.ip}', ${r.port || 80})">
                    Edit
                </button>
                <button class="btn btn-sm btn-danger"
                    onclick="deleteRouter(${r.id})">
                    Delete
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Edit router details in the form
function editRouter(id, name, ip, port) {
    routerId.value = id;
    routerName.value = name;
    routerIP.value = ip;
    routerPort.value = port;
    routerPassword.value = '';
}

// Delete router
async function deleteRouter(id) {
    if (!confirm('Delete this router?')) return;

    const res = await fetch(apiUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id, delete: true })
    });

    const json = await res.json();
    if (json.success) {
        alert(json.message); // Show confirmation message (optional)
        loadRouters(); // Reload the list of routers
    } else {
        alert('Error deleting router.'); // Show error message
    }
}

// Add/Update router form submission
routerForm.addEventListener('submit', async e => {
    e.preventDefault();

    const payload = {
        id: routerId.value || undefined,
        name: routerName.value,
        ip: routerIP.value,
        port: routerPort.value,
        password: routerPassword.value
    };

    const res = await fetch(apiUrl, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload)
    });

    const json = await res.json();
    routerMessage.textContent = json.message || '';
    routerForm.reset();
    loadRouters();
});

// Initial load of routers
loadRouters();

// Optional: auto-refresh every 30s
// setInterval(loadRouters, 30000);
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
