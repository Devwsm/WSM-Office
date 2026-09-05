/**
 * Entry JS utama. Alpine.js dipakai untuk interaksi ringan (sidebar
 * toggle, dsb) — mirip gaya versi native tapi lebih rapi.
 */
import Alpine from "alpinejs";
import "./alerts";
import "./attendance";

window.Alpine = Alpine;
Alpine.start();
