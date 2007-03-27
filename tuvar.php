<?php
	require("inc/parametrit.inc");

	echo "<font class='head'>".t("Tuotekysely")."</font><hr>";
		 //syotetaan tuotenumero
		$formi='formi';
		$kentta = 'tuoteno';

		echo "<form action='$PHP_SELF' method='post' name='$formi' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='Z'>";
		echo "<table><tr>";
		echo "<th>".t("Anna tuotenumero").":</th>";
		echo "<td><input type='text' name='tuoteno' value=''></td>";
		echo "<td class='back'><input type='Submit' value='".t("Valitse")."'></td>";
		echo "</tr></table>";
		echo "</form>";

	if ($tee == 'Z') {

		if(substr($tuoteno,0,1) == '*') { // Nyt me selataan
			$tee = 'Y';
			$query = "	SELECT tuoteno, nimitys
						FROM tuote
						WHERE yhtio = '$kukarow[yhtio]' 
						and status NOT IN ('P','X')
						and nimitys like '%" . substr($tuoteno,1) . "%'
						ORDER BY tuoteno";
		}
		if (substr($tuoteno,-1) == '*') {
			$tee = 'Y';
			$query = "	SELECT tuoteno, nimitys
						FROM tuote
						WHERE yhtio = '$kukarow[yhtio]' 
						and status NOT IN ('P','X')
						and tuoteno like '" . substr($tuoteno,0,-1) . "%'
						ORDER BY tuoteno";
		}
		if ($tee == 'Y') {
			$tresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($tresult) >  100) {
				$kentta='tuoteno';
				$varaosavirhe = "".t("Haulla loytyy liikaa tuotteita")."!";
				$tuoteno='';
				$tee = '';
			}
			else {
				if (mysql_num_rows($tresult) == 0) {
					$kentta='tuoteno';
					$varaosavirhe = "".t("Haulla ei loydy tuotteita")."!";
					$tuoteno='';
					$tee = '';
				}
				else {
					//Tehdaan pop-up valmiiksi myohempaa kayttoa varten
					$kentta = 'atil';
					$ulos = "<select name='tuoteno'>";
					while ($trow = mysql_fetch_array ($tresult)) {
						$ulos .= "<option value='$trow[0]'>$trow[0] $trow[1]";
					}
					$ulos .= "</select>";
				}
			}
		}
	}
	//tuotteen varastostatus
	if ($tee == 'Z') {
		$query = "	SELECT tuote.*, date_format(muutospvm, '%Y-%m-%d') muutos, date_format(luontiaika, '%Y-%m-%d') luonti,
					group_concat(distinct tuotteen_toimittajat.toimittaja order by tuotteen_toimittajat.tunnus separator '<br>') toimittaja,
					group_concat(distinct tuotteen_toimittajat.osto_era order by tuotteen_toimittajat.tunnus separator '<br>') osto_era,
					group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') toim_tuoteno,
					group_concat(distinct tuotteen_toimittajat.tuotekerroin order by tuotteen_toimittajat.tunnus separator '<br>') tuotekerroin 
					FROM tuote 
					LEFT JOIN tuotteen_toimittajat USING (yhtio, tuoteno)
					WHERE tuote.yhtio = '$kukarow[yhtio]' 
					and tuote.status NOT IN ('P','X')
					and tuote.tuoteno = '$tuoteno'
					GROUP BY tuote.tuoteno";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			$tuoterow = mysql_fetch_array($result);

			//saldot per varastopaikka
			$query = "select * from tuotepaikat where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
			$sresult = mysql_query($query) or pupe_error($query);

			//saldolaskentaa tulevaisuuteen
			$query = "	SELECT  if(tyyppi = 'O', toimaika, kerayspvm) paivamaara,
						sum(if(tyyppi='O', varattu, 0)) tilattu,
						sum(if((tyyppi='L' or tyyppi='G' or tyyppi='V'), varattu, 0)) varattu
						FROM tilausrivi
						WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno' and varattu>0 and tyyppi in ('O','L','G','V')
						GROUP BY toimaika";
			$presult = mysql_query($query) or pupe_error($query);

			//tilauksessa olevat
			$query = "	SELECT toimaika paivamaara,
						sum(varattu) tilattu, otunnus
						FROM tilausrivi
						WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno' and varattu>0 and tyyppi='O'
						GROUP BY toimaika, otunnus
						ORDER BY toimaika";
			$tulresult = mysql_query($query) or pupe_error($query);

			//korvaavat tuotteet
			$query  = "select * from korvaavat where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
			$korvaresult = mysql_query($query) or pupe_error($query);

			//eka laitetaan tuotteen yleiset (aika staattiset) tiedot
			echo "<table>";
			echo "<tr><th>".t("Tuoteno")."</th><th colspan='5'>".t("Nimitys")."</th>";
			echo "<tr><td>$tuoterow[tuoteno]</td><td colspan='5'>".substr(asana('nimitys_',$tuoterow['tuoteno'],$tuoterow['nimitys']),0,100)."</td></tr>";

			echo "<tr><th>".t("Os/Try")."</th><th>".t("Toimittaja")."</th><th>".t("Aleryhm�")."</th><th>".t("T�hti")."</th><th colspan='2'>".t("VAK")."</th></tr>";
			echo "<td>$tuoterow[osasto]/$tuoterow[try]</td><td>$tuoterow[toimittaja]</td>
					<td>$tuoterow[aleryhma]</td><td>$tuoterow[tahtituote]</td><td colspan='2'>$tuoterow[vakkoodi]</td></tr>";
			echo "<tr><th>".t("Toimtuoteno")."</th><th>".t("Myyntihinta")."</th><th>".t("Nettohinta")."</th><th colspan='3'>".t("Viimeksi tullut")."</th>";
			echo "<tr><td>$tuoterow[toim_tuoteno]</td>
					<td>$tuoterow[myyntihinta]</td><td>$tuoterow[nettohinta]</td><td colspan='3'>$tuoterow[vihapvm]</td></tr>";
			echo "<tr><th>".t("H�lyraja")."</th><th>".t("Tiler�")."</th><th>".t("Toier�")."</th><th>".t("Kerroin")."</th><th>".t("Tarrakerroin")."</th><th>".t("Tarrakpl")."</th>";
			echo "<tr><td>$tuoterow[halytysraja]</td>
					<td>$tuoterow[osto_era]</td><td>$tuoterow[myynti_era]</td><td>$tuoterow[tuotekerroin]</td>
					<td>$tuoterow[tarrakerroin]</td><td>$tuoterow[tarrakpl]</td></tr>";
			echo "</table><br>";


			// Varastosaldot ja paikat
			echo "<table><tr><td class='back' valign='top'>";

			if ($tuoterow["ei_saldoa"] == '') {
				//saldot
				echo "<table>";
				echo "<tr><th>".t("Varastopaikka")."</th><th>".t("Saldo")."</th></tr>";

				$kokonaissaldo = 0;
				if (mysql_num_rows($sresult) > 0) {
					while ($saldorow = mysql_fetch_array ($sresult)) {
						echo "<tr><td>$saldorow[hyllyalue] $saldorow[hyllynro] $saldorow[hyllyvali] $saldorow[hyllytaso]</td>";
						echo "<td>$saldorow[saldo]</td></tr>";
						//summataan kokonaissaldoa
						$kokonaissaldo += $saldorow["saldo"];
					}
				}
				echo "<tr><th>".t("Yhteens�")."</th><td>$kokonaissaldo</td></tr>";
				$asaldo = $kokonaissaldo;
				$ennpois = 0;
				$tilauksessa = 0;
				while ($prow = mysql_fetch_array ($presult)) {
					if($prow["varattu"] > 0) {
						$ennpois += $prow["varattu"];
					}
				}
				$myytavissa = $kokonaissaldo-$ennpois;
				echo "<tr><th>".t("Myyt�viss�")."</th><td>$myytavissa</td></tr>";

				echo "</table>";
				echo "</td><td class='back' valign='top'>";

				// tilatut
				echo "<table>";
				echo "<tr><th>".t("P�iv�m��r�")."</th><th>".t("Tilattu")."</th><th>".t("Tilaus")."</th></tr>";

				$tilauksessa = 0;
				if (mysql_num_rows($tulresult)>0) {
					while ($prow = mysql_fetch_array ($tulresult)) {
							$asaldo = $asaldo + $prow["tilattu"];
							echo "<tr><td>$prow[paivamaara]</td><td>$prow[tilattu]</td><td>$prow[otunnus]</td></tr>";
							$tilauksessa += $prow["tilattu"];
					}
				}
				echo "<tr><th>".t("Yhteens�")."</th><td>$tilauksessa</td></tr>";
				echo "</table>";
				echo "</td><td class='back' valign='top'>";
			}

			echo "<table>";
			echo "<th>".t("Korvaavat")."</th><th>".t("Kpl").".</th>";

			if (mysql_num_rows($korvaresult)==0)
			{
				echo "<tr><td>".t("Ei korvaavia")."!</td><td></td></tr>";
			}
			else
			{
				// tuote l�ytyi, joten haetaan sen id...
				$row    = mysql_fetch_array($korvaresult);
				$id		= $row['id'];

				$query = "select * from korvaavat where id='$id' and tuoteno<>'$tuoteno' and yhtio='$kukarow[yhtio]' order by jarjestys, tuoteno";
				$korva2result = mysql_query($query) or pupe_error($query);

				while ($row = mysql_fetch_array($korva2result))
				{
					//hateaan viel� korvaaville niiden saldot.
					//saldot per varastopaikka
					$query = "select sum(saldo) alkusaldo from tuotepaikat where tuoteno='$row[tuoteno]' and yhtio='$kukarow[yhtio]'";
					$alkuresult = mysql_query($query) or pupe_error($query);
					$alkurow = mysql_fetch_array($alkuresult);

					//ennakkopoistot
					$query = "	SELECT sum(varattu) varattu
								FROM tilausrivi
								WHERE tyyppi in ('L','G','V') and yhtio = '$kukarow[yhtio]' and tuoteno = '$row[tuoteno]' and varattu>0";
					$varatutresult = mysql_query($query) or pupe_error($query);
					$varatutrow = mysql_fetch_array($varatutresult);

					$vapaana = $alkurow["alkusaldo"] - $varatutrow["varattu"];

					echo "<tr><td><a href='$PHP_SELF?tee=Z&tuoteno=$row[tuoteno]'>$row[tuoteno]</a></td><td>$vapaana</td></tr>";
				}

			}
			
			echo "</table></td></tr></table><br>";


			$query = "	SELECT tilausrivi.*, lasku.ytunnus, tilausrivi.varattu+tilausrivi.jt kpl, lasku.nimi, tilausrivi.toimaika, round((tilausrivi.varattu+tilausrivi.jt)*tilausrivi.hinta*(1-(tilausrivi.ale/100)),2) rivihinta
						FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
						JOIN lasku use index (PRIMARY) ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tyyppi = 'G'
						and tilausrivi.tuoteno = '$tuoteno'
						and tilausrivi.laadittu > '0000-00-00 00:00:00'
						and tilausrivi.laskutettuaika = '0000-00-00'
						and (tilausrivi.varattu != 0 or jt != 0)
						and tilausrivi.var not in ('P')
						ORDER BY tyyppi, var";
			$jtresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($jtresult) != 0) {

				// Avoimet rivit
				echo "</td></tr><tr><td class='back' valign='top'><br><table>";

				echo "<tr>
						<th>".t("Asiakas/Toimittaja")."</th>
						<th>".t("Tilaus/Keikka")."</th>
						<th>".t("Tyyppi")."</th>
						<th>".t("Toimaika")."</th>
						<th>".t("Kpl")."</th>
						</tr>";

				// jtrivej� l�ytyi
				while ($jtrow = mysql_fetch_array($jtresult)) {

					$tyyppi = "";
					$merkki = "";
					$keikka = "";

					if ($jtrow["tyyppi"] == "O") {
						$tyyppi = t("Ostotilaus");
						$merkki = "+";

						$query = "	SELECT laskunro
									FROM lasku
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus='$jtrow[uusiotunnus]'";
						$keikkares = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($keikkares) > 0) {
							$keikkarow = mysql_fetch_array($keikkares);
							$keikka = " / ".$keikkarow["laskunro"];
						}
					}
					elseif($jtrow["tyyppi"] == "E") {
						$tyyppi = t("Ennakkotilaus");
						$merkki = "-";
					}
					elseif($jtrow["tyyppi"] == "G") {
						$tyyppi = t("Varastosiirto");
						$merkki = "-";
					}
					elseif($jtrow["tyyppi"] == "V") {
						$tyyppi = t("Valmistus");
						$merkki = "-";
					}
					elseif($jtrow["tyyppi"] == "L" and $jtrow["var"] == "J") {
						$tyyppi = t("J�lkitoimitus");
						$merkki = "-";
					}
					elseif(($jtrow["tyyppi"] == "L" or $jtrow["tyyppi"] == "N") and $jtrow["varattu"] > 0) {
						$tyyppi = t("Myynti");
						$merkki = "-";
					}
					elseif(($jtrow["tyyppi"] == "L" or $jtrow["tyyppi"] == "N") and $jtrow["varattu"] < 0) {
						$tyyppi = t("Hyvitys");
						$merkki = "+";
					}
					echo "<tr>
							<td>$jtrow[nimi]</td>
							<td>$jtrow[otunnus] $keikka</td>";
					echo "	<td>$tyyppi</td>
							<td>".substr($jtrow["toimaika"],0,10)."</td>
							<td>$merkki".abs($jtrow["kpl"])."</td>
							</tr>";
				}

				echo "</table>";
			}
			
			echo "</td></tr><tr><td class='back' valign='top'><br>";
			echo "<table>";
			echo "<form action='$PHP_SELF#Tapahtumat' method='post'>";

			if ($historia == "") $historia=1;
			$chk[$historia] = "SELECTED";

			echo "<input type='hidden' name='tee' value='Z'>";
			echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";

			echo "<a href='#' name='Tapahtumat'>";

			echo "<tr>";
			echo "<th colspan='2'>".t("N�yt� tapahtumat").": ";
			echo "<select name='historia' onchange='submit();'>'";
			echo "<option value='1' $chk[1]> ".t("20 viimeisint�")."</option>";
			echo "<option value='2' $chk[2]> ".t("Tilivuoden alusta")."</option>";
			echo "<option value='3' $chk[3]> ".t("L�hes kaikki")."</option>";
			echo "</select>";
			echo "</th>";


			if ($tapahtumalaji == "laskutus") 			$sel1="SELECTED";
			if ($tapahtumalaji == "tulo") 				$sel2="SELECTED";
			if ($tapahtumalaji == "valmistus") 			$sel3="SELECTED";
			if ($tapahtumalaji == "siirto") 			$sel4="SELECTED";
			if ($tapahtumalaji == "kulutus") 			$sel5="SELECTED";
			if ($tapahtumalaji == "Inventointi") 		$sel6="SELECTED";
			if ($tapahtumalaji == "Ep�kurantti") 		$sel7="SELECTED";
			if ($tapahtumalaji == "poistettupaikka") 	$sel8="SELECTED";
			if ($tapahtumalaji == "uusipaikka") 		$sel9="SELECTED";

			echo "<th colspan='2'>".t("Tapahtumalaji").": ";
			echo "<select name='tapahtumalaji' onchange='submit();'>'";
			echo "<option value=''>".t("N�yt� kaikki")."</option>";
			echo "<option value='laskutus' $sel1>".t("Laskutukset")."</option>";
			echo "<option value='tulo' $sel2>".t("Tulot")."</option>";
			echo "<option value='valmistus' $sel3>".t("Valmistukset")."</option>";
			echo "<option value='siirto' $sel4>".t("Siirrot")."</option>";
			echo "<option value='kulutus' $sel5>".t("Kulutukset")."</option>";
			echo "<option value='Inventointi' $sel6>".t("Inventoinnit")."</option>";
			echo "<option value='Ep�kurantti' $sel7>".t("Ep�kuranttiusmerkinn�t")."</option>";
			echo "<option value='poistettupaikka' $sel8>".t("Poistetut tuotepaikat")."</option>";
			echo "<option value='uusipaikka' $sel9>".t("Perustetut tuotepaikat")."</option>";
			echo "</select>";
			echo "</th>";

			echo "<tr>";
			echo "<th>".t("K�ytt�j�@Pvm")."</th>";
			echo "<th>".t("Tyyppi")."</th>";
			echo "<th>".t("Kpl")."</th>";
			echo "<th>".t("Selite")."";

			echo "</th></form>";
			echo "</tr>";


			//tapahtumat
			if ($historia == '1' or $historia == '') {
				$maara = "LIMIT 20";
				$ehto = ' and tapahtuma.laadittu >= date_sub(now(), interval 6 month)';
			}
			if ($historia == '2') {
				$maara = "";
				$ehto = " and tapahtuma.laadittu > '$yhtiorow[tilikausi_alku]'";
			}
			if ($historia == '3') {
				$maara = "LIMIT 2500";
				$ehto = "";
			}

			$query = "	SELECT concat_ws('@', tapahtuma.laatija, tapahtuma.laadittu) kuka, tapahtuma.laji, tapahtuma.kpl, tapahtuma.kplhinta, tapahtuma.hinta,
						if(tapahtuma.laji in ('tulo','valmistus'), tapahtuma.kplhinta, tapahtuma.hinta)*tapahtuma.kpl arvo, tapahtuma.selite, lasku.tunnus laskutunnus
						FROM tapahtuma use index (yhtio_tuote_laadittu)
						LEFT JOIN tilausrivi ON tilausrivi.yhtio=tapahtuma.yhtio and tilausrivi.tunnus=tapahtuma.rivitunnus
						LEFT JOIN lasku ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
						WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
						and tapahtuma.tuoteno = '$tuoteno'
						and tapahtuma.laadittu > '0000-00-00 00:00:00'
						and tapahtuma.laji = 'siirto'
						$ehto
						ORDER BY tapahtuma.laadittu desc $maara";
			$qresult = mysql_query($query) or pupe_error($query);

			$vararvo_nyt = sprintf('%.2f',$kokonaissaldo_tapahtumalle*$tuoterow["kehahin"]);

			while ($prow = mysql_fetch_array ($qresult)) {

				$vararvo_nyt -= $prow["arvo"];

				if ($tapahtumalaji == "" or strtoupper($tapahtumalaji)==strtoupper($prow["laji"])) {
					echo "<tr>";
					echo "<td nowrap>$prow[kuka]</td>";
					echo "<td nowrap>";

					
					
					echo t("$prow[laji]");
					

					echo "</td>";

					echo "<td nowrap align='right'>$prow[kpl]</td>";
					echo "<td>$prow[selite]</td>";
					echo "</tr>";
				}
			}
			echo "</table>";
			
			
		}
		else {
			echo "<font class='message'>".t("Yht��n tuotetta ei l�ytynyt")."!<br></font>";
		}
		$tee = '';
	}
	if ($tee == "Y") {
			echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='Z'>";
			echo "<table><tr>";
			echo "<th>".t("Valitse tuotenumero").":</th>";
			echo "<td>$ulos</td>";
			echo "<td class='back'><input type='Submit' value='".t("Valitse")."'></td>";
			echo "</tr></table>";
			echo "</form>";
	}

	require ("inc/footer.inc");
?>
