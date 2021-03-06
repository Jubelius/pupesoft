<?php

require("pdflib/phppdflib.class.php");

//PDF parametrit
$pdf = new pdffile;
$pdf->set_default('margin-top', 	0);
$pdf->set_default('margin-bottom', 	0);
$pdf->set_default('margin-left', 	0);
$pdf->set_default('margin-right', 	0);
$rectparam["width"] = 0.3;

$norm["height"] = 10;
$norm["font"] = "Times-Roman";

$pieni["height"] = 8;
$pieni["font"] = "Times-Roman";

// defaultteja
$kala = 575;
$lask = 1;
$sivu = 1;

function alku ($laji = '') {
	global $pdf, $asiakasrow, $yhtiorow, $kukarow, $asrow, $sivu, $rectparam, $norm, $pieni, $maksuehto, $pvm, $alkuvv, $alkukk, $alkupp, $loppuvv, $loppukk, $loppupp;

	// jos ollaan sy�tetty poikkeava p�iv�, k�ytet��n sit�
	if ($pvm != '') {
		// t�m�vuosi ja viimevuosi
		$vvt = substr($pvm, 0, 4);
		$vvv = substr($pvm, 0, 4) - 1;
		$now = $pvm;
	}
	else {
		// t�m�vuosi ja viimevuosi
		$vvt = date("Y");
		$vvv = date("Y") - 1;
		$now = date("Y-m-d");
	}

	$firstpage = $pdf->new_page("a4");

	//otsikko
	$pdf->draw_text(30,  815,  $yhtiorow["nimi"], 				$firstpage);
	$pdf->draw_text(300, 815, "OSTOSEURANTA", 					$firstpage);
	$pdf->draw_text(530, 815, "Sivu $sivu", 					$firstpage, $norm);

	//vasen sarake
	$pdf->draw_text(50, 729, $asiakasrow["ytunnus"], 					$firstpage, $pieni);
	$pdf->draw_text(50, 717, $asiakasrow["nimi"], 						$firstpage, $norm);
	$pdf->draw_text(50, 707, $asiakasrow["nimitark"], 					$firstpage, $norm);
	$pdf->draw_text(50, 697, $asiakasrow["osoite"], 					$firstpage, $norm);
	$pdf->draw_text(50, 687, $asiakasrow["postino"]." ".$asiakasrow["postitp"], $firstpage, $norm);
	$pdf->draw_text(50, 677, $asiakasrow["maa"], 						$firstpage, $norm);

	//oikea sarake
	$pdf->draw_text(300, 727, "Hyv� yhteisty�kumppanimme,",		$firstpage, $norm);
	$pdf->draw_text(300, 707, "Ohessa t�m�nhetkinen ostotilanteenne.",		$firstpage, $norm);
	$pdf->draw_text(300, 687, "Kunnioittaen,",					$firstpage, $norm);
	$pdf->draw_text(300, 677, $yhtiorow["nimi"],				$firstpage, $norm);

	//aika
	$pdf->draw_text(300,  610, "Rajaus: ",			$firstpage, $pieni);
	$pdf->draw_text(350,  610, tv1dateconv("$alkuvv-$alkukk-$alkupp")." - ".tv1dateconv("$loppuvv-$loppukk-$loppupp"),			$firstpage, $pieni);

	//otsikkotiedot
	if ($laji == "osasto") {
		$pdf->draw_text(30,  600, "Osasto",			$firstpage, $pieni);
	}
	else {
		//otsikkotiedot
		$pdf->draw_text(30,  600, "Tuoteryhm�",			$firstpage, $pieni);
	}

	$oikpos = $pdf->strlen("Kpl $vvt", $pieni);
	$pdf->draw_text(330-$oikpos, 600, "Kpl $vvt",			$firstpage, $pieni);

	$oikpos = $pdf->strlen("Kpl $vvv", $pieni);
	$pdf->draw_text(380-$oikpos, 600, "Kpl $vvv",			$firstpage, $pieni);

	$oikpos = $pdf->strlen("Ostot $vvt", $pieni);
	$pdf->draw_text(460-$oikpos, 600, "Ostot $vvt",			$firstpage, $pieni);

	$oikpos = $pdf->strlen("Ostot $vvv", $pieni);
	$pdf->draw_text(530-$oikpos, 600, "Ostot $vvv",			$firstpage, $pieni);

	$oikpos = $pdf->strlen("Indeksi", $pieni);
	$pdf->draw_text(575-$oikpos, 600, "Indeksi",			$firstpage, $pieni);

	return($firstpage);
}

