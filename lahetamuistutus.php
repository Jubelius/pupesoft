<?php
	
	if (strpos($_SERVER['SCRIPT_NAME'], "lahetamuistutus.php")  !== FALSE) {	
		require "inc/parametrit.inc";
	}

	echo "<font class='head'>".t("Lähetetään käyttäjille muistutukset hyväksynnästä")."</font><hr>";

	$query = "	SELECT concat_ws(' ',lasku.nimi, nimitark) nimi, tapvm, erpcm, round(summa * valuu.kurssi,2) summa, kuka.eposti
				FROM lasku, valuu, kuka
				WHERE lasku.yhtio='$kukarow[yhtio]' and valuu.yhtio=lasku.yhtio and
				kuka.yhtio=lasku.yhtio and lasku.valkoodi=valuu.nimi and
				lasku.hyvaksyja_nyt=kuka.kuka and kuka.eposti <> '' and
				lasku.tila = 'H'
				ORDER BY kuka.eposti, tapvm";
	$result = mysql_query($query) or pupe_error($query);

	while ($trow=mysql_fetch_array($result)) {
		$laskuja++;
		if ($trow['eposti'] != $veposti) {
			if ($veposti != '') {
				$meili = t("Sinulla on hyväksyttävänä seuraavat laskut").":\n\n" . $meili;
				$tulos = mail($veposti, t("Muistutus laskujen hyväksynnästä")."", $meili, "From: " . $yhtiorow["nimi"] . "<" . $yhtiorow["admin_email"] . ">\nReply-To: " . $yhtiorow["nimi"] . "<" . $yhtiorow["admin_email"] . ">\n", "-f $yhtiorow[postittaja_email]");
				$maara++;
			}
			$meili = '';
			$veposti = $trow['eposti'];
		}

		$meili .= "Laskuttaja: " . $trow['nimi'] . "\n";
		$meili .= "Laskutuspäivä: " . $trow['tapvm'] . "\n";
		$meili .= "Eräpäivä: " . $trow['erpcm'] . "\n";
		$meili .= "Summa: " .$yhtiorow["valkoodi"]." ".$trow['summa'] . "\n\n";
	}
	if ($meili != '') {
		$meili = t("Sinulla on hyväksyttävänä seuraavat laskut").":\n\n" . $meili;
		$tulos = mail($veposti, t("Muistutus laskujen hyväksynnästä")."", $meili, "From: " . $yhtiorow["nimi"] . "<" . $yhtiorow["admin_email"]. ">\nReply-To: " . $yhtiorow["nimi"] . "<" . $yhtiorow["admin_email"] . ">\n", "-f $yhtiorow[postittaja_email]");
		$maara++;
	}
	echo "<font class='message'>".t("Lähetettiin")." $maara ".t("muistutusta. Muistutettuja laskuja")." $laskuja</font><hr>";
	
?>
