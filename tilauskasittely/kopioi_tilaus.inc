<?php

if (!function_exists("kopioi_tilaus")) {

	// otetaan parametriksi tunnus
	function kopioi_tilaus($tunnus, $muokkaa_lasku = "", $muokkaa_rivi = "", $tapahtumapvm = "") {

		// tarvitaan yhti�rowta ja kukarowta
		global $yhtiorow, $kukarow;

		$query = "	SELECT *
					FROM lasku
					WHERE tunnus = '$tunnus'
					AND yhtio = '{$kukarow["yhtio"]}'";
		$monistares = pupe_query($query);

		$query = "	SELECT *
					FROM laskun_lisatiedot
					WHERE otunnus = '$tunnus'
					AND yhtio = '{$kukarow["yhtio"]}'";
		$lisatiedotres = pupe_query($query);

		// Katsotaan, ett� tilausrivi on voimassa $tapahtumapvm == sopimuksen t�m� laskutusp�iv�
		if ($tapahtumapvm != "") {
			// tilausrivi.kerayspvm => poikkeava alkup�iv� on sy�tetty ja se on pienempi kuin t�m� laskutusp�iv�
			// tilausrivi.toimaika => poikkeava loppup�iv� on sy�tetty ja se suurempi kuin t�m� laskutusp�iv�
			$lisaehto = "	AND (tilausrivi.kerayspvm = '0000-00-00' or (tilausrivi.kerayspvm != '0000-00-00' and tilausrivi.kerayspvm <= '$tapahtumapvm'))
							AND (tilausrivi.toimaika = '0000-00-00' or (tilausrivi.toimaika != '0000-00-00' and tilausrivi.toimaika >= '$tapahtumapvm'))";
		}
		else {
			$lisaehto = "";
		}

		$query = "	SELECT *
					FROM tilausrivi
					WHERE otunnus = '$tunnus'
					AND yhtio = '{$kukarow["yhtio"]}'
					$lisaehto";
		$rivires = pupe_query($query);

		if (mysql_num_rows($monistares) == 0) {
			echo "lasku not found";
			return FALSE;
		}

		if (mysql_num_rows($rivires) == 0) {
			echo "tilausrivi not found";
			return FALSE;
		}

		$monistarow = mysql_fetch_array($monistares);

		$fields = mysql_field_name($monistares,0);
		$values = "'".$monistarow[0]."'";

		// monistetaan kaikki paitsi tunnus
		for ($i = 1; $i < mysql_num_fields($monistares)-1; $i++) {
			$field = mysql_field_name($monistares, $i);
			$fields .= ", $field";

			//	Muokkaukset
			if (isset($muokkaa_lasku[$field]) and is_array($muokkaa_lasku[$field])) {
				if ($muokkaa_lasku[$field]["from"] != "" and $muokkaa_lasku[$field]["to"] != "") {
					$monistarow[$i] = preg_replace($muokkaa_lasku[$field]["from"], $muokkaa_lasku[$field]["to"], $monistarow[$i]);
				}
			}

			$values .= ", '$monistarow[$i]'";
		}

		$kysely  = "INSERT into lasku ($fields) VALUES ($values)";
		$insres  = pupe_query($kysely);
		$utunnus = mysql_insert_id();

		// laskun_lisatiedot
		if (mysql_num_rows($lisatiedotres) > 0) {
			$lisatiedotrow = mysql_fetch_array($lisatiedotres);

			$fields = mysql_field_name($lisatiedotres,0);
			$values = "'".$lisatiedotrow[0]."'";

			// monistetaan kaikki paitsi tunnus
			for ($i = 1; $i < mysql_num_fields($lisatiedotres) - 1; $i++) {
				$field = mysql_field_name($lisatiedotres, $i);
				$fields .= ", $field";

				if ($field == 'otunnus') {
					$values .= ", '$utunnus'";
				}
				else {
					$values .= ", '$lisatiedotrow[$i]'";
				}
			}
		}

		$query = "INSERT INTO laskun_lisatiedot ($fields) VALUES ($values)";
		$insres = pupe_query($query);

		while ($rivirow = mysql_fetch_array($rivires)) {

			$rfields = mysql_field_name($rivires,0);
			$rvalues = "'$monistarow[0]'";

			for ($i = 1; $i < mysql_num_fields($rivires)-1; $i++) {

				$field = mysql_field_name($rivires, $i);

				if (mysql_field_name($rivires, $i) == "otunnus") {
					$rfields .= ", $field";
					$rvalues .= ", '$utunnus'";
				}
				else {

					//	Muokkaukset
					if (isset($muokkaa_rivi[$field]) and is_array($muokkaa_rivi[$field])) {
						if ($muokkaa_rivi[$field]["from"] != "" and $muokkaa_rivi[$field]["to"] != "") {
							$rivirow[$i] = preg_replace($muokkaa_rivi[$field]["from"], $muokkaa_rivi[$field]["to"], $rivirow[$i]);
						}
					}

					$rfields .= ", $field";
					$rvalues .= ", '$rivirow[$i]'";
				}
			}

			$kysely = "INSERT into tilausrivi ($rfields) VALUES ($rvalues)";
			$insres = pupe_query($kysely);
			$insid  = mysql_insert_id();
		}

		//Korjataan perheid:t uusilla riveill�
		$query = "	SELECT perheid, min(tunnus) uusiperheid
					FROM tilausrivi
					WHERE yhtio = '{$kukarow["yhtio"]}'
					AND otunnus = '$utunnus'
					AND perheid != 0
					GROUP by perheid";
		$copresult = pupe_query($query);

		while ($coprivirow = mysql_fetch_array($copresult)) {
			$query = "	UPDATE tilausrivi
						SET perheid = '{$coprivirow["uusiperheid"]}'
						WHERE yhtio = '{$kukarow["yhtio"]}'
						AND otunnus = '$utunnus'
						AND perheid = '{$coprivirow["perheid"]}'";
			$cores = pupe_query($query);
		}

		//Korjataan perheid2:t uusilla riveill�
		$query = "	SELECT perheid2, min(tunnus) uusiperheid2
					FROM tilausrivi
					WHERE yhtio = '{$kukarow["yhtio"]}'
					AND otunnus = '$utunnus'
					AND perheid2 != 0
					GROUP by perheid2";
		$copresult = pupe_query($query);

		while ($coprivirow = mysql_fetch_array($copresult)) {
			$query = "	UPDATE tilausrivi
						SET perheid2 = '{$coprivirow["uusiperheid2"]}'
						WHERE yhtio = '{$kukarow["yhtio"]}'
						AND otunnus = '$utunnus'
						AND perheid2 = '{$coprivirow["perheid2"]}'";
			$cores = pupe_query($query);
		}

		return $utunnus; // palautetaan uusi lasku.tunnus
	}
}