function rivi ($firstpage, $laji = '') {
	global $firstpage, $pdf, $row, $kala, $sivu, $lask, $rectparam, $norm, $pieni, $lask, $sumkpled, $sumkplva, $sumed, $sumva, $kukarow, $yhtiorow, $edosasto;

	if ($lask >= 34) {
		// rivej� on tarpeeks, teh��n uus headeri
		$sivu++;
		$kala = 575;
		$lask = 1;
		$firstpage = alku($laji);
	}

	if ($row["ed"] != 0) {
		$indexi = round($row["va"]/$row["ed"],2)*100;
	}
	else {
		$indexi = 0;
	}

	if ($laji == 'osasto') {
		// tehd��n avainsana query
		$tryre = t_avainsana("OSASTO", "", "and avainsana.selite ='$row[osasto]'");
		$tryro = mysql_fetch_array($tryre);
		$asananumero = $row["osasto"];
	}
	else {
		// tehd��n avainsana query
		$tryre = t_avainsana("TRY", "", "and avainsana.selite ='$row[try]'");
		$tryro = mysql_fetch_array($tryre);
		$asananumero = $row["try"];
	}

	if ($edosasto != $row["osasto"] and $laji == "") {
		$edosasto = $row["osasto"];

		$tryre = t_avainsana("OSASTO", "", "and avainsana.selite ='$row[osasto]'");
		$osrow = mysql_fetch_array($tryre);

		if ($edosasto != "" and $lask > 1) {
			$kala = $kala - 20;
			$lask++;
		}

		$pdf->draw_text(30, $kala, "Osasto $row[osasto] - $osrow[selitetark]", $firstpage, $norm);
		$kala = $kala - 20;
		$lask++;
	}

	$pdf->draw_text(30,  $kala, $asananumero, $firstpage, $norm);
	$pdf->draw_text(55,  $kala, $tryro["selitetark"],$firstpage, $norm);

	$oikpos = $pdf->strlen(sprintf("%.0f", $row["kplva"]), $norm);
	$pdf->draw_text(330-$oikpos, $kala, sprintf("%.0f", $row["kplva"]), 		$firstpage, $norm);

	$oikpos = $pdf->strlen(sprintf("%.0f", $row["kpled"]), $norm);
	$pdf->draw_text(380-$oikpos, $kala, sprintf("%.0f", $row["kpled"]), 		$firstpage, $norm);

	$oikpos = $pdf->strlen(hintapyoristys($row["va"]), $norm);
	$pdf->draw_text(460-$oikpos, $kala, hintapyoristys($row["va"]),			$firstpage, $norm);

	$oikpos = $pdf->strlen(hintapyoristys($row["ed"]), $norm);
	$pdf->draw_text(530-$oikpos, $kala, hintapyoristys($row["ed"]), 		$firstpage, $norm);

	$oikpos = $pdf->strlen($indexi, $norm);
	$pdf->draw_text(575-$oikpos, $kala, $indexi, $firstpage, $norm);
	$kala = $kala - 15;

	$lask++;
}

