<?php

$lue_data_output_file = "";
$lue_data_output_text = "";
$lue_data_err_file = "";
$lue_data_virheelliset_rivit = array();
$api_output = "";
$api_status = TRUE;

// Enabloidaan, ett� Apache flushaa kaiken mahdollisen ruudulle kokoajan.
//apache_setenv('no-gzip', 1);
ini_set('zlib.output_compression', 0);
ini_set('implicit_flush', 1);
ob_implicit_flush(1);

ini_set("memory_limit", "5G");
ini_set("post_max_size", "100M");
ini_set("upload_max_filesize", "100M");
ini_set("mysql.connect_timeout", 600);
ini_set("max_execution_time", 18000);

if (php_sapi_name() == 'cli') {

	$pupe_root_polku = dirname(__FILE__);
	require ("{$pupe_root_polku}/inc/connect.inc");
	require ("{$pupe_root_polku}/inc/functions.inc");

	$cli = true;

	ini_set("include_path", ".".PATH_SEPARATOR.$pupe_root_polku.PATH_SEPARATOR."/usr/share/pear".PATH_SEPARATOR."/usr/share/php/");

	if (trim($argv[1]) != '') {
		$kukarow['yhtio'] = mysql_real_escape_string($argv[1]);
		$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);
	}
	else {
		die ("Et antanut yhti�t�.\n");
	}

	if (trim($argv[2]) != '') {
		$table = trim($argv[2]);
	}
	else {
		die ("Et antanut taulun nime�.\n");
	}

	$kukarow['kuka'] = "cli";
	$kukarow['nimi'] = "cli";

	// Mik� tiedosto k�sitell��n
	if (trim($argv[3]) != '') {
		$path_parts = pathinfo(trim($argv[3]));
		$_FILES['userfile']['name'] = $path_parts['basename'];
		$_FILES['userfile']['type'] = (strtoupper($path_parts['extension']) == 'TXT' or strtoupper($path_parts['extension']) == 'CSV') ? 'text/plain' : (strtoupper($path_parts['extension']) == 'XLS') ? 'application/vnd.ms-excel' : '';
		$_FILES['userfile']['tmp_name'] = $argv[3];
		$_FILES['userfile']['error'] = 0; // UPLOAD_ERR_OK
		$_FILES['userfile']['size'] = filesize($argv[3]);
	}
	else {
		die ("Et antanut tiedoston nime� ja polkua.\n");
	}

	// Logfile, johon kirjoitetaan kaikki output
	if (isset($argv[4]) and trim($argv[4]) != '') {
		$lue_data_output_file = trim($argv[4]);
		$fileparts = pathinfo($lue_data_output_file);

		if (!is_writable($fileparts["dirname"])) {
			die ("Virheellinen hakemisto: ".$fileparts["dirname"]);
		}

		if (file_exists($lue_data_output_file)) {
			die ("Ei voida k�ytt�� olemassaolevaa log-file�: ".$lue_data_output_file);
		}
	}

	// Errorfile, johon kirjoitetaan kaikki vriheelliset rivit
	if (isset($argv[5]) and trim($argv[5]) != '') {
		$lue_data_err_file = trim($argv[5]);
		$fileparts = pathinfo($lue_data_err_file);

		if (!is_writable($fileparts["dirname"])) {
			die ("Virheellinen hakemisto: ".$fileparts["dirname"]);
		}

		if (file_exists($lue_data_err_file)) {
			die ("Ei voida k�ytt�� olemassaolevaa err-file�: ".$lue_data_err_file);
		}
	}
}
else {
	// Laitetaan max time 5H
	ini_set("max_execution_time", 18000);
	if (strpos($_SERVER['SCRIPT_NAME'], "lue_data.php") !== FALSE) {
		require ("inc/parametrit.inc");
	}
	$cli = false;
}


// Funktio, jolla tehd��n luedatan output
function lue_data_echo($string, $now = false) {

	global $cli, $lue_data_output_file, $lue_data_output_text, $api_kentat, $api_output;

	if (isset($api_kentat)) {
		$api_output .= $string."\n";
	}
	elseif ($cli === FALSE) {
		if ($now === TRUE) {
			echo $string;
		}
		else {
			$lue_data_output_text .= $string;
		}
	}
	elseif ($lue_data_output_file == "") {
		echo strip_tags($string)."\n";
	}
	elseif ($lue_data_output_file != "") {
		file_put_contents($lue_data_output_file, strip_tags($string)."\n", FILE_APPEND);
	}
	else {
		// Tiukkaa touhua, die!
		die ("Virheelliset parametrit");
	}
}

lue_data_echo("<font class='head'>".t("Datan sis��nluku")."</font><hr>");

// Saako p�ivitt��
if (!$cli and $oikeurow['paivitys'] != '1') {
	if ($uusi == 1) {
		lue_data_echo("<b>".t("Sinulla ei ole oikeutta lis�t�")."</b><br>");
		$uusi = '';
	}
	if ($del == 1) {
		lue_data_echo("<b>".t("Sinulla ei ole oikeuttaa poistaa")."</b><br>");
		$del = '';
		$tunnus = 0;
	}
	if ($upd == 1) {
		lue_data_echo("<b>".t("Sinulla ei ole oikeuttaa muuttaa")."</b><br>");
		$upd = '';
		$uusi = 0;
		$tunnus = 0;
	}
}

if (!isset($table)) $table = '';

$kasitellaan_tiedosto = FALSE;
require ("inc/pakolliset_sarakkeet.inc");

if (isset($_FILES['userfile']) and (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE or ($cli and trim($_FILES['userfile']['tmp_name']) != ''))) {

	$kasitellaan_tiedosto = TRUE;

	if ($_FILES['userfile']['size'] == 0) {
		lue_data_echo("<font class='error'><br>".t("Tiedosto on tyhj�")."!</font>");
		$kasitellaan_tiedosto = FALSE;
	}

	$path_parts = pathinfo($_FILES['userfile']['name']);
	$ext = strtoupper($path_parts['extension']);

	lue_data_echo("<font class='message'>".t("Tarkastetaan l�hetetty tiedosto")."...<br><br></font>");

	$retval = tarkasta_liite("userfile", array("XLSX","XLS","ODS","SLK","XML","GNUMERIC","CSV","TXT","DATAIMPORT"));

	if ($retval !== TRUE) {
		lue_data_echo("<font class='error'><br>".t("V��r� tiedostomuoto")."!</font>");
		$kasitellaan_tiedosto = FALSE;
	}
}
elseif (isset($api_kentat) and count($api_kentat) > 0) {
	$kasitellaan_tiedosto = TRUE;
}

