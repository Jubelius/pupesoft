<?php
	if (!isset($link)) require "inc/parametrit.inc";

	enable_ajax();

	if (isset($livesearch_tee) and $livesearch_tee == "TILIHAKU") {
		livesearch_tilihaku();
		exit;
	}

	// Talletetaan k�ytt�j�n nimell� tositteen/liitteen kuva, jos sellainen tuli
	// koska, jos tulee virheit� tiedosto katoaa. Kun kaikki on ok, annetaan sille oikea nimi
	if ($tee == 'I' and isset($_FILES['userfile']) and is_uploaded_file($_FILES['userfile']['tmp_name'])) {
		$retval = tarkasta_liite("userfile", array("PNG", "JPG", "GIF", "PDF"));

		if ($retval === true) {
			$kuva = tallenna_liite("userfile", "lasku", 0, "");
		}
		else {
			echo $retval;
			$tee = "";
		}
	}

	if (!isset($tiliointirivit)) $tiliointirivit = array();
	if (!isset($iliitos)) $iliitos = array();
	if (!isset($ed_iliitostunnus)) $ed_iliitostunnus = array();

	if (isset($tullaan) and $tullaan == 'muutosite') {
		$iliitos = unserialize(urldecode($iliitos));
		$ed_iliitostunnus = unserialize(urldecode($ed_iliitostunnus));
		$tiliointirivit = unserialize(urldecode($tiliointirivit));
	}

	if (isset($tiliointirivit) and !is_array($tiliointirivit)) $tiliointirivit = unserialize(urldecode($tiliointirivit));

	if (isset($muutparametrit)) {
		list($tee, $kuitti, $kuva, $maara, $tpp, $tpk, $tpv, $summa, $valkoodi, $alv_tili, $nimi, $comments, $selite, $liitos, $liitostunnus, $tunnus, $tiliointirivit, $MAX_FILE_SIZE, $itili, $ikustp, $ikohde, $isumma, $ivero, $iselite, $iliitos, $ed_iliitostunnus) = explode("#!#", $muutparametrit);

		$itili		= unserialize(urldecode($itili));
		$ikustp		= unserialize(urldecode($ikustp));
		$ikohde		= unserialize(urldecode($ikohde));
		$isumma		= unserialize(urldecode($isumma));
		$ivero		= unserialize(urldecode($ivero));
		$iselite	= unserialize(urldecode($iselite));
		$iliitos = unserialize(urldecode($iliitos));
		$ed_iliitostunnus = unserialize(urldecode($ed_iliitostunnus));
		$tiliointirivit = unserialize(urldecode($tiliointirivit));
	}

	if ($toimittajaid > 0) {

		for ($i = 1; $i <= count($iliitos); $i++) {

			if ($iliitos[$i] == 'toimi' and $iliitostunnus[$i] == $toimittajaid) {
				$ed_iliitostunnus[$i] = $toimittajaid;
			}
			elseif ($iliitos[$i] == 'toimi' and isset($iliitostunnus) and !isset($iliitostunnus[$i]) and $ed_iliitostunnus[$i] == $toimittajaid) {
				$ed_iliitostunnus[$i] = '';
			}
		}
	}
	elseif ($asiakasid > 0) {

		for ($i = 1; $i <= count($iliitos); $i++) {

			if ($iliitos[$i] == 'asiakas' and $iliitostunnus[$i] == $asiakasid) {
				$ed_iliitostunnus[$i] = $asiakasid;
			}
			elseif ($iliitos[$i] == 'asiakas' and isset($iliitostunnus) and !isset($iliitostunnus[$i]) and $ed_iliitostunnus[$i] == $asiakasid) {
				$ed_iliitostunnus[$i] = '';
			}
		}
	}

	$muutparametrit = $tee."#!#".$kuitti."#!#".$kuva."#!#".$maara."#!#".$tpp."#!#".$tpk."#!#".$tpv."#!#".$summa."#!#".$valkoodi."#!#".$alv_tili."#!#".$nimi."#!#".$comments."#!#".$selite."#!#".$liitos."#!#".$liitostunnus."#!#".$tunnus."#!#".urlencode(serialize($tiliointirivit))."#!#".$MAX_FILE_SIZE."#!#".urlencode(serialize($itili))."#!#".urlencode(serialize($ikustp))."#!#".urlencode(serialize($ikohde))."#!#".urlencode(serialize($isumma))."#!#".urlencode(serialize($ivero))."#!#".urlencode(serialize($iselite))."#!#".urlencode(serialize($iliitos))."#!#".urlencode(serialize($ed_iliitostunnus));

	echo "<font class='head'>".t("Uusi muu tosite")."</font><hr>\n";

	$kurssi = 1;

	// Jos syotet��n nimi niin ei liitet� asiakasta eik� toimittajaa
	if ($nimi != "") {
		$toimittajaid 	= 0;
		$asiakasid 		= 0;
		$toimittaja_y	= "";
		$asiakas_y		= "";
	}

	if ($toimittaja_y != '') {
		$ytunnus = $toimittaja_y;
		$toimittajaid = 0;
		$asiakasid = 0;

		require ("inc/kevyt_toimittajahaku.inc");

		if ($toimittajaid > 0) {
			$tee = "";
		}

		if ($monta == 0) {
			$tee = "N";
		}
		elseif ($toimittajaid == 0) {
			require ("inc/footer.inc");
			exit;
		}

	}

	if ($asiakas_y != '') {
		$ytunnus = $asiakas_y;
		$asiakasid = 0;
		$toimittajaid = 0;

		require ("inc/asiakashaku.inc");

		if ($asiakasid > 0) {
			$tee = "";
		}

		if ($monta == 0) {
			$tee = "N";
		}
		elseif ($asiakasid == 0) {
			require ("inc/footer.inc");
			exit;
		}
	}

	if ($toimittajaid > 0) {

		$query = "SELECT * FROM toimi WHERE tunnus = '{$toimittajaid}'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) != 1) {
			echo t("Toimittajaa")." {$ytunnus} ".t("ei l�ytynytk��n")."!";
			exit;
		}

		$toimrow = mysql_fetch_assoc($result);
	}

	if ($asiakasid > 0) {

		$query = "SELECT * FROM asiakas WHERE tunnus = '{$asiakasid}'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) != 1) {
			echo t("Asiakasta")." {$ytunnus} ".t("ei l�ytynytk��n")."!";
			exit;
		}

		$asrow = mysql_fetch_assoc($result);
	}

	// Tarkistetetaan sy�tteet perustusta varten
	if ($tee == 'I') {
		$totsumma = 0;
		$summa = str_replace (",", ".", $summa);
		$gok  = 0;
		$tpk += 0;
		$tpp += 0;
		$tpv += 0;

		if (isset($gokfrom) and ($gokfrom == "palkkatosite" or $gokfrom == "avaavatase")) {
			$gok = 1;
		}

		$tapvmvirhe = "";

		if (!checkdate($tpk, $tpp, $tpv)) {
			$tapvmvirhe = "<font class='error'>".t("Virheellinen tapahtumapvm")."</font>";
			$gok = 1;
		}

		if ($valkoodi != $yhtiorow["valkoodi"] and $gok == 0) {

			// koitetaan hakea maksup�iv�n kurssi
			$query = "	SELECT *
						FROM valuu_historia
						WHERE kotivaluutta = '{$yhtiorow['valkoodi']}'
						AND valuutta = '{$valkoodi}'
						AND kurssipvm <= '{$tpv}-{$tpk}-{$tpp}'
						ORDER BY kurssipvm DESC
						LIMIT 1";
			$valuures = pupe_query($query);

			if (mysql_num_rows($valuures) == 1) {
				$valuurow = mysql_fetch_assoc($valuures);
				$kurssi = $valuurow["kurssi"];
			}
			else {
				echo "<font class='error'>".t("Ei l�ydetty sopivaa kurssia!")."</font><br>\n";
				$gok = 1;
			}
		}

		if (is_uploaded_file($_FILES['tositefile']['tmp_name'])) {
			//	ei koskaan p�ivitet� automaattisesti
			$tee = "";

			$retval = tarkasta_liite("tositefile", array("TXT", "CSV", "XLS"));

			if ($retval === true) {
				$path_parts = pathinfo($_FILES['tositefile']['name']);
				$name	= strtoupper($path_parts['filename']);
				$ext	= strtoupper($path_parts['extension']);

				if (strtoupper($ext)=="XLS") {
					require_once ('excel_reader/reader.php');

					// ExcelFile
					$data = new Spreadsheet_Excel_Reader();

					// Set output Encoding.
					$data->setOutputEncoding('CP1251');
					$data->setRowColOffset(0);
					$data->read($_FILES['tositefile']['tmp_name']);
				}

				echo "<font class='message'>".t("Tutkaillaan mit� olet l�hett�nyt").".<br></font>\n";

				// luetaan eka rivi tiedostosta..
				if (strtoupper($ext) == "XLS") {
					$otsikot = array();

					for ($excej = 0; $excej < $data->sheets[0]['numCols']; $excej++) {
						$otsikot[] = strtoupper(trim($data->sheets[0]['cells'][0][$excej]));
					}
				}
				else {
					$file	 = fopen($_FILES['tositefile']['tmp_name'],"r") or die (t("Tiedoston avaus ep�onnistui")."!");

					$rivi    = fgets($file);
					$otsikot = explode("\t", strtoupper(trim($rivi)));
				}

				// luetaan tiedosto loppuun ja tehd��n array koko datasta
				$excelrivi = array();

				if (strtoupper($ext) == "XLS") {
					for ($excei = 0; $excei < $data->sheets[0]['numRows']; $excei++) {
						for ($excej = 0; $excej <= $data->sheets[0]['numCols']; $excej++) {
							$excelrivi[$excei][$excej] = $data->sheets[0]['cells'][$excei][$excej];
						}
					}
				}
				else {
					$excei = 1;

					while ($rivi = fgets($file)) {
						// luetaan rivi tiedostosta..
						$poista	 = array("'", "\\");
						$rivi	 = str_replace($poista,"",$rivi);
						$rivi	 = explode("\t", trim($rivi));

						$excej = 0;
						foreach ($rivi as $riv) {
							$excelrivi[$excei][$excej] = $riv;
							$excej++;
						}
						$excei++;
					}
					fclose($file);
				}

				$maara = 0;

				foreach ($excelrivi as $erivi) {
					foreach ($erivi as $e => $eriv) {

						if (strtolower($otsikot[$e]) == "kustp") {
							// Kustannuspaikka
							$ikustp_tsk  	 = trim($eriv);
							$ikustp[$maara]  = 0;

							if ($ikustp_tsk != "") {
								$query = "	SELECT tunnus
											FROM kustannuspaikka
											WHERE yhtio = '{$kukarow['yhtio']}'
											and tyyppi = 'K'
											and kaytossa != 'E'
											and nimi = '{$ikustp_tsk}'";
								$ikustpres = pupe_query($query);

								if (mysql_num_rows($ikustpres) == 1) {
									$ikustprow = mysql_fetch_assoc($ikustpres);
									$ikustp[$maara] = $ikustprow["tunnus"];
								}
							}

							if ($ikustp_tsk != "" and $ikustp[$maara] == 0) {
								$query = "	SELECT tunnus
											FROM kustannuspaikka
											WHERE yhtio = '{$kukarow['yhtio']}'
											and tyyppi = 'K'
											and kaytossa != 'E'
											and koodi = '{$ikustp_tsk}'";
								$ikustpres = pupe_query($query);

								if (mysql_num_rows($ikustpres) == 1) {
									$ikustprow = mysql_fetch_assoc($ikustpres);
									$ikustp[$maara] = $ikustprow["tunnus"];
								}
							}

							if (is_numeric($ikustp_tsk) and (int) $ikustp_tsk > 0 and $ikustp[$maara] == 0) {

								$ikustp_tsk = (int) $ikustp_tsk;

								$query = "	SELECT tunnus
											FROM kustannuspaikka
											WHERE yhtio = '{$kukarow['yhtio']}'
											and tyyppi = 'K'
											and kaytossa != 'E'
											and tunnus = '{$ikustp_tsk}'";
								$ikustpres = pupe_query($query);

								if (mysql_num_rows($ikustpres) == 1) {
									$ikustprow = mysql_fetch_assoc($ikustpres);
									$ikustp[$maara] = $ikustprow["tunnus"];
								}
							}
						}
						else {
							${"i".strtolower($otsikot[$e])}[$maara] = $eriv;
						}
					}
					$maara++;
				}

				//	Lis�t��n viel� 2 tyhj�� rivi� loppuun
				$maara += 2;
				$gokfrom = "filesisaan";
			}
			else {

				//	Liitetiedosto ei kelpaa
				echo $retval;
				$tee = "";
			}
		}
		elseif (isset($_FILES['tositefile']['error']) and $_FILES['tositefile']['error'] != 4) {
			// nelonen tarkoittaa, ettei mit��n file� uploadattu.. eli jos on joku muu errori niin ei p��stet� eteenp�in
			echo "<font class='error'>".t("Tositetiedoston sis��nluku ep�onnistui")."! (Error: ".$_FILES['userfile']['error'].")</font><br>\n";
			$tee = "";
		}

		// turvasumma kotivaluutassa
		$turvasumma = round($summa * $kurssi, 2);
		// turvasumma valuutassa
		$turvasumma_valuutassa = $summa;

		$kuittiok = 0; // Onko joku vienneist� kassa-tili, jotta kuitti voidaan tulostaa
		$isumma_valuutassa = array();

		for ($i=1; $i<$maara; $i++) {

 			// K�sitell��nk� rivi??
			if (strlen($itili[$i]) > 0 or strlen($isumma[$i]) > 0) {

				$isumma[$i] = str_replace (",", ".", $isumma[$i]);

				// Oletussummalla korvaaminen mahdollista
				if ($turvasumma_valuutassa > 0) {
					// Summan vastaluku k�ytt��n
					if (substr($isumma[$i], -1) == "%") {

						$isummanumeric = preg_replace("/[^0-9\.]/", "", $isumma[$i]);

						if ($isumma[$i]{0} == '-') {
							$isumma[$i] = round(-1 * ($turvasumma_valuutassa * ($isummanumeric/100)), 2);
						}
						else {
							$isumma[$i] = round(1 * ($turvasumma_valuutassa * ($isummanumeric/100)), 2);
						}
					}
					elseif ($isumma[$i] == '-') {
						$isumma[$i] = -1 * $turvasumma_valuutassa;
					}
					elseif ($isumma[$i] == '+') {
						$isumma[$i] = 1 * $turvasumma_valuutassa;
					}
					// Kopioidaan summa
					elseif (strlen($itili[$i]) > 0 and $isumma[$i] == 0) {
						$isumma[$i] = $turvasumma_valuutassa;
					}
				}

				// otetaan valuuttasumma talteen
				$isumma_valuutassa[$i] = $isumma[$i];
				// k��nnet��n kotivaluuttaan
				$isumma[$i] = round($isumma[$i] * $kurssi, 2);

				if (strlen($selite) > 0 and strlen($iselite[$i]) == 0) { // Siirret��n oletusselite tili�inneille
					$iselite[$i] = $selite;
				}

				if (strlen($iselite[$i]) == 0 and strlen($comments) == 0) { // Selite ja kommentti puuttuu
					$ivirhe[$i] = t('Rivilt� puuttuu selite').'<br>';
					$gok = 1;
				}

				if ($isumma[$i] == 0) { // Summa puuttuu
					$ivirhe[$i] .= t('Rivilt� puuttuu summa').'<br>';
					$gok = 1;
				}

				$ulos 		= "";
				$virhe 		= "";
				$tili 		= $itili[$i];
				$summa 		= $isumma[$i];
				$totsumma  += $summa;
				$selausnimi = "itili['.$i.']"; // Minka niminen mahdollinen popup on?
				$vero 		= "";
				$tositetila = "X";
				$kustp_tark		= $ikustp[$i];		// n�m� muuttujat menev�t tarkistatiliointi.inc:iin tarkistukseen, mik�li on pakollisia kentti� tilikartan takaata
				$kohde_tark		= $ikohde[$i];      // n�m� muuttujat menev�t tarkistatiliointi.inc:iin tarkistukseen, mik�li on pakollisia kentti� tilikartan takaata
				$projekti_tark	= $iprojekti[$i];   // n�m� muuttujat menev�t tarkistatiliointi.inc:iin tarkistukseen, mik�li on pakollisia kentti� tilikartan takaata

				if (isset($toimittajaid) and $toimittajaid > 0) {
					$tositeliit = $toimrow["tunnus"];
				}
				elseif (isset($asiakasid) and $asiakasid > 0) {
					$tositeliit = $asrow['tunnus'];
				}
				else {
					$tositeliit = 0;
				}

				require "inc/tarkistatiliointi.inc";

				if ($vero!='') $ivero[$i]=$vero; //Jos meill� on hardkoodattuvero, otetaan se k�ytt��n

				if (isset($ivirhe[$i]))	{
					$ivirhe[$i] .= $virhe;
				}

				if (!isset($ivirhe[$i]) and strlen($virhe) > 0) {
					$ivirhe[$i] = $virhe;
				}

				$iulos[$i] = $ulos;

				if ($ok == 0) { // Sielt� kenties tuli p�ivitys tilinumeroon
					if ($itili[$i] != $tili) { // Annetaan k�ytt�j�n p��tt�� onko ok
						$itili[$i] = $tili;
						$gok = 1; // Tositetta ei kirjoiteta kantaan viel�
					}
					else {
						if ($itili[$i] == $yhtiorow['kassa']) $kassaok = 1;
					}
				}
				else {
					$gok = $ok; // Nostetaan virhe ylemm�lle tasolle
				}
			}
		}

		if (count($isumma_valuutassa) == 0) {
			$gok = 1;
		}

		$kuittivirhe = "";

		if ($kuitti != '') {
			if ($kassaok == 0) {
				$gok = 1;
				$kuittivirhe = "<font class='error'>".t("Pyysit kuittia, mutta kassatilille ei ole vientej�")."</font><br>\n";
			}

			if ($nimi == '' and $toimrow["nimi"] == '' and $asrow['nimi'] == '') {
				$gok = 1;
				$kuittivirhe .= "<font class='error'>".t("Kuitille on annettava nimi tai asiakas tai toimittaja")."</font><br>\n";
			}
		}

		$heittovirhe = 0;

		if (abs($totsumma) >= 0.01 and $heittook == '') {
			$heittovirhe = 1;
			$gok = 1;
		}

		// jos loppusumma on isompi kuin tietokannassa oleva tietuen koko (10 numeroa + 2 desimaalia), niin herjataan
		if ($summa != '' and abs($summa) > 0) {
			if (abs($summa) > 9999999999.99) {
				echo "<font class='error'>".t("VIRHE: liian iso summa")."!</font><br/>\n";
				$gok=1;
			}
		}

 		// Jossain tapahtui virhe
		if ($gok == 1) {
			if ($gokfrom == "") {
				echo "<br><font class='error'>".t("HUOM").": ".t("Jossain oli virheit�/muutoksia")."!</font><br>\n";
			}

			$tee = '';
		}

		$summa = $turvasumma;
	}

	// Kirjoitetaan tosite jos tiedot ok!
	if ($tee == 'I' and isset($teetosite)) {

		if (trim($nimi) != '') {
			$qlisa = " nimi = '{$nimi}',";
		}

		$paivitetaanko = false;

		if (isset($tunnus) and trim($tunnus) > 0) {

			$tunnus = (int) $tunnus;

			$query = "	UPDATE lasku SET
						tapvm = '{$tpv}-{$tpk}-{$tpp}',
						{$qlisa}
						alv_tili = '{$alv_tili}',
						comments = '{$comments}'
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$tunnus}'";
			$result = pupe_query($query);

			$paivitetaanko = true;

			$maara = count($ed_iliitostunnus)+1;
		}
		else {
			$query = "	INSERT into lasku set
						yhtio 		= '{$kukarow['yhtio']}',
						tapvm 		= '{$tpv}-{$tpk}-{$tpp}',
						{$qlisa}
						tila 		= 'X',
						alv_tili 	= '{$alv_tili}',
						comments	= '{$comments}',
						laatija 	= '{$kukarow['kuka']}',
						luontiaika 	= now()";
			$result = pupe_query($query);
			$tunnus = mysql_insert_id ($link);
		}

		if (isset($avaavatase) and $avaavatase == 'joo') {
			$query = "	UPDATE tilikaudet SET
						avaava_tase = '{$tunnus}'
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$tilikausi}'";
			$avaavatase_result = pupe_query($query);
		}

		if ($kuva) {
			// p�ivitet��n kuvalle viel� linkki toiseensuuntaa
			$query = "UPDATE liitetiedostot set liitostunnus = '{$tunnus}', selite = '{$selite} {$summa}' where tunnus = '{$kuva}'";
			$result = pupe_query($query);
		}

		foreach ($ed_iliitostunnus as $liit_indx => $liit) {
			$iliitostunnus[$liit_indx] = $liit;
		}

		// Tehd��n tili�innit
		for ($i=1; $i<$maara; $i++) {
			if (strlen($itili[$i]) > 0) {

				$tili				= $itili[$i];
				$kustp				= $ikustp[$i];
				$kohde				= $ikohde[$i];
				$projekti			= $iprojekti[$i];
				$summa				= $isumma[$i];
				$vero				= $ivero[$i];
				$selite 			= $iselite[$i];
				$summa_valuutassa	= $isumma_valuutassa[$i];
				$valkoodi 			= $valkoodi;
				$liitos				= mysql_real_escape_string($iliitos[$i]);
				$liitostunnus		= mysql_real_escape_string($iliitostunnus[$i]);

				require("inc/teetiliointi.inc");

				$itili[$i]				= '';
				$ikustp[$i]				= '';
				$ikohde[$i]				= '';
				$iprojekti[$i]			= '';
				$isumma[$i]				= '';
				$ivero[$i]				= '';
				$iselite[$i]			= '';
				$isumma_valuutassa[$i]	= '';
				$iliitos[$i]			= '';
				$iliitostunnus[$i]		= '';
			}
		}
		if ($kuitti != '') require("inc/kuitti.inc");

		$tee		= "";
		$selite		= "";
		$fnimi		= "";
		$summa		= "";
		$nimi		= "";
		$kuitti		= "";
		$kuva 		= "";
		$turvasumma_valuutassa = "";
		$valkoodi 	= "";

		echo "<font class='message'>".t("Tosite luotu")."!</font>\n";

		echo "	<form action = 'muutosite.php' method='post'>
				<input type='hidden' name='tee' value='E'>
				<input type='hidden' name='tunnus' value='{$tunnus}'>
				<input type='Submit' value='".t("N�yt� tosite")."'>
				</form><br><hr><br>";
	}
	else {
		$tee = "";
	}

	if ($tee == '') {
		if ($maara=='') $maara = '3'; //n�ytet��n defaulttina kaks

		//p�iv�m��r�n tarkistus
		$tilalk = explode("-", $yhtiorow["tilikausi_alku"]);
		$tillop = explode("-", $yhtiorow["tilikausi_loppu"]);

		$tilalkpp = $tilalk[2];
		$tilalkkk = $tilalk[1]-1;
		$tilalkvv = $tilalk[0];

		$tilloppp = $tillop[2];
		$tillopkk = $tillop[1]-1;
		$tillopvv = $tillop[0];

		echo "	<script language='javascript'>
					function tositesumma() {
						var summa = 0;

						for (var i=0; i<document.tosite.elements.length; i++) {
				         	if (document.tosite.elements[i].type == 'text' && document.tosite.elements[i].name.substring(0,6) == 'isumma') {

								if (document.tosite.elements[i].value == '+') {
									summa+=1.0*document.tosite.summa.value.replace(',','.');
								}
								else if (document.tosite.elements[i].value == '-') {
									summa-=1.0*document.tosite.summa.value.replace(',','.');
								}
								else {
									summa+=1.0*document.tosite.elements[i].value.replace(',','.');
								}
							}
				    	}

						document.tosite.tositesum.value=Math.round(summa*100)/100;
					}
				</script> ";

		echo "	<script language='javascript'>
					function selitejs() {

						var selitetxt = document.tosite.selite.value;

						for (var i=0; i<document.tosite.elements.length; i++) {
				         	if (document.tosite.elements[i].type == 'text' && document.tosite.elements[i].name.substring(0,7) == 'iselite') {
								document.tosite.elements[i].value=selitetxt;
							}
				    	}
					}
				</script> ";

		echo "	<SCRIPT LANGUAGE=JAVASCRIPT>

				function verify(){
					var pp = document.tosite.tpp;
					var kk = document.tosite.tpk;
					var vv = document.tosite.tpv;

					pp = Number(pp.value);
					kk = Number(kk.value)-1;
					vv = Number(vv.value);

					if (vv < 1000) {
						vv = vv+2000;
					}

					var dateSyotetty = new Date(vv,kk,pp);
					var dateTallaHet = new Date();
					var ero = (dateTallaHet.getTime() - dateSyotetty.getTime()) / 86400000;

					var tilalkpp = {$tilalkpp};
					var tilalkkk = {$tilalkkk};
					var tilalkvv = {$tilalkvv};
					var dateTiliAlku = new Date(tilalkvv,tilalkkk,tilalkpp);
					dateTiliAlku = dateTiliAlku.getTime();


					var tilloppp = {$tilloppp};
					var tillopkk = {$tillopkk};
					var tillopvv = {$tillopvv};
					var dateTiliLoppu = new Date(tillopvv,tillopkk,tilloppp);
					dateTiliLoppu = dateTiliLoppu.getTime();

					dateSyotetty = dateSyotetty.getTime();

					if(dateSyotetty < dateTiliAlku || dateSyotetty > dateTiliLoppu) {
						var msg = '".t("VIRHE: Sy�tetty p�iv�m��r� ei sis�lly kuluvaan tilikauteen")."!';

						if(alert(msg)) {
							return false;
						}
						else {
							return false;
						}
					}

					if(ero >= 30) {
						var msg = '".t("Oletko varma, ett� haluat p�iv�t� laskun yli 30pv menneisyyteen")."?';
						return confirm(msg);
					}
					if(ero <= -14) {
						var msg = '".t("Oletko varma, ett� haluat p�iv�t� laskun yli 14pv tulevaisuuteen")."?';
						return confirm(msg);
					}

					if (vv < dateTallaHet.getFullYear()) {
						if (5 < dateTallaHet.getDate()) {
							var msg = '".t("Oletko varma, ett� haluat p�iv�t� laskun menneisyyteen")."?';
							return confirm(msg);
						}
					}
					else if (vv == dateTallaHet.getFullYear()) {
						if (kk < dateTallaHet.getMonth() && 5 < dateTallaHet.getDate()) {
							var msg = '".t("Oletko varma, ett� haluat p�iv�t� laskun menneisyyteen")."?';
							return confirm(msg);
						}
					}


				}
			</SCRIPT>";

		$formi = 'tosite';
		$kentta = 'tpp';

		echo "<br>\n";
		echo "<font class='head'>".t("Tositteen otsikkotiedot").":</font>\n";

		echo "<form name='tosite' action='tosite.php' method='post' enctype='multipart/form-data' onSubmit = 'return verify()' autocomplete='off'>\n";
		echo "<input type='hidden' name='tee' value='I'>\n";

		echo "<input type='hidden' name='tiliointirivit' value='",urlencode(serialize($tiliointirivit)),"' />";
		echo "<input type='hidden' name='tunnus' value='{$tunnus}' />";

		if (isset($tullaan) and $tullaan == 'muutosite' and ((!isset($toimittajaid) and !isset($asiakasid)) or ($toimittajaid == 0 and $asiakasid == 0))) {
			echo "<input type='hidden' name='ed_iliitostunnus' value='",urlencode(serialize($ed_iliitostunnus)),"' />";
			echo "<input type='hidden' name='iliitos' value='",urlencode(serialize($iliitos)),"' />";
			echo "<input type='hidden' name='tullaan' value='{$tullaan}' />";
		}

		if ((isset($gokfrom) and $gokfrom == 'avaavatase') or (isset($tilikausi) and is_numeric($tilikausi))) {
			echo "<input type='hidden' name='avaavatase' value='joo' />";
			echo "<input type='hidden' name='tilikausi' value='{$tilikausi}' />";
		}

		// Uusi tosite
		// Tehd��n haluttu m��r� tili�intirivej�
		$tilmaarat = array("3","5","9","13","17","21","25","29","33","41","51","101","151", "201", "301", "401", "501", "601", "701", "801", "901", "1001");

		if (isset($gokfrom) and $gokfrom != "") {
			// Valitaan sopiva tili�intim��r� kun tullaan palkkatositteelta
			foreach ($tilmaarat as $tilmaara) {
				if ($tilmaara > $maara) {
					$maara = $tilmaara;
					break;
				}
			}
		}

		if (isset($tunnus) and $tunnus > 0 and count($tiliointirivit) > 0) {

			$query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tila = 'X' and tunnus = '{$tunnus}'";
			$lasku_chk_res = pupe_query($query);
			$lasku_chk_row = mysql_fetch_assoc($lasku_chk_res);

			$comments = $lasku_chk_row['comments'];

			$itili = $ikustp = $ikohde = $iprojekti = $isumma = $isumma_valuutassa = $ivero = $iselite = array();

			$skipattuja = 0;

			foreach ($tiliointirivit as $xxx => $rivix) {

				$query = "SELECT * FROM tiliointi WHERE yhtio = '{$kukarow['yhtio']}' AND ltunnus = '{$tunnus}' AND tunnus = '{$rivix}'";
				$info_res = pupe_query($query);
				$info_row = mysql_fetch_assoc($info_res);

				if ($info_row['korjattu'] != '') {
					$skipattuja++;
					continue;
				}

				$xxx -= $skipattuja;

				$itili[$xxx] = $info_row['tilino'];
				$ikustp[$xxx] = $info_row['kustp'];
				$ikohde[$xxx] = $info_row['kohde'];
				$iprojekti[$xxx] = $info_row['projekti'];
				$isumma[$xxx] = $info_row['summa'];
				$isumma_valuutassa[$xxx] = $info_row['summa_valuutassa'];
				$ivero[$xxx] = $info_row['vero'];
				$iselite[$xxx] = $info_row['selite'];
			}

			$maara = count($tiliointirivit) + 1 - $skipattuja;

			if (!in_array($maara, $tilmaarat)) {
				$tilmaarat[] = $maara;
				sort($tilmaarat);
			}
		}

		$sel = array();
		$sel[$maara] = "selected";

		echo "<table>
			<tr>
			<th>".t("Tili�intirivien m��r�")."</th>
			<td>
			<select name='maara' onchange='submit();'>";

		foreach ($tilmaarat as $tilmaara) {
			echo "<option {$sel[$tilmaara]} value='{$tilmaara}'>".($tilmaara-1)."</option>";
		}

		echo "</select></td>";

		echo "<th nowrap>".t("Liit� toimittaja")."</th>";
		echo "<td>";

		if ($toimittajaid > 0) {
			echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>{$toimrow['ytunnus']} {$toimrow['nimi']}\n";
		// }
		// else {
		}
		echo "<input type = 'text' name = 'toimittaja_y' size='20'></td><td class='back'><input type = 'submit' value = '".t("Etsi")."'>";

		echo "</td>\n";
		echo "</tr>\n";

		$tpp = !isset($tpp) ? date('d') : $tpp;
		$tpk = !isset($tpk) ? date('m') : $tpk;
		$tpv = !isset($tpv) ? date('Y') : $tpv;

		echo "<tr>\n";
		echo "<th>".t("Tositteen p�iv�ys")."</th>\n";
		echo "<td><input type='text' name='tpp' maxlength='2' size='2' value='{$tpp}'>\n";
		echo "<input type='text' name='tpk' maxlength='2' size='2' value='{$tpk}'>\n";
		echo "<input type='text' name='tpv' maxlength='4' size='4' value='{$tpv}'> ".t("ppkkvvvv")." {$tapvmvirhe}</td>\n";

		echo "<th nowrap>".t("tai")." ".t("Liit� asiakas")."</th>";
		echo "<td>";

		if ($asiakasid > 0) {
			echo "<input type='hidden' name='asiakasid' value='{$asiakasid}'>{$asrow['ytunnus']} {$asrow['nimi']} {$asrow['nimitark']}<br>{$asrow['toim_ovttunnus']} {$asrow['toim_nimi']} {$asrow['toim_nimitark']} {$asrow['toim_postitp']}\n";
		// }
		// else {
		}
		echo "<input type = 'text' name = 'asiakas_y' size='20'></td><td class='back'><input type = 'submit' value = '".t("Etsi")."'>";

		echo "</td>\n";
		echo "</tr>\n";

		if (!isset($turvasumma_valuutassa)) $turvasumma_valuutassa = $summa;

		echo "<tr><th>".t("Summa")."</th><td><input type='text' name='summa' value='{$turvasumma_valuutassa}' onchange='javascript:tositesumma();' onkeyup='javascript:tositesumma();'>\n";

		$query = "	SELECT nimi, tunnus
					FROM valuu
					WHERE yhtio = '{$kukarow['yhtio']}'
					ORDER BY jarjestys";
		$vresult = pupe_query($query);

		echo " <select name='valkoodi'>\n";

		while ($vrow = mysql_fetch_assoc($vresult)) {
			$sel="";
			if (($vrow['nimi'] == $yhtiorow["valkoodi"] and $valkoodi == "") or ($vrow["nimi"] == $valkoodi)) {
				$sel = "selected";
			}
			echo "<option value='{$vrow['nimi']}' {$sel}>{$vrow['nimi']}</option>\n";
		}

		echo "</select>\n";
		echo "</td>\n";
		echo "<th>".t("tai")." ".t("Sy�t� nimi")."</th><td><input type='text' size='20' name='nimi' value='{$nimi}'></td></tr>\n";


		echo "<tr><th>".t("Tositteen kuva/liite")."</th>\n";

		if (strlen($kuva) > 0) {
			echo "<td>".t("Kuva jo tallessa")."!<input name='kuva' type='hidden' value = '{$kuva}'></td>\n";
		}
		else {
			echo "<td><input type='hidden' name='MAX_FILE_SIZE' value='8000000'><input name='userfile' type='file'></td>\n";
		}

		echo "<th>".t("Tulosta kuitti")."</th><td>";

		if ($kukarow['kirjoitin'] > 0) {

			if ($kuitti != '') {
				$kuitti = 'checked';
			}

			echo "<input type='checkbox' name='kuitti' {$kuitti}>\n";
		}
		else {
			echo "<font class='message'>".t("Sinulla ei ole oletuskirjoitinta. Et voi tulostaa kuitteja")."!</font>\n";
		}

		echo " {$kuittivirhe}</td></tr>\n";

		// tutkitaan ollaanko jossain toimipaikassa alv-rekister�ity
		$query = "	SELECT *
					FROM yhtion_toimipaikat
					WHERE yhtio = '{$kukarow['yhtio']}'
					and maa != ''
					and vat_numero != ''
					and toim_alv != ''";
		$alhire = pupe_query($query);

		// ollaan alv-rekister�ity
		if (mysql_num_rows($alhire) >= 1) {

			echo "<tr>\n";
			echo "<th>".t("Alv tili")."</th><td colspan='3'>\n";
			echo "<select name='alv_tili'>\n";
			echo "<option value='{$yhtiorow['alv']}'>{$yhtiorow['alv']} - {$yhtiorow['nimi']}, {$yhtiorow['kotipaikka']}, {$yhtiorow['maa']}</option>\n";

			while ($vrow = mysql_fetch_assoc($alhire)) {
				$sel = "";
				if ($alv_tili == $vrow['toim_alv']) {
					$sel = "selected";
				}
				echo "<option value='{$vrow['toim_alv']}' {$sel}>{$vrow['toim_alv']} - {$vrow['nimi']}, {$vrow['kotipaikka']}, {$vrow['maa']}</option>\n";
			}

			echo "</select>\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
		else {
			$tilino_alv = $yhtiorow["alv"];
			echo "<input type='hidden' name='alv_tili' value='{$tilino_alv}'>\n";
		}

		if (is_readable("excel_reader/reader.php")) {
			$excel = ".xls, ";
		}
		else {
			$excel = "";
		}

		echo "<tr>\n";
		echo "<th>".t("Tositteen kommentti")."</th>\n";
		echo "<td colspan='3'><input type='text' name='comments' value='{$comments}' size='60'></td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<th>".t("Tili�intien selitteet")."</th>\n";
		echo "<td colspan='3'><input type='text' name='selite' value='{$selite}' maxlength='150' size='60' onchange='javascript:selitejs();' onkeyup='javascript:selitejs();'></td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		echo "<br><font class='head'>".t("Lue tositteen rivit tiedostosta").":</font>\n";

		echo "<table>
				<tr>
					<th>".t("Valitse tiedosto")."</th>
					<td><input type='file' name='tositefile' onchage='submit()'>  <font class='info'>".t("Vain {$excel}.txt ja .cvs tiedosto sallittuja")."</td>
				</tr>
			</table>";

		echo "<br><font class='head'>".t("Sy�t� tositteen rivit").":</font>\n";

		echo "<table>\n";

		for ($i=1; $i<$maara; $i++) {

			if ($i == 1) {
				echo "<tr>\n";
				echo "<th width='200'>".t("Tili")."</th>\n";
				echo "<th>".t("Tarkenne")."</th>\n";
				echo "<th>".t("Summa")."</th>\n";
				echo "<th>".t("Vero")."</th>\n";
				echo "<th>",t("Liitos"),"</th>";
				echo "</tr>\n";
			}

			echo "<tr>\n";

			if (!isset($iulos[$i]) or $iulos[$i] == '') {
				//Annetaan selv�kielinen nimi
				$tilinimi = '';

				if (isset($itili[$i]) and $itili[$i] != '') {
					$query = "	SELECT nimi
								FROM tili
								WHERE yhtio = '{$kukarow['yhtio']}' and tilino = '{$itili[$i]}'";
					$vresult = pupe_query($query);

					if (mysql_num_rows($vresult) == 1) {
						$vrow = mysql_fetch_assoc($vresult);
						$tilinimi = $vrow['nimi'];
					}
				}
				echo "<td width='200' valign='top'>".livesearch_kentta("tosite", "TILIHAKU", "itili[$i]", 170, $itili[$i], "EISUBMIT")." {$tilinimi}</td>\n";
			}
			else {
				echo "<td width='200' valign='top'>{$iulos[$i]}</td>\n";
			}

			echo "<td>\n";

			$query = "	SELECT tunnus, nimi, koodi
						FROM kustannuspaikka
						WHERE yhtio = '{$kukarow['yhtio']}'
						and tyyppi = 'K'
						and kaytossa != 'E'
						ORDER BY nimi";
			$result = pupe_query($query);

			if (mysql_num_rows($result) > 0) {
				echo "<select name = 'ikustp[{$i}]' style='width: 140px'><option value = ' '>".t("Ei kustannuspaikkaa");

				while ($kustannuspaikkarow = mysql_fetch_assoc($result)) {
					$valittu = "";
					if (isset($ikustp[$i]) and $kustannuspaikkarow["tunnus"] == $ikustp[$i]) {
						$valittu = "SELECTED";
					}
					echo "<option value = '{$kustannuspaikkarow['tunnus']}' {$valittu}>{$kustannuspaikkarow['koodi']} {$kustannuspaikkarow['nimi']}\n";
				}
				echo "</select><br>\n";
			}

			$query = "	SELECT tunnus, nimi, koodi
						FROM kustannuspaikka
						WHERE yhtio = '{$kukarow['yhtio']}'
						and tyyppi = 'O'
						and kaytossa != 'E'
						ORDER BY nimi";
			$result = pupe_query($query);

			if (mysql_num_rows($result) > 0) {
				echo "<select name = 'ikohde[{$i}]' style='width: 140px'><option value = ' '>".t("Ei kohdetta");

				while ($kustannuspaikkarow = mysql_fetch_assoc($result)) {
					$valittu = "";
					if (isset($ikohde[$i]) and $kustannuspaikkarow["tunnus"] == $ikohde[$i]) {
						$valittu = "SELECTED";
					}
					echo "<option value = '{$kustannuspaikkarow['tunnus']}' {$valittu}>{$kustannuspaikkarow['koodi']} {$kustannuspaikkarow['nimi']}\n";
				}
				echo "</select><br>\n";
			}

			$query = "	SELECT tunnus, nimi, koodi
						FROM kustannuspaikka
						WHERE yhtio = '{$kukarow['yhtio']}'
						and tyyppi = 'P'
						and kaytossa != 'E'
						ORDER BY nimi";
			$result = pupe_query($query);

			if (mysql_num_rows($result) > 0) {
				echo "<select name = 'iprojekti[{$i}]' style='width: 140px'><option value = ' '>".t("Ei projektia");

				while ($kustannuspaikkarow = mysql_fetch_assoc($result)) {
					$valittu = "";
					if (isset($iprojekti[$i]) and $kustannuspaikkarow["tunnus"] == $iprojekti[$i]) {
						$valittu = "SELECTED";
					}
					echo "<option value = '{$kustannuspaikkarow['tunnus']}' {$valittu}>{$kustannuspaikkarow['koodi']} {$kustannuspaikkarow['nimi']}\n";
				}
				echo "</select>\n";
			}

			echo "</td>\n";
			echo "<td valign='top' align='right'><input type='text' size='13' style='text-align: right;' name='isumma[{$i}]' value='{$isumma_valuutassa[$i]}' onchange='javascript:tositesumma();' onkeyup='javascript:tositesumma();'> {$valkoodi}<br>&nbsp;&nbsp;{$isumma[$i]}&nbsp;&nbsp;{$valkoodi}</td>\n";

			if (!isset($hardcoded_alv) or $hardcoded_alv != 1) {
				echo "<td valign='top'>" . alv_popup('ivero['.$i.']', $ivero[$i]) . "</td>\n";
			}
			else {
				echo "<td></td>\n";
			}

			echo "<td>";

			echo "<input type='hidden' name='iliitostunnus[0]' value='default' />";

			if ($toimittajaid > 0) {

				$chk = (isset($iliitostunnus[$i]) and trim($iliitostunnus[$i]) == $toimittajaid) ? ' checked' : ((isset($ed_iliitostunnus[$i]) and trim($ed_iliitostunnus[$i]) == $toimittajaid) ? ' checked' : '');

				echo "<input type='hidden' name='ed_iliitostunnus[{$i}]' value='{$ed_iliitostunnus[$i]}' />";
				echo "<input type='hidden' name='iliitos[{$i}]' value='toimi' />";
				echo "<input type='checkbox' name='iliitostunnus[{$i}]' value='{$toimittajaid}' {$chk} /> ";

			}

			if ($asiakasid > 0) {

				$chk = (isset($iliitostunnus[$i]) and trim($iliitostunnus[$i]) == $asiakasid) ? ' checked' : ((isset($ed_iliitostunnus[$i]) and trim($ed_iliitostunnus[$i]) == $asiakasid) ? ' checked' : '');

				echo "<input type='hidden' name='ed_iliitostunnus[{$i}]' value='{$ed_iliitostunnus[$i]}' />";
				echo "<input type='hidden' name='iliitos[{$i}]' value='asiakas' />";
				echo "<input type='checkbox' name='iliitostunnus[{$i}]' value='{$asiakasid}' {$chk} /> ";

			}

			if (isset($iliitos[$i]) and trim($iliitos[$i]) != '' and ((isset($iliitostunnus[$i]) and trim($iliitostunnus[$i]) != '') or (isset($ed_iliitostunnus[$i]) and trim($ed_iliitostunnus[$i]) != ''))) {

				$tunnus_chk = isset($iliitostunnus[$i]) ? $iliitostunnus[$i] : $ed_iliitostunnus[$i];
				
				$query = "SELECT nimi, nimitark FROM {$iliitos[$i]} WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$tunnus_chk}'";
				$asiakasres = pupe_query($query);
				$asiakasrow = mysql_fetch_assoc($asiakasres);
				
				echo "{$asiakasrow['nimi']} {$asiakasrow['nimitark']}";

			}

			echo "</td>\n";

			echo "<td class='back'>";
			if (isset($ivirhe[$i])) echo "<font class='error'>{$ivirhe[$i]}</font>";
			echo "</td>\n";
			echo "</tr>\n";

			echo "<tr><td colspan='5' nowrap><input type='text' name='iselite[{$i}]' value='{$iselite[$i]}' maxlength='150' size='80' placeholder='".t("Selite")."'></td></tr>\n";
			echo "<tr style='height: 5px;'></tr>\n";
		}

		echo "<tr><th colspan='2'>".t("Tosite yhteens�").":</th><td><input type='text' size='13' style='text-align: right;' name='tositesum' value='' readonly> {$valkoodi}</td><td></td></tr>\n";
		echo "</table><br>\n";

		echo "<script language='javascript'>javascript:tositesumma();</script>";

		if ($heittovirhe == 1) {

			$heittotila = '';

			if ($heittook != '') {
				$heittotila = 'checked';
			}

			echo "<font class='error'>".t("HUOM: Tosite ei t�sm��").":</font> <input type='checkbox' name='heittook' {$heittotila}> ".t("Hyv�ksy heitto").".<br><br>";
		}

		echo "<input type='submit' name='teetosite' value='".t("Tee tosite")."'></form>\n";

	}

	require "inc/footer.inc";
?>