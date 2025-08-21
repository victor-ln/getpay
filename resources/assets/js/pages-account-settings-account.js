document.addEventListener("DOMContentLoaded", function () {
    const passwordInput = document.getElementById("password");
    const togglePasswordIcon = document.getElementById("togglePassword");

    togglePasswordIcon.addEventListener("click", function () {
        const type =
            passwordInput.getAttribute("type") === "password"
                ? "text"
                : "password";
        passwordInput.setAttribute("type", type);
        this.classList.toggle("bx-lock-alt");
        this.classList.toggle("bx-lock-open-alt");
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const passwordInput = document.getElementById("password_confirmation");
    const togglePasswordIcon = document.getElementById("togglePasswordConfirm");

    togglePasswordIcon.addEventListener("click", function () {
        const type =
            passwordInput.getAttribute("type") === "password"
                ? "text"
                : "password";
        passwordInput.setAttribute("type", type);
        this.classList.toggle("bx-lock-alt");
        this.classList.toggle("bx-lock-open-alt");
    });
});