if ($kasitellaan_tiedosto) {

	/** K�sitelt�v�n filen nimi **/
	$kasiteltava_tiedoto_path = $_FILES['userfile']['tmp_name'];

	if (isset($api_kentat) and count($api_kentat) > 0) {
		$excelrivit = $api_kentat;
	}
	else {
		$excelrivit = pupeFileReader($kasiteltava_tiedoto_path, $ext);
	}

	/** Otetaan tiedoston otsikkorivi **/
	$headers = $excelrivit[0];
	$headers = array_map('trim', $headers);
	$headers = array_map('strtoupper', $headers);

	// Unsetatan tyhj�t sarakkeet
	for ($i = (count($headers)-1); $i > 0 ; $i--) {
		if ($headers[$i] != "") {
			break;
		}
		else {
			unset($headers[$i]);
		}
	}

	$taulut			= array();
	$mul_taulut 	= array();
	$mul_taulas 	= array();
	$taulunotsikot	= array();
	$taulunrivit	= array();

	// Katsotaan onko sarakkeita useasta taulusta
	for ($i = 0; $i < count($headers); $i++) {
		if (strpos($headers[$i], ".") !== FALSE) {

			list($taulu, $sarake) = explode(".", $headers[$i]);
			$taulu = strtolower(trim($taulu));

			// Joinataanko sama taulu monta kertaa?
			if ((isset($mul_taulas[$taulu]) and isset($mul_taulut[$taulu."__".$mul_taulas[$taulu]]) and in_array($headers[$i], $mul_taulut[$taulu."__".$mul_taulas[$taulu]])) or (isset($mul_taulut[$taulu]) and (!isset($mul_taulas[$taulu]) or !isset($mul_taulut[$taulu."__".$mul_taulas[$taulu]])) and in_array($headers[$i], $mul_taulut[$taulu]))) {
				$mul_taulas[$taulu]++;

				$taulu = $taulu."__".$mul_taulas[$taulu];
			}
			elseif (isset($mul_taulas[$taulu]) and isset($mul_taulut[$taulu."__".$mul_taulas[$taulu]])) {
				$taulu = $taulu."__".$mul_taulas[$taulu];
			}

			$taulut[] = $taulu;
			$mul_taulut[$taulu][] = $headers[$i];
		}
		else {
			$taulut[] = $table;
		}
	}

	// T�ss� kaikki taulut jotka l�yty failista
	$unique_taulut = array_unique($taulut);

	// Tutkitaan mill� ehdoilla taulut joinataan kesken��n
	$joinattavat = array();

	$table_tarkenne = '';

	foreach ($unique_taulut as $utaulu) {
		list($taulu, ) = explode(".", $utaulu);
		$taulu = preg_replace("/__[0-9]*$/", "", $taulu);

		if (substr($taulu, 0, 11) == 'puun_alkio_') {
			$taulu = 'puun_alkio';
			$table_tarkenne = substr($taulu, 11);
		}

		list($pakolliset, $kielletyt, $wherelliset, $eiyhtiota, $joinattava, $saakopoistaa, $oletukset) = pakolliset_sarakkeet($taulu);

		// Laitetaan aina kaikkiin tauluihin
		$joinattava["TOIMINTO"] = $table;

		$joinattavat[$utaulu] = $joinattava;
	}

	// Laitetaan jokaisen taulun otsikkorivi kuntoon
	for ($i = 0; $i < count($headers); $i++) {

		if (strpos($headers[$i], ".") !== FALSE) {
			list($sarake1, $sarake2) = explode(".", $headers[$i]);
			if ($sarake2 != "") $sarake1 = $sarake2;
		}
		else {
			$sarake1 = $headers[$i];
		}

		$sarake1 = strtoupper(trim($sarake1));

		$taulunotsikot[$taulut[$i]][] = $sarake1;

		// Pit��k� t�m� sarake laittaa my�s johonki toiseen tauluun?
		foreach ($joinattavat as $taulu => $joinit) {

			if (strpos($headers[$i], ".") !== FALSE) {
				list ($etu, $taka) = explode(".", $headers[$i]);
				if ($taka == "") $taka = $etu;
			}
			else {
				$taka = $headers[$i];
			}

			if (isset($joinit[$taka]) and (!isset($taulunotsikot[$taulu]) or !in_array($sarake1, $taulunotsikot[$taulu]))) {
				$taulunotsikot[$taulu][] = $sarake1;
			}
		}
	}

	foreach ($taulunotsikot as $taulu => $otsikot) {
		if (count($otsikot) != count(array_unique($otsikot))) {
			lue_data_echo("<font class='error'>$taulu-".t("taulun sarakkeissa ongelmia, ei voida jatkaa")."!</font><br>");
			if ($lue_data_output_file != "") {
				lue_data_echo("## LUE-DATA-EOF ##");
			}
			lue_data_echo($lue_data_output_text, true);
			require ("inc/footer.inc");
			exit;
		}
	}

	// Otetaan tuotteiden oletusalv hanskaan
	if (in_array("tuote", $taulut)) {
		$oletus_alvprossa = alv_oletus();
	}

	// rivim��r� exceliss�
	$excelrivimaara = count($excelrivit);

	// sarakem��r� exceliss�
	$excelsarakemaara = count($headers);

	// Luetaan tiedosto loppuun ja tehd��n taulukohtainen array koko datasta, t�ss� kohtaa putsataan jokaisen solun sis�lt� pupesoft_cleanstring -funktiolla
	for ($excei = 1; $excei < $excelrivimaara; $excei++) {
		for ($excej = 0; $excej < $excelsarakemaara; $excej++) {

			$taulunrivit[$taulut[$excej]][$excei-1][] = pupesoft_cleanstring($excelrivit[$excei][$excej]);

			// Pit��k� t�m� sarake laittaa my�s johonki toiseen tauluun?
			foreach ($taulunotsikot as $taulu => $joinit) {

				if (strpos($headers[$excej], ".") !== FALSE) {
					list ($etu, $taka) = explode(".", $headers[$excej]);
					if ($taka == "") $taka = $etu;
				}
				else {
					$taka = $headers[$excej];
				}

				if (in_array($taka, $joinit) and $taulu != $taulut[$excej] and $taulut[$excej] == $joinattavat[$taulu][$taka]) {
					$taulunrivit[$taulu][$excei-1][] = pupesoft_cleanstring($excelrivit[$excei][$excej]);
				}
			}
		}
	}

	// Korjataan spessujoini yhteensopivuus_tuote_lisatiedot/yhteensopivuus_tuote
	if (in_array("yhteensopivuus_tuote", $taulut) and in_array("yhteensopivuus_tuote_lisatiedot", $taulut)) {

		foreach ($taulunotsikot["yhteensopivuus_tuote_lisatiedot"] as $key => $column) {
			if ($column == "TUOTENO") {
				$joinsarake = $key;
				break;
			}
		}

		// Vaihdetaan otsikko
		$taulunotsikot["yhteensopivuus_tuote_lisatiedot"][$joinsarake] = "YHTEENSOPIVUUS_TUOTE_TUNNUS";

		// Tyhjennet��n arvot
		foreach ($taulunrivit["yhteensopivuus_tuote_lisatiedot"] as $ind => $rivit) {
			$taulunrivit["yhteensopivuus_tuote_lisatiedot"][$ind][$joinsarake] = "";
		}
	}

	/*
	foreach ($taulunrivit as $taulu => $rivit) {

		list($table_mysql, ) = explode(".", $taulu);
		$table_mysql = preg_replace("/__[0-9]*$/", "", $table_mysql);

		echo "<table>";
		echo "<tr><th>$table_mysql</th>";
		foreach ($taulunotsikot[$taulu] as $key => $column) {
			echo "<th>$key => $column</th>";
		}
		echo "</tr>";
		for ($eriviindex = 0; $eriviindex < count($rivit); $eriviindex++) {
			echo "<tr><th>$table_mysql</th>";
			foreach ($rivit[$eriviindex] as $eriv) {
				echo "<td>$eriv</td>";
			}
			echo "</tr>";
		}
		echo "</table><br>";
	}
	exit;
	*/

	// REST-api ei salli etenemispalkkia
	if ((!$cli or $lue_data_output_file != "") and !isset($api_kentat)) {
		require('inc/ProgressBar.class.php');
	}

	$taulunrivit_keys = array_keys($taulunrivit);

	for ($tril = 0; $tril < count($taulunrivit); $tril++) {

		$taulu = $taulunrivit_keys[$tril];
		$rivit = $taulunrivit[$taulu];

		$vikaa			= 0;
		$tarkea			= 0;
		$wheretarkea	= 0;
		$kielletty		= 0;
		$lask			= 0;
		$postoiminto	= 'X';
		$table_mysql 	= "";
		$tarkyhtio		= "";
		$tarkylisa 		= 1;
		$indeksi		= array();
		$indeksi_where	= array();
		$trows			= array();
		$tlength		= array();
		$tdecimal		= array();
		$apu_sarakkeet	= array();
		$rivimaara 		= count($rivit);
		$dynaamiset_rivit = array();

		// Siivotaan joinit ja muut pois tietokannan nimest�
		list($table_mysql, ) = explode(".", $taulu);
		$table_mysql = preg_replace("/__[0-9]*$/", "", $table_mysql);

		if (substr($table_mysql, 0, 11) == 'puun_alkio_') {
			$table_tarkenne = substr($table_mysql, 11);
			$table_mysql = 'puun_alkio';
		}

		// jos tullaan jotenkin hassusti, nmiin ei tehd� mit��n
		if (trim($table_mysql) == "") continue;

		// Haetaan valitun taulun sarakkeet
		$query = "SHOW COLUMNS FROM $table_mysql";
		$fres  = pupe_query($query);

		while ($row = mysql_fetch_array($fres)) {
			// Pushataan arrayseen kaikki sarakenimet ja tietuetyypit
			$trows[$table_mysql.".".strtoupper($row[0])] = $row[1];

			$tlengthpit = preg_replace("/[^0-9,]/", "", $row[1]);

			if (strpos($tlengthpit, ",") !== FALSE) {
				// Otetaan desimaalien m��r� talteen
				$tdecimal[$table_mysql.".".strtoupper($row[0])] = (int) substr($tlengthpit, strpos($tlengthpit, ",")+1);
				$tlengthpit = substr($tlengthpit, 0, strpos($tlengthpit, ",")+1)+1;
			}

			if (substr($row[1], 0, 7) == "decimal" or substr($row[1], 0, 3) == "int") {
				// Sallitaan my�s miinusmerkki...
				$tlengthpit++;
			}

			$tlength[$table_mysql.".".strtoupper($row[0])] = trim($tlengthpit);
		}

		// N�m� ovat pakollisia dummysarakkeita jotka ohitetaan lopussa automaattisesti!
		if (in_array($table_mysql, array("yhteyshenkilo", "asiakkaan_avainsanat"))) {
			$apu_sarakkeet = array("YTUNNUS");
		}

		if (count($apu_sarakkeet) > 0) {
			foreach($apu_sarakkeet as $s) {
				$trows[$table_mysql.".".strtoupper($s)] = "";
			}
		}

		if ($table_mysql == 'tullinimike') {
			$tulli_ei_kielta = "";
			$tulli_ei_toimintoa = "";

			if (in_array("KIELI", $taulunotsikot[$taulu]) === FALSE) {
				$tulli_ei_kielta = "PUUTTUU";
				$taulunotsikot[$taulu][] = "KIELI";
			}
			if (in_array("TOIMINTO", $taulunotsikot[$taulu]) === FALSE) {
				$taulunotsikot[$taulu][] = "TOIMINTO";
				$tulli_ei_toimintoa = "PUUTTUU";
			}
		}

		// Otetaan pakolliset, kielletyt, wherelliset ja eiyhtiota tiedot
		list($pakolliset, $kielletyt, $wherelliset, $eiyhtiota, $joinattavat, $saakopoistaa, $oletukset) = pakolliset_sarakkeet($table_mysql, $taulunotsikot[$taulu]);

		// $trows sis�lt�� kaikki taulun sarakkeet ja tyypit tietokannasta
		// $taulunotsikot[$taulu] sis�lt�� kaikki sarakkeet saadusta tiedostosta
		foreach ($taulunotsikot[$taulu] as $key => $column) {
			if ($column != '') {
				if ($column == "TOIMINTO") {
					//TOIMINTO sarakkeen positio tiedostossa
					$postoiminto = (string) array_search($column, $taulunotsikot[$taulu]);
				}
				else {
					if (!isset($trows[$table_mysql.".".$column]) and $column != "AVK_TUNNUS") {
						lue_data_echo("<font class='error'>".t("Saraketta")." \"$column\" ".t("ei l�ydy")." $table_mysql-".t("taulusta")."!</font><br>");
						$vikaa++;
					}

					// yhtio ja tunnus kentti� ei saa koskaan muokata...
					if ($column == 'YHTIO' or $column == 'TUNNUS') {
						lue_data_echo("<font class='error'>".t("Yhti�- ja tunnussaraketta ei saa muuttaa")." $table_mysql-".t("taulussa")."!</font><br>");
						$vikaa++;
					}

					if (in_array($column, $pakolliset)) {
						// pushataan positio indeksiin, ett� tiedet��n miss� kohtaa avaimet tulevat
						$pos = array_search($column, $taulunotsikot[$taulu]);
						$indeksi[$column] = $pos;
						$tarkea++;
					}

					if (in_array($column, $kielletyt)) {
						// katotaan ettei kiellettyj� sarakkeita muuteta
						$viesti .= t("Sarake").": $column ".t("on kielletty sarake")." $table_mysql-".t("taulussa")."!<br>";
						$kielletty++;
					}

					if (is_array($wherelliset) and in_array($column, $wherelliset)) {
						// katotaan ett� m��ritellyt where lausekkeen ehdot l�ytyv�t
						$pos = array_search($column, $taulunotsikot[$taulu]);
						$indeksi_where[$column] = $pos;
						$wheretarkea++;
					}
				}
			}
			else {
				$vikaa++;
				lue_data_echo("<font class='error'>".t("Tiedostossa on tyhji� sarakkeiden otsikoita")."!</font><br>");
			}
		}

		// Oli virheellisi� sarakkeita tai pakollisia ei l�ytynyt..
		if ($vikaa != 0 or $tarkea != count($pakolliset) or $postoiminto == 'X' or $kielletty > 0 or (is_array($wherelliset) and $wheretarkea != count($wherelliset))) {

			if ($vikaa != 0) {
				lue_data_echo("<font class='error'>".t("V��ri� sarakkeita tai yritit muuttaa yhti�/tunnus saraketta")."!</font><br>");
			}

			if ($tarkea != count($pakolliset)) {
				$pakolliset_text = "<font class='error'>".t("Pakollisia/t�rkeit� kentti� puuttuu")."! ( ";

				foreach ($pakolliset as $apupako) {
					$pakolliset_text .= "$apupako ";
				}

				$pakolliset_text .= " ) $table_mysql-".t("taulusta")."!</font><br>";
				lue_data_echo($pakolliset_text);
			}

			if ($postoiminto == 'X') {
				lue_data_echo("<font class='error'>".t("Toiminto sarake puuttuu")."!</font><br>");
			}

			if ($kielletty > 0) {
				lue_data_echo("<font class='error'>".t("Yrit�t p�ivitt�� kiellettyj� sarakkeita")." $table_mysql-".t("taulussa")."!</font><br>$viesti");
			}

			if (is_array($wherelliset) and $wheretarkea != count($wherelliset)) {
				$pakolliset_text = "<font class='error'>".t("Sinulta puuttui jokin pakollisista sarakkeista")." (";

				foreach ($wherelliset as $apupako) {
					$pakolliset_text .= "$apupako ";
				}

				$pakolliset_text .= ") $table_mysql-".t("taulusta")."!</font><br>";
				lue_data_echo($pakolliset_text);
			}

			lue_data_echo("<font class='error'>".t("Virheit� l�ytyi. Ei voida jatkaa")."!<br></font>");
			if ($lue_data_output_file != "") {
				lue_data_echo("## LUE-DATA-EOF ##");
			}

			if (!isset($api_kentat)) {
				lue_data_echo($lue_data_output_text, true);
				require ("inc/footer.inc");
				exit;
			}
			else {
				// Jos tullaan api.php:st� ja p��dyt��n virheeseen, t�ll� estet��n ettei menn� for-looppiin riville 650
				// EI voida sanoa EXIT tai DIE koska api.php pit�� menn� loppuun.
				$rivit = array();
				$api_status = FALSE;
			}
		}

		lue_data_echo("<br><font class='message'>".t("Tiedosto ok, aloitetaan p�ivitys")." $table_mysql-".t("tauluun")."...<br></font>");
		lue_data_echo($lue_data_output_text, true);

		$lue_data_output_text = "";
		$rivilaskuri = 1;

		$puun_alkio_index_plus = 0;

		$max_rivit = count($rivit);

		// REST-api ei salli etenemispalkkia
		if ((!$cli or $lue_data_output_file != "") and !isset($api_kentat)) {
			$bar = new ProgressBar();
			$bar->initialize($max_rivit);
		}

		for ($eriviindex = 0; $eriviindex < (count($rivit) + $puun_alkio_index_plus); $eriviindex++) {

			// Komentorivill� piirret��n progressbar, ellei ole output loggaus p��ll�
			// REST-api skippaa
			if (!isset($api_kentat)) {
				if ($cli and $lue_data_output_file == "") {
					progress_bar($eriviindex, $max_rivit);
				}
				elseif (!$cli or $lue_data_output_file != "") {
					$bar->increase();
				}
			}
			$hylkaa    = 0;
			$tila      = "";
			$tee       = "";
			$eilisataeikamuuteta = "";
			$rivilaskuri++;

			//asiakashinta/asiakasalennus/toimittajahinta/toimittajaalennus spessuja
			$chasiakas_ryhma 	= '';
			$chytunnus 			= '';
			$chryhma 			= '';
			$chtuoteno 			= '';
			$chasiakas			= 0;
			$chsegmentti		= 0;
			$chpiiri			= '';
			$chminkpl			= 0;
			$chmaxkpl			= 0;
			$chalennuslaji		= 0;
			$chmonikerta		= "";
			$chalkupvm 			= '0000-00-00';
			$chloppupvm 		= '0000-00-00';
			$and 				= '';
			$tpupque 			= '';
			$toimi_liitostunnus = '';
			$chtoimittaja		= '';

			if ($eiyhtiota == "" or $eiyhtiota == "EILAATIJAA") {
				$valinta   = " yhtio = '{$kukarow['yhtio']}'";
			}
			elseif ($eiyhtiota == "TRIP") {
				$valinta   = " tunnus > 0 ";
			}

			// Rakennetaan rivikohtainen array
			$rivi = array();

			foreach ($rivit[$eriviindex] as $eriv) {
				$rivi[] = $eriv;
			}

			if ($table_mysql == 'tullinimike' and $tulli_ei_kielta != "") {
				$rivi[] = "FI";
			}

			if ($table_mysql == 'tullinimike' and $tulli_ei_toimintoa != "") {
				$rivi[] = "LISAA";
			}

			// Rivin toiminto
			$rivi[$postoiminto] = strtoupper(trim($rivi[$postoiminto]));

			//Sallitaan my�s MUOKKAA ja LIS�� toiminnot
			if ($rivi[$postoiminto] == "LIS��") $rivi[$postoiminto] = "LISAA";
			if ($rivi[$postoiminto] == "MUOKKAA") $rivi[$postoiminto] = "MUUTA";
			if ($rivi[$postoiminto] == "MUOKKAA/LIS��") $rivi[$postoiminto] = "MUUTA/LISAA";
			if ($rivi[$postoiminto] == "MUOKKAA/LISAA") $rivi[$postoiminto] = "MUUTA/LISAA";
			if ($rivi[$postoiminto] == "MUUTA/LIS��") $rivi[$postoiminto] = "MUUTA/LISAA";
			if ($rivi[$postoiminto] == "POISTA") $rivi[$postoiminto] = "POISTA";

			//Jos eri where-ehto array on m��ritelty
			if (is_array($wherelliset)) {
				$indeksi = array_merge($indeksi, $indeksi_where);
				$indeksi = array_unique($indeksi);
			}

			// Lis�t��n taulun oletusarvot, jos ollaan lis��m�ss� uutta tietuetta
			if ($rivi[$postoiminto] == "LISAA") {
				foreach ($oletukset as $oletus_kentta => $oletus_arvo) {
					// Etsit��n taulunotsikot arrayst� KEY, jonka arvo on oletuskentt�
					$oletus_positio = array_keys($taulunotsikot[$taulu], $oletus_kentta, true);

					// Kentt� l�ytyy taulukosta ja se on tyhj�, laitetaan siihen oletusarvo
					// Jos kentt�� EI L�YDY, niin lis�t��n se muiden oletusten kanssa alempana
					if (count($oletus_positio) == 1 and $rivi[$oletus_positio[0]] == "") {
						$rivi[$oletus_positio[0]] = $oletus_arvo;
					}
				}
			}

			$avkmuuttuja = FALSE;

			foreach ($indeksi as $j) {
				if ($taulunotsikot[$taulu][$j] == "TUOTENO") {

					$tuoteno = trim($rivi[$j]);

					$valinta .= " and TUOTENO='$tuoteno'";
				}
				elseif ($table_mysql == 'tullinimike' and strtoupper($taulunotsikot[$taulu][$j]) == "CN") {

					$taulunrivit[$taulu][$eriviindex][$j] = $rivit[$eriviindex][$j] = $rivi[$j] = str_replace(' ','',$rivi[$j]);

					$valinta .= " and cn='".$rivi[$j]."'";

					if (trim($rivi[$j]) == '') {
						$tila = 'ohita';
					}
				}
				elseif ($table_mysql == 'extranet_kayttajan_lisatiedot' and strtoupper($taulunotsikot[$taulu][$j]) == "LIITOSTUNNUS" and $liitostunnusvalinta == 2) {
					$query = "	SELECT tunnus
								FROM kuka
								WHERE yhtio = '$kukarow[yhtio]'
								and extranet != ''
								and kuka = '$rivi[$j]'";
					$apures = pupe_query($query);

					if (mysql_num_rows($apures) == 1) {
						$apurivi = mysql_fetch_assoc($apures);

						$taulunrivit[$taulu][$eriviindex][$j] = $rivit[$eriviindex][$j] = $rivi[$j] = $apurivi["tunnus"];

						$valinta .= " and ".$taulunotsikot[$taulu][$j]."='$apurivi[tunnus]'";
					}
					else {
						// Ei l�ydy, trigger�id��n virhe
						$taulunrivit[$taulu][$eriviindex][$j] = $rivit[$eriviindex][$j] = $rivi[$j] = "XXX";
						$valinta .= " and ".$taulunotsikot[$taulu][$j]."='XXX'";
					}
				}
				elseif ($table_mysql == 'sanakirja' and $taulunotsikot[$taulu][$j] == "FI") {
					// jos ollaan mulkkaamassa RU ni tehd��n utf-8 -> latin-1 konversio FI kent�ll�
					if (in_array("RU", $taulunotsikot[$taulu])) {
						$rivi[$j] = iconv("UTF-8", "ISO-8859-1", $rivi[$j]);
					}

					$valinta .= " and ".$taulunotsikot[$taulu][$j]."= BINARY '".trim($rivi[$j])."'";
				}
				elseif ($table_mysql == 'tuotepaikat' and $taulunotsikot[$taulu][$j] == "OLETUS") {
					//ei haluta t�t� t�nne
				}
				elseif ($table_mysql == 'yhteensopivuus_tuote_lisatiedot' and $taulunotsikot[$taulu][$j] == "YHTEENSOPIVUUS_TUOTE_TUNNUS" and $taulunrivit[$taulu][$eriviindex][$j] == "") {
					// Hetaan liitostunnus yhteensopivuus_tuote-taulusta
					$apusql = "	SELECT tunnus
								FROM yhteensopivuus_tuote
								WHERE yhtio = '$kukarow[yhtio]'
								and tyyppi  = '{$taulunrivit["yhteensopivuus_tuote"][$eriviindex][array_search("TYYPPI", $taulunotsikot["yhteensopivuus_tuote"])]}'
								and atunnus = '{$taulunrivit["yhteensopivuus_tuote"][$eriviindex][array_search("ATUNNUS", $taulunotsikot["yhteensopivuus_tuote"])]}'
								and tuoteno = '{$taulunrivit["yhteensopivuus_tuote"][$eriviindex][array_search("TUOTENO", $taulunotsikot["yhteensopivuus_tuote"])]}'";
					$apures = pupe_query($apusql);

					if (mysql_num_rows($apures) == 1) {
						$apurivi = mysql_fetch_assoc($apures);

						$taulunrivit[$taulu][$eriviindex][$j] = $rivit[$eriviindex][$j] = $rivi[$j] = $apurivi["tunnus"];

						$valinta .= " and ".$taulunotsikot[$taulu][$j]."='$apurivi[tunnus]'";
					}
				}
				elseif ($table_mysql == 'puun_alkio') {

					// voidaan vaan lis�t� puun alkioita
					if ($rivi[$postoiminto] != "LISAA" and $rivi[$postoiminto] != "POISTA") {
						$tila = 'ohita';
					}

					if ($tila != 'ohita' and $taulunotsikot[$taulu][$j] == "PUUN_TUNNUS") {

						// jos ollaan valittu koodi puun_tunnuksen sarakkeeksi, niin haetaan dynaamisesta puusta tunnus koodilla
						if ($dynaamisen_taulun_liitos == 'koodi') {

							$query_x = "	SELECT tunnus
											FROM dynaaminen_puu
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND laji = '{$table_tarkenne}'
											AND koodi = '".trim($rivi[$j])."'";
							$koodi_tunnus_res = pupe_query($query_x);

							// jos tunnusta ei l�ydy, ohitetaan kyseinen rivi
							if (mysql_num_rows($koodi_tunnus_res) == 0) {
								$tila = 'ohita';
							}
							else {
								$koodi_tunnus_row = mysql_fetch_assoc($koodi_tunnus_res);
								$valinta .= " and puun_tunnus = '{$koodi_tunnus_row['tunnus']}' ";
							}
						}
						else {
							$valinta .= " and puun_tunnus = '".trim($rivi[$j])."' ";
						}
					}
					elseif ($tila != 'ohita' and $taulunotsikot[$taulu][$j] == "LIITOS") {
						if ($table_tarkenne == 'asiakas' and $dynaamisen_taulun_liitos != '') {

							$query = "	SELECT tunnus
										FROM asiakas
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND laji != 'P'
										AND $dynaamisen_taulun_liitos = '".trim($rivi[$j])."'";
							$asiakkaan_haku_res = pupe_query($query);

							unset($rivit[$eriviindex]);

							while ($asiakkaan_haku_row = mysql_fetch_assoc($asiakkaan_haku_res)) {

								$rivi_array_x = array();

								foreach ($taulunotsikot[$taulu] as $indexi_x => $columnin_nimi_x) {
									switch ($columnin_nimi_x) {
										case 'LIITOS':
											$rivi_array_x[] = $asiakkaan_haku_row['tunnus'];
											break;
										default:
											$rivi_array_x[] = $rivi[$indexi_x];
									}
								}

								array_push($dynaamiset_rivit, $rivi_array_x);
							}

							$puun_alkio_index_plus++;

							if ($rivimaara == ($eriviindex+1)) {
								$dynaamisen_taulun_liitos = '';

								foreach ($dynaamiset_rivit as $dyn_rivi) array_push($rivit, $dyn_rivi);
							}

							continue 2;
						}
						else {
							$valinta .= " and liitos = '".trim($rivi[$j])."' ";
						}
					}
				}
				elseif ($table_mysql == 'asiakas' and stripos($rivi[$postoiminto], 'LISAA') !== FALSE and $taulunotsikot[$taulu][$j] == "YTUNNUS" and $rivi[$j] == "AUTOM") {

					if ($yhtiorow["asiakasnumeroinnin_aloituskohta"] != "") {
						$apu_asiakasnumero = $yhtiorow["asiakasnumeroinnin_aloituskohta"];
					}
					else {
						$apu_asiakasnumero = 0;
					}

					//jos konsernin asiakkaat synkronoidaan niin asiakkaiden yksil�iv�t tiedot on oltava konsernitasolla-yksil�lliset
					if ($tarkyhtio == "") {
						$query = "	SELECT *
									FROM yhtio
									JOIN yhtion_parametrit ON yhtion_parametrit.yhtio = yhtio.yhtio
									where konserni = '$yhtiorow[konserni]'
									and (synkronoi = 'asiakas' or synkronoi like 'asiakas,%' or synkronoi like '%,asiakas,%' or synkronoi like '%,asiakas')";
						$vresult = pupe_query($query);

						if (mysql_num_rows($vresult) > 0) {
							// haetaan konsernifirmat
							$query = "	SELECT group_concat(concat('\'',yhtio.yhtio,'\'')) yhtiot
										FROM yhtio
										JOIN yhtion_parametrit ON yhtion_parametrit.yhtio = yhtio.yhtio
										where konserni = '$yhtiorow[konserni]'
										and (synkronoi = 'asiakas' or synkronoi like 'asiakas,%' or synkronoi like '%,asiakas,%' or synkronoi like '%,asiakas')";
							$vresult = pupe_query($query);
							$srowapu = mysql_fetch_array($vresult);
							$tarkyhtio = $srowapu["yhtiot"];
						}
						else {
							$tarkyhtio = "'$kukarow[yhtio]'";
						}
					}

					$query = "	SELECT MAX(asiakasnro+0) asiakasnro
								FROM asiakas USE INDEX (asno_index)
								WHERE yhtio in ($tarkyhtio)
								AND asiakasnro+0 >= $apu_asiakasnumero";
					$vresult = pupe_query($query);
					$vrow = mysql_fetch_assoc($vresult);

					if ($vrow['asiakasnro'] != '') {
						$apu_ytunnus = $vrow['asiakasnro'] + $tarkylisa;
						$tarkylisa++;
					}
					else {
						$apu_ytunnus = $tarkylisa;
						$tarkylisa++;
					}

					// P�ivitet��n generoitu arvo kaikkiin muuttujiin...
					$taulunrivit[$taulu][$eriviindex][$j] = $rivit[$eriviindex][$j] = $rivi[$j] = $apu_ytunnus;

					foreach ($taulunotsikot as $autotaulu => $autojoinit) {
						if (in_array("YTUNNUS", $joinit) and $autotaulu != $taulut[$j] and $taulu == $joinattavat[$autotaulu]["YTUNNUS"]) {
							$taulunrivit[$autotaulu][$eriviindex][array_search("YTUNNUS", $taulunotsikot[$autotaulu])] = $apu_ytunnus;
						}
					}

					$valinta .= " and ".$taulunotsikot[$taulu][$j]."='$apu_ytunnus'";
				}
				elseif ($table_mysql == 'auto_vari_korvaavat') {

					if ($taulunotsikot[$taulu][$j] == "AVK_TUNNUS") {
						$valinta = " yhtio = '$kukarow[yhtio]' and tunnus = '".trim(pupesoft_cleanstring($rivi[$j]))."'";

						$apu_sarakkeet = array("AVK_TUNNUS");
						$avkmuuttuja = TRUE;
					}
					elseif (!$avkmuuttuja) {
						$valinta .= " and ".$taulunotsikot[$taulu][$j]."='".trim(pupesoft_cleanstring($rivi[$j]))."'";
					}
				}
				else {
					$valinta .= " and ".$taulunotsikot[$taulu][$j]."='".trim(pupesoft_cleanstring($rivi[$j]))."'";
				}

				// jos pakollinen tieto puuttuu kokonaan
				if (trim($rivi[$j]) == "" and in_array($taulunotsikot[$taulu][$j], $pakolliset)) {
					$tila = 'ohita';
				}
			}

			if (substr($taulu, 0, 11) == 'puun_alkio_') {
				$valinta .= " and laji = '".substr($taulu, 11)."' ";
			}

			// jos ei ole puuttuva tieto etsit��n rivi�
			if ($tila != 'ohita') {

				if (in_array($table_mysql, array("yhteyshenkilo", "asiakkaan_avainsanat", "kalenteri")) and (!in_array("LIITOSTUNNUS", $taulunotsikot[$taulu]) or (in_array("LIITOSTUNNUS", $taulunotsikot[$taulu]) and $rivi[array_search("LIITOSTUNNUS", $taulunotsikot[$taulu])] == ""))) {

					if ((in_array("YTUNNUS", $taulunotsikot[$taulu]) and ($table_mysql == "yhteyshenkilo" or $table_mysql == "asiakkaan_avainsanat")) or (in_array("ASIAKAS", $taulunotsikot[$taulu]) and $table_mysql == "kalenteri")) {

						if ($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "T" and $table_mysql == "yhteyshenkilo") {
							$tpque = "	SELECT tunnus
										from toimi
										where yhtio	= '$kukarow[yhtio]'
										and ytunnus	= '".$rivi[array_search("YTUNNUS", $taulunotsikot[$taulu])]."'
										and tyyppi != 'P'";
							$tpres = pupe_query($tpque);
						}
						elseif (($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "A" and $table_mysql == "yhteyshenkilo") or $table_mysql == "asiakkaan_avainsanat") {
							$tpque = "	SELECT tunnus
										from asiakas
										where yhtio	= '$kukarow[yhtio]'
										and ytunnus	= '".$rivi[array_search("YTUNNUS", $taulunotsikot[$taulu])]."'";
							$tpres = pupe_query($tpque);
						}
						elseif ($table_mysql == "kalenteri") {
							$tpque = "	SELECT tunnus
										from asiakas
										where yhtio	= '$kukarow[yhtio]'
										and ytunnus	= '".$rivi[array_search("ASIAKAS", $taulunotsikot[$taulu])]."'";
							$tpres = pupe_query($tpque);
						}

						if (mysql_num_rows($tpres) == 0) {
							if ($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "T" and $table_mysql == "yhteyshenkilo") {
								lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Toimittajaa")." '".$rivi[array_search("YTUNNUS", $taulunotsikot[$taulu])]."' ".t("ei l�ydy")."!<br>");
							}
							elseif (($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "A" and $table_mysql == "yhteyshenkilo") or $table_mysql == "asiakkaan_avainsanat") {
								lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Asiakasta")." '".$rivi[array_search("YTUNNUS", $taulunotsikot[$taulu])]."' ".t("ei l�ydy")."!<br>");
							}
							else {
								lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Asiakasta")." '".$rivi[array_search("ASIAKAS", $taulunotsikot[$taulu])]."' ".t("ei l�ydy")."!<br>");
							}

							$hylkaa++; // ei p�ivitet� t�t� rivi�
						}
						elseif (mysql_num_rows($tpres) == 1) {
							$tpttrow = mysql_fetch_array($tpres);

							//	Liitet��n pakolliset arvot
							if (!in_array("LIITOSTUNNUS", $taulunotsikot[$taulu])) {
								$taulunotsikot[$taulu][] = "LIITOSTUNNUS";
							}

							$rivi[] = $tpttrow["tunnus"];

							$valinta .= " and liitostunnus='$tpttrow[tunnus]' ";
						}
						else {

							if ($ytunnustarkkuus == 2) {
								$lasind = count($rivi);

								//	Liitet��n pakolliset arvot
								if (!in_array("LIITOSTUNNUS", $taulunotsikot[$taulu])) {
									$taulunotsikot[$taulu][] = "LIITOSTUNNUS";
								}

								$pushlask = 1;

								while ($tpttrow = mysql_fetch_array($tpres)) {

									$rivi[$lasind] = $tpttrow["tunnus"];

									if ($pushlask < mysql_num_rows($tpres)) {
										$rivit[] = $rivi;
									}

									$pushlask++;
								}

								$valinta .= " and liitostunnus='$rivi[$lasind]' ";
							}
							else {
								if ($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "T" and $table_mysql == "yhteyshenkilo") {
									lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Toimittaja")." '".$rivi[array_search("YTUNNUS", $taulunotsikot[$taulu])]."' ".t("Samalla ytunnuksella l�ytyy useita toimittajia! Lis�� toimittajan tunnus LIITOSTUNNUS-sarakkeeseen")."!<br>");
								}
								elseif (($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "A" and $table_mysql == "yhteyshenkilo") or $table_mysql == "asiakkaan_avainsanat") {
									lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Asiakas")." '".$rivi[array_search("YTUNNUS", $taulunotsikot[$taulu])]."' ".t("Samalla ytunnuksella l�ytyy useita asiakkaita! Lis�� asiakkaan tunnus LIITOSTUNNUS-sarakkeeseen")."!<br>");
								}
								else {
									lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Asiakas")." '".$rivi[array_search("ASIAKAS", $taulunotsikot[$taulu])]."' ".t("Samalla ytunnuksella l�ytyy useita asiakkaita! Lis�� asiakkaan tunnus LIITOSTUNNUS-sarakkeeseen")."!<br>");
								}

								$hylkaa++; // ei p�ivitet� t�t� rivi�
							}
						}
					}
					else {
						lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Rivi� ei voi lis�t� jos ei tiedet� ainakin YTUNNUSTA!")."<br>");
						$hylkaa++;
					}
				}
				elseif (in_array($table_mysql, array("yhteyshenkilo", "asiakkaan_avainsanat", "kalenteri")) and in_array("LIITOSTUNNUS", $taulunotsikot[$taulu])) {

					if ($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "T" and $table_mysql == "yhteyshenkilo") {
						$tpque = "	SELECT tunnus
									from toimi
									where yhtio	= '$kukarow[yhtio]'
									and tunnus	= '".$rivi[array_search("LIITOSTUNNUS", $taulunotsikot[$taulu])]."'
									and tyyppi != 'P'";
						$tpres = pupe_query($tpque);
					}
					elseif (($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "A" and $table_mysql == "yhteyshenkilo") or $table_mysql == "asiakkaan_avainsanat" or $table_mysql == "kalenteri") {
						$tpque = "	SELECT tunnus
									from asiakas
									where yhtio	= '$kukarow[yhtio]'
									and tunnus	= '".$rivi[array_search("LIITOSTUNNUS", $taulunotsikot[$taulu])]."'";
						$tpres = pupe_query($tpque);
					}

					if (mysql_num_rows($tpres) != 1) {
						lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Toimittajaa/Asiakasta")." '$rivi[$r]' ".t("ei l�ydy! Rivi� ei p�ivitetty/lis�tty")."!<br>");
						$hylkaa++; // ei p�ivitet� t�t� rivi�
					}
					else {
						$tpttrow = mysql_fetch_array($tpres);

						// Lis�t��n ehtoon
						$valinta .= " and liitostunnus='$tpttrow[tunnus]' ";
					}
				}

				$query = "	SELECT tunnus
							FROM $table_mysql
							WHERE $valinta";
				$fresult = pupe_query($query);

				if ($rivi[$postoiminto] == "MUUTA/LISAA") {
					// Muutetaan jos l�ytyy muuten lis�t��n!
					if (mysql_num_rows($fresult) == 0) {
						$rivi[$postoiminto] = "LISAA";
					}
					else {
						$rivi[$postoiminto] = "MUUTA";
					}
				}
				elseif ($rivi[$postoiminto] == 'LISAA' and $table_mysql != $table and mysql_num_rows($fresult) != 0) {
					// joinattaviin tauluhin tehd��n muuta-operaatio jos rivi l�ytyy
					$rivi[$postoiminto] = "MUUTA";
				}
				elseif ($rivi[$postoiminto] == 'MUUTA' and $table_mysql != $table and mysql_num_rows($fresult) == 0) {
					// joinattaviin tauluhin tehd��n lisaa-operaatio jos rivi� ei l�ydy
					$rivi[$postoiminto] = "LISAA";
				}
				elseif ($rivi[$postoiminto] == 'LISAA' and mysql_num_rows($fresult) != 0) {
					if ($table_mysql != 'asiakasalennus' and $table_mysql != 'asiakashinta' and $table_mysql != 'toimittajaalennus' and $table_mysql != 'toimittajahinta') {
						lue_data_echo(t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("VIRHE:")." ".t("Rivi on jo olemassa, ei voida perustaa uutta!")."</font> $valinta<br>");
						$tila = 'ohita';
					}
				}
				elseif ($rivi[$postoiminto] == 'MUUTA' and mysql_num_rows($fresult) == 0) {
					if ($table_mysql != 'asiakasalennus' and $table_mysql != 'asiakashinta' and $table_mysql != 'toimittajaalennus' and $table_mysql != 'toimittajahinta') {
						lue_data_echo(t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("Rivi� ei voida muuttaa, koska sit� ei l�ytynyt!")."</font> $valinta<br>");
						$tila = 'ohita';
					}
				}
				elseif ($rivi[$postoiminto] == 'POISTA') {

					// Sallitut taulut
					if (!$saakopoistaa) {
						lue_data_echo(t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("Rivin poisto ei sallittu!")."</font> $valinta<br>");
						$tila = 'ohita';
					}
					elseif (mysql_num_rows($fresult) == 0) {
						lue_data_echo(t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("Rivi� ei voida poistaa, koska sit� ei l�ytynyt!")."</font> $valinta<br>");
						$tila = 'ohita';
					}
				}
				elseif ($rivi[$postoiminto] != 'MUUTA' and $rivi[$postoiminto] != 'LISAA') {
					lue_data_echo(t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("Rivi� ei voida k�sitell� koska silt� puuttuu toiminto!")."</font> $valinta<br>");
					$tila = 'ohita';
				}
			}
			else {
				lue_data_echo(t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("Pakollista tietoa puuttuu/tiedot ovat virheelliset!")."</font> $valinta<br>");
			}

			// lis�t��n rivi
			if ($tila != 'ohita') {
				if ($rivi[$postoiminto] == 'LISAA') {
					if ($eiyhtiota == "") {
						$query = "INSERT into $table_mysql SET yhtio='$kukarow[yhtio]', laatija='$kukarow[kuka]', luontiaika=now(), muuttaja='$kukarow[kuka]', muutospvm=now() ";
					}
					elseif ($eiyhtiota == "EILAATIJAA") {
						$query = "INSERT INTO {$table_mysql} SET yhtio = '{$kukarow['yhtio']}' ";
					}
					elseif ($eiyhtiota == "TRIP") {
						$query = "INSERT into $table_mysql SET laatija='$kukarow[kuka]', luontiaika=now() ";
					}
				}

				if ($rivi[$postoiminto] == 'MUUTA') {
					if ($eiyhtiota == "") {
						$query = "UPDATE $table_mysql SET yhtio='$kukarow[yhtio]', muuttaja='$kukarow[kuka]', muutospvm=now() ";
	      			}
					elseif ($eiyhtiota == "EILAATIJAA") {
						$query = "UPDATE {$table_mysql} SET yhtio = '{$kukarow['yhtio']}' ";
					}
					elseif ($eiyhtiota == "TRIP") {
						$query = "UPDATE $table_mysql SET muuttaja='$kukarow[kuka]', muutospvm=now() ";
	      			}
				}

				if ($rivi[$postoiminto] == 'POISTA') {
					$query = "DELETE FROM $table_mysql ";
				}

				foreach ($taulunotsikot[$taulu] as $r => $otsikko) {

					//	N�it� ei koskaan lis�t�
					if (is_array($apu_sarakkeet) and in_array($otsikko, $apu_sarakkeet)) {
						continue;
					}

					if ($r != $postoiminto) {

						// Avainsanojen perheet kuntoon!
						if ($table_mysql == 'avainsana' and $rivi[$postoiminto] == 'LISAA' and $rivi[array_search("PERHE", $taulunotsikot[$taulut[$r]])] == "AUTOM") {

							$mpquery = "SELECT max(perhe)+1 max
										FROM avainsana";
							$vresult = pupe_query($mpquery);
							$vrow = mysql_fetch_assoc($vresult);

							$apu_ytunnus = $vrow['max'] + $tarkylisa;
							$tarkylisa++;

							$j = array_search("PERHE", $taulunotsikot[$taulut[$r]]);

							// P�ivitet��n generoitu arvo kaikkiin muuttujiin...
							$taulunrivit[$taulu][$eriviindex][$j] = $rivit[$eriviindex][$j] = $rivi[$j] = $apu_ytunnus;

							foreach ($taulunotsikot as $autotaulu => $autojoinit) {
								if (in_array("PERHE", $joinit) and $autotaulu != $taulut[$r] and $taulu == $joinattavat[$autotaulu]["PERHE"]) {
									$taulunrivit[$autotaulu][$eriviindex][array_search("PERHE", $taulunotsikot[$autotaulu])] = $apu_ytunnus;
								}
							}
						}

						$rivi[$r] = trim(addslashes($rivi[$r]));

						if (substr($trows[$table_mysql.".".$otsikko],0,7) == "decimal" or substr($trows[$table_mysql.".".$otsikko],0,4) == "real") {

							//korvataan decimal kenttien pilkut pisteill�...
							$rivi[$r] = str_replace(",", ".", $rivi[$r]);

							$desimaali_talteen = (float) $rivi[$r];

							// Jos MySQL kent�ss� on desimaaleja, py�ristet��n luku sallittuun tarkkuuteen
							if ($tdecimal[$table_mysql.".".$otsikko] > 0) {
								$rivi[$r] = round($rivi[$r], $tdecimal[$table_mysql.".".$otsikko]);
							}

							if ($desimaali_talteen != $rivi[$r]) {
								lue_data_echo(t("Huomio rivill�").": $rivilaskuri <font class='message'>".t("Luku py�ristettiin sallittuun tarkkuuteen")." $desimaali_talteen &raquo; $rivi[$r]</font><br>");
							}
						}

						if ((int) $tlength[$table_mysql.".".$otsikko] > 0 and strlen($rivi[$r]) > $tlength[$table_mysql.".".$otsikko] and ($table_mysql != "tuotepaikat" and $otsikko != "OLETUS" and $rivi[$r] != 'XVAIHDA')) {
							lue_data_echo(t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("VIRHE").": $otsikko ".t("kent�ss� on liian pitk� tieto")."!</font> $rivi[$r]: ".strlen($rivi[$r])." > ".$tlength[$table_mysql.".".$otsikko]."!<br>");
							$hylkaa++; // ei p�ivitet� t�t� rivi�
						}

						if ($table_mysql == 'tuotepaikat' and $otsikko == 'OLETUS') {
							// $tuoteno pit�s olla jo aktivoitu ylh��ll�
							// haetaan tuotteen varastopaikkainfo
							$tpque = "	SELECT sum(if (oletus='X',1,0)) oletus, sum(if (oletus='X',0,1)) regular
										from tuotepaikat where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
							$tpres = pupe_query($tpque);

							if (mysql_num_rows($tpres) == 0) {
								$rivi[$r] = "X"; // jos yht��n varastopaikkaa ei l�ydy, pakotetaan oletus
								lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Tuotteella")." '$tuoteno' ".t("ei ole yht��n varastopaikkaa, pakotetaan t�st� oletus").".<br>");
							}
							else {
								$tprow = mysql_fetch_array($tpres);
								if ($rivi[$r] == 'XVAIHDA' and $tprow['oletus'] > 0) {
									//vaihdetaan t�m� oletukseksi
									lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Tuotteelle")." '$tuoteno' ".t("Vaihdetaan annettu paikka oletukseksi").".<br>");
								}
								elseif ($rivi[$r] != '' and $tprow['oletus'] > 0) {
									$rivi[$r] = ""; // t�ll� tuotteella on jo oletuspaikka, nollataan t�m�
									lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Tuotteella")." '$tuoteno' ".t("on jo oletuspaikka, ei p�ivitetty oletuspaikkaa")."!<br>");
								}
								elseif ($rivi[$r] == '' and $tprow['oletus'] == 0) {
									$rivi[$r] = "X"; // jos yht��n varastopaikkaa ei l�ydy, pakotetaan oletus
									lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Tuotteella")." '$tuoteno' ".t("ei ole yht��n oletuspaikkaa! T�t� EI PIT�ISI tapahtua! Tehd��n nyt t�st� oletus").".<br>");
								}
							}
						}

						if ($table_mysql == 'tuote' and ($otsikko == 'EPAKURANTTI25PVM' or $otsikko == 'EPAKURANTTI50PVM' or $otsikko == 'EPAKURANTTI75PVM' or $otsikko == 'EPAKURANTTI100PVM')) {

							// $tuoteno pit�s olla jo aktivoitu ylh��ll�
							if (trim($rivi[$r]) != '' and trim($rivi[$r]) != '0000-00-00' and $otsikko == 'EPAKURANTTI25PVM') {
								$tee = "25paalle";
							}
							elseif (trim($rivi[$r]) == "peru") {
								$tee = "peru";
							}
							elseif ($tee == "") {
								$tee = "pois";
							}

							if (trim($rivi[$r]) != '' and trim($rivi[$r]) != '0000-00-00' and $otsikko == 'EPAKURANTTI50PVM') {
								$tee = "puolipaalle";
							}
							elseif (trim($rivi[$r]) == "peru") {
								$tee = "peru";
							}
							elseif ($tee == "") {
								$tee = "pois";
							}

							if (trim($rivi[$r]) != '' and trim($rivi[$r]) != '0000-00-00' and $otsikko == 'EPAKURANTTI75PVM') {
								$tee = "75paalle";
							}
							elseif (trim($rivi[$r]) == "peru") {
								$tee = "peru";
							}
							elseif ($tee == "") {
								$tee = "pois";
							}

							if (trim($rivi[$r]) != '' and trim($rivi[$r]) != '0000-00-00' and $otsikko == 'EPAKURANTTI100PVM') {
								$tee = "paalle";
							}
							elseif (trim($rivi[$r]) == "peru") {
								$tee = "peru";
							}
							elseif ($tee == "") {
								$tee = "pois";
							}

							// ei yritet� laittaa uusia tuotteita kurantiksi vaikka kent�t olisikin exceliss�
							if ($rivi[$postoiminto] == 'LISAA' and $tee == 'pois') {
								$tee = "";
							}

							$eilisataeikamuuteta = "joo";
						}

						if ($table_mysql == 'tuote' and ($otsikko == 'KUSTP' or $otsikko == 'KOHDE' or $otsikko == 'PROJEKTI') and $rivi[$r] != "") {
							// Kustannuspaikkarumba t�nnekin
							$ikustp_tsk = $rivi[$r];
							$ikustp_ok  = 0;

							if ($otsikko == "PROJEKTI") $kptyyppi = "P";
							if ($otsikko == "KOHDE")	$kptyyppi = "O";
							if ($otsikko == "KUSTP")	$kptyyppi = "K";

							if ($ikustp_tsk != "") {
								$ikustpq = "SELECT tunnus
											FROM kustannuspaikka
											WHERE yhtio = '$kukarow[yhtio]'
											and tyyppi = '$kptyyppi'
											and kaytossa != 'E'
											and nimi = '$ikustp_tsk'";
								$ikustpres = pupe_query($ikustpq);

								if (mysql_num_rows($ikustpres) == 1) {
									$ikustprow = mysql_fetch_assoc($ikustpres);
									$ikustp_ok = $ikustprow["tunnus"];
								}
							}

							if ($ikustp_tsk != "" and $ikustp_ok == 0) {
								$ikustpq = "SELECT tunnus
											FROM kustannuspaikka
											WHERE yhtio = '$kukarow[yhtio]'
											and tyyppi = '$kptyyppi'
											and kaytossa != 'E'
											and koodi = '$ikustp_tsk'";
								$ikustpres = pupe_query($ikustpq);

								if (mysql_num_rows($ikustpres) == 1) {
									$ikustprow = mysql_fetch_assoc($ikustpres);
									$ikustp_ok = $ikustprow["tunnus"];
								}
							}

							if (is_numeric($ikustp_tsk) and (int) $ikustp_tsk > 0 and $ikustp_ok == 0) {

								$ikustp_tsk = (int) $ikustp_tsk;

								$ikustpq = "SELECT tunnus
											FROM kustannuspaikka
											WHERE yhtio = '$kukarow[yhtio]'
											and tyyppi = '$kptyyppi'
											and kaytossa != 'E'
											and tunnus = '$ikustp_tsk'";
								$ikustpres = pupe_query($ikustpq);

								if (mysql_num_rows($ikustpres) == 1) {
									$ikustprow = mysql_fetch_assoc($ikustpres);
									$ikustp_ok = $ikustprow["tunnus"];
								}
							}

							if ($ikustp_ok > 0) {
								$rivi[$r] = $ikustp_ok;
								$rivit[$eriviindex][$r]	= $ikustp_ok;
							}
						}

						// tehd��n riville oikeellisuustsekkej�
						if ($table_mysql == 'sanakirja' and $otsikko == 'FI') {
							// jos ollaan mulkkaamassa RU ni tehd��n utf-8 -> latin-1 konversio FI kent�ll�
							 if (in_array("RU", $taulunotsikot[$taulu])) {
								$rivi[$r] = iconv("UTF-8", "ISO-8859-1", $rivi[$r]);
							}
						}

						// tehd��n riville oikeellisuustsekkej�
						if ($table_mysql == 'tuotteen_toimittajat' and $otsikko == 'TOIMITTAJA' and !in_array("LIITOSTUNNUS", $taulunotsikot[$taulu])) {

							$tpque = "	SELECT tunnus
										from toimi
										where yhtio	= '{$kukarow['yhtio']}'
										and ytunnus	= '{$rivi[$r]}'
										and tyyppi != 'P'";
							$tpres = pupe_query($tpque);

							if (mysql_num_rows($tpres) != 1) {
								$tpque = "	SELECT tunnus
											from toimi
											where yhtio	= '{$kukarow['yhtio']}'
											and ovttunnus = '{$rivi[$r]}'
											and ovttunnus != ''
											and tyyppi != 'P'";
								$tpres = pupe_query($tpque);
							}

							if (mysql_num_rows($tpres) != 1) {
								$tpque = "	SELECT tunnus
											from toimi
											where yhtio	= '{$kukarow['yhtio']}'
											and toimittajanro = '{$rivi[$r]}'
											and toimittajanro != ''
											and tyyppi != 'P'";
								$tpres = pupe_query($tpque);
							}

							if (mysql_num_rows($tpres) != 1) {
								lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Toimittajaa")." '$rivi[$r]' ".t("ei l�ydy! Tai samalla ytunnuksella l�ytyy useita toimittajia! Lis�� toimittajan tunnus LIITOSTUNNUS-sarakkeeseen. Rivi� ei p�ivitetty/lis�tty")."! ".t("TUOTENO")." = $tuoteno<br>");
								$hylkaa++; // ei p�ivitet� t�t� rivi�
							}
							else {
								$tpttrow = mysql_fetch_array($tpres);

								// Tarvitaan tarkista.inc failissa
								$toimi_liitostunnus = $tpttrow["tunnus"];

								if ($rivi[$postoiminto] != 'POISTA') {
									$query .= ", liitostunnus='$tpttrow[tunnus]' ";
								}

								$valinta .= " and liitostunnus='$tpttrow[tunnus]' ";
							}
						}
						elseif ($table_mysql == 'tuotteen_toimittajat' and $otsikko == 'LIITOSTUNNUS') {
							$tpque = "	SELECT tunnus
										from toimi
										where yhtio	= '$kukarow[yhtio]'
										and tunnus	= '$rivi[$r]'
										and tyyppi != 'P'";
							$tpres = pupe_query($tpque);

							if (mysql_num_rows($tpres) != 1) {
								lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Toimittajaa")." '$rivi[$r]' ".t("ei l�ydy! Rivi� ei p�ivitetty/lis�tty")."! ".t("TUOTENO")." = $tuoteno<br>");
								$hylkaa++; // ei p�ivitet� t�t� rivi�
							}
							else {
								$tpttrow = mysql_fetch_array($tpres);

								// Tarvitaan tarkista.inc failissa
								$toimi_liitostunnus = $tpttrow["tunnus"];
								$valinta .= " and liitostunnus='$tpttrow[tunnus]' ";
							}
						}

						//tarkistetaan asiakasalennus ja asiakashinta juttuja
						if ($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta' or $table_mysql == 'toimittajaalennus' or $table_mysql == 'toimittajahinta') {
							if ($otsikko == 'RYHMA' and $rivi[$r] != '') {
								$chryhma = $rivi[$r];
							}

							// Asiakas sarakkaassa on tunnus
							if ($otsikko == 'ASIAKAS' and $asiakkaanvalinta == '1' and $rivi[$r] != "") {
								$chasiakas = $rivi[$r];
							}

							// Asiakas sarakkaassa on toim_ovttunnus (ytunnus pit�� olla setattu) (t�m� on oletus er�ajossa)
							if ($otsikko == 'ASIAKAS' and $asiakkaanvalinta != '1' and $rivi[$r] != "") {
								$etsitunnus = " SELECT tunnus
												FROM asiakas
												USE INDEX (toim_ovttunnus_index)
												WHERE yhtio = '$kukarow[yhtio]'
												AND toim_ovttunnus = '$rivi[$r]'
												AND toim_ovttunnus != ''
												AND ytunnus != ''
												AND ytunnus = '".$rivi[array_search("YTUNNUS", $taulunotsikot[$taulu])]."'";
								$etsiresult = pupe_query($etsitunnus);

								if (mysql_num_rows($etsiresult) == 1) {
									$etsirow = mysql_fetch_assoc($etsiresult);

									// Vaihdetaan asiakas sarakkeeseen tunnus sek� ytunnus tulee nollata (koska ei saa olla molempia)
									$chasiakas = $etsirow['tunnus'];
									$chytunnus = "";
									$rivi[$r] = $etsirow['tunnus'];
									$rivi[array_search("YTUNNUS", $taulunotsikot[$taulu])] = "";
								}
								else {
									$chasiakas = -1;
								}
							}

							if ($otsikko == 'TOIMITTAJA' and (int) $rivi[$r] > 0) {
								$chtoimittaja = $rivi[$r];
							}

							if ($otsikko == 'TUOTENO' and $rivi[$r] != '') {
								$chtuoteno = trim($rivi[$r]);
							}

							if ($otsikko == 'ASIAKAS_RYHMA' and $rivi[$r] != '') {
								$chasiakas_ryhma = $rivi[$r];
							}

							if ($otsikko == 'YTUNNUS' and $rivi[$r] != '') {
								$chytunnus = trim($rivi[$r]);
							}

							if ($otsikko == 'ALKUPVM' and $rivi[$r] != '') {
								$chalkupvm = $rivi[$r];
							}

							if ($otsikko == 'LOPPUPVM' and $rivi[$r] != '') {
								$chloppupvm = $rivi[$r];
							}

							if ($otsikko == 'ASIAKAS_SEGMENTTI' and $segmenttivalinta == '1' and (int) $rivi[$r] > 0) {
								// 1 tarkoittaa dynaamisen puun KOODIA
								$etsitunnus = " SELECT tunnus FROM dynaaminen_puu WHERE yhtio='$kukarow[yhtio]' AND laji='asiakas' AND koodi='$rivi[$r]'";
								$etsiresult = pupe_query($etsitunnus);
								$etsirow = mysql_fetch_assoc($etsiresult);

								$chsegmentti = $etsirow['tunnus'];
							}

							if ($otsikko == 'ASIAKAS_SEGMENTTI' and $segmenttivalinta == '2' and (int) $rivi[$r] > 0) {
								// 2 tarkoittaa dynaamisen puun TUNNUSTA
								$chsegmentti = $rivi[$r];
							}

							if ($otsikko == 'PIIRI' and $rivi[$r] != '') {
								$chpiiri = $rivi[$r];
							}

							if ($otsikko == 'MINKPL' and (int) $rivi[$r] > 0) {
								$chminkpl = (int) $rivi[$r];
							}

							if ($otsikko == 'MAXKPL' and (int) $rivi[$r] > 0) {
								$chmaxkpl = (int) $rivi[$r];
							}

							if ($otsikko == 'ALENNUSLAJI' and (int) $rivi[$r] > 0) {
								$chalennuslaji = (int) $rivi[$r];
							}

							if ($otsikko == 'MONIKERTA' and $rivi[$r] != '') {
								$chmonikerta = trim($rivi[$r]);
							}
						}

						//tarkistetaan kuka juttuja
						if ($table_mysql == 'kuka') {
							if ($otsikko == 'SALASANA' and $rivi[$r] != '') {
								$rivi[$r] = md5(trim($rivi[$r]));
							}

							if ($otsikko == 'OLETUS_ASIAKAS' and $rivi[$r] != '') {
								$xquery = "	SELECT tunnus
											FROM asiakas
											WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$rivi[$r]'";
								$xresult = pupe_query($xquery);

								if (mysql_num_rows($xresult) == 0) {
									$xquery = "	SELECT tunnus
												FROM asiakas
												WHERE yhtio = '$kukarow[yhtio]' and ytunnus = '$rivi[$r]'";
									$xresult = pupe_query($xquery);
								}

								if (mysql_num_rows($xresult) == 0) {
									$xquery = "	SELECT tunnus
												FROM asiakas
												WHERE yhtio = '$kukarow[yhtio]' and asiakasnro = '$rivi[$r]'";
									$xresult = pupe_query($xquery);
								}

								if (mysql_num_rows($xresult) == 0) {
									lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Asiakasta")." '$rivi[$r]' ".t("ei l�ydy! Rivi� ei p�ivitetty/lis�tty")."! $otsikko = $rivi[$r]<br>");
									$hylkaa++; // ei p�ivitet� t�t� rivi�
								}
								elseif (mysql_num_rows($xresult) > 1) {
									lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Asiakasta")." '$rivi[$r]' ".t("l�ytyi monia! Rivi� ei p�ivitetty/lis�tty")."! $otsikko = $rivi[$r]<br>");
									$hylkaa++; // ei p�ivitet� t�t� rivi�
								}
								else {
									$x2row = mysql_fetch_array($xresult);
									$rivi[$r] = $x2row['tunnus'];
								}
							}
						}

						//muutetaan rivi�, silloin ei saa p�ivitt�� pakollisia kentti�
						if ($rivi[$postoiminto] == 'MUUTA' and (!in_array($otsikko, $pakolliset) or $table_mysql == 'auto_vari_korvaavat' or $table_mysql == 'asiakashinta' or $table_mysql == 'asiakasalennus' or $table_mysql == 'toimittajahinta' or $table_mysql == 'toimittajasalennus' or ($table_mysql == "tuotepaikat" and $otsikko == "OLETUS" and $rivi[$r] == 'XVAIHDA'))) {
							///* T�ss� on kaikki oikeellisuuscheckit *///
							if (($table_mysql == 'asiakashinta' and $otsikko == 'HINTA') or ($table_mysql == 'toimittajahinta' and $otsikko == 'HINTA')) {
								if ($rivi[$r] != 0 and $rivi[$r] != '') {
									$query .= ", $otsikko = '$rivi[$r]' ";
								}
								elseif ($rivi[$r] == 0) {
									lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Hintaa ei saa nollata!")."<br>");
								}
							}
							elseif ($table_mysql == 'avainsana' and $otsikko == 'SELITE') {
								if ($rivi[$r] != 0 and $rivi[$r] != '') {
									$query .= ", $otsikko = '$rivi[$r]' ";
								}
								elseif ($rivi[$r] == 0) {
									lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Selite ei saa olla tyhj�!")."<br>");
								}
							}
							elseif ($table_mysql=='tuotepaikat' and $otsikko == 'OLETUS' and $rivi[$r] == 'XVAIHDA') {
								//vaihdetaan t�m� oletukseksi
								$rivi[$r] = "X"; // pakotetaan oletus

								$tpupque = "UPDATE tuotepaikat SET oletus = '' where yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";

								$query .= ", $otsikko = '$rivi[$r]' ";
							}
							elseif ($table_mysql=='tuotepaikat' and $otsikko == 'OLETUS') {
								//echo t("Virhe rivill�").": $rivilaskuri Oletusta ei voi muuttaa!<br>";
							}
							else {
								if ($eilisataeikamuuteta == "") {
									$query .= ", $otsikko = '$rivi[$r]' ";
								}
					  		}
						}

						//lis�t��n rivi
						if ($rivi[$postoiminto] == 'LISAA') {
							if ($table_mysql == 'tuotepaikat' and $otsikko == 'OLETUS' and $rivi[$r] == 'XVAIHDA') {
								//vaihdetaan t�m� oletukseksi
								$rivi[$r] = "X"; // pakotetaan oletus

								$tpupque = "UPDATE tuotepaikat SET oletus = '' where yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";

								$query .= ", $otsikko = '$rivi[$r]' ";
							}
							elseif (substr($taulu, 0, 11) == 'puun_alkio_') {
								if ($otsikko == 'PUUN_TUNNUS') {
									if ($dynaamisen_taulun_liitos == 'koodi') {
										$query_x = "	SELECT tunnus
														FROM dynaaminen_puu
														WHERE yhtio = '{$kukarow['yhtio']}'
														AND laji = '{$table_tarkenne}'
														AND koodi = '".trim($rivi[$r])."'";
										$koodi_tunnus_res = pupe_query($query_x);
										$koodi_tunnus_row = mysql_fetch_assoc($koodi_tunnus_res);

										$query .= ", puun_tunnus = '{$koodi_tunnus_row['tunnus']}' ";
									}
									else {
										$query .= ", puun_tunnus = '{$rivi[$r]}' ";
									}
								}
								else {
									$query .= ", $otsikko = '".trim($rivi[$r])."' ";
								}
							}
							elseif ($eilisataeikamuuteta == "") {
								$query .= ", $otsikko = '$rivi[$r]' ";
							}
						}
					}
				}

				// tarkistetaan asiakasalennus ja asiakashinta keisseiss� onko t�llanen rivi jo olemassa, sek� toimittajahinta ett� toimittajaalennus
				if ($hylkaa == 0 and ($chasiakas != 0 or $chasiakas_ryhma != '' or $chytunnus != '' or $chpiiri != '' or $chsegmentti != 0 or $chtoimittaja != '') and ($chryhma != '' or $chtuoteno != '') and ($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta' or $table_mysql == 'toimittajahinta' or $table_mysql == 'toimittajaalennus')) {
					if ($chasiakas_ryhma != '') {
						$and .= " and asiakas_ryhma = '$chasiakas_ryhma'";
					}
					if ($chytunnus != '') {
						$and .= " and ytunnus = '$chytunnus'";
					}
					if ($chasiakas != 0) {
						$and .= " and asiakas = '$chasiakas'";
					}
					if ($chsegmentti > 0) {
						$and .= " and asiakas_segmentti = '$chsegmentti'";
					}
					if ($chpiiri != '') {
						$and .= " and piiri = '$chpiiri'";
					}

					if ($chryhma != '') {
						$and .= " and ryhma = '$chryhma'";
					}
					if ($chtuoteno != '') {
						$and .= " and tuoteno = '$chtuoteno'";
					}

					if ($chminkpl != 0) {
						$and .= " and minkpl = '$chminkpl'";
					}

					if ($chtoimittaja != '') {
						$and .= " and toimittaja = '$chtoimittaja'";
					}

					if ($table_mysql == 'asiakashinta' or $table_mysql == 'toimittajahinta') {
						if ($chmaxkpl != 0) {
							$and .= " and maxkpl = '$chmaxkpl'";
						}
					}

					if ($table_mysql == 'asiakasalennus' or $table_mysql == 'toimittajaalennus') {

						if ($chmonikerta != '') {
							$and .= " and monikerta != ''";
						}
						else {
							$and .= " and monikerta  = ''";
						}

						if ($chalennuslaji == 0) {
							$and .= " and alennuslaji = '1'";
						}
						elseif ($chalennuslaji != 0) {
							$and .= " and alennuslaji = '$chalennuslaji'";
						}
					}

					$and .= " and alkupvm = '$chalkupvm' and loppupvm = '$chloppupvm'";
				}

				if (substr($taulu, 0, 11) == 'puun_alkio_' and $rivi[$postoiminto] != 'POISTA') {
					$query .= " , laji = '{$table_tarkenne}' ";
				}

				// Ollaan lis��m�ss� tietuetta, katsotaan ett� on kaikki oletukset MySQL aliaksista
				// Taulun oletusarvot, jos ollaan lis��m�ss� uutta tietuetta
				if ($rivi[$postoiminto] == "LISAA") {
					foreach ($oletukset as $oletus_kentta => $oletus_arvo) {
						if (stripos($query, ", $oletus_kentta = ") === FALSE) {
							$query .= ", $oletus_kentta = '$oletus_arvo' ";
						}
					}
				}

				// lis�t��n tuote, mutta ei olla speksattu alvia ollenkaan...
				if ($rivi[$postoiminto] == 'LISAA' and $table_mysql == 'tuote' and stripos($query, ", alv = ") === FALSE) {
					$query .= ", alv = '$oletus_alvprossa' ";
				}

				// Jos on asiakas-taulu, niin populoidaan kaikkien dropdown-menujen arvot, mik�li niit� ei ole annettu.
				if ($table_mysql == 'asiakas' and $rivi[$postoiminto] == 'LISAA') {
					if (stripos($query, ", maksuehto = ") === FALSE) {
						$select_query = "SELECT * FROM maksuehto WHERE yhtio = '{$kukarow['yhtio']}' AND kaytossa='' ORDER BY jarjestys, teksti limit 1";
						$select_result = pupe_query($select_query);
						$select_row = mysql_fetch_assoc($select_result);

						$query .= ", maksuehto = '{$select_row["tunnus"]}' ";
					}

					if (stripos($query, ", toimitustapa = ") === FALSE) {
						$select_query = "SELECT * FROM toimitustapa WHERE yhtio = '{$kukarow['yhtio']}' ORDER BY jarjestys, selite limit 1";
						$select_result = pupe_query($select_query);
						$select_row = mysql_fetch_assoc($select_result);

						$query .= ", toimitustapa = '{$select_row["selite"]}' ";
					}

					if (stripos($query, ", valkoodi = ") === FALSE) {
						$query .= ", valkoodi = '{$yhtiorow["valkoodi"]}' ";
					}

					if (stripos($query, ", kerayspoikkeama = ") === FALSE) {
						$query .= ", kerayspoikkeama = '0' ";
					}

					if (stripos($query, ", laskutusvkopv = ") === FALSE) {
						$query .= ", laskutusvkopv = '0' ";
					}

					if (stripos($query, ", laskutyyppi = ") === FALSE) {
						$query .= ", laskutyyppi = '-9' ";
					}

					if (stripos($query, ", maa = ") === FALSE) {
						$query .= ", maa = '{$yhtiorow["maa"]}' ";
					}

					if (stripos($query, ", kansalaisuus = ") === FALSE) {
						$query .= ", kansalaisuus = '{$yhtiorow["kieli"]}' ";
					}

					if (stripos($query, ", laskutus_maa = ") === FALSE) {
						$query .= ", laskutus_maa = '{$yhtiorow["maa"]}' ";
					}

					if (stripos($query, ", toim_maa = ") === FALSE) {
						$query .= ", toim_maa = '{$yhtiorow["maa"]}' ";
					}

					if (stripos($query, ", kolm_maa = ") === FALSE) {
						$query .= ", kolm_maa = '{$yhtiorow["maa"]}' ";
					}

					if (stripos($query, ", kieli = ") === FALSE) {
						$query .= ", kieli = '{$yhtiorow["kieli"]}' ";
					}

					if (stripos($query, ", chn = ") === FALSE) {
						$query .= ", chn = '100' ";
					}

					if (stripos($query, ", alv = ") === FALSE) {
						//yhti�n oletusalvi!
						$wquery = "SELECT selite from avainsana where yhtio='$kukarow[yhtio]' and laji = 'alv' and selitetark != ''";
						$wtres  = pupe_query($wquery);
						$wtrow  = mysql_fetch_array($wtres);

						$query .= ", alv = '{$wtrow["selite"]}' ";
					}

					if (stripos($query, ", asiakasnro = ") === FALSE and $yhtiorow["automaattinen_asiakasnumerointi"] != "") {

						if ($yhtiorow["asiakasnumeroinnin_aloituskohta"] != "") {
							$apu_asiakasnumero = $yhtiorow["asiakasnumeroinnin_aloituskohta"];
						}
						else {
							$apu_asiakasnumero = 0;
						}

						$select_query = "	SELECT MAX(asiakasnro+0) asiakasnro
											FROM asiakas USE INDEX (asno_index)
											WHERE yhtio = '{$kukarow["yhtio"]}'
											AND asiakasnro+0 >= $apu_asiakasnumero";
						$select_result = pupe_query($select_query);
						$select_row = mysql_fetch_assoc($select_result);

						if ($select_row['asiakasnro'] != '') {
							$vapaa_asiakasnro = $select_row['asiakasnro'] + 1;
						}

						$query .= ", asiakasnro = '$vapaa_asiakasnro' ";
					}

				}

				if ($rivi[$postoiminto] == 'MUUTA') {
					if (($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta' or $table_mysql == 'toimittajahinta' or $table_mysql == 'toimittajaalennus') and $and != "") {
						$query .= " WHERE yhtio = '$kukarow[yhtio]'";
						$query .= $and;
					}
					else {
						$query .= " WHERE ".$valinta;
					}

					$query .= " ORDER BY tunnus";
				}

				if ($rivi[$postoiminto] == 'POISTA') {
					if (($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta' or $table_mysql == 'toimittajahinta' or $table_mysql == 'toimittajaalennus') and $and != "") {
						$query .= " WHERE yhtio = '$kukarow[yhtio]'";
						$query .= $and;
					}
					else {
						$query .= " WHERE ".$valinta;
					}

					$query .= " LIMIT 1 ";
				}

				//	Tarkastetaan tarkistarivi.incia vastaan..
				//	Generoidaan oikeat arrayt
				$errori 		= "";
				$t 				= array();
				$virhe 			= array();
				$poistolukko	= "LUEDATA";

				// Jos on uusi rivi niin kaikki lukot on auki
				if ($rivi[$postoiminto] == 'LISAA') {
					$poistolukko = "";
				}

				//	Otetaan talteen query..
				$lue_data_query = $query;

				$tarq = "	SELECT *
							FROM $table_mysql";
				if ($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta' or $table_mysql == 'toimittajahinta' or $table_mysql == 'toimittajaalennus') {
					$tarq .= " WHERE yhtio = '$kukarow[yhtio]'";
					$tarq .= $and;
				}
				else {
					$tarq .= " WHERE ".$valinta;
				}
				$result = pupe_query($tarq);

				if ($rivi[$postoiminto] == 'MUUTA' and mysql_num_rows($result) != 1) {
					lue_data_echo(t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("P�ivitett�v�� rivi� ei l�ytynyt")."!</font><br>");
				}
				elseif ($rivi[$postoiminto] == 'LISAA' and mysql_num_rows($result) != 0) {

					if ($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta' or $table_mysql == 'toimittajahinta' or $table_mysql == 'toimittajaalennus') {
						lue_data_echo(t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("Rivi� ei lis�tty, koska se l�ytyi jo j�rjestelm�st�")."!</font><br>");
					}
				}
				else {
					$tarkrow = mysql_fetch_array($result);
					$tunnus = $tarkrow["tunnus"];

					// Teghd��n pari injektiota tarkrow-arrayseen
					$tarkrow["luedata_from"] = "LUEDATA";
					$tarkrow["luedata_toiminto"] = $rivi[$postoiminto];

					// Tehd��n oikeellisuustsekit
					for ($i=1; $i < mysql_num_fields($result); $i++) {

						// Tarkistetaan saako k�ytt�j� p�ivitt�� t�t� kentt��
						$Lindexi = array_search(strtoupper(mysql_field_name($result, $i)), $taulunotsikot[$taulu]);

						if (strtoupper(mysql_field_name($result, $i)) == 'TUNNUS') {
							$tassafailissa = TRUE;
						}
						elseif ($Lindexi !== FALSE and array_key_exists($Lindexi, $rivit[$eriviindex])) {
							$t[$i] = $rivit[$eriviindex][$Lindexi];

							// T�m� rivi on exceliss�
							$tassafailissa = TRUE;
						}
						else {
							$t[$i] = isset($tarkrow[mysql_field_name($result, $i)]) ? $tarkrow[mysql_field_name($result, $i)] : "";

							// T�m� rivi ei oo exceliss�
							$tassafailissa = FALSE;
						}

						$funktio = $table_mysql."tarkista";

						if (!function_exists($funktio)) {
							@include("inc/$funktio.inc");
						}

						unset($virhe);

						if (function_exists($funktio)) {
							$funktio($t, $i, $result, $tunnus, $virhe, $tarkrow);
						}

						// Ignoorataan virhe jos se ei koske t�ss� failissa olutta saraketta
						if ($tassafailissa and isset($virhe[$i]) and $virhe[$i] != "") {
							switch ($table_mysql) {
								case "tuote":
									$virheApu = t("Tuote")." ".$tarkrow["tuoteno"].": ";
									break;
								default:
									$virheApu = "";
							}

							lue_data_echo(t("Virhe rivill�").": $rivilaskuri <font class='error'>$virheApu".mysql_field_name($result, $i).": ".$virhe[$i]." (".$t[$i].")</font><br>");
							$errori = 1;
						}
					}

					if ($errori != "") {
						$hylkaa++;
					}

					//	Palautetaan vanha query..
					$query = $lue_data_query;

					if ($hylkaa == 0) {

						// Haetaan rivi niin kuin se oli ennen muutosta
						$syncquery = "	SELECT *
										FROM $table_mysql";

						if ($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta' or $table_mysql == 'toimittajahinta' or $table_mysql == 'toimittajaalennus') {
							$syncquery .= " WHERE yhtio = '$kukarow[yhtio]'";
							$syncquery .= $and;
						}
						else {
							$syncquery .= " WHERE ".$valinta;
						}
						$syncres = pupe_query($syncquery);
						$syncrow = mysql_fetch_array($syncres);

						// tuotepaikkojen oletustyhjennysquery uskalletaan ajaa vasta t�ss�
						if ($tpupque != '') {
							$tpupres = pupe_query($tpupque);
						}

						$tpupque = "";

						// Itse lue_datan p�ivitysquery
						$iresult = pupe_query($query);

						// Synkronoidaan
						if ($rivi[$postoiminto] == 'LISAA') {
							$tunnus = mysql_insert_id();
						}
						else {
							$tunnus = $syncrow["tunnus"];
						}

						synkronoi($kukarow["yhtio"], $table_mysql, $tunnus, $syncrow, "");

						// tehd��n ep�kunrattijutut
						if ($tee == "paalle" or $tee == "25paalle" or $tee == "puolipaalle" or $tee == "75paalle" or $tee == "pois" or $tee == "peru") {
							require("epakurantti.inc");
						}

						$lask++;
					}
				}
			}

			// Meill� oli joku virhe
			if ($tila == 'ohita' or $hylkaa > 0) {
				$api_status = FALSE;
				$lue_data_virheelliset_rivit[$rivilaskuri-1] = $excelrivit[$rivilaskuri-1];
			}
		}

		lue_data_echo("<br><font class='message'>".t("P�ivitettiin")." $lask ".t("rivi�")."!</font><br><br>");

		// Kirjoitetaan LOG fileen lopputagi, jotta tiedet��n ett� ajo on valmis
		if ($lue_data_output_file != "") {
			lue_data_echo("## LUE-DATA-EOF ##");

			// Kirjoitetaan viel� loppuun virheelliset rivit
			if (count($lue_data_virheelliset_rivit) > 0) {

				if (include('Spreadsheet/Excel/Writer.php')) {

					$workbook = new Spreadsheet_Excel_Writer($lue_data_err_file);
					$workbook->setVersion(8);
					$worksheet = $workbook->addWorksheet(t('Virheelliset rivit'));

					$format_bold = $workbook->addFormat();
					$format_bold->setBold();

					$excelrivi = 0;
					$excelsarake = 0;

					$worksheet->write($excelrivi, $excelsarake++, ucfirst(t("Alkuper�inen rivinumero")), $format_bold);

					foreach ($excelrivit[0] as $otsikko) {
						$worksheet->write($excelrivi, $excelsarake++, ucfirst($otsikko), $format_bold);
					}

					$excelrivi++;
					$excelsarake = 0;

					foreach ($lue_data_virheelliset_rivit as $rivinro => $lue_data_virheellinen_rivi) {
						$worksheet->writeNumber($excelrivi, $excelsarake++, ($rivinro+1));

						foreach ($lue_data_virheellinen_rivi as $lue_data_virheellinen_sarake) {
							$worksheet->writeString($excelrivi, $excelsarake++, $lue_data_virheellinen_sarake);
						}

						$excelrivi++;
						$excelsarake = 0;
					}

					// We need to explicitly close the workbook
					$workbook->close();
				}
			}
		}
	}
}

lue_data_echo("<br>".$lue_data_output_text, true);

if (!$cli and !isset($api_kentat)) {
	// Taulut, jota voidaan k�sitell�
	$taulut = array(
		'abc_parametrit'                  => 'ABC-parametrit',
		'asiakas'                         => 'Asiakas',
		'asiakasalennus'                  => 'Asiakasalennukset',
		'asiakashinta'                    => 'Asiakashinnat',
		'asiakaskommentti'                => 'Asiakaskommentit',
		'asiakkaan_avainsanat'            => 'Asiakkaan avainsanat',
		'avainsana'                       => 'Avainsanat',
		'budjetti'                        => 'Budjetti',
		'etaisyydet'                      => 'Et�isyydet varastosta',
		'extranet_kayttajan_lisatiedot'   => 'Extranet-k�ytt�j�n lis�tietoja',
		'hinnasto'                        => 'Hinnasto',
		'kalenteri'                       => 'Kalenteritietoja',
		'kuka'                            => 'K�ytt�j�tietoja',
		'kustannuspaikka'                 => 'Kustannuspaikat',
		'liitetiedostot'                  => 'Liitetiedostot',
		'maksuehto'                       => 'Maksuehto',
		'pakkaus'                         => 'Pakkaustiedot',
		'perusalennus'                    => 'Perusalennukset',
		'rahtikirjanumero'				  => 'LOGY-rahtikirjanumerot',
		'rahtimaksut'                     => 'Rahtimaksut',
		'rahtisopimukset'                 => 'Rahtisopimukset',
		'rekisteritiedot'                 => 'Rekisteritiedot',
		'sanakirja'                       => 'Sanakirja',
		'sarjanumeron_lisatiedot'         => 'Sarjanumeron lis�tiedot',
		'taso'                            => 'Tilikartan rakenne',
		'tili'                            => 'Tilikartta',
		'todo'                            => 'Todo-lista',
		'toimi'                           => 'Toimittaja',
		'toimitustapa'                    => 'Toimitustavat',
		'toimitustavan_lahdot'            => 'Toimitustavan l�hd�t',
		'lahdot'            			  => 'L�hd�t',
		'tullinimike'                     => 'Tullinimikeet',
		'tuote'                           => 'Tuote',
		'tuotepaikat'                     => 'Tuotepaikat',
		'tuoteperhe'                      => 'Tuoteperheet',
		'tuotteen_alv'                    => 'Tuotteiden ulkomaan ALV',
		'tuotteen_avainsanat'             => 'Tuotteen avainsanat',
		'tuotteen_orginaalit'             => 'Tuotteiden originaalit',
		'tuotteen_toimittajat'            => 'Tuotteen toimittajat',
		'vak'                             => 'VAK-tietoja',
		'varaston_hyllypaikat'            => 'Varaston hyllypaikat',
		'yhteyshenkilo'                   => 'Yhteyshenkil�t',
		'toimittajahinta'                 => 'Toimittajan hinnat',
		'toimittajaalennus'               => 'Toimittajan alennukset',
	);

	// Lis�t��n dynaamiset tiedot
	$dynaamiset_avainsanat_result = t_avainsana('DYNAAMINEN_PUU', '', " and selite != '' ");
	$dynaamiset_avainsanat = "";

	while ($dynaamiset_avainsanat_row = mysql_fetch_assoc($dynaamiset_avainsanat_result)) {
		$taulut["puun_alkio_".strtolower($dynaamiset_avainsanat_row['selite'])] = "Dynaaminen_".strtolower($dynaamiset_avainsanat_row['selite']);
		if ($table == 'puun_alkio_'.strtolower($dynaamiset_avainsanat_row['selite'])) {
			$dynaamiset_avainsanat = 'puun_alkio_'.strtolower($dynaamiset_avainsanat_row['selite']);
		}
	}

	// Yhti�kohtaisia
	if ($kukarow['yhtio'] == 'mast') {
		$taulut['auto_vari']              = 'Autov�ri-datat';
		$taulut['auto_vari_tuote']        = 'Autov�ri-v�rikirja';
		$taulut['auto_vari_korvaavat']    = 'Autov�ri-korvaavat';
	}

	if ($kukarow['yhtio'] == 'artr' or $kukarow['yhtio'] == 'allr') {
		$taulut['autodata']                        = 'Autodatatiedot';
		$taulut['autodata_tuote']                  = 'Autodata tuotetiedot';
		$taulut['yhteensopivuus_auto']             = 'Yhteensopivuus automallit';
		$taulut['yhteensopivuus_auto_2']           = 'Yhteensopivuus automallit 2';
		$taulut['yhteensopivuus_mp']               = 'Yhteensopivuus mp-mallit';
		$taulut['yhteensopivuus_rekisteri']        = 'Yhteensopivuus rekisterinumerot';
		$taulut['yhteensopivuus_tuote']            = 'Yhteensopivuus tuotteet';
		$taulut['yhteensopivuus_tuote_lisatiedot'] = 'Yhteensopivuus tuotteet lis�tiedot';
	}

	// Taulut aakkosj�rjestykseen
	asort($taulut);

	// Selectoidaan aktiivi
	$sel = array_fill_keys(array($table), " selected") + array_fill_keys($taulut, '');

	echo "<form method='post' name='sendfile' enctype='multipart/form-data'>";
	echo "<input type='hidden' name='tee' value='file'>";
	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Valitse tietokannan taulu").":</th>";
	echo "<td>";
	echo "<select name='table' onchange='submit();'>";

	foreach ($taulut as $taulu => $nimitys) {
		echo "<option value='$taulu' {$sel[$taulu]}>".t($nimitys)."</option>";
	}

	echo "</select>";
	echo "</td>";
	echo "</tr>";

	if (in_array($table, array("yhteyshenkilo", "asiakkaan_avainsanat", "kalenteri"))) {
		echo "<tr><th>".t("Ytunnus-tarkkuus").":</th>
					<td><select name='ytunnustarkkuus'>
					<option value=''>".t("P�ivitet��n vain, jos Ytunnuksella l�ytyy yksi rivi")."</option>
					<option value='2'>".t("P�ivitet��n kaikki sy�tetyll� Ytunnuksella l�ytyv�t asiakkaat")."</option>
					</select></td>
			</tr>";
	}

	if (trim($dynaamiset_avainsanat) != '' and $table == $dynaamiset_avainsanat) {
		echo "	<tr><th>",t("Valitse liitos"),":</th>
					<td><select name='dynaamisen_taulun_liitos'>";

		if ($table == 'puun_alkio_asiakas') {
			echo "	<option value=''>",t("Asiakkaan tunnus"),"</option>
					<option value='ytunnus'>",t("Asiakkaan ytunnus"),"</option>
					<option value='toim_ovttunnus'>",t("Asiakkaan toimitusosoitteen ovttunnus"),"</option>
					<option value='asiakasnro'>",t("Asiakkaan asiakasnumero"),"</option>";
		}
		else {
			echo "	<option value=''>",t("Puun alkion tunnus"),"</option>
					<option value='koodi'>",t("Puun alkion koodi"),"</option>";
		}

		echo "</select></td></tr>";
	}

	if (in_array($table, array("asiakasalennus", "asiakashinta"))) {
		echo "<tr><th>".t("Segmentin valinta").":</th>
					<td><select name='segmenttivalinta'>
					<option value='1'>".t("Valitaan k�ytett�v�ksi asiakas-segmentin koodia")."</option>
					<option value='2'>".t("Valitaan k�ytett�v�ksi asiakas-segmentin tunnusta ")."</option>
					</select></td>
			</tr>";
		echo "<tr><th>".t("Asiakkaan valinta").":</th>
					<td><select name='asiakkaanvalinta'>
					<option value='1'>".t("Asiakas-sarakkeessa asiakkaan tunnus")."</option>
					<option value='2'>".t("Asiakas-sarakkeessa asiakkaan toim_ovttunnus")."</option>
					</select></td>
			</tr>";
	}

	if ($table == "extranet_kayttajan_lisatiedot") {
		echo "<tr><th>".t("Liitostunnus").":</th>
				<td><select name='liitostunnusvalinta'>
				<option value='1'>".t("Liitostunnus-sarakkeessa liitostunnus")."</option>
				<option value='2'>".t("Liitostunnus-sarakkeessa k�ytt�j�nimi")."</option>
				</select></td>
		</tr>";
	}

	echo "	<tr><th>".t("Valitse tiedosto").":</th>
				<td><input name='userfile' type='file'></td>
			<td class='back'><input type='submit' name='laheta' value='".t("L�het�")."'></td>
			</tr>

			</table>
		</form>
		<br>";
}

if (!isset($api_kentat)) require ("inc/footer.inc");

?>
