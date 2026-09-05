/**
 * alerts.js
 * ---------------------------------------------------------------------
 * Semua interaksi "alert" (notifikasi sukses/gagal, ringkasan validasi,
 * dan konfirmasi sebelum aksi destruktif seperti keluar akun / nonaktifkan
 * karyawan) dipusatkan di sini pakai SweetAlert2, gaya disamakan dengan
 * palet WSM (ink/cream, radius besar, tombol pill).
 *
 * Dua cara pakai dari Blade:
 * 1. Flash otomatis — taruh @include('partials.flash-data') di layout,
 *    partial itu nulis JSON session('status')/session('error')/
 *    $errors ke <script id="wsm-flash-data">, lalu file ini yang baca &
 *    tampilkan toast/summary saat halaman selesai load.
 * 2. Konfirmasi sebelum submit — tambahkan atribut data-confirm di
 *    <form>, contoh:
 *      <form method="POST" action="..." data-confirm="Teks konfirmasi"
 *          data-confirm-title="Judul" data-confirm-button="Ya, lanjutkan"
 *          data-confirm-danger="1">
 *    File ini otomatis intercept submit, tampilkan SweetAlert, dan baru
 *    submit betulan kalau user pilih "Ya".
 * ---------------------------------------------------------------------
 */
import Swal from "sweetalert2";

const swalWsm = Swal.mixin({
    buttonsStyling: false,
    reverseButtons: true,
    customClass: {
        popup: "rounded-[28px]! p-2",
        title: "text-[22px]! font-black! tracking-tight!",
        htmlContainer: "text-[14px]! text-[#5e5951]!",
        confirmButton: "btn-wsm-black mx-1.5!",
        cancelButton: "btn-wsm-white mx-1.5!",
    },
});

const swalWsmDanger = swalWsm.mixin({
    customClass: {
        popup: "rounded-[28px]! p-2",
        title: "text-[22px]! font-black! tracking-tight!",
        htmlContainer: "text-[14px]! text-[#5e5951]!",
        confirmButton: "btn-wsm-red mx-1.5!",
        cancelButton: "btn-wsm-white mx-1.5!",
    },
});

const toastWsm = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3800,
    timerProgressBar: true,
    customClass: {
        popup: "rounded-2xl!",
    },
    didOpen: (el) => {
        el.addEventListener("mouseenter", Swal.stopTimer);
        el.addEventListener("mouseleave", Swal.resumeTimer);
    },
});

const WsmAlert = {
    success(message, title) {
        return toastWsm.fire({
            icon: "success",
            title: title || message,
            text: title ? message : undefined,
        });
    },
    error(message, title) {
        return toastWsm.fire({
            icon: "error",
            title: title || message,
            text: title ? message : undefined,
        });
    },
    warning(message, title) {
        return toastWsm.fire({
            icon: "warning",
            title: title || message,
            text: title ? message : undefined,
        });
    },
    /** Popup (bukan toast) berisi daftar pesan error validasi, dipakai saat form redirect back dengan $errors. */
    validationSummary(messages) {
        const items = messages.map((m) => `<li>${m}</li>`).join("");
        return swalWsm.fire({
            icon: "error",
            title: "Ada isian yang belum sesuai",
            html: `<ul class="mt-2 list-disc space-y-1 pl-5 text-left">${items}</ul>`,
            confirmButtonText: "Oke, saya perbaiki",
        });
    },
    /** Dialog konfirmasi generik. Resolusinya SweetAlert result ({isConfirmed}). */
    confirm({
        title = "Yakin?",
        text = "",
        confirmText = "Ya, lanjutkan",
        cancelText = "Batal",
        icon = "warning",
        danger = false,
    } = {}) {
        const runner = danger ? swalWsmDanger : swalWsm;
        return runner.fire({
            title,
            text,
            icon,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
        });
    },
};

window.WsmAlert = WsmAlert;

// --- Intercept form dengan atribut data-confirm ---
document.addEventListener("submit", function (event) {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (!form.hasAttribute("data-confirm")) return;
    if (form.dataset.confirmed === "1") return; // sudah dikonfirmasi, biarkan submit jalan

    event.preventDefault();

    WsmAlert.confirm({
        title: form.dataset.confirmTitle || "Yakin?",
        text:
            form.getAttribute("data-confirm") || "Tindakan ini akan diproses.",
        confirmText: form.dataset.confirmButton || "Ya, lanjutkan",
        cancelText: form.dataset.cancelButton || "Batal",
        danger: form.dataset.confirmDanger === "1",
    }).then((result) => {
        if (result.isConfirmed) {
            form.dataset.confirmed = "1";
            if (typeof form.requestSubmit === "function") {
                form.requestSubmit();
            } else {
                form.submit();
            }
        }
    });
});

// --- Baca flash data dari Blade & tampilkan otomatis saat halaman load ---
document.addEventListener("DOMContentLoaded", function () {
    const el = document.getElementById("wsm-flash-data");
    if (!el) return;

    let data;
    try {
        data = JSON.parse(el.textContent || "{}");
    } catch (e) {
        return;
    }

    if (data.status) WsmAlert.success(data.status);
    if (data.error) WsmAlert.error(data.error);
    if (data.warning) WsmAlert.warning(data.warning);
    if (Array.isArray(data.errors) && data.errors.length === 1) {
        WsmAlert.error(data.errors[0], "Periksa lagi isiannya");
    } else if (Array.isArray(data.errors) && data.errors.length > 1) {
        WsmAlert.validationSummary(data.errors);
    }
});
