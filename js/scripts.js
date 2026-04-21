// Sistema SAT - JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initTooltips();
    initConfirmDelete();
    initAutoHideAlerts();
});

function initTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

function initConfirmDelete() {
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-delete') || e.target.closest('.btn-delete')) {
            if (!confirm('¿Está seguro de que desea eliminar este elemento?')) {
                e.preventDefault();
                return false;
            }
        }
    });
}

function initAutoHideAlerts() {
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
}

function showLoading() {
    var loading = document.createElement('div');
    loading.id = 'loading-overlay';
    loading.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>';
    loading.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.8);display:flex;justify-content:center;align-items:center;z-index:9999;';
    document.body.appendChild(loading);
}

function hideLoading() {
    var loading = document.getElementById('loading-overlay');
    if (loading) {
        loading.remove();
    }
}

function formatCurrency(amount) {
    return 'S/ ' + parseFloat(amount).toFixed(2);
}

function ajaxPost(url, data, callback) {
    showLoading();
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(data)
    })
    .then(response => response.text())
    .then(data => {
        hideLoading();
        callback(data);
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('Ocurrió un error. Por favor, intente de nuevo.');
    });
}

function actualizarEstadoOrden(ordenId, nuevoEstado) {
    if (!confirm('¿Cambiar el estado de la orden?')) {
        return;
    }
    
    ajaxPost(
        'cambiar_estado.php',
        { id: ordenId, estado: nuevoEstado },
        function(response) {
            if (response === 'OK') {
                location.reload();
            } else {
                alert('Error al actualizar el estado');
            }
        }
    );
}

function buscarEnTabla(inputId, tableId) {
    var input = document.getElementById(inputId);
    var table = document.getElementById(tableId);
    var filter = input.value.toUpperCase();
    var tr = table.getElementsByTagName('tr');
    
    for (var i = 1; i < tr.length; i++) {
        var found = false;
        var td = tr[i].getElementsByTagName('td');
        for (var j = 0; j < td.length; j++) {
            if (td[j]) {
                var txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        tr[i].style.display = found ? '' : 'none';
    }
}

function validarFormulario(formId) {
    var form = document.getElementById(formId);
    if (!form) return true;
    
    var inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    var valid = true;
    
    inputs.forEach(function(input) {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            valid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return valid;
}

document.addEventListener('input', function(e) {
    if (e.target.classList.contains('form-control') && e.target.hasAttribute('required')) {
        if (e.target.value.trim()) {
            e.target.classList.remove('is-invalid');
        }
    }
});

var lazyImages = [].slice.call(document.querySelectorAll('img[data-src]'));
if ('IntersectionObserver' in window) {
    var lazyImageObserver = new IntersectionObserver(function(entries, observer) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                var lazyImage = entry.target;
                lazyImage.src = lazyImage.dataset.src;
                lazyImage.classList.remove('lazy');
                lazyImageObserver.unobserve(lazyImage);
            }
        });
    });
    lazyImages.forEach(function(lazyImage) {
        lazyImageObserver.observe(lazyImage);
    });
}