const MAX = 1000;
let suggest, remaining, submit;

document.addEventListener('DOMContentLoaded', function(event) {
    suggest = document.getElementById('suggest');
    remaining = document.getElementById('remaining');
    submit = document.getElementById('submit');

    setRemaining();
    
    if(suggest) {
        suggest.addEventListener('keyup', function (event) {
            setRemaining();
        });
    }
});

function setRemaining() {
    let result = MAX - suggest.value.length;

    if (result < 0) {
        if (!submit.disabled) {
            submit.disabled = true;
        }
    } else {
        if (submit.disabled) {
            submit.disabled = false;
        }
    }

    remaining.textContent = result;
}
