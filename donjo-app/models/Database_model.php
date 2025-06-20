<?php

/*
 *
 * File ini bagian dari:
 *
 * OpenSID
 *
 * Sistem informasi desa sumber terbuka untuk memajukan desa
 *
 * Aplikasi dan source code ini dirilis berdasarkan lisensi GPL V3
 *
 * Hak Cipta 2009 - 2015 Combine Resource Institution (http://lumbungkomunitas.net/)
 * Hak Cipta 2016 - 2024 Perkumpulan Desa Digital Terbuka (https://opendesa.id)
 *
 * Dengan ini diberikan izin, secara gratis, kepada siapa pun yang mendapatkan salinan
 * dari perangkat lunak ini dan file dokumentasi terkait ("Aplikasi Ini"), untuk diperlakukan
 * tanpa batasan, termasuk hak untuk menggunakan, menyalin, mengubah dan/atau mendistribusikan,
 * asal tunduk pada syarat berikut:
 *
 * Pemberitahuan hak cipta di atas dan pemberitahuan izin ini harus disertakan dalam
 * setiap salinan atau bagian penting Aplikasi Ini. Barang siapa yang menghapus atau menghilangkan
 * pemberitahuan ini melanggar ketentuan lisensi Aplikasi Ini.
 *
 * PERANGKAT LUNAK INI DISEDIAKAN "SEBAGAIMANA ADANYA", TANPA JAMINAN APA PUN, BAIK TERSURAT MAUPUN
 * TERSIRAT. PENULIS ATAU PEMEGANG HAK CIPTA SAMA SEKALI TIDAK BERTANGGUNG JAWAB ATAS KLAIM, KERUSAKAN ATAU
 * KEWAJIBAN APAPUN ATAS PENGGUNAAN ATAU LAINNYA TERKAIT APLIKASI INI.
 *
 * @package   OpenSID
 * @author    Tim Pengembang OpenDesa
 * @copyright Hak Cipta 2009 - 2015 Combine Resource Institution (http://lumbungkomunitas.net/)
 * @copyright Hak Cipta 2016 - 2024 Perkumpulan Desa Digital Terbuka (https://opendesa.id)
 * @license   http://www.gnu.org/licenses/gpl.html GPL V3
 * @link      https://github.com/OpenSID/OpenSID
 *
 */

use App\Models\Modul;
use App\Models\Migrasi;
use App\Models\SettingAplikasi;
use Illuminate\Support\Facades\DB;

defined('BASEPATH') || exit('No direct script access allowed');

class Database_model extends MY_Model
{
    private $engine           = 'InnoDB';
    private int $showProgress = 0;
    public string $minimumVersion;

    public function __construct()
    {
        parent::__construct();

        $this->load->dbutil();
        if (! $this->dbutil->database_exists($this->db->database)) {
            return;
        }

        $this->minimumVersion = MINIMUM_VERSI;
        $this->cek_engine_db();
        $this->load->dbforge();
    }

    private function cek_engine_db(): void
    {
        $db_debug           = $this->db->db_debug;
        $this->db->db_debug = false; //disable debugging for queries

        $query = $this->db->query("SELECT `engine` FROM INFORMATION_SCHEMA.TABLES WHERE table_schema= '" . $this->db->database . "' AND table_name = 'user'");
        $error = $this->db->error();
        if ($error['code'] != 0) {
            $this->engine = $query->row()->engine;
        }

        $this->db->db_debug = $db_debug; //restore setting
    }

    private function cekCurrentVersion()
    {
        $version = setting('current_version');
        if ($version == null) {
            // versi tidak terdeteksi dari modul periksa.
            return SettingAplikasi::where('key', 'current_version')->first()->value;
        }

        return $version;
    }

