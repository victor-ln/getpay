/**
 * Inicializa a DataTable para a página de gestão de reembolsos
 * com layout customizado e integração de filtros.
 */

import $ from "jquery";
import "datatables.net-bs5";
import "datatables.net-buttons-bs5";
import "datatables.net-buttons/js/buttons.html5.js";
import "datatables.net-buttons/js/buttons.print.js";
import JSZip from "jszip";
import pdfMake from "pdfmake/build/pdfmake";
import pdfFonts from "pdfmake/build/vfs_fonts";

// Configuração para exportação PDF e Excel
window.JSZip = JSZip;
pdfMake.vfs = pdfFonts.pdfMake.vfs;

$(document).ready(function () {
    const tableElement = $("#refunds-datatable");
    if (!tableElement.length) {
        return;
    }

    // Pega os valores dos filtros da URL para pré-preenchê-los
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get("status") || "refundable";
    const search = urlParams.get("search") || "";
    const date_from = urlParams.get("date_from") || "";
    const date_to = urlParams.get("date_to") || "";

    // Cria o HTML da nossa barra de filtros customizada
    const customFilterBar = `
        <div class="row g-3">
            <div class="col-md-3">
                <label for="status-filter" class="form-label">Status</label>
                <select class="form-select form-select-sm" name="status" id="status-filter">
                    <option value="refundable" ${status === "refundable" ? "selected" : ""}>Refundable</option>
                    <option value="refunded" ${status === "refunded" ? "selected" : ""}>Refunded</option>
                    <option value="all" ${status === "all" ? "selected" : ""}>All</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="search-filter" class="form-label">Search User</label>
                <input type="text" class="form-control form-control-sm" name="search" id="search-filter" placeholder="Name or email..." value="${search}">
            </div>
            <div class="col-md-2">
                <label for="date-from-filter" class="form-label">From</label>
                <input type="date" class="form-control form-control-sm" name="date_from" id="date-from-filter" value="${date_from}">
            </div>
            <div class="col-md-2">
                <label for="date-to-filter" class="form-label">To</label>
                <input type="date" class="form-control form-control-sm" name="date_to" id="date-to-filter" value="${date_to}">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
        </div>
    `;

    // Inicializa a DataTable
    const dataTable = tableElement.DataTable({
        // A configuração do DOM agora cria um container para nossos botões e filtros
        dom:
            "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
            "<'row'<'col-12'tr>>" +
            "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",

        // Desabilita a busca padrão da DataTables
        searching: true,

        // Botões de exportação
        buttons: [
            { extend: "copy", className: "btn-sm" },
            { extend: "csv", className: "btn-sm" },
            { extend: "excel", className: "btn-sm" },
            { extend: "pdf", className: "btn-sm" },
            { extend: "print", className: "btn-sm" },
        ],

        // Ordem inicial pela coluna de data
        order: [[0, "desc"]],
    });

    // Adiciona nossa barra de filtros e os botões de exportação ao cabeçalho da tabela
    const toolbar = `
        <div class="card-header">
            <form action="{{ route('refunds.index') }}" method="GET">
                ${customFilterBar}
            </form>
            <hr>
            <div class="dt-buttons btn-group flex-wrap"></div>
        </div>
    `;

    tableElement.closest(".card-datatable").prepend(toolbar);
    dataTable.buttons().container().appendTo(".dt-buttons");
});
