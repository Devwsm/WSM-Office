/**
 * attendance.js
 * ---------------------------------------------------------------------
 * Fase 4 — logic widget absen di employee/home.blade.php:
 * 1. Ambil geolocation browser (dipakai buat "Test Lokasi" & absen beneran).
 * 2. Render mini map (Leaflet + OpenStreetMap, tanpa API key) dengan
 *    marker kantor, marker posisi user, dan lingkaran radius toleransi
 *    — biar user SADAR sebelum absen kalau dia jauh dari kantor.
 * 3. Opsional ambil foto selfie langsung dari kamera depan (bukan upload
 *    galeri — pakai `capture="user"` di <input type="file">), dikompres
 *    lewat <canvas> di browser (nggak nambah dependency PHP buat resize
 *    gambar di server, sesuai batasan hosting cPanel tanpa terminal).
 * 4. Jarak & radius yang dihitung di sini CUMA buat UX (kasih tau user
 *    lebih awal) — validasi & hitung ulang jarak yang beneran dipakai
 *    tetap di server (lihat App\Support\Geo & AttendanceController),
 *    karena koordinat dari browser bisa dimanipulasi.
 * ---------------------------------------------------------------------
 */
import Alpine from "alpinejs";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

function haversineMeters(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const toRad = (d) => (d * Math.PI) / 180;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
    return Math.round(R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
}

/** Kompres file gambar jadi JPEG base64 data URL, max lebar/tinggi `maxSize`. */
function compressImage(file, maxSize = 720, quality = 0.75) {
    return new Promise((resolve, reject) => {
        if (!file) {
            resolve(null);
            return;
        }
        const reader = new FileReader();
        reader.onerror = () => reject(new Error("Gagal membaca file foto."));
        reader.onload = () => {
            const img = new Image();
            img.onerror = () => reject(new Error("Gagal memuat foto."));
            img.onload = () => {
                let { width, height } = img;
                if (width > height && width > maxSize) {
                    height = Math.round((height * maxSize) / width);
                    width = maxSize;
                } else if (height > maxSize) {
                    width = Math.round((width * maxSize) / height);
                    height = maxSize;
                }
                const canvas = document.createElement("canvas");
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext("2d");
                ctx.drawImage(img, 0, 0, width, height);
                resolve(canvas.toDataURL("image/jpeg", quality));
            };
            img.src = reader.result;
        };
        reader.readAsDataURL(file);
    });
}

Alpine.data("attendanceWidget", (config) => ({
    mode: config.defaultMode || "kantor",
    workContext: "",
    officeLat: config.officeLat,
    officeLng: config.officeLng,
    officeName: config.officeName,
    radiusMeters: config.radiusMeters,
    hasClockIn: config.hasClockIn,
    hasClockOut: config.hasClockOut,

    photoDataUrl: null,
    photoLoading: false,

    geo: null,
    distance: null,
    withinRadius: null,
    geoLoading: false,
    geoError: null,

    showModal: false,
    modalPurpose: null, // 'test' | 'clockIn' | 'clockOut'
    submitting: false,

    map: null,

    async captureGeo() {
        this.geoLoading = true;
        this.geoError = null;

        if (!navigator.geolocation) {
            this.geoLoading = false;
            this.geoError = "Geolocation tidak didukung browser ini.";
            return false;
        }

        try {
            const position = await new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(resolve, reject, {
                    enableHighAccuracy: true,
                    timeout: 12000,
                    maximumAge: 0,
                });
            });

            this.geo = {
                lat: position.coords.latitude,
                lng: position.coords.longitude,
                accuracy: position.coords.accuracy,
            };

            if (this.mode === "kantor") {
                this.distance = haversineMeters(
                    this.officeLat,
                    this.officeLng,
                    this.geo.lat,
                    this.geo.lng,
                );
                this.withinRadius = this.distance <= this.radiusMeters;
            } else {
                this.distance = null;
                this.withinRadius = null;
            }

            this.geoLoading = false;
            return true;
        } catch (err) {
            this.geoLoading = false;
            this.geoError =
                err.code === 1
                    ? "Izin lokasi ditolak. Aktifkan akses lokasi untuk browser ini dulu."
                    : err.code === 2
                      ? "Lokasi tidak tersedia. Coba lagi di tempat dengan sinyal GPS lebih baik."
                      : "Pengambilan lokasi timeout, coba lagi.";
            return false;
        }
    },

    async testLocation() {
        const ok = await this.captureGeo();
        if (!ok) {
            window.WsmAlert?.error(this.geoError, "Gagal ambil lokasi");
            return;
        }
        this.modalPurpose = "test";
        this.showModal = true;
        this.$nextTick(() => this.renderMap());
    },

    async openConfirm(purpose) {
        const ok = await this.captureGeo();
        if (!ok) {
            window.WsmAlert?.error(this.geoError, "Gagal ambil lokasi");
            return;
        }
        this.modalPurpose = purpose;
        this.showModal = true;
        this.$nextTick(() => this.renderMap());
    },

    closeModal() {
        this.showModal = false;
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
    },

    renderMap() {
        if (!this.$refs.mapEl || !this.geo) return;
        if (this.map) {
            this.map.remove();
            this.map = null;
        }

        const userPoint = [this.geo.lat, this.geo.lng];
        const officePoint = [this.officeLat, this.officeLng];
        const showRadius =
            this.mode === "kantor" && this.officeLat && this.officeLng;

        const bounds = showRadius
            ? L.latLngBounds([userPoint, officePoint])
            : L.latLngBounds([userPoint]);

        this.map = L.map(this.$refs.mapEl, {
            zoomControl: false,
            attributionControl: false,
        });

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
        }).addTo(this.map);

        L.circleMarker(userPoint, {
            radius: 9,
            color: "#111111",
            weight: 2,
            fillColor: this.withinRadius === false ? "#f16c61" : "#27c84d",
            fillOpacity: 1,
        })
            .addTo(this.map)
            .bindTooltip("Posisi kamu");

        if (showRadius) {
            L.circleMarker(officePoint, {
                radius: 7,
                color: "#111111",
                weight: 2,
                fillColor: "#3558f4",
                fillOpacity: 1,
            })
                .addTo(this.map)
                .bindTooltip(this.officeName || "Kantor");

            L.circle(officePoint, {
                radius: this.radiusMeters,
                color: "#3558f4",
                weight: 1,
                fillColor: "#3558f4",
                fillOpacity: 0.08,
            }).addTo(this.map);

            bounds.extend(
                L.circle(officePoint, {
                    radius: this.radiusMeters,
                }).getBounds(),
            );
        }

        this.map.fitBounds(bounds.pad(0.35));
    },

    async handlePhotoInput(event) {
        const file = event.target.files?.[0];
        if (!file) return;

        this.photoLoading = true;
        try {
            this.photoDataUrl = await compressImage(file);
        } catch (err) {
            window.WsmAlert?.error(err.message || "Gagal memproses foto.");
        } finally {
            this.photoLoading = false;
        }
    },

    removePhoto() {
        this.photoDataUrl = null;
        if (this.$refs.photoInput) this.$refs.photoInput.value = "";
    },

    confirmSubmit() {
        if (!this.geo) return;
        this.submitting = true;

        const formRef =
            this.modalPurpose === "clockIn"
                ? this.$refs.clockInForm
                : this.$refs.clockOutForm;

        formRef.querySelector('[name="lat"]').value = this.geo.lat;
        formRef.querySelector('[name="lng"]').value = this.geo.lng;
        formRef.querySelector('[name="accuracy"]').value = Math.round(
            this.geo.accuracy || 0,
        );
        formRef.querySelector('[name="photo"]').value = this.photoDataUrl || "";

        if (this.modalPurpose === "clockIn") {
            formRef.querySelector('[name="mode"]').value = this.mode;
            formRef.querySelector('[name="work_context"]').value =
                this.workContext;
        }

        formRef.requestSubmit ? formRef.requestSubmit() : formRef.submit();
    },
}));
