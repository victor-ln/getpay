document
    .getElementById("copyReferralLinkBtn")
    ?.addEventListener("click", function () {
        const input = document.getElementById("referralLinkInput");
        input.select();
        document.execCommand("copy");
        // Feedback para o utilizador
        this.innerHTML = '<i class="bx bx-check"></i>';
        setTimeout(() => {
            this.innerHTML = '<i class="bx bx-copy"></i>';
        }, 2000);
    });