    public function migrasi_db_cri($install = false): void
    {
        $this->load->helper('directory');
        // Tunggu restore selesai sebelum migrasi
        if (isset($this->session->sedang_restore) && $this->session->sedang_restore == 1) {
            return;
        }

        $migratedDatabase = Migrasi::pluck('versi_database', 'versi_database')->toArray();

        session_success();
        $versi          = (int) str_replace('.', '', $this->cekCurrentVersion());
        $minimumVersi   = (int) str_replace('.', '', $this->minimumVersion);
        $currentVersion = currentVersion();

        if (! $install && $versi < $minimumVersi) {
            show_error('<h2>Silakan upgrade dulu ke OpenSID dengan minimal versi ' . $this->minimumVersion . '</h2>');
        }

        $migrations = directory_map('donjo-app/models/migrations', 1);
        // sort by name
        usort($migrations, static fn($a, $b): int => strcmp($a, $b));

        try {
            foreach ($migrations as $migrate) {
                // Migrasi_2023102701.php contoh nama file yang valid
                preg_match('/\d+/', $migrate, $matches);
                if ($matches) {
                    $migrateName = $matches[0];
                    if (! isset($migratedDatabase[$migrateName])) {
                        $this->jalankan_migrasi('Migrasi_' . $migrateName);
                        $migrasiDb = Migrasi::firstOrCreate(['versi_database' => $migrateName]);
                        $migrasiDb->update(['premium' => ['Migrasi_' . $migrateName]]);
                    }
                }
            }
            // untuk mencegah kesalahan nama file migrasi, tambahkan record berdasarkan VERSI_DATABASE saat ini
            $migrasiDb = Migrasi::firstOrCreate(['versi_database' => VERSI_DATABASE]);
            $migrasiDb->update(['premium' => ['Migrasi_' . VERSI_DATABASE]]);
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            if ($this->getShowProgress()) {
                echo json_encode(['message' => $e->getMessage(), 'status' => 0]);
            }
        }

        // Migrasi Surat Bawaan
        $this->jalankan_migrasi('migrasi_surat_bawaan');

        // Migrasi beta
        $this->jalankan_migrasi('migrasi_beta');

        // Migrasi revisi
        $this->jalankan_migrasi('migrasi_rev');

        // Migrasi umum
        $this->jalankan_migrasi('migrasi_umum');

        // Migrasi view
        $this->createView();

        // Migrasi Hapus Module
        Modul::whereIn('slug', ['layanan-pelanggan', 'pendaftaran-kerjasama'])->delete();

        cache()->flush();

        // Lengkapi folder desa
        folder_desa();
        kosongkanFolder(config_item('cache_blade'));

        // delete cache list path view blade
        cache()->forget('views_blade');

        SettingAplikasi::withoutGlobalScope(App\Scopes\ConfigIdScope::class)->where('key', '=', 'current_version')->update(['value' => $currentVersion]);
        SettingAplikasi::where(['key' => 'compatible_version_general'])->update(['value' => PREMIUM ? versiUmumSetara($currentVersion) : null]);
        $this->load->model('track_model');
        $this->track_model->kirim_data();

        log_message('notice', 'Versi database sudah terbaru');
        if ($this->getShowProgress()) {
            // sleep(1.5);
            echo json_encode(['message' => 'Versi database sudah terbaru', 'status' => 0]);
        }

        if (strlen($this->db->password) < 80) {
            updateConfigFile('password', encrypt($this->db->password));
        }

        set_session('success', 'Migrasi berhasil dilakukan');
    }

    // Cek apakah migrasi perlu dijalankan
    public function cek_migrasi($install = true): void
    {
        // Paksa menjalankan migrasi kalau belum
        // Migrasi direkam di tabel migrasi
        if (Migrasi::where('versi_database', '=', VERSI_DATABASE)->doesntExist()) {
            $this->migrasi_db_cri($install);
        }
    }

    public function get_views()
    {
        $db    = $this->db->database;
        $views = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'VIEW' AND TABLE_SCHEMA = '{$db}'");

        return array_column($views, 'TABLE_NAME');
    }

    /**
     * Get the value of showProgress
     */
    public function getShowProgress()
    {
        return $this->showProgress;
    }

    /**
     * Set the value of showProgress
     *
     * @param mixed $showProgress
     *
     * @return self
     */
    public function setShowProgress($showProgress)
    {
        $this->showProgress = $showProgress;

        return $this;
    }

    public function jalankan_migrasi($migrasi)
    {
        $this->load->model('migrations/' . $migrasi);
        if ($this->getShowProgress()) {
            // sleep(1.5);
            echo json_encode(['message' => 'Jalankan ' . $migrasi, 'status' => 0]);
        }

        try {
            $this->{$migrasi}->up();
            log_message('notice', 'Berhasil Jalankan ' . $migrasi);

            return true;
        } catch (Exception $e) {
            log_message('error', 'Gagal Jalankan ' . $migrasi . ' dengan error ' . $e->getMessage());
            if ($this->getShowProgress()) {
                // sleep(1.5);
                echo json_encode(['message' => 'Gagal Jalankan ' . $migrasi . ' dengan error ' . $e->getMessage(), 'status' => 500]);
            }
        }

        return false;
    }

