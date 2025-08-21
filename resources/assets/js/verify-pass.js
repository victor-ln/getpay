/**
 * Classe para validação de senha em tempo real com medidor de força.
 * Versão final adaptada para o tema e com bugs corrigidos.
 */
$(document).ready(function () {
    class PasswordValidator {
        constructor(options = {}) {
            this.options = {
                formSelector: "#formPasswordChange",
                passwordSelector: "#password",
                confirmPasswordSelector: "#password_confirmation",
                saveButtonSelector: ".save-btn",
                minLength: 8,
                requireUppercase: true,
                requireLowercase: true,
                requireNumbers: true,
                requireSpecialChars: true,
                ...options,
            };
            this.init();
        }

        init() {
            this.$form = $(this.options.formSelector);
            if (!this.$form.length) return;

            this.$password = this.$form.find(this.options.passwordSelector);
            this.$confirmPassword = this.$form.find(
                this.options.confirmPasswordSelector,
            );
            this.$saveButton = this.$form.find(this.options.saveButtonSelector);

            if (!this.$password.length || !this.$confirmPassword.length) return;

            this.createFeedbackContainers();
            this.setupEventListeners();
            this.validatePasswords();
        }

        createFeedbackContainers() {
            // Garante que os containers de feedback e medidor de força existam
            if (
                !this.$password
                    .closest(".form-password-toggle")
                    .find(".password-strength-meter").length
            ) {
                this.$password.closest(".input-group").after(`
                    <div class="password-strength-meter mt-2">
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar" role="progressbar" style="width: 0%;"></div>
                        </div>
                        <small class="strength-meter-text text-muted"></small>
                    </div>
                    <div class="password-feedback"></div>
                `);
            }
            if (
                !this.$confirmPassword
                    .closest(".form-password-toggle")
                    .find(".confirm-password-feedback").length
            ) {
                this.$confirmPassword
                    .closest(".input-group")
                    .after('<div class="confirm-password-feedback"></div>');
            }
        }

        setupEventListeners() {
            const debouncedValidate = this.debounce(
                () => this.validatePasswords(),
                300,
            );
            this.$password.on("input.passwordValidator", debouncedValidate);
            this.$confirmPassword.on(
                "input.passwordValidator",
                debouncedValidate,
            );

            // A lógica de mostrar/ocultar senha já é gerenciada pelo seu tema.
            // O script pages-account-settings-account.js provavelmente faz isso.
            // Portanto, removemos a função setupPasswordToggle() daqui para evitar conflitos.
        }

        validatePasswords() {
            // Se ambos os campos estão vazios, não faz nada e habilita o botão (caso de edição de perfil sem mudar a senha)
            if (
                this.$password.val() === "" &&
                this.$confirmPassword.val() === ""
            ) {
                this.resetAllFeedback();
                this.toggleSaveButton(true);
                return;
            }

            const isPasswordValid = this.validatePasswordStrength();
            const isMatchValid = this.validatePasswordMatch();
            this.toggleSaveButton(isPasswordValid && isMatchValid);
        }

        validatePasswordStrength() {
            const password = this.$password.val();
            const errors = [];
            if (password.length < this.options.minLength)
                errors.push(`mínimo ${this.options.minLength} caracteres`);
            if (this.options.requireUppercase && !/[A-Z]/.test(password))
                errors.push("1 maiúscula");
            if (this.options.requireLowercase && !/[a-z]/.test(password))
                errors.push("1 minúscula");
            if (this.options.requireNumbers && !/\d/.test(password))
                errors.push("1 número");
            if (
                this.options.requireSpecialChars &&
                !/[!@#$%^&*(),.?":{}|<>]/.test(password)
            )
                errors.push("1 especial");

            this.updatePasswordFeedback(errors);
            this.updateStrengthMeter(password);
            return errors.length === 0;
        }

        validatePasswordMatch() {
            const password = this.$password.val();
            const confirmPassword = this.$confirmPassword.val();

            if (confirmPassword.length > 0) {
                const isMatch = password === confirmPassword;
                this.updateConfirmPasswordFeedback(isMatch);
                return isMatch;
            }
            this.updateConfirmPasswordFeedback(true); // Se a confirmação está vazia, não mostra erro
            return false; // Mas não é válido para habilitar o botão
        }

        updatePasswordFeedback(errors) {
            const $feedback = this.$password
                .closest(".form-password-toggle")
                .find(".password-feedback");
            if (errors.length > 0) {
                this.$password.removeClass("is-valid").addClass("is-invalid");
                $feedback.html(
                    `<small class="text-danger d-block">Requisitos: ${errors.join(", ")}.</small>`,
                );
            } else {
                this.$password.removeClass("is-invalid").addClass("is-valid");
                $feedback.empty();
            }
        }

        updateConfirmPasswordFeedback(isValid) {
            const $feedback = this.$confirmPassword
                .closest(".form-password-toggle")
                .find(".confirm-password-feedback");
            if (isValid) {
                this.$confirmPassword
                    .removeClass("is-invalid")
                    .addClass("is-valid");
                $feedback.empty();
            } else {
                this.$confirmPassword
                    .removeClass("is-valid")
                    .addClass("is-invalid");
                $feedback.html(
                    `<small class="text-danger">As senhas não coincidem.</small>`,
                );
            }
        }

        updateStrengthMeter(password) {
            const strength = this.calculatePasswordStrength(password);
            const $meterContainer = this.$password
                .closest(".form-password-toggle")
                .siblings(".password-strength-meter");
            const $fill = $meterContainer.find(".progress-bar");
            const $text = $meterContainer.find(".strength-meter-text");

            const strengthConfig = {
                0: { width: "0%", color: "#dc3545", text: "Very Weak" },
                1: { width: "25%", color: "#dc3545", text: "Weak" },
                2: { width: "50%", color: "#ffc107", text: "Medium" },
                3: { width: "75%", color: "#fd7e14", text: "Good" },
                4: { width: "100%", color: "#28a745", text: "Strong" },
                5: { width: "100%", color: "#28a745", text: "Very Strong" },
            };

            const score = password.length > 0 ? strength : 0;
            const config = strengthConfig[score];

            $fill
                .css({ width: config.width, "background-color": config.color })
                .text(`${config.width}`);
            $text.text(config.text).css("color", config.color);
        }

        calculatePasswordStrength(password) {
            let strength = 0;
            if (password.length >= this.options.minLength) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;
            if (password.length >= 12) strength++;
            return strength;
        }

        toggleSaveButton(enable) {
            if (this.$saveButton.length) {
                this.$saveButton.prop("disabled", !enable);
            }
        }

        resetAllFeedback() {
            this.$password.removeClass("is-valid is-invalid");
            this.$confirmPassword.removeClass("is-valid is-invalid");
            this.$form
                .find(".password-feedback, .confirm-password-feedback")
                .empty();
            this.updateStrengthMeter("");
        }

        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func.apply(this, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    }

    if ($("#formPasswordChange").length) {
        new PasswordValidator();
    }

    $("#formPasswordChange").on("submit", async function (e) {
        e.preventDefault(); // Impede o envio tradicional do formulário

        const form = $(this);
        const saveButton = form.find(".save-btn");
        const originalButtonText = saveButton.html();

        saveButton
            .prop("disabled", true)
            .html(
                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...',
            );

        // Pega os dados do formulário
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        try {
            // Envia os dados para o backend via AJAX (PUT)
            const response = await axios.put(form.attr("action"), data);

            // Sucesso! Mostra um alerta de sucesso e limpa os campos
            showToast(response.data.message, "success");
            form[0].reset();
        } catch (error) {
            let errorMessage = "An unexpected error occurred.";
            if (error.response && error.response.status === 422) {
                errorMessage = Object.values(error.response.data.errors)
                    .map((e) => e[0])
                    .join("<br>");
            } else if (error.response && error.response.data.message) {
                errorMessage = error.response.data.message;
            }
            showToast(errorMessage, "danger");
        } finally {
            // Reabilita o botão, independentemente do resultado
            saveButton.prop("disabled", false).html(originalButtonText);
        }
    });

    function showToast(message, type = "success") {
        const toastContainer = $(".toast-container");
        if (!toastContainer.length) return;

        // Mapeia o tipo para as classes de cor do Bootstrap
        const toastColors = {
            success: "bg-success text-white",
            danger: "bg-danger text-white",
            warning: "bg-warning text-dark",
            info: "bg-info text-dark",
        };
        const toastClass = toastColors[type] || "bg-secondary text-white";
        const toastId = `toast-${Date.now()}`;

        // Cria o HTML do toast dinamicamente
        const toastHtml = `
        <div id="${toastId}" class="toast align-items-center ${toastClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

        toastContainer.append(toastHtml);

        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: 3000 }); // O toast some após 5 segundos

        toast.show();

        // Remove o elemento do DOM após ele ser escondido para não poluir a página
        toastElement.addEventListener("hidden.bs.toast", () => {
            toastElement.remove();
        });
    }
});
