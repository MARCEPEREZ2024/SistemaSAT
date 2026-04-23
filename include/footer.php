<?php if (isLoggedIn()): ?>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>var BASE_URL = '<?= BASE_URL ?>';</script>
<script src="<?= BASE_URL ?>js/scripts.js"></script>
<script src="<?= BASE_URL ?>js/ajax_utils.js"></script>
<script>
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

function actualizarBadges() {
    Promise.all([
        fetch(BASE_URL + 'api/chat_messages.php?action=contar', {cache: 'no-cache'}).then(function(r) { return r.json(); }),
        fetch(BASE_URL + 'api/notifications.php', {cache: 'no-cache'}).then(function(r) { return r.json(); })
    ])
    .then(function(results) {
        var chatNoLeidos = results[0].no_leidos || 0;
        var notifNoLeidas = results[1].no_leidas || 0;
        var totalNoLeidos = chatNoLeidos + notifNoLeidas;
        
        var chatBadge = document.getElementById('chatBadge');
        var notifBadge = document.getElementById('notifBadge');
        
        if (chatBadge) {
            if (chatNoLeidos > 0) {
                chatBadge.textContent = chatNoLeidos > 99 ? '99+' : chatNoLeidos;
                chatBadge.style.display = 'block';
            } else {
                chatBadge.style.display = 'none';
            }
        }
        
        if (notifBadge) {
            if (notifNoLeidas > 0) {
                notifBadge.textContent = notifNoLeidas > 99 ? '99+' : notifNoLeidas;
                notifBadge.style.display = 'inline';
            } else {
                notifBadge.style.display = 'none';
            }
        }
    })
    .catch(function() {});
}

if (typeof BASE_URL !== 'undefined') {
    actualizarBadges();
    setInterval(actualizarBadges, 10000);
}
</script>
<style>
.toast-message {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 5px;
    color: white;
    z-index: 9999;
    opacity: 0;
    transition: opacity 0.3s;
}
.toast-message.show { opacity: 1; }
.toast-success { background: #198754; }
.toast-error { background: #dc3545; }
.toast-info { background: #0dcaf0; color: #000; }
.toast-warning { background: #ffc107; color: #000; }
</style>
</body>
</html>
<?php endif; ?>