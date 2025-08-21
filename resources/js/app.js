import "./bootstrap";
import Swal from "sweetalert2";
window.Swal = Swal;
import Inputmask from "inputmask";

import "jquery-mask-plugin";

// Se quiser, pode colocar um exemplo aqui também
$(document).ready(function () {
    $(".money").mask("000.000.000.000.000,00", { reverse: true });
});
/*
  Add custom scripts here
*/
import.meta.glob([
    "../assets/img/**",
    // '../assets/json/**',
    "../assets/vendor/fonts/**",
]);
