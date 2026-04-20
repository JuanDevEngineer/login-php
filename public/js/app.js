// URL base: se lee del atributo data-base-url del <body> (lo emite header.php)
const URL = document.body.dataset.baseUrl || "";
const guardarDatos = $("#datos");

// Helper: obtener el token CSRF del form
function csrf(form) {
    return $(form).find('input[name="csrf_token"]').val() || "";
}

$(document).ready(function () {
    ObtenerDatosLocalStorage();
});

toastr.options = {
    "closeButton": false,
    "debug": false,
    "newestOnTop": true,
    "progressBar": true,
    "positionClass": "toast-top-center",
    "preventDuplicates": false,
    "onclick": null,
    "showDuration": "300",
    "hideDuration": "1000",
    "timeOut": "2000",
    "extendedTimeOut": "1000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut"
};

// login
$('#form-login').submit(function (event) {
    event.preventDefault();
    const name = $('#name').val();
    const pass = $('#pass').val();

    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: `${URL}/App/signIn`,
        data: {
            name,
            pass,
            csrf_token: csrf(this),
        },
        success: (response) => {
            if (response.status) {
                toastr.success('Usuario Logueado Correctamente!');
                setTimeout(() => {
                    $(location).attr('href', `${URL}/Admin/inicio`);
                }, 1500);
            } else if (response.errorinputs) {
                toastr.warning(response.errorinputs);
            } else {
                toastr.error(response.error || 'Error');
            }
        }
    });
});

// registrar
$('#form-registro').submit(function (event) {
    event.preventDefault();

    const name = $('#name').val();
    const correo = $('#correo').val();
    const pass = $('#pass').val();

    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: `${URL}/App/signUp`,
        data: {
            name: name,
            correo: correo,
            password: pass,
            csrf_token: csrf(this),
        },
        success: (response) => {
            if (response.error) {
                toastr.error(response.error);
            } else {
                toastr.success(response.success);
                setTimeout(() => {
                    $(location).attr('href', `${URL}/App/acceso`);
                }, 2000);
            }
        }
    });
});

// envio del recover passwords
$('#form-recover').submit(function (event) {
    event.preventDefault();

    const email = $('#email_recover').val();

    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: `${URL}/App/enviarCambioPassword`,
        data: {
            email: email,
            csrf_token: csrf(this),
        },
        success: (response) => {
            toastr.success(response.message || 'Enviado');
            setTimeout(() => {
                $(location).attr('href', `${URL}/App/acceso`);
            }, 2000);
        }
    });
});

// cambio de contraseña
$('#form-password').submit(function (event) {
    event.preventDefault();
    const id_user = $('#id_user').val();
    const pass = $('#pass_recover').val();
    const pass_confirm = $('#pass_recover_confirm').val();

    if (pass.length < 8) {
        toastr.error('La contraseña debe tener al menos 8 caracteres.');
        return;
    }
    if (pass !== pass_confirm) {
        toastr.error('Las contraseñas no coinciden.');
        return;
    }

    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: `${URL}/App/UpdatePass`,
        data: {
            id_user: id_user,
            pass: pass,
            csrf_token: csrf(this),
        },
        success: (response) => {
            if (response.status) {
                toastr.success('Contraseña cambiada correctamente!');
                setTimeout(() => {
                    $(location).attr('href', `${URL}/App/acceso`);
                }, 2000);
            } else {
                toastr.error(response.response || 'Error');
            }
        }
    });
});

// "Recordarme" sólo guarda el username (NUNCA la contraseña)
$('#datos').change(function () {
    recordarDatosLogin();
});

function recordarDatosLogin() {
    if (guardarDatos.is(':checked')) {
        localStorage.setItem("login_user", $("#name").val());
        toastr.success("Usuario recordado");
    } else {
        localStorage.removeItem("login_user");
    }
}

function ObtenerDatosLocalStorage() {
    const saved = localStorage.getItem("login_user");
    if (saved) {
        $("#name").val(saved);
        $("#datos").prop("checked", true);
        $("#pass").focus();
    }
}
