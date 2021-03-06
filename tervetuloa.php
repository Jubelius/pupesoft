<?php

$pupe_DataTables = array("etusivun_tyomaarays");

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
					echo "<td><font class='$style'>".tarkistahetu($trow["ytunnus"])."</font></td>";


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
					and myyja = '$kukarow[tunnus]'
					and tila in ('N','L')
					and alatila != 'X'
					and chn = '999'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			echo "<table width='100%'>";

			// ei n�ytet� suotta firman nime�, jos k�ytt�j� kuuluu vaan yhteen firmaan
			if (mysql_num_rows($kukres) == 1) $kukrow["nimi"] = "";

			echo "<tr>";
			echo "<td colspan='3' class='back'><font class='head'>".t("Laskutuskiellossa olevat laskusi")." $kukrow[nimi]</font><hr></td>";
			echo "</tr>";

			echo "<tr>";
			echo "<th>".t("tunnus")."</a></th>";
			echo "<th>".t("nimi")."</th>";
			echo "<th>".t("luontiaika")."</th>";
			echo "</tr>";

			while ($trow = mysql_fetch_array($result)) {
				echo "<tr>";
				echo "<td><a href='muokkaatilaus.php?toim=LASKUTUSKIELTO&etsi=$trow[tunnus]'>$trow[tunnus]</a></td>";
				echo "<td>$trow[nimi]</td>";
				echo "<td>".tv1dateconv($trow["luontiaika"])."</td>";
				echo "</tr>";
			}

			echo "</table>";
			echo "<br><br>";
		}

	}

	///* MUISTUTUKSET *///
	//listataan paivan muistutukset

	$selectlisa = $yhtiorow['tyomaarays_asennuskalenteri_muistutus'] == 'K' ? ", kalenteri.pvmloppu, kalenteri.kentta02 " : '';

	$query = "	SELECT kalenteri.tunnus tunnus, left(pvmalku,10) Muistutukset, asiakas.nimi Asiakas, yhteyshenkilo.nimi Yhteyshenkilo,
				kalenteri.kentta01 Kommentit, kalenteri.tapa Tapa $selectlisa
				FROM kalenteri
				LEFT JOIN yhteyshenkilo ON kalenteri.henkilo=yhteyshenkilo.tunnus and yhteyshenkilo.yhtio=kalenteri.yhtio and yhteyshenkilo.tyyppi = 'A'
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

			if ($yhtiorow['tyomaarays_asennuskalenteri_muistutus'] == 'K' and trim($prow['kentta02']) != '' and is_numeric($prow['kentta02']) and $prow['Tapa'] == "Asentajan kuittaus") {

				if ($prow['pvmloppu'] > date("Y-m-d H:i:s")) {
					continue;
				}

				$query = "	SELECT *
							FROM kalenteri
							WHERE tyyppi = 'kalenteri'
							AND pvmalku like '$prow[Muistutukset]%'
							AND kentta02 = '$prow[kentta02]'";
				$asentajien_merkkaukset_res = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($asentajien_merkkaukset_res) > 0) {

					$query = "	UPDATE kalenteri SET
								kuittaus = ''
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus = '$prow[tunnus]'";
					$muistutus_kuittaus_res = mysql_query($query) or pupe_error($query);

					continue;
				}
			}

			echo "<tr>";
			echo "<td nowrap><a href='".$palvelin2."crm/kuittaamattomat.php?tee=A&kaletunnus=$prow[tunnus]&kuka=$prow[kuka]'>".tv1dateconv($prow["Muistutukset"])."</a></td>";
			echo "<td>$prow[Asiakas]<br>$prow[Yhteyshenkilo]</td>";
			echo "<td>$prow[Kommentit]</td>";
			echo "<td nowrap>$prow[Tapa]</td>";
			echo "</tr>";
        }
        echo "</table><br>";
	}

	// N�ytet��n k�ytt�j�kohtaiset ty�m��r�ykset
	$tyojonosql = "	SELECT lasku.tunnus,
					lasku.nimi,
					lasku.toimaika,
					a2.selitetark tyostatus,
					a2.selitetark_2 tyostatusvari,
					a5.selitetark tyom_prioriteetti
					FROM lasku
					JOIN tyomaarays ON (tyomaarays.yhtio = lasku.yhtio AND tyomaarays.otunnus = lasku.tunnus AND tyomaarays.tyojono != '' AND tyomaarays.suorittaja = '{$kukarow["kuka"]}')
					LEFT JOIN avainsana a2 ON (a2.yhtio=tyomaarays.yhtio and a2.laji='TYOM_TYOSTATUS' and a2.selite=tyomaarays.tyostatus)
					LEFT JOIN avainsana a5 ON (a5.yhtio=tyomaarays.yhtio and a5.laji='TYOM_PRIORIT' and a5.selite=tyomaarays.prioriteetti)
					WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
					AND lasku.tila in ('A','L','N','S','C')
					AND lasku.alatila != 'X'
					ORDER BY ifnull(a5.jarjestys, 9999), ifnull(a2.jarjestys, 9999), lasku.toimaika asc, a2.selitetark";
	$tyoresult = pupe_query($tyojonosql);

	if (mysql_num_rows($tyoresult) > 0) {

		pupe_DataTables(array(array($pupe_DataTables[0], 5, 5)));

		$padding_muuttuja = " style='padding-right:15px;'";

		echo "<table class='display dataTable' id='$pupe_DataTables[0]'>";
		echo "<thead>";

		echo "<tr>";
		echo "<td colspan='5' class='back'><font class='head'>".t("Omat Ty�m��r�ykset")."</font><hr></td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th $padding_muuttuja>".t("Ty�numero")."</th>";
		echo "<th $padding_muuttuja>".t("Prioriteetti")."</th>";
		echo "<th $padding_muuttuja>".t("Status")."</th>";
		echo "<th $padding_muuttuja>".t("Asiakas")."</th>";
		echo "<th $padding_muuttuja>".t("P�iv�m��r�")."</th>";
		echo "</tr>";

		echo "</thead>";
		echo "<tbody>";

	 	while ($tyorow = mysql_fetch_array($tyoresult)) {
			// Laitetetaan taustav�ri jos sellainen on sy�tetty
			$varilisa = ($tyorow["tyostatusvari"] != "") ? " style='background-color: {$tyorow["tyostatusvari"]};'" : "";

			echo "<tr $varilisa>";
			echo "<td><a href='{$palvelin2}tilauskasittely/tilaus_myynti.php?toim=TYOMAARAYS&tee=AKTIVOI&from=LASKUTATILAUS&tilausnumero={$tyorow['tunnus']}'>".$tyorow['tunnus']."</a></td>";
			echo "<td>{$tyorow["tyom_prioriteetti"]}</td>";
			echo "<td>{$tyorow["tyostatus"]}</td>";
			echo "<td>{$tyorow["nimi"]}</td>";
			echo "<td>{$tyorow["toimaika"]}</td>";
			echo "</tr>";
		}
		echo "</tbody>";
		echo "</table><br>";
	}

	if (tarkista_oikeus("alv_laskelma_uusi.php")) {

		$ulos = '';

		// Katsotaan pienin tilikausi, josta l�het��n esitt�m��n
		$min_query = "	SELECT date_format(ifnull(min(tilikausi_alku), '9999-01-01'), '%Y%m') min
						FROM tilikaudet
						WHERE yhtio = '{$kukarow["yhtio"]}'
						AND tilikausi_alku >= '2010-11-01'
						AND tilikausi_alku >= date_sub('{$yhtiorow['tilikausi_alku']}', interval 1 month)";
		$min_result = pupe_query($min_query);
		$min_row = mysql_fetch_assoc($min_result);

		for ($i = $min_row['min']; $i <= date("Ym"); $i++) {

			if (substr($i, -2) == 13) {
				$i += 88;
			}

			$alvpvm = date("Y-m-d", mktime(0, 0, 0, (substr($i,4)+1), 0, substr($i, 0, 4)));

			$query = "	SELECT lasku.tunnus
						FROM lasku
						JOIN tiliointi ON (tiliointi.yhtio = lasku.yhtio AND tiliointi.ltunnus = lasku.tunnus)
						WHERE lasku.yhtio = '{$kukarow['yhtio']}'
						AND lasku.tapvm = '$alvpvm'
						AND lasku.tila = 'X'
						AND lasku.nimi = 'ALVTOSITEMAKSUUN$alvpvm'
						LIMIT 1";
			$tositelinkki_result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($tositelinkki_result) == 0) {

				list($vv,$kk,$pp) = explode("-", $alvpvm);

				$ulos .= "<tr><td><a href='{$palvelin2}raportit/alv_laskelma_uusi.php?kk=$kk&vv=$vv'>".t("ALV")." $kk $vv ".t("tosite tekem�tt�")."</a></td></tr>";
			}
		}

		if (trim($ulos) != '') {
			echo "<table>";
			echo "<tr><th>",t("ALV-ilmoitus"),"</th></tr>";
			echo $ulos;
			echo "</table><br />";
		}
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
