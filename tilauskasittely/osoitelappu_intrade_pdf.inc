<?php

// tarvitaan tilauksen tunnus muutujassa $tunnus
// printterin komento muuttujassa $oslapp
// $kukarow[yhtio] jostain saadaan yhtio
// $yhtiorow array josta saada l�hett�j�n tiedot

if ($phpnimi != "rahtikirja_custom.php") {
	//Tulostetaan standardi kolliosoitelappu
	$query = "	SELECT lasku.*, if(yhteyshenkilo.nimi!='',concat_ws(' / ', yhteyshenkilo.nimi,if(yhteyshenkilo.gsm!='',yhteyshenkilo.gsm,if(yhteyshenkilo.puh!='',yhteyshenkilo.puh,NULL))),'') yhteyshenkilo
				FROM lasku
				LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus
				LEFT JOIN yhteyshenkilo ON yhteyshenkilo.yhtio=laskun_lisatiedot.yhtio and yhteyshenkilo.tunnus=laskun_lisatiedot.yhteyshenkilo_tekninen and yhteyshenkilo.tyyppi = 'A'
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.tunnus = '$tunnus'";
	$tempr = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_assoc($tempr);

	// K�ytet��nk� VAK-tietokantaa
	if ($yhtiorow["vak_kasittely"] != "") {
		$vakselect = "concat_ws(' ', concat('UN',yk_nro), nimi_ja_kuvaus, lipukkeet, pakkausryhma)";
		$vakjoin   = "JOIN vak ON tuote.yhtio = vak.yhtio and tuote.vakkoodi = vak.tunnus";
	}
	else {
		$vakselect = "tuote.vakkoodi";
		$vakjoin   = "";
	}

	// Haetaan kaikki vakkoodit arrayseen
	$query = "	SELECT group_concat(distinct $vakselect SEPARATOR ', ') vakkoodi
				FROM tilausrivi
				JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.vakkoodi not in ('','0'))
				$vakjoin
				where tilausrivi.otunnus = '$laskurow[tunnus]'
				and tilausrivi.yhtio = '$kukarow[yhtio]'
				and tilausrivi.var in ('','H')
				and tilausrivi.tyyppi in ('L','G')
				ORDER BY tuote.vakkoodi";
	$vres = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($vres) > 0) {
		$vak = mysql_fetch_assoc($vres);
		$vakit = $vak["vakkoodi"];
	}
}
else {
	$laskurow = $osoitelappurow;
}

$prikomento = explode('-#', $oslapp);

// haetaan printterin osoitetiedot
$query = "SELECT * from kirjoittimet where yhtio='$kukarow[yhtio]' and trim(komento) like concat(trim('$prikomento[0]'),'%') and nimi!=''";
$pres  = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($pres) > 0) {
	$print = mysql_fetch_assoc($pres);

	$yhtiorow["nimi"]    = $print["nimi"];
	$yhtiorow["osoite"]  = $print["osoite"];
	$yhtiorow["postino"] = $print["postino"];
	$yhtiorow["postitp"] = $print["postitp"];
	$yhtiorow["puhelin"] = $print["puhelin"];
}

if (!function_exists('tarkiste')) {
	function tarkiste($sscc) {
		$kerroin = 3; // kerroin aluks 3
		$summa   = 0; // summa nolla tietty

		// loopataan luvut oikeelta vasemmalle
		for ($i = 16; $i >= 0; $i--) {
			$summa += $kerroin * (ord($sscc{$i})-48); // lis�t��n summaan ko. luku * kerroin (t�� hanskaa kirjaimet )
			$kerroin = 4 - $kerroin; // kerroin on vuorotellen 3 tai 1
		}

		$sscc = ceil($summa / 10) * 10 - $summa; // tarkiste on luku mik� pit�� lis�t�, ett� p��st��n seuraavaan tasakymmeneen

		return $sscc;
	}
}