function loppu ($firstpage, $sendemail = '') {
	global $pdf, $kala, $rectparam, $norm, $pieni, $sumkpled, $sumkplva, $sumed, $sumva, $sumindexi, $yhtiorow, $kukarow, $edasiakas, $edemail, $edasiakasno, $komento, $emailok, $asnum;

	//kirjoitetaan yhteens�rivit loppuun
	$kala -= 15;

	$pdf->draw_text(250, $kala, "Yhteens�:", 	$firstpage, $norm);

	$oikpos = $pdf->strlen(sprintf("%.0f", $sumkplva), $norm);
	$pdf->draw_text(330-$oikpos, $kala, sprintf("%.0f", $sumkplva), 		$firstpage, $norm);

	$oikpos = $pdf->strlen(sprintf("%.0f", $sumkpled), $norm);
	$pdf->draw_text(380-$oikpos, $kala, sprintf("%.0f", $sumkpled), 		$firstpage, $norm);

	$oikpos = $pdf->strlen(hintapyoristys($sumva), $norm);
	$pdf->draw_text(460-$oikpos, $kala, hintapyoristys($sumva),			$firstpage, $norm);

	$oikpos = $pdf->strlen(hintapyoristys($sumed), $norm);
	$pdf->draw_text(530-$oikpos, $kala, hintapyoristys($sumed), 		$firstpage, $norm);

	if ($sumed != 0) {
		$sumindexi = round($sumva/$sumed,2)*100;
	}
	else {
		$sumindexi = 0;
	}

	$oikpos = $pdf->strlen($sumindexi, $norm);
	$pdf->draw_text(575-$oikpos, $kala, $sumindexi,	 	$firstpage, $norm);

	//ja nollataan muuttujat
	$sumkpled  = 0;
	$sumkplva  = 0;
	$sumed     = 0;
	$sumva     = 0;
	$sumindexi = 0;

	if ($sendemail != "") {
		return;
	}

	//keksit��n uudelle failille joku varmasti uniikki nimi:
	$pdffilenimi = "/tmp/vuosisop-".md5(uniqid(rand(),true)).".pdf";

	//kirjoitetaan pdf faili levylle..
	$fh = fopen($pdffilenimi, "w");
	if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus ep�onnistui $pdffilenimi");
	fclose($fh);

	if ($asnum != '') { // ollaan valittu vain yksi asiakas, l�hetet��n meili ajajalle
		$edemail = $kukarow['eposti'];
	}

	// asiakkaalla on emailosoite ja ei olla ruksattu, l�hetet��n s�hk�posti
	if ($edemail!='' and $emailok=='') {
		echo "L�hetet��n meili asiakkaalle $edasiakasno ($edemail).. ";

		$bound = uniqid(time()."_") ;

		$header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
		$header .= "MIME-Version: 1.0\n" ;
		$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

		$content  = "--$bound\n";

		$content .= "Content-Type: application/pdf; name=\"Ostoseuranta.pdf\"\n" ;
		$content .= "Content-Transfer-Encoding: base64\n" ;
		$content .= "Content-Disposition: inline; filename=\"Ostoseuranta.pdf\"\n\n";

		$handle  = fopen($pdffilenimi, "r");
		$sisalto = fread($handle, filesize($pdffilenimi));
		fclose($handle);

		$content .= chunk_split(base64_encode($sisalto));
		$content .= "\n" ;

		$content .= "--$bound\n";

		$boob     = mail($edemail, mb_encode_mimeheader($yhtiorow['nimi']." - Ostoseuranta ".date("d.m.Y"), "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");

		if ($boob===FALSE) {
			echo "Meilin l�hetys ep�onnistui!<br>";
		}
		else {
			echo "ok.<br>";
		}
	}
	// asiakkaalla on email, mutta halutaan skipata emailien l�hetys...
	elseif ($edemail!='' and $emailok!='') {
		echo "Skipataan asiakas $edasiakasno.<br>";
	}
	// muissa keiseissa tulostetaan paprulle ta s�hk�postiin
	else {
		echo "Tulostetaan asiakkaan $edasiakasno ostoseuranta.. ";

		if ($komento == "email") {
			$liite = $pdffilenimi;
			$ctype = '';
			$kutsu = "Ostoseuranta.pdf";

			require("../inc/sahkoposti.inc");
		}
		else{
			$line = exec($komento." ".$pdffilenimi);
		}


		echo "ok.<br>";
	}

	//poistetaan tmp file samantien kuleksimasta...
	system("rm -f $pdffilenimi");
}

?>