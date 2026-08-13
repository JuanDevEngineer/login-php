/**
 * Gestor de roles.
 *
 * Se carga en todo el panel, así que todo arranca solo si la tabla de roles
 * existe en la página actual.
 */
(function () {
    'use strict';

    if (!document.getElementById('tabla-roles')) {
        return;
    }

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

    function notify(message) {
        if (typeof toastr !== 'undefined') {
            toastr.success(message);
        } else {
            $.alert({ title: 'Listo', content: message });
        }
    }

    // ------------------------------------------------------------- DataTable
    function renderTable() {
        if (table !== null) {
            table.destroy();
            $('#tabla-roles tbody').empty();
            table = null;
        }

        table = $('#tabla-roles').DataTable({
            responsive: true,
            ajax: {
                url: BASE_URL + '/api/roles/list',
                type: 'GET',
                dataSrc: ''
            },
            language: {
                sProcessing: 'Procesando...',
                sLengthMenu: 'Mostrar _MENU_ registros',
                sZeroRecords: 'No se encontraron resultados.',
                sEmptyTable: 'No hay roles para mostrar.',
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
                { data: 'id_rol' },
                {
                    data: 'rol_usuario',
                    render: function (data) {
                        return '<code>' + $('<div>').text(data).html() + '</code>';
                    }
                },
                {
                    data: 'usuarios',
                    render: function (data) {
                        const cls = data > 0 ? 'badge-info' : 'badge-secondary';
                        return '<span class="badge ' + cls + '">' + Number(data) + '</span>';
                    }
                },
                {
                    data: 'es_sistema',
                    render: function (data) {
                        return data
                            ? '<span class="badge badge-warning"><i class="fas fa-lock mr-1"></i>Sistema</span>'
                            : '<span class="badge badge-light">Personalizado</span>';
                    }
                },
                {
                    data: 'id_rol',
                    orderable: false,
                    render: function (data, type, row) {
                        // Los roles de sistema no se editan ni se borran. El
                        // servidor lo rechaza igual; esto es solo para que la
                        // interfaz no ofrezca algo que va a fallar.
                        const editar = row.editable
                            ? '<button type="button" class="btn btn-info btn-sm mr-1"'
                              + ' data-action="editar-rol" data-id="' + data + '"'
                              + ' data-nombre="' + $('<div>').text(row.rol_usuario).html() + '"'
                              + ' title="Editar"><i class="fas fa-edit"></i></button>'
                            : '<button type="button" class="btn btn-info btn-sm mr-1" disabled'
                              + ' title="Rol del sistema"><i class="fas fa-edit"></i></button>';

                        let borrar;
                        if (row.es_sistema) {
                            borrar = '<button type="button" class="btn btn-danger btn-sm" disabled'
                                   + ' title="Rol del sistema"><i class="fas fa-trash"></i></button>';
                        } else if (row.usuarios > 0) {
                            borrar = '<button type="button" class="btn btn-danger btn-sm" disabled'
                                   + ' title="Tiene ' + row.usuarios + ' usuario(s) asignado(s)">'
                                   + '<i class="fas fa-trash"></i></button>';
                        } else {
                            borrar = '<button type="button" class="btn btn-danger btn-sm"'
                                   + ' data-action="borrar-rol" data-id="' + data + '"'
                                   + ' data-nombre="' + $('<div>').text(row.rol_usuario).html() + '"'
                                   + ' title="Eliminar"><i class="fas fa-trash"></i></button>';
                        }

                        return editar + borrar;
                    }
                }
            ]
        });
    }

    // ---------------------------------------------------------------- crear
    $('[data-action="nuevo-rol"]').on('click', function () {
        const form = $('#form-crear-rol').get(0);
        if (form) {
            form.reset();
        }
        $('#modal-crear-rol').modal('show');
    });

    $('#form-crear-rol').on('submit', function (event) {
        event.preventDefault();

        const $form = $(this);
        const $submit = $form.find('button[type="submit"]');

        if (!$('#create-rol-nombre').val().trim()) {
            $.alert({ title: 'Nombre', content: 'Escribí un nombre para el rol.' });
            return;
        }

        $submit.prop('disabled', true);

        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: $form.attr('action'),
            data: withCsrf({ nombre: $('#create-rol-nombre').val() })
        }).done(function (response) {
            if (response && response.success) {
                $('#modal-crear-rol').modal('hide');
                renderTable();
                notify(response.message || 'Rol creado.');
            } else {
                alertError(response);
            }
        }).fail(alertError).always(function () {
            $submit.prop('disabled', false);
        });
    });

    // --------------------------------------------------------------- editar
    $('#tabla-roles').on('click', '[data-action="editar-rol"]', function () {
        $('#edit-rol-id').val($(this).data('id'));
        $('#edit-rol-nombre').val($(this).data('nombre'));
        $('#modal-editar-rol').modal('show');
    });

    $('#form-editar-rol').on('submit', function (event) {
        event.preventDefault();

        const $form = $(this);
        const $submit = $form.find('button[type="submit"]');

        if (!$('#edit-rol-nombre').val().trim()) {
            $.alert({ title: 'Nombre', content: 'El nombre no puede quedar vacío.' });
            return;
        }

        $submit.prop('disabled', true);

        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: $form.attr('action'),
            data: withCsrf({
                id: $('#edit-rol-id').val(),
                nombre: $('#edit-rol-nombre').val()
            })
        }).done(function (response) {
            if (response && response.success) {
                $('#modal-editar-rol').modal('hide');
                renderTable();
                notify(response.message || 'Rol actualizado.');
            } else {
                alertError(response);
            }
        }).fail(alertError).always(function () {
            $submit.prop('disabled', false);
        });
    });

    // -------------------------------------------------------------- eliminar
    $('#tabla-roles').on('click', '[data-action="borrar-rol"]', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');

        $.confirm({
            title: 'Eliminar rol',
            content: '¿Seguro que querés eliminar <strong>' + $('<div>').text(nombre).html()
                     + '</strong>? Esta acción no se puede deshacer.',
            type: 'red',
            buttons: {
                eliminar: {
                    btnClass: 'btn-red',
                    action: function () {
                        $.ajax({
                            type: 'POST',
                            dataType: 'json',
                            url: BASE_URL + '/api/roles/delete',
                            data: withCsrf({ id: id })
                        }).done(function (response) {
                            if (response && response.success) {
                                renderTable();
                                notify(response.message || 'Rol eliminado.');
                            } else {
                                alertError(response);
                            }
                        }).fail(alertError);
                    }
                },
                cancelar: function () {}
            }
        });
    });

    // ------------------------------------------------------------- arranque
    $(renderTable);
}());
