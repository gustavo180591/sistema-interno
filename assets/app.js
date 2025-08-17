// assets/app.js

document.addEventListener('DOMContentLoaded', function() {
    // Inicialización de tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Inicialización de selects mejorados con Choices.js
    if (typeof Choices !== 'undefined') {
        // Inicializar solo los selects con data-choices="true"
        const selectElements = document.querySelectorAll('select[data-choices="true"]');
        
        selectElements.forEach(select => {
            const options = select.hasAttribute('data-options') ? 
                JSON.parse(select.getAttribute('data-options')) : 
                { searchEnabled: false, shouldSort: false };
            
            new Choices(select, {
                searchEnabled: options.searchEnabled || false,
                shouldSort: options.shouldSort || false,
                placeholder: options.placeholder || false,
                itemSelectText: '',
                classNames: {
                    containerInner: 'choices__inner',
                    input: 'choices__input',
                    list: 'choices__list',
                    item: 'choices__item',
                },
                ...options
            });
        });
    }

    // Validación de formulario personalizada y verificación de ID de ticket
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        // Validación al enviar el formulario
        form.addEventListener('submit', async function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            } else if (form.querySelector('[data-ticket-id]')) {
                // Solo validar si el formulario es válido
                event.preventDefault();
                
                const ticketIdInput = form.querySelector('[data-ticket-id]');
                const ticketId = ticketIdInput.value.trim();
                
                if (ticketId) {
                    try {
                        // Mostrar indicador de carga
                        const submitButton = form.querySelector('button[type="submit"]');
                        const originalButtonText = submitButton.innerHTML;
                        submitButton.disabled = true;
                        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verificando...';
                        
                        // Verificar si el ID del ticket ya existe
                        const response = await fetch(`/ticket/check-id?ticketId=${encodeURIComponent(ticketId)}`);
                        const data = await response.json();
                        
                        if (data.exists) {
                            // Si el ticket existe, mostrar el modal de colaboración
                            const colaborarModal = new bootstrap.Modal(document.getElementById('colaborarModal'));
                            document.getElementById('colaborarTicketId').textContent = ticketId;
                            document.getElementById('colaborarForm').action = `/ticket/${data.ticketId}/colaborar`;
                            
                            // Almacenar los datos del formulario en el modal
                            const formData = new FormData(form);
                            document.getElementById('colaborarForm').querySelector('input[name="formData"]').value = 
                                JSON.stringify(Object.fromEntries(formData));
                                
                            colaborarModal.show();
                        } else {
                            // Si no existe, enviar el formulario normalmente
                            form.submit();
                        }
                    } catch (error) {
                        console.error('Error al verificar el ID del ticket:', error);
                        // En caso de error, permitir el envío del formulario
                        form.submit();
                    } finally {
                        // Restaurar el botón
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonText;
                    }
                    return;
                }
                
                // Si llegamos aquí y el formulario es válido, enviar
                form.submit();
            }
            
            form.classList.add('was-validated');
        }, false);
        
        // Validación en tiempo real para el campo de ID del ticket
        const ticketIdInput = form.querySelector('[data-ticket-id]');
        if (ticketIdInput) {
            ticketIdInput.addEventListener('input', debounce(async function(e) {
                const ticketId = e.target.value.trim();
                const feedback = document.getElementById('ticketIdFeedback');
                const submitButton = form.querySelector('button[type="submit"]');
                
                if (ticketId.length < 3) {
                    // Validación de longitud mínima
                    feedback.textContent = 'El ID debe tener al menos 3 caracteres';
                    feedback.className = 'invalid-feedback d-block';
                    e.target.classList.add('is-invalid');
                    submitButton.disabled = true;
                    return;
                } else {
                    feedback.textContent = '';
                    feedback.className = 'd-none';
                    e.target.classList.remove('is-invalid');
                    submitButton.disabled = false;
                }
                
                // Verificar disponibilidad del ID
                try {
                    const response = await fetch(`/ticket/check-id?ticketId=${encodeURIComponent(ticketId)}`);
                    const data = await response.json();
                    
                    if (data.exists) {
                        feedback.textContent = 'Este ID ya está en uso. Puedes unirte al ticket existente.';
                        feedback.className = 'text-warning d-block';
                        e.target.classList.add('is-valid');
                    } else {
                        feedback.textContent = 'ID disponible';
                        feedback.className = 'text-success d-block';
                        e.target.classList.add('is-valid');
                    }
                } catch (error) {
                    console.error('Error al verificar el ID:', error);
                }
            }, 500));
        }
    });
    
    // Función para debounce
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Manejar el envío del formulario de colaboración
    const colaborarForm = document.getElementById('colaborarForm');
    if (colaborarForm) {
        colaborarForm.addEventListener('submit', function(e) {
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uniéndose...';
        });
    }
});
