<?php

	// tarvitaan
	// $kukarow
	// $tuoteno
	// $tee jossa tulee olla:
	// - paalle (täysepäkurantti)
	// - 75paalle
	// - puolipaalle
	// - 25paalle
	// - pois (kurantiksi ja keskihankintahinta nostetaan takaisin)
	// - peru (kurantiksi ja keskihankintahinta jää alas nykyiselle tasolle)

	$tuoteno = isset($tuoteno) ? mysql_real_escape_string(trim($tuoteno)) : "";
	$tee = isset($tee) ? trim($tee) : "";
	$varaston_muutos = 0;

	// Katsotaan löytyykö tuote
	if ($tuoteno != "") {

		$query = "	SELECT tuote.tunnus,
					tuote.tuoteno,
					tuote.kehahin,
					tuote.epakurantti25pvm,
					tuote.epakurantti50pvm,
					tuote.epakurantti75pvm,
					tuote.epakurantti100pvm,
					tuote.kustp,
					tuote.kohde,
					tuote.projekti,
					round(if (tuote.epakurantti100pvm = '0000-00-00',
							if (tuote.epakurantti75pvm = '0000-00-00',
								if (tuote.epakurantti50pvm = '0000-00-00',
									if (tuote.epakurantti25pvm = '0000-00-00',
										tuote.kehahin,
									tuote.kehahin * 0.75),
								tuote.kehahin * 0.5),
							tuote.kehahin * 0.25),
						0),
					6) kehahin_nyt,
					tuote.sarjanumeroseuranta,
					sum(tuotepaikat.saldo) saldo
					FROM tuote
					LEFT JOIN tuotepaikat ON (tuote.yhtio = tuotepaikat.yhtio AND tuote.tuoteno = tuotepaikat.tuoteno)
					WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
					AND tuote.tuoteno = '$tuoteno'
					GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12";
		$result = pupe_query($query);
		$tuoterow = mysql_fetch_assoc($result);

		if (mysql_num_rows($result) != 1) {
			echo "<font class='error'>".t("Tuote %s ei löydy!", "", $tuoteno)."</font><br>";
			$tee = "";
		}
		elseif ($tuoterow["sarjanumeroseuranta"] == 'S' or $tuoterow["sarjanumeroseuranta"] == 'U' or $tuoterow["sarjanumeroseuranta"] == 'G') {

			$sarjanumerolisa = "";

			if (isset($sarjatunnus) and $sarjatunnus > 0) {
				$sarjanumerolisa = " and sarjanumeroseuranta.tunnus = {$sarjatunnus} ";
			}

			$query	= "	SELECT sarjanumeroseuranta.*, sarjanumeroseuranta.tunnus sarjatunnus,
						tilausrivi_osto.tunnus osto_rivitunnus,
						tilausrivi_osto.perheid2 osto_perheid2,
						tilausrivi_osto.nimitys nimitys,
						lasku_myynti.nimi myynimi
						FROM sarjanumeroseuranta
						JOIN tilausrivi tilausrivi_osto use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus and tilausrivi_osto.laskutettuaika != '0000-00-00'
						LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
						LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.uusiotunnus
						LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
						WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
						and sarjanumeroseuranta.tuoteno = '$tuoterow[tuoteno]'
						and sarjanumeroseuranta.myyntirivitunnus != -1
						and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
						$sarjanumerolisa";
			$sarjares = pupe_query($query);

			if (isset($sarjatunnus) and $sarjatunnus > 0) {
				$sarjarow = mysql_fetch_assoc($sarjares);
			}
			elseif (!isset($sarjatunnus) or $sarjatunnus == 0) {
				echo "<font class='message'>".t("Valitse sarjanumero")."</font><hr>";
				echo "<table>";
				echo "<tr><th>".t("Nimitys")."</th>";
				echo "<th>".t("Sarjanumero")."</th>";
				echo "<th>".t("Varastopaikka")."</th>";
				echo "<th>".t("Ostohinta")."</th>";
				echo "<th>".t("Varattu asiakaalle")."</th></tr>";

				while ($sarjarow = mysql_fetch_assoc($sarjares)) {

					$fnlina1 = "";

					if (($sarjarow["siirtorivitunnus"] > 0) or ($sarjarow["osto_perheid2"] > 0 and $sarjarow["osto_perheid2"] != $sarjarow["osto_rivitunnus"])) {

						if ($sarjarow["osto_perheid2"] > 0 and $sarjarow["osto_perheid2"] != $sarjarow["osto_rivitunnus"]) {
							$ztun = $sarjarow["osto_perheid2"];
						}
						else {
							$ztun = $sarjarow["siirtorivitunnus"];
						}

						$query = "	SELECT tilausrivi.tunnus, tilausrivi.tuoteno, sarjanumeroseuranta.sarjanumero, tyyppi, otunnus
									FROM tilausrivi
									LEFT JOIN sarjanumeroseuranta ON (tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus)
									WHERE tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.tunnus='$ztun'";
						$siires = pupe_query($query);
						$siirow = mysql_fetch_assoc($siires);

						if ($siirow["tyyppi"] == "O") {
							// pultattu kiinni johonkin
							$fnlina1 = " <font class='message'>(".t("Varattu lisävarusteena").": $siirow[tuoteno] <a href='tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=".urlencode($siirow["tuoteno"])."&sarjanumero_haku=".urlencode($siirow["sarjanumero"])."'>$siirow[sarjanumero]</a>)</font>";
						}
						elseif ($siirow["tyyppi"] == "G") {
							// jos tämä on jollain siirtolistalla
							$fnlina1 = " <font class='message'>(".t("Kesken siirtolistalla").": $siirow[otunnus])</font>";
						}
					}

					echo "<tr>
							<td>$sarjarow[nimitys]</td>
							<td><a href='tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=".urlencode($tuoterow["tuoteno"])."&sarjanumero_haku=".urlencode($sarjarow["sarjanumero"])."'>$sarjarow[sarjanumero]</a></td>
							<td>$sarjarow[hyllyalue] $sarjarow[hyllynro] $sarjarow[hyllyvali] $sarjarow[hyllytaso]</td>
							<td align='right'>";
					echo sprintf('%.2f', sarjanumeron_ostohinta("tunnus", $sarjarow["sarjatunnus"]));
					echo "</td><td>$sarjarow[myynimi] $fnlina1</td>";

					echo "<td>";

					echo "<form method='post' autocomplete='off'>";
					echo "<input type='hidden' name='tuoteno' value='{$tuoteno}'>";
					echo "<input type='hidden' name='sarjanro' value='{$sarjarow['sarjanumero']}'>";
					echo "<input type='hidden' name='sarjatunnus' value='{$sarjarow['sarjatunnus']}'>";
					echo "<input type='hidden' name='tee' value='vahvista'>";
					echo "<input type='submit' value='".t("Valitse")."'>";
					echo "</form>";

					echo "</td>";
					echo "</tr>";
				}

				echo "</table><br>";

				$tee = 'STOP';
			}
		}
		else {

			$nykyinen_keskihankintahinta = $tuoterow["kehahin_nyt"];
			$tuotteen_saldo = $tuoterow["saldo"];
			$brutto_varastonarvo = $tuotteen_saldo * $tuoterow["kehahin"];
			$brutto_keskihankintahinta = $tuoterow["kehahin"];

			// Tehdään selkokielinen selite
			$epakurantti_selite = "Epäkuranttimuutos: ".t("Tuote")." {$tuoterow["tuoteno"]} ".t("päivitetään")." ";

			// Lasketaan uusi keskihankintahinta
			if ($tee == "paalle") {
				$uusi_keskihankintahinta = 0;
				$epakurantti_selite .= "100% ".t("epäkurantiksi");
			}
			elseif ($tee == "25paalle") {
				$uusi_keskihankintahinta = $tuoterow["kehahin"] * 0.75;
				$epakurantti_selite .= "25% ".t("epäkurantiksi");
			}
			elseif ($tee == "puolipaalle") {
				$uusi_keskihankintahinta = $tuoterow["kehahin"] * 0.5;
				$epakurantti_selite .= "50% ".t("epäkurantiksi");
			}
			elseif ($tee == "75paalle") {
				$uusi_keskihankintahinta = $tuoterow["kehahin"] * 0.25;
				$epakurantti_selite .= "75% ".t("epäkurantiksi");
			}
			elseif ($tee == "pois") {
				$uusi_keskihankintahinta = $tuoterow["kehahin"];
				$epakurantti_selite .= t("kurantiksi");
			}
			elseif ($tee == "peru") {
				$uusi_keskihankintahinta = $tuoterow["kehahin_nyt"];
				$epakurantti_selite .= t("kurantiksi");
			}
			else {
				$uusi_keskihankintahinta = $tuoterow["kehahin_nyt"];
				$epakurantti_selite .= $tee;
			}

			// Lasketaan keskihankintahinnan muutos
			$keskihankintahinta_muutos = abs($nykyinen_keskihankintahinta - $uusi_keskihankintahinta);

			// Lasketaan varastonmuutos
			$uusi_varastonarvo = $tuotteen_saldo * $uusi_keskihankintahinta;
			$nykyinen_varastonarvo = $tuotteen_saldo * $nykyinen_keskihankintahinta;
			$varaston_muutos = round(($nykyinen_varastonarvo - $uusi_varastonarvo) * -1, 2);

			// Selitteen loppu
			$epakurantti_selite .= ". ".t("Alkup. kehahin").": $brutto_keskihankintahinta ";
			$epakurantti_selite .= t("Kehahin ennen muutosta").": $nykyinen_keskihankintahinta ";
			$epakurantti_selite .= t("Uusi kehahin").": $uusi_keskihankintahinta ";
			$epakurantti_selite .= t("Saldo").": $tuotteen_saldo ";
			$epakurantti_selite .= t("Varastonmuutos").": $varaston_muutos";
		}
	}

	// Tehdään oikeellisuustarkistukset
	if ($tee == "paalle" or $tee == "25paalle" or $tee == "puolipaalle" or $tee == "75paalle" or $tee == "pois" or $tee == "peru") {

		// katotaan onko tilauksessa
		$query = "	SELECT sum(varattu) varattu
					FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
					WHERE yhtio = '{$kukarow["yhtio"]}'
					and tuoteno = '{$tuoterow["tuoteno"]}'
					and varattu > 0
					and tyyppi = 'O'";
		$tilre = pupe_query($query);
		$tilro = mysql_fetch_assoc($tilre);

		if ($tilro["varattu"] != 0 and $tee != "pois" and $tee != "peru") {
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuotetta on tilauksessa!")." ".t("Ei voida laittaa epäkurantiksi!")."</font><br>";
			$tee = "";
		}

		// katotaan onko liitetty keikkaan jonka virallista varastonarvoa ei ole laskettu
		$query = "	SELECT laskunro
					FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
					JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
						AND lasku.tunnus = tilausrivi.uusiotunnus
						AND lasku.tila = 'K'
						AND lasku.alatila not in ('X', 'I'))
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tuoteno = '$tuoterow[tuoteno]'
					and varattu = 0
					and tyyppi = 'O'";
		$tilre = pupe_query($query);

		if (mysql_num_rows($tilre) != 0 and $tee != "pois" and $tee != "peru") {
			$tilro = mysql_fetch_assoc($tilre);
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuotetta on viemättä varastoon saapumisella")." $tilro[laskunro]. ".t("Ei voida laittaa epäkurantiksi!")."</font><br>";
			$tee = "";
		}

		if ($tuoterow["saldo"] < 0) {
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuotten saldo on negatiivinen!")." ".t("Ei voida vaihtaa tilaa!")."</font><br>";
			$tee = "";
		}

		// ei voida aktivoida tuotetta kurantiksi jos se on jo kurantti
		if (($tee == 'pois' or $tee == "peru") and ($tuoterow['epakurantti25pvm'] == '0000-00-00') and ($tuoterow['epakurantti50pvm'] == '0000-00-00') and ($tuoterow['epakurantti75pvm'] == '0000-00-00') and ($tuoterow['epakurantti100pvm'] == '0000-00-00')) {
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuote väärässä tilassa!")." ".t("Ei voida laittaa kurantiksi!")."</font><br>";
			$tee = "";
		}

		// ei voida laittaa puoliepäkurantiksi jos tuote on puoliepäkurantti tai täysepäkurantti
		if (($tee == '25paalle') and (($tuoterow['epakurantti25pvm'] != '0000-00-00') or ($tuoterow['epakurantti50pvm'] != '0000-00-00') or ($tuoterow['epakurantti75pvm'] != '0000-00-00') or ($tuoterow['epakurantti100pvm'] != '0000-00-00'))) {
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuote väärässä tilassa!")." ".t("Ei voida laittaa 25% epäkurantiksi!")."</font><br>";
			$tee = "";
		}

		// ei voida laittaa puoliepäkurantiksi jos tuote on puoliepäkurantti tai täysepäkurantti
		if (($tee == 'puolipaalle') and (($tuoterow['epakurantti50pvm'] != '0000-00-00') or ($tuoterow['epakurantti75pvm'] != '0000-00-00') or ($tuoterow['epakurantti100pvm'] != '0000-00-00'))) {
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuote väärässä tilassa!")." ".t("Ei voida laittaa puoliepäkurantiksi!")."</font><br>";
			$tee = "";
		}

		// ei voida laittaa puoliepäkurantiksi jos tuote on puoliepäkurantti tai täysepäkurantti
		if (($tee == '75paalle') and (($tuoterow['epakurantti75pvm'] != '0000-00-00') or ($tuoterow['epakurantti100pvm'] != '0000-00-00'))) {
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuote väärässä tilassa!")." ".t("Ei voida laittaa 75% epäkurantiksi!")."</font><br>";
			$tee = "";
		}

		// ei voida laittaa täysepäkurantiksi jos tuote on jo täysepäkurantti
		if (($tee == 'paalle') and ($tuoterow['epakurantti100pvm'] != '0000-00-00')) {
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuote väärässä tilassa!")." ".t("Ei voida laittaa täysiepäkurantiksi!")."</font><br>";
			$tee = "";
		}
	}

	// Päivitetään tuote
	if ($tee == "paalle" or $tee == "25paalle" or $tee == "puolipaalle" or $tee == "75paalle" or $tee == "pois" or $tee == "peru") {

		// Tehdään lisämuuttujat, jos hypätään jonkun epäkuranttitason yli
		$lisa75 = "";
		$lisa50 = "";
		$lisa25 = "";

		if ($tuoterow['epakurantti75pvm'] == '0000-00-00') { // jos tuote ei ole puoliepäkurantti, päivitetään sama päivä tännekkin
			$lisa75 = " epakurantti75pvm = now(), ";
		}

		if ($tuoterow['epakurantti50pvm'] == '0000-00-00') { // jos tuote ei ole puoliepäkurantti, päivitetään sama päivä tännekkin
			$lisa50 = " epakurantti50pvm = now(), ";
		}

		if ($tuoterow['epakurantti25pvm'] == '0000-00-00') { // jos tuote ei ole puoliepäkurantti, päivitetään sama päivä tännekkin
			$lisa25 = " epakurantti25pvm = now(), ";
		}

		///* Päivitetään tuote kurantiksi *///
		if ($tee == 'pois' or $tee == "peru") {
			$query = "	UPDATE tuote SET
						epakurantti25pvm = '0000-00-00',
						epakurantti50pvm = '0000-00-00',
						epakurantti75pvm = '0000-00-00',
						epakurantti100pvm = '0000-00-00'
						WHERE yhtio = '$kukarow[yhtio]'
						AND	tuoteno = '$tuoterow[tuoteno]'";
			$result = pupe_query($query);
		}

		///* Päivitetään tuote täysepäkurantiksi *///
		if ($tee == 'paalle') {
			$query = "	UPDATE tuote set
						$lisa75
						$lisa50
						$lisa25
						epakurantti100pvm = now()
						WHERE yhtio = '$kukarow[yhtio]'
						and	tuoteno = '$tuoterow[tuoteno]'";
			$result = pupe_query($query);
		}

		///* Päivitetään tuote 75epäkurantiksi *///
		if ($tee == '75paalle') {
			$query = "	UPDATE tuote set
						$lisa50
						$lisa25
						epakurantti75pvm = now(),
						epakurantti100pvm = ''
						WHERE yhtio = '$kukarow[yhtio]'
						and	tuoteno = '$tuoterow[tuoteno]'";
			$result = pupe_query($query);
		}

		///* Päivitetään tuote puoliepäkurantiksi *///
		if ($tee == 'puolipaalle') {
			$query = "	UPDATE tuote set
						$lisa25
						epakurantti50pvm = now(),
						epakurantti75pvm = '',
						epakurantti100pvm = ''
						WHERE yhtio = '$kukarow[yhtio]'
						and	tuoteno = '$tuoterow[tuoteno]'";
			$result = pupe_query($query);
		}

		///* Päivitetään tuote 25epäkurantiksi *///
		if ($tee == '25paalle') {
			$query = "	UPDATE tuote set
						epakurantti25pvm = now(),
						epakurantti50pvm = '',
						epakurantti75pvm = '',
						epakurantti100pvm = ''
						WHERE yhtio = '$kukarow[yhtio]'
						and	tuoteno = '$tuoterow[tuoteno]'";
			$result = pupe_query($query);
		}

		///* Päivitetään tuotteen keskihankintahinta nykyiselle tasolle *///
		if ($tee == 'peru') {
			$query = "	UPDATE tuote SET
						kehahin = '$uusi_keskihankintahinta',
						muutospvm = now(),
						muuttaja = '$kukarow[kuka]'
						WHERE yhtio = '$kukarow[yhtio]'
						and tuoteno = '$tuoterow[tuoteno]'";
			$result = pupe_query($query);
		}

		// jos ollaan laittamassa tuotetta epäkurantiksi niin käännetään saldon etumerkki
		if ($tee == 'paalle' or $tee == '75paalle' or $tee == 'puolipaalle' or $tee == '25paalle') {
			$tuotteen_saldo = $tuotteen_saldo * -1;
		}

		///* Tehdään tapahtuma *///
		$query = "	INSERT into tapahtuma set
					yhtio   	= '$kukarow[yhtio]',
					tuoteno 	= '$tuoterow[tuoteno]',
					laji    	= 'Epäkurantti',
					kpl     	= '$tuotteen_saldo',
					hinta   	= round($keskihankintahinta_muutos, 6),
					kplhinta	= round($keskihankintahinta_muutos, 6),
					selite  	= '$epakurantti_selite',
					laatija    	= '$kukarow[kuka]',
					laadittu 	= now()";
		$result = pupe_query($query);

		// otetaan tapahtuman tunnus, laitetaan se tiliöinnin otsikolle
		$tapahtumaid = mysql_insert_id();

		if (!isset($php_cli) or !$php_cli) {
			echo "<font class='message'>";
			echo t("Tuote")." $tuoteno ok! <br>";
		}

		echo t("Varastonarvoa muutettiin")." $varaston_muutos $yhtiorow[valkoodi].";

		if (!isset($php_cli) or !$php_cli) {
			echo "</font><br><br>";
		}
	}

    // Tehdään oikeellisuustarkistukset
    if ($toim == "VAIHDAKEHAHIN" and $tee == "vaihda_kehahin") {
        // Pilkut pisteiks
    	$uusiarvo = str_replace(",", ".", $uusiarvo);

    	if ($tuoterow['epakurantti25pvm'] != '0000-00-00' or $tuoterow['epakurantti50pvm'] != '0000-00-00' or $tuoterow['epakurantti75pvm'] != '0000-00-00' or $tuoterow['epakurantti100pvm'] != '0000-00-00') {
            echo "<font class='error'>".t("VIRHE: Tuote on epäkurantti. Keskihankintahintaa ei voida vaihtaa")."!</font><br>";
            $tee = "vahvista";
		}

		if (!is_numeric($uusiarvo)) {
			echo "<font class='error'>".t("VIRHE: Uusi keskihankintahinta ei ole numeerinen")."!</font><br>";
			$tee = "vahvista";
			$uusiarvo = "";
		}

		if (is_numeric($uusiarvo) and $uusiarvo < 0) {
			echo "<font class='error'>".t("VIRHE: Uusi keskihankintahinta ei voi olla negatiivinen")."!</font><br>";
			$tee = "vahvista";
			$uusiarvo = "";
		}

		if (trim($oma_selite) == "") {
			echo "<font class='error'>".t("VIRHE: Selite on syötettävä")."!</font><br>";
			$tee = "vahvista";
		}

		if (is_numeric($uusiarvo) and $uusiarvo == $tuoterow["kehahin"]) {
			echo "<font class='error'>".t("VIRHE: Tuotteella on jo tämä keskihankintahinta")."!</font><br>";
			$tee = "vahvista";
		}

        $nykyinen_keskihankintahinta = $tuoterow["kehahin"];
		$tuotteen_saldo = $tuoterow["saldo"];

		// Tehdään selkokielinen selite
		$epakurantti_selite = t("Keskihankintahinnan muutos").": ".t("Tuote")." {$tuoterow["tuoteno"]} ".t("päivitetään")." ";

        $uusi_keskihankintahinta = round($uusiarvo, 6);

		// Lasketaan keskihankintahinnan muutos
		$keskihankintahinta_muutos = abs($nykyinen_keskihankintahinta - $uusi_keskihankintahinta);

		// Lasketaan varastonmuutos
		$uusi_varastonarvo = $tuotteen_saldo * $uusi_keskihankintahinta;
		$nykyinen_varastonarvo = $tuotteen_saldo * $nykyinen_keskihankintahinta;
		$varaston_muutos = round(($nykyinen_varastonarvo - $uusi_varastonarvo) * -1, 2);

		// Selitteen loppu
		$epakurantti_selite .= ". ".t("Kehahin ennen muutosta").": $nykyinen_keskihankintahinta ";
		$epakurantti_selite .= t("Uusi kehahin").": $uusi_keskihankintahinta ";
		$epakurantti_selite .= t("Saldo").": $tuotteen_saldo ";
		$epakurantti_selite .= t("Varastonmuutos").": $varaston_muutos";

        $epakurantti_selite = mysql_real_escape_string($epakurantti_selite." ".t("Selite").": ".$oma_selite);
    }

    // vaihdetaan tuotteen kehahinta
    if ($toim == "VAIHDAKEHAHIN" and $tee == "vaihda_kehahin") {

        $query = "LOCK TABLES tuotepaikat WRITE, tapahtuma WRITE, tuote WRITE, lasku WRITE, tiliointi WRITE, tili READ";
    	$lock = pupe_query($query);

        $query = "	SELECT *
					FROM tuotepaikat
					WHERE yhtio = '$kukarow[yhtio]'
					AND tuoteno = '$tuoterow[tuoteno]'
					AND saldo != 0";
		$paikatres = pupe_query($query);

		$luontiaika1 = date("Y-m-d H:i:s", mktime(date("H"), date("i"), date("s"), date("m"), date("d"), date("Y")));
		$luontiaika2 = date("Y-m-d H:i:s", mktime(date("H"), date("i"), date("s")+1, date("m"), date("d"), date("Y")));

		while ($paikatrow = mysql_fetch_assoc($paikatres)) {

		    $miinus = $paikatrow['saldo'] * -1;

            // Poistetaan saldo vanhalla keharilla
		    $query = "	INSERT into tapahtuma set
    					yhtio   	= '$kukarow[yhtio]',
    					tuoteno 	= '$tuoterow[tuoteno]',
    					laji    	= 'tulo',
    					kpl     	= {$miinus},
    					hinta   	= {$tuoterow['kehahin']},
    					kplhinta	= {$tuoterow['kehahin']},
    					selite  	= '{$epakurantti_selite}',
    					hyllyalue   = '{$paikatrow['hyllyalue']}',
    					hyllynro    = '{$paikatrow['hyllynro']}',
    					hyllyvali   = '{$paikatrow['hyllyvali']}',
    					hyllytaso   = '{$paikatrow['hyllytaso']}',
    					laatija    	= '$kukarow[kuka]',
    					laadittu 	= '$luontiaika1'";
    		$result = pupe_query($query);
		}

		// vaihdetaan kehari tuotteelle
		$query = "	UPDATE tuote SET
					kehahin     = '{$uusi_keskihankintahinta}',
					muutospvm   = '$luontiaika1',
					muuttaja    = '$kukarow[kuka]'
					WHERE yhtio = '$kukarow[yhtio]'
					and tuoteno = '$tuoterow[tuoteno]'";
		$result = pupe_query($query);

        // Lisätään keharinmuutostapahtuma
	    $query = "	INSERT into tapahtuma set
					yhtio   	= '$kukarow[yhtio]',
					tuoteno 	= '$tuoterow[tuoteno]',
					laji    	= 'arvomuutos',
					kpl     	= 0,
					hinta   	= {$uusi_keskihankintahinta},
					kplhinta	= {$tuoterow['kehahin']},
					selite  	= '{$epakurantti_selite}',
					laatija    	= '$kukarow[kuka]',
					laadittu 	= '$luontiaika1'";
		$result = pupe_query($query);

        // otetaan tapahtuman tunnus, laitetaan se tiliöinnin otsikolle
		$tapahtumaid = mysql_insert_id();

		mysql_data_seek($paikatres, 0);

		while ($paikatrow = mysql_fetch_assoc($paikatres)) {

		    $plussa = $paikatrow['saldo'];

    		// Lisätään saldo uudella keharilla
		    $query = "	INSERT into tapahtuma set
    					yhtio   	= '$kukarow[yhtio]',
    					tuoteno 	= '$tuoterow[tuoteno]',
    					laji    	= 'tulo',
    					kpl     	= {$plussa},
    					hinta   	= {$uusi_keskihankintahinta},
    					kplhinta	= {$uusi_keskihankintahinta},
    					selite  	= '{$epakurantti_selite}',
    					hyllyalue   = '{$paikatrow['hyllyalue']}',
    					hyllynro    = '{$paikatrow['hyllynro']}',
    					hyllyvali   = '{$paikatrow['hyllyvali']}',
    					hyllytaso   = '{$paikatrow['hyllytaso']}',
    					laatija    	= '$kukarow[kuka]',
    					laadittu 	= '$luontiaika2'";
    		$result = pupe_query($query);
		}

		if (!isset($php_cli) or !$php_cli) {
			echo "<font class='message'>";
			echo t("Tuote")." $tuoteno ok! <br>";
		}

		echo t("Varastonarvoa muutettiin")." $varaston_muutos $yhtiorow[valkoodi].";

		if (!isset($php_cli) or !$php_cli) {
			echo "</font><br><br>";
		}

		$query = "UNLOCK TABLES";
    	$lock = pupe_query($query);
    }

	// Tehdään oikeellisuustarkistukset
	if ($tee == "sarjanro_paalle") {
        // Pilkut pisteiks
    	$uusiarvo = str_replace(",", ".", $uusiarvo);

		if ($sarjarow["osto_rivitunnus"] == 0) {
			echo "<font class='error'>".t("VIRHE: Sarjanumeroa ei löydy!")."</font><br>";
			$tee = "";
		}

		$nykyinenarvo = sarjanumeron_ostohinta("tunnus", $sarjarow["sarjatunnus"]);

		if (!is_numeric($uusiarvo)) {
			echo "<font class='error'>".t("VIRHE: Sarjanumeron uusi arvo ei ole numeerinen!")."</font><br>";
			$tee = "";
		}

		if (trim($oma_selite)) {
			echo "<font class='error'>".t("VIRHE: Selite on syötettävä")."!</font><br>";
			$tee = "";
		}

		if (is_numeric($uusiarvo) and $uusiarvo < 0) {
			echo "<font class='error'>".t("VIRHE: Sarjanumeron uusi arvo ei voi olla negatiivinen!")."</font><br>";
			$tee = "";
		}

		$uusiarvo = (float) $uusiarvo;

		$keskihankintahinta_muutos = round($uusiarvo-$nykyinenarvo, 6);
		$varaston_muutos = round($keskihankintahinta_muutos, 2);

		// Tehdään selkokielinen selite
		$epakurantti_selite = mysql_real_escape_string("Epäkuranttimuutos: ".t("Tuote")." {$tuoterow["tuoteno"]} ".t("Selite").": ".$oma_selite);
	}

	// Päivitetään sarjanumero
	if ($tee == "sarjanro_paalle") {

		///* Tehdään tapahtuma *///
		$query = "	INSERT into sarjanumeroseuranta_arvomuutos set
					yhtio   			= '$kukarow[yhtio]',
					sarjanumerotunnus 	= '$sarjatunnus',
					arvomuutos   		= round($keskihankintahinta_muutos, 6),
					selite  			= '$epakurantti_selite',
					laatija    			= '$kukarow[kuka]',
					luontiaika 			= now()";
		$result = pupe_query($query);

		// otetaan tapahtuman tunnus, laitetaan se tiliöinnin otsikolle
		$tapahtumaid = mysql_insert_id();

		if (!isset($php_cli) or !$php_cli) {
			echo "<font class='message'>";
			echo t("Tuote")." $tuoteno ok! <br>";
		}

		echo t("Varastonarvoa muutettiin")." $varaston_muutos $yhtiorow[valkoodi].";

		if (!isset($php_cli) or !$php_cli) {
			echo "</font><br><br>";
		}
	}

    //Tehdäänkö kirjaus kirjnapitoon?
	if ($varaston_muutos != 0 and ($tee == "paalle" or $tee == "25paalle" or $tee == "puolipaalle" or $tee == "75paalle" or $tee == "pois" or $tee == "peru" or $tee == "vaihda_kehahin" or $tee == "sarjanro_paalle")) {

        ///* Kirjanpito *///
		if ($tapahtumat_samalle_tositteelle != "kylla" or $laskuid == 0) {
			$query = "	INSERT into lasku set
						yhtio      = '$kukarow[yhtio]',
						tapvm      = now(),
						tila       = 'X',
						alatila    = 'E',
						viite      = '$tapahtumaid',
						laatija    = '$kukarow[kuka]',
						luontiaika = now()";
			$result = pupe_query($query);
			$laskuid = mysql_insert_id($link);
		}

		if ($toim != "VAIHDAKEHAHIN" and $yhtiorow["varastonmuutos_epakurantti"] != "") {
			$varastonmuutos_tili = $yhtiorow["varastonmuutos_epakurantti"];
		}
		else {
			$varastonmuutos_tili = $yhtiorow["varastonmuutos"];
		}

		// Otetaan ensisijaisesti kustannuspaikka tuotteen takaa
		$kustp_ins 		= $tuoterow["kustp"];
		$kohde_ins 		= $tuoterow["kohde"];
		$projekti_ins 	= $tuoterow["projekti"];

		// Tiliöidään ensisijaisesti varastonmuutos tilin oletuskustannuspaikalle
		list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($varastonmuutos_tili, $kustp_ins, $kohde_ins, $projekti_ins);

		// Toissijaisesti kokeillaan vielä varasto-tilin oletuskustannuspaikkaa
		list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["varasto"], $kustp_ins, $kohde_ins, $projekti_ins);

		$query = "	INSERT into tiliointi set
					yhtio    = '$kukarow[yhtio]',
					ltunnus  = '$laskuid',
					tilino   = '$yhtiorow[varasto]',
					kustp    = '{$kustp_ins}',
					kohde	 = '{$kohde_ins}',
					projekti = '{$projekti_ins}',
					tapvm    = now(),
					summa    = $varaston_muutos,
					vero     = 0,
					lukko    = '',
					selite   = '$epakurantti_selite',
					laatija  = '$kukarow[kuka]',
					laadittu = now()";
		$result = pupe_query($query);

		$query = "	INSERT into tiliointi set
					yhtio    = '$kukarow[yhtio]',
					ltunnus  = '$laskuid',
					tilino   = '$varastonmuutos_tili',
					kustp    = '{$kustp_ins}',
					kohde	 = '{$kohde_ins}',
					projekti = '{$projekti_ins}',
					tapvm    = now(),
					summa    = $varaston_muutos * -1,
					vero     = 0,
					lukko    = '',
					selite   = '$epakurantti_selite',
					laatija  = '$kukarow[kuka]',
					laadittu = now()";
		$result = pupe_query($query);
	}

?>