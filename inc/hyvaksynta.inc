<?php

//	Jos olemme tekem�ss� jotain meill� on oltava kuka tai listaamme kaikki laskut mit� tietokannasta l�ytyy!
if($tee == "T" and strlen(trim($kuka)) == 0) {
	echo "<font class='error'>".t("VIRHE: Hyv�ksyj� puuttuu")."!</font><br><br>";
	$tee = "";
}

if ($tee == '') {
 	// Summaus hyv�ksynn�ss� olevista laskuista
	echo "<font class='head'>".t("Laskuja hyv�ksym�tt�")."</font><hr>";

	$query = "	SELECT hyvaksyja_nyt,
				if(kuka.nimi is not null, kuka.nimi, hyvaksyja_nyt) nimi,
				concat_ws('/',sum(1), sum(if(alatila='H', 1, 0))) kplpys,
				min(erpcm) erpcm,
				max(erpcm) maxerpcm,
				round(sum(if(lasku.alatila = 'K', lasku.summa - lasku.kasumma, lasku.summa) * if(lasku.maksu_kurssi = 0, lasku.vienti_kurssi, lasku.maksu_kurssi)), 2) summa
				FROM lasku
				LEFT JOIN kuka ON kuka.yhtio=lasku.yhtio and kuka.kuka=lasku.hyvaksyja_nyt
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and tila = 'H'
				and hyvaksyja_nyt != ''
				GROUP BY 1,2
				ORDER BY nimi";
	$result = mysql_query($query) or pupe_error($query);

	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Kuka")."</th>";
	echo "<th>".t("Kpl")."/".t("pys�ytetty")."</th>";
	echo "<th>".t("Min")." ".t("er�pvm")."</th>";
	echo "<th>".t("Max")." ".t("er�pvm")."</th>";
	echo "<th>".t("Summa")." $yhtiorow[valkoodi]</th>";
	echo "</tr>";

	while ($trow = mysql_fetch_array($result)) {
		echo "<tr class='aktiivi'>";
		echo "<td><a href = 'raportit.php?toim=hyvaksynta&tee=T&kuka=$trow[hyvaksyja_nyt]'>$trow[nimi]</a></td>";
		echo "<td>$trow[kplpys]</td>";
		echo "<td>".tv1dateconv($trow["erpcm"])."</td>";
		echo "<td>".tv1dateconv($trow["maxerpcm"])."</td>";
		echo "<td align='right'>$trow[summa]</td>";
		echo "</tr>";
	}
	echo "</table><br>";
}

