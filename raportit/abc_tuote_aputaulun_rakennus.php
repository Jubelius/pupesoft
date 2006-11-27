<?php

if (trim($argv[1]) != '') {

	if ($argc == 0) die ("T�t� scripti� voi ajaa vain komentorivilt�!");

	// otetaan tietokanta connect
	require ("../inc/connect.inc");
	require ("../inc/functions.inc");

	$kukarow['yhtio'] = trim($argv[1]);

	if (trim($argv[2]) != "") {
		$abctyyppi = trim($argv[2]);
	}

	if (trim($argv[3]) != "") {
		$saldottomatmukaan = trim($argv[3]);
	}

	$query    = "select * from yhtio where yhtio='$kukarow[yhtio]'";
	$yhtiores = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($yhtiores) == 1) {
		$yhtiorow = mysql_fetch_array($yhtiores);
	}
	else {
		die ("Yhti� $kukarow[yhtio] ei l�ydy!");
	}

	$tee = "YHTEENVETO";
}
else {
	require ("../inc/parametrit.inc");
	echo "<font class='head'>".t("ABC-Aputaulun rakennus")."<hr></font>";
}

if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")-1));
if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")-1));
if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")-1));

if (!isset($kkl)) $kkl = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($vvl)) $vvl = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($ppl)) $ppl = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

if (!isset($abctyyppi)) $abctyyppi = "kate";

$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');
$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00,0.00);

