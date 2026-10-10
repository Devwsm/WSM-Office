<?php

/**
 * Konten halaman depan publik (Beranda) — profil artis Whisnu Santika.
 * ---------------------------------------------------------------------
 * Satu tempat untuk semua teks, angka, dan tautan yang tampil di Beranda
 * di luar judul/tagline/4 kartu (itu tetap dikelola Owner lewat Pengaturan
 * Kantor → Beranda Publik). Mau memperbarui angka atau menambah rilisan?
 * Cukup edit file ini, tidak perlu menyentuh Blade.
 *
 * Sumber (dicek 2026-10-10): whisnusantika.com (statistik, daftar rilisan,
 * tautan), Mixcloud resmi (awal karier), JawaPos / Medcom / DVOX Mag /
 * EDM Identity / Magnetic Mag (sorotan karier dan album). Statistik
 * berubah tiap saat, jadi `stats_note` menyebut kapan angkanya diambil.
 * Tidak ada gambar yang ditautkan langsung dari situs lain.
 * ---------------------------------------------------------------------
 */
return [

    'artist' => [
        'name' => 'Whisnu Santika',
        'role' => 'DJ & Producer',
        'title' => 'Pionir Indonesian Bounce',
        'summary' => 'DJ, produser, dan penulis lagu asal Jakarta yang memadukan EDM, dancehall, hip hop, dan afrobeat '
            . 'dalam satu identitas bunyi yang khas. Dari klub Jakarta sampai panggung Tomorrowland Belgium, '
            . 'ia membawa Indonesian Bounce ke pendengar di banyak negara.',
        'genres' => ['Indonesian Bounce', 'EDM', 'Dancehall', 'Afrobeat', 'House'],
        'official_site' => 'https://whisnusantika.com',
    ],

    // Angka dari whisnusantika.com. `value` + `suffix` ditampilkan; `decimals`
    // dipakai animasi hitung naik. Perbarui bila angkanya sudah jauh berubah.
    'stats_note' => 'Angka per Oktober 2026, sumber whisnusantika.com.',
    'stats' => [
        ['label' => 'YouTube Subscribers', 'value' => 593, 'suffix' => 'K+', 'decimals' => 0],
        ['label' => 'Spotify Monthly Listeners', 'value' => 3.3, 'suffix' => 'M+', 'decimals' => 1],
        ['label' => 'Instagram Followers', 'value' => 443, 'suffix' => 'K+', 'decimals' => 0],
    ],

    'album' => [
        'badge' => 'Album Debut',
        'title' => 'Map of Feelings',
        'text' => 'Sisi yang lebih personal dari Whisnu: musik dansa tetap jadi fondasinya, tetapi setiap lagu '
            . 'bercerita tentang cinta, kehilangan, rindu, dan proses mengenal diri sendiri. Ia ingin menjadi '
            . 'jembatan antara musik elektronik dan dunia pop Indonesia.',
        'url' => 'https://mapoffeelings.com/',
        'collaborators' => [
            'Ari Lesmana',
            'eńau',
            'Judika',
            'Ifan Seventeen',
            'Nuca',
            'Rossa',
            'Maul Ibrahim',
            'Fanny Soegi',
            'Tiara Andini',
        ],
    ],

    'highlights' => [
        [
            'color' => '#3558f4',
            'label' => 'Panggung Dunia',
            'title' => 'Tomorrowland Belgium',
            'text' => 'Tampil di 2024 dan kembali dipercaya mengisi panggung Tomorrowland Belgium 2026.'
        ],
        [
            'color' => '#deb92e',
            'label' => 'Festival',
            'title' => 'Djakarta Warehouse Project',
            'text' => 'Rutin tampil di DWP, termasuk Year Mix 2025 yang direkam langsung di DWP25 Bali.'
        ],
        [
            'color' => '#27c84d',
            'label' => 'Identitas Bunyi',
            'title' => 'Indonesian Bounce',
            'text' => 'Perpaduan house, Latin, breaks, dan funk yang ia pelopori dan kini dikenal luas.'
        ],
        [
            'color' => '#b4ef4b',
            'label' => 'Event Sendiri',
            'title' => 'INTRA FUTURA',
            'text' => 'Ruang untuk memainkan house, tech house, dan techno di luar warna Indonesian Bounce.'
        ],
    ],

    // Rilisan pilihan, urutan tampil = urutan di sini. `year` boleh null.
    'releases' => [
        ['title' => 'Aku Harus Pergi', 'artists' => 'Whisnu Santika, Ari Lesmana', 'year' => 2026, 'url' => 'https://ffm.to/akuharuspergi'],
        ['title' => 'I\'ll Be Yours', 'artists' => 'Whisnu Santika, Rey Putra, Cosmo Kent', 'year' => 2025, 'url' => 'https://whisnusantika.ffm.to/illbeyours'],
        ['title' => 'J-Town', 'artists' => 'Whisnu Santika, Rey Putra, MC DRWE', 'year' => null, 'url' => 'https://ffm.to/j-town'],
        ['title' => 'Yalla Habibi', 'artists' => 'Whisnu Santika', 'year' => 2025, 'url' => 'https://ffm.to/yallahabibi'],
        ['title' => 'Vamos!', 'artists' => 'Whisnu Santika, hbrp, KEEBO, MC Spyder', 'year' => null, 'url' => 'https://ffm.to/vamosmusic'],
        ['title' => 'Cartel', 'artists' => 'Whisnu Santika, hbrp, KEEBO', 'year' => null, 'url' => 'https://ffm.to/cartelmusic'],
        ['title' => 'Mambo Jambo', 'artists' => 'Whisnu Santika, Adnan Veron, Dub It, Liquid Silva', 'year' => null, 'url' => 'https://ffm.to/mambojambomusic'],
        ['title' => 'Tequila', 'artists' => 'Whisnu Santika, East Blake, Adnan Veron', 'year' => null, 'url' => 'https://ffm.to/tequilayouranthem'],
    ],

    // Warna kartu rilisan bergilir mengikuti palet WSM.
    'release_colors' => ['#3558f4', '#deb92e', '#27c84d', '#b4ef4b', '#f16c61'],

    // ---- Tentang Kami (public/about.blade.php) -----------------------------
    // DRAF dari sumber publik; visi & misi dirumuskan dari pernyataan Whisnu di
    // media (mis. memetakan musik Indonesia ke panggung dunia, menjembatani
    // elektronik dan pop). Minta tim/Owner mengonfirmasi atau mengganti.
    'about' => [
        'intro' => 'Whisnu Santika Musik (WSM) adalah rumah di balik karya Whisnu Santika, DJ, produser, dan penulis '
            . 'lagu asal Jakarta yang memelopori Indonesian Bounce. Kami mengurus proses kreatif, rilis, kampanye, '
            . 'dan tim yang bekerja di baliknya, supaya musik dari Jakarta sampai ke pendengar di dalam dan luar negeri.',
        'vision' => 'Menempatkan musik Indonesia di peta musik dunia lewat karya yang jujur, berenergi, '
            . 'dan dekat dengan pendengarnya.',
        'mission' => [
            'Memproduksi musik yang berakar pada identitas Indonesia dan terbuka pada pengaruh dunia.',
            'Menjembatani musik elektronik dan pop Indonesia lewat kolaborasi lintas genre.',
            'Menghadirkan lagu yang menemani dan menyemangati pendengar sehari-hari.',
            'Membangun komunitas pendengar dan tim kreatif yang tumbuh bersama.',
        ],
        'timeline_intro' => 'Tonggak penting perjalanan Whisnu Santika, dari kamar produksi sampai panggung dunia.',
        'timeline' => [
            ['year' => '2012', 'text' => 'Mulai berkarier sebagai DJ di Jakarta.'],
            ['year' => '2017', 'text' => 'Mulai memproduseri musik dan merilis single pertama, "Indonesia".'],
            ['year' => '2018', 'text' => 'Merilis "Jungle Bash" dan memantapkan warna bounce-nya.'],
            ['year' => '2019', 'text' => 'Merilis "Chitty Chitty Bang!", tonggak awal nuansa Latin khasnya.'],
            ['year' => '2024', 'text' => 'Pertama kali tampil di Tomorrowland Belgium.'],
            ['year' => '2025', 'text' => 'Tampil di Djakarta Warehouse Project; merilis "Mangu", "Yalla Habibi", "J-Town", dan "I\'ll Be Yours".'],
            ['year' => '2026', 'text' => 'Merilis album debut Map of Feelings dan kembali ke Tomorrowland Belgium.'],
        ],
    ],

    // ---- Layanan (public/services.blade.php) --------------------------------
    // `badge` = nama kelas badge di app.css (blue, yellow, green, gray).
    'services' => [
        'intro' => 'Dari ide pertama sampai karya sampai ke pendengar, tim WSM menangani empat hal ini.',
        'items' => [
            [
                'badge' => 'blue',
                'tag' => 'Produksi',
                'title' => 'Produksi Musik',
                'text' => 'Dari ide, aransemen, dan kolaborasi vokal sampai rilis di platform digital. Cakupannya single, '
                    . 'remix dan edit pack, sampai album penuh seperti Map of Feelings.'
            ],
            [
                'badge' => 'yellow',
                'tag' => 'Kampanye',
                'title' => 'Kampanye & Promosi',
                'text' => 'Strategi rilis dan promosi yang menghubungkan karya ke pendengar: lyric video, konten sosial '
                    . 'media, sampai peluncuran kreatif seperti sesi peluncuran single di Roblox.'
            ],
            [
                'badge' => 'green',
                'tag' => 'Kreatif',
                'title' => 'Arahan Kreatif',
                'text' => 'Menjaga identitas bunyi dan visual tiap rilisan: konsep album, artwork, video, dan cerita '
                    . 'di balik lagu supaya satu proyek terasa utuh.'
            ],
            [
                'badge' => 'gray',
                'tag' => 'Operasional',
                'title' => 'Manajemen Tim',
                'text' => 'Mengatur kerja tim di balik layar: jadwal dan pembagian tugas, absensi, serta anggaran '
                    . 'proyek lewat sistem kerja internal WSM.'
            ],
        ],
    ],

    // ---- Beranda, bagian "Yang kami kerjakan" -------------------------------
    'work' => [
        ['title' => 'Produksi Musik', 'text' => 'Dari ide dan kolaborasi vokal sampai rilis, termasuk single, edit pack, dan album.'],
        ['title' => 'Kampanye & Promosi', 'text' => 'Rilis, lyric video, dan peluncuran kreatif yang menghubungkan karya ke pendengar.'],
        ['title' => 'Operasional Tim', 'text' => 'Jadwal, tugas, absensi, dan anggaran proyek tim di balik layar.'],
    ],

    'socials' => [
        ['label' => 'Instagram', 'url' => 'https://www.instagram.com/whisnusantika'],
        ['label' => 'YouTube', 'url' => 'https://youtube.com/@whisnusantika'],
        ['label' => 'Spotify', 'url' => 'https://open.spotify.com/artist/6gvsmDZKW5wRvjKCPnbHDh'],
        ['label' => 'TikTok', 'url' => 'https://www.tiktok.com/@whisnusantika'],
    ],
];