// Yhden k�ytt�j�n p�yd�ll� olevat laskut tai sen muutos
if ($tee == 'T') {
	// Muutetaan hyv�ksynt��
	if ((strlen($mika) > 0) and (strlen($nimi) > 0)) {
		$query = "	SELECT hyvak1, hyvak2, hyvak3, hyvak4, hyvak5, hyvaksyja_nyt, h1time, h2time, h3time, h4time, h5time
				  	FROM lasku
				  	WHERE tunnus = '$mika' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Muutettavaa laskua ei l�ytynyt")."!</font>";
			exit;
		}
		$trow=mysql_fetch_array ($result);

		$mita="";

		// Aikaikkunan poisto, joku muu ehti ennen meit�
		if ($trow['hyvaksyja_nyt'] == $kuka) {
			if (($trow['hyvak1'] == $kuka) and ($trow['h1time'] == '0000-00-00 00:00:00')) $mita = "hyvak1";
			elseif (($trow['hyvak2'] == $kuka) and ($trow['h2time'] == '0000-00-00 00:00:00')) $mita = "hyvak2";
			elseif (($trow['hyvak3'] == $kuka) and ($trow['h3time'] == '0000-00-00 00:00:00')) $mita = "hyvak3";
			elseif (($trow['hyvak4'] == $kuka) and ($trow['h4time'] == '0000-00-00 00:00:00')) $mita = "hyvak4";
			elseif (($trow['hyvak5'] == $kuka) and ($trow['h5time'] == '0000-00-00 00:00:00')) $mita = "hyvak5";
			else {
				echo "<font class='error'>".t("Laskun siirto ei onnistunut")."</font> '$kuka' --> '$nimi'<br>";
				exit;
			}

			$query = "	UPDATE lasku set
						$mita = '$nimi',
						hyvaksyja_nyt = '$nimi',
						alatila = ''
					  	WHERE tunnus = '$mika'";
			$result = mysql_query($query) or pupe_error($query);
			echo "<font class='message'>".t("Lasku siirrettiin").".. '$kuka' --> '$nimi'</font><br>";
		}
		else {
			echo "<font class='error'>".t("Lasku ei en�� ollut siirrett�viss�")."!</font><br>";
		}
	}

	$query = "	SELECT nimi, kuka, tuuraaja
			  	FROM kuka
			  	WHERE yhtio = '$kukarow[yhtio]'
				and kuka = '$kuka'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='message'>".t("K�ytt�j�� ei en�� l�ydy t�lt� yhti�lt�")."!</font><br>";
		$trow = array();
	}
	else {
		$trow = mysql_fetch_array ($result);
	}

	echo "<font class='head'>$trow[nimi]".t(":n p�yd�ll� olevat laskut")."</font><hr>";

	// Tehd��n popup, jolla voidaan hyv�ksyj� my�hemmin vaihtaa
	$query = "	SELECT DISTINCT kuka.kuka, kuka.nimi
				FROM kuka
				JOIN oikeu ON oikeu.yhtio = kuka.yhtio and oikeu.kuka = kuka.kuka and oikeu.nimi like '%hyvak.php'
				WHERE kuka.yhtio = '$kukarow[yhtio]'
				and kuka.hyvaksyja = 'o'
				ORDER BY kuka.nimi";
	$result = mysql_query($query) or pupe_error($query);

	$ulos = "<select name='nimi'>";

	while ($vrow = mysql_fetch_array($result)) {
		$sel = "";
		if ($vrow['kuka'] == $trow['tuuraaja']) {
			$sel = "selected";
		}
		$ulos .= "<option value = '$vrow[kuka]' $sel>$vrow[nimi]";
	}
	$ulos .= "</select>";

	$query = "	SELECT tapvm, kapvm, erpcm, if(alatila='',nimi,concat_ws('<br>', nimi, comments)) nimi, postitp, round(summa * vienti_kurssi, 2) 'kotisumma',
				summa, valkoodi, ebid, tunnus, alatila, if(laskunro = 0, '', laskunro) laskunro
				FROM lasku
				WHERE hyvaksyja_nyt='$kuka'
				and yhtio = '$kukarow[yhtio]'
				and tila in ('H','Y','M','P','Q')
				ORDER BY erpcm";
	$result = mysql_query($query) or pupe_error($query);

	echo "<table><tr>";
	echo "<th>".t("Tapvm")."</th>";
	echo "<th>".t("Kapvm")."</th>";
	echo "<th>".t("Er�pvm")."</th>";
	echo "<th>".t("Nimi")."</th>";
	echo "<th>".t("Postitp")."</th>";
	echo "<th>".t("Laskunro")."</th>";
	echo "<th>".t("Yhti�n valuutassa")."</th>";
	echo "<th>".t("Laskun valuutassa")."</th>";
	echo "<th>".t("Kustp")."</th>";
	echo "<th>".t("EBID")."</th>";
	echo "<th>".t("Siirr�")."</th>";
	echo "</tr>";

	while ($trow = mysql_fetch_array ($result)) {

		echo "<tr class='aktiivi'>";

		if ($trow['alatila'] == 'H') {
			$erotin = 'th';
	 	}
		else {
			$erotin = 'td';
		}

		echo "<$erotin>".tv1dateconv($trow["tapvm"])."</$erotin>";

		if ($trow["kapvm"] != "0000-00-00") {
			echo "<$erotin>".tv1dateconv($trow["kapvm"])."</$erotin>";
		}
		else {
			echo "<$erotin></$erotin>";
		}

		echo "<$erotin>".tv1dateconv($trow["erpcm"])."</$erotin>";
		echo "<$erotin>$trow[nimi]</$erotin>";
		echo "<$erotin>$trow[postitp]</$erotin>";
		echo "<$erotin>$trow[laskunro]</$erotin>";
		echo "<$erotin style='text-align: right;'>$trow[kotisumma] $yhtiorow[valkoodi]</$erotin>";
		echo "<$erotin style='text-align: right;'>$trow[summa] $trow[valkoodi]</$erotin>";

		// Selvitet��n laskun kustannuspaikat
		$kustpq = "	SELECT tiliointi.kustp, kustannuspaikka.nimi
					FROM tiliointi
					JOIN kustannuspaikka ON (kustannuspaikka.yhtio = tiliointi.yhtio AND kustannuspaikka.tunnus = tiliointi.kustp)
					WHERE tiliointi.yhtio = '$kukarow[yhtio]'
					AND tiliointi.ltunnus = '$trow[tunnus]'
					and tiliointi.kustp != 0
					and tiliointi.korjattu = ''
					ORDER BY tiliointi.tunnus LIMIT 1";
		$kustpres = pupe_query($kustpq);

		$kustannuspaikan_nimi = "";

		if (mysql_num_rows($kustpres) == 1) {
			$kustprivi = mysql_fetch_assoc($kustpres);
			$kustannuspaikan_nimi = $kustprivi["nimi"];
		}

		echo "<td>$kustannuspaikan_nimi</td>";

		// tehd��n lasku linkki
		echo "<$erotin>". ebid($trow['tunnus']) ."</$erotin>";

		echo "	<form action = 'raportit.php?toim=hyvaksynta&tee=T&kuka=$kuka' method = 'post'>
				<input type = 'hidden' name = 'mika' value = '$trow[tunnus]'>";
		echo "	<$erotin>
				$ulos
				<input type = 'submit' value = '".t("Siirr�")."'>
				</$erotin>
				</form></tr>";

	}
	echo "</table><br>";
}

?>