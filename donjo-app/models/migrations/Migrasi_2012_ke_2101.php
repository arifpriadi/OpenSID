<?php

/**
 * File ini:
 *
 * Model untuk migrasi database
 *
 * donjo-app/models/migrations/Migrasi_2012_ke_2101.php
 *
 */

/**
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
 * Hak Cipta 2016 - 2020 Perkumpulan Desa Digital Terbuka (https://opendesa.id)
 *
 * Dengan ini diberikan izin, secara gratis, kepada siapa pun yang mendapatkan salinan
 * dari perangkat lunak ini dan file dokumentasi terkait ("Aplikasi Ini"), untuk diperlakukan
 * tanpa batasan, termasuk hak untuk menggunakan, menyalin, mengubah dan/atau mendistribusikan,
 * asal tunduk pada syarat berikut:

 * Pemberitahuan hak cipta di atas dan pemberitahuan izin ini harus disertakan dalam
 * setiap salinan atau bagian penting Aplikasi Ini. Barang siapa yang menghapus atau menghilangkan
 * pemberitahuan ini melanggar ketentuan lisensi Aplikasi Ini.

 * PERANGKAT LUNAK INI DISEDIAKAN "SEBAGAIMANA ADANYA", TANPA JAMINAN APA PUN, BAIK TERSURAT MAUPUN
 * TERSIRAT. PENULIS ATAU PEMEGANG HAK CIPTA SAMA SEKALI TIDAK BERTANGGUNG JAWAB ATAS KLAIM, KERUSAKAN ATAU
 * KEWAJIBAN APAPUN ATAS PENGGUNAAN ATAU LAINNYA TERKAIT APLIKASI INI.
 *
 * @package	OpenSID
 * @author	Tim Pengembang OpenDesa
 * @copyright	Hak Cipta 2009 - 2015 Combine Resource Institution (http://lumbungkomunitas.net/)
 * @copyright	Hak Cipta 2016 - 2020 Perkumpulan Desa Digital Terbuka (https://opendesa.id)
 * @license	http://www.gnu.org/licenses/gpl.html	GPL V3
 * @link 	https://github.com/OpenSID/OpenSID
 */

class Migrasi_2012_ke_2101 extends MY_model {

	public function up()
	{
		$this->tambah_kolom_urut();
		
		$hasil = true;

		// Tambah menu Layanan Mandiri > Pengaturan
		$query = "
			INSERT INTO setting_modul (`id`, `modul`, `url`, `aktif`, `ikon`, `urut`, `level`, `parent`, `hidden`, `ikon_kecil`) VALUES
			('314', 'Pengaturan', 'setting/mandiri', '1', 'fa-gear', '6', '2', '14', '0', 'fa-gear')
			ON DUPLICATE KEY UPDATE modul = VALUES(modul), url = VALUES(url), level = VALUES(level), parent = VALUES(parent), hidden = VALUES(hidden);
		";
		$this->db->query($query);

		// Tambahkan key layanan_mandiri
		$hasil =& $this->db->query("INSERT INTO setting_aplikasi (`key`, value, keterangan, jenis, kategori) VALUES ('layanan_mandiri', '1', 'Apakah layanan mandiri ditampilkan atau tidak', 'boolean', 'setting_mandiri')
			ON DUPLICATE KEY UPDATE value = VALUES(value), keterangan = VALUES(keterangan), jenis = VALUES(jenis), kategori = VALUES(kategori)");

		// Ubah isi field pd tabel kelompok jd unik, kode = kode_id
		$hasil =& $this->db->query("UPDATE kelompok SET kode=CONCAT_WS('_', kode, id) WHERE id IS NOT NULL");
		// Field unik pd tabel kelompok
		$hasil =& $this->tambah_indeks('kelompok', 'kode');

		status_sukses($hasil);
	}

	private function tambah_kolom_urut()
	{
		//tambah kolom urut di tabel tweb_wil_clusterdesa
		if (!$this->db->field_exists('urut', 'tweb_wil_clusterdesa'))
			$this->dbforge->add_column('tweb_wil_clusterdesa', array(
				'urut' => array(
				'type' => 'INT',
				'constraint' => 11,
				'null' => TRUE,
				),
			));
	}

}
