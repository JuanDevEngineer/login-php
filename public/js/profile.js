/**
 * Perfil — cambio de foto sin recargar la página.
 *
 * Flujo: elegir (clic o arrastrar) → vista previa → confirmar → subir con
 * barra de progreso → reemplazar la imagen en el sitio y en la barra lateral.
 *
 * El problema de la versión anterior era que se apoyaba en el `custom-file`
 * de Bootstrap, cuyo label solo se actualiza si está cargado el plugin
 * bs-custom-file-input. Sin él parecía que elegir un archivo no hacía nada.
 * Acá el input va oculto y toda la interfaz la manejamos nosotros.
 */
(function () {
    'use strict';

    const wrap = document.getElementById('avatar-dropzone');
    if (!wrap) {
        return; // No estamos en la página de perfil.
    }

    const BASE_URL = document.body.dataset.baseUrl || '';
    const MAX_BYTES = parseInt(wrap.dataset.maxBytes, 10) || 2097152;
    const USER_ID = wrap.dataset.userId;

    const ACCEPTED = ['image/jpeg', 'image/pjpeg', 'image/png', 'image/gif', 'image/webp'];

    const form = document.getElementById('form-avatar');
    const fileInput = document.getElementById('avatar-file');
    const currentImg = wrap.querySelector('.avatar-img:not(.avatar-preview)');
    const previewImg = document.getElementById('avatar-preview');
    const overlay = document.getElementById('avatar-progress-overlay');
    const progressText = document.getElementById('avatar-progress-text');
    const confirmBox = document.getElementById('avatar-confirm');
    const filenameEl = document.getElementById('avatar-filename');
    const hintEl = document.getElementById('avatar-hint');
    const actionsBox = document.getElementById('avatar-actions');

    let objectUrl = null;

    // ------------------------------------------------------------- utilidades

    function humanSize(bytes) {
        return bytes >= 1048576
            ? (bytes / 1048576).toFixed(1) + ' MB'
            : Math.round(bytes / 1024) + ' KB';
    }

    function notifyOk(message) {
        if (typeof toastr !== 'undefined') {
            toastr.success(message);
        }
    }

    function notifyError(message) {
        if (typeof toastr !== 'undefined') {
            toastr.error(message);
        } else if (typeof $ !== 'undefined' && $.alert) {
            $.alert({ title: 'Error', content: message, type: 'red' });
        } else {
            window.alert(message);
        }
    }

    function errorFrom(xhr) {
        if (xhr && xhr.responseJSON && xhr.responseJSON.error) {
            return xhr.responseJSON.error;
        }
        if (xhr && xhr.status === 413) {
            return 'El archivo es demasiado grande para el servidor.';
        }
        return 'No pudimos subir la imagen. Probá de nuevo.';
    }

    function releasePreview() {
        if (objectUrl !== null) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
    }

    function resetSelection() {
        releasePreview();
        fileInput.value = '';
        previewImg.classList.add('d-none');
        previewImg.removeAttribute('src');
        confirmBox.classList.add('d-none');
        hintEl.classList.remove('d-none');
        filenameEl.textContent = '';
    }

    function showProgress(percent) {
        overlay.classList.add('is-visible');
        progressText.textContent = percent + '%';
    }

    function hideProgress() {
        overlay.classList.remove('is-visible');
        progressText.textContent = '0%';
    }

    // --------------------------------------------------------- validar y previsualizar

    function acceptFile(file) {
        if (!file) {
            return;
        }

        // Validación en el navegador: es cortesía, no seguridad. El servidor
        // vuelve a comprobar tipo y tamaño con finfo, porque esto se saltea
        // desde la consola en dos líneas.
        if (ACCEPTED.indexOf(file.type) === -1) {
            notifyError('Ese archivo no es una imagen admitida. Usá JPG, PNG, GIF o WebP.');
            return;
        }
        if (file.size > MAX_BYTES) {
            notifyError('La imagen pesa ' + humanSize(file.size)
                + ' y el máximo es ' + humanSize(MAX_BYTES) + '.');
            return;
        }

        releasePreview();
        objectUrl = URL.createObjectURL(file);

        previewImg.src = objectUrl;
        previewImg.classList.remove('d-none');

        filenameEl.textContent = file.name + ' · ' + humanSize(file.size);
        confirmBox.classList.remove('d-none');
        hintEl.classList.add('d-none');
    }

    /** Mete el archivo arrastrado dentro del <input file> para poder enviarlo. */
    function assignToInput(file) {
        if (typeof DataTransfer === 'undefined') {
            return false;
        }
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
        return true;
    }

    // -------------------------------------------------------------- elegir archivo

    document.getElementById('avatar-pick').addEventListener('click', function () {
        fileInput.click();
    });

    // También se puede hacer clic en la foto.
    wrap.querySelector('.avatar-frame').addEventListener('click', function () {
        fileInput.click();
    });

    fileInput.addEventListener('change', function () {
        acceptFile(this.files[0]);
    });

    // --------------------------------------------------------- arrastrar y soltar

    ['dragenter', 'dragover'].forEach(function (type) {
        wrap.addEventListener(type, function (event) {
            event.preventDefault();
            event.stopPropagation();
            wrap.classList.add('is-dragging');
        });
    });

    ['dragleave', 'drop'].forEach(function (type) {
        wrap.addEventListener(type, function (event) {
            event.preventDefault();
            event.stopPropagation();
            wrap.classList.remove('is-dragging');
        });
    });

    wrap.addEventListener('drop', function (event) {
        const file = event.dataTransfer && event.dataTransfer.files[0];
        if (!file) {
            return;
        }
        if (!assignToInput(file)) {
            notifyError('Tu navegador no admite arrastrar archivos. Usá el botón de la cámara.');
            return;
        }
        acceptFile(file);
    });

    // El navegador abre el archivo si se suelta fuera de la zona: lo evitamos.
    ['dragover', 'drop'].forEach(function (type) {
        document.addEventListener(type, function (event) {
            if (!wrap.contains(event.target)) {
                event.preventDefault();
            }
        });
    });

    // --------------------------------------------------------------------- subir

    document.getElementById('avatar-save').addEventListener('click', function () {
        if (!fileInput.files || !fileInput.files[0]) {
            notifyError('Elegí una imagen primero.');
            return;
        }

        // FormData(form) ya arrastra csrf_token y el id del campo oculto.
        const data = new FormData(form);

        $.ajax({
            url: form.action,
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            cache: false,
            dataType: 'json',
            xhr: function () {
                const xhr = $.ajaxSettings.xhr();
                if (xhr.upload) {
                    xhr.upload.addEventListener('progress', function (event) {
                        if (event.lengthComputable) {
                            showProgress(Math.round((event.loaded / event.total) * 100));
                        }
                    });
                }
                return xhr;
            },
            beforeSend: function () {
                showProgress(0);
            }
        }).done(function (response) {
            if (response && response.success) {
                applyNewAvatar(response.avatarUrl, true);
                notifyOk(response.message || 'Foto actualizada.');
            } else {
                notifyError((response && response.error) || 'No se pudo actualizar la foto.');
            }
        }).fail(function (xhr) {
            notifyError(errorFrom(xhr));
        }).always(function () {
            hideProgress();
        });
    });

    document.getElementById('avatar-cancel').addEventListener('click', resetSelection);

    // -------------------------------------------------------------------- quitar

    document.getElementById('avatar-remove').addEventListener('click', function () {
        const doRemove = function () {
            $.ajax({
                url: BASE_URL + '/api/profile/image/delete',
                type: 'POST',
                dataType: 'json',
                data: {
                    id: USER_ID,
                    csrf_token: $(form).find('input[name="csrf_token"]').val()
                },
                beforeSend: function () {
                    showProgress(0);
                }
            }).done(function (response) {
                if (response && response.success) {
                    applyNewAvatar(response.avatarUrl, false);
                    notifyOk(response.message || 'Foto eliminada.');
                } else {
                    notifyError((response && response.error) || 'No se pudo quitar la foto.');
                }
            }).fail(function (xhr) {
                notifyError(errorFrom(xhr));
            }).always(hideProgress);
        };

        if (typeof $ !== 'undefined' && $.confirm) {
            $.confirm({
                title: 'Quitar foto',
                content: '¿Querés volver al avatar por defecto?',
                type: 'red',
                buttons: {
                    quitar: { btnClass: 'btn-red', action: doRemove },
                    cancelar: function () {}
                }
            });
        } else if (window.confirm('¿Quitar la foto de perfil?')) {
            doRemove();
        }
    });

    // ------------------------------------------------- reflejar el cambio en la UI

    function applyNewAvatar(url, hasPhoto) {
        // Sin recargar: cambiamos la foto acá y en la barra lateral.
        currentImg.src = url;

        const sidebarImg = document.querySelector('.user-panel .image img');
        if (sidebarImg) {
            sidebarImg.src = url;
        }

        actionsBox.classList.toggle('d-none', !hasPhoto);
        resetSelection();
    }

    // Liberamos el object URL al salir, para no dejar memoria retenida.
    window.addEventListener('pagehide', releasePreview);
}());
