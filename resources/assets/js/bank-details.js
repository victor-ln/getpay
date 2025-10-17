document.addEventListener("DOMContentLoaded", function () {
    const bankDetailsModal = document.getElementById("bankDetailsModal");
    if (!bankDetailsModal) return;

    const loadingState = bankDetailsModal.querySelector("#modal-loading-state");
    const contentState = bankDetailsModal.querySelector("#modal-content-state");
    const title = bankDetailsModal.querySelector(".modal-title");
    const custodyEl = bankDetailsModal.querySelector("#modal-bank-custody");
    const acquirerBalanceEl = bankDetailsModal.querySelector(
        "#modal-acquirer-balance",
    );
    const clientListEl = bankDetailsModal.querySelector("#modal-client-list");

    bankDetailsModal.addEventListener("show.bs.modal", async function (event) {
        const button = event.relatedTarget;
        const bankId = button.dataset.bankId;

        // Reset modal and show loading
        title.textContent = "Bank Details";
        contentState.classList.add("d-none");
        loadingState.classList.remove("d-none");
        custodyEl.textContent = "--";
        acquirerBalanceEl.textContent = "...";
        clientListEl.innerHTML = "";

        try {
            // Fetch data from the new route
            const response = await fetch(`/admin/banks/${bankId}/details`);
            if (!response.ok) throw new Error("Failed to load data");

            const data = await response.json();

            // Populate modal with fetched data
            title.textContent = `Details for ${data.bank_name}`;
            custodyEl.textContent = data.total_custody;
            acquirerBalanceEl.textContent = data.acquirer_balance;

            clientListEl.innerHTML = "";
            if (data.active_clients && data.active_clients.length > 0) {
                const listGroup = document.createElement("ul");
                listGroup.className = "list-group";

                data.active_clients.forEach((client) => {
                    const li = document.createElement("li");
                    li.className =
                        "list-group-item d-flex justify-content-between align-items-center";

                    const clientName = document.createElement("span");
                    clientName.textContent = client.name;

                    const clientBalance = document.createElement("span");
                    clientBalance.className = "badge bg-secondary";
                    clientBalance.textContent = `R$ ${client.balance_formatted}`;

                    li.appendChild(clientName);
                    li.appendChild(clientBalance);
                    listGroup.appendChild(li);
                });
                clientListEl.appendChild(listGroup);
            } else {
                clientListEl.innerHTML =
                    '<p class="text-muted">No clients are currently using this bank as their default.</p>';
            }

            // Show content
            loadingState.classList.add("d-none");
            contentState.classList.remove("d-none");
        } catch (error) {
            console.error("Error fetching bank details:", error);
            title.textContent = "Error";
            clientListEl.innerHTML =
                '<li class="list-group-item text-danger">Could not load details.</li>';
            loadingState.classList.add("d-none");
            contentState.classList.remove("d-none");
        }
    });
});
