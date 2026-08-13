/**
 * Formularios públicos: login, registro y recuperación de contraseña.
 *
 * Todos usan el mismo patrón: se envían por AJAX al endpoint que ya declara el
 * atributo action del <form>, con el token CSRF que viene en el campo oculto.
 * Nunca se guarda una contraseña en localStorage.
 */
(function () {
    'use strict';

    const BASE_URL = document.body.dataset.baseUrl || '';
    const REMEMBER_KEY = 'login_username';

    toastr.options = {
        closeButton: false,
        newestOnTop: true,
        progressBar: true,
        positionClass: 'toast-top-center',
        timeOut: 2500,
        showMethod: 'fadeIn',
        hideMethod: 'fadeOut'
    };

    /** Serializa un formulario incluyendo su campo csrf_token. */
    function payload($form, extra) {
        const data = {};
        $form.serializeArray().forEach(function (field) {
            data[field.name] = field.value;
        });
        return Object.assign(data, extra || {});
    }

    function notifyError(response) {
        const message = (response && response.responseJSON && response.responseJSON.error)
            || (response && response.error)
            || 'No pudimos procesar la solicitud.';
        toastr.error(message);
    }

    function submitJson($form, onSuccess) {
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: $form.attr('action'),
            data: payload($form)
        }).done(function (response) {
            if (response && response.success) {
                onSuccess(response);
            } else {
                notifyError(response);
            }
        }).fail(notifyError);
    }

    function goTo(url, delay) {
        window.setTimeout(function () {
            window.location.href = url;
        }, delay || 1200);
    }

    // ------------------------------------------------------------------ login
    $('#form-login').on('submit', function (event) {
        event.preventDefault();
        const $form = $(this);

        if ($('#remember-me').is(':checked')) {
            // Solo el usuario. La contraseña jamás se persiste en el navegador.
            localStorage.setItem(REMEMBER_KEY, $form.find('[name="username"]').val());
        } else {
            localStorage.removeItem(REMEMBER_KEY);
        }

        submitJson($form, function (response) {
            toastr.success('Sesión iniciada.');
            goTo(response.redirect || BASE_URL + '/dashboard');
        });
    });

    // --------------------------------------------------------------- registro
    $('#form-registro').on('submit', function (event) {
        event.preventDefault();
        const $form = $(this);

        if (($form.find('[name="password"]').val() || '').length < 8) {
            toastr.error('La contraseña debe tener al menos 8 caracteres.');
            return;
        }

        submitJson($form, function (response) {
            toastr.success(response.message || 'Cuenta creada.');
            goTo(response.redirect || BASE_URL + '/login', 1800);
        });
    });

    // ------------------------------------------------- solicitud de recuperación
    $('#form-recover').on('submit', function (event) {
        event.preventDefault();

        submitJson($(this), function (response) {
            toastr.success(response.message || 'Revisá tu correo.');
            goTo(response.redirect || BASE_URL + '/login', 2500);
        });
    });

    // --------------------------------------------- confirmación de contraseña
    $('#form-password').on('submit', function (event) {
        event.preventDefault();
        const $form = $(this);
        const password = $form.find('[name="password"]').val() || '';
        const confirm = $form.find('[name="password_confirm"]').val() || '';

        if (password.length < 8) {
            toastr.error('La contraseña debe tener al menos 8 caracteres.');
            return;
        }
        if (password !== confirm) {
            toastr.error('Las contraseñas no coinciden.');
            return;
        }

        submitJson($form, function (response) {
            toastr.success(response.message || 'Contraseña actualizada.');
            goTo(response.redirect || BASE_URL + '/login', 1800);
        });
    });

    // ------------------------------------------------- restaurar usuario guardado
    $(function () {
        const saved = localStorage.getItem(REMEMBER_KEY);
        if (saved && $('#form-login').length) {
            $('#form-login').find('[name="username"]').val(saved);
            $('#remember-me').prop('checked', true);
            $('#form-login').find('[name="password"]').trigger('focus');
        }
    });
}());
