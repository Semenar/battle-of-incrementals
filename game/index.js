// Clock

var clock_last_run = new Date();
var clock_left = 0;
var clock_limit = 0;

function convert_to_time(seconds) {
    if (seconds < 0) return "00:00";

    minutes = Math.floor(seconds / 60);
    seconds -= minutes * 60;

    hours = Math.floor(minutes / 60);
    minutes -= hours * 60;

    days = Math.floor(hours / 24);
    hours -= days * 24;

    if (days > 0) {
        return days + "." + String(hours).padStart(2, "0") + ":" + String(minutes).padStart(2, "0") + ":" + String(seconds).padStart(2, "0");
    }
    else if (hours > 0) {
        return String(hours).padStart(2, "0") + ":" + String(minutes).padStart(2, "0") + ":" + String(seconds).padStart(2, "0");
    }
    else {
        return String(minutes).padStart(2, "0") + ":" + String(seconds).padStart(2, "0");
    }
}

function ticking_clock(left, limit) {
    clock_last_run = Date.now();
    clock_left = left * 1000;
    clock_limit = limit * 1000;
    setInterval(ticking_clock_update, 100);
}

function ticking_clock_update() {
    clock_left -= Date.now() - clock_last_run;
    clock_last_run = Date.now();

    document.getElementById("clock").innerHTML = convert_to_time(Math.floor(clock_left / 1000));
    if (clock_left < clock_limit) document.getElementById("clock").className = "screen-stat-clock alert";
}