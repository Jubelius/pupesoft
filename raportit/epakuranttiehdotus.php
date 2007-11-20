<?php

///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 1;
require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Epäkuranttiehdotus")."</font><hr>";

// nollataan muuttujat
$epakuranttipvm = "";
$chk1 = "";
$chk2 = "";
$chk3 = "";
$chk4 = "";

if ($tyyppi == '25') $chk1 = "selected";
if ($tyyppi == 'puoli') $chk2 = "selected";
if ($tyyppi == '75') $chk3 = "selected";
if ($tyyppi == 'taysi') $chk4 = "selected";

if ($tuotetyyppi == '25') $tchk1 = "selected";
if ($tuotetyyppi == 'puoli') $tchk2 = "selected";
if ($tuotetyyppi == '75') $tchk3 = "selected";
if ($tuotetyyppi == 'taysi') $tchk4 = "selected";

// defaultteja
if (!isset($alkupvm))  $alkupvm  = date("Y-m-d",mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
if (!isset($loppupvm)) $loppupvm = date("Y-m-d",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
if (!isset($taysraja)) $taysraja = date("Y-m-d",mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
if (!isset($raja))     $raja = "0.5";

// errorcheckejä
if (!checkdate(substr($alkupvm,5,2), substr($alkupvm,8,2), substr($alkupvm,0,4))) {
	echo "<font class='error'>".t("Virheellinen päivämäärä")." $alkupvm!</font><br><br>";
	unset($subnappi);
}

if (!checkdate(substr($loppupvm,5,2), substr($loppupvm,8,2), substr($loppupvm,0,4))) {
	echo "<font class='error'>".t("Virheellinen päivämäärä")." $loppupvm!</font><br><br>";
	unset($subnappi);
}

if (!checkdate(substr($taysraja,5,2), substr($taysraja,8,2), substr($taysraja,0,4))) {
	echo "<font class='error'>".t("Virheellinen päivämäärä")." $taysraja!</font><br><br>";
	unset($subnappi);
}

echo "<form name='epakurantti' action='$PHP_SELF' method='post' autocomplete='off'>";
echo "<table>";

echo "<tr>";
echo "<th>".t("Valitse ehdotus").":</th>";
echo "<td colspan='2'>";
echo "<select name='tyyppi'>";
echo "<option $chk1 value='25'>25% epäkuranttiehdotus</option>";
echo "<option $chk2 value='puoli'>Puoliepäkuranttiehdotus</option>";
echo "<option $chk3 value='75'>75% epäkuranttiehdotus</option>";
echo "<option $chk4 value='taysi'>Täysepäkuranttiehdotus</option>";
echo "</select>";
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Tuoterajaus").":</th>";
echo "<td colspan='2'>";
echo "<select name='tuotetyyppi'>";
echo "<option value=''>Näytä kaikki tuotteet</option>";
echo "<option $tchk1 value='25'>Näytä vain 25% epäkurantit</option>";
echo "<option $tchk2 value='puoli'>Näytä vain puoliepäkurantit</option>";
echo "<option $tchk3 value='75'>Näytä vain 75% epäkurantit</option>";
echo "<option $tchk4 value='taysi'>Näytä vain täysepäkurantit</option>";
echo "</select>";
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Valitse alku- ja loppupäivä").":</th>";
echo "<td><input type='text' name='alkupvm'  value='$alkupvm'></td>";
echo "<td><input type='text' name='loppupvm' value='$loppupvm'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Anna epäkuranttiusraja (kierto)").":</th>";
echo "<td colspan='2'><input type='text' name='raja' value='$raja'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Anna täysepäkuranttisuuden alaraja pvm").":</th>";
echo "<td colspan='2'><input type='text' name='taysraja' value='$taysraja'></td><td class='back'>(Tuote on pitänyt laittaa edelliselle epäkuranttiustasolle ennen tätä päivää, jotta ehdotetaan seuraavaan epäkuranttitasoon)</td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Anna osasto ja/tai tuoteryhmä").":</th>";
echo "<td><input type='text' name='osasto' value='$osasto'></td>";
echo "<td><input type='text' name='try'    value='$try'></td>";
echo "</tr>";

