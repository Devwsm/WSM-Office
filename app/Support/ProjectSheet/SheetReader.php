<?php

namespace App\Support\ProjectSheet;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * SheetReader — membaca file tracker (xlsx / xls / csv) yang diunduh dari Google
 * Sheet atau dari tombol "Download Excel" project, lalu mengubahnya jadi baris
 * bersih siap dicocokkan oleh SheetSync.
 *
 * Padanan `v32ParseXlsxFile()` + `v27AnalyzeCsvText()` di prototype, dengan
 * aturan yang sama:
 *   - Sheet terbaik dipilih otomatis (yang judul kolomnya paling lengkap),
 *     judul kolom dicari di 50 baris pertama (jadi judul/tanggal di atas tabel
 *     tidak masalah).
 *   - Baris "judul section" dikenali dan dipakai sebagai section item di
 *     bawahnya: baris yang SECTION-nya sama dengan ITEM, atau baris yang hanya
 *     berisi teks HURUF BESAR tanpa NO/DATE/PIC/PROGRESS.
 *   - Kolom SECTION boleh tidak berjudul (di tracker tim, kolom itu ada di
 *     antara NO dan ITEM tanpa judul).
 *   - Tanggal serial Excel dikonversi otomatis; link diambil dari hyperlink sel
 *     (di Google Sheet teks sel-nya cuma "LINK", URL-nya ada di hyperlink).
 */
final class SheetReader
{
    /** Alias judul kolom -> field. Dibandingkan setelah di-UPPERCASE & spasi dirapikan. */
    private const ALIASES = [
        'id' => ['ID WSM', 'ID', 'WSM ID'],
        'section' => ['SECTION', 'CATEGORY', 'KATEGORI'],
        'no' => ['NO', 'NO.', 'NOMOR'],
        'item' => ['ITEM', 'TASK', 'ITEM / TASK', 'ITEM/TASK', 'JUDUL', 'PEKERJAAN'],
        'date' => ['DATE', 'DUE', 'DUE DATE', 'TENGGAT', 'DEADLINE', 'TANGGAL'],
        'focus' => ['FOCUS'],
        'pic' => ['PIC', 'PIC 1'],
        'pic2' => ['PIC 2'],
        'pic3' => ['PIC 3'],
        'progress' => ['PROGRESS', 'STATUS'],
        'note' => ['NOTE', 'NOTES', 'CATATAN'],
        'link' => ['LINK', 'URL'],
    ];

    private const MAX_ROWS = 3000;

    private const HEADER_SCAN_ROWS = 50;

    /**
     * @return array{sheet: string, header_row: int, rows: list<array<string, mixed>>}
     *
     * @throws \RuntimeException pesan siap tampil ke user.
     */
    public function parse(string $path, string $extension): array
    {
        try {
            $book = $this->load($path, strtolower($extension));
        } catch (\Throwable $e) {
            throw new \RuntimeException('File tidak bisa dibaca — pastikan formatnya .xlsx atau .csv yang tidak rusak.');
        }

        $best = null;

        foreach ($book->getAllSheets() as $sheet) {
            $found = $this->findHeader($sheet);

            if ($found !== null && ($best === null || $found['score'] > $best['score'])) {
                $best = $found + ['worksheet' => $sheet];
            }
        }

        if ($best === null) {
            throw new \RuntimeException('Kolom ITEM (atau TASK) tidak ditemukan di file ini. Pakai file hasil "Download Excel" project, atau tracker Google Sheet dengan kolom NO / ITEM / DATE / PIC / PROGRESS.');
        }

        /** @var Worksheet $ws */
        $ws = $best['worksheet'];
        $highest = min($ws->getHighestDataRow(), $best['header_row'] + self::MAX_ROWS + 1);

        if ($ws->getHighestDataRow() > $best['header_row'] + self::MAX_ROWS) {
            throw new \RuntimeException('File terlalu besar (lebih dari ' . self::MAX_ROWS . ' baris). Pecah dulu per project.');
        }

        return [
            'sheet' => $ws->getTitle(),
            'header_row' => $best['header_row'],
            'rows' => $this->readRows($ws, $best['map'], $best['header_row'], $highest),
        ];
    }

    private function load(string $path, string $extension): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        if ($extension === 'csv') {
            $reader = new Csv;
            $reader->setInputEncoding('UTF-8');

            return $reader->load($path);
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(false); // hyperlink sel hanya terbaca kalau bukan data-only.

        return $reader->load($path);
    }

    /** @return array{header_row: int, map: array<string, int>, score: int}|null */
    private function findHeader(Worksheet $ws): ?array
    {
        $limit = min($ws->getHighestDataRow(), self::HEADER_SCAN_ROWS);
        $lastCol = Coordinate::columnIndexFromString($ws->getHighestDataColumn());
        $best = null;

        for ($row = 1; $row <= $limit; $row++) {
            $map = [];
            $headers = [];

            for ($col = 1; $col <= $lastCol; $col++) {
                $header = $this->normalizeHeader($ws->getCell([$col, $row])->getValue());
                $headers[$col] = $header;

                foreach (self::ALIASES as $field => $aliases) {
                    if (! isset($map[$field]) && in_array($header, $aliases, true)) {
                        $map[$field] = $col;
                        break;
                    }
                }
            }

            if (! isset($map['item'])) {
                continue;
            }

            // Kolom SECTION tanpa judul tepat di sebelah kiri ITEM (tracker tim).
            if (! isset($map['section']) && $map['item'] > 1 && ($headers[$map['item'] - 1] ?? null) === '') {
                $map['section'] = $map['item'] - 1;
            }

            $score = count($map);

            if ($score >= 2 && ($best === null || $score > $best['score'])) {
                $best = ['header_row' => $row, 'map' => $map, 'score' => $score];
            }
        }

        return $best;
    }

