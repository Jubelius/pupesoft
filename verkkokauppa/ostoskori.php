<?php

if($_POST["tee"] == "tarjous") $nayta_pdf=1; //Generoidaan .pdf-file

if ($_POST["pyytaja"] == "yhteensopivuus") {
	$_POST["pyytaja"] = "yhteensopivuus.php";
	$pyytajadir = "";
}
else {
	$_POST["pyytaja"] = "tuote_selaus_haku.php";
	$pyytajadir = "tilauskasittely/";
}

if (file_exists("inc/parametrit.inc")) {
	require ("inc/parametrit.inc");
	$post_myynti = $pyytajadir.$pyytaja;
	$pyytaja = substr($pyytaja,0,-4);
}
else {
	require ("parametrit.inc");
	$post_myynti = $pyytaja;
	$pyytaja = substr($pyytaja,0,-4);
}

if ($tee == 'tarjous') {
	$query = "	select lasku.tunnus laskutunnus, asiakas.* 
				from lasku, asiakas
				where lasku.yhtio = asiakas.yhtio and lasku.liitostunnus = asiakas.tunnus and 
				lasku.yhtio = '$kukarow[yhtio]' and
				tila = 'B' and
				liitostunnus = '$kukarow[oletus_asiakas]' and
				alatila='$ostoskori'";
	$result = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($result) == 1) {
		$laskurow = mysql_fetch_array($result);
		
		$query = "  SELECT *, concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta
					FROM tilausrivi
					WHERE otunnus = '$laskurow[laskutunnus]' and yhtio='$kukarow[yhtio]'
					ORDER BY tunnus";
		$result = mysql_query($query) or pupe_error($query);
		
		//kuollaan jos yht��n rivi� ei l�ydy
		if (mysql_num_rows($result) == 0) {
			echo t("Laskurivej� ei l�ytynyt");
			exit;
		}
		
		require_once("tulosta_tarjous.inc");
		
		$sivu = 1;

		// aloitellaan laskun teko
		$firstpage = alku();

		while ($row = mysql_fetch_array($result)) {
			rivi($firstpage);
		}

		loppu($firstpage);
		alvierittely ($firstpage);

		//keksit��n uudelle failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$pdffilenimi = "/tmp/tarjous-".md5(uniqid(mt_rand(), true)).".pdf";

		//kirjoitetaan pdf faili levylle..
		$fh = fopen($pdffilenimi, "w");
		if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus ep�onnistui $pdffilenimi");
		fclose($fh);

		//Ty�nnet��n tuo pdf vaan putkeen!
		echo file_get_contents($pdffilenimi);

		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $pdffilenimi");

		unset($pdf);
		unset($firstpage);
	}
	$tee = '';
}


if ($tee == "poistakori") {
	$query = "	select tunnus from lasku
				where yhtio = '$kukarow[yhtio]' and
				tila = 'B' and
				liitostunnus = '$kukarow[oletus_asiakas]' and
				alatila='$ostoskori'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		// l�yty vaan yks dellataan se
		//$ostoskori = mysql_fetch_array($result);
		$kalakori = mysql_fetch_array($result);
		
		$query = "	delete from tilausrivi
					where yhtio = '$kukarow[yhtio]' and
					tyyppi = 'B' and
					otunnus = '$kalakori[tunnus]'";
		$result = mysql_query($query) or pupe_error($query);

		$query = "	delete from lasku
					where yhtio = '$kukarow[yhtio]' and
					tila = 'B' and
					tunnus = '$kalakori[tunnus]'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<font class='message'>Ostoskori tyhjennetty.</font><br>";
	}
	else {
		echo "<font class='message'>Ostoskorin tyhjennys ep�onnistui.</font><br>";
	}

	$tee = "";
}

if ($tee == "poistarivi") {
	$query = "	select tunnus from lasku
				where yhtio = '$kukarow[yhtio]' and
				tila = 'B' and
				liitostunnus = '$kukarow[oletus_asiakas]' and
				alatila='$ostoskori'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		// l�yty vaan yks dellataan siit� rivi
		//$ostoskori = mysql_fetch_array($result);
		$kalakori = mysql_fetch_array($result);

		$query = "	delete from tilausrivi
					where yhtio = '$kukarow[yhtio]' and
					tyyppi = 'B' and
					tunnus = '$rivitunnus'";
		$result = mysql_query($query) or pupe_error($query);
	}
	else {
		echo "<font class='message'>Rivin poisto ep�onnistui.</font><br>";
	}

	$tee = "";
}

