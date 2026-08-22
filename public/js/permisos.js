/**
 * Matriz de permisos por rol.
 *
 * Un solo formulario que se rellena según el rol elegido en el select. Los
 * roles con acceso total (ROL_ADMIN) se muestran con todo marcado y en solo
 * lectura: el servidor rechaza igualmente cualquier intento de guardarlos.
 */
(function () {
    'use strict';

    const form = document.getElementById('form-permisos');
    if (!form) {
        return; // No estamos en la matriz.
    }

    const selector = document.getElementById('rol-activo');
    const hiddenId = document.getElementById('rol_id');
    const aviso = document.getElementById('rol-bloqueado');
    const resumen = document.getElementById('permisos-resumen');
    const guardar = document.getElementById('permisos-guardar');
    const checks = Array.prototype.slice.call(form.querySelectorAll('.permission-check'));

    function optionSeleccionada() {
        return selector.options[selector.selectedIndex];
    }

    function concedidos(option) {
        try {
            return JSON.parse(option.dataset.granted || '[]');
        } catch (e) {
            return [];
        }
    }

    function actualizarResumen() {
        const marcados = checks.filter(function (c) { return c.checked; }).length;
        resumen.textContent = marcados + ' de ' + checks.length + ' permisos concedidos';
    }

    /** Refleja en los checkboxes el rol elegido. */
    function pintarRol() {
        const option = optionSeleccionada();
        if (!option) {
            return;
        }

        const editable = option.dataset.editable === '1';
        const granted = concedidos(option);

        hiddenId.value = option.value;

        checks.forEach(function (check) {
            check.checked = granted.indexOf(check.value) !== -1;
            check.disabled = !editable;
        });

        aviso.classList.toggle('d-none', editable);
        guardar.disabled = !editable;

        actualizarResumen();
    }

    selector.addEventListener('change', pintarRol);
    checks.forEach(function (check) {
        check.addEventListener('change', actualizarResumen);
    });

    // ------------------------------------------------------------- guardar
    form.addEventListener('submit', function (event) {
        event.preventDefault();

        const option = optionSeleccionada();
        if (!option || option.dataset.editable !== '1') {
            return;
        }

        // FormData recoge csrf_token, rol_id y todos los permisos[] marcados.
        // Si no hay ninguno marcado, el servidor recibe la lista vacía y
        // revoca todo: desmarcar es una acción, no una ausencia de datos.
        const data = new FormData(form);

        guardar.disabled = true;

        $.ajax({
            url: form.action,
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function (response) {
            if (response && response.success) {
                // Guardamos lo aplicado en el <option> para que cambiar de rol
                // y volver muestre el estado real, no el que había al cargar.
                option.dataset.granted = JSON.stringify(response.permisos || []);

                if (typeof toastr !== 'undefined') {
                    toastr.success(response.message || 'Permisos actualizados.');
                }
            } else {
                mostrarError(response);
            }
        }).fail(mostrarError).always(function () {
            guardar.disabled = false;
        });
    });

    function mostrarError(xhr) {
        const mensaje = (xhr && xhr.responseJSON && xhr.responseJSON.error)
            || (xhr && xhr.error)
            || 'No pudimos guardar los permisos.';

        if (typeof toastr !== 'undefined') {
            toastr.error(mensaje);
        } else {
            window.alert(mensaje);
        }
    }

    pintarRol();
}());
