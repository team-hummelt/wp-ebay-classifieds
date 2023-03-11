document.addEventListener("DOMContentLoaded", function () {
    (function ($) {
        'use strict';

        let colImportOverview = $('#colImportOverview');
        let colImportSettings = $('#colImportSettings');
        let colImportHandle = $('#colImportHandle');
        let bodyWait = $('.body-wait-loading');

        function wp_ebay_importer_fetch(data, is_formular = true, callback) {
            let formData = new FormData();
            if (is_formular) {
                let input = new FormData(data);
                for (let [name, value] of input) {
                    formData.append(name, value);
                }
            } else {
                for (let [name, value] of Object.entries(data)) {
                    formData.append(name, value);
                }
            }
            formData.append('_ajax_nonce', wei_ajax_obj.nonce);
            formData.append('action', 'EbayImporter');

            fetch(wei_ajax_obj.ajax_url, {
                method: 'POST',
                body: formData
            }).then((response) => response.json())
                .then((result) => {
                    if (typeof callback === 'function') {
                        document.addEventListener("load", callback(result));
                    }
                })
                .catch((error) => {
                    console.error('Error:', error);
                });
        }

        if (colImportOverview.length !== 0) {
            let formData = {
                'method': 'get_import_overview_template'
            }
            set_body_wait_spinner('#colImportOverview')
            wp_ebay_importer_fetch(formData, false, wec_action_callback)
        }

        $(document).on('submit', '.wec-admin-form', function (event) {
            let button = event.originalEvent.submitter;
            if (button.hasAttribute('data-wait')) {
                swal_timer();
            }
            if (button.hasAttribute('data-osm')) {
                $('#osmData').html('').removeClass('bg-white')
                $('.btn-copy').attr('data-id', '').addClass('disabled');
            }
            let formData = $(this).closest("form").get(0);
            wp_ebay_importer_fetch(formData, true, wec_submit_form_callback)
            event.preventDefault();
        });
        $(document).on('click', '.btn-reset-osm', function () {
            $('.wec-admin-form').trigger('reset');
            $('#osmData').html('').removeClass('bg-white');
            $('.btn-copy').attr('data-id', '').addClass('disabled');
        });


        function wec_submit_form_callback(data) {
            Swal.close();
            let osmWrapper = $('#osmData');
            switch (data.type) {
                case 'import_db_handle':
                    if (data.status) {
                        let formData = {
                            'method': 'get_import_overview_template',
                            'target': '#colImportOverview'
                        }
                        set_body_wait_spinner('#colImportOverview')
                        wp_ebay_importer_fetch(formData, false, wec_action_callback)
                    }
                    break;
                case'search_osm_map':
                    if (data.status) {
                        osmWrapper.html(data.osm).addClass('bg-white')
                        $('.btn-copy').attr('data-id', data.osm).removeClass('disabled')
                        return false;
                    } else {
                        osmWrapper.removeClass('bg-white')
                    }
                    break;
            }
            swal_alert_response(data)
        }

        $(document).on('click', '.btn-toggle', function () {
            $('.btn-toggle').prop('disabled', false);
            $(this).prop('disabled', true);
        });

        $(document).on('click', '.btn-copy', function () {
            let secretId = $(this).attr('data-id');
            if (!secretId) {
                return false;
            }
            let el = document.createElement('textarea');
            el.value = secretId;

            el.setAttribute('readonly', '');
            el.style = {position: 'absolute', left: '-100vw'};
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
            let info = $('#info');
            info.animate({opacity: '1'}, "3000");
            info.animate({opacity: '0'}, "3000");
        });

        $(document).on('click', '.wec-action', function () {
            let type = $(this).attr('data-type');
            let target;
            let toggle;
            let id;
            let formData;
            let handle;
            let swal;

            $(this).attr('data-target') ? target = $(this).attr('data-target') : target = '';
            $(this).attr('data-toggle') ? toggle = $(this).attr('data-toggle') : toggle = '';
            $(this).attr('data-handle') ? handle = $(this).attr('data-handle') : handle = '';
            $(this).attr('data-id') ? id = $(this).attr('data-id') : id = '';
            switch (type) {
                case 'ebay_import_template_handle':
                    colImportOverview.html('');
                    set_body_wait_spinner('#colImportOverview')
                    formData = {
                        'method': type,
                        'id': id,
                        'handle': handle
                    }
                    break;
                case 'get_import_overview_template':
                    colImportSettings.html('');
                    colImportHandle.html('')
                    set_body_wait_spinner('#colImportHandle')
                    set_body_wait_spinner('#colImportSettings')
                    formData = {
                        'method': type,
                        'target': target
                    }
                    break;
                case 'delete_import':
                    formData = {
                        'method': type,
                        'id': id,
                        'handle': handle
                    }
                    swal = {
                        title: 'Import wirklich löschen?',
                        body: 'Alle Posts und Anhänge werden unwiderruflich gelöscht.<br>Das Löschen kann nicht rückgängig gemacht werden.',
                        btn: 'Import löschen'
                    }
                    swal_fire_app_delete(formData, swal);
                    return false;
                case'get_import_settings_template':
                    formData = {
                        'method': type,
                        'target': target
                    }
                    colImportHandle.html('')
                    colImportOverview.html('')
                    set_body_wait_spinner('#colImportHandle')
                    set_body_wait_spinner('#colImportOverview')
                    break;
                case 'load_default_expression':
                    formData = {
                        'method': type,
                    }
                    swal = {
                        title: 'Einstellungen wirklich laden?',
                        body: 'Das Andern der Einstellungen kann nicht rückgängig gemacht werden.',
                        btn: 'Einstellungen laden'
                    }
                    swal_fire_app_delete(formData, swal);
                    return false;
                case 'now_synchronize':
                    formData = {
                        'method': type,
                        'id': id,
                        'system': $(this).attr('data-system')
                    }
                    if($(this).attr('data-system')){
                        Swal.fire({
                            customClass: {
                                popup: 'swal-info-container'
                            },
                            html: `<span class="swal-delete-body">Die eBay Importe werden im Hintergrund Synchronisiert.</span>`,
                            title: 'Importe Synchronisieren',
                            showClass: {
                                popup: 'animate__animated animate__fadeInDown'
                            }
                        })
                    } else {
                        let swalObj = {
                            'title': 'Importe Synchronisieren',
                            'msg': 'Ebay-Importe werden Synchronisiert.'
                        }
                        swal_timer(swalObj);
                    }

                    break;

            }

            if (formData) {
                wp_ebay_importer_fetch(formData, false, wec_action_callback)
            }
        });

        function wec_action_callback(data) {
            $('.body-wait-loading').html('');
            let endtime;
            switch (data.type) {
                case 'get_import_overview_template':
                    if (data.status) {
                        colImportOverview.html(data.template);
                        if (data.target) {
                            new bootstrap.Collapse(data.target, {
                                toggle: true,
                                parent: '#collParent'
                            })
                        }
                    } else {
                        warning_message(data.msg)
                    }
                    break;
                case'ebay_import_template_handle':
                    if (data.status) {
                        colImportHandle.html(data.template);
                        new bootstrap.Collapse(colImportHandle, {
                            toggle: true,
                            parent: '#collParent'
                        })
                    } else {
                        warning_message(data.msg);
                    }
                    break;
                case'delete_import':
                    if (data.status) {
                        $('#import' + data.id).remove();
                    }
                    swal_alert_response(data)
                    break;
                case'get_import_settings_template':
                    if (data.status) {
                        colImportSettings.html(data.template);
                        endtime = new Date(data.next_time);
                        initializeClock('#nextSyncTime', endtime);
                        let toggle;
                        data.toggle ? toggle = false : toggle = true;
                        new bootstrap.Collapse(colImportSettings, {
                            toggle: toggle,
                            parent: '#collParent'
                        })
                    } else {
                        warning_message(data.msg);
                    }
                    break;
                case 'load_default_expression':
                    if (data.status) {
                        let formData = {
                            'method': 'get_import_settings_template',
                            'toggle': 1
                        }
                        wp_ebay_importer_fetch(formData, false, wec_action_callback)
                    }
                    swal_alert_response(data)
                    break;
                case 'now_synchronize':
                    if (data.status) {
                        if(!data.system){
                            Swal.close();
                            swal_alert_response(data);
                        }
                    } else {
                        warning_message(data.msg)
                    }
                    break;
            }
        }

        let ebayImporterSendFormTimeout;
        $(document).on('input propertychange change', '.ebay-importer-admin-autosave', function () {
            let formData = $(this).closest("form").get(0);
            let target = $(this).attr('data-target');
            let spin = $(target);
            spin.html('');
            spin.addClass('wait');
            clearTimeout(ebayImporterSendFormTimeout);
            ebayImporterSendFormTimeout = setTimeout(function () {
                wp_ebay_importer_fetch(formData, true, ebay_importer_formular_autosave_callback);
            }, 1000);
        });

        function ebay_importer_formular_autosave_callback(data) {
            switch (data.type) {
                case 'import_db_handle':
                    show_ajax_spinner(data, '.import');
                    break;
            }
        }

        function swal_fire_app_delete(data, swal) {
            Swal.fire({
                title: swal.title,
                reverseButtons: true,
                html: `<span class="swal-delete-body">${swal.body}</span>`,
                confirmButtonText: swal.btn,
                cancelButtonText: 'Abbrechen',
                showClass: {
                    //popup: 'animate__animated animate__fadeInDown'
                },
                customClass: {
                    popup: 'swal-delete-container'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    wp_ebay_importer_fetch(data, false, wec_action_callback)
                }
            });
        }

        function swal_timer(data = '') {
            let timerInterval
            Swal.fire({
                title: data.title ? data.title : 'Kartendaten suchen',
                html: data.msg ? data.msg : 'Open-Street-Map Kartendaten werden gesucht...', //'Daten werden aktualisiert...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                customClass: {
                    popup: 'swal-info-container'
                },
                hideClass: {
                    //popup: 'animate__animated animate__fadeOutUp'
                },
                didOpen: () => {
                    Swal.showLoading()
                    /*const b = Swal.getHtmlContainer().querySelector('b')
                    timerInterval = setInterval(() => {
                        b.textContent = Swal.getTimerLeft()
                    }, 100)*/
                },
                willClose: () => {
                    clearInterval(timerInterval)
                }
            }).then((result) => {
            })
        }

        function set_body_wait_spinner(target) {
            let html = '<div class="body-wait-loading"><div class="body-wait"></div></div>';
            let bodyTarget = document.querySelector(target);
            bodyTarget.insertAdjacentHTML('afterbegin', html);
        }

        function show_ajax_spinner(data, target = '') {
            let msg = '';
            if (data.status) {
                msg = '<i class="text-success fw-bold bi bi-check2-circle"></i>&nbsp; Saved! Last: ' + data.msg;
            } else {
                msg = '<i class="text-danger bi bi-exclamation-triangle"></i>&nbsp; ' + data.msg;
            }
            let spinner = document.querySelector(target + '.ajax-status-spinner');
            spinner.classList.remove('wait');
            spinner.innerHTML = msg;
        }


    })(jQuery);
});
