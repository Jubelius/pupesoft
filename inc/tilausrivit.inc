<?php

		$ale_select_lisa = generoi_alekentta_select('erikseen', 'M');

		if ($trow['tila'] == 'U') {
			
			if ($yhtiorow["varaako_jt_saldoa"] != "") {
				$lisavarattu = " + tilausrivi.varattu";
			}
			else {
				$lisavarattu = "";
			}
			
			$query = "	SELECT nimitys, tuoteno, tilkpl tilattu, kpl toimitettu, if(var='J', jt $lisavarattu, 0) j�lkitoimitus, hinta, {$ale_select_lisa} rivihinta, if(alv>=600, '".t("K.V.")."', if(alv>=500, '".t("M.V.")."', alv)) alv, toimaika, kerayspvm
						FROM tilausrivi
						WHERE uusiotunnus = '$tunnus' and yhtio='$kukarow[yhtio]'
						ORDER BY toimaika, tunnus";
		}
		//n�m� ovat ostoreskontran laskuja ja sillon pit�� l�yt�� keikan perusteella rivit
		elseif (in_array($trow['tila'], array('H','Y','M','P','Q')) and (int) $laskrow["laskunro"] > 0) {
			$query = "	SELECT laskunro
						FROM lasku
						WHERE vanhatunnus = '$tunnus' and yhtio='$kukarow[yhtio]' and tila = 'K' limit 1";
			$laskresult = mysql_query($query) or pupe_error($query);
			$laskrow = mysql_fetch_array($laskresult);
			
			$query = "	SELECT nimitys Nimitys, tuoteno Tuotenumero, kpl, yksikko Yksikk�, tilausrivi.hinta Hinta, rivihinta Rivihinta, tilausrivi.laatija Laatija, otunnus Tilaukselta
						FROM lasku, tilausrivi
						WHERE lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.uusiotunnus and lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila = 'K' and lasku.vanhatunnus = 0 and laskunro = '$laskrow[laskunro]'
						ORDER BY tilausrivi.toimaika, tilausrivi.tunnus";
		}
		else {
			
			if ($yhtiorow["varaako_jt_saldoa"] != "") {
				$lisavarattu = " + tilausrivi.varattu";
			}
			else {
				$lisavarattu = "";
			}

			$query_ale_lisa = generoi_alekentta('M');

			$query = "	SELECT nimitys, tuoteno, tilkpl tilattu, varattu toimitettu, if(var='J', jt $lisavarattu, 0) j�lkitoimitus, hinta, {$ale_select_lisa} round((varattu+jt)*hinta * {$query_ale_lisa},'$yhtiorow[hintapyoristys]') rivihinta, if(alv>=600, '".t("K.V.")."', if(alv>=500, '".t("M.V.")."', alv)) alv, toimaika, kerayspvm
						FROM tilausrivi
						WHERE otunnus = '$tunnus' and yhtio='$kukarow[yhtio]'
						ORDER BY toimaika, tunnus";	
		}
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {

			echo "<table>";

			for ($i = 0; $i < mysql_num_fields($result); $i++) {
				echo "<th>" . t(mysql_field_name($result,$i)) ."</th>";
			}
			echo "</tr>";

			while ($trow=mysql_fetch_array ($result)) {
				echo "<tr>";
				for ($i=0; $i<mysql_num_fields($result); $i++) {
					echo "<td>$trow[$i]</td>";
				}
				echo "</tr>";
			}
			echo "</table><br><br>";
		}
		else {
			echo "<br><br><b>".t("Laskulla ei ole (viel�) tuoterivej�")."</b><br><br>";
		}
?>
