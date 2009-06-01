<?php

require('inc/parametrit.inc');

if ($tee == "lataa_tiedosto") {
	echo file_get_contents("/tmp/".$tmpfilenimi);
	exit;
}

echo "<font class='head'>".t("Viranomaisilmoitukset")."</font><hr><br><br>";

if ($tee == "VSRALVKK") {
	include ('raportit/alv_laskelma.php');
	alvlaskelma($kk,$vv);
}

if ($tee == "VSRALVYV") {

	echo "<font class='message'></font>".t("Arvonlisäveron yhteenvetoilmoitus kaudelta").":";

	//	Haetaan alkupiste
	$query = "SELECT ((year(now())-year(min(tilikausi_alku)))*4), quarter(now()) from tilikaudet where tilikausi_alku != '0000-00-00' and yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);
	$row = mysql_fetch_array($result);

	$kausia = $row[0]+$row[1]+1;
	$kvarttaali = $row[1];
	$vuosi = date("Y");

	//	Ei näytetä ihan kaikeka
	if ($kausia > 10) $kausia = 10;

	echo "<form enctype='multipart/form-data' action='$PHP_SELF' method='post'>
			<input type='hidden' name='tee' value='$tee'>
			<select name='kohdekausi' onchange='submit();'>
				<option value = ''>".t('Valitse kohdekausi')."</option>";

	for ($i=1; $i<$kausia; $i++) {
		
		if ($kohdekausi == $kvarttaali."/".$vuosi) {
			$sel = "SELECTED";
		}
		else {
			$sel = "";
		}
		
		echo "<option value='$kvarttaali/$vuosi' $sel>$kvarttaali/$vuosi</option>";

		if ($kvarttaali == 1) {
			$kvarttaali = 4;
			$vuosi--;
		}
		else {
			$kvarttaali--;
		}

	}
	echo "</select></form>";
	
	
	if (strtoupper($yhtiorow["maa"])== 'FI') {
		//muutetaan ytunnus takas oikean näköseks
		$ytunpit = 8-strlen($yhtiorow["ytunnus"]);

		if ($ytunpit > 0) {
			$uytunnus = $yhtiorow["ytunnus"];
			while ($ytunpit > 0) {
			    $uytunnus = "0".$uytunnus; $ytunpit--;
			}
		}
		else {
			$uytunnus = $yhtiorow["ytunnus"];
		}

		$uytunnus = substr($uytunnus,0,7)."-".substr($uytunnus,7,1);
	}
	else {
		$uytunnus = $yhtiorow["ytunnus"];
	}

	if ($kohdekausi != "") {

		list($kvarttaali,$vuosi) = explode("/", $kohdekausi);

		switch ($kvarttaali) {
			case 1:
				$alkupvm = "$vuosi-01-01";
				$loppupvm = "$vuosi-04-01";
				break;
			case 2:
				$alkupvm = "$vuosi-04-01";
				$loppupvm = "$vuosi-07-01";
				break;
			case 3:
				$alkupvm = "$vuosi-07-01";
				$loppupvm = "$vuosi-10-01";
				break;
			case 4:
				$alkupvm = "$vuosi-10-01";
				$loppupvm = ($vuosi+1)."-01-01";
				break;
			default:
				die("Kohdekausi on väärä!!!");
		}
		
		echo "</font><br><hr>";

		if ($ytunnus != "") {
			//	Onko syötetty maa oikea
			$query = "SELECT distinct(koodi) from maat where koodi = '$maa'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 1) {
				$query = "	UPDATE asiakas SET maa = '$maa'
							WHERE yhtio = '$kukarow[yhtio]'and ytunnus='$ytunnus'";
				$result = mysql_query($query) or pupe_error($query);
				echo "<font class='message'>".t("Korjattiin asiakkaan")." '$ytunnus' ".t("maaksi")." '$maa'</font><br>";
			}
			else {
				echo "<font class='error'>".t("Syötetty maa on väärin")."</font><br>";
			}
			
		}

		$query = "SELECT group_concat(distinct(koodi) SEPARATOR '\',\'') from maat where eu != '' and koodi != 'FI'";
		$result = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($result);
		$eumaat = $row[0];

		$query = "	SELECT
		 			tuote.tuotetyyppi,
					if(tuote.tuotetyyppi in ('','R'), 'JOO', 'EI') tav_pal,
					lasku.ytunnus, 
					asiakas.nimi, 
					if(lasku.maa='', asiakas.maa, lasku.maa) as maa,					
					if(lasku.maa='','X','') asiakkaan_maa,				
					round(sum(rivihinta),2) summa, 
					round(sum(rivihinta)*100,0) arvo, 
					count(distinct(lasku.tunnus)) laskuja										
					FROM lasku USE INDEX (yhtio_tila_tapvm)
					JOIN tilausrivi USE INDEX (uusiotunnus_index) ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus)
					JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.tuoteno != '$yhtiorow[ennakkomaksu_tuotenumero]')
					LEFT JOIN asiakas ON (asiakas.yhtio = lasku.yhtio and lasku.liitostunnus = asiakas.tunnus)
					WHERE lasku.yhtio = '$kukarow[yhtio]' 
					and lasku.tila = 'U'
					and lasku.tapvm >= '$alkupvm' 
					and lasku.tapvm < '$loppupvm'
					and lasku.vienti = 'E'
					GROUP BY 1,2,3,4,5,6
					ORDER BY tav_pal DESC, tuote.tuotetyyppi, lasku.ytunnus, asiakas.nimi ";
		$result = mysql_query($query) or pupe_error($query);

		$ok=0;

		if (mysql_num_rows($result) > 0) {

			$arvo 		= 0;
			$summa_tav	= 0;
			$summa_pal	= 0;
			
			$osatiedot 	= "";
			$i			= 0;
			$edtav_pal	= "XXX";
			
			echo "<table>";
			
			$ttyyppi = array('A' => t("Päiväraha"), 'B' => t("Muu kulu"), ''  => t("Normaali / Valmiste"), 'R' => t("Raaka-aine"), 'K' => t("Palvelu"), 'M' => t("Muu/Informatiivinen"));
			
			while ($row = mysql_fetch_array($result)) {
				
				if ($row["tav_pal"] != $edtav_pal or $edtav_pal == "XXX") {
					
					if ($edtav_pal != "XXX") echo "<tr><th colspan='4'></th><td class='tumma' align='right'>".sprintf("%.2f", $summa_tav)."</th><th></th></tr>";
					
					if ($edtav_pal != "XXX") {
						echo "<tr><td class='back' colspan='6'><br><br><br>Palvelutuotteet (ei ilmoiteta verottajalle):</td></tr>";
					}
					
					echo "<tr><th>".t("Tuotetyyppi")."</th><th>".t("Maatunnus")."</th><th>".t("Ytunnus")."</th><th>".t("Asiakas")."</th><th>".t("Arvo")."</th><th>".t("Laskuja")."</th></tr>";
				}
				
				$edtav_pal = $row["tav_pal"];
				
				if ($row["maa"] == "") {
					$query = "	SELECT distinct koodi, nimi
								FROM maat
								WHERE nimi != ''
								ORDER BY koodi";
					$vresult = mysql_query($query) or pupe_error($query);
					$ulos = "<select name='maa'>";

					$ulos .= "<option value=''>".t("Valitse maa")."</option>";

					while ($vrow=mysql_fetch_array($vresult)) {

						$ulos .= "<option value = '".strtoupper($vrow[0])."'>".t($vrow[1])."</option>";
					}

					$ulos .= "</select>";

					echo "<tr><form enctype='multipart/form-data' action='$PHP_SELF' method='post'>
								<input type='hidden' name='tee' value='$tee'>
								<input type='hidden' name='ytunnus' value='$row[ytunnus]'>
								<input type='hidden' name='kohdekausi' value='$kohdekausi'>
								<td>$ulos</td><td>$row[ytunnus]</td><td>$row[nimi]</td><td>$row[summa]</td><td>$row[laskuja]</td>
								<td class='back'>
								<font class='error'>".t("VIRHE!!! asiakkaan maa puuttuu!!")."</font><br>
								<input type='submit' name='tallenna' value='".t("Korjaa asiakkaan maa")."'>
								</td></form></tr>";
					$ok = 1;
				}
				elseif($row["maa"] != "" and $row["asiakkaan_maa"] == "X") {
					echo "<tr><td>".$ttyyppi[strtoupper($row["tuotetyyppi"])]."</td><td>$row[maa]</td><td>$row[ytunnus]</td><td>$row[nimi]</td><td align='right'>$row[summa]</td><td align='right'>$row[laskuja]</td><td class='back'><font class='info'>".t("HUOM! Maa haettu asiakkaan tiedoista")."</font></td></tr>";
				}
				else {
					echo "<tr><td>".$ttyyppi[strtoupper($row["tuotetyyppi"])]."</td><td>$row[maa]</td><td>$row[ytunnus]</td><td>$row[nimi]</td><td align='right'>$row[summa]</td><td align='right'>$row[laskuja]</td></tr>";
				}

				if ($row["maa"] != "") {										
					if ($row["tav_pal"] == "JOO") {
						$i++;
						
						$arvo+=$row["arvo"];
						$summa_tav+=$row["summa"];
						
						$osatiedot .= "102:$row[maa]\n";
						$osatiedot .= "103:".sprintf("%012.12s",str_replace(array($row["maa"],"-","_"), "", $row["ytunnus"]))."\n";
						$osatiedot .= "210:$row[arvo]\n";
						$osatiedot .= "104:\n";
						$osatiedot .= "009:$i\n";
					}
					else {
						$summa_pal+=$row["summa"];
					}						
				}
			}
			
			echo "<tr><th colspan='4'></th><td class='tumma' align='right'>".sprintf("%.2f", $summa_pal)."</th><th></th></tr>";
			echo "<tr><th colspan='4'></th><td class='tumma' align='right'>".sprintf("%.2f", ($summa_tav+$summa_pal))."</th><th></th></tr>";
			echo "</table>";

			if ($ok == 0) {

				$file = "000:$tee\n";
				$file .= "100:".date("dmY")."\n";
				$file .= "105:E03\n";
				$file .= "010:$uytunnus\n";
				$file .= "053:$kohdekausi\n";
				$file .= "098:1\n";
				$file .= "101:$arvo\n";
				$file .= "001:$i\n";
				$file .= $osatiedot;
				$file .= "999:1\n";

				$filenimi = "VSRALVYV-$kvarttaali$vuosi	".date("dmy-His").".txt";
				$fh = fopen("/tmp/".$filenimi, "w");
				
				if (fwrite($fh, $file) === FALSE) die("Kirjoitus epäonnistui $filenimi");
				fclose($fh);

				echo "<br><form enctype='multipart/form-data' action='$PHP_SELF' method='post'>
						<input type='hidden' name='tee' value='lataa_tiedosto'>
						<input type='hidden' name='kausi' value='$kausi'>
						<input type='hidden' name='lataa_tiedosto' value='1'>
						<input type='hidden' name='kaunisnimi' value='".t("Arvonlisaveron_yhteenvetoilmoitus-$kvarttaali$vuosi")."'>
						<input type='hidden' name='tmpfilenimi' value='$filenimi'>
						<input type='submit' name='tallenna' value='".t("Tallenna tiedosto")."'></form>";
			}
			else {
				echo "<br><font class='error'>".t("Korjaa virheet maat ennen ilmoituksen lähettämistä")."</font>";
			}
		}
		else {
			echo "<br><font class='message'>".t("Ei aineistoa valitulla kaudella")."</font>";
		}
	}
}

if ($tee == "") {
	echo "<form action='viranomaisilmoitukset.php' method='post'>
			<select name='tee' onchange='submit();'>
				<option value = ''>".t('Valitse viranomaisilmoitus')."</option>
				<option value = 'VSRALVYV'>".t("Arvonlisäveron yhteenvetoilmoitus")."</option>
				<option value = 'VSRALVKK'>".t("Arvonlisäveroilmoitus")."</option>
			</select></form>";
}

require ("inc/footer.inc");

?>