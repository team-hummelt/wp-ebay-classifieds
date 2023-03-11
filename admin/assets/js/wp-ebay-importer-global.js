function swal_alert_response(data) {
    if (data.status) {
        Swal.fire({
            position: 'top-end',
            title: data.title,
            text: data.msg,
            icon: 'success',
            timer: 1500,
            showConfirmButton: false,
            showClass: {
                popup: 'animate__animated animate__fadeInDown'
            },
            customClass: {
                popup: 'bg-light'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp'
            }
        }).then();
    } else {
        Swal.fire({
            position: 'top-end',
            title: data.title,
            text: data.msg,
            icon: 'error',
            timer: 3000,
            showConfirmButton: false,
            showClass: {
                popup: 'animate__animated animate__fadeInDown'
            },
            customClass: {
                popup: 'swal-error-container'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp'
            }
        }).then();
    }
}

//Message Handle
function success_message(msg) {
    let x = document.getElementById("snackbar-success");
    x.innerHTML = msg;
    x.className = "show";
    setTimeout(function () {
        x.className = x.className.replace("show", "");
    }, 3000);
}

function warning_message(msg) {
    let x = document.getElementById("snackbar-warning");
    x.innerHTML = msg;
    x.className = "show";
    setTimeout(function () {
        x.className = x.className.replace("show", "");
    }, 3000);
}

function getTimeRemaining(endtime) {
    const total = Date.parse(endtime) - Date.parse(new Date());
    const seconds = Math.floor((total / 1000) % 60);
    const minutes = Math.floor((total / 1000 / 60) % 60);
    const hours = Math.floor((total / (1000 * 60 * 60)) % 24);
    const days = Math.floor(total / (1000 * 60 * 60 * 24));

    return {
        total,
        days,
        hours,
        minutes,
        seconds
    };
}

function initializeClock(target, endtime) {

    if (!target) {
        return false;
    }
    const timeinterval = setInterval(() => {
        const t = getTimeRemaining(endtime);
        const clock = document.querySelector(target);
        if(!clock){
            return false;
        }
        clock.innerHTML = `<small class="fw-semibold mt-2">${t.days > 0 ? t.days + ' Tag(e) ' : '0 Tag(e) '} ${('0' + t.hours).slice(-2)}:${('0' + t.minutes).slice(-2)}:${('0' + t.seconds).slice(-2)}</small>`;
        if (t.total <= 0) {
            clearInterval(timeinterval);
        }
    }, 1000);
}