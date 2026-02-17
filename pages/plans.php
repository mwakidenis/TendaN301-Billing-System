<?php
include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid">
            <h1 class="mt-4 mb-4">WiFi Plans</h1>

            <!-- Plan Creation Form -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light">Create New Plan</div>
                <div class="card-body">
                    <form id="planForm" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Plan Name</label>
                            <input type="text" id="planName" class="form-control" placeholder="Plan Name" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Days</label>
                            <input type="number" id="planDays" class="form-control" placeholder="0" min="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Hours</label>
                            <input type="number" id="planHours" class="form-control" placeholder="0" min="0" max="23">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Minutes</label>
                            <input type="number" id="planMinutes" class="form-control" placeholder="0" min="0" max="59">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Add Plan</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Plans List -->
            <div id="plansList" class="plans-grid">
                <div class="col-12 text-muted">Loading plans...</div>
            </div>
        </div>
    </section>
</div>

<style>
/* Plans grid */
.plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

/* Plan cards */
.plan-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 30px 20px;
    min-height: 200px; /* good height */
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: 0 6px 15px rgba(0,0,0,0.08);
    position: relative;
    transition: transform 0.2s, box-shadow 0.2s;
}

.plan-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 25px rgba(0,0,0,0.15);
}

.plan-card h5 {
    font-size: 1.4rem;
    margin-bottom: 15px;
    color: #333;
}

.plan-card p {
    font-size: 1.1rem;
    color: #555;
    margin: 0;
}

/* Delete button */
.plan-card .delete-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    background: #e74c3c;
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .plan-card { min-height: 180px; }
}
@media (max-width: 500px) {
    .plan-card { min-height: 160px; }
}
</style>

<script>
const plansApi = '/api/plans.php';

// Load plans
async function loadPlans() {
    const res = await fetch(plansApi);
    const data = await res.json();
    const container = document.getElementById('plansList');
    container.innerHTML = '';

    if (!data.success || !data.plans.length) {
        container.innerHTML = `<div class="col-12 text-muted">No plans available</div>`;
        return;
    }

    data.plans.forEach(plan => {
        const div = document.createElement('div');
        div.className = 'plan-card';
        div.innerHTML = `
            <button class="delete-btn" onclick="deletePlan(${plan.id})">&times;</button>
            <h5>${plan.name}</h5>
            <p>${plan.days}d ${plan.hours}h ${plan.minutes}m</p>
        `;
        container.appendChild(div);
    });
}

// Add plan
document.getElementById('planForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const name = document.getElementById('planName').value.trim();
    const days = parseInt(document.getElementById('planDays').value) || 0;
    const hours = parseInt(document.getElementById('planHours').value) || 0;
    const minutes = parseInt(document.getElementById('planMinutes').value) || 0;

    const res = await fetch(plansApi, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, days, hours, minutes })
    });
    const data = await res.json();

    if (data.success) {
        document.getElementById('planForm').reset();
        loadPlans();
    } else {
        alert(data.error || 'Failed to create plan');
    }
});

// Delete plan
async function deletePlan(id) {
    if (!confirm('Are you sure you want to delete this plan?')) return;

    const res = await fetch(plansApi, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    });
    const data = await res.json();
    if (data.success) loadPlans();
    else alert(data.error || 'Failed to delete plan');
}

// Initial load
loadPlans();
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
