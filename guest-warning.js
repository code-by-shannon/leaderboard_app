document.addEventListener("DOMContentLoaded", () => {
    const input = document.querySelector('input[name="name"]');
    const warning = document.getElementById("guest-warning");
    const dismiss = document.getElementById("dismiss-warning");

    if (!input || !warning || !dismiss) return;

    let shownThisSession = false;

    input.addEventListener("focus", () => {
        if (!shownThisSession) {
            warning.classList.remove("hidden");
            shownThisSession = true;
        }
    });

    dismiss.addEventListener("click", () => {
        warning.classList.add("hidden");
    });
});
