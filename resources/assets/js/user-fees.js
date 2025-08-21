const assignFeeModal = document.getElementById("assignFeeModal");
if (assignFeeModal) {
    assignFeeModal.addEventListener("show.bs.modal", (event) => {
        const button = event.relatedTarget;
        const feeId = button.getAttribute("data-fee-id");
        const assignedFeeIdInput =
            assignFeeModal.querySelector("#assignedFeeId");
        assignedFeeIdInput.value = feeId;
        // Resetar o select e o checkbox ao abrir o modal
        const assignedUserIdSelect =
            assignFeeModal.querySelector("#assignedUserId");
        assignedUserIdSelect.selectedIndex = 0;
        const isDefaultFeeCheckbox =
            assignFeeModal.querySelector("#isDefaultFee");
        isDefaultFeeCheckbox.checked = false;
    });

    const saveUserFeeButton = document.getElementById("saveUserFee");
    if (saveUserFeeButton) {
        saveUserFeeButton.addEventListener("click", () => {
            const feeId = document.getElementById("assignedFeeId").value;
            const userId = document.getElementById("assignedUserId").value;
            const feeType = document.getElementById("feeType").value;
            const isDefault = document.getElementById("isDefaultFee").checked
                ? 1
                : 0;
            console.log({
                user_id: userId,
                fee_id: feeId,
                is_default: isDefault,
                type: feeType,
            });
            if (userId) {
                fetch("/user-fees", {
                    // Defina sua rota para salvar a associação
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        fee_id: feeId,
                        is_default: isDefault,
                        type: feeType,
                    }),
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.success) {
                            // Exibir mensagem de sucesso (e.g., usando SweetAlert)
                            alert("Fee assigned successfully!");
                            $("#assignFeeModal").modal("hide"); // Fechar o modal
                        } else {
                            // Exibir mensagem de erro
                            alert("Error assigning fee.");
                        }
                    })
                    .catch((error) => {
                        console.error("Error:", error);
                        alert("An error occurred while assigning the fee.");
                    });
            } else {
                alert("Please select a user.");
            }
        });
    }
}
