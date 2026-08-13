/**
 * Panel: gestor de usuarios y subida de avatar.
 *
 * El token CSRF se lee del <meta name="csrf-token"> del layout y se adjunta a
 * toda petición POST, incluida la subida de archivos.
 */
(function () {
    'use strict';

    const BASE_URL = document.body.dataset.baseUrl || '';
    const CSRF = $('meta[name="csrf-token"]').attr('content') || '';

    let table = null;

    function withCsrf(data) {
        return Object.assign({ csrf_token: CSRF }, data || {});
    }

    function errorOf(response) {
        return (response && response.responseJSON && response.responseJSON.error)
            || (response && response.error)
            || 'No pudimos procesar la solicitud.';
    }

    function alertError(response) {
        $.alert({ title: 'Error', content: errorOf(response), type: 'red' });
    }

    // ------------------------------------------------------- avatar de perfil
    $('#form-avatar').on('submit', function (event) {
        event.preventDefault();

        const file = $('#avatar-file').get(0).files[0];
        if (!file) {
            $.alert({ title: 'Imagen', content: 'Elegí una imagen primero.' });
            return;
        }

        const data = new FormData(this);
        data.append('csrf_token', CSRF);

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            cache: false,
            dataType: 'json'
        }).done(function (response) {
            if (response && response.success) {
                $.alert({
                    title: 'Perfil',
                    content: response.message || 'Imagen actualizada.',
                    type: 'green',
                    buttons: { ok: function () { window.location.reload(); } }
                });
            } else {
                alertError(response);
            }
        }).fail(alertError);
    });

    // --------------------------------------------------- selects del gestor
    function loadUserOptions() {
        $.getJSON(BASE_URL + '/api/users/names', function (rows) {
            const $select = $('#filtro-usuario');
            rows.forEach(function (row) {
                $select.append($('<option>', { value: row.id_usuario, text: row.username }));
            });
        });
    }

    /** Llena todos los <select> de rol que existan en la página. */
    function loadRoleOptions() {
        const $targets = $('#edit-rol, #create-rol');
        if (!$targets.length) {
            return;
        }

        $.getJSON(BASE_URL + '/api/roles', function (rows) {
            rows.forEach(function (row) {
                $targets.append($('<option>', { value: row.id_rol, text: row.rol_usuario }));
            });
        });
    }

    /** Repone el <option> de un usuario recién creado en el filtro. */
    function addUserOption(user) {
        const $select = $('#filtro-usuario');
        if ($select.length) {
            $select.append($('<option>', { value: user.id_usuario, text: user.username }));
        }
    }

    // ------------------------------------------------------------- DataTable
    function renderTable(userId, status) {
        if (table !== null) {
            table.destroy();
            $('#ud_user tbody').empty();
            table = null;
        }

        table = $('#ud_user').DataTable({
            responsive: true,
            ajax: {
                url: BASE_URL + '/api/users/list',
                type: 'POST',
                data: withCsrf({ id: userId || '', estado: status || '' }),
                dataSrc: ''
            },
            language: {
                sProcessing: 'Procesando...',
                sLengthMenu: 'Mostrar _MENU_ registros',
                sZeroRecords: 'No se encontraron resultados.',
                sEmptyTable: 'No hay usuarios para mostrar.',
                sInfo: 'Mostrando del _START_ al _END_ de _TOTAL_',
                sInfoEmpty: 'Sin registros',
                sInfoFiltered: '(filtrado de _MAX_ en total)',
                sSearch: 'Buscar:',
                sLoadingRecords: 'Cargando...',
                oPaginate: {
                    sFirst: 'Primero',
                    sLast: 'Último',
                    sNext: 'Siguiente',
                    sPrevious: 'Anterior'
                }
            },
            columns: [
                { data: 'id_usuario' },
                { data: 'username' },
                { data: 'email' },
                { data: 'rol_usuario' },
                {
                    data: 'estado',
                    render: function (data, type, row) {
                        const active = Number(row.estado) === 1;
                        return '<span class="' + (active ? 'usuario-ev' : 'usuario-er') + '"'
                            + ' role="button" data-action="toggle" data-id="' + row.id_usuario + '"'
                            + ' title="Cambiar estado">'
                            + '<i class="fas ' + (active ? 'fa-check-circle' : 'fa-times-circle') + '"></i>'
                            + '</span>';
                    }
                },
                {
                    data: 'id_usuario',
                    orderable: false,
                    render: function (data) {
                        return '<button type="button" class="btn btn-info btn-sm"'
                            + ' data-action="edit" data-id="' + data + '">'
                            + '<i class="fas fa-edit"></i></button>';
                    }
                }
            ]
        });
    }

    // Delegación de eventos: los botones se crean después, así que se escucha
    // en la tabla en vez de usar onclick inline.
    $('#ud_user').on('click', '[data-action="edit"]', function () {
        openEditModal($(this).data('id'));
    });

    $('#ud_user').on('click', '[data-action="toggle"]', function () {
        const id = $(this).data('id');

        $.confirm({
            title: 'Estado',
            content: '¿Querés cambiar el estado de este usuario?',
            type: 'dark',
            buttons: {
                confirmar: function () {
                    $.ajax({
                        type: 'POST',
                        dataType: 'json',
                        url: BASE_URL + '/api/users/toggle',
                        data: withCsrf({ id: id })
                    }).done(function (response) {
                        if (response && response.success) {
                            toastrOrAlert('Estado actualizado.');
                            renderTable($('#filtro-usuario').val(), $('#filtro-estado').val());
                        } else {
                            alertError(response);
                        }
                    }).fail(alertError);
                },
                cancelar: function () {}
            }
        });
    });

    function toastrOrAlert(message) {
        if (typeof toastr !== 'undefined') {
            toastr.success(message);
        } else {
            $.alert({ title: 'Listo', content: message });
        }
    }

    // ----------------------------------------------------------- crear usuario
    $('[data-action="nuevo-usuario"]').on('click', function () {
        const form = $('#form-crear-usuario').get(0);
        if (form) {
            // Limpiamos el formulario en cada apertura para no arrastrar datos
            // (ni contraseñas) del intento anterior. reset() no toca el campo
            // oculto de CSRF, que es lo que queremos.
            form.reset();
            $('#create-avatar').next('.custom-file-label').text('Foto de perfil (opcional)');
        }
        $('#modal-crear').modal('show');
    });

    // Mostrar el nombre del archivo elegido en el label de Bootstrap.
    $('#create-avatar').on('change', function () {
        const name = this.files && this.files[0]
            ? this.files[0].name
            : 'Foto de perfil (opcional)';
        $(this).next('.custom-file-label').text(name);
    });

    $('#form-crear-usuario').on('submit', function (event) {
        event.preventDefault();

        const $form = $(this);
        const $submit = $form.find('button[type="submit"]');
        const password = $('#create-password').val() || '';
        const confirm = $('#create-password-confirm').val() || '';

        // Validación de cortesía: el servidor la repite igual, porque esto se
        // puede saltear desde la consola del navegador.
        if (!$('#create-username').val().trim() || !$('#create-email').val().trim()) {
            $.alert({ title: 'Campos', content: 'Usuario y correo son obligatorios.' });
            return;
        }
        if (password.length < 8) {
            $.alert({ title: 'Contraseña', content: 'Debe tener al menos 8 caracteres.' });
            return;
        }
        if (password !== confirm) {
            $.alert({ title: 'Contraseña', content: 'Las contraseñas no coinciden.' });
            return;
        }
        if (!$('#create-rol').val()) {
            $.alert({ title: 'Rol', content: 'Elegí un rol para el usuario.' });
            return;
        }

        // FormData porque el formulario incluye el avatar. Ya arrastra el
        // csrf_token del campo oculto.
        const data = new FormData(this);

        $submit.prop('disabled', true);

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            cache: false,
            dataType: 'json'
        }).done(function (response) {
            if (response && response.success) {
                $('#modal-crear').modal('hide');
                addUserOption(response.user);
                renderTable($('#filtro-usuario').val(), $('#filtro-estado').val());
                toastrOrAlert(response.message || 'Usuario creado.');
            } else {
                alertError(response);
            }
        }).fail(alertError).always(function () {
            $submit.prop('disabled', false);
        });
    });

    // ---------------------------------------------------------- modal editar
    function openEditModal(id) {
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: BASE_URL + '/api/users/find',
            data: withCsrf({ id: id })
        }).done(function (user) {
            if (!user || user.success === false) {
                alertError(user);
                return;
            }
            $('#edit-id').val(user.id_usuario);
            $('#edit-username').val(user.username);
            $('#edit-email').val(user.email);
            $('#edit-rol-actual').val(user.rol_usuario);
            $('#edit-rol').val(user.rol_id);
            $('#edit-estado').val(user.estado_txt);
            $('#edit-registro').val(user.registro);
            $('#modal-editar').modal('show');
        }).fail(alertError);
    }

    $('#form-editar-usuario').on('submit', function (event) {
        event.preventDefault();
        const $form = $(this);

        const data = {};
        $form.serializeArray().forEach(function (field) {
            data[field.name] = field.value;
        });

        if (!data.username || !data.email || !data.rol) {
            $.alert({ title: 'Campos', content: 'Completá usuario, correo y rol.' });
            return;
        }

        $.confirm({
            title: 'Actualizar',
            content: '¿Confirmás los cambios?',
            type: 'dark',
            buttons: {
                confirmar: function () {
                    $.ajax({
                        type: 'POST',
                        dataType: 'json',
                        url: $form.attr('action'),
                        data: data
                    }).done(function (response) {
                        if (response && response.success) {
                            $('#modal-editar').modal('hide');
                            renderTable($('#filtro-usuario').val(), $('#filtro-estado').val());
                            toastrOrAlert(response.message || 'Usuario actualizado.');
                        } else {
                            alertError(response);
                        }
                    }).fail(alertError);
                },
                cancelar: function () {}
            }
        });
    });

    // ------------------------------------------------------------- filtros
    $('#form-filtros').on('submit', function (event) {
        event.preventDefault();
        $('.usuario').show();
        renderTable($('#filtro-usuario').val(), $('#filtro-estado').val());
    });

    // ------------------------------------------------------------- arranque
    $(function () {
        if ($('#filtro-usuario').length) {
            loadUserOptions();
        }
        loadRoleOptions();
        if ($('#ud_user').length) {
            renderTable();
        }
    });
}());
