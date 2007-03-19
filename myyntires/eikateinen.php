<?php

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Lasku ei ollutkaan k�teist�")."</font><hr>";

if ((int) $maksuehto != 0 and (int) $tunnus != 0) {
	// tutkaillaan maksuehtoa
	$query = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$maksuehto'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Maksuehto katosi")."!</font><br><br>";
		$laskuno   = 0;
		$tunnus    = 0;
		$maksuehto = 0;
	}
	else {
		$mehtorow = mysql_fetch_array($result);
	}
	
	// tutkaillaan laskua
	$query = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Lasku katosi")."!</font><br><br>";
		$laskuno   = 0;
		$tunnus    = 0;
		$maksuehto = 0;
	}
	else {
		$laskurow = mysql_fetch_array($result);
	}
}

if ((int) $maksuehto != 0 and (int) $tunnus != 0) {

	// korjaillaan er�p�iv�t ja kassa-alet
	if ($mehtorow['abs_pvm'] == '0000-00-00') {
		$erapvm = "adddate('$laskurow[tapvm]', interval $mehtorow[rel_pvm] day)";
	}
	else {
		$erapvm = "'$mehtorow[abs_pvm]'";
	}

	if ($mehtorow['kassa_teksti'] != '') {
		if ($mehtorow['kassa_abspvm'] == '0000-00-00') {
			$kassa_erapvm = "adddate('$laskurow[tapvm]', interval $mehtorow[kassa_relpvm] day)";
		}
		else {
			$kassa_erapvm = "'$mehtorow[kassa_abspvm]'";
		}
		$kassa_loppusumma = round($laskurow['summa']*$mehtorow['kassa_alepros']/100, 2);
	}
	else {
		$kassa_erapvm     = "''";
		$kassa_loppusumma = "";
	}

	// p�ivitet��n lasku	
	$query = "	update lasku set 
				mapvm     ='', 
				maksuehto ='$maksuehto',
				erpcm     = $erapvm,
				kapvm     = $kassa_erapvm,
				kasumma   ='$kassa_loppusumma'
				where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_affected_rows() > 0) {
		echo "<font class='message'>".t("Muutettin laskun")." $laskurow[laskunro] ".t("maksuehdoksi")." $mehtorow[teksti] $mehtorow[kassa_teksti] ".t("ja merkattiin maksu avoimeksi").".</font><br>";	
	}
	else {
		echo "<font class='error'>".t("Laskua")." $laskurow[laskunro] ".t("ei pystytty muuttamaan")."!</font><br>";	
	}

	// tehd��n kirjanpitomuutokset
	$query = "update tiliointi set tilino='$yhtiorow[myyntisaamiset]', summa='$laskurow[summa]' where yhtio='$kukarow[yhtio]' and ltunnus='$tunnus' and tilino='$yhtiorow[kassa]'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_affected_rows() > 0) {
		echo "<font class='message'>".t("Korjattiin kirjanpitoviennit")." (".mysql_affected_rows()." ".t("kpl").").</font><br>";	
	}
	else {
		echo "<font class='error'>".t("Kirjanpitomuutoksia ei osattu tehd�! Korjaa kirjanpito k�sin")."!</font><br>";
	}

	// yliviivataan kassa-aletili�innit
	$query = "update tiliointi set korjattu='X' where yhtio='$kukarow[yhtio]' and ltunnus='$tunnus' and tilino='$yhtiorow[myynninkassaale]'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_affected_rows() > 0) {
		echo "<font class='message'>".t("Poistettiin kassa-alekirjaukset")." (".mysql_affected_rows()." ".t("kpl").").</font><br><br>";
	}

	$laskuno = 0;
}

if ((int) $laskuno != 0) {
	// haetaan lasku. pit�� olla maksettu ja maksuehto k�teinen
	$query = "select *, lasku.tunnus ltunnus 
				from lasku, maksuehto 
				where lasku.yhtio='$kukarow[yhtio]' 
				and lasku.yhtio=maksuehto.yhtio
				and lasku.maksuehto=maksuehto.tunnus
				and lasku.laskunro='$laskuno' 
				and tila='U' 
				and alatila='X' 
				and kateinen!=''";
	$result = mysql_query($query) or pupe_error($query);
	
	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Laskunumerolla")." '$laskuno' ".t("ei l�ydy k�teislaskua")."!</font><br><br>";
		$laskuno = 0;
	}
	else {
		$laskurow = mysql_fetch_array($result);

		echo "<form action='$PHP_SELF' method='post' autocomplete='off'>";
		echo "<input name='tunnus' type='hidden' value='$laskurow[ltunnus]'>";
		
		echo "<table>
			<tr><th>".t("Laskutusosoite")."</th><th>".t("Toimitusosoite")."</th></tr>
			<tr><td>$laskurow[ytunnus]<br> $laskurow[nimi] $laskurow[nimitark]<br> $laskurow[osoite]<br> $laskurow[postino] $laskurow[postitp]</td><td>$laskurow[ytunnus]<br> $laskurow[toim_nimi] $laskurow[toim_nimitark]<br> $laskurow[toim_osoite]<br> $laskurow[toim_postino] $laskurow[toim_postitp]</td></tr>
			<tr><th>".t("Laskunumero")."</th><td>$laskurow[laskunro]</td></tr>
			<tr><th>".t("Laskun summa")."</th><td>$laskurow[summa]</td></tr>
			<tr><th>".t("Laskun summa (veroton)")."</th><td>$laskurow[arvo]</td></tr>
			<tr><th>".t("Maksuehto")."</th><td>$laskurow[teksti]</td></tr>
			<tr><th>".t("Tapahtumap�iv�")."</th><td>$laskurow[tapvm]</td></tr>
			<tr><th>".t("Uusi maksuehto")."</th>
			<td>";

		// haetaan kaikki maksuehdot (paitsi k�teinen)
		$query = "	SELECT maksuehto.tunnus, concat_ws(' ', ".avain('selectcon','MEHTOTXT_').",  ".avain('selectcon2','MEHTOKATXT_').") selite
					FROM maksuehto
					".avain('join','MEHTOTXT_')."
					".avain('join2','MEHTOKATXT_')."
					WHERE maksuehto.yhtio = '$kukarow[yhtio]' and maksuehto.kateinen=''
					ORDER BY maksuehto.jarjestys, maksuehto.teksti";
		$vresult = mysql_query($query) or pupe_error($query);
		
		echo "<select name='maksuehto'>";

		while ($vrow=mysql_fetch_array($vresult)) {
			echo "<option value='$vrow[tunnus]'>$vrow[selite]</option>";
		}
		echo "</select>";
				
		echo "</td>
			</tr>
			</table><br>";

		echo "<input name='subnappi' type='submit' value='".t("Muuta maksuehto")."'></td>";
		echo "</form>";
	}
}


if ($laskuno == 0) {
	echo "<form name='eikat' action='$PHP_SELF' method='post' autocomplete='off'>";
	echo "<table><tr>";
	echo "<th>".t("Sy�t� laskunumero")."</th>";
	echo "<td><input type='text' name='laskuno'></td>";
	echo "<td class='back'><input name='subnappi' type='submit' value='".t("Pist� etsien")."'></td>";
	echo "</tr></table>";
	echo "</form>";
}

// kursorinohjausta
$formi = "eikat";
$kentta = "laskuno";

require ("../inc/footer.inc");

?>