document.addEventListener("DOMContentLoaded", function () {
    (function ($) {
        'use strict';
        function public_ebay_importer_fetch(data, is_formular = true, callback) {

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
            formData.append('_ajax_nonce', ecp_ajax_obj.nonce);
            formData.append('action', 'PublicEbayImporter');

            fetch(ecp_ajax_obj.ajax_url, {
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

        let content = $('.site-content');
        if(content !== 0){
            let formData = {
                'method':'test'
            }
           // public_ebay_importer_fetch(formData, false, public_test_callback)
        }

        function public_test_callback(data){
            console.log(data.msg);
        }

    })(jQuery);
});
