// Búsqueda en tiempo real AJAX
(function() {
    'use strict';
    
    let searchTimeout = null;
    let searchInput = null;
    let resultsContainer = null;
    let searchUrl = null;
    let searchAction = null;
    
    window.initAjaxSearch = function(inputId, resultsId, url, action) {
        searchInput = document.getElementById(inputId);
        resultsContainer = document.getElementById(resultsId);
        searchUrl = url;
        searchAction = action;
        
        if (!searchInput || !resultsContainer) return;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                resultsContainer.innerHTML = '';
                resultsContainer.style.display = 'none';
                return;
            }
            
            searchTimeout = setTimeout(function() {
                performSearch(query);
            }, 300);
        });
        
        searchInput.addEventListener('focus', function() {
            if (this.value.trim().length >= 2) {
                resultsContainer.style.display = 'block';
            }
        });
        
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                resultsContainer.style.display = 'none';
            }
        });
    };
    
    function performSearch(query) {
        const url = searchUrl + '?action=' + searchAction + '&q=' + encodeURIComponent(query);
        
        fetch(url)
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.results && data.results.length > 0) {
                    displayResults(data.results);
                } else {
                    resultsContainer.innerHTML = '<div class="p-2 text-muted">Sin resultados</div>';
                    resultsContainer.style.display = 'block';
                }
            })
            .catch(function(error) {
                console.error('Error en búsqueda:', error);
            });
    }
    
    function displayResults(results) {
        let html = '';
        
        results.forEach(function(item) {
            let href = '#';
            if (searchAction === 'ordenes') {
                href = BASE_URL + 'ordenes/ver.php?id=' + item.id;
            } else if (searchAction === 'clientes') {
                href = BASE_URL + 'clientes/ver.php?id=' + item.id;
            } else if (searchAction === 'inventario') {
                href = BASE_URL + 'inventario/ver.php?id=' + item.id;
            } else if (searchAction === 'equipos') {
                href = BASE_URL + 'equipos/ver.php?id=' + item.id;
            }
            
            html += '<a href="' + href + '" class="dropdown-item">' + item.text + '</a>';
        });
        
        resultsContainer.innerHTML = html;
        resultsContainer.style.display = 'block';
    }
})();

// Gráficos con Chart.js
(function() {
    'use strict';
    
    window.loadChart = function(canvasId, url, type, options) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return null;
        
        fetch(url)
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                renderChart(canvas, data, type, options);
            })
            .catch(function(error) {
                console.error('Error cargando gráfico:', error);
            });
        
        return null;
    };
    
    function renderChart(canvas, data, type, options) {
        const ctx = canvas.getContext('2d');
        const defaultColors = [
            '#0d6efd', '#198754', '#dc3545', '#ffc107', '#6c757d', 
            '#0dcaf0', '#6610f2', '#e56e00', '#20c997', '#fd7e14'
        ];
        
        if (type === 'doughnut' || type === 'pie') {
            new Chart(ctx, {
                type: type,
                data: {
                    labels: Object.keys(data),
                    datasets: [{
                        data: Object.values(data),
                        backgroundColor: defaultColors
                    }]
                },
                options: options || {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        } else if (type === 'line' || type === 'bar') {
            const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            const valores = [];
            
            for (let i = 1; i <= 12; i++) {
                valores.push(data[i] || 0);
            }
            
            new Chart(ctx, {
                type: type,
                data: {
                    labels: meses,
                    datasets: [{
                        label: 'Datos',
                        data: valores,
                        backgroundColor: type === 'bar' ? '#0d6efd' : 'rgba(13, 110, 253, 0.1)',
                        borderColor: '#0d6efd',
                        fill: type === 'line'
                    }]
                },
                options: options || {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }
    };
})();

// Notificaciones toast
(function() {
    'use strict';
    
    window.showToast = function(message, type) {
        type = type || 'info';
        
        const toast = document.createElement('div');
        toast.className = 'toast-message toast-' + type;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(function() {
            toast.classList.add('show');
        }, 100);
        
        setTimeout(function() {
            toast.classList.remove('show');
            setTimeout(function() {
                toast.remove();
            }, 300);
        }, 3000);
    };
})();

// Loading spinner
(function() {
    'use strict';
    
    window.showLoading = function(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        
        if (element) {
            element.innerHTML = '<div class="text-center"><span class="spinner-border spinner-border-sm"></span> Cargando...</div>';
        }
    };
    
    window.hideLoading = function(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        
        if (element) {
            const spinner = element.querySelector('.spinner-border');
            if (spinner) {
                spinner.parentElement.remove();
            }
        }
    };
})();

// Confirmar acciones
(function() {
    'use strict';
    
    window.confirmAction = function(message, callback) {
        if (confirm(message)) {
            callback();
        }
    };
})();

// Notificaciones WebSocket
(function() {
    'use strict';
    
    let ws = null;
    let reconnectAttempts = 0;
    const maxReconnectAttempts = 5;
    let baseUrl = null;
    
    window.initWebSocketNotifications = function(url) {
        baseUrl = url;
        connect();
    };
    
    function connect() {
        if (ws && ws.readyState === WebSocket.OPEN) return;
        
        const wsUrl = 'ws://localhost:8080';
        
        try {
            ws = new WebSocket(wsUrl);
            
            ws.onopen = function() {
                reconnectAttempts = 0;
                console.log('WebSocket conectado');
                
                if (typeof userId !== 'undefined') {
                    ws.send(JSON.stringify({
                        type: 'subscribe',
                        user_id: userId
                    }));
                }
            };
            
            ws.onmessage = function(e) {
                try {
                    const msg = JSON.parse(e.data);
                    
                    if (msg.type === 'notification' || msg.type === 'broadcast') {
                        const notif = msg.data;
                        updateNotificationBadge(1);
                        showNotificationToast(notif);
                    }
                } catch (err) {
                    console.error('Error parseando mensaje WS:', err);
                }
            };
            
            ws.onerror = function(err) {
                console.error('WebSocket error:', err);
            };
            
            ws.onclose = function() {
                if (reconnectAttempts < maxReconnectAttempts) {
                    reconnectAttempts++;
                    setTimeout(connect, 3000 * reconnectAttempts);
                }
            };
            
        } catch (err) {
            console.error('Error conectando WebSocket:', err);
        }
    }
    
    function updateNotificationBadge(count) {
        const badge = document.getElementById('notifBadge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = 'inline';
        }
    }
    
    function showNotificationToast(notif) {
        const toast = document.createElement('div');
        toast.className = 'toast-message toast-info';
        toast.innerHTML = '<i class="bi bi-bell"></i> ' + notif.mensaje;
        document.body.appendChild(toast);
        
        setTimeout(function() { toast.classList.add('show'); }, 100);
        
        setTimeout(function() {
            toast.classList.remove('show');
            setTimeout(function() { toast.remove(); }, 300);
        }, 4000);
    }
    
    window.stopWebSocketNotifications = function() {
        if (ws) {
            ws.close();
            ws = null;
        }
    };
})();