// rakennetaan tiedot
if ($tee == 'YHTEENVETO') {

	if ($abctyyppi == "kate") {
		$abcwhat = "kate";
		$abcchar = "TK";
	}
	elseif ($abctyyppi == "kpl") {
		$abcwhat = "kpl";
		$abcchar = "TP";
	}
	else {
		$abcwhat = "summa";
		$abcchar = "TM";
	}

	//siivotaan ensin aputaulu tyhj�ksi
	$query = "	DELETE from abc_aputaulu
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = '$abcchar'";
	$res = mysql_query($query) or pupe_error($query);

	// katotaan halutaanko saldottomia mukaan.. default on ett� EI haluta
	if ($saldottomatmukaan == "") {
		$tuotejoin = " JOIN tuote on (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.ei_saldoa = '') ";
	}
	else {
		$tuotejoin = " JOIN tuote on (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno) ";
	}

	// otetaan isot queryt slavelta
	$useslave = 1;
	require ("../inc/connect.inc");

	//haetaan ensin koko kauden yhteismyynti ja ostot
	$query = "	SELECT
				sum(if(tyyppi='O', 1, 0))			kpl_osto,
				sum(if(tyyppi='L', 1, 0))			kpl,
				sum(if(tyyppi='L', rivihinta, 0))	summa,
				sum(if(tyyppi='L', kate, 0)) 		kate
				FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika)
				$tuotejoin
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				and tilausrivi.tyyppi in ('L','O')
				and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'
				and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
				HAVING $abcwhat > 0";
	$res = mysql_query($query) or pupe_error($query);
	$row = mysql_fetch_array($res);

	//kokokauden kokonaismyynti
	$kaudenmyynyht 		= $row["summa"];
	$kaudenkateyht 		= $row["kate"];
	$kaudenmyyriviyht 	= $row["kpl"];
	$kaudenostriviyht 	= $row["kpl_osto"];
	$sisainen_taso		= "34"; // t�� on nyt hardcoodattu, eli milt� kirjanpidon tasolta otetaan kulut

	if ($kustannuksetyht == "") {
		// etsit��n kirjanpidosta mitk� on meid�n kulut samalta ajanjaksolta
		$query  = "	SELECT sum(summa) summa
					FROM tiliointi use index (tapvm_index)
					join tili use index (tili_index) on (tili.yhtio=tiliointi.yhtio and tili.tilino=tiliointi.tilino and sisainen_taso like '$sisainen_taso%')
					where tiliointi.yhtio = '$kukarow[yhtio]' and
					tiliointi.tapvm >= '$vva-$kka-$ppa' and
					tiliointi.tapvm <= '$vvl-$kkl-$ppl' and
					tiliointi.korjattu = ''";
		$result = mysql_query($query) or pupe_error($query);
		$kprow  = mysql_fetch_array($result);
		$kustannuksetyht = $kprow["summa"];
	}

	// paljonko on rivej� kaikenkaikkiaan
	$rivityht = $kaudenmyyriviyht + $kaudenostriviyht;

	if ($rivityht != 0) {
		// lasketaan myynti- ja ostorivien osuus kokonaisriveist�
		$myynti_osuus = $kaudenmyyriviyht / $rivityht;
		$osto_osuus   = $kaudenostriviyht / $rivityht;
	}
	else {
		$myynti_osuus = 0;
		$osto_osuus   = 0;
	}

	// lasketaan myynnin ja oston kustannusten osuus kokonaiskustannuksista
	$myynninkustayht = $kustannuksetyht * $myynti_osuus;
	$ostojenkustayht = $kustannuksetyht * $osto_osuus;

	// sitten lasketaan viel� yhden myyntirivin kulu
	if ($kaudenmyyriviyht != 0) {
		$kustapermyyrivi = $myynninkustayht / $kaudenmyyriviyht;
	}
	else {
		$kustapermyyrivi = 0;
	}

	// ja lasketaan yhden ostorivin kulu
	if ($kaudenostriviyht != 0) {
		$kustaperostrivi = $ostojenkustayht / $kaudenostriviyht;
	}
	else {
		$kustaperostrivi = 0;
	}

	// rakennetaan tuotekohtaiset ABC-luokat.. haetaan kaikki tilausrivit ajanjaksolta
	$query = "	SELECT
				tilausrivi.tuoteno,
				ifnull(tuote.try,'#') try,
				ifnull(tuote.osasto,'#') osasto,
				sum(if(tyyppi='L' and (var='H' or var=''), 1, 0))			rivia,
				sum(if(tyyppi='L' and (var='H' or var=''), kpl, 0))			kpl,
				sum(if(tyyppi='L' and (var='H' or var=''), rivihinta, 0))	summa,
				sum(if(tyyppi='L' and (var='H' or var=''), kate, 0))		kate,
				sum(if(tyyppi='L' and (var='P'), tilkpl, 0))				puutekpl,
				sum(if(tyyppi='L' and (var='P'), 1, 0))						puuterivia,
				sum(if(tyyppi='O', 1, 0))									osto_rivia,
				sum(if(tyyppi='O', kpl, 0))									osto_kpl,
				sum(if(tyyppi='O', rivihinta, 0))							osto_summa,
				ifnull(sum(saldo) * if(epakurantti2pvm = '0000-00-00', if(epakurantti1pvm = '0000-00-00', kehahin, kehahin / 2), 0), 0) vararvo
				FROM tilausrivi USE INDEX (yhtio_tyyppi_laskutettuaika)
				$tuotejoin
				LEFT JOIN tuotepaikat USE INDEX (tuote_index) ON (tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno)
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				and tilausrivi.tyyppi in ('L','O')
				and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'
				and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
				GROUP BY 1,2,3
	   			ORDER BY $abcwhat desc";
	$res = mysql_query($query) or pupe_error($query);

	$i					= 0;
	$ryhmaprossa		= 0;

	// otetaan takasin master yhteys
	$useslave = 0;
	require ("../inc/connect.inc");

	while ($row = mysql_fetch_array($res)) {

		// katotaan onko kelvollinen tuote, elikk� luokitteluperuste pit�� olla > 0
		if ($row["${abcwhat}"] > 0) {

			// laitetaan oikeeseen luokkaan
			$luokka = $i;

			if ($abctyyppi == "kate") {
				//tuotteen suhteellinen kate totaalikatteesta
				if ($kaudenkateyht != 0) $tuoteprossa = ($row["kate"] / $kaudenkateyht) * 100;
				else $tuoteprossa = 0;
			}
			elseif ($abctyyppi == "kpl") {
				//tuotteen suhteellinen kpl totaalikappaleista
				if ($kaudenmyyriviyht != 0) $tuoteprossa = ($row["kpl"] / $kaudenmyyriviyht) * 100;
				else $tuoteprossa = 0;
			}
			else {
				//tuotteen suhteellinen myynti totaalimyynnist�
				if ($kaudenmyynyht != 0) $tuoteprossa = ($row["summa"] / $kaudenmyynyht) * 100;
				else $tuoteprossa = 0;
			}

			//muodostetaan ABC-luokka ryhm�prossan mukaan
			$ryhmaprossa += $tuoteprossa;
		}
		else {
			// ei ole kelvollinen tuote laitetaan I-luokkaan
			$luokka = 8;
		}

		if ($row["summa"] != 0) $kateprosentti = round($row["kate"] / $row["summa"] * 100,2);
		else $kateprosentti = 0;

		if ($row["vararvo"] != 0) $kiertonopeus  = round(($row["summa"] - $row["kate"]) / $row["vararvo"],2);
		else $kiertonopeus = 0;

		if ($row["rivia"] != 0) $myyntieranarvo = round($row["summa"] / $row["rivia"],2);
		else $myyntieranarvo = 0;

		if ($row["rivia"] != 0) $myyntieranakpl = round($row["kpl"] / $row["rivia"],2);
		else $myyntieranakpl = 0;

		if ($row["puuterivia"] + $row["rivia"] != 0) $palvelutaso = round(100 - ($row["puuterivia"] / ($row["puuterivia"] + $row["rivia"]) * 100),2);
		else $palvelutaso = 0;

		if ($row["osto_rivia"] != 0) $ostoeranarvo = round ($row["osto_summa"] / $row["osto_rivia"],2);
		else $ostoeranarvo = 0;

		if ($row["osto_rivia"] != 0) $ostoeranakpl = round ($row["osto_kpl"] / $row["osto_rivia"],2);
		else $ostoeranakpl = 0;

		$query = "	INSERT INTO abc_aputaulu
					SET yhtio			= '$kukarow[yhtio]',
					tyyppi				= '$abcchar',
					luokka				= '$luokka',
					tuoteno				= '$row[tuoteno]',
					osasto				= '$row[osasto]',
					try					= '$row[try]',
					summa				= '$row[summa]',
					kate				= '$row[kate]',
					katepros			= '$kateprosentti',
					vararvo				= '$row[vararvo]',
					varaston_kiertonop 	= '$kiertonopeus',
					myyntierankpl 		= '$myyntieranakpl',
					myyntieranarvo 		= '$myyntieranarvo',
					rivia				= '$row[rivia]',
					kpl					= '$row[kpl]',
					puutekpl			= '$row[puutekpl]',
					puuterivia			= '$row[puuterivia]',
					palvelutaso 		= '$palvelutaso',
					osto_rivia			= '$row[osto_rivia]',
					osto_kpl			= '$row[osto_kpl]',
					ostoerankpl 		= '$ostoeranakpl',
					ostoeranarvo 		= '$ostoeranarvo',
					osto_summa			= '$row[osto_summa]'";
		$insres = mysql_query($query) or pupe_error($query);

		// luokka vaihtuu
		if ($ryhmaprossa >= $ryhmaprossat[$i]) {
			$ryhmaprossa = 0;
			$i++;

			// ei menn� ikin� H-luokkaa pidemm�lle
			if ($i == 8) {
				$i = 7;
			}
		}
	}

	// nyt pit�� viel� k�yd� l�pi kaikki tuotteet joilla on saldoa mutta ei l�ydy viel� abc_aputaulusta.. ne kuuluu my�s I-luokkaan
	$query = "	SELECT
				tuote.tuoteno,
				tuote.try,
				tuote.osasto,
				abc_aputaulu.luokka,
				sum(saldo) saldo,
				sum(saldo) * if(epakurantti2pvm='0000-00-00', if(epakurantti1pvm='0000-00-00', kehahin, kehahin/2), 0) vararvo
				FROM tuotepaikat USE INDEX (tuote_index)
				JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno)
				LEFT JOIN abc_aputaulu USE INDEX (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuotepaikat.yhtio
				and abc_aputaulu.tuoteno = tuotepaikat.tuoteno
				and tyyppi = '$abcchar')
				WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
				GROUP BY 1,2,3,4
				HAVING saldo > 0 and luokka is null";
	$tuores = mysql_query($query) or pupe_error($query);

	// ja k�yd��n kaikki ne tuotteet l�pi ja lis�t��n aputauluun
	while ($row = mysql_fetch_array($tuores)) {

		$query = "	INSERT INTO abc_aputaulu
					SET yhtio			= '$kukarow[yhtio]',
					tyyppi				= '$abcchar',
					luokka				= '8',
					tuoteno				= '$row[tuoteno]',
					osasto				= '$row[osasto]',
					try					= '$row[try]',
					vararvo				= '$row[vararvo]'";
		$insres = mysql_query($query) or pupe_error($query);

	}

	// p�ivitet��n kaikille riveille kustannukset
	$query = "	UPDATE abc_aputaulu SET
				kustannus		= round(rivia * '$kustapermyyrivi', 2),
				kustannus_osto	= round(osto_rivia * '$kustaperostrivi', 2),
				kustannus_yht	= kustannus + kustannus_osto
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = '$abcchar'";
	$ires = mysql_query($query) or pupe_error($query);

	// p�ivitet��n ensiks kaikki osastot ja tuoteryhm�t I-luokkaan ja k�yd��n sitten p�ivitt�m�ss� niit� oikeisiin luokkiin
	$query = "	UPDATE abc_aputaulu SET
				luokka_osasto = '8',
				luokka_try = '8'
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = '$abcchar'";
	$ires = mysql_query($query) or pupe_error($query);

	// haetaan kaikki osastot
	$query = "	SELECT distinct osasto FROM abc_aputaulu use index (yhtio_tyyppi_osasto_try)
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = '$abcchar'
				order by osasto";
	$kaikres = mysql_query($query) or pupe_error($query);

	// tehd��n osastokohtaiset luokat
	while ($arow = mysql_fetch_array($kaikres)) {

		//haetaan luokan myynti yhteens�
		$query = "	SELECT
					sum(summa) yhtmyynti,
					sum(kpl)   yhtkpl,
					sum(kate)  yhtkate
					FROM abc_aputaulu use index (yhtio_tyyppi_osasto_try)
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = '$abcchar'
					and osasto = '$arow[osasto]'
					and $abcwhat > 0";
		$resi 	= mysql_query($query) or pupe_error($query);
		$yhtrow = mysql_fetch_array($resi);

		//rakennetaan aliluokat
		$query = "	SELECT
					summa,
					kate,
					kpl,
					tunnus
					FROM abc_aputaulu use index (yhtio_tyyppi_osasto_try)
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = '$abcchar'
					and osasto = '$arow[osasto]'
					and $abcwhat > 0
					ORDER BY $abcwhat desc";
		$res = mysql_query($query) or pupe_error($query);

		$i			 = 0;
		$ryhmaprossa = 0;

		while ($row = mysql_fetch_array($res)) {

			if ($abctyyppi == "kate") {
				//tuotteen suhteellinen kate totaalikatteesta
				if ($yhtrow["yhtkate"] != 0) $tuoteprossa = ($row["kate"] / $yhtrow["yhtkate"]) * 100;
				else $tuoteprossa = 0;
			}
			elseif ($abctyyppi == "kpl") {
				//tuotteen suhteellinen kpl totaalikappaleista
				if ($yhtrow["yhtkpl"] != 0) $tuoteprossa = ($row["kpl"] / $yhtrow["yhtkpl"]) * 100;
				else $tuoteprossa = 0;
			}
			else {
				//tuotteen suhteellinen myynti totaalimyynnist�
				if ($yhtrow["yhtmyynti"] != 0) $tuoteprossa = ($row["summa"] / $yhtrow["yhtmyynti"]) * 100;
				else $tuoteprossa = 0;
			}

			//muodostetaan ABC-luokka ryhm�prossan mukaan
			$ryhmaprossa += $tuoteprossa;

			$query = "	UPDATE abc_aputaulu
						SET luokka_osasto = '$i'
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = '$abcchar'
						and tunnus  = '$row[tunnus]'";
			$insres = mysql_query($query) or pupe_error($query);

			//luokka vaihtuu
			if (round($ryhmaprossa,2) >= $ryhmaprossat[$i]) {
				$ryhmaprossa = 0;
				$i++;

				// ei menn� ikin� H-luokkaa pidemm�lle
				if ($i == 8) {
					$i = 7;
				}
			}
		}

	}

	// haetaan kaikki tryt
	$query = "	SELECT distinct try FROM abc_aputaulu use index (yhtio_tyyppi_try)
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = '$abcchar'
				order by try";
	$kaikres = mysql_query($query) or pupe_error($query);

	// tehd��n try kohtaiset luokat
	while ($arow = mysql_fetch_array($kaikres)) {

		//haetaan luokan myynti yhteens�
		$query = "	SELECT
					sum(summa) yhtmyynti,
					sum(kpl) yktkpl,
					sum(kate) yhtkate
					FROM abc_aputaulu use index (yhtio_tyyppi_try)
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = '$abcchar'
					and try = '$arow[try]'
					and $abcwhat > 0";
		$resi 	= mysql_query($query) or pupe_error($query);
		$yhtrow = mysql_fetch_array($resi);

		//rakennetaan aliluokat
		$query = "	SELECT
					summa,
					kate,
					kpl,
					tunnus
					FROM abc_aputaulu use index (yhtio_tyyppi_try)
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = '$abcchar'
					and try = '$arow[try]'
					and $abcwhat > 0
					ORDER BY $abcwhat desc";
		$res = mysql_query($query) or pupe_error($query);

		$i			 = 0;
		$ryhmaprossa = 0;

		while ($row = mysql_fetch_array($res)) {

			if ($abctyyppi == "kate") {
				//tuotteen suhteellinen kate totaalikatteesta
				if ($yhtrow["yhtkate"] != 0) $tuoteprossa = ($row["kate"] / $yhtrow["yhtkate"]) * 100;
				else $tuoteprossa = 0;
			}
			elseif ($abctyyppi == "kpl") {
				//tuotteen suhteellinen kpl totaalikappaleista
				if ($yhtrow["yhtkpl"] != 0) $tuoteprossa = ($row["kpl"] / $yhtrow["yhtkpl"]) * 100;
				else $tuoteprossa = 0;
			}
			else {
				//tuotteen suhteellinen myynti totaalimyynnist�
				if ($yhtrow["yhtmyynti"] != 0) $tuoteprossa = ($row["summa"] / $yhtrow["yhtmyynti"]) * 100;
				else $tuoteprossa = 0;
			}

			//muodostetaan ABC-luokka ryhm�prossan mukaan
			$ryhmaprossa += $tuoteprossa;

			$query = "	UPDATE abc_aputaulu
						SET luokka_try = '$i'
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = '$abcchar'
						and tunnus  = '$row[tunnus]'";
			$insres = mysql_query($query) or pupe_error($query);

			//luokka vaihtuu
			if (round($ryhmaprossa,2) >= $ryhmaprossat[$i]) {
				$ryhmaprossa = 0;
				$i++;

				// ei menn� ikin� H-luokkaa pidemm�lle
				if ($i == 8) {
					$i = 7;
				}
			}
		}

	}

	$query = "OPTIMIZE table abc_aputaulu";
	$optir = mysql_query($query) or pupe_error($query);
}

