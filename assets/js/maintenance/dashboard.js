document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize charts if they exist
    const equipmentStatusCtx = document.getElementById('equipmentStatusChart');
    const maintenanceTrendsCtx = document.getElementById('maintenanceTrendsChart');
    
    // Equipment Status Doughnut Chart
    if (equipmentStatusCtx) {
        new Chart(equipmentStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Operativo', 'En Mantenimiento', 'Fuera de Servicio'],
                datasets: [{
                    data: [
                        equipmentStatusCtx.dataset.operational || 0,
                        equipmentStatusCtx.dataset.maintenance || 0,
                        equipmentStatusCtx.dataset.outOfService || 0
                    ],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.9)',
                        'rgba(255, 193, 7, 0.9)',
                        'rgba(220, 53, 69, 0.9)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });
    }

    // Maintenance Trends Line Chart
    if (maintenanceTrendsCtx) {
        new Chart(maintenanceTrendsCtx, {
            type: 'line',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                datasets: [
                    {
                        label: 'Preventivo',
                        data: [12, 19, 15, 17, 14, 13, 16, 18, 15, 17, 20, 22],
                        borderColor: 'rgba(40, 167, 69, 1)',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Correctivo',
                        data: [8, 5, 7, 6, 4, 5, 3, 2, 4, 3, 2, 1],
                        borderColor: 'rgba(220, 53, 69, 1)',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }

    // Add smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId !== '#') {
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 20,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });

    // Add hover effect for cards with hover-shadow class
    document.querySelectorAll('.hover-shadow').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.classList.add('shadow-sm');
        });
        card.addEventListener('mouseleave', function() {
            this.classList.remove('shadow-sm');
        });
    });

    // Initialize date range picker if it exists
    const dateRangePicker = document.querySelector('.date-range-picker');
    if (dateRangePicker) {
        // Initialize your date range picker here
        // Example: $(dateRangePicker).daterangepicker({...});
    }
});
