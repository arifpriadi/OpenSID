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
 * Hak Cipta 2016 - 2023 Perkumpulan Desa Digital Terbuka (https://opendesa.id)
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
 * @copyright Hak Cipta 2016 - 2023 Perkumpulan Desa Digital Terbuka (https://opendesa.id)
 * @license   http://www.gnu.org/licenses/gpl.html GPL V3
 * @link      https://github.com/OpenSID/OpenSID
 *
 */

defined('BASEPATH') || exit('No direct script access allowed');

$this->load->model('keluarga_model');
if ($_SESSION['id_ibu']) {
    $ibu                            = $this->get_data_surat($_SESSION['id_ibu']);
    $input['nik_ibu']               = $ibu['nik'];
    $input['nama_ibu']              = $ibu['nama'];
    $input['tempat_lahir_ibu']      = strtoupper($ibu['tempatlahir']);
    $input['tanggal_lahir_ibu']     = $ibu['tanggallahir'];
    $input['umur_ibu']              = str_pad($ibu['umur'], 3, ' ', STR_PAD_LEFT);
    $input['pekerjaanid_ibu']       = str_pad($ibu['pekerjaan_id'], 2, '0', STR_PAD_LEFT);
    $input['pekerjaanibu']          = $ibu['pekerjaan'];
    $input['alamat_ibu']            = trim($ibu['alamat'] . ' ' . $ibu['dusun']);
    $input['rt_ibu']                = $ibu['rt'];
    $input['rw_ibu']                = $ibu['rw'];
    $input['desaibu']               = $config['nama_desa'];
    $input['kecibu']                = $config['nama_kecamatan'];
    $input['kabibu']                = $config['nama_kabupaten'];
    $input['provinsiibu']           = $config['nama_propinsi'];
    $input['tanggalperkawinan_ibu'] = $ibu['tanggalperkawinan'];
    $input['kewarganegaraan_ibu']   = $ibu['warganegara'];

    $ayah = $this->get_data_suami($ibu['id']);
    if ($ayah) {
        $input['nik_ayah']             = $ayah['nik'];
        $input['nama_ayah']            = $ayah['nama'];
        $input['tempat_lahir_ayah']    = strtoupper($ayah['tempatlahir']);
        $input['tanggal_lahir_ayah']   = $ayah['tanggallahir'];
        $input['umur_ayah']            = str_pad($ayah['umur'], 3, ' ', STR_PAD_LEFT);
        $input['pekerjaanid_ayah']     = str_pad($ayah['pekerjaan_id'], 2, '0', STR_PAD_LEFT);
        $input['pekerjaanayah']        = $ayah['pek'];
        $input['alamat_ayah']          = trim($ayah['alamat'] . ' ' . $ayah['dusun']);
        $input['rt_ayah']              = $ayah['rt'];
        $input['rw_ayah']              = $ayah['rw'];
        $input['desaayah']             = $config['nama_desa'];
        $input['kecayah']              = $config['nama_kecamatan'];
        $input['kabayah']              = $config['nama_kabupaten'];
        $input['provinsiayah']         = $config['nama_propinsi'];
        $input['kewarganegaraan_ayah'] = $ayah['wn'];
    } else {
        $input['pekerjaanid_ayah'] = str_pad($input['pekerjaanid_ayah'], 2, '0', STR_PAD_LEFT);
        $input['umur_ayah']        = str_pad($input['umur_ayah'], 3, ' ', STR_PAD_LEFT);
    }
} else {
    $input['pekerjaanid_ibu']  = str_pad($input['pekerjaanid_ibu'], 2, '0', STR_PAD_LEFT);
    $input['umur_ibu']         = str_pad($input['umur_ibu'], 3, ' ', STR_PAD_LEFT);
    $input['pekerjaanid_ayah'] = str_pad($input['pekerjaanid_ayah'], 2, '0', STR_PAD_LEFT);
    $input['umur_ayah']        = str_pad($input['umur_ayah'], 3, ' ', STR_PAD_LEFT);
}
if ($_SESSION['id_bayi']) {
    $bayi               = $this->get_data_surat($_SESSION['id_bayi']);
    $input['nik_bayi']  = $bayi['nik'];
    $input['nama_bayi'] = $bayi['nama'];
    $input['sex']       = $bayi['sex_id'];
    $input['hari']      = hari(strtotime($bayi['tanggallahir']));
    $input['tanggal']   = $bayi['tanggallahir'];
}

// Tulis perubahan data kelahiran di form surat ke database
$kolom         = ['waktu_lahir', 'tempat_dilahirkan', 'tempatlahir', 'jenis_kelahiran', 'kelahiran_anak_ke', 'penolong_kelahiran', 'berat_lahir', 'panjang_lahir'];
$penduduk_baru = [];

if(count($penduduk_baru) > 0) {
    foreach ($kolom as $item) {
        if ($_POST[$item] != $bayi[$item]) {
            $penduduk_baru[$item] = $_POST[$item];
        }
    }
    $data['tanggallahir'] = tgl_indo_in($_POST['tanggallahir']);
    $this->db->where('id', $_SESSION['id_bayi'])->update('tweb_penduduk', $penduduk_baru);
}

// Jika ibu dari database, gunakan data ibu untuk info kepala keluarga.
// Kalau tidak, gunakan data yang lahir. Salah satu harus dari database.
if ($ibu) {
    $input['kepala_kk'] = $ibu['kepala_kk'];
    $input['no_kk']     = $ibu['no_kk'];
} elseif ($bayi) {
    $input['kepala_kk'] = $bayi['kepala_kk'];
    $input['no_kk']     = $bayi['no_kk'];
}
if ($_SESSION['id_pelapor']) {
    $pelapor                        = $this->get_data_surat($_SESSION['id_pelapor']);
    $input['nik_pelapor']           = $pelapor['nik'];
    $input['nama_pelapor']          = $pelapor['nama'];
    $input['tanggal_lahir_pelapor'] = $pelapor['tanggallahir'];
    $input['umur_pelapor']          = str_pad($pelapor['umur'], 3, ' ', STR_PAD_LEFT);
    $input['jkpelapor']             = $pelapor['sex_id'];
    $input['pekerjaanid_pelapor']   = str_pad($pelapor['pekerjaan_id'], 2, '0', STR_PAD_LEFT);
    $input['pekerjaanpelapor']      = $pelapor['pekerjaan'];
    $input['alamat_pelapor']        = trim($pelapor['alamat'] . ' ' . $pelapor['dusun']);
    $input['rt_pelapor']            = $pelapor['rt'];
    $input['rw_pelapor']            = $pelapor['rw'];
    $input['desapelapor']           = $config['nama_desa'];
    $input['kecpelapor']            = $config['nama_kecamatan'];
    $input['kabpelapor']            = $config['nama_kabupaten'];
    $input['provinsipelapor']       = $config['nama_propinsi'];

    // Tambahan
    $input['no_kk_pelapor']           = $pelapor['no_kk'];
    $input['kewarganegaraan_pelapor'] = $pelapor['warganegara'];
} else {
    $input['pekerjaanid_pelapor'] = str_pad($input['pekerjaanid_pelapor'], 2, '0', STR_PAD_LEFT);
    $input['umur_pelapor']        = str_pad($input['umur_pelapor'], 3, ' ', STR_PAD_LEFT);
}
if ($_SESSION['id_saksi1']) {
    $saksi1                        = $this->get_data_surat($_SESSION['id_saksi1']);
    $input['nik_saksi1']           = $saksi1['nik'];
    $input['nama_saksi1']          = $saksi1['nama'];
    $input['tanggal_lahir_saksi1'] = $saksi1['tanggallahir'];
    $input['umur_saksi1']          = str_pad($saksi1['umur'], 3, ' ', STR_PAD_LEFT);
    $input['jksaksi1']             = $saksi1['sex_id'];
    $input['pekerjaanid_saksi1']   = str_pad($saksi1['pekerjaan_id'], 2, '0', STR_PAD_LEFT);
    $input['pekerjaansaksi1']      = $saksi1['pekerjaan'];
    $input['alamat_saksi1']        = trim($saksi1['alamat'] . ' ' . $saksi1['dusun']);
    $input['rt_saksi1']            = $saksi1['rt'];
    $input['rw_saksi1']            = $saksi1['rw'];
    $input['desasaksi1']           = $config['nama_desa'];
    $input['kecsaksi1']            = $config['nama_kecamatan'];
    $input['kabsaksi1']            = $config['nama_kabupaten'];
    $input['provinsisaksi1']       = $config['nama_propinsi'];

    // Tambahan
    $input['no_kk_saksi1']           = $saksi1['no_kk'];
    $input['kewarganegaraan_saksi1'] = $saksi1['warganegara'];
} else {
    $input['pekerjaanid_saksi1'] = str_pad($input['pekerjaanid_saksi1'], 2, '0', STR_PAD_LEFT);
    $input['umur_saksi1']        = str_pad($input['umur_saksi1'], 3, ' ', STR_PAD_LEFT);
}
if ($_SESSION['id_saksi2']) {
    $saksi2                        = $this->get_data_surat($_SESSION['id_saksi2']);
    $input['nik_saksi2']           = $saksi2['nik'];
    $input['nama_saksi2']          = $saksi2['nama'];
    $input['tanggal_lahir_saksi2'] = $saksi2['tanggallahir'];
    $input['umur_saksi2']          = str_pad($saksi2['umur'], 3, ' ', STR_PAD_LEFT);
    $input['jksaksi2']             = $saksi2['sex_id'];
    $input['pekerjaanid_saksi2']   = str_pad($saksi2['pekerjaan_id'], 2, '0', STR_PAD_LEFT);
    $input['pekerjaansaksi2']      = $saksi2['pekerjaan'];
    $input['alamat_saksi2']        = trim($saksi2['alamat'] . ' ' . $saksi2['dusun']);
    $input['rt_saksi2']            = $saksi2['rt'];
    $input['rw_saksi2']            = $saksi2['rw'];
    $input['desasaksi2']           = $config['nama_desa'];
    $input['kecsaksi2']            = $config['nama_kecamatan'];
    $input['kabsaksi2']            = $config['nama_kabupaten'];
    $input['provinsisaksi2']       = $config['nama_propinsi'];

    // Tambahan
    $input['no_kk_saksi2']           = $saksi2['no_kk'];
    $input['kewarganegaraan_saksi2'] = $saksi2['warganegara'];
} else {
    $input['pekerjaanid_saksi2'] = str_pad($input['pekerjaanid_saksi2'], 2, '0', STR_PAD_LEFT);
    $input['umur_saksi2']        = str_pad($input['umur_saksi2'], 3, ' ', STR_PAD_LEFT);
}