if ($tee == "") {

	// piirrell��n formi
	echo "<form action='$PHP_SELF' method='post' autocomplete='OFF'>";
	echo "<input type='hidden' name='tee' value='YHTEENVETO'>";
	echo "<table>";

	echo "<th colspan='4'>".t("Valitse kausi").":</th>";

	echo "<tr><th>".t("Alkup�iv�m��r� (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td>
			</tr><tr><th>".t("Loppup�iv�m��r� (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";

	echo "<tr><th>ABC-luokkien laskentatapa</th>";
	echo "<td colspan='3'><select name='abctyyppi'>";
	echo "<option value='kate'>Katteen mukaan</option>";
	echo "<option value='myynti'>Myynnin mukaan</option>";
	echo "</select></td></tr>";

	echo "<tr><td colspan='4' class='back'><br></td></tr>";
	echo "<tr><th colspan='1'>".t("Kustannukset valitulla kaudella")."</th>
			<td colspan='3'><input type='text' name='kustannuksetyht' value='$kustannuksetyht' size='15'></td></tr>";
	echo "<tr><th colspan='1'>".t("Huomioi laskennassa my�s saldottomat tuotteet")."</th>
			<td colspan='3'><input type='checkbox' name='saldottomatmukaan' value='kylla'></td></tr>";

	echo "</table>";
	echo "<br><input type='submit' value='".t("Rakenna")."'>";
	echo "</form><br><br><br>";

}

if (trim($argv[1]) == '') {
	require ("../inc/footer.inc");
}

?>