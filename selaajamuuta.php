<?php
	require "inc/parametrit.inc";

	js_popup();
	
	enable_ajax();

	if ($livesearch_tee == "TILIHAKU") {
		livesearch_tilihaku();
		exit;
	}

	echo "<font class='head'>".t("Tili�innin tarkastus")."</font><hr>";

	if (($tee == 'U' or $tee == 'P' or $tee == 'M' or $tee == 'J') and $oikeurow['paivitys'] != 1) {
		echo "<font class='errpr'>".t("Yritit p�ivitt�� vaikka sinulla ei ole siihen oikeuksia")."</font>";
		exit;
	}

	if ($tunnus != 0) {
		$query = "	SELECT *, concat_ws(' ', tapvm, mapvm) laskunpvm
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
		$result = mysql_query ($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			$smlaskurow = mysql_fetch_array($result);
			$laskunpvm = $smlaskurow['laskunpvm'];
		}
		else {
			echo t("Lasku katosi")." $tunnus";
			exit;
		}
	}

	if ($laji == '') 	$laji  = 'O';
	if ($laji == 'M') 	$selm  = 'SELECTED';
	if ($laji == 'O') 	$selo  = 'SELECTED';
	if ($laji == 'MM') 	$selmm = 'SELECTED';
	if ($laji == 'OM') 	$selom = 'SELECTED';
	if ($laji == 'X') 	$selx  = 'SELECTED';
	if ($laji == 'M') 	$lajiv = "tila = 'U'";
	if ($laji == 'O') 	$lajiv = "tila in ('H', 'Y', 'M', 'P', 'Q')";
	if ($laji=='X') 	$lajiv = "tila = 'X'";

	$pvm = 'tapvm';

	if ($laji == 'OM') {
		$lajiv = "tila = 'Y'";
		$pvm   = 'mapvm';
	}
	if ($laji == 'MM') {
		$lajiv = "tila = 'U'";
		$pvm   = 'mapvm';
	}

	// mik� kuu/vuosi nyt on
	$year = date("Y");
	$kuu  = date("n");

	// poimitaan erikseen edellisen kuun viimeisen p�iv�n vv,kk,pp raportin oletusp�iv�m��r�ksi
	if ($vv=='') $vv = date("Y",mktime(0,0,0,$kuu,0,$year));
	if ($kk=='') $kk = date("n",mktime(0,0,0,$kuu,0,$year));
	if (strlen($kk)==1) $kk = "0" . $kk;

	//Yl�s hakukriteerit
	if ($viivatut == 'on') $viivacheck='checked';

	echo "<form name = 'valinta' action = '$PHP_SELF' method='post'>
			<table>
			<tr><th>".t("Anna kausi, muodossa kk-vvvv").":</th>
			<td><input type = 'text' name = 'kk' value='$kk' size=2></td>
			<td><input type = 'text' name = 'vv' value='$vv' size=4></td>
			<th>".t("Mitk� tositteet listataan").":</th>
			<td><select name='laji'>
			<option value='M' $selm>".t("myyntilaskut")."
			<option value='O' $selo>".t("ostolaskut")."
			<option value='MM' $selmm>".t("myyntilaskut maksettu")."
			<option value='OM' $selom>".t("ostolaskut maksettu")."
			<option value='X' $selx>".t("muut")."
			</select></td>
			<td><input type='checkbox' name='viivatut' $viivacheck> ".t("Korjatut")."</td>
			<td class='back'><input type = 'submit' value = '".t("Valitse")."'></td>
			</tr>
			</table>
			</form><br><br>";

	$formi = 'valinta';
	$kentta = 'kk';

	// Vasemmalle laskuluettelo
	if ($vv < 2000) $vv += 2000;
	$lvv=$vv;
	$lkk=$kk;
	$lkk++;

	if ($lkk > 12) {
		$lkk='01';
		$lvv++;
	}

	echo "<div style='float: left; width: 55%; padding-right: 10px;'>";

	$query = "	SELECT *, $pvm pvm
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]' and $pvm >= '$vv-$kk-01' and $pvm < '$lvv-$lkk-01' and $lajiv
				ORDER BY tapvm desc, summa desc";
	$result = mysql_query($query) or pupe_error($query);
	$loppudiv ='';

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Haulla ei l�ytynyt yht��n laskua")."</font>";
	}
	else {

		echo "<div id='vasen' style='height: 300px; overflow: auto; margin-bottom: 10px; width: 100%;'>";
		echo "<table width='100%'>";
		echo "<tr>";
		echo "<th>".t("Nimi")."</th>";
		echo "<th>".t("Tapvm")."</th>";
		echo "<th>".t("Summa")."</th>";
		echo "<th>".t("Valuutta")."</th>";
		echo "</tr>";

		while ($trow = mysql_fetch_array($result)) {

			echo "<tr>";

			$ero = "td";
			if ($trow['tunnus']==$tunnus) $ero = "th";

			$komm = "";
			if ($trow['comments'] != '') {
				$loppudiv .= "<div id='id_".$trow['tunnus']."' class='popup' style='width:250px'>";
				$loppudiv .= $trow["comments"]."<br></div>";

				$komm = " <a onmouseout=\"popUp(event,'id_".$trow['tunnus']."')\" onmouseover=\"popUp(event,'id_".$trow['tunnus']."')\"><img src='pics/lullacons/alert.png'></a>";
			}
			
			if ($trow["nimi"] == "") {
				$trow["nimi"] = t("Ei nime�");
			}

			echo "<$ero><a name='$trow[tunnus]' href='$PHP_SELF?tee=E&tunnus=$trow[tunnus]&laji=$laji&vv=$vv&kk=$kk&viivatut=$viivatut#$trow[tunnus]'>$trow[nimi]</a>$komm</$ero>";
			echo "<$ero>".tv1dateconv($trow["pvm"])."</$ero>";
			echo "<$ero style='text-align: right;'>$trow[summa]</$ero>";
			echo "<$ero>$trow[valkoodi]</$ero>";
			echo "</tr>";
		}

		echo "</table>";
		echo "</div>";
	}

	echo "<div style='height: 400px; overflow: auto; width: 100%;'>";

	if ($tee == 'P') {
		// Olemassaolevaa tili�inti� muutetaan, joten poistetaan rivi ja annetaan perustettavaksi
		$query = "	SELECT *
					FROM tiliointi
					WHERE tunnus = '$ptunnus' and
					yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo t("Tili�inti� ei l�ydy")."! $query";

			require ("inc/footer.inc");
			exit;
		}

		$tiliointirow = mysql_fetch_array($result);

		$tili		= $tiliointirow['tilino'];
		$kustp		= $tiliointirow['kustp'];
		$kohde		= $tiliointirow['kohde'];
		$projekti	= $tiliointirow['projekti'];
		$summa		= $tiliointirow['summa'];
		$vero		= $tiliointirow['vero'];
		$selite		= $tiliointirow['selite'];
		$tositenro  = $tiliointirow['tosite'];

		$ok = 1;

		// Etsit��n kaikki tili�intirivit, jotka kuuluvat t�h�n tili�intiin ja lasketaan niiden summa
		$query = "	SELECT sum(summa)
					FROM tiliointi
					WHERE aputunnus = '$ptunnus' and
					yhtio = '$kukarow[yhtio]' and
					korjattu = ''
					GROUP BY aputunnus";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 0) {
			$summarow = mysql_fetch_array($result);
			$summa += $summarow[0];

			$query = "	UPDATE tiliointi SET
						korjattu = '$kukarow[kuka]',
						korjausaika = now()
						WHERE aputunnus = '$ptunnus' and
						yhtio = '$kukarow[yhtio]' and
						korjattu = ''";
			$result = mysql_query($query) or pupe_error($query);
		}

		$query = "	UPDATE tiliointi SET
					korjattu = '$kukarow[kuka]',
					korjausaika = now()
					WHERE tunnus = '$ptunnus' and
					yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		$tee = "E";
	}

	if ($tee == 'U') {
		// Lis�t��n tili�intirivi

		$summa = str_replace ( ",", ".", $summa);
		$selausnimi = 'tili'; // Minka niminen mahdollinen popup on?

		require ("inc/tarkistatiliointi.inc");

		$tiliulos = $ulos;

		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and
					tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo t("Laskua ei en�� l�ydy! Systeemivirhe!");

			require ("inc/footer.inc");
			exit;
		}

		$laskurow = mysql_fetch_array($result);

 		// Tarvitaan kenties tositenro
		if ($kpexport == 1 or strtoupper($yhtiorow['maa']) != 'FI') {

			if ($tositenro != 0) {
				$query = "	SELECT tosite
							FROM tiliointi
							WHERE yhtio = '$kukarow[yhtio]' and
							ltunnus = '$tunnus' and
							tosite = '$tositenro'";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) == 0) {
					echo t("Tositenron tarkastus ei onnistu! Systeemivirhe!");

					require ("inc/footer.inc");
					exit;
				}
			}
			else {
				// T�ll� ei viel� ole tositenroa. Yritet��n jotain
				// T�lle saamme tositenron ostoveloista
				if ($laskurow['tapvm'] == $tiliointipvm) {

					$query = "	SELECT tosite FROM tiliointi
								WHERE yhtio = '$kukarow[yhtio]' and
								ltunnus = '$tunnus' and
								tapvm = '$tiliointipvm' and
								tilino = '$yhtiorow[ostovelat]' and
								summa = round($laskurow[summa] * $laskurow[vienti_kurssi],2) * -1";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) == 0) {
						echo t("Tositenron tarkastus ei onnistu! Systeemivirhe!");

						require ("inc/footer.inc");
						exit;
					}

					$tositerow = mysql_fetch_array ($result);
					$tositenro = $tositerow['tosite'];
				}

 				// T�lle saamme tositenron ostoveloista
				if ($laskurow['mapvm'] == $tiliointipvm) {

					$query = "	SELECT tosite FROM tiliointi
								WHERE yhtio = '$kukarow[yhtio]' and
								ltunnus = '$tunnus' and
								tapvm = '$tiliointipvm' and
								tilino = '$yhtiorow[ostovelat]' and
								summa = round($laskurow[summa] * $laskurow[vienti_kurssi],2)";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) == 0) {
						echo t("Tositenron tarkastus ei onnistu! Systeemivirhe!");

						require ("inc/footer.inc");
						exit;
					}

					$tositerow = mysql_fetch_array ($result);
					$tositenro = $tositerow['tosite'];
				}
			}
		}

		if ($ok != 1) {
			require ("inc/teetiliointi.inc");
		}

		$tee = "E";
	}

	if ($tee == 'E') {
		// Tositteen tili�intirivit...
		require "inc/tiliointirivit.inc";
	}

	echo "</div>";
	echo "</div>";
	
	echo "<div style='height: 710px; overflow: auto; width: 40%;'>";
	//Oikealla laskun kuva
	if ($smlaskurow["tunnus"] > 0) {

		if ($smlaskurow["tila"] == "U") {
			$url = $palvelin2."tilauskasittely/tulostakopio.php?otunnus=$smlaskurow[tunnus]&toim=LASKU&tee=NAYTATILAUS";
		}
		else {
			$urlit = ebid($smlaskurow["tunnus"], TRUE);

			$url = $urlit[0];
		}

		echo "<iframe src='$url' style='width:100%; height: 710px; border: 0px; display: block;'></iFrame>";
	}
	echo "</div>";

	echo $loppudiv;

	echo "<div style='float: bottom;'>";
	require ("inc/footer.inc");
	echo "</div>";
?>