// tehd��n SSCC :
// (00)
// 1
// ean (8)
// l�hetenro (6) viimeist�
// kollityht (2)
// tarkiste (1)

$apu_kollityht = $kollityht;

if ($kollityht > 100) {
	$apu_kollityht = 99; // varmuuden vuoks, kun ei saa olla ku kaks merkki�
}
elseif ($kollityht == 0) {
	$apu_kollityht = 1;
}

if ($yhtiorow['ean'] == '') {
	$yhtiorow['ean'] = $yhtiorow["ytunnus"];
}

$sscc = 1;
$sscc .= sprintf("%-08.08s",$yhtiorow["ean"]);
$sscc .= sprintf('%06.06d', substr($laskurow["tunnus"], -6));
$sscc .= sprintf('%02.02d', $apu_kollityht);
$loppu = tarkiste($sscc);
$sscc = $sscc.$loppu;

//PDF:n luonti ja defaultit
require_once("pdflib/phppdflib.class.php");

// jos php-gd on installoitu niin loidataab barcode library
if (in_array("gd", get_loaded_extensions())) {
	if (@include_once("viivakoodi/Barcode.php"));
	else include_once("Barcode.php");
}

//PDF parametrit
$pdf = new pdffile;
$pdf->set_default('margin-top', 	0);
$pdf->set_default('margin-bottom', 	0);
$pdf->set_default('margin-left', 	0);
$pdf->set_default('margin-right', 	0);
$rectparam["width"] = 0.3;

//fontit
$otsik["height"] = 9;
$otsik["font"] = "Helvetica";

$pien["height"] = 10;
$pien["font"] = "Helvetica";

$norm["height"] = 10;
$norm["font"] = "Helvetica-Bold";

$piennorm["height"] = 9;
$piennorm["font"] = "Helvetica-Bold";

$kirj["height"] = 12;
$kirj["font"] = "Helvetica-Bold";

$iso["height"] = 16;
$iso["font"] = "Helvetica-Bold";

$huge["height"] = 20;
$huge["font"] = "Helvetica-Bold";

$tulostakolli = 1; // tulostetaan aina yksi kappale???

