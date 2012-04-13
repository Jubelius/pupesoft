<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Logistiikkaseuranta")."</font><hr>";

	echo "<br>";
	echo "<table><form method='post' action=''>";
	echo "<input type='hidden' name='tee' value='nayta'>";

	echo "<tr><th>Tilausnumero</th><td><input type='text' name='tilaus' value='$tilaus' size='15'></td></tr>";
	echo "<tr><th>Laskunumero</th><td><input type='text' name='lasku' value='$lasku' size='15'></td></tr>";
	echo "<tr><th>Valitse p�iv�:</th>";
	echo "<td><select name='paiva'>";

	for ($y = 20120401; $y <= date("Ymd"); $y++) {

		$z = substr($y,0,4)."-".substr($y,4,2)."-".substr($y,6,2);

		$sel = ($paiva == $z) ? "SELECTED" : "";

		echo "<option value='$z' $sel>".substr($y,6,2).".".substr($y,4,2).".".substr($y,0,4)."</option>";
	}

	echo "</select></td>";
	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td></tr></table><br><br>";

	if ($tee == "nayta") {

		$tilaus = mysql_real_escape_string($tilaus);
		$lasku = mysql_real_escape_string($lasku);
		$paiva = mysql_real_escape_string($paiva);

		$pvmlisa = " and tapvm = '$paiva' and summa > 0 ";

		if ($tilaus > 0) {
			$query = "	SELECT laskunro
						FROM lasku
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tila 	= 'L'
						AND alatila = 'X'
						and tunnus = $tilaus";
			$lasku_res = pupe_query($query);

			if (mysql_num_rows($lasku_res) > 0) {
				$laskurow = mysql_fetch_assoc($lasku_res);

				$laskulisa 	= " and laskunro = {$laskurow['laskunro']} ";
				$pvmlisa 	= "";
				$lasku 		= "";
			}
			else {
				$laskulisa 	= " and tunnus = 0 ";
			}
		}

		if ($lasku > 0) {
			$laskulisa 	= " and laskunro = {$laskurow['laskunro']} ";
			$pvmlisa 	= "";
		}

		$query = "	SELECT tunnus, laskunro
					FROM lasku
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tila 	= 'U'
					AND alatila = 'X'
					{$laskulisa}
					{$pvmlisa}
					ORDER BY tunnus";
		$lasku_res = pupe_query($query);

		echo "<table>";

		while ($laskurow = mysql_fetch_assoc($lasku_res)) {

			$rivi 		= "";
			$naytarivi  = TRUE;

			$query = "	SELECT tunnus, nimi, toimitustapa, tila, alatila, tilaustyyppi, toimitustavan_lahto, varasto, kohdistettu, rahtivapaa, eilahetetta
						FROM lasku
						WHERE yhtio  = '{$kukarow['yhtio']}'
						and tunnus   = '{$laskurow['laskunro']}'
						AND tila 	 = 'L'
						AND alatila  = 'X'";
			$tilaus_res = pupe_query($query);

			$tilaukset = "";

			$rivi .= "<tr>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Lasku/Tilaus</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Asiakas</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Toimitustapa / L�ht�</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Tila</th>";
			$rivi .= "</tr>";

			while ($tilausrow = mysql_fetch_assoc($tilaus_res)) {

				$tilaukset .= $tilausrow['tunnus'].",";

				$laskutyyppi = $tilausrow["tila"];
				$alatila	 = $tilausrow["alatila"];

				//tehd��n selv�kielinen tila/alatila
				require "inc/laskutyyppi.inc";

				$tarkenne = " ";

				if ($tilausrow["tila"] == "V" and $tilausrow["tilaustyyppi"] == "V") {
					$tarkenne = " (".t("Asiakkaalle").") ";
				}
				elseif ($tilausrow["tila"] == "V" and  $tilausrow["tilaustyyppi"] == "W") {
					$tarkenne = " (".t("Varastoon").") ";
				}
				elseif(($tilausrow["tila"] == "N" or $tilausrow["tila"] == "L") and $tilausrow["tilaustyyppi"] == "R") {
					$tarkenne = " (".t("Reklamaatio").") ";
				}
				elseif(($tilausrow["tila"] == "N" or $tilausrow["tila"] == "L") and $tilausrow["tilaustyyppi"] == "A") {
					$laskutyyppi = "Ty�m��r�ys";
				}
				elseif($tilausrow["tila"] == "N" and $tilausrow["tilaustyyppi"] == "E") {
					$laskutyyppi = "Ennakkotilaus kesken";
				}

				// Tilauksen tiedot
				$rivi .= "<tr>";
				$rivi .= "<td class='spec'>$laskurow[laskunro] / <a target='Asiakkaantilaukset' href='asiakkaantilaukset.php?tee=NAYTATILAUS&toim=MYYNTI&tunnus=$tilausrow[tunnus]'>$tilausrow[tunnus]</a></td>";
				$rivi .= "<td class='spec'>$tilausrow[nimi] / $tilausrow[eilahetetta]</td>";
				$rivi .= "<td class='spec'>$tilausrow[toimitustapa] / $tilausrow[toimitustavan_lahto] / ($tilausrow[kohdistettu]|$tilausrow[rahtivapaa])</td>";
				$rivi .= "<td class='spec'>".t("$laskutyyppi")."$tarkenne".t("$alatila")."</td>";
				$rivi .= "</tr>";
			}

			$tilaukset = substr($tilaukset, 0, -1);

			// Tilausrivit
			$query = "	SELECT tilausrivi.*, keraysvyohyke.nimitys kervyohyke
						FROM tilausrivi
						JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno and tuote.ei_saldoa ='')
						JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus and tilausrivin_lisatiedot.ohita_kerays = '')
						LEFT JOIN varaston_hyllypaikat ON (varaston_hyllypaikat.yhtio = tilausrivi.yhtio AND varaston_hyllypaikat.hyllyalue = tilausrivi.hyllyalue AND varaston_hyllypaikat.hyllynro = tilausrivi.hyllynro AND varaston_hyllypaikat.hyllyvali = tilausrivi.hyllyvali AND varaston_hyllypaikat.hyllytaso = tilausrivi.hyllytaso)
						LEFT JOIN keraysvyohyke ON (varaston_hyllypaikat.yhtio = keraysvyohyke.yhtio AND varaston_hyllypaikat.keraysvyohyke = keraysvyohyke.tunnus)
						WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
						and tilausrivi.otunnus in ($tilaukset)
						#and tilausrivi.kpl	   > 0
						ORDER BY tilausrivi.otunnus, tilausrivi.tunnus";
			$tilausrivi_res = pupe_query($query);

			$rivi .= "<tr>";
			$rivi .= "<td class='back' colspan='4' style='padding:0px; margin:0px;'>";
			$rivi .= "<table style='width:100%; height:100%;'>";

			$rivi .= "<tr>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Tilaus</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Tuote</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Nimitys</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Tilkpl</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Kpl</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Var</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Ker�yser�</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Pakkaus</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Vy�hyke</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Tilrivi laadittu</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Kerer� laadittu</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Ker��j�</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Ker�ttyaika</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Kerer� kpl</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Kerer� ker�tty</th>";
			$rivi .= "</tr>";

			$ohita_kerays = array();

			while ($tilausrivirow = mysql_fetch_assoc($tilausrivi_res)) {

				// Kerayser�/er�t
				$query = "	SELECT group_concat(luontiaika) luontiaika,
							group_concat(distinct nro) nro,
							group_concat(pakkausnro) pakkausnro,
							round(sum(kpl)) kpl,
							round(sum(kpl_keratty)) kpl_keratty
							FROM kerayserat
							WHERE yhtio 	= '{$kukarow['yhtio']}'
							and otunnus 	= '{$tilausrivirow['otunnus']}'
							and tilausrivi 	= '{$tilausrivirow['tunnus']}'";
				$keraysera_res = pupe_query($query);
				$kerayserarow = mysql_fetch_assoc($keraysera_res);

				$pakkaus_kirjain = ($kerayserarow["pakkausnro"] > 0) ? chr(64+$kerayserarow["pakkausnro"]) : "";

				// Tsekataan viel� ohita_kerays jutut!
				if ($tilausrivirow['perheid'] > 0 and $tilausrivirow['tunnus'] != $tilausrivirow['perheid']) {

					// haetaan is�
					$query_isa = " SELECT tuoteno
								   FROM tilausrivi
								   WHERE yhtio = '{$kukarow['yhtio']}'
								   AND tunnus  = '{$tilausrivirow['perheid']}'
								   AND perheid = '{$tilausrivirow['perheid']}'";
					$isa_chk_res = pupe_query($query_isa);
					$isa_chk_row = mysql_fetch_assoc($isa_chk_res);

					$query_ohita = " SELECT ohita_kerays
									 FROM tuoteperhe
									 WHERE yhtio 	= '{$kukarow['yhtio']}'
									 AND tyyppi 	= 'P'
									 AND isatuoteno = '{$isa_chk_row['tuoteno']}'
									 AND tuoteno 	= '{$tilausrivirow['tuoteno']}'";
					$ohita_chk_res = pupe_query($query_ohita);
					$ohita_chk_row = mysql_fetch_assoc($ohita_chk_res);

					if ($ohita_chk_row['ohita_kerays'] != '') {
						$ohita_kerays[$tilausrivirow['tunnus']] = TRUE;
					}
				}

				$kereraekotus = (isset($ohita_kerays[$tilausrivirow['tunnus']])) ? "KER�YS OHITETEAAN" : $kerayserarow["nro"];

				$rivi .= "<tr>";
				$rivi .= "<td>$tilausrivirow[otunnus]</td>";
				$rivi .= "<td><a target='Tuotekysely' href='{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($tilausrivirow["tuoteno"])."'>$tilausrivirow[tuoteno]</a></td>";
				$rivi .= "<td>".substr($tilausrivirow["nimitys"],0,20)."</td>";
				$rivi .= "<td align='right'>".(float) ($tilausrivirow["tilkpl"])."</td>";
				$rivi .= "<td align='right'>".(float) ($tilausrivirow["varattu"]+$tilausrivirow["kpl"])."</td>";
				$rivi .= "<td>$tilausrivirow[var]</td>";
				$rivi .= "<td>$kereraekotus</td>";
				$rivi .= "<td>$pakkaus_kirjain</td>";
				$rivi .= "<td>$tilausrivirow[kervyohyke]</td>";
				$rivi .= "<td>".tv1dateconv($tilausrivirow["laadittu"], "PITKA", "LYHYT")."</td>";
				$rivi .= "<td>".tv1dateconv($kerayserarow["luontiaika"], "PITKA", "LYHYT")."</td>";
				$rivi .= "<td>$tilausrivirow[keratty]</td>";
				$rivi .= "<td>".tv1dateconv($tilausrivirow["kerattyaika"], "PITKA", "LYHYT")."</td>";
				$rivi .= "<td align='right'>$kerayserarow[kpl]</td>";
				$rivi .= "<td align='right'>$kerayserarow[kpl_keratty]</td>";
				$rivi .= "</tr>";

				if ($tilausrivirow["kpl"] != 0 and $tilausrivirow["keratty"] == "" and !isset($ohita_kerays[$tilausrivirow['tunnus']])) {
					$naytarivi = TRUE;
				}
			}

			$rivi .= "</table>";
			$rivi .= "</td>";
			$rivi .= "</tr>";

			// Rahtikirjan  tiedot kannasta
			$rivi .= "<tr>";
			$rivi .= "<td class='back' colspan='4' style='padding:0px; margin:0px;'>";
			$rivi .= "<table style='width:100%; height:100%;'>";

			$rivi .= "<tr>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Rahtikirja</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Kollit</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Kilot</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Kuutiot</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Pakkaus</th>";
			$rivi .= "</tr>";

			$query = "	SELECT otsikkonro, rahtikirjanro, kollit, round(kilot,2) kilot, kuutiot, pakkaus
						FROM rahtikirjat
						WHERE yhtio	= '{$kukarow['yhtio']}'
						and otsikkonro in ($tilaukset)";
			$rakir_res = pupe_query($query);

			while ($rakirrow = mysql_fetch_assoc($rakir_res)) {
				$rivi .= "<tr>";
				$rivi .= "<td>$rakirrow[otsikkonro]</td>";
				$rivi .= "<td>$rakirrow[kollit]</td>";
				$rivi .= "<td>$rakirrow[kilot]</td>";
				$rivi .= "<td>$rakirrow[kuutiot]</td>";
				$rivi .= "<td>$rakirrow[pakkaus]</td>";
				$rivi .= "</tr>";
			}

			/*
			// Rahtikirjan  tiedot, nii ku ne pit�is olla
			$rivi .= "<tr>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Rahtikirja</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Kollit</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Kilot</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Kuutiot</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Pakkaus</th>";
			$rivi .= "</tr>";

			$query = "	SELECT
						kerayserat.nro,
						IFNULL(pakkaus.pakkaus, 'MUU KOLLI') pakkaus,
						IFNULL(pakkaus.pakkauskuvaus, 'MUU KOLLI') pakkauskuvaus,
						IFNULL(pakkaus.oma_paino, 0) oma_paino,
						IF(pakkaus.puukotuskerroin is not null and pakkaus.puukotuskerroin > 0, pakkaus.puukotuskerroin, 1) puukotuskerroin,
						SUM(tuote.tuotemassa * kerayserat.kpl_keratty) tuotemassa,
						SUM(tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys * kerayserat.kpl_keratty) as kuutiot,
						COUNT(distinct kerayserat.pakkausnro) AS kollit
						FROM kerayserat
						LEFT JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
						JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
						WHERE kerayserat.yhtio 	= '{$kukarow['yhtio']}'
						AND kerayserat.otunnus 	in ($tilaukset)
						GROUP BY 1,2,3,4
						ORDER BY kerayserat.pakkausnro";
			$rakir_res = pupe_query($query);

			while ($rakirrow = mysql_fetch_assoc($rakir_res)) {

				$kilot = round($rakirrow["tuotemassa"] + $rakirrow["oma_paino"], 2);
				$kuutiot = round($rakirrow["kuutiot"] * $rakirrow["puukotuskerroin"], 4);

				$rivi .= "<tr>";
				$rivi .= "<td>{$laskurow['tunnus']}</td>";
				$rivi .= "<td>$rakirrow[kollit]</td>";
				$rivi .= "<td>$kilot</td>";
				$rivi .= "<td>$kuutiot</td>";
				$rivi .= "<td>$rakirrow[pakkaus]</td>";
				$rivi .= "</tr>";
			}

			*/

			$rivi .= "</table>";
			$rivi .= "</td>";
			$rivi .= "</tr>";


			// Rahtiveloitus
			$rivi .= "<tr>";
			$rivi .= "<td class='back' colspan='4' style='padding:0px; margin:0px;'>";
			$rivi .= "<table style='width:100%; height:100%;'>";

			$rivi .= "<tr>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Tuoteno</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Nimitys</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Hinta</th>";
			$rivi .= "</tr>";

			// Rahtiveloitus
			$query = "	SELECT tuoteno, nimitys, round(hinta, 2) hinta
						FROM tilausrivi
						WHERE yhtio = '{$kukarow['yhtio']}'
						and otunnus in ($tilaukset)
						and tuoteno = '{$yhtiorow['rahti_tuotenumero']}'";
			$raku_res = pupe_query($query);

			$veloitettu = 0;

			while ($rakurow = mysql_fetch_assoc($raku_res)) {
				$rivi .= "<tr>";
				$rivi .= "<td>$rakurow[tuoteno]</td>";
				$rivi .= "<td>$rakurow[nimitys]</td>";
				$rivi .= "<td>$rakurow[hinta]</td>";
				$rivi .= "</tr>";

				$veloitettu += $rakurow["hinta"];
			}

			$rivi .= "</table>";
			$rivi .= "</td>";
			$rivi .= "</tr>";


			// Rahtiveloitus uudestaanlaskettuna
			$rivi .= "<tr>";
			$rivi .= "<td class='back' colspan='4' style='padding:0px; margin:0px;'>";
			$rivi .= "<table style='width:100%; height:100%;'>";

			$rivi .= "<tr>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Tuoteno</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Nimitys</th>";
			$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Hinta</th>";
			$rivi .= "</tr>";

			// haetaan laskutettavista tilauksista kaikki distinct toimitustavat per asiakas per p�iv� miss� merahti (eli kohdistettu) = K (K�ytet��n l�hett�j�n rahtisopimusnumeroa)
			// j�lkivaatimukset omalle riville
			$query = "	SELECT group_concat(distinct lasku.tunnus) tunnukset
						FROM lasku, rahtikirjat, maksuehto
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tunnus in ($tilaukset)
						and lasku.rahtivapaa 	= ''
						and lasku.kohdistettu 	= 'K'
						and lasku.yhtio 	= rahtikirjat.yhtio
						and lasku.tunnus 	= rahtikirjat.otsikkonro
						and lasku.yhtio 	= maksuehto.yhtio
						and lasku.maksuehto = maksuehto.tunnus
						GROUP BY date_format(rahtikirjat.tulostettu, '%Y-%m-%d'), lasku.ytunnus, lasku.toimitustapa, maksuehto.jv";
			$result  = pupe_query($query);

			$yhdista = array();

			while ($row = mysql_fetch_assoc($result)) {
				$yhdista[] = $row["tunnukset"];
			}

			$veloitettu_oispitany = 0;

			foreach ($yhdista as $otsikot) {

				// lis�t��n n�ille tilauksille rahtikulut
				$virhe = 0;
				$pvm = "";

				// haetaan ekan otsikon tiedot
				$query = "  SELECT lasku.*, maksuehto.jv
							FROM lasku, maksuehto
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							and lasku.tunnus in ($otsikot)
							and lasku.yhtio = maksuehto.yhtio
							and lasku.maksuehto = maksuehto.tunnus
							ORDER BY lasku.tunnus
							LIMIT 1";
				$otsre = pupe_query($query);
				$ramarow = mysql_fetch_assoc($otsre);

				if (mysql_num_rows($otsre)!=1) $virhe++;

				// summataan kaikki painot yhteen
				$query = "	SELECT sum(kilot) kilot
							FROM rahtikirjat
							WHERE yhtio = '$kukarow[yhtio]'
							and otsikkonro in ($otsikot)";
				$pakre = pupe_query($query);
				$pakka = mysql_fetch_assoc($pakre);
				if (mysql_num_rows($pakre)!=1) $virhe++;

				// haetaan v�h�n infoa rahtikirjoista
				$query = "	SELECT distinct date_format(tulostettu, '%d.%m.%Y') pvm, rahtikirjanro
							from rahtikirjat
							where yhtio = '$kukarow[yhtio]'
							and otsikkonro in ($otsikot)";
				$rahre = pupe_query($query);
				if (mysql_num_rows($rahre)==0) $virhe++;

				$rahtikirjanrot = "";
				while ($rahrow = mysql_fetch_assoc($rahre)) {
					if ($rahrow["pvm"]!='') $pvm = $rahrow["pvm"]; // pit�s olla kyll� aina sama
					$rahtikirjanrot .= "$rahrow[rahtikirjanro] ";
				}

				// vika pilkku pois
				$rahtikirjanrot = substr($rahtikirjanrot,0,-1);

				// haetaan rahdin hinta
				$rahtihinta_array = hae_rahtimaksu($otsikot);

				$rahtihinta_ale = array();

				// rahtihinta tulee rahtimatriisista yhti�n kotivaluutassa ja on verollinen, jos myyntihinnat ovat verollisia, tai veroton, jos myyntihinnat ovat verottomia (huom. yhti�n parametri alv_kasittely)
				if (is_array($rahtihinta_array)) {
					$rahtihinta = $rahtihinta_array['rahtihinta'];

					foreach ($rahtihinta_array['alennus'] as $ale_k => $ale_v) {
						$rahtihinta_ale[$ale_k] = $ale_v;
					}
				}
				else {
					$rahtihinta = 0;
				}

				$query = "  SELECT *
							FROM tuote
							WHERE yhtio = '$kukarow[yhtio]'
							AND tuoteno = '$yhtiorow[rahti_tuotenumero]'";
				$rhire = pupe_query($query);

				if ($rahtihinta != 0 and $virhe == 0 and mysql_num_rows($rhire) == 1) {

					$trow       = mysql_fetch_assoc($rhire);
					$otunnus    = $ramarow['tunnus'];
					$hinta      = $rahtihinta;
					$nimitys    = "$pvm $ramarow[toimitustapa]";
					$kommentti  = t("Rahtikirja").": $rahtikirjanrot";
					$netto      = count($rahtihinta_ale) > 0 ? '' : 'N';

					list($lis_hinta, $lis_netto, $lis_ale_kaikki, $alehinta_alv, $alehinta_val) = alehinta($ramarow, $trow, '1', $netto, $hinta, $rahtihinta_ale);
					list($rahinta, $alv) = alv($ramarow, $trow, $lis_hinta, '', $alehinta_alv);

					$ale_lisa_insert_query_1 = $ale_lisa_insert_query_2 = '';

					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
						$ale_lisa_insert_query_1 .= " ale{$alepostfix},";
						$ale_lisa_insert_query_2 .= " '".$lis_ale_kaikki["ale{$alepostfix}"]."',";
					}

					$rivi .= "<tr><td>$trow[tuoteno]</td><td>$nimitys</td><td>$rahtihinta</td></tr>\n";

					$veloitettu_oispitany += $rahtihinta;
				}
			}

			$rivi .= "</table>";
			$rivi .= "</td>";
			$rivi .= "</tr>";

			if ($veloitettu_oispitany != $veloitettu) {
				$naytarivi = TRUE;
			}

			// V�lirivi
			$rivi .= "<tr>";
			$rivi .= "<td class='back' colspan='4' style='height:10px;'></td>";
			$rivi .= "</tr>";

			if ($naytarivi) {
				echo "$rivi";
			}
		}

		echo "</table>";
	}

	require ("inc/footer.inc");
?>