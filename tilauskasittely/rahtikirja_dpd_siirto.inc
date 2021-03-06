<?php

/* Pupesoft -> DPD DELISprint datasiirto
 * rahtikirja_dpd_siirto.inc v0.8a 18.03.2008
 * Originaali: rahtikirja_dpd.inc
 * Kustomointi: Mikko Nousiainen mikko@datamonitor.fi
 *************************************************************/

//jos on laitettu kollikentt��n 0 niin ei yritet� luoda siirtotiedostoa
if ($kollityht > 0) {

	if (($dpd_delisprint_host != "" and $dpd_delisprint_user != "" and $dpd_delisprint_pass != "" and $dpd_delisprint_path != "") or $dpd_delisprint_path != "") {

		if ($toitarow['logy_rahtikirjanumerot'] != "" and strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE and (!isset($muutos) or $muutos != 'yes')) {

			$logyq = "	SELECT min(rahtikirjanro) rahtikirjanro
						FROM rahtikirjanumero
						WHERE yhtio = '$kukarow[yhtio]'
						and kaytettyaika = '0000-00-00 00:00:00'";
			$logyres = pupe_query($logyq);
			$logyrow = mysql_fetch_assoc($logyres);

			$rahtikirjanro = $logyrow["rahtikirjanro"];

			$logyq = "	UPDATE rahtikirjanumero
						SET kayttaja = '$kukarow[kuka]',
						kaytettyaika = now()
						WHERE yhtio = '$kukarow[yhtio]'
						and rahtikirjanro = '$logyrow[rahtikirjanro]'";
			$logyres = pupe_query($logyq);
		}
		elseif (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE and (!isset($muutos) or $muutos != 'yes')) {
			$rahtikirjanro = $lotsikot[0];
		}
		else {
			$rahtikirjanro = $rivi["rahtikirjanro"];
		}

		if ($phpnimi == "rahtikirja_custom.php") {
			$laskurow = $osoitelappurow;
			$rakir_row = $osoitelappurow;
		}

		//$apu_kollityht = $kollityht;
		if ($kollityht > 10) {
			die("Liikaa kolleja! (max 10 kpl)"); // kolleja ei saa olla yli 10 per toimitusrivi...
		}

		//	Luodaan meid�n tiedostorakenne parcelOrder arrayhin ja pukataan se tonne aineistoon sitten laakista
		$parcelOrder=array();
		$parcelOrder["rahtikirjanro"] = $rahtikirjanro;

		/*
			Arvotaan l�hetystapa
		*/

		// onko EXPRESS10 tai 12
		if ($rakir_row["jv"] != '' or $mehto['jv'] != '') {
			$parcelOrder["lahetystapa"] = "NN";
		}
		elseif (strpos($toitarow['selite'], "E10")) {
			$parcelOrder["lahetystapa"] = "E10";
		}
		elseif (strpos($toitarow['selite'], "E12")) {
			$parcelOrder["lahetystapa"] = "E12";
		}
		else {
			$parcelOrder["lahetystapa"] = "NP";
		}

		//	Haetaan otsikkotiedot pohjalle
		$query = "	SELECT *
					FROM lasku
					WHERE yhtio='$kukarow[yhtio]' and tunnus in ($otunnukset)
					ORDER BY tunnus limit 1";
		$laskures = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($laskures);

		for($i=0;$i<mysql_num_fields($laskures);$i++) {
			$r = mysql_field_name($laskures, $i);
			$v = $laskurow[$i];

			//	Korvaavuksia ja n�pp�ily�
			if($r=="asiakkaan_viite" and $v == "") {
				$v = $laskurow["viite"];
			}

			//	Poistetaan aina kaikki turhat merkit ja v�lily�nnit
			$parcelOrder[$r] = trim(str_replace(array("\r\n", "\r", "\n"), " ", $v));
		}

		//	Luotamme vahvasti siihen, ett� meill� on rakir_res tallella.. T��lt� tulee jotain hy�dyllist� kumminkin..
		for($i=0;$i<mysql_num_fields($rakir_res);$i++) {

			$r = mysql_field_name($rakir_res, $i);
			$v = $rakir_row[$r];

			//	Korvaavuksia ja n�pp�ily�
			if($r=="jv" and $v != "") {
				$parcelOrder["jv_maara"] 		= $yhteensa;
				$parcelOrder["jv_valkoodi"] 	= $yhtiorow["valkoodi"];
				$parcelOrder["jv_pankkitili"] 	= $yhtiorow["pankkitili1"];
				$parcelOrder["jv_viite"] 		= $viite;
			}

			$parcelOrder[$r] = $v;
		}

		//	Haetaan asiakastiedot
		$query = "	SELECT *
					FROM asiakas
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
		$asres = mysql_query($query) or pupe_error($query);
		$asrow = mysql_fetch_array($asres);

		for($i=0;$i<mysql_num_fields($asres);$i++) {
			$r = mysql_field_name($asres, $i);
			$v = $asrow[$i];

			//	Korvaavuksia ja n�pp�ily�

			$parcelOrder["asiakas_".$r] = $v;
		}

		/*
		Jos meill� oli joku yhteyshenkil� haetaan sen tiedot my�s! (Falceback asiakas)
		*/
		$query = "	SELECT nimi, titteli, email, puh, gsm, fax
					FROM laskun_lisatiedot
					JOIN yhteyshenkilo ON yhteyshenkilo.yhtio=laskun_lisatiedot.yhtio and yhteyshenkilo.tunnus=laskun_lisatiedot.yhteyshenkilo_tekninen and yhteyshenkilo.tyyppi = 'A'
					WHERE laskun_lisatiedot.yhtio = '$kukarow[yhtio]'
					and otunnus IN ($otunnukset)
					and yhteyshenkilo_tekninen > 0";
		$yhres = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($yhres) == 0) {
			$query = "	SELECT nimi, titteli, email, puh, gsm, fax
						FROM laskun_lisatiedot
						JOIN yhteyshenkilo ON yhteyshenkilo.yhtio=laskun_lisatiedot.yhtio and yhteyshenkilo.tunnus=laskun_lisatiedot.yhteyshenkilo_kaupallinen and yhteyshenkilo.tyyppi = 'A'
						WHERE laskun_lisatiedot.yhtio = '$kukarow[yhtio]'
						and otunnus IN ($otunnukset)
						and yhteyshenkilo_tekninen > 0";
			$yhres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($yhres) == 0) {
				$query = "	SELECT '2' O, '' nimi, '' titteli, email, puhelin puh, gsm, fax
							FROM asiakas
							WHERE yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
				$yhres = mysql_query($query) or pupe_error($query);
			}
		}

		if (mysql_num_rows($yhres) > 0) {
			$yhrow = mysql_fetch_array($yhres);
			for($i=0;$i<mysql_num_fields($yhres);$i++) {
				$r = mysql_field_name($yhres, $i);
				$v = $yhrow[$i];

				//	Korvaavuksia ja n�pp�ily�
				$parcelOrder["yhteyshenkilo_".$r] = $v;
			}
		}

		$query ="	SELECT sum(kollit) kollit,
					sum(kilot) kilot,
					sum(kuutiot) kuutiot,
					sum(lavametri) lavametri,
					min(pakkauskuvaustark) pakkauskuvaustark
					from rahtikirjat use index (otsikko_index)
					where yhtio 		= '$kukarow[yhtio]'
					and otsikkonro 		= '$rahtikirjanro'
					and rahtikirjanro 	= '$rahtikirjanro'
					and pakkaus 		= '$pakkaus[0]'";
		//and pakkauskuvaus = '$row[selitetark]'";
		$rakires = mysql_query($query) or pupe_error($query);
		$rakirow = mysql_fetch_array($rakires);

		for($i=0;$i<mysql_num_fields($rakires);$i++) {
			$r = mysql_field_name($rakires, $i);
			$v = $rakirow[$i];

			//	Korvaavuksia ja n�pp�ily�
			if($r == "kilot") {
				$k = round(($rakirow[$i]/$rakirow["kollit"]), 1);
				if($rakirow["kollit"] == 2) {
					$v = str_replace('.', ',', $k)."/".str_replace('.', ',', $k);
				}
				else {
					$v = str_replace('.', ',', $k);
				}
			}

			$parcelOrder[$r] = $v;
		}

		//echo "<hr>data:<pre>".print_r($parcelOrder, true)."</pre>";

		//	Luodaan aineostoformaatti
		if(!is_array($delisprintFileFormat)) {
			// L�hetystapa ; Paino (kg) ; Senders Reference: ; Order #: ; Nimi ; Osoite 1 ; Maa ; Postinr. ; Kaup. ; Yhteyshenkil� ; Puh. ; Fax ; S�hk�posti ;
			$delisprintFileFormat = array(
											"lahetystapa",
											"kollit",
											"kilot",
											"rahtikirjanro",
											"asiakkaan_tilausnumero",
											"toim_nimi",
											"toim_osoite",
											"toim_maa",
											"toim_postino",
											"toim_postitp",
											"yhteyshenkilo_nimi",
											"puh",
											"fax",
											"email",
											"comments",
										);

		}

		//echo "<hr>data:<pre>".print_r($delisprintFileFormat, true)."</pre>";

		unset($aineisto);
		foreach($delisprintFileFormat as $p) {
			if(isset($aineisto)) {
				$aineisto .=";";
			}
			$aineisto.=$parcelOrder[$p];
		}

		// Muunnetaan merkist� Windows ANSI
		iconv("ISO-8859-1", "Windows-1252", $aineisto);

		if ($dpd_delisprint_path != '') {
			if (substr($dpd_delisprint_path,-1) != '/') {
				$dpd_delisprint_path .= '/';
			}
			$filenimi = $dpd_delisprint_path."dpdimport-".md5(uniqid(rand(),true)).".dat";
		}
		else {
			$filenimi = "/tmp/dpdimport-".md5(uniqid(rand(),true)).".dat";
		}

		//kirjoitetaan faili levylle..
		if (file_put_contents($filenimi, $aineisto) === FALSE) {
			echo "<br><font class='error'>".t("tiedoston kirjoitus EP�ONNISTUI")."</font><br>";
		}

		if ($dpd_delisprint_host != "" and $dpd_delisprint_user != "" and $dpd_delisprint_pass != "" and $dpd_delisprint_path != "") {
			// tarvitaan  $ftphost $ftpuser $ftppass $ftppath $ftpfile
			// palautetaan $palautus ja $syy
			$ftphost = $dpd_delisprint_host;
			$ftpuser = $dpd_delisprint_user;
			$ftppass = $dpd_delisprint_pass;
			$ftppath = $dpd_delisprint_path;
			$ftpfile = realpath($filenimi);
			require ("inc/ftp-send.inc");
		}
	}
	else {
		echo "<br><font class='error'>".t("DPD aineiston luotiin tarvittavia parametrej� puuttuu!")."</font><br>";
	}

}

?>
