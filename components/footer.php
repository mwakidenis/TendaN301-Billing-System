<!-- components/footer.php -->
<style>
/* ===== Footer Styling ===== */
.main-footer {
    background: #23272b; /* matches sidebar brand */
    color: #fff;
    padding: 1rem 1.5rem;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #3c4043;
    position: relative; /* keeps footer at bottom if content is short */
    z-index: 500;
}

/* Copyright text */
.main-footer strong {
    color: #007bff; /* blue accent */
}

/* Optional hover effect for links if any */
.main-footer a {
    color: #ffc107; /* yellow accent for links */
    text-decoration: none;
    transition: color 0.25s ease;
}
.main-footer a:hover {
    color: #28a745; /* green on hover */
}

/* Control sidebar fix */
.control-sidebar {
    background: #343a40; /* match dashboard background */
}
</style>

<!-- Control sidebar -->
<aside class="control-sidebar control-sidebar-dark"></aside>

<!-- Footer -->
<footer class="main-footer">
    <strong>&copy; <?= date('Y') ?> jasiri billing.</strong>
    <span>All rights reserved.</span>
</footer>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
