<?php

	// jos ollaan saatu komentorivilt� parametri
	// komentorivilt� pit�� tulla parametrin� yhtio
	if (trim($argv[1]) != '') {

		if ($argc == 0) die ("T�t� scripti� voi ajaa vain komentorivilt�!");

		// otetaan tietokanta connect
		require ("inc/connect.inc");
		require ("inc/functions.inc");

		// hmm.. j�nn��
		$kukarow['yhtio'] = $argv[1];
		$kukarow['kuka'] = "crond";

		$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);
		$aja = 'run';

		echo date("d.m.Y @ G:i:s").": Iltasiivo $yhtiorow[nimi]\n";
	}
	else {
		require ("inc/parametrit.inc");

		echo "<font class='head'>Iltasiivo</font><hr>";

		if ($aja != "run") {
			echo "<br><form action='$PHP_SELF' method='post'>";
			echo "<input type='hidden' name='aja' value='run'>";
			echo "<input type='submit' value='Aja iltasiivo!'>";
			echo "</form>";
		}

		echo "<pre>";
	}

	if ($aja == "run") {

		$iltasiivo = "";
		$laskuri   = 0;

		// poistetaan kaikki tuotteen_toimittajat liitokset joiden toimittaja on poistettu
		$query = "	SELECT toimi.tunnus, tuotteen_toimittajat.tunnus toimtunnus
					from tuotteen_toimittajat
					left join toimi on toimi.yhtio = tuotteen_toimittajat.yhtio and toimi.tunnus = tuotteen_toimittajat.liitostunnus
					where tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
					having toimi.tunnus is null";
		$result = mysql_query($query) or die($query);

		while ($row = mysql_fetch_array($result)) {
			$query = "DELETE from tuotteen_toimittajat where tunnus = '$row[toimtunnus]'";
			$deler = mysql_query($query) or die($query);
			$laskuri ++;
		}

		if ($laskuri > 0) $iltasiivo .= date("d.m.Y @ G:i:s").": Poistettiin $laskuri poistetun toimittajan tuoteliitosta.\n";
		$laskuri = 0;

		// poistetaan kaikki tuotteen_toimittajat liitokset joiden tuote on poistettu
		$query = "	SELECT tuote.tunnus, tuotteen_toimittajat.tunnus toimtunnus
					from tuotteen_toimittajat
					left join tuote on tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno
					where tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
					having tuote.tunnus is null";
		$result = mysql_query($query) or die($query);

		while ($row = mysql_fetch_array($result)) {
			$query = "DELETE from tuotteen_toimittajat where tunnus = '$row[toimtunnus]'";
			$deler = mysql_query($query) or die($query);
			$laskuri ++;
		}

		if ($laskuri > 0) $iltasiivo .= date("d.m.Y @ G:i:s").": Poistettiin $laskuri poistetun tuotteen tuoteliitosta.\n";

		$laskuri = 0;
		$laskuri2 = 0;

		// poistetaan kaikki JT-otsikot jolla ei ole en�� rivej� ja extranet tilaukset joilla ei ole rivej� ja tietenkin my�s ennakkootsikot joilla ei ole rivej�.
		$query = "	SELECT tilausrivi.tunnus, lasku.tunnus laskutunnus, lasku.tila, lasku.tunnusnippu
					FROM lasku
					LEFT JOIN tilausrivi on( tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					AND lasku.tila in ('N','E','L')
					AND lasku.alatila != 'X'
					AND tilausrivi.tunnus is null";
		$result = mysql_query($query) or die($query);

		while ($row = mysql_fetch_array($result)) {
			$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mit�t�i ohjelmassa iltasiivo.php")." (1)<br>";

			//	Jos kyseess� on tunnusnippupaketti, halutaan s�ilytt�� linkki t�st� tehtyihin tilauksiin, tilaus merkataan vain toimitetuksi
			if ($row["tunnusnippu"] > 0) {
				$query = "UPDATE lasku set tila = 'L', alatila='X' where yhtio = '$kukarow[yhtio]' and tunnus = '$row[laskutunnus]'";
				$deler = mysql_query($query) or die($query);
				$laskuri2 ++;
			}
			else {
				$query = "UPDATE lasku set alatila='$row[tila]', tila='D',  comments = '$komm' where yhtio = '$kukarow[yhtio]' and tunnus = '$row[laskutunnus]'";
				$deler = mysql_query($query) or die($query);
				$laskuri ++;
			}

			//poistetaan TIETENKIN kukarow[kesken] ettei voi sy�tt�� extranetiss� rivej� t�lle
			$query = "UPDATE kuka set kesken = '' where yhtio = '$kukarow[yhtio]' and kesken = '$row[laskutunnus]'";
			$deler = mysql_query($query) or die($query);

		}

		if ($laskuri > 0) $iltasiivo .= date("d.m.Y @ G:i:s").": Poistettiin $laskuri rivit�nt� tilausta.\n";
		if ($laskuri2 > 0) $iltasiivo .= date("d.m.Y @ G:i:s").": Merkattiin toimitetuksi $laskuri2 rivit�nt� tilausta.\n";

		$laskuri = 0;

		// Merkit��n laskut mit�t�idyksi joilla on pelk�st��n mit�t�ityj� rivej� / pelk�st��n puuterivej�.
		$query = "	SELECT lasku.tunnus laskutunnus, lasku.tila, count(*) kaikki, sum(if (tilausrivi.tyyppi='D' or tilausrivi.var='P', 1, 0)) dellatut
					from lasku
					join tilausrivi on tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
					where lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila in ('N','E','L')
					and lasku.alatila != 'X'
					group by 1,2
					having dellatut > 0 and kaikki = dellatut";
		$result = mysql_query($query) or die($query);

		while ($row = mysql_fetch_array($result)) {
			$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mit�t�i ohjelmassa iltasiivo.php")." (2)<br>";

			$query = "UPDATE lasku set alatila='$row[tila]', tila='D',  comments = '$komm' where yhtio = '$kukarow[yhtio]' and tunnus = '$row[laskutunnus]'";
			$deler = mysql_query($query) or die($query);
			$laskuri ++;

			//poistetaan TIETENKIN kukarow[kesken] ettei voi sy�tt�� extranetiss� rivej� t�lle
			$query = "UPDATE kuka set kesken = '' where yhtio = '$kukarow[yhtio]' and kesken = '$row[laskutunnus]'";
			$deler = mysql_query($query) or die($query);

		}

		if ($laskuri > 0) $iltasiivo .= date("d.m.Y @ G:i:s").": Mit�t�itiin $laskuri tilausta joilla oli pelkki� mit�t�ityj� rivej�.\n";


		$laskuri = 0;

		// Merkit��n rivit mit�t�idyksi joiden otsikot on mit�t�ity (ei mit�t�id� puuterivej�, eik� suoraan keikkaan lis�ttyj� ostorivej� lasku.alatila!='K')
		$query = "	SELECT lasku.tunnus laskutunnus
					from lasku
					join tilausrivi on tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D' and tilausrivi.var != 'P'
					where lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila = 'D'
					and lasku.alatila != 'K'
					GROUP BY 1";
		$result = mysql_query($query) or die($query);

		while ($row = mysql_fetch_array($result)) {
			$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mit�t�i ohjelmassa iltasiivo.php")." (3)<br>";

			$query = "UPDATE tilausrivi set tyyppi='D' where yhtio = '$kukarow[yhtio]' and otunnus = '$row[laskutunnus]' and var != 'P'";
			$deler = mysql_query($query) or die($query);
			$laskuri ++;
		}

		if ($laskuri > 0) $iltasiivo .= date("d.m.Y @ G:i:s").": Mit�t�itiin $laskuri mit�t�idyn tilauksen rivit. (Rivit jostain syyst� ei dellattuja)\n";


		// t�ss� tehd��n isitt�mist� perheist� ei-perheit� ja my�s perheist� joissa ei ole lapsia eli nollataan perheid
		$lask = 0;
		$lask2 = 0;

		$query = "	SELECT perheid, count(*) koko
					FROM tilausrivi
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'L' and laskutettuaika = '0000-00-00' and perheid != '0'
					GROUP BY perheid";
		$result = mysql_query($query) or pupe_error($query);

		while ($row = mysql_fetch_array($result)) {
			$query = "	SELECT perheid
						FROM tilausrivi
						WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'L' and laskutettuaika = '0000-00-00' and tunnus = '$row[perheid]'";
			$result2 = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result2) == 0) {
				$lask++;
				$query = "UPDATE tilausrivi SET perheid = 0 WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'L' and laskutettuaika = '0000-00-00' and perheid = '$row[perheid]' order by tunnus";
				$upresult = mysql_query($query) or pupe_error($query);
			}
			else {
				if ($row['koko'] == 1) {
					$lask2++;
					$query = "UPDATE tilausrivi SET perheid = 0 WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'L' and laskutettuaika = '0000-00-00' and perheid = '$row[perheid]' order by tunnus";
					$upresult = mysql_query($query) or pupe_error($query);
				}
			}
		}

		if ($lask+$lask2 > 0) {
			$iltasiivo .= date("d.m.Y @ G:i:s").": Tuhottiin $lask orpoa perhett�, ja $lask2 lapsetonta is�� (eli perheid nollattiin)\n";
		}

		$lasktuote = 0;
		$laskpois = 0;
		$poistetaankpl = 0;

		$query = "	SELECT tuoteno, liitostunnus, count(tunnus) countti
					FROM tuotteen_toimittajat
					WHERE yhtio = '$kukarow[yhtio]'
					GROUP BY 1,2
					HAVING countti > 1";
		$result = mysql_query($query) or pupe_error($query);

		while ($row = mysql_fetch_array($result)) {
			$lasktuote++;
			$poistetaankpl = $row['countti']-1;

			$poisquery = "	DELETE FROM tuotteen_toimittajat
							WHERE yhtio = '$kukarow[yhtio]'
							AND tuoteno = '$row[tuoteno]'
							AND liitostunnus = '$row[liitostunnus]'
							ORDER BY tunnus DESC
						 	LIMIT $poistetaankpl";
			$poisresult = mysql_query($poisquery) or pupe_error($poisquery);
			$laskpois += mysql_affected_rows();
		}

		if ($lasktuote > 0) {
			$iltasiivo .= date("d.m.Y @ G:i:s").": Poistettiin $lasktuote tuotteelta yhteens� $laskpois duplikaatti tuotteen_toimittajaa\n";
		}

		$kukaquery = "	UPDATE kuka
						SET taso = '2'
						WHERE taso = '3'";
		$kukaresult = mysql_query($kukaquery) or pupe_error($kukaquery);

		if (mysql_affected_rows() > 0) {
			$iltasiivo .= date("d.m.Y @ G:i:s").": P�ivitettiin ".mysql_affected_rows()." k�ytt�j�n taso 3 --> 2\n";
		}

		// mit�t�id��n keskenolevia extranet-tilauksia, jos ne on liian vanhoja ja yhti�n parametri on p��ll�
		if ($yhtiorow['iltasiivo_mitatoi_ext_tilauksia'] != '') {

			$laskuri = 0;
			$aikaraja = (int) $yhtiorow['iltasiivo_mitatoi_ext_tilauksia'];

			$query = "	SELECT lasku.tunnus laskutunnus
						FROM lasku
						JOIN kuka ON (kuka.yhtio = lasku.yhtio AND kuka.kuka = lasku.laatija AND kuka.extranet != '')
						WHERE lasku.yhtio = '{$kukarow['yhtio']}'
						AND lasku.tila = 'N'
						AND lasku.alatila = ''
						AND lasku.luontiaika < DATE_SUB(now(), INTERVAL $aikaraja HOUR)";
			$result = mysql_query($query) or die($query);

			while ($row = mysql_fetch_assoc($result)) {
				// laitetaan kaikki poimitut extranet jt-rivit takaisin omille vanhoille tilauksille
				$query = "	SELECT tilausrivi.tunnus, tilausrivin_lisatiedot.vanha_otunnus
							FROM tilausrivi
							JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.positio = 'JT')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.otunnus = '{$row['laskutunnus']}'";
				$jt_rivien_muisti_res = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($jt_rivien_muisti_res) > 0) {
					$jt_saldo_lisa = $yhtiorow["varaako_jt_saldoa"] == "" ? ", jt = varattu, varattu = 0 " : '';

					while ($jt_rivien_muisti_row = mysql_fetch_assoc($jt_rivien_muisti_res)) {
						$query = "	UPDATE tilausrivi SET
									otunnus = '{$jt_rivien_muisti_row['vanha_otunnus']}',
									var = 'J'
									$jt_saldo_lisa
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tunnus = '{$jt_rivien_muisti_row['tunnus']}'";
						$jt_rivi_res = mysql_query($query) or pupe_error($query);
					}
				}

				$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mit�t�i ohjelmassa iltasiivo.php")." (4)<br>";

				$query = "	UPDATE lasku SET
							alatila = 'N',
							tila = 'D',
							comments = '$komm'
							WHERE yhtio = '{$kukarow['yhtio']}'
							and tunnus = '{$row['laskutunnus']}'";
				$deler = mysql_query($query) or die($query);

				$query = "	UPDATE tilausrivi SET
							tyyppi = 'D'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND otunnus = '{$row['laskutunnus']}'
							and var != 'P'";
				$deler = mysql_query($query) or die($query);

				//poistetaan TIETENKIN kukarow[kesken] ettei voi sy�tt�� extranetiss� rivej� t�lle
				$query = "	UPDATE kuka SET
							kesken = ''
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND kesken = '{$row['laskutunnus']}'";
				$deler = mysql_query($query) or die($query);

				$laskuri++;
			}

			if ($laskuri > 0) $iltasiivo .= date("d.m.Y @ G:i:s").": Mit�t�itiin $laskuri extranet-tilausta, jotka olivat $aikaraja tuntia vanhoja.\n";
		}

		if (table_exists('suorituskykyloki')) {
			$query = "	DELETE FROM suorituskykyloki
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND luontiaika < date_sub(now(), INTERVAL 1 YEAR)";
			$deler = mysql_query($query) or die($query);

			if (mysql_affected_rows() > 0) {
				$iltasiivo .= date("d.m.Y @ G:i:s").": Poistettiin ".mysql_affected_rows()." rivi� suorituskykylokista.\n";
			}
		}

		if ($iltasiivo != "" or trim($argv[1]) != '') {
			echo $iltasiivo;
			echo date("d.m.Y @ G:i:s").": Iltasiivo $yhtiorow[nimi]. Done!\n\n";
		}

		if ($iltasiivo != "") {
			if (isset($iltasiivo_email) and $iltasiivo_email == 1) {
				$header	 = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
				$header .= "MIME-Version: 1.0\n" ;
				$subject = "Iltasiivo ".date("d.m.Y")." - $yhtiorow[nimi]";

				mail($yhtiorow["admin_email"], "Iltasiivo yhtiolle '{$yhtiorow["yhtio"]}'", $iltasiivo, $header, " -f $yhtiorow[postittaja_email]");
			}
		}
	}

	if (trim($argv[1]) == '') {
		echo "</pre>";
		require('inc/footer.inc');
	}

?>