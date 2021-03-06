<?php

	$teccom_editilaus = "../dataout/teccom-order-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".txt";

	if (!$toot = fopen($teccom_editilaus, "w")) die("Filen $teccom_editilaus luonti epaonnistui!");

	$query = "  SELECT tilausrivi.tunnus,
				tilausrivi.varattu,
				tilausrivi.yksikko,
				ifnull(tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno) toim_tuoteno
				FROM tilausrivi
				LEFT JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
					AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
					AND tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]')
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				AND tilausrivi.tyyppi = 'O'
				AND otunnus = '$laskurow[tunnus]'
				AND varattu > 0
				ORDER BY tunnus";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("L�hetett�vi� tilausrivej� ei l�ydy")."</font>";
	}
	else {
		
		if ($laskurow['tilaustyyppi'] == '' OR $laskurow['tilaustyyppi'] == '0') $laskurow['tilaustyyppi'] = '2';
		
		// Otsikkoa
		$out = sprintf("%-10.10s", "000ORDH");						// VAKIOALKU
		$out .= sprintf("%-9.9s", $toimirow['toimittajanro']);		// Toimittajan numero 6 merkki�
		$out .= sprintf("%-20.20s", $laskurow['nimi']);				// Toimittajan nimi 20 merkki�
		$out .= sprintf("%-20.20s", $yhtiorow['nimi']);				// L�hett�j�n nimi 20 merkki�
		$out .= sprintf("%08.8s", $laskurow['tunnus']);				// Ostotilauksen numero 8 merkki�
		$out .= sprintf("%4.4s", "");								// Toimitustapa 4 merkki�, ei k�yt�ss�
		$out .= sprintf("%-1.1s", $laskurow['tilaustyyppi']);		// Tilaustapa, 1 = Pikatilaus, 2 = Normaalitilaus, 3 = Y�l�hetys
		fwrite($toot, $out . "\n");

		while ($tilausrivirow = mysql_fetch_array($result)) {
			$out = sprintf("%-7.7s", "000ORDI");							// VAKIOALKU
			$out .= sprintf("%-11.11s", $tilausrivirow['tunnus']);			// Tilausrivin numero 11 merkki�
			$out .= sprintf("%-60.60s", $tilausrivirow['toim_tuoteno']);	// Toimittajan Tuoteno 60 merkki�
			$out .= sprintf("%-6.6s", $toimirow['toimittajanro']);			// toimittajan numero 6 merkki�
			$out .= sprintf("%05.0f", $tilausrivirow['varattu']);			// tilausm��r� 5 merkki�
			$out .= sprintf("%03.3s",$tilausrivirow['yksikko']); 			// yksikk� 3 merkki�
			fwrite($toot, $out . "\n");
		}

		fclose ($toot);

		// tarvitaan  $ftphost $ftpuser $ftppass $ftppath $ftpfile
		// palautetaan $palautus ja $syy
		$ftphost = $toimirow['edi_palvelin'];
		$ftpuser = $toimirow['edi_kayttaja'];
		$ftppass = $toimirow['edi_salasana'];
		$ftppath = $toimirow['edi_polku'];
		$ftpfile = realpath($teccom_editilaus);

		require ("inc/ftp-send.inc");

		if ($palautus == 0) {
			echo "<font class='message'>".t("Tilaus l�hetettiin onnistuneesti")."!</font><br>";
			$sqlquery = "	UPDATE lasku SET
							kirjoitin = 'edi'
							WHERE yhtio = '$kukarow[yhtio]'
							AND tunnus = '$laskurow[tunnus]'";
			$result = mysql_query($sqlquery) or pupe_error($sqlquery);
		}
		else {
			echo "<font class='error'>".t("Tilauksen l�hetys ep�onnistui")."!</font><br>";
		}
	}

	$teccom_editilaus = "";
	$out = "";

?>