if ($tee == "") {
	echo "<font class='head'>".t("Ostoskorisi")."</font><hr>";

	echo "<table><tr>";
	echo "	<form method='post' action='$post_myynti'>
			<input type='hidden' name='ostoskori' value='$ostoskori'>
			<td class='back'><input type='submit' value='".t("Palaa selaimeen")."'></td>
			</form>";

	if ($ostoskori != '') {
		$query = "	select lasku.tunnus
					from lasku, tilausrivi
					where lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and
					lasku.yhtio = '$kukarow[yhtio]' and
					tila = 'B' and
					liitostunnus = '$kukarow[oletus_asiakas]' and
					alatila='$ostoskori'";
		$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) > 0 and file_exists("tulosta_tarjous.inc")) {
			echo "	<form method='post' action='ostoskori.php' target='_blank'>
					<input type='hidden' name='ostoskori' value='$ostoskori'>
					<input type='hidden' name='pyytaja' value='$pyytaja'>
					<input type='hidden' name='tee' value='tarjous'>
					<td class='back'><input type='submit' value='".t("Tee tarjous")."'></td>
					</form>";
		}
	}
	
	echo "</table></tr>";

	if (is_numeric($ostoskori)) {
		$lisa = "and alatila='$ostoskori'";
	}
	else {
		$lisa = "";
	}

	$query = "	SELECT lasku.*, count(*) rivit
				FROM lasku use index (yhtio_tila_liitostunnus_tapvm)
				JOIN tilausrivi on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi = 'B')
				WHERE lasku.yhtio = '$kukarow[yhtio]' and
				lasku.tila = 'B' and
				lasku.liitostunnus = '$kukarow[oletus_asiakas]'
				$lisa
				GROUP BY lasku.tunnus
				HAVING rivit > 0
				ORDER BY alatila";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {

		while ($ostoskori = mysql_fetch_array($result)) {

			echo "<table>";

			echo "<tr>";
			echo "<th colspan='3'>Ostoskorissa nro $ostoskori[alatila] olevat tuotteet</th>";

			echo "<form method='post' action='ostoskori.php'>";
			echo "<th colspan='3' style='text-align:right;'>";			
			echo "<input type='hidden' name='tee' value='poistakori'>
					<input type='hidden' name='pyytaja' value='$pyytaja'>
					<input type='hidden' name='ostoskori' value='$ostoskori[alatila]'>
					<input type='submit' value='".t("Tyhjenn� ostoskori")."'>";
			echo "</th>";
			echo "</form>";
			echo "<tr>";

			echo "<tr>";
			echo "<th>Tuoteno</th>";
			echo "<th>Nimitys</th>";
			echo "<th>M��r�</th>";
			echo "<th>Yksikk�hinta</th>";
			echo "<th>Rivihinta</th>";
			echo "<th>Poista</th>";
			echo "</tr>";

			$query = "	SELECT *
						FROM tilausrivi
						WHERE yhtio = '$kukarow[yhtio]' and
						otunnus = '$ostoskori[tunnus]' and
						tyyppi = 'B'";
			$riviresult = mysql_query($query) or pupe_error($query);

			while ($koririvi = mysql_fetch_array($riviresult)) {

				echo "<tr>";
				echo "<td>$koririvi[tuoteno]</td>";
				echo "<td>$koririvi[nimitys]</td>";
				echo "<td>$koririvi[varattu]</td>";
				echo "<td>$koririvi[hinta]</td>";
				echo "<td>$koririvi[rivihinta]</td>";
				echo "<form method='post' action='ostoskori.php'>";
				echo "<td>";
				echo "<input type='hidden' name='tee' value='poistarivi'>
						<input type='hidden' name='pyytaja' value='$pyytaja'>
						<input type='hidden' name='ostoskori' value='$ostoskori[alatila]'>
						<input type='hidden' name='rivitunnus' value='$koririvi[tunnus]'>
						<input type='submit' value='".t("Poista")."'>";
				echo "</td>";
				echo "</form>";
				echo "</tr>";

			}

			echo "</table><br>";

		}

	}
	else {
		echo "<font class='message'>Ostoskorisi on tyhj�.</font><br>";
	}
}

?>