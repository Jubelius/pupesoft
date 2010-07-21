<?php
	if (!isset($link)) require "inc/parametrit.inc";

	enable_ajax();

	if ($livesearch_tee == "TILIHAKU") {
		livesearch_tilihaku();
		exit;
	}

	echo "<font class='head'>".t("Uusi muu tosite")."</font><hr>\n";

	$kurssi = 1;

	if ($tee == 'TOIMHAKU') {

		require ("inc/kevyt_toimittajahaku.inc");

		if ($toimittajaid > 0) {
			$tee = "";
		}

		if ($monta == 0) {
			$tee = "N";
		}
	}

	if ($tee == 'ASHAKU') {

		require ("inc/asiakashaku.inc");

		if ($asiakasid > 0) {
			$tee = "";
		}

		if ($monta == 0) {
			$tee = "N";
		}
	}

	if ($toimittajaid > 0) {

		$query = "SELECT * FROM toimi WHERE tunnus = '$toimittajaid'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo t("Toimittajaa")." $ytunnus ".t("ei l�ytynytk��n")."!";
			exit;
		}

		$toimasrow = mysql_fetch_assoc($result);
	}
	elseif ($asiakasid > 0) {

		$query = "SELECT * FROM asiakas WHERE tunnus = '$asiakasid'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo t("Asiakasta")." $ytunnus ".t("ei l�ytynytk��n")."!";
			exit;
		}

		$toimasrow = mysql_fetch_assoc($result);
	}

	// Tarkistetetaan sy�tteet perustusta varten
	if ($tee == 'I') {
		$totsumma = 0;
		$summa = str_replace ( ",", ".", $summa);
		$gok  = 0;
		$tpk += 0;
		$tpp += 0;
		$tpv += 0;
		if ($tpv < 1000) $tpv += 2000;

		if (isset($gokfrom) and $gokfrom == "palkkatosite") {
			$gok = 1;
		}

		if (!checkdate($tpk, $tpp, $tpv)) {
			$tapvmvirhe = "<font class='error'>".t("Virheellinen tapahtumapvm")."</font>";
			$gok = 1;
		}

		if ($valkoodi != $yhtiorow["valkoodi"] and $gok == 0) {

			// koitetaan hakea maksup�iv�n kurssi
			$query = "	SELECT *
						FROM valuu_historia
						WHERE kotivaluutta = '$yhtiorow[valkoodi]'
						AND valuutta = '$valkoodi'
						AND kurssipvm <= '$tpv-$tpk-$tpp'
						ORDER BY kurssipvm DESC
						LIMIT 1";
			$valuures = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($valuures) == 1) {
				$valuurow = mysql_fetch_assoc($valuures);
				$kurssi = $valuurow["kurssi"];
			}
			else {
				echo "<font class='error'>".t("Ei l�ydetty sopivaa kurssia!")."</font><br>\n";
				$gok = 1;
			}
		}

		// Talletetaan k�ytt�j�n nimell� tositteen/liitteen kuva, jos sellainen tuli
		// koska, jos tulee virheit� tiedosto katoaa. Kun kaikki on ok, annetaan sille oikea nimi
		if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
			$retval = tarkasta_liite("userfile", array("PNG", "JPG", "GIF", "PDF"));

			if ($retval === true) {
				$kuva = tallenna_liite("userfile", "lasku", 0, "");
			}
			else {
				echo $retval;
				$tee = "";
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
				$excelrivi[][] = array();

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
											WHERE yhtio = '$kukarow[yhtio]'
											and tyyppi = 'K'
											and kaytossa != 'E'
											and nimi = '$ikustp_tsk'";
								$ikustpres = mysql_query($query) or pupe_error($query);

								if (mysql_num_rows($ikustpres) == 1) {
									$ikustprow = mysql_fetch_assoc($ikustpres);
									$ikustp[$maara] = $ikustprow["tunnus"];
								}
							}

							if ($ikustp_tsk != "" and $ikustp[$maara] == 0) {
								$query = "	SELECT tunnus
											FROM kustannuspaikka
											WHERE yhtio = '$kukarow[yhtio]'
											and tyyppi = 'K'
											and kaytossa != 'E'
											and koodi = '$ikustp_tsk'";
								$ikustpres = mysql_query($query) or pupe_error($query);

								if (mysql_num_rows($ikustpres) == 1) {
									$ikustprow = mysql_fetch_assoc($ikustpres);
									$ikustp[$maara] = $ikustprow["tunnus"];
								}
							}

							if (is_numeric($ikustp_tsk) and (int) $ikustp_tsk > 0 and $ikustp[$maara] == 0) {

								$ikustp_tsk = (int) $ikustp_tsk;

								$query = "	SELECT tunnus
											FROM kustannuspaikka
											WHERE yhtio = '$kukarow[yhtio]'
											and tyyppi = 'K'
											and kaytossa != 'E'
											and tunnus = '$ikustp_tsk'";
								$ikustpres = mysql_query($query) or pupe_error($query);

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
				$maara+=2;

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

				$isumma[$i] = str_replace ( ",", ".", $isumma[$i]);

				// Oletussummalla korvaaminen mahdollista
				if ($turvasumma_valuutassa > 0) {
					// Summan vastaluku k�ytt��n
					if ($isumma[$i] == '-') {
						$isumma[$i] = -1 * $turvasumma_valuutassa;
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
				if (strlen($iselite[$i]) == 0) { // Selite puuttuu
					$ivirhe[$i] = t('Rivilt� puuttuu selite').'<br>';
					$gok = 1;
				}

				if ($isumma[$i] == 0) { // Summa puuttuu
					$ivirhe[$i] .= t('Rivilt� puuttuu summa').'<br>';
					$gok = 1;
				}

				$ulos='';
				$virhe = '';
				$tili = $itili[$i];
				$summa = $isumma[$i];
				$totsumma += $summa;
				$selausnimi = 'itili[' . $i .']'; // Minka niminen mahdollinen popup on?
				$vero='';

				require "inc/tarkistatiliointi.inc";

				if ($vero!='') $ivero[$i]=$vero; //Jos meill� on hardkoodattuvero, otetaan se k�ytt��n

				$ivirhe[$i] .= $virhe;
				$iulos[$i] = $ulos;

				if ($ok==0) { // Sielt� kenties tuli p�ivitys tilinumeroon
					if ($itili[$i] != $tili) { // Annetaan k�ytt�j�n p��tt�� onko ok
						$itili[$i] = $tili;
						$gok = 1; // Tositetta ei kirjoiteta kantaan viel�
					}
					else {
						if ($itili[$i] == $yhtiorow['kassa']) $kassaok=1;
					}
				}
				else {
					$gok = $ok; // Nostetaan virhe ylemm�lle tasolle
				}
			}
		}

		if (count($isumma_valuutassa) == 0) {
			$gok=1;
		}

		if ($kuitti != '') {
			if ($kassaok==0) {
				$gok=1;
				echo "<font class='error'>".t("Pyysit kuittia, mutta kassatilille ei ole vientej�")."</font><br>\n";
			}
			if ($nimi == '') {
				$gok=1;
				echo "<font class='error'>".t("Kuitille on annettava nimi")."</font><br>\n";
			}
		}

		if (abs($totsumma) >= 0.01 and $heittook  == '') {
			$gok=1;
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
			echo "<br><font class='error'>".t("HUOM").": ".t("Jossain oli virheit�/muutoksia")."!</font><br>\n";
			$tee = '';
		}

		$summa = $turvasumma;
	}

	// Kirjoitetaan tosite jos tiedot ok!
	if ($tee == 'I' and isset($teetosite)) {

		if ($toimittajaid > 0 or $asiakasid > 0) {
			$qlisa = "	ytunnus 		= '$toimasrow[ytunnus]',
						nimi 			= '$toimasrow[nimi]',
						nimitark 		= '$toimasrow[nimitark]',
						osoite 			= '$toimasrow[osoite]',
						osoitetark 		= '$toimasrow[osoitetark]',
						postino 		= '$toimasrow[postino]',
						postitp 		= '$toimasrow[postitp]',
						maa 			= '$toimasrow[maa]',
						liitostunnus	= '$toimasrow[tunnus]',";
		}
		else {
			$qlisa = "	nimi			= '$nimi',";
		}


		$query = "	INSERT into lasku set
					yhtio 		= '$kukarow[yhtio]',
					tapvm 		= '$tpv-$tpk-$tpp',
					$qlisa
					tila 		= 'X',
					alv_tili 	= '$alv_tili',
					comments	= '$comments',
					laatija 	= '$kukarow[kuka]',
					luontiaika 	= now()";
		$result = mysql_query($query) or pupe_error($query);
		$tunnus = mysql_insert_id ($link);

		if ($kuva) {
			// p�ivitet��n kuvalle viel� linkki toiseensuuntaa
			$query = "UPDATE liitetiedostot set liitostunnus='$tunnus', selite='$selite $summa' where tunnus='$kuva'";
			$result = mysql_query($query) or pupe_error($query);
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

				require("inc/teetiliointi.inc");

				$itili[$i]				= '';
				$ikustp[$i]				= '';
				$ikohde[$i]				= '';
				$iprojekti[$i]			= '';
				$isumma[$i]				= '';
				$ivero[$i]				= '';
				$iselite[$i]			= '';
				$isumma_valuutassa[$i]	= '';
			}
		}
		if ($kuitti != '') require("inc/kuitti.inc");

		$tee="";
		$selite="";
		$fnimi="";
		$summa="";
		$nimi="";
		$kuitti="";
		$kuva = "";
		$turvasumma_valuutassa = "";
		$valkoodi = "";

		echo "<font class='message'>".t("Tosite luotu")."!</font>\n";

		echo "	<form action = 'muutosite.php' method='post'>
				<input type='hidden' name='tee' value='E'>
				<input type='hidden' name='tunnus' value='$tunnus'>
				<input type='Submit' value='".t("N�yt� tosite")."'>
				</form><br><hr><br>";
	}
	else {
		$tee = "";
	}

	// Uusi tosite
	if (($tee == '' or $tee == "N") and !isset($toimasrow)) {
		echo "<br><table>";
		echo "<tr><th nowrap><form action = '$PHP_SELF?tee=TOIMHAKU' method='post'>".t("Perusta tosite toimittajan Y-tunnuksen/nimen perusteella")."</td>";
		echo "<td><input type = 'text' name = 'ytunnus' size='15'></td><td class='back'><input type = 'submit' value = '".t("Etsi")."'></form></td></tr>";
		echo "<tr><th nowrap><form action = '$PHP_SELF?tee=ASHAKU' method='post'>".t("Perusta tosite asiakkaan Y-tunnuksen/nimen perusteella")."</td>";
		echo "<td><input type = 'text' name = 'ytunnus' size='15'></td><td class='back'><input type = 'submit' value = '".t("Etsi")."'></form></td></tr>";
		echo "</table>";
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
									summa+=1.0*document.tosite.summa.value;
								}
								else if (document.tosite.elements[i].value == '-') {
									summa-=1.0*document.tosite.summa.value;
								}
								else {
									summa+=1.0*document.tosite.elements[i].value;
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

					var tilalkpp = $tilalkpp;
					var tilalkkk = $tilalkkk;
					var tilalkvv = $tilalkvv;
					var dateTiliAlku = new Date(tilalkvv,tilalkkk,tilalkpp);
					dateTiliAlku = dateTiliAlku.getTime();


					var tilloppp = $tilloppp;
					var tillopkk = $tillopkk;
					var tillopvv = $tillopvv;
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

		// Tehd��n haluttu m��r� tili�intirivej�
		$sel = array();
		$sel[$maara] = "selected";

		echo "<table>
			<tr>
			<th>".t("Tili�intirivien m��r�")."</th>
			<td>
			<select name='maara' onchange='submit();'>
			<option $sel[3] value='3'>2</option>
			<option $sel[5] value='5'>4</option>
			<option $sel[9] value='9'>8</option>
			<option $sel[17] value='17'>16</option>
			<option $sel[33] value='33'>32</option>
			<option $sel[151] value='151'>150</option>
			<option $sel[201] value='201'>200</option>
			<option $sel[301] value='301'>300</option>
			<option $sel[401] value='401'>400</option>
			<option $sel[501] value='501'>500</option>
			</select></td></tr>";

		echo "<tr>\n";
		echo "<th>".t("Tositteen p�iv�ys")."</th>\n";
		echo "<td><input type='text' name='tpp' maxlength='2' size='2' value='$tpp'>\n";
		echo "<input type='text' name='tpk' maxlength='2' size='2' value='$tpk'>\n";
		echo "<input type='text' name='tpv' maxlength='4' size='4' value='$tpv'> ".t("ppkkvvvv")."</td><td class='back'>$tapvmvirhe</td>\n";
		echo "</tr>\n";
		echo "<tr><th>".t("Summa")."</th><td><input type='text' name='summa' value='$turvasumma_valuutassa' onchange='javascript:tositesumma();' onkeyup='javascript:tositesumma();'>\n";

		$query = "	SELECT nimi, tunnus
					FROM valuu
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY jarjestys";
		$vresult = mysql_query($query) or pupe_error($query);

		echo " <select name='valkoodi'>\n";

		while ($vrow = mysql_fetch_assoc($vresult)) {
			$sel="";
			if (($vrow['nimi'] == $yhtiorow["valkoodi"] and $valkoodi == "") or ($vrow["nimi"] == $valkoodi)) {
				$sel = "selected";
			}
			echo "<option value='$vrow[nimi]' $sel>$vrow[nimi]</option>\n";
		}

		echo "</select>\n";
		echo "</td></tr>\n";

		// tutkitaan ollaanko jossain toimipaikassa alv-rekister�ity
		$query = "	SELECT *
					FROM yhtion_toimipaikat
					WHERE yhtio = '$kukarow[yhtio]'
					and maa != ''
					and vat_numero != ''
					and toim_alv != ''";
		$alhire = mysql_query($query) or pupe_error($query);

		// ollaan alv-rekister�ity
		if (mysql_num_rows($alhire) >= 1) {

			echo "<tr>\n";
			echo "<th>".t("Alv tili")."</th><td>\n";
			echo "<select name='alv_tili'>\n";
			echo "<option value='$yhtiorow[alv]'>$yhtiorow[alv] - $yhtiorow[nimi], $yhtiorow[kotipaikka], $yhtiorow[maa]</option>\n";

			while ($vrow = mysql_fetch_assoc($alhire)) {
				$sel = "";
				if ($alv_tili == $vrow['toim_alv']) {
					$sel = "selected";
				}
				echo "<option value='$vrow[toim_alv]' $sel>$vrow[toim_alv] - $vrow[nimi], $vrow[kotipaikka], $vrow[maa]</option>\n";
			}

			echo "</select>\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
		else {
			$tilino_alv = $yhtiorow["alv"];
			echo "<input type='hidden' name='alv_tili' value='$tilino_alv'>\n";
		}


		if ($toimittajaid > 0) {
			echo "<tr><th>".t("Toimittaja")."</th><td><input type='hidden' name='toimittajaid' value='$toimittajaid'>$toimasrow[ytunnus] $toimasrow[nimi]\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
		elseif ($asiakasid > 0) {
			echo "<tr><th>".t("Asiakas")."</th><td><input type='hidden' name='asiakasid' value='$asiakasid'>$toimasrow[ytunnus] $toimasrow[nimi] $toimasrow[nimitark]<br>$toimasrow[toim_ovttunnus] $toimasrow[toim_nimi] $toimasrow[toim_nimitark] $toimasrow[toim_postitp]\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
		else {
			echo "<tr><th>".t("Nimi")."</th><td><input type='text' name='nimi' value='$nimi'>\n";

			if ($kukarow['kirjoitin'] > 0) {

				if ($kuitti != '') {
					$kuitti = 'checked';
				}

				echo " ".t("Tulosta kuitti")." <input type='checkbox' name='kuitti' $kuitti>\n";
			}
			else {
				echo "<font class='message'>".t("Sinulla ei ole oletuskirjoitinta. Et voi tulostaa kuitteja")."!</font>\n";
			}

			echo "</td>\n";
			echo "</tr>\n";
		}

		if(is_readable("excel_reader/reader.php")) {
			$excel = ".xls, ";
		}
		else {
			$excel = "";
		}

		echo "<tr>\n";
		echo "<th>".t("Tositteen kommentti")."</th>\n";
		echo "<td><input type='text' name='comments' value='$comments' size='60'></td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<th>".t("Tili�intien selitteet")."</th>\n";
		echo "<td><input type='text' name='selite' value='$selite' maxlength='150' size='60' onchange='javascript:selite();' onkeyup='javascript:selitejs();'></td>\n";
		echo "</tr>\n";

		echo "<tr><th>".t("Tositteen kuva/liite")."</th>\n";

		if (strlen($kuva) > 0) {
			echo "<td>".t("Kuva jo tallessa")."!<input name='kuva' type='hidden' value = '$kuva'></td>\n";
		}
		else {
			echo "<td><input type='hidden' name='MAX_FILE_SIZE' value='8000000'><input name='userfile' type='file'></td></tr>\n";
		}

		echo "</table>\n";

		echo "<br><font class='head'>".t("Lue tositteen rivit tiedostosta").":</font>\n";

		echo "<table>
				<tr>
					<th>".t("Valitse tiedosto")."</th>
					<td><input type='file' name='tositefile' onchage='submit()'>  <font class='info'>".t("Vain $excel.txt ja .cvs tiedosto sallittuja")."</td>
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
				echo "</tr>\n";
			}

			echo "<tr>\n";

			if ($iulos[$i] == '') {
				//Annetaan selv�kielinen nimi
				$tilinimi = '';

				if ($itili[$i] != '') {
					$query = "	SELECT nimi
								FROM tili
								WHERE yhtio = '$kukarow[yhtio]' and tilino = '$itili[$i]'";
					$vresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($vresult) == 1) {
						$vrow = mysql_fetch_assoc($vresult);
						$tilinimi = $vrow['nimi'];
					}
				}
				echo "<td width='200' valign='top'>".livesearch_kentta("tosite", "TILIHAKU", "itili[$i]", 170, $itili[$i], "EISUBMIT")." $tilinimi</td>\n";
			}
			else {
				echo "<td width='200' valign='top'>$iulos[$i]</td>\n";
			}

			echo "<td>\n";

			$query = "	SELECT tunnus, nimi, koodi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = 'K'
						and kaytossa != 'E'
						ORDER BY nimi";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) > 0) {
				echo "<select name = 'ikustp[$i]' style='width: 140px'><option value = ' '>".t("Ei kustannuspaikkaa");

				while ($kustannuspaikkarow=mysql_fetch_assoc ($result)) {
					$valittu = "";
					if ($kustannuspaikkarow["tunnus"] == $ikustp[$i]) {
						$valittu = "SELECTED";
					}
					echo "<option value = '$kustannuspaikkarow[tunnus]' $valittu>$kustannuspaikkarow[koodi] $kustannuspaikkarow[nimi]\n";
				}
				echo "</select><br>\n";
			}

			$query = "	SELECT tunnus, nimi, koodi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = 'O'
						and kaytossa != 'E'
						ORDER BY nimi";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) > 0) {
				echo "<select name = 'ikohde[$i]' style='width: 140px'><option value = ' '>".t("Ei kohdetta");

				while ($kustannuspaikkarow=mysql_fetch_assoc ($result)) {
					$valittu = "";
					if ($kustannuspaikkarow["tunnus"] == $ikohde[$i]) {
						$valittu = "SELECTED";
					}
					echo "<option value = '$kustannuspaikkarow[tunnus]' $valittu>$kustannuspaikkarow[koodi] $kustannuspaikkarow[nimi]\n";
				}
				echo "</select><br>\n";
			}

			$query = "	SELECT tunnus, nimi, koodi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = 'P'
						and kaytossa != 'E'
						ORDER BY nimi";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) > 0) {
				echo "<select name = 'iprojekti[$i]' style='width: 140px'><option value = ' '>".t("Ei projektia");

				while ($kustannuspaikkarow=mysql_fetch_assoc ($result)) {
					$valittu = "";
					if ($kustannuspaikkarow["tunnus"] == $iprojekti[$i]) {
						$valittu = "SELECTED";
					}
					echo "<option value = '$kustannuspaikkarow[tunnus]' $valittu>$kustannuspaikkarow[koodi] $kustannuspaikkarow[nimi]\n";
				}
				echo "</select>\n";
			}

			echo "</td>\n";
			echo "<td valign='top' align='right'><input type='text' size='13' style='text-align: right;' name='isumma[$i]' value='$isumma_valuutassa[$i]' onchange='javascript:tositesumma();' onkeyup='javascript:tositesumma();'> $valkoodi<br>&nbsp;&nbsp;$isumma[$i]&nbsp;&nbsp;$valkoodi</td>\n";

			if ($hardcoded_alv != 1) {
				echo "<td valign='top'>" . alv_popup('ivero['.$i.']', $ivero[$i]) . "</td>\n";
			}
			else {
				echo "<td></td>\n";
			}

			echo "<td class='back'><font class='error'>$ivirhe[$i]</font></td>\n";
			echo "</tr>\n";

			echo "<tr><td colspan='4' nowrap><input type='text' name='iselite[$i]' value='$iselite[$i]' maxlength='150' size='80' placeholder='".t("Selite")."'></td></tr>\n";
			echo "<tr style='height: 5px;'></tr>\n";
		}

		echo "<tr><th colspan='2'>".t("Tosite yhteens�").":</th><td><input type='text' size='13' style='text-align: right;' name='tositesum' value='' readonly> $valkoodi</td><td></td></tr>\n";
		echo "</table><br>\n";

		echo "<script language='javascript'>javascript:tositesumma();</script>";

		if ($gok == 1) {

			$heittotila = '';

			if ($heittook != '') {
				$heittotila = 'checked';
			}

			echo "<font class='error'>".t("HUOM: Tosite ei t�sm��").":</font> <input type='checkbox' name='heittook' $heittotila> ".t("Hyv�ksy heitto").".<br><br>";
		}

		echo "<input type='submit' name='teetosite' value='".t("Tee tosite")."'></form>\n";

	}

	require "inc/footer.inc";
?>