for ($tulostuskpl=1; $tulostuskpl<=$tulostakolli; $tulostuskpl++) {

	// tehd��n pdf:n uusi sivu
	$firstpage = $pdf->new_page("a5");

	if (class_exists("Image_Barcode")) {
		//luodaan viivakoodiolio kuljetusohjeelle = postino
		$nimi = "/tmp/".md5(uniqid(rand(),true)).".jpg";

		imagejpeg(Image_Barcode::draw('400'.$laskurow['asiakkaan_tilausnumero'], 'code128', 'jpg', false, 3, 170), $nimi);

		$fh = fopen($nimi, "r");
		$data = fread($fh, filesize($nimi));
		fclose($fh);

		$image = $pdf->jfif_embed($data);

		$logoparam['scale'] = 102/282;
		$pdf->image_place($image, mm_pt(72), mm_pt(24), $firstpage, $logoparam);
		system("rm -f $nimi");

		$nimi = "/tmp/".md5(uniqid(rand(),true)).".jpg";

		imagejpeg(Image_Barcode::draw('00'.$sscc, 'code128', 'jpg', false, 3, 170), $nimi);

		$fh = fopen($nimi, "r");
		$data = fread($fh, filesize($nimi));
		fclose($fh);

		$image = $pdf->jfif_embed($data);

		$logoparam['scale'] = 102/282;
		$pdf->image_place($image, mm_pt(40), mm_pt(24), $firstpage, $logoparam);
		system("rm -f $nimi");
	}

	$pdf->draw_text(mm_pt(22), mm_pt(65), "Asiakastilaus: ",	$firstpage, $norm);
	$pdf->draw_text(mm_pt(50), mm_pt(65), "(400) $laskurow[asiakkaan_tilausnumero]",	$firstpage, $iso);

	//teh�� t�st� viel� kaunis kattella
	$sscc = substr($sscc,0,4).substr($sscc,4,1).substr($sscc,5,8).substr($sscc,13,8).substr($sscc,21,1);

	// piirret��n viivakoodi paprulle
	$pdf->draw_text(mm_pt(22), mm_pt(33), "Kolli - Item ID.: ", $firstpage, $otsik);
	$pdf->draw_text(mm_pt(45), mm_pt(33), "(00) $sscc", $firstpage, $iso);

	// sitten aletaan piirt�m��n itse PDF sis�lt��
	// ensin isot viivat kohilleen
	$pdf->draw_rectangle(mm_pt(179),mm_pt(21.5),mm_pt(31),mm_pt(126.5), $firstpage, $rectparam);

	unset($data);
	if( (int) $yhtiorow["lasku_logo"] > 0) {
		$liite = hae_liite($yhtiorow["lasku_logo"], "Yllapito", "array");
		$data = $liite["data"];
		$isizelogo[0] = $liite["image_width"];
		$isizelogo[1] = $liite["image_height"];
		unset($liite);
	}
	elseif(file_exists($yhtiorow["lasku_logo"])) {
		$filename = $yhtiorow["lasku_logo"];

		$fh = fopen($filename, "r");
		$data = fread($fh, filesize($filename));
		fclose($fh);

		$isizelogo = getimagesize($yhtiorow["lasku_logo"]);
	}

	if($data) {
		$image = $pdf->jfif_embed($data);

		if(!$image) {
			echo t("Logokuvavirhe");
		}
		else {

			$logoparam = array();

			if ($isizelogo[0] > $isizelogo[1] and $isizelogo[1] * (200 / $isizelogo[0]) <= 40) {
				$logoparam['scale'] = 200 / $isizelogo[0];
			}
			else {
				$logoparam['scale'] = 40  / $isizelogo[1];
			}

			$placement = $pdf->image_place($image, 580-($logoparam['scale']*$isizelogo[1]), 410-($logoparam['scale']*$isizelogo[0]), $firstpage, $logoparam);
		}
	}

	//Mist�
	$pdf->draw_text(mm_pt(22), mm_pt(175), "Mist� - Fr�n - From: ",							$firstpage, $otsik);
	$pdf->draw_text(mm_pt(22), mm_pt(170), $yhtiorow['nimi'],								$firstpage, $pien);
	if ($yhtiorow['nimitark'] != '') {
		$pdf->draw_text(mm_pt(22), mm_pt(165), $yhtiorow['nimitark'],						$firstpage, $pien);
		$pdf->draw_text(mm_pt(22), mm_pt(160), $yhtiorow['osoite']." ".$yhtiorow['postino']." ".strtoupper($yhtiorow['postitp']),$firstpage, $pien);
	}
	else {
		$pdf->draw_text(mm_pt(22), mm_pt(165), $yhtiorow['osoite'].", ".$yhtiorow['postino']." ".strtoupper($yhtiorow['postitp']),$firstpage, $pien);
	}

	$pdf->draw_text(mm_pt(100), mm_pt(175), "L�hetyspvm",									$firstpage, $norm);
	$pdf->draw_text(mm_pt(100), mm_pt(170), date("d.m.Y") ,									$firstpage, $norm);

	$query = "SELECT * FROM toimitustapa WHERE yhtio = '$kukarow[yhtio]' and selite = '$laskurow[toimitustapa]'";
	$toitares = mysql_query($query) or pupe_error($query);
	$toitarow = mysql_fetch_assoc($toitares);

	$pdf->draw_text(mm_pt(22), mm_pt(157), "Terminaalitoimitus" ,							$firstpage, $norm);
	$pdf->draw_text(mm_pt(22), mm_pt(152), "$toitarow[toim_nimi]",							$firstpage, $norm);
	$pdf->draw_text(mm_pt(22), mm_pt(149), "$toitarow[toim_nimitark]",						$firstpage, $piennorm);
	$pdf->draw_text(mm_pt(22), mm_pt(145), "$toitarow[toim_osoite], $toitarow[toim_postino] $toitarow[toim_postitp]", $firstpage, $norm);

	$pdf->draw_text(mm_pt(22),	mm_pt(140), "Minne - Till - To: " ,							$firstpage, $otsik);

	$pdf->draw_text(mm_pt(22),	mm_pt(136), $laskurow['toim_nimi'],							$firstpage, $norm);
	$pdf->draw_text(mm_pt(22),	mm_pt(132), $laskurow['toim_nimitark'],						$firstpage, $norm);
	$pdf->draw_text(mm_pt(22),	mm_pt(127), $laskurow['toim_osoite'],						$firstpage, $norm);
	$pdf->draw_text(mm_pt(22),	mm_pt(120), $laskurow['toim_postino']." ".$laskurow['toim_postitp'],	$firstpage, $iso);


	$pdf->draw_text(mm_pt(100), mm_pt(140), "Toimituspvm",									$firstpage, $norm);
	$pdf->draw_text(mm_pt(100), mm_pt(135), date("d.m.Y") ,									$firstpage, $norm);

	// Kuljetusohjeet = viivakoodi tulee t�h�n
	$pdf->draw_text(mm_pt(22), mm_pt(112), "Kuljetusohjeet - Transportinstruktioner - Transport Instructions:",	$firstpage, $otsik);
	$pdf->draw_text(mm_pt(22), mm_pt(107), "Reitti:                            Jono:                            L�hetysehto:",	$firstpage, $otsik);

	if (isset($vakit) and $vakit != "") {
		$pdf->draw_text(mm_pt(22), mm_pt(102), "VAK: ".$vakit, $firstpage, $norm);
	}

	$pdf->draw_text(mm_pt(22), mm_pt(97),  "L�mp�tila +0....+30C",							$firstpage, $norm);

	$pdf->draw_text(mm_pt(105), mm_pt(97),  "Kolli - Item",									$firstpage, $otsik);

	//kolliluku
	if ($rahtikirja_tulostus == "yep") {
		$pdf->draw_text(mm_pt(105), mm_pt(92), $kollityht, $firstpage, $norm);
	}
	else {
		$query = "	SELECT sum(kollit) kollit
					FROM rahtikirjat
					WHERE yhtio='$kukarow[yhtio]' and otsikkonro = '$laskurow[tunnus]'";
		$pakka = mysql_query($query) or pupe_error($query);
		$pak = mysql_fetch_assoc($pakka);

		if ((int) $pak["kollit"] > 0) {
			$pdf->draw_text(mm_pt(105), mm_pt(92), $pak["kollit"], $firstpage, $norm);
		}
	}
}

//keksit��n uudelle failille joku varmasti uniikki nimi:
$pdffilenimi = "/tmp/osoitelappu-intrade-".md5(uniqid(rand(),true)).".pdf";

//kirjoitetaan pdf faili levylle..
$fh = fopen($pdffilenimi, "w");
if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus ep�onnistui $pdffilenimi");
fclose($fh);

if ($oslapp == 'email') {
	$liite = $pdffilenimi;
	$kutsu = "Osoitelappu";

	require("inc/sahkoposti.inc");
}
elseif ($tee == 'NAYTATILAUS') {
	//Ty�nnet��n tuo pdf vaan putkeen!
	echo file_get_contents($pdffilenimi);
}
elseif ($oslapp != '' and $oslapp != 'edi') {
	$line = exec("$oslapp $pdffilenimi");
	echo t("Osoitelappu tulostuu")."...<br>";
}

//poistetaan tmp file samantien kuleksimasta...
system("rm -f $pdffilenimi");
$pdffilenimi = '';

?>