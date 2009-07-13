<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Tervetuloa pupesoft-j�rjestelm��n")."</font><hr><br>";

if (!isset($tee) or $tee == '') {

	if (file_exists("tervetuloa_".$kukarow["yhtio"].".inc")) {
		require("tervetuloa_".$kukarow["yhtio"].".inc");
	}

	echo "<table>";
	echo "<tr>";

	///* Uutiset *///
	echo "<tr><td class='back' valign='top'>";
	$toim = "";
	require("uutiset.php");
	echo "</td>";

	///* Hyv�ksytt�v�t laskut*///
	echo "<td class='back' width='10'></td>";
	echo "<td class='back' valign='top' width='450'>";

	// haetaan kaikki yritykset, jonne t�m� k�ytt�j� p��see
	$query  = "	SELECT distinct yhtio.yhtio, yhtio.nimi 
				from kuka
				join yhtio using (yhtio)
				where kuka='$kukarow[kuka]'";
	$kukres = mysql_query($query) or pupe_error($query);

	while ($kukrow = mysql_fetch_array($kukres)) {

		$query = "	SELECT count(*) FROM lasku
					WHERE hyvaksyja_nyt = '$kukarow[kuka]' and yhtio = '$kukrow[yhtio]' and alatila = 'H' and tila!='D'
					ORDER BY erpcm";
		$result = mysql_query($query) or pupe_error($query);
		$piilorow = mysql_fetch_array ($result);

		$query = "	SELECT tapvm, erpcm, ytunnus, nimi, round(summa * vienti_kurssi, 2) 'kotisumma', if(erpcm<=now(), 1, 0) wanha
					FROM lasku
					WHERE hyvaksyja_nyt = '$kukarow[kuka]' and yhtio = '$kukrow[yhtio]' and alatila!='H' and tila!='D'
					ORDER BY erpcm";
		$result = mysql_query($query) or pupe_error($query);

		if ((mysql_num_rows($result) > 0) or ($piilorow[0] > 0)) {

			echo "<table width='100%'>";

			// ei n�ytet� suotta firman nime�, jos k�ytt�j� kuuluu vaan yhteen firmaan
			if (mysql_num_rows($kukres) == 1) $kukrow["nimi"] = "";

			echo "<tr><td colspan='".mysql_num_fields($result)."' class='back'><font class='head'>".t("Hyv�ksytt�v�t laskusi")." $kukrow[nimi]</font><hr></td></tr>";

			if ($piilorow[0] > 0)
				echo "<tr><td colspan='".mysql_num_fields($result)."' class='back'>". sprintf(t('Sinulla on %d pys�ytetty� laskua'), $piilorow[0]) . "</tr>";

			if (mysql_num_rows($result) > 0) {
				
				echo "<th>" . t("Er�pvm")."</th>";
				echo "<th>" . t("Ytunnus")."</th>";
				echo "<th>" . t("Nimi")."</th>";
				echo "<th>" . t("Summa")."</th>";
				

				while ($trow=mysql_fetch_array ($result)) {
					echo "<tr>";
					
					if($trow["wanha"] == 1) {
						$style = "error'"; 
					}
					else {
						$style = "";
					}
					
					echo "<td><font class='$style'>".tv1dateconv($trow["erpcm"])."</font></td>";
					echo "<td><font class='$style'>$trow[ytunnus]</font></td>";
					
					
					if ($kukrow["yhtio"] == $kukarow["yhtio"]) {
						echo "<td><a href='hyvak.php'><font class='$style'>$trow[nimi]</font></a></td>";
					}
					else {
						echo "<td><font class='$style'>$trow[nimi]</font></td>";
					}
					
					echo "<td align='right'><font class='$style'>$trow[kotisumma]</font></td>";
										
					echo "</tr>";
				}
			}
			echo "</table><br><br>";
		}
		
		$query = "	SELECT tunnus, nimi, luontiaika
					FROM lasku use index (tila_index)
					WHERE yhtio = '$kukrow[yhtio]' 
					and myyja='$kukarow[tunnus]'
					and tila in ('N','L') 
					and alatila != 'X' 
					and chn = '999'";
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result) > 0) {
			echo "<table width='100%'>";

			// ei n�ytet� suotta firman nime�, jos k�ytt�j� kuuluu vaan yhteen firmaan
			if (mysql_num_rows($kukres) == 1) $kukrow["nimi"] = "";

			echo "<tr><td colspan='".mysql_num_fields($result)."' class='back'><font class='head'>".t("Laskutuskiellossa olevat laskusi")." $kukrow[nimi]</font><hr></td></tr>";
			
			if (mysql_num_rows($result) > 0) {
				for ($i = 0; $i < mysql_num_fields($result); $i++) {
					echo "<th>" . t(mysql_field_name($result,$i))."</th>";
				}
				while ($trow=mysql_fetch_array ($result)) {
					echo "<tr>";
					for ($i=0; $i<mysql_num_fields($result); $i++) {
												
						if (mysql_field_name($result,$i) == "tunnus" and $kukrow["yhtio"] == $kukarow["yhtio"]) {
							echo "<td><a href='muokkaatilaus.php?toim=LASKUTUSKIELTO&etsi=".$kukarow["kuka"]."'>$trow[$i]</a></td>";
						}
						else {
							echo "<td>$trow[$i]</td>";
						}
					}
					echo "</tr>";
				}
			}
			echo "</table><br><br>";
		}

	}
	
	///* MUISTUTUKSET *///
	//listataan paivan muistutukset
	$query = "	SELECT kalenteri.tunnus tunnus, left(pvmalku,10) Muistutukset, asiakas.nimi Asiakas, yhteyshenkilo.nimi Yhteyshenkilo, 
				kalenteri.kentta01 Kommentit, kalenteri.tapa Tapa
				FROM kalenteri
				LEFT JOIN yhteyshenkilo ON kalenteri.henkilo=yhteyshenkilo.tunnus and yhteyshenkilo.yhtio=kalenteri.yhtio
				LEFT JOIN asiakas ON asiakas.tunnus=kalenteri.liitostunnus and asiakas.yhtio=kalenteri.yhtio
				WHERE kalenteri.kuka = '$kukarow[kuka]'
				and kalenteri.tyyppi = 'Muistutus'
				and kalenteri.kuittaus = 'K'
				and kalenteri.yhtio = '$kukarow[yhtio]' 
				ORDER BY kalenteri.pvmalku desc";
	$result = mysql_query($query) or pupe_error($query);
	
	if (mysql_num_rows($result) > 0) {
        echo "<table width='100%'>";
        echo "<tr>";
       	echo "<th colspan='4'>".t("Muistutukset")."</th>";
        echo "</tr>";


        while ($prow = mysql_fetch_array ($result)) {
                echo "<tr>";
               	echo "<td nowrap><a href='".$palvelin2."crm/kuittaamattomat.php?tee=A&kaletunnus=$prow[tunnus]&kuka=$prow[kuka]'>".tv1dateconv($prow["Muistutukset"])."</a></td>";
                echo "<td>$prow[Asiakas]<br>$prow[Yhteyshenkilo]</td>";
                echo "<td>$prow[Kommentit]</td>";
                echo "<td nowrap>$prow[Tapa]</td>";
                echo "</tr>";
        }
        echo "</table><br>";
}

	///* RUOKALISTA *///
	$query = "	SELECT *, kalenteri.tunnus tun, year(pvmalku) vva, month(pvmalku) kka, dayofmonth(pvmalku) ppa, year(pvmloppu) vvl, month(pvmloppu) kkl, dayofmonth(pvmloppu) ppl
				from kalenteri
				left join kuka on kuka.yhtio=kalenteri.yhtio and kuka.kuka=kalenteri.kuka
				where tyyppi='ruokalista'
				and kalenteri.yhtio='$kukarow[yhtio]'
				and pvmalku<=now()
				and pvmloppu>=now()
				LIMIT 1";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {
		while($uutinen = mysql_fetch_array($result)) {
			echo "
				<table width='100%'>
				<tr><td colspan='5' class='back'><font class='head'>".t("Ruokalista")." $uutinen[ppa].$uutinen[kka].-$uutinen[ppl].$uutinen[kkl].$uutinen[vvl]</font><hr></td></tr>
				<tr><th>".t("Maanantai")."</th></tr>
				<tr><td valign='top'>$uutinen[kentta01]</td></tr>
				<tr><th>".t("Tiistai")."</th></tr>
				<tr><td valign='top'>$uutinen[kentta02]</td></tr>
				<tr><th>".t("Keskiviikko")."</th></tr>
				<tr><td valign='top'>$uutinen[kentta03]</td></tr>
				<tr><th>".t("Torstai")."</th></tr>
				<tr><td valign='top'>$uutinen[kentta04]</td></tr>
				<tr><th>".t("Perjantai")."</th></tr>
				<tr><td valign='top'>$uutinen[kentta05]</td></tr>
				</table>";
		}
	}

	echo "</td>";
	echo "</tr>";
	echo "</table>";
}

require("inc/footer.inc");

?>
