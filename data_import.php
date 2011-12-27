<?php

	// Enabloidaan, ett� Apache flushaa kaiken mahdollisen ruudulle kokoajan.
	ini_set('zlib.output_compression', 0);
	ini_set('implicit_flush', 1);
	ob_implicit_flush(1);

	// Ladataan tiedosto
	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require ("inc/parametrit.inc");

	// Ladataan tai poistetaan tiedosto
	if (isset($tee) and ($tee == "lataa_tiedosto" or $tee == "poista_file")) {

		// Tarkistetaan eka, ett� t�m� on t�m�n k�ytt�j�n file
		// Filename on muotoa: lue-data#username#yhtio#taulu#randombit#alkuperainen_filename#jarjestys.CSV.LOG
		$filen_tiedot = explode("#", $datain_filenimi);
		$kuka = $filen_tiedot[1];
		$yhtio = $filen_tiedot[2];

		$datain_filenimi = basename($datain_filenimi);

		if ($kuka != $kukarow["kuka"] or $yhtio != $kukarow["yhtio"]) {
			echo "<font class='error'>".t("Virheellinen tiedostonimi")."!</font><br>";
		}
		elseif ($tee == "lataa_tiedosto") {
			readfile($pupe_root_polku."/datain/".$datain_filenimi);
			exit;
		}
		elseif ($tee == "poista_file") {
			unlink($pupe_root_polku."/datain/".$datain_filenimi);
		}
	}

	echo "<font class='head'>".t("Datan sis��nluku")."</font><hr>";

	// Muuttujat
	$tee = isset($tee) ? trim($tee) : "";
	$table = isset($table) ? trim($table) : "";
	$laheta = isset($laheta) ? trim($laheta) : "";

	// K�sitell��n file
	if ($tee == "file" and $laheta != "") {

		$kasitellaan_tiedosto = TRUE;
		$kasitellaan_tiedosto_tyyppi = "";

		if (isset($_FILES['userfile']) and is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

			echo "<font class='message'>".t("Tarkastetaan l�hetetty tiedosto")."...</font><br><br>\n";

			$alkuperainen_filenimi = $_FILES['userfile']['name'];
			$kasiteltava_tiedosto_path = $_FILES['userfile']['tmp_name'];

			if ($_FILES['userfile']['size'] == 0) {
				echo "<font class='error'>".t("Tiedosto on tyhj�")."!</font><br>\n";
				$kasitellaan_tiedosto = FALSE;
			}

			$path_parts = pathinfo($_FILES['userfile']['name']);
			$kasitellaan_tiedosto_tyyppi = strtoupper($path_parts['extension']);

			// Vain Excel tai validi CSV!
			$return = tarkasta_liite("userfile", array("XLSX","XLS","CSV"));

			if ($return !== TRUE) {
				echo "<font class='error'>".t("V��r� tiedostomuoto")." $kasitellaan_tiedosto_tyyppi !</font><br>\n";
				$kasitellaan_tiedosto = FALSE;
			}

			if (!is_executable("/usr/bin/ssconvert") and ($kasitellaan_tiedosto_tyyppi == "XLSX" or $kasitellaan_tiedosto_tyyppi == "XLS")) {
				echo "<font class='error'>".t("Gnumeric (ssconvert) ei ole asennettu")."!</font><br>\n";
				$kasitellaan_tiedosto = FALSE;
			}

			if (!is_executable("/usr/bin/split")) {
				echo "<font class='error'>".t("Split komento ei ole asennettu")."!</font><br>\n";
				$kasitellaan_tiedosto = FALSE;
			}

			if (strpos($alkuperainen_filenimi, "#") !== FALSE or strpos($alkuperainen_filenimi, "/") !== FALSE or strpos($alkuperainen_filenimi, ":") !== FALSE) {
				echo "<font class='error'>".t("Tiedostonimess� kiellettyj� merkkej�").": '#' '/' ':' </font><br>\n";
				$kasitellaan_tiedosto = FALSE;
			}

			// Tehd��n Excel -> CSV konversio
			if ($kasitellaan_tiedosto === TRUE and ($kasitellaan_tiedosto_tyyppi == "XLS" or $kasitellaan_tiedosto_tyyppi == "XLSX")) {

				$kasiteltava_tiedosto_path_csv = $kasiteltava_tiedosto_path.".csv";

				/** M��ritell��n importattavan tiedoston tyyppi. Kaikki vaihtoehdot saa komentorivilt�: ssconvert --list-importers **/
				if ($kasitellaan_tiedosto_tyyppi == "XLSX") {
					$import_type = "--import-type=Gnumeric_Excel:xlsx";
				}
				else {
					$import_type = "--import-type=Gnumeric_Excel:excel";
				}

				$return = system("/usr/bin/ssconvert --export-type=Gnumeric_stf:stf_csv $import_type ".escapeshellarg($kasiteltava_tiedosto_path)." ".escapeshellarg($kasiteltava_tiedosto_path_csv));

				if ($return === FALSE) {
					echo "<font class='error'>".t("Tiedoston konversio ep�onnistui")."!</font><br>\n";
					$kasitellaan_tiedosto = FALSE;
				}
				else {
					$kasitellaan_tiedosto_tyyppi = "CSV";
				}

				// Poistetaan orig uploadfile
				unset($kasiteltava_tiedosto_path);

				// Otetaan uusi file muuttujaan
				$kasiteltava_tiedosto_path = $kasiteltava_tiedosto_path_csv;
			}

			// Generoidaan uusi k�ytt�j�kohtainen filenimi datain -hakemistoon. Konversion j�lkeen filename on muotoa: lue-data#username#yhtio#taulu#randombit#alkuperainen_filename#jarjestys.CSV
			$kasiteltava_filenimi = "lue-data#".$kukarow["kuka"]."#".$kukarow["yhtio"]."#".$table."#".md5(uniqid(microtime(), TRUE) . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'])."#".$alkuperainen_filenimi;
			$kasiteltava_filepath = $pupe_root_polku."/datain/";
			$kasiteltava_kokonimi = $kasiteltava_filepath.$kasiteltava_filenimi;

			// Siirret��n tiedosto datain -hakemistoon
			if (!rename($kasiteltava_tiedosto_path, $kasiteltava_kokonimi)) {
				echo "<font class='error'>".t("Tiedoston kopiointi ep�onnistui")."! $kasiteltava_tiedosto_path &raquo; $kasiteltava_kokonimi</font><br>\n";
				$kasitellaan_tiedosto = FALSE;
			}
		}
		else {
			echo "<font class='error'>".t("Et valinnut tiedostoa")."!</font><br>\n";
			$kasitellaan_tiedosto = FALSE;
		}

		// File saatu palvelimelle OK
		if ($kasitellaan_tiedosto === TRUE and $kasitellaan_tiedosto_tyyppi == "CSV") {

			// Otetaan tiedostosta ensimm�inen rivi talteen, siin� on headerit
			$file = fopen($kasiteltava_kokonimi, "r") or die (t("Tiedoston avaus ep�onnistui")."!");
			$header_rivi = fgets($file);
			fclose($file);

			// Laitetaan header fileen, koska filejen mergett�minen on nopeempaa komentorivilt�
			$header_file = $kasiteltava_filepath.md5(uniqid(microtime(), TRUE) . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
			file_put_contents($header_file, $header_rivi);

			// Splitataan tiedosto 10000 rivin osiin datain -hakemistoon
			chdir($kasiteltava_filepath);
			system("/usr/bin/split -l 10000 ".escapeshellarg($kasiteltava_kokonimi)." ".escapeshellarg($kasiteltava_filenimi."#"));

			// Poistetaan alkuper�inen
			unlink($kasiteltava_kokonimi);

			// Loopataan l�pi kaikki splitatut tiedostot
			if ($handle = opendir($kasiteltava_filepath)) {
			    while (false !== ($file = readdir($handle))) {

					// T�m� file t�m�n k�ytt�j�n t�m�n session file
					if (substr($file, 0, strlen($kasiteltava_filenimi)) == $kasiteltava_filenimi) {

						// Jos kyseess� on eka file (loppuu "aa"), ei laiteta headeri�
						if (substr($file, -2) == "aa") {
							// Renametaan alkuper�iseksi plus CSV p��te
							rename($file, $file.".CSV");
						}
						else {
							// Keksit��n temp file
							$temp_file = $kasiteltava_filepath.md5(uniqid(microtime(), TRUE) . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);

							// Concatenoidaan headerifile ja t�m� file temppi fileen
							system("cat ".escapeshellarg($header_file)." ".escapeshellarg($file)." > ".escapeshellarg($temp_file));

							// Poistetaan alkuper�inen file
							unlink($file);

							// Renametaan temppifile alkuper�iseksi plus CSV p��te
							rename($temp_file, $file.".CSV");
						}
					}
			    }
			    closedir($handle);
			}

			// Poistetaan headerifile
			unlink($header_file);

			echo "<font class='message'>".t("Tiedosto laitettu k�sittelyjonoon")."!</font><br><br>\n";
		}
		else {
			echo "<font class='error'>".t("Dataa ei k�sitelty")."!</font><br><br>\n";
		}

	}

	// Katsotaan onko k�ytt�j�ll� tiedostoja k�sittelyss�
	$tiedostoja_jonossa = 0;
	$omia_tiedostoja_jonossa = 0;

	if ($handle = opendir($pupe_root_polku."/datain")) {
	    while (false !== ($file = readdir($handle))) {
			// T�m� file on valmis lue-data file

			if (substr($file, 0, 9) == "lue-data#" and substr($file, -4) == ".CSV") {
				$tiedostoja_jonossa++;

				// T�m� on t�m�n k�ytt�j�n file
				if (substr($file, 0, 11+strlen($kukarow["kuka"])+strlen($kukarow["yhtio"])) == "lue-data#{$kukarow["kuka"]}#{$kukarow["yhtio"]}#") {
					$omia_tiedostoja_jonossa++;
				}
			}
	    }
	    closedir($handle);
	}

	if ($tiedostoja_jonossa > 0) {
		echo "<br>";
		echo "<font class='message'>".t("Sinulla")." ".t("on")." $omia_tiedostoja_jonossa ".t("tiedostoa")." ".t("odottamassa k�sittely�").".</font><br>";
		echo "<font class='message'>".t("Palvelimella")." ".t("on")." ".t("yhteens�")." $tiedostoja_jonossa ".t("tiedostoa")." ".t("odottamassa k�sittely�").".</font><br>";
		echo "<br>";
	}

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
		'kuka'                            => 'Kustannuspaikat',
		'kustannuspaikka'                 => 'K�ytt�j�tietoja',
		'liitetiedostot'                  => 'Liitetiedostot',
		'maksuehto'                       => 'Maksuehto',
		'pakkaus'                         => 'Pakkaustiedot',
		'perusalennus'                    => 'Perusalennukset',
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
		'tullinimike'                     => 'Tullinimikeet',
		'tuote'                           => 'Tuote',
		'tuotepaikat'                     => 'Tuotepaikat',
		'tuoteperhe'                      => 'Tuoteperheet',
		'tuotteen_alv'                    => 'Tuotteen avainsanat',
		'tuotteen_avainsanat'             => 'Tuotteen toimittajat',
		'tuotteen_orginaalit'             => 'Tuotteiden originaalit',
		'tuotteen_toimittajat'            => 'Tuotteiden ulkomaan ALV',
		'vak'                             => 'VAK-tietoja',
		'varaston_hyllypaikat'            => 'Varaston hyllypaikat',
		'yhteyshenkilo'                   => 'Yhteyshenkil�t',
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
		$taulut['autodata']                        = 'Autodata tuotetiedot';
		$taulut['autodata_tuote']                  = 'Autodatatiedot';
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

	echo "<form method='post' name='sendfile' enctype='multipart/form-data' action=''>";
	echo "<input type='hidden' name='tee' value='file'>";
	echo "<table>";
	echo "<tr>";
	echo "<td>".t("Valitse tietokannan taulu").":</td>";
	echo "<td>";
	echo "<select name='table' onchange='submit();'>";

	foreach ($taulut as $taulu => $nimitys) {
		echo "<option value='$taulu' {$sel[$taulu]}>".t($nimitys)."</option>";
	}

	echo "</select>";
	echo "</td>";
	echo "</tr>";

	if (in_array($table, array("yhteyshenkilo", "asiakkaan_avainsanat", "kalenteri"))) {
		echo "<tr><td>".t("Ytunnus-tarkkuus").":</td>
				<td><select name='ytunnustarkkuus'>
				<option value=''>".t("P�ivitet��n vain, jos Ytunnuksella l�ytyy yksi rivi")."</option>
				<option value='2'>".t("P�ivitet��n kaikki sy�tetyll� Ytunnuksella l�ytyv�t asiakkaat")."</option>
				</select></td>
		</tr>";
	}

	if (trim($dynaamiset_avainsanat) != '' and $table == $dynaamiset_avainsanat) {
		echo "	<tr><td>",t("Valitse liitos"),":</td>
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
		echo "<tr><td>".t("Segmentin valinta").":</td>
				<td><select name='segmenttivalinta'>
				<option value='1'>".t("Valitaan k�ytett�v�ksi asiakas-segmentin koodia")."</option>
				<option value='2'>".t("Valitaan k�ytett�v�ksi asiakas-segmentin tunnusta ")."</option>
				</select></td>
		</tr>";
	}

	if ($table == "extranet_kayttajan_lisatiedot") {
		echo "<tr><td>".t("Liitostunnus").":</td>
				<td><select name='liitostunnusvalinta'>
				<option value='1'>".t("Liitostunnus-sarakkeessa liitostunnus")."</option>
				<option value='2'>".t("Liitostunnus-sarakkeessa k�ytt�j�nimi")."</option>
				</select></td>
		</tr>";
	}

	echo "	<tr><td>".t("Valitse tiedosto").":</td>
			<td><input name='userfile' type='file'></td>
			<td class='back'><input type='submit' name='laheta' value='".t("L�het�")."'></td>
		</tr>

		</table>
		</form>
		<br>";

	// N�ytet��n k�ytt�j�n kaikki LOG filet
	$kasitelty = array();
	$kasitelty_i = 0;

	if ($handle = opendir($pupe_root_polku."/datain")) {
	    while (false !== ($file = readdir($handle))) {
			// T�m� file on valmis lue-data file
			if (substr($file, 0, 11+strlen($kukarow["kuka"])+strlen($kukarow["yhtio"])) == "lue-data#{$kukarow["kuka"]}#{$kukarow["yhtio"]}#" and substr($file, -4) == ".LOG") {

				$log = file_get_contents($pupe_root_polku."/datain/".$file);

				// T�m� logi on jo k�sitelty
				if (strpos($log, "## LUE-DATA-EOF ##") !== FALSE) {

					// Filename on muotoa: lue-data#username#yhtio#taulu#randombit#alkuperainen_filename#jarjestys.CSV.LOG
					$filen_tiedot = explode("#", $file);
					$kuka = $filen_tiedot[1];
					$taulu = $filen_tiedot[3];
					$orig_file = $filen_tiedot[5];

					$kasitelty[$kasitelty_i]["filename"] = $file;
					$kasitelty[$kasitelty_i]["orig_file"] = $orig_file;
					$kasitelty[$kasitelty_i]["taulu"] = $taulut[$taulu];
					$kasitelty[$kasitelty_i]["aika"] = date("d.m.Y H:i:s", filemtime($pupe_root_polku."/datain/".$file));
					$kasitelty[$kasitelty_i]["kaunisnimi"] = "$kuka-$taulu-".date("Ymd-His", filemtime($pupe_root_polku."/datain/".$file)).".txt";
					$kasitelty_i++;
				}
			}
	    }
	    closedir($handle);
	}

	if (count($kasitelty) > 0) {

		echo "<font class='head'>".t("Sinun k�sitellyt ajot").":</font><hr>";

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Tiedosto")."</th>";
		echo "<th>".t("Taulu")."</th>";
		echo "<th>".t("K�sitelty")."</th>";
		echo "<th colspan='2'>".t("Lokitiedosto")."</th>";
		echo "</tr>";

		foreach ($kasitelty as $file) {
			echo "<tr class='aktiivi'>";
			echo "<td>{$file["orig_file"]}</td>";
			echo "<td>{$file["taulu"]}</td>";
			echo "<td>{$file["aika"]}</td>";
			echo "<td><form method='post'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='{$file["kaunisnimi"]}'>";
			echo "<input type='hidden' name='datain_filenimi' value='{$file["filename"]}'>";
			echo "<input type='submit' value='".t("Tallenna")."'>";
			echo "</form></td>";
			echo "<td><form method='post' onsubmit=\"return confirm('".t("Oletko varma, ett� haluat poistaa lokitiedoston?")."')\">";
			echo "<input type='hidden' name='tee' value='poista_file'>";
			echo "<input type='hidden' name='datain_filenimi' value='{$file["filename"]}'>";
			echo "<input type='submit' value='".t("Poista")."'>";
			echo "</form></td>";
			echo "</tr>";
		}

		echo "</table>";
	}

	require("inc/footer.inc");