echo "</table>";
echo "<br><input type='submit' name='subnappi' value='Aja raportti'>";
echo "</form><br><br>";

if ($subnappi != '') {

	$lisa = "";
	$msg  = "";

	if ($osasto != '') {
		$osasto = (int) $osasto;
		$lisa  .= "and osasto='$osasto' ";
		$msg   .= ", osasto $osasto";
	}

	if ($try != '') {
		$try   = (int) $try;
		$lisa .= "and try='$try' ";
		$msg  .= ", tuoteryhmä $try";
	}

	if ($tyyppi == '25') {
		// 25epäkurantteja etsittäessä tuote ei saa olla puoli eikä täysiepäkurantti
		$epakuranttipvm = "and epakurantti25pvm='0000-00-00' and epakurantti50pvm='0000-00-00' and epakurantti75pvm='0000-00-00' and epakurantti100pvm='0000-00-00'";
		echo "<font class='message'>".t("25% epäkuranttiehdotus, myydyt kappaleet")." $alkupvm - $loppupvm, ".t("kiertoraja")." $raja$msg. ".t("Viimeinen saapuminen ennen")." $alkupvm.</font><br><br>";
	}
	
	if ($tyyppi == 'puoli') {
		// puoliepäkurantteja etsittäessä tuote ei saa olla puoli eikä täysiepäkurantti
		$epakuranttipvm = "and epakurantti50pvm='0000-00-00' and epakurantti75pvm='0000-00-00' and epakurantti100pvm='0000-00-00'";
		echo "<font class='message'>".t("Puoliepäkuranttiehdotus, myydyt kappaleet")." $alkupvm - $loppupvm, ".t("kiertoraja")." $raja$msg. ".t("Viimeinen saapuminen ennen")." $alkupvm.</font><br><br>";
	}

	if ($tyyppi == '75') {
		// 75epäkurantteja etsittäessä tuote ei saa olla puoli eikä täysiepäkurantti
		$epakuranttipvm = "and epakurantti75pvm='0000-00-00' and epakurantti100pvm='0000-00-00'";
		echo "<font class='message'>".t("75% epäkuranttiehdotus, myydyt kappaleet")." $alkupvm - $loppupvm, ".t("kiertoraja")." $raja$msg. ".t("Viimeinen saapuminen ennen")." $alkupvm.</font><br><br>";
	}

	if ($tyyppi == 'taysi') {
		// täysiepäkurantteja etsittäessä tuotteen pitää olla puoliepäkurantti mutta ei täysepäkurantti
		$epakuranttipvm = "and epakurantti100pvm='0000-00-00'";
		echo "<font class='message'>".t("Täysiepäkuranttiehdotus, myydyt kappaleet")." $alkupvm - $loppupvm, ".t("kiertoraja")." $raja$msg. ".t("Viimeinen saapuminen ennen")." $alkupvm.</font><br><br>";
	}

	if ($tuotetyyppi == "25") {
		$epakuranttipvm = "and epakurantti25pvm != '0000-00-00' and epakurantti50pvm = '0000-00-00' and epakurantti75pvm = '0000-00-00' and epakurantti100pvm = '0000-00-00'";
	}

	if ($tuotetyyppi == "puoli") {
		$epakuranttipvm = "and epakurantti25pvm != '0000-00-00' and epakurantti50pvm != '0000-00-00' and epakurantti75pvm = '0000-00-00' and epakurantti100pvm = '0000-00-00'";
	}

	if ($tuotetyyppi == "75") {
		$epakuranttipvm = "and epakurantti25pvm != '0000-00-00' and epakurantti50pvm != '0000-00-00' and epakurantti75pvm != '0000-00-00' and epakurantti100pvm = '0000-00-00'";
	}

	if ($tuotetyyppi == "taysi") {
		$epakuranttipvm = "and epakurantti25pvm != '0000-00-00' and epakurantti50pvm != '0000-00-00' and epakurantti75pvm != '0000-00-00' and epakurantti100pvm != '0000-00-00'";
	}

	// etsitään saldolliset tuotteet
	$query  = "	SELECT tuote.tuoteno, tuote.osasto, tuote.try, tuote.myyntihinta, tuote.nimitys, tuote.tahtituote, tuote.status, tuote.hinnastoon, round(if(epakurantti75pvm='0000-00-00', if(epakurantti50pvm='0000-00-00', if(epakurantti25pvm='0000-00-00', kehahin, kehahin*0.75), kehahin*0.5), kehahin*0.25),6) kehahin, tuote.vihapvm, epakurantti25pvm, epakurantti50pvm, epakurantti75pvm,
				(select group_concat(distinct tuotteen_toimittajat.toimittaja separator '/') from tuotteen_toimittajat where tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno=tuote.tuoteno) toimittaja, ifnull(sum(saldo),0) saldo
				from tuote
				LEFT JOIN tuotepaikat on tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno
				where tuote.yhtio='$kukarow[yhtio]' and tuote.ei_saldoa='' $epakuranttipvm $lisa
				group by tuote.tuoteno, tuote.osasto, tuote.try, tuote.myyntihinta, tuote.nimitys, tuote.tahtituote, kehahin, tuote.vihapvm
				having saldo > 0";
	$result = mysql_query($query) or pupe_error($query);

	echo t("Löytyi")." ".mysql_num_rows($result)." ".t("sopivaa tuotetta.. Aloitellaan laskenta.")."<br><br>";

	flush();

	if (table_exists("yhteensopivuus_tuote")) {
		$yhtsop = "\t".t("yhteensopivuus")."\n";
	} else {
		$yhtsop = "\n";
	}

	echo "<pre>";
	echo "".t("osasto")."\t".t("try")."\t".t("kpl")."\t".t("saldo")."\t".t("kierto")."\t".t("tahtituote")."\t".t("status")."\t".t("hinnastoon")."\t".t("eka saapuminen")."\t".t("vika saapuminen")."\t".t("hinta")."\t".t("kehahin")."\t".t("tuoteno")."\t".t("nimitys")."\t".t("toimittaja").$yhtsop;
	
	
	while ($row = mysql_fetch_array($result)) {

		if ($row["epakurantti75pvm"] != "0000-00-00") {
			$epispvm = $row["epakurantti75pvm"];
		}
		elseif ($row["epakurantti50pvm"] != "0000-00-00") {
			$epispvm = $row["epakurantti50pvm"];
		}
		elseif ($row["epakurantti25pvm"] != "0000-00-00") {
			$epispvm = $row["epakurantti25pvm"];
		}

		// jos meillä on tuotteen vihapvm käytetään sitä, muuten eka from 70s...
		if ($row["vihapvm"] == "0000-00-00") $row["vihapvm"] = '1970-01-01';

		// haetaan eka ja vika saapumispäivä
		$query  = "SELECT date_format(ifnull(min(laadittu),'1970-01-01'),'%Y-%m-%d') min, date_format(ifnull(max(laadittu),'$row[vihapvm]'),'%Y-%m-%d') max from tapahtuma where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]' and laji='Tulo'";
		$tapres = mysql_query($query) or pupe_error($query);
		$taprow = mysql_fetch_array($tapres);

		// verrataan vähän päivämääriä. onpa vittumaista PHP:ssä!
		list($vv1,$kk1,$pp1) = split("-",$taprow["max"]); //$saapunut
		list($vv2,$kk2,$pp2) = split("-",$alkupvm); //$alaraja
		list($vv3,$kk3,$pp3) = split("-",$epispvm); //$epaku1pv
		list($vv4,$kk4,$pp4) = split("-",$taysraja); //$epa2raja
		$saapunut = (int) date('Ymd',mktime(0,0,0,$kk1,$pp1,$vv1));
		$alaraja  = (int) date('Ymd',mktime(0,0,0,$kk2,$pp2,$vv2));
		$epaku1pv = (int) date('Ymd',mktime(0,0,0,$kk3,$pp3,$vv3));
		$epa2raja = (int) date('Ymd',mktime(0,0,0,$kk4,$pp4,$vv4));

		// tätä tuotetta on saapunut ennen myyntirajauksen alarajaa, joten otetaan käsittelyyn
		if (($saapunut < $alaraja) and (($tyyppi != '25' and $epaku1pv < $epa2raja) or ($tyyppi == '25'))) {

			// haetaan tuotteen myydyt kappaleet
			$query  = "SELECT ifnull(sum(kpl),0) kpl FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika) WHERE yhtio='$kukarow[yhtio]' and tyyppi='L' and tuoteno='$row[tuoteno]' and laskutettuaika >= '$alkupvm' and laskutettuaika <= '$loppupvm'";
			$myyres = mysql_query($query) or pupe_error($query);
			$myyrow = mysql_fetch_array($myyres);

			// haetaan tuotteen kulutetut kappaleet
			$query  = "SELECT ifnull(sum(kpl),0) kpl FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika) WHERE yhtio='$kukarow[yhtio]' and tyyppi='V' and tuoteno='$row[tuoteno]' and toimitettuaika >= '$alkupvm' and toimitettuaika <= '$loppupvm'";
			$kulres = mysql_query($query) or pupe_error($query);
			$kulrow = mysql_fetch_array($kulres);

			// haetaan tuotteen ennakkopoistot
			$query  = "SELECT ifnull(sum(varattu),0) ennpois FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu) WHERE yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]' and tyyppi='L' and varattu<>0";
			$ennres = mysql_query($query) or pupe_error($query);
			$ennrow = mysql_fetch_array($ennres);

			// lasketaan saldo
			$saldo = $row["saldo"] - $ennrow["ennpois"];

			// lasketaan varaston kiertonopeus
			if ($saldo > 0) {
				$kierto = round(($myyrow["kpl"] + $kulrow["kpl"]) / $saldo, 2);
			}
			else {
				$kierto = 0;
			}

			// typecast
			$raja = (float) str_replace(",",".",$raja);

			if (table_exists("yhteensopivuus_tuote")) {
				$query = "SELECT count(yhteensopivuus_rekisteri.tunnus)
				FROM yhteensopivuus_tuote, yhteensopivuus_rekisteri
				WHERE yhteensopivuus_tuote.yhtio = yhteensopivuus_rekisteri.yhtio 
				AND yhteensopivuus_tuote.atunnus = yhteensopivuus_rekisteri.autoid
				AND yhteensopivuus_tuote.yhtio = '$kukarow[yhtio]'
				AND yhteensopivuus_tuote.tuoteno = '$row[tuoteno]'";

				$yhteensopivuus_res = mysql_query($query) or pupe_error($query);
				$yhteensopivuus_row = mysql_fetch_array($yhteensopivuus_res);
			}

			// katellaan ollaanko alle rajan
			if ($kierto < $raja) {
				echo "$row[osasto]\t$row[try]\t".str_replace(".",",",$myyrow['kpl']+$kulrow['kpl'])."\t".str_replace(".",",",$saldo)."\t".str_replace(".",",",$kierto)."\t$row[tahtituote]\t$row[status]\t$row[hinnastoon]\t$taprow[min]\t$taprow[max]\t".str_replace(".",",",$row['myyntihinta'])."\t".str_replace(".",",",$row['kehahin'])."\t$row[tuoteno]\t".asana('nimitys_',$row['tuoteno'],$row['nimitys'])."\t$row[toimittaja]";

				if ($yhteensopivuus_row[0] != 0) {
					echo "\t".$yhteensopivuus_row[0]."\n";
				} else {
					echo "\t\n";
				}				
			}
			

		} // end saapunut ennen alarajaa

		flush();
	}

	echo "</pre>";
}

// kursorinohjausta
$formi  = "epakurantti";
$kentta = "osasto";

require ("../inc/footer.inc");
