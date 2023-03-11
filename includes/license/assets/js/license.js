let sendLicense = document.getElementById('sendAjaxLicenseForm');
let licenseAlert = document.getElementById('licenseAlert');
let licenseErrMsg = document.getElementById('licenseErrMsg');
if (sendLicense) {

    sendLicense.addEventListener("submit", function (e) {
        e.preventDefault();
        send_xhr_license_data(sendLicense);
    });
}

function send_xhr_license_data(data) {
    let xhr = new XMLHttpRequest();
    let formData = new FormData();
    xhr.open('POST', license_obj .ajax_url, true);
    let input = new FormData(data);
    for (let [name, value] of input) formData.append(name, value);

    formData.append('_ajax_nonce', license_obj .nonce);
    formData.append('action', license_obj.licenseHandle);
    xhr.send(formData);
    //Response
    xhr.onload = function () {
        if (this.readyState === 4 && this.status === 200) {
            let data = JSON.parse(this.responseText);
            if (data.status) {
                if(!data.if_authorize){
                    licenseAlert.classList.add('d-none');
                    product_aktivieren_button(data.send_url);
                }
            } else {
                licenseAlert.classList.remove('d-none');
                licenseErrMsg.innerHTML = data.msg;
            }
        }
    }
}

function product_aktivieren_button(url) {
    if (!url) {
        return false;
    }
    let html = `<a href="${url}" id="" type="button" class="btn btn-success">
            <i class="bi bi-box-arrow-right"></i>&nbsp; Plugin aktivieren
            </a>`;

    let saveBtn = document.getElementById('saveBtn');
    let activateBtn = document.getElementById('activateBtn');
    saveBtn.classList.add('d-none');
    activateBtn.classList.remove('d-none');
    activateBtn.innerHTML = html;
}