    private function createView()
    {
        DB::statement('DROP VIEW IF EXISTS `dokumen_hidup`');
        DB::statement('CREATE VIEW `dokumen_hidup` AS select `dokumen`.`id` AS `id`,`dokumen`.`config_id` AS `config_id`,`dokumen`.`satuan` AS `satuan`,`dokumen`.`nama` AS `nama`,`dokumen`.`enabled` AS `enabled`,`dokumen`.`tgl_upload` AS `tgl_upload`,`dokumen`.`id_pend` AS `id_pend`,`dokumen`.`kategori` AS `kategori`,`dokumen`.`attr` AS `attr`,`dokumen`.`tipe` AS `tipe`,`dokumen`.`url` AS `url`,`dokumen`.`tahun` AS `tahun`,`dokumen`.`kategori_info_publik` AS `kategori_info_publik`,`dokumen`.`updated_at` AS `updated_at`,`dokumen`.`deleted` AS `deleted`,`dokumen`.`id_syarat` AS `id_syarat`,`dokumen`.`id_parent` AS `id_parent`,`dokumen`.`created_at` AS `created_at`,`dokumen`.`created_by` AS `created_by`,`dokumen`.`updated_by` AS `updated_by`,`dokumen`.`dok_warga` AS `dok_warga`,`dokumen`.`lokasi_arsip` AS `lokasi_arsip` from `dokumen` where `dokumen`.`deleted` <> 1');

        DB::statement('DROP VIEW IF EXISTS `keluarga_aktif`');
        DB::statement('CREATE VIEW `keluarga_aktif` AS select `k`.`id` AS `id`,`k`.`config_id` AS `config_id`,`k`.`no_kk` AS `no_kk`,`k`.`nik_kepala` AS `nik_kepala`,`k`.`tgl_daftar` AS `tgl_daftar`,`k`.`kelas_sosial` AS `kelas_sosial`,`k`.`tgl_cetak_kk` AS `tgl_cetak_kk`,`k`.`alamat` AS `alamat`,`k`.`id_cluster` AS `id_cluster`,`k`.`updated_at` AS `updated_at`,`k`.`updated_by` AS `updated_by` from (`tweb_keluarga` `k` left join `tweb_penduduk` `p` on(`k`.`nik_kepala` = `p`.`id`)) where `p`.`status_dasar` = 1');

        DB::statement('DROP VIEW IF EXISTS `master_inventaris`');
        DB::statement("CREATE VIEW `master_inventaris` AS select 'inventaris_asset' AS `asset`,`inventaris_asset`.`config_id` AS `config_id`,`inventaris_asset`.`id` AS `id`,`inventaris_asset`.`nama_barang` AS `nama_barang`,`inventaris_asset`.`kode_barang` AS `kode_barang`,'Baik' AS `kondisi`,`inventaris_asset`.`keterangan` AS `keterangan`,`inventaris_asset`.`asal` AS `asal`,`inventaris_asset`.`tahun_pengadaan` AS `tahun_pengadaan` from `inventaris_asset` where `inventaris_asset`.`visible` = 1 union all select 'inventaris_gedung' AS `asset`,`inventaris_gedung`.`config_id` AS `config_id`,`inventaris_gedung`.`id` AS `id`,`inventaris_gedung`.`nama_barang` AS `nama_barang`,`inventaris_gedung`.`kode_barang` AS `kode_barang`,`inventaris_gedung`.`kondisi_bangunan` AS `kondisi_bangunan`,`inventaris_gedung`.`keterangan` AS `keterangan`,`inventaris_gedung`.`asal` AS `asal`,year(`inventaris_gedung`.`tanggal_dokument`) AS `tahun_pengadaan` from `inventaris_gedung` where `inventaris_gedung`.`visible` = 1 union all select 'inventaris_jalan' AS `asset`,`inventaris_jalan`.`config_id` AS `config_id`,`inventaris_jalan`.`id` AS `id`,`inventaris_jalan`.`nama_barang` AS `nama_barang`,`inventaris_jalan`.`kode_barang` AS `kode_barang`,`inventaris_jalan`.`kondisi` AS `kondisi`,`inventaris_jalan`.`keterangan` AS `keterangan`,`inventaris_jalan`.`asal` AS `asal`,year(`inventaris_jalan`.`tanggal_dokument`) AS `tahun_pengadaan` from `inventaris_jalan` where `inventaris_jalan`.`visible` = 1 union all select 'inventaris_peralatan' AS `asset`,`inventaris_peralatan`.`config_id` AS `config_id`,`inventaris_peralatan`.`id` AS `id`,`inventaris_peralatan`.`nama_barang` AS `nama_barang`,`inventaris_peralatan`.`kode_barang` AS `kode_barang`,'Baik' AS `Baik`,`inventaris_peralatan`.`keterangan` AS `keterangan`,`inventaris_peralatan`.`asal` AS `asal`,`inventaris_peralatan`.`tahun_pengadaan` AS `tahun_pengadaan` from `inventaris_peralatan` where `inventaris_peralatan`.`visible` = 1");

        DB::statement('DROP VIEW IF EXISTS `penduduk_hidup`');
        DB::statement('CREATE VIEW `penduduk_hidup` AS select `tweb_penduduk`.`id` AS `id`,`tweb_penduduk`.`config_id` AS `config_id`,`tweb_penduduk`.`nama` AS `nama`,`tweb_penduduk`.`nik` AS `nik`,`tweb_penduduk`.`id_kk` AS `id_kk`,`tweb_penduduk`.`kk_level` AS `kk_level`,`tweb_penduduk`.`id_rtm` AS `id_rtm`,`tweb_penduduk`.`rtm_level` AS `rtm_level`,`tweb_penduduk`.`sex` AS `sex`,`tweb_penduduk`.`tempatlahir` AS `tempatlahir`,`tweb_penduduk`.`tanggallahir` AS `tanggallahir`,`tweb_penduduk`.`agama_id` AS `agama_id`,`tweb_penduduk`.`pendidikan_kk_id` AS `pendidikan_kk_id`,`tweb_penduduk`.`pendidikan_sedang_id` AS `pendidikan_sedang_id`,`tweb_penduduk`.`pekerjaan_id` AS `pekerjaan_id`,`tweb_penduduk`.`status_kawin` AS `status_kawin`,`tweb_penduduk`.`warganegara_id` AS `warganegara_id`,`tweb_penduduk`.`dokumen_pasport` AS `dokumen_pasport`,`tweb_penduduk`.`dokumen_kitas` AS `dokumen_kitas`,`tweb_penduduk`.`ayah_nik` AS `ayah_nik`,`tweb_penduduk`.`ibu_nik` AS `ibu_nik`,`tweb_penduduk`.`nama_ayah` AS `nama_ayah`,`tweb_penduduk`.`nama_ibu` AS `nama_ibu`,`tweb_penduduk`.`foto` AS `foto`,`tweb_penduduk`.`golongan_darah_id` AS `golongan_darah_id`,`tweb_penduduk`.`id_cluster` AS `id_cluster`,`tweb_penduduk`.`status` AS `status`,`tweb_penduduk`.`alamat_sebelumnya` AS `alamat_sebelumnya`,`tweb_penduduk`.`alamat_sekarang` AS `alamat_sekarang`,`tweb_penduduk`.`status_dasar` AS `status_dasar`,`tweb_penduduk`.`hamil` AS `hamil`,`tweb_penduduk`.`cacat_id` AS `cacat_id`,`tweb_penduduk`.`sakit_menahun_id` AS `sakit_menahun_id`,`tweb_penduduk`.`akta_lahir` AS `akta_lahir`,`tweb_penduduk`.`akta_perkawinan` AS `akta_perkawinan`,`tweb_penduduk`.`tanggalperkawinan` AS `tanggalperkawinan`,`tweb_penduduk`.`akta_perceraian` AS `akta_perceraian`,`tweb_penduduk`.`tanggalperceraian` AS `tanggalperceraian`,`tweb_penduduk`.`cara_kb_id` AS `cara_kb_id`,`tweb_penduduk`.`telepon` AS `telepon`,`tweb_penduduk`.`tanggal_akhir_paspor` AS `tanggal_akhir_paspor`,`tweb_penduduk`.`no_kk_sebelumnya` AS `no_kk_sebelumnya`,`tweb_penduduk`.`ktp_el` AS `ktp_el`,`tweb_penduduk`.`status_rekam` AS `status_rekam`,`tweb_penduduk`.`waktu_lahir` AS `waktu_lahir`,`tweb_penduduk`.`tempat_dilahirkan` AS `tempat_dilahirkan`,`tweb_penduduk`.`jenis_kelahiran` AS `jenis_kelahiran`,`tweb_penduduk`.`kelahiran_anak_ke` AS `kelahiran_anak_ke`,`tweb_penduduk`.`penolong_kelahiran` AS `penolong_kelahiran`,`tweb_penduduk`.`berat_lahir` AS `berat_lahir`,`tweb_penduduk`.`panjang_lahir` AS `panjang_lahir`,`tweb_penduduk`.`tag_id_card` AS `tag_id_card`,`tweb_penduduk`.`created_at` AS `created_at`,`tweb_penduduk`.`created_by` AS `created_by`,`tweb_penduduk`.`updated_at` AS `updated_at`,`tweb_penduduk`.`updated_by` AS `updated_by`,`tweb_penduduk`.`id_asuransi` AS `id_asuransi`,`tweb_penduduk`.`no_asuransi` AS `no_asuransi`,`tweb_penduduk`.`email` AS `email`,`tweb_penduduk`.`email_token` AS `email_token`,`tweb_penduduk`.`email_tgl_kadaluarsa` AS `email_tgl_kadaluarsa`,`tweb_penduduk`.`email_tgl_verifikasi` AS `email_tgl_verifikasi`,`tweb_penduduk`.`telegram` AS `telegram`,`tweb_penduduk`.`telegram_token` AS `telegram_token`,`tweb_penduduk`.`telegram_tgl_kadaluarsa` AS `telegram_tgl_kadaluarsa`,`tweb_penduduk`.`telegram_tgl_verifikasi` AS `telegram_tgl_verifikasi`,`tweb_penduduk`.`bahasa_id` AS `bahasa_id`,`tweb_penduduk`.`ket` AS `ket`,`tweb_penduduk`.`negara_asal` AS `negara_asal`,`tweb_penduduk`.`tempat_cetak_ktp` AS `tempat_cetak_ktp`,`tweb_penduduk`.`tanggal_cetak_ktp` AS `tanggal_cetak_ktp`,`tweb_penduduk`.`suku` AS `suku`,`tweb_penduduk`.`bpjs_ketenagakerjaan` AS `bpjs_ketenagakerjaan`,`tweb_penduduk`.`hubung_warga` AS `hubung_warga` from `tweb_penduduk` where `tweb_penduduk`.`status_dasar` = 1');

        DB::statement('DROP VIEW IF EXISTS `rekap_mutasi_inventaris`');
        DB::statement("CREATE VIEW `rekap_mutasi_inventaris` AS select 'inventaris_asset' AS `asset`,`mutasi_inventaris_asset`.`config_id` AS `config_id`,`mutasi_inventaris_asset`.`id_inventaris_asset` AS `id_inventaris_asset`,`mutasi_inventaris_asset`.`status_mutasi` AS `status_mutasi`,`mutasi_inventaris_asset`.`jenis_mutasi` AS `jenis_mutasi`,`mutasi_inventaris_asset`.`tahun_mutasi` AS `tahun_mutasi`,`mutasi_inventaris_asset`.`keterangan` AS `keterangan` from `mutasi_inventaris_asset` where `mutasi_inventaris_asset`.`visible` = 1 union all select 'inventaris_gedung' AS `inventaris_gedung`,`mutasi_inventaris_gedung`.`config_id` AS `config_id`,`mutasi_inventaris_gedung`.`id_inventaris_gedung` AS `id_inventaris_gedung`,`mutasi_inventaris_gedung`.`status_mutasi` AS `status_mutasi`,`mutasi_inventaris_gedung`.`jenis_mutasi` AS `jenis_mutasi`,`mutasi_inventaris_gedung`.`tahun_mutasi` AS `tahun_mutasi`,`mutasi_inventaris_gedung`.`keterangan` AS `keterangan` from `mutasi_inventaris_gedung` where `mutasi_inventaris_gedung`.`visible` = 1 union all select 'inventaris_jalan' AS `inventaris_jalan`,`mutasi_inventaris_jalan`.`config_id` AS `config_id`,`mutasi_inventaris_jalan`.`id_inventaris_jalan` AS `id_inventaris_jalan`,`mutasi_inventaris_jalan`.`status_mutasi` AS `status_mutasi`,`mutasi_inventaris_jalan`.`jenis_mutasi` AS `jenis_mutasi`,`mutasi_inventaris_jalan`.`tahun_mutasi` AS `tahun_mutasi`,`mutasi_inventaris_jalan`.`keterangan` AS `keterangan` from `mutasi_inventaris_jalan` where `mutasi_inventaris_jalan`.`visible` = 1 union all select 'inventaris_peralatan' AS `inventaris_peralatan`,`mutasi_inventaris_peralatan`.`config_id` AS `config_id`,`mutasi_inventaris_peralatan`.`id_inventaris_peralatan` AS `id_inventaris_peralatan`,`mutasi_inventaris_peralatan`.`status_mutasi` AS `status_mutasi`,`mutasi_inventaris_peralatan`.`jenis_mutasi` AS `jenis_mutasi`,`mutasi_inventaris_peralatan`.`tahun_mutasi` AS `tahun_mutasi`,`mutasi_inventaris_peralatan`.`keterangan` AS `keterangan` from `mutasi_inventaris_peralatan` where `mutasi_inventaris_peralatan`.`visible` = 1");
    }
}
