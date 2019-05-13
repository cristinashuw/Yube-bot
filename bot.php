<?php
$TOKEN="insertYourAPItoken";
function request_url($method)
{
	global $TOKEN;
	return "https://api.telegram.org/bot" . $TOKEN . "/". $method;
}
function get_updates($offset) 
{
	$url = request_url("getUpdates")."?offset=".$offset;
        $resp = file_get_contents($url);
        $result = json_decode($resp, true);
        if ($result["ok"]==1)
            return $result["result"];
        return array();
}
function send_reply($chatid, $msgid, $text)
{
    $data = array(
        'chat_id' => $chatid,
        'text'  => $text,
    );
    // use key 'http' even if you send the request to https://...
    $options = array(
    	'http' => array(
        	'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        	'method'  => 'POST',
        	'content' => http_build_query($data),
    	),
    );
    $context  = stream_context_create($options);
    $result = file_get_contents(request_url('sendMessage'), false, $context);
    print_r($result);
}
function create_response($text,$chatid)
{
	$db_username   = 'root';
	$db_password   = '';
	$db_name    = 'bot';
	$db_host    = 'localhost';
	$koneksi = new mysqli($db_host, $db_username, $db_password, $db_name);
	if ($koneksi->connect_error)
	{
   		$tes1 = die('Error : ('. $koneksi->connect_errno .') '. $koneksi->connect_error);
	}
	else
	{
		$q_regis = $koneksi->query("select * from register where chatid = '$chatid'");
		$jumregis = $q_regis->num_rows;
		if ($text == "/start")
		{
			/*$q_regis = $koneksi->query("insert into register (chatid, ket) values ('$chatid', '1')");*/
			if ($jumregis == 0)
			{
				$tes1 = "🎉🎉 Selamat datang 🎉🎉
Perkenalkan nama saya UVERSBOT. 
Just call me Yube 😉

Dengan adanya aku, Anda bisa melihat data-datamu tanpa membuka portal lohh 👏👏👏. Data2 yang ada dimulai dari:
- Data pribadi
- KRS
- Tagihan Keuangan
- Jadwal ujian
- KHS (nilai)
- Total SKS
- IPK
- Pengumuman 
selain itu, Yube juga bisa bantu kamu untuk ganti password di portal 🤩🥳\n \n
Silakan masukkan NIM anda untuk melakukan registrasi.";
			}
			else
			{
				$regis = $q_regis->fetch_assoc();
				$nim = $regis['nim'];
				$q_mhs = $koneksi->query("select nama from tbl_mhs where nim = '$nim'");
				$mhs = $q_mhs->fetch_assoc();
				$nama = $mhs['nama'];
				$tes1 = "🎉 Selamat datang kembali, $nama 🎉
Terima kasih telah menggunakan UVERSBOT.

Ketik 'hi Yube' untuk informasi selanjutnya.  ";
			}
		}
		else
		{
			if ($jumregis == 0)
			{
				$q_cek = $koneksi->query("select nim, nama, prodi, jk from tbl_mhs where nim = '$text'");
				$jumcek = $q_cek->num_rows;
				$q_onproses = $koneksi->query("select * from onproses where chatid = '$chatid' limit 1");
				$jumonproses = $q_onproses->num_rows;
				if ($jumonproses >= 1)
				{
					if ($text == "ya" || $text == "Ya" || $text == "Iya" || $text == "iya" || $text == "Yes")
					{
						$koneksi->query("insert into register (chatid, nim) values ('$chatid', (select nim from onproses where chatid = '$chatid'))");
						$q_cnama = $koneksi->query("select nama from tbl_mhs where nim = (select nim from onproses where chatid = '$chatid')");
						$cnama = $q_cnama->fetch_assoc();
						$nama = $cnama['nama'];
						$koneksi->query("delete from onproses where chatid = '$chatid'");
						$tes1 = "🎉🎉Selamat datang $nama 🎉🎉 
Anda telah berhasil melakukan register. \n \nAda yang bisa saya bantu?
Ketik 'hi Yube' untuk informasi selanjutnya.";
					}
					else if ($text == "batal" || $text == "Batal" || $text == "cancel" || $text == "Cancel" || $text == "No" || $text == "no")
					{
						$koneksi->query("delete from onproses where chatid = '$chatid'");
						$tes1 = '.Ok.';
					}
					else
					{
						$tes1 = "Ketik 'ya' untuk melanjutkan registrasi
Ketik 'batal' untuk membatalkan registrasi";
					}
				}
				else if ($jumcek == 0)
				{
					$tes1 = "Silakan masukkan NIM Anda dengan benar untuk melakukan registrasi! 😊";
				}
				else
				{
					$q_validasi = $koneksi->query("select nim from register where nim = '$text'");
					$jumvalid = $q_validasi->num_rows;
					if ($jumvalid == 1)
					{
						$tes1 = $text." 😅
NIM ini sudah pernah registrasi. Silakan masukkan NIM lain.";
					}
					else
					{
						if ($jumonproses == 0)
						{
							$koneksi->query("insert into onproses (chatid, tanggal, ket, nim) values ('$chatid', now(), '1', '$text')");
							$cek = $q_cek->fetch_assoc();
							$vnim = $cek['nim'];
							$vnama = $cek['nama'];
							$vprodi = $cek['prodi'];
							$vjk = $cek['jk'];
							$tes1 = "nama: $vnama
NIM: $vnim
prodi: $vprodi
JK: $vjk

ketik 'ya' jika data sudah benar untuk proses registrasi
ketik 'batal' untuk membatalkan registrasi";
						}
						/*$koneksi->query("insert into register (chatid, nim) values ('$chatid', '$text')");
						$cek = $q_cek->fetch_assoc();
						$nama = $cek['nama'];
						$tes1 = "Selamat datang $nama anda telah berhasil melakukan register";*/
					}
				}
			}
			else
			{
				$regis = $q_regis->fetch_assoc();
				$nim = $regis['nim'];
				$q_bot = $koneksi->query("SELECT * FROM bot WHERE bot.`key` = '$text' LIMIT 1");
				$jumbot = $q_bot->num_rows;
				if ($regis['ket'] == 2)
				{
					$bot = $q_bot->fetch_assoc();
					if ($bot['action'] == "stop")
					{
						$q_ingat = $koneksi->query("update register set ket = '' where chatid = '$chatid'");
						$tes1 = $bot['jawab'];
					}
					else 
					{
						$q_utsuas = $koneksi->query("select utsuas from tbl_periode where status = 'aktif'");
						$utsuas = $q_utsuas->fetch_assoc();
						if ($utsuas['utsuas'] == 0)
						{
							$q_nilai = $koneksi->query("SELECT tbl_rombel.n_akhir2, tbl_kelas.kd_mk, tbl_mk.nm_mk, tbl_mk.sks
							FROM tbl_rombel
							INNER JOIN tbl_mhs ON tbl_rombel.nim=tbl_mhs.nim
							INNER JOIN tbl_kelas ON tbl_rombel.kd_kelas=tbl_kelas.kode
							INNER JOIN tbl_mk ON tbl_kelas.kd_mk=tbl_mk.kode_mk
							WHERE tbl_rombel.nim = $nim AND tbl_kelas.periode = (SELECT kode FROM tbl_periode WHERE nm_periode = '$text')
							ORDER BY tbl_kelas.kd_mk");
						}
						else
						{
							$q_nilai = $koneksi->query("SELECT tbl_rombel.feedback1, tbl_rombel.feedback2, tbl_rombel.n_akhir2, tbl_kelas.kd_mk, tbl_mk.nm_mk, tbl_mk.sks
    						FROM tbl_rombel
    						INNER JOIN tbl_mhs ON tbl_rombel.nim=tbl_mhs.nim
    						INNER JOIN tbl_kelas ON tbl_rombel.kd_kelas=tbl_kelas.kode
    						INNER JOIN tbl_mk ON tbl_kelas.kd_mk=tbl_mk.kode_mk
   							WHERE tbl_rombel.nim = $nim AND tbl_kelas.periode = (SELECT kode FROM tbl_periode WHERE nm_periode = '$text') ORDER BY tbl_kelas.kd_mk");
						}
						$jumnilai = $q_nilai->num_rows;
						if ($jumnilai == 0)
						{
							$tes1 = "Tidak ditemukan nilai tersebut. 
Mungkin anda belum mengambil semester $text, atau format anda salah. 

Ketik dengan format:
(gasal/genap/sp)<spasi>tahun ajaran
contoh:
- gasal 2015/2016
- SP 2016/2017

ATAU ketik stop untuk membatalkan";
						}
						else if ($utsuas['utsuas'] == 0)
						{
							$hasilnilai = "";
							$no = 1;
							$i = "";
							while ($nilai = $q_nilai->fetch_assoc())
							{
								$hasilnilai .= $no.". ".$nilai['kd_mk']." ".$nilai['nm_mk']." nilai: ".$nilai['n_akhir2']." \n";
								$no++;
							}
							$convert = ucwords($text);
							$koneksi->query("update register set ket = '' where chatid = '$chatid'");
							$tes1 = "Nilai Anda pada semester ".$convert." :\n".$hasilnilai;
						}
						else
						{
							$hasilnilai = "";
							$no = 1;
							$i = "";
							while ($nilai = $q_nilai->fetch_assoc())
							{
								if ($nilai['feedback1'] == 0 || $nilai['feedback2'] == 0)
								{
									$ketnilai = "sensor";
									$i = 1;
								}
								else
								{
									$ketnilai = $nilai['n_akhir2'];
								}
								$hasilnilai .= $no.". ".$nilai['kd_mk']." ".$nilai['nm_mk']." nilai: $ketnilai \n";
								$no++;
							}
							if ($i == 1)
							{
								$sensor = "sensor: 
Silakan isi kuesioner dulu di uvers.ac.id/portal untuk melihat nilai. 

Terimakasih 🥂";
							}
							else
							{
								$sensor ="";
							}
							$convert = ucwords($text);
							$koneksi->query("update register set ket = '' where chatid = '$chatid'");
							$tes1 = "Nilai Anda pada semester ".$convert." 😉 \n \n".$hasilnilai." \n".$sensor;
						}
					}
				}
				else if ($regis['ket'] == 4)
				{
					$bot = $q_bot->fetch_assoc();
					if ($bot['action'] == "stop")
					{
						$q_ingat = $koneksi->query("update register set ket = '' where chatid = '$chatid'");
						$tes1 = $bot['jawab'];
					}
					else
					{
						if ($text == "semua" || $text == "Semua")
						{
							$q_jadwal = $koneksi->query("SELECT tbl_kelas.ruang, tbl_kelas.kode, tbl_kelas.nm_kls, tbl_kelas.kd_mk, tbl_mk.nm_mk, tbl_mk.sks, tbl_kelas.hari, tbl_kelas.jam
							FROM tbl_rombel 
							INNER JOIN tbl_kelas ON tbl_rombel.kd_kelas = tbl_kelas.kode
							INNER JOIN tbl_mk ON tbl_kelas.kd_mk = tbl_mk.kode_mk
							WHERE tbl_kelas.periode=(SELECT kode FROM tbl_periode WHERE STATUS='aktif') AND tbl_rombel.nim = '$nim'
							ORDER BY kd_mk");
						}
						else
						{
							$q_jadwal = $koneksi->query("SELECT tbl_kelas.ruang, tbl_kelas.kode, tbl_kelas.nm_kls, tbl_kelas.kd_mk, tbl_mk.nm_mk, tbl_mk.sks, tbl_kelas.hari, tbl_kelas.jam
							FROM tbl_rombel 
							INNER JOIN tbl_kelas ON tbl_rombel.kd_kelas = tbl_kelas.kode
							INNER JOIN tbl_mk ON tbl_kelas.kd_mk = tbl_mk.kode_mk
							WHERE tbl_kelas.periode=(SELECT kode FROM tbl_periode WHERE STATUS='aktif') AND tbl_rombel.nim = '$nim' AND tbl_kelas.hari = '$text'
							ORDER BY kd_mk");
						}
						$jumjadwal = $q_jadwal->num_rows;
						if ($jumjadwal == 0)
						{
							$koneksi->query("update register set ket = '' where chatid = '$chatid'");
							$tes1 = "Tidak ditemukan jadwal.";
						}
						else
						{
							$hasil = "";
							$no = 1;
							while ($krs = $q_jadwal->fetch_assoc())
							{
								$hasil .= $no.". ".$krs['kd_mk']." ".$krs['nm_mk']."
    SKS : ".$krs['sks']."
    kelas : ".$krs['nm_kls']."
    hari : ".$krs['hari']."
    jam : ".$krs['jam']."
    ruang : ".$krs['ruang']."\n";
								$no++;
							}
							$koneksi->query("update register set ket = '' where chatid = '$chatid'");
							$tes1 = "Ini jadwalnya: \n ".$hasil;
						}
					}
				}
				else if ($regis['ket'] == 9)
				{
					$bot = $q_bot->fetch_assoc();
					if ($bot['action'] == "stop")
					{
						$koneksi->query("update register set ket = '' where chatid = '$chatid'");
						$tes1 = $bot['jawab'];
					}
					else
					{
						$password = md5($text);
						$q_pass = $koneksi->query("select * from tbl_mhs where sandi = '$password' and nim ='$nim'");
						$jumpass = $q_pass->num_rows;
						if ($jumpass == 1)
						{
							$tes1 = "Silakan masukkan password baru \n \nKetik stop untuk membatalkan";
							$koneksi->query("update register set ket = '10' where chatid = '$chatid'");
						}
						else
						{
							$tes1 = "Maaf password Anda salah ☹";
							$koneksi->query("update register set ket = '' where chatid = '$chatid'");
						}
					}
				}
				else if ($regis['ket'] == 10)
				{
					$bot = $q_bot->fetch_assoc();
					if ($bot['action'] == "stop")
					{
						$koneksi->query("update register set ket = '' where chatid = '$chatid'");
						$tes1 = $bot['jawab'];
					}
					else
					{
						$password = md5($text);
						$koneksi->query("update tbl_mhs set sandi = '$password' where nim ='$nim'");
						$koneksi->query("update register set ket = '' where chatid = '$chatid'");
						$tes1 = "Password berhasil di ubah. Silakan login di uvers.ac.id/portal";
					}
				}
				else if ($regis['ket'] == 7)
				{
					$bot = $q_bot->fetch_assoc();
					if ($bot['action'] == "stop")
					{
						$q_ingat = $koneksi->query("update register set ket = '' where chatid = '$chatid'");
						$tes1 = $bot['jawab'];
					}
					else
					{
						if (is_numeric($text))
						{
							if ($text <= 15)
							{
								$cari = $text-1;
								$kd_prodi = substr($nim,4,3);
								$q_cari = $koneksi->query("select konten from tbl_pengumuman where prodi like '%$kd_prodi%' order by tgl DESC, id DESC limit $cari,1");
								$jumcari = $q_cari->num_rows;
								if ($jumcari == 0)
								{
									$tes1 = "Data tidak ditemukan";
									$koneksi->query("update register set ket = '' where chatid = '$chatid'");
								}
								else
								{
									$konten = $q_cari->fetch_assoc();
									$koneksi->query("update register set ket = '' where chatid = '$chatid'");
									$tes1 = "Pengumuman:\n".$konten['konten'];
								}
							}
							else
							{
								$tes1 = "Data tidak ditemukan";
								$koneksi->query("update register set ket = '' where chatid = '$chatid'");
							}
						}
						else
						{
							$tes1 = "Data tidak ditemukan";
							$koneksi->query("update register set ket = '' where chatid = '$chatid'");
						}
					}
				}
				else if ($jumbot != 0)
				{
					$bot = $q_bot->fetch_assoc();
					if ($bot['action'] == "love")
					{
						$tes1 = $bot['jawab']." ❤️";
					}
					else if ($bot['action'] == "bantu")
					{
						$tes1 = "Melalui saya (Yube 😜), kamu bisa:

- melihat Data pribadi
- cek KRS
- cek Tagihan Keuangan
- melihat Jadwal ujian
- melihat KHS (nilai)
- melihat Total SKS
- melihat IPK
- membaca Pengumuman
- Ganti password

~~~~hebat kan akuh? 🙄~~~~";
					}
					else if ($bot['action'] == "no")
					{
						$tes1 = $bot['jawab'];
					}
					else if ($bot['action'] == "stop")
					{
						$koneksi->query("update register set ket = '' where chatid = '$chatid'");
						$tes1 = $bot['jawab'];
					}
					else if ($bot['action'] == "krs")
					{
						$q_periode = $koneksi->query("select nm_periode from tbl_periode where tbl_periode.status = 'aktif'");
						$periode = $q_periode->fetch_assoc();
						$q_krs = $koneksi->query("SELECT tbl_kelas.ruang, tbl_kelas.kode, tbl_kelas.nm_kls, tbl_kelas.kd_mk, tbl_mk.nm_mk, tbl_mk.sks, tbl_kelas.hari, tbl_kelas.jam
						FROM tbl_rombel 
						INNER JOIN tbl_kelas ON tbl_rombel.kd_kelas = tbl_kelas.kode
						INNER JOIN tbl_mk ON tbl_kelas.kd_mk = tbl_mk.kode_mk
						WHERE tbl_kelas.periode=(SELECT kode FROM tbl_periode WHERE STATUS='aktif') AND tbl_rombel.nim = '$nim'
						ORDER BY kd_mk");
						$hasil = "";
						$no = 1;
						while ($krs = $q_krs->fetch_assoc())
						{
							if($krs['hari'] == NULL)
							{
								$hari = "-";
							}
							else
							{
								$hari = $krs['hari'];
							}
							if($krs['jam'] == NULL)
							{
								$jam = "-";
							}
							else
							{
								$jam = $krs['jam'];
							}
							if($krs['ruang'] == NULL)
							{
								$ruang = "-";
							}
							else
							{
								$ruang = $krs['ruang'];
							}
							
							$hasil .= $no.". ".$krs['kd_mk']." ".$krs['nm_mk']."
    SKS  : ".$krs['sks']."
    kelas  : ".$krs['nm_kls']."
    hari  : $hari
    jam  : $jam
    ruang  : $ruang\n \n";
							$no++;
						}
						$tes1 = $bot['jawab']." ".$periode['nm_periode'].": \n".$hasil;
					}
					else if ($bot['action'] == "nilai")
					{
						$tes1 = $bot['jawab']."💯

Ketik dengan format:
(gasal/genap/sp)<spasi>tahun ajaran.
Contoh:
- gasal 2015/2016
- SP 2017/2018

ATAU ketik 'stop' untuk membatalkan";
						$ket = $bot['gol'];
						$koneksi->query("update register set ket = '$ket' where chatid = '$chatid'");
					}
					else if ($bot['action'] == "jadwal")
					{
						$tes1 = $bot['jawab']."
Ketik nama hari atau ketik semua untuk melihat jadwal";
						$ket = $bot['gol'];
						$koneksi->query("update register set ket = '$ket' where chatid = '$chatid'");
					}
					else if ($bot['action'] == "pass")
					{
						$ket = $bot['gol'];
						$koneksi->query("update register set ket = '$ket' where chatid = '$chatid'");
						$tes1 = $bot['jawab'];
					}
					else if ($bot['action'] == "data")
					{
						$q_data = $koneksi->query("select nim, ktp, nama, tmpt_lahir, tgl_lahir, jk, agama from tbl_mhs where nim = '$nim'");
						$data = $q_data->fetch_assoc();
						if ($data['jk'] == "P")
						{
							$jk = "Perempuan";
						}
						else
						{
							$jk = "Laki-laki";
						}
						$array_bulan = array(1=>'Januari','Februari','Maret', 'April', 'Mei', 'Juni','Juli','Agustus','September','Oktober', 'November','Desember');
						$date = date("d-n-Y",strtotime($data['tgl_lahir'])); $tgl = explode("-",$date);
						$tes1 = $bot['jawab']." 📂

NIM : $nim
No. KTP : ".$data['ktp']."
Nama : ".$data['nama']."
Tempat Lahir : ".$data['tmpt_lahir']."
Tanggal Lahir : ".$tgl[0]." ".$array_bulan[$tgl[1]]." ".$tgl[2]."
Jenis Kelamin: $jk
Agama : ".$data['agama'];

					}
					else if ($bot['action'] == "info")
					{
						$kd_prodi = substr($nim,4,3);
						$q_info = $koneksi->query("select id, tgl, judul from tbl_pengumuman where prodi like '%$kd_prodi%' order by tgl DESC, id DESC limit 15");
						$juminfo = $q_info->num_rows;
						if ($juminfo == 0)
						{
							$tes1 = "Belum ada pengumuman";
						}
						else
						{
							$hasil = "";
							$i = 1;
							while ($info = $q_info->fetch_assoc())
							{
								$hasil .= "$i. ".$info['judul']."\n";
							}
							$ket = $bot['gol'];
							$koneksi->query("update register set ket = '$ket' where chatid = '$chatid'");
							$tes1 = $bot['jawab']."\n$hasil";
						}	
					}
					else if ($bot['action'] == "ujian")
					{
						$q_utsuas = $koneksi->query("select utsuas from tbl_periode where status = 'aktif'");
						$utsuas = $q_utsuas->fetch_assoc();
						if ($utsuas['utsuas'] == 0)
						{
							$q_ujian = $koneksi->query("SELECT tbl_kelas.kd_mk, tbl_mk.nm_mk, tbl_ujian.tgl, tbl_ujian.waktu, tbl_ujian.ruang
							FROM tbl_rombel
							LEFT JOIN tbl_ujian ON tbl_ujian.no = tbl_rombel.set_uts
							INNER JOIN tbl_kelas ON tbl_kelas.kode = tbl_rombel.kd_kelas
							INNER JOIN tbl_mk ON tbl_mk.kode_mk = tbl_kelas.kd_mk
							WHERE tbl_rombel.nim='$nim' AND tbl_kelas.periode=(select kode from tbl_periode where tbl_periode.status = 'aktif')
							ORDER BY tgl ASC, waktu ASC");
						}
						else
						{
							$q_ujian = $koneksi->query("SELECT tbl_kelas.kd_mk, tbl_mk.nm_mk, tbl_ujian.tgl, tbl_ujian.waktu, tbl_ujian.ruang
							FROM tbl_rombel
							LEFT JOIN tbl_ujian ON tbl_ujian.no = tbl_rombel.set_uas
							INNER JOIN tbl_kelas ON tbl_kelas.kode = tbl_rombel.kd_kelas
							INNER JOIN tbl_mk ON tbl_mk.kode_mk = tbl_kelas.kd_mk
							WHERE tbl_rombel.nim='$nim' AND tbl_kelas.periode=(select kode from tbl_periode where tbl_periode.status = 'aktif')
							ORDER BY tgl ASC, waktu ASC");
						}
						$hasil = "";
						$i = 1;
						$j = 1;
						while ($ujian = $q_ujian->fetch_assoc())
						{
							if ($ujian['tgl'] == NULL && $ujian['waktu'] == NULL && $ujian['ruang'] == NULL)
							{
								$j++;
							}
							else
							{
								$hasil .= "								
$i. ".$ujian['kd_mk']." ".$ujian['nm_mk']. " 
    Tanggal: ".$ujian['tgl']." 
    Waktu: ".$ujian['waktu']." 
    Ruang: ".$ujian['ruang']."


💯💪🥂\n";
								$i++;
							}
						}
						if ($i == 1)
						{
							$tes1 = "jadwal ujian tidak tersedia.";
						}
						else
						{
							$tes1 = $bot['jawab']."\n".$hasil;
						}
					}
					else if ($bot['action'] == "ipk")
					{
						$q_ipk = $koneksi->query("SELECT tbl_rombel.feedback1, tbl_rombel.feedback2, tbl_kelas.kd_mk, tbl_mk.nm_mk, tbl_mk.sks, MAX(nilai.angka) AS angka
						FROM tbl_rombel
						INNER JOIN tbl_kelas ON tbl_rombel.kd_kelas=tbl_kelas.kode
						INNER JOIN tbl_mk ON tbl_kelas.kd_mk=tbl_mk.kode_mk
						INNER JOIN nilai ON tbl_rombel.n_akhir2 = nilai.huruf
						WHERE tbl_rombel.nim = '$nim' AND tbl_kelas.periode < (SELECT kode FROM tbl_periode WHERE tbl_periode.`status` = 'aktif')
						GROUP BY tbl_kelas.kd_mk
						ORDER BY tbl_kelas.periode, tbl_kelas.kd_mk");
						$jumipk = $q_ipk->num_rows;
						if ($jumipk == 0)
						{
							$tes1 = "Belum ada IPK";
						}
						else
						{
							$indeks ="";
							$total ="";
							$sks = "";
							$totalindeks ="";
							while ($ipk = $q_ipk->fetch_assoc())
							{
								$indeks=$ipk['angka'];
								$total = $ipk['sks']*$indeks;
		 						$sks += $ipk['sks'];
		 						$totalindeks += $total;
							}
							$grandtotal = $totalindeks/$sks;
							$ipk = number_format($grandtotal,2);
							$tes1 = "total sks: $sks \nIP Kumulatif: $ipk";
						}
					}
					else if ($bot['action'] == "tagihan")
					{
						$q_tagihan = $koneksi->query("SELECT tbl_periode.nm_periode, a_invoice.invoice, a_invoice.tagihan, SUM(a_pembayaran.total_bayar) AS bayar
						FROM a_invoice
						INNER JOIN tbl_periode ON tbl_periode.kode = a_invoice.periode
						LEFT JOIN a_pembayaran ON a_pembayaran.invoice = a_invoice.invoice
						WHERE a_invoice.nim = 2015131020 AND tbl_periode.`status` = 'Aktif'
						GROUP BY a_invoice.`invoice`");
						$jumtagihan = $q_tagihan->num_rows;
						if ($jumtagihan == 0)
						{
							$tes1 = "belum ada tagihan";
						}
						else
						{
							$hasil = "";
							while ($tagihan = $q_tagihan->fetch_assoc())
							{
								$cicilan = $tagihan['tagihan']/5;
								$sisa = $tagihan['tagihan']-$tagihan['bayar'];
								$hasil .= "💰💰💰 \n \nNo.INV : ".$tagihan['invoice']."\nPeriode : ".$tagihan['nm_periode']."\nJumlah Tagihan : ".number_format($tagihan['tagihan'],0,".",".")."
Cicilan per-bulan : ".number_format($cicilan,0,".",".")." \nTelah dibayar : ".number_format($tagihan['bayar'],0,".",".")." \n \nSISA TAGIHAN : ".number_format($sisa,0,".",".");
							}
							$tes1 = $bot['jawab']."
$hasil";
						}
						
						$ket = $bot['gol'];
						$koneksi->query("update register set ket = '$ket' where chatid = '$chatid'");
					}
				}
				else
				{
					$q_mhs = $koneksi->query("select nama from tbl_mhs where nim = '$nim'");
					$mhs = $q_mhs->fetch_assoc();
					$nama = $mhs['nama'];
					$tes1 = "Halo $nama, 
ada yang bisa saya bantu? 🧐

Ketik 'hi Yube' untuk informasi selanjutnya.";
				}
			}
		}
	}
	$koneksi->close();
	return $tes1;
}
function process_message($message)
{
    $updateid = $message["update_id"];
    $message_data = $message["message"];
    if (isset($message_data["text"])) {
	$chatid = $message_data["chat"]["id"];
        $message_id = $message_data["message_id"];
        $text = $message_data["text"];
        $response = create_response($text,$chatid);
	
        send_reply($chatid, $message_id, $response);
    }
    return $updateid;
}
function process_one()
{
	$update_id  = 0;
	if (file_exists("last_update_id")) {
		$update_id = (int)file_get_contents("last_update_id");
	}
	$updates = get_updates($update_id);
	foreach ($updates as $message)
	{
     		$update_id = process_message($message);
	}
	file_put_contents("last_update_id", $update_id + 1);
}
while (true) {
	process_one();
}
          
?>