    private function normalizeHeader(mixed $value): string
    {
        return mb_strtoupper(trim((string) preg_replace('/\s+/u', ' ', (string) ($value ?? ''))));
    }

    /**
     * @param  array<string, int>  $map
     * @return list<array<string, mixed>>
     */
    private function readRows(Worksheet $ws, array $map, int $headerRow, int $highest): array
    {
        $links = $this->hyperlinks($ws);
        $rows = [];
        $currentSection = '';

        for ($r = $headerRow + 1; $r <= $highest; $r++) {
            $cell = fn(string $field) => isset($map[$field]) ? $this->cellValue($ws, $map[$field], $r) : null;

            $id = $this->text($cell('id'));
            $section = $this->text($cell('section'));
            $no = $this->text($cell('no'));
            $title = $this->text($cell('item'));
            $dateRaw = $cell('date');
            $pic = $this->text($cell('pic'));
            $pic2 = $this->text($cell('pic2'));
            $pic3 = $this->text($cell('pic3'));
            $progress = $this->text($cell('progress'));

            if ($title === '' && $id === '') {
                continue;
            }

            // Baris judul section: bukan task, hanya penanda kelompok.
            if ($id === '' && $title !== '' && $this->isSectionHeader($section, $no, $title, $dateRaw, $pic, $progress)) {
                $currentSection = $title;

                continue;
            }

            $rows[] = [
                'row' => $r,
                'id' => $id,
                'section' => $section !== '' ? $section : $currentSection,
                'no' => $no,
                'title' => $title,
                'date' => $dateRaw,
                'pic' => $pic,
                'pic2' => $pic2,
                'pic3' => $pic3,
                'progress' => $progress,
                'note' => $this->text($cell('note')),
                'link' => $this->linkFor($ws, $map, $r, $links),
            ];
        }

        return $rows;
    }

    /** Nilai mentah sel; rumus dihitung dulu (kalau gagal, pakai teks rumusnya). */
    private function cellValue(Worksheet $ws, int $col, int $row): mixed
    {
        $cell = $ws->getCell([$col, $row]);
        $value = $cell->getValue();

        if (is_string($value) && str_starts_with($value, '=')) {
            try {
                return $cell->getCalculatedValue();
            } catch (\Throwable) {
                return $value;
            }
        }

        return $value;
    }

    private function isSectionHeader(string $section, string $no, string $title, mixed $date, string $pic, string $progress): bool
    {
        if ($section !== '' && mb_strtoupper($section) === mb_strtoupper($title)) {
            return true;
        }

        if ($section !== '' || $no !== '' || $this->text($date) !== '' || $pic !== '' || $progress !== '') {
            return false;
        }

        $letters = preg_replace('/[^\p{L}]/u', '', $title) ?? '';

        return $letters !== '' && mb_strtoupper($letters) === $letters;
    }

    /** @return array<string, string> koordinat sel (mis. "I8") -> URL hyperlink. */
    private function hyperlinks(Worksheet $ws): array
    {
        $out = [];

        foreach ($ws->getHyperlinkCollection() as $coordinate => $hyperlink) {
            $url = trim((string) $hyperlink->getUrl());

            if ($url !== '') {
                $out[$coordinate] = $url;
            }
        }

        return $out;
    }

    /** @param  array<string, int>  $map @param  array<string, string>  $links */
    private function linkFor(Worksheet $ws, array $map, int $row, array $links): string
    {
        if (! isset($map['link'])) {
            return '';
        }

        $coordinate = Coordinate::stringFromColumnIndex($map['link']) . $row;

        if (isset($links[$coordinate])) {
            return $links[$coordinate];
        }

        $raw = $ws->getCell($coordinate)->getValue();

        if (is_string($raw) && preg_match('/^=HYPERLINK\(\s*"([^"]+)"/i', $raw, $m)) {
            return $m[1];
        }

        return $this->text($raw);
    }

    /** Nilai sel -> teks rapi (angka bulat tanpa ".0", baris baru diseragamkan). */
    private function text(mixed $value): string
    {
        if ($value === null || $value === false) {
            return '';
        }

        if (is_float($value) && floor($value) === $value && abs($value) < 1e12) {
            return (string) (int) $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y');
        }

        return trim(str_replace(["\r\n", "\r"], "\n", (string) $value));
    }

    /**
     * Sel tanggal -> 'Y-m-d', null kalau kosong, false kalau ada isinya tapi
     * tidak terbaca sebagai tanggal (mis. "Early / Mid April").
     */
    public static function parseDate(mixed $value): string|false|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            // Serial Excel di bawah 20000 (sebelum 1954) bukan tanggal kerja — biasanya angka biasa yang salah kolom.
            if ((float) $value < 20000) {
                return false;
            }

            try {
                $date = ExcelDate::excelToDateTimeObject((float) $value);
            } catch (\Throwable) {
                return false;
            }

            $year = (int) $date->format('Y');

            return $year >= 1900 && $year <= 2100 ? $date->format('Y-m-d') : false;
        }

        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        foreach (['d/m/Y', 'j/n/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $text);
            $errors = \DateTimeImmutable::getLastErrors();

            if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
                continue;
            }

            $year = (int) $date->format('Y');

            if ($year >= 1900 && $year <= 2100) {
                return $date->format('Y-m-d');
            }
        }

        return false;
    }
}