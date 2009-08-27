<?php

	echo "<font class='head'>".t("ABC-Analyysi: Tuoteosasto tai tuoteryhm�")."<hr></font>";

	if ($toim == "kulutus") {
		$myykusana = t("Kulutus");
	}
	else {
		$myykusana = t("Myynti");
	}

	if (trim($saapumispp) != '' and trim($saapumiskk) != '' and trim($saapumisvv) != '') {
		$saapumispp = $saapumispp;
		$saapumiskk = $saapumiskk;
		$saapumisvv	= $saapumisvv;
	}
	elseif (trim($saapumispvm) != '') {
		list($saapumisvv, $saapumiskk, $saapumispp) = split('-', $saapumispvm);
	}

	// piirrell��n formi
	echo "<form action='$PHP_SELF' method='post' autocomplete='OFF'>";
	echo "<input type='hidden' name='tee' value='OSASTOTRY'>";
	echo "<input type='hidden' name='toim' value='$toim'>";

	// Monivalintalaatikot (osasto, try tuotemerkki...)
	// M��ritell��n mitk� latikot halutaan mukaan
	$lisa  = "";
	$ulisa = "";
	$mulselprefix = "abc_aputaulu";
	$monivalintalaatikot = array("OSASTO", "TRY", "TUOTEMERKKI", "TUOTEMYYJA", "TUOTEOSTAJA");

	require ("../tilauskasittely/monivalintalaatikot.inc");

	echo "<br/>";
	echo "<table style='display:inline;'>";
	echo "<tr>";
	echo "<th>".t("Sy�t� viimeinen saapumisp�iv�").":</th>";
	echo "	<td><input type='text' name='saapumispp' value='$saapumispp' size='2'>
			<input type='text' name='saapumiskk' value='$saapumiskk' size='2'>
			<input type='text' name='saapumisvv' value='$saapumisvv'size='4'></td></tr>";

	echo "<tr>";
	echo "<th>".t("Taso").":</th>";

	if ($lisatiedot != '') $sel = "selected";
	else $sel = "";

	echo "<td><select name='lisatiedot'>";
	echo "<option value=''>".t("Normaalitiedot")."</option>";
	echo "<option value='TARK' $sel>".t("N�ytet��n kaikki sarakkeet")."</option>";
	echo "</select></td>";
	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td>";
	echo "</tr>";
	echo "</form>";
	echo "</table><br>";

	if (count($mul_osasto) > 0 or count($mul_try) > 0 or count($mul_tme) > 0 or count($mul_tuotemyyja) > 0 or count($mul_tuoteostaja) > 0  or count($mul_malli) > 0 or (trim($saapumispp) != '' and trim($saapumiskk) != '' and trim($saapumisvv) != '')) {

		$valinta 		= 'luokka';
		$valintalisa 	= "";

		if (count($mul_osasto) == 1) {
			$valinta = "luokka_osasto";
		}
		if (count($mul_try) == 1) {
			$valinta = "luokka_try";
		}
		if (count($mul_tme) == 1) {
			$valinta = "luokka_try";
		}
		if (count($mul_tuotemyyja) == 1) {
			$valinta = "luokka_try";
		}
		if (count($mul_tuoteostaja) == 1) {
			$valinta = "luokka_try";
		}
		if (count($mul_malli) == 1) {
			$valinta = "luokka_try";
		}

		if (trim($saapumispp) != '' and trim($saapumiskk) != '' and trim($saapumisvv) != '') {
			$saapumispvm = "$saapumisvv-$saapumiskk-$saapumispp";
			$valintalisa .= " and saapumispvm <= '$saapumispvm' ";
			$valinta = "luokka_try";
		}

		if (count($haku) > 0) {
			foreach ($haku as $kentta => $arvo) {
				if (strlen($arvo) > 0 and $kentta != 'kateosuus') {
					$lisa  .= " and abc_aputaulu.$kentta like '%$arvo%'";
					$ulisa2 .= "&haku[$kentta]=$arvo";
				}
				if (strlen($arvo) > 0 and $kentta == 'kateosuus') {
					$hav = "HAVING abc_aputaulu.kateosuus like '%$arvo%' ";
					$ulisa2 .= "&haku[$kentta]=$arvo";
				}
			}
		}

		if (strlen($order) > 0) {
			$jarjestys = $order." ".$sort;
		}
		else {
			$jarjestys = "$valinta, $abcwhat desc";
		}

		//kauden yhteismyynnit ja katteet
		$query = "	SELECT
					sum(summa) yhtmyynti,
					sum(kate)  yhtkate
					FROM abc_aputaulu
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi='$abcchar'
					$lisa
					$valintalisa";
		$sumres = mysql_query($query) or pupe_error($query);
		$sumrow = mysql_fetch_array($sumres);

		if ($sumrow["yhtkate"] == 0) {
			$sumrow["yhtkate"] = 0.01;
		}

		//haetaan rivien arvot
		$query = "	SELECT
					luokka,
					osasto,
					try,
					tuotemerkki,
					$valinta,
					tuoteno,
					osasto,
					nimitys,
					tulopvm,
					try,
					summa,
					kate,
					katepros,
					kate/$sumrow[yhtkate] * 100	kateosuus,
					vararvo,
					varaston_kiertonop,
					katepros * varaston_kiertonop kate_kertaa_kierto,
					myyntierankpl,
					myyntieranarvo,
					rivia,
					kpl,
					puuterivia,
					palvelutaso,
					ostoerankpl,
					ostoeranarvo,
					osto_rivia,
					osto_kpl,
					osto_summa,
					kustannus,
					kustannus_osto,
					kustannus_yht,
					kate-kustannus_yht total,
					myyjanro,
					ostajanro,
					malli,
					mallitarkenne,
					saapumispvm,
					saldo
					FROM abc_aputaulu
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi='$abcchar'
					$valintalisa
					$lisa
					$hav
					ORDER BY $jarjestys";
		$res = mysql_query($query) or pupe_error($query);

		echo "<br><table>";
		echo "<tr>";

		if ($valinta == 'luokka_osasto')	$otsikko = "Osaston";
		if ($valinta == 'luokka_try') 		$otsikko = "Tryn";

		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=$valinta&sort=asc$ulisa2'>$otsikko<br>".t("Luokka")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=luokka&sort=asc$ulisa2'>".t("ABC")."<br>".t("Luokka")."</th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=tuoteno&sort=asc$ulisa2'>".t("Tuoteno")."</a><br>&nbsp;</th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=nimitys&sort=asc$ulisa2'>".t("Nimitys")."</a><br>&nbsp;</th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=osasto&sort=asc$ulisa2'>".t("Osasto")."</a><br>&nbsp;</th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=try&sort=asc$ulisa2'>".t("Try")."</a><br>&nbsp;</th>";

		if ($lisatiedot == "TARK") {
			echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=myyjanro&sort=asc$ulisa2'>".t("Myyj�")."</a><br>&nbsp;</th>";
			echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=ostajanro&sort=asc$ulisa2'>".t("Ostaja")."</a><br>&nbsp;</th>";
			echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=malli&sort=asc$ulisa2'>".t("Malli")."</a><br>&nbsp;</th>";
			echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=mallitarkenne&sort=asc$ulisa2'>".t("Mallitarkenne")."</a><br>&nbsp;</th>";
			echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=saapumispvm&sort=asc$ulisa2'>".t("Viimeinen")."<br>".t("Saapumispvm")."</a><br>&nbsp;</th>";
			echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=saldo&sort=asc$ulisa2'>".t("Saldo")."</a><br>&nbsp;</th>";
		}


		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=tulopvm&sort=desc$ulisa2'>".t("Tulopvm")."</a><br>&nbsp;</th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=summa&sort=desc$ulisa2'>$myykusana<br>".t("tot")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=kate&sort=desc$ulisa2'>".t("Kate")."<br>".t("tot")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=katepros&sort=desc$ulisa2'>".t("Kate")."<br>%</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=kateosuus&sort=desc$ulisa2'>".t("Osuus")." %<br>".t("kat").".</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=vararvo&sort=desc$ulisa2'>".t("Varast").".<br>".t("arvo")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=varaston_kiertonop&sort=desc$ulisa2'>".t("Varast").".<br>".t("kiert").".</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=kate_kertaa_kierto&sort=desc$ulisa2'>".t("Kate")."% x<br>".t("kiert").".</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=kpl&sort=desc$ulisa2'>$myykusana<br>".t("m��r�")."</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=myyntierankpl&sort=desc$ulisa2'>$myykusana".t("er�")."<br>".t("m��r�")."</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=myyntieranarvo&sort=desc$ulisa2'>$myykusana".t("er�")."<br>$yhtiorow[valkoodi]</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=rivia&sort=desc$ulisa2'>$myykusana<br>".t("rivej�")."</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=puuterivia&sort=desc$ulisa2'>".t("Puute")."<br>".t("rivej�")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=palvelutaso&sort=desc$ulisa2'>".t("Palvelu")."-<br>".t("taso")." %</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=ostoerankpl&sort=desc$ulisa2'>".t("Ostoer�")."<br>".t("m��r�")."</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=ostoeranarvo&sort=desc$ulisa2'>".t("Ostoer�")."<br>$yhtiorow[valkoodi]</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=osto_rivia&sort=desc$ulisa2'>".t("Ostettu")."<br>".t("rivej�")."</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=kustannus&sort=desc$ulisa2'>".t("Myynn").".<br>".t("kustan").".</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=kustannus_osto&sort=desc$ulisa2'>".t("Oston")."<br>".t("kustan").".</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=kustannus_yht&sort=desc$ulisa2'>".t("Kustan").".<br>".t("yht")."</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&order=total&sort=desc$ulisa2'>".t("Kate -")."<br>".t("Kustannus")."</a></th>";
		echo "</tr>";

		echo "<form action='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta' method='post'>";
		echo "<tr>";
		echo "<th><input type='text' name='haku[$valinta]' value='$haku[$valinta]' size='5'></th>";
		echo "<th><input type='text' name='haku[luokka]' value='$haku[luokka]' size='5'></th>";
		echo "<th><input type='text' name='haku[tuoteno]' value='$haku[tuoteno]' size='5'></th>";
		echo "<th><input type='text' name='haku[nimitys]' value='$haku[nimitys]' size='5'></th>";
		echo "<th><input type='text' name='haku[osasto]' value='$haku[osasto]' size='5'></th>";
		echo "<th><input type='text' name='haku[try]' value='$haku[try]' size='5'></th>";

		if ($lisatiedot == "TARK") {
			echo "<th><input type='text' name='haku[myyjanro]' value='$haku[myyjanro]' size='5'></th>";
			echo "<th><input type='text' name='haku[ostajanro]' value='$haku[ostajanro]' size='5'></th>";
			echo "<th><input type='text' name='haku[malli]' value='$haku[malli]' size='5'></th>";
			echo "<th><input type='text' name='haku[mallitarkenne]' value='$haku[mallitarkenne]' size='5'></th>";
			echo "<th><input type='text' name='haku[saapumispvm]' value='$haku[saapumispvm]' size='5'></th>";
			echo "<th><input type='text' name='haku[saldo]' value='$haku[saldo]' size='5'></th>";
		}

		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[tulopvm]' value='$haku[tulopvm]' size='5'></th>";
		echo "<th><input type='text' name='haku[summa]' value='$haku[summa]' size='5'></th>";
		echo "<th><input type='text' name='haku[kate]' value='$haku[kate]' size='5'></th>";
		echo "<th><input type='text' name='haku[katepros]' value='$haku[katepros]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[kateosuus]' value='$haku[kateosuus]' size='5'></th>";
		echo "<th><input type='text' name='haku[vararvo]' value='$haku[vararvo]' size='5'></th>";
		echo "<th><input type='text' name='haku[varaston_kiertonop]' value='$haku[varaston_kiertonop]' size='5'></th>";
		echo "<th><input type='text' name='haku[kate_kertaa_kierto]' value='$haku[kate_kertaa_kierto]' size='5'></th>";
		echo "<th><input type='text' name='haku[kpl]' value='$haku[kpl]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[myyntierankpl]' value='$haku[myyntierankpl]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[myyntieranarvo]' value='$haku[myyntieranarvo]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[rivia]' value='$haku[rivia]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[puuterivia]' value='$haku[puuterivia]' size='5'></th>";
		echo "<th><input type='text' name='haku[palvelutaso]' value='$haku[palvelutaso]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[ostoerankpl]' value='$haku[ostoerankpl]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[ostoeranarvo]' value='$haku[ostoeranarvo]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[osto_rivia]'	value='$haku[osto_rivia]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[kustannus]' value='$haku[kustannus]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[kustannus_osto]'value='$haku[kustannus_osto]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[kustannus_yht]' value='$haku[kustannus_yht]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[total]' value='$haku[total]' size='5'></th>";
		echo "<td class='back'><input type='Submit' value='".t("Etsi")."'></td></form></tr>";

		if (mysql_num_rows($res) == 0) {
			echo "</table>";
		}
		else {

			$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');
			$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00,0.00);

			while ($row = mysql_fetch_array($res)) {

				if (strtoupper($row['ei_varastoida']) == 'O') {
					$row['ei_varastoida'] = "<font style='color:FF0000'>".t("Ei varastoitava")."</font>";
				}
				else {
					$row['ei_varastoida'] = "";
				}

				if ($lisatiedot == "TARK") {
					// tehd��n avainsana query
					$keyres = t_avainsana("TRY", "", "and avainsana.selite ='$row[try]'");
					$keytry = mysql_fetch_array($keyres);

					// tehd��n avainsana query
					$keyres = t_avainsana("OSASTO", "", "and avainsana.selite ='$row[osasto]'");
					$keyosa = mysql_fetch_array($keyres);
				}

				echo "<tr>";
				echo "<td>".$ryhmanimet[$row[$valinta]]."</td>";
				echo "<td valign='top'><a href='$PHP_SELF?toim=$toim&tee=YHTEENVETO&luokka=$l$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot'>".$ryhmanimet[$row["luokka"]]."</a></td>";
				echo "<td valign='top'><a href='../tuote.php?tee=Z&tuoteno=".urlencode($row["tuoteno"])."'>$row[tuoteno]</a></td>";
				echo "<td valign='top'>$row[nimitys]  $row[ei_varastoida]</td>";
				echo "<td valign='top' nowrap><a href='$PHP_SELF?toim=$toim&tee=YHTEENVETO$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot'>$row[osasto] $keyosa[selitetark]</a></td>";
				echo "<td valign='top' nowrap><a href='$PHP_SELF?toim=$toim&tee=YHTEENVETO$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot'>$row[try] $keytry[selitetark]</a></td>";

				if ($lisatiedot == "TARK") {
					$query = "	SELECT distinct myyja, nimi
								FROM kuka
								WHERE yhtio='$kukarow[yhtio]'
								AND myyja = '$row[myyjanro]'
								AND myyja != ''
								ORDER BY myyja";
					$sresult = mysql_query($query) or pupe_error($query);
					$srow = mysql_fetch_array($sresult);

					echo "<td valign='top'>$srow[nimi]</td>";

					$query = "	SELECT distinct myyja, nimi
								FROM kuka
								WHERE yhtio='$kukarow[yhtio]'
								AND myyja = '$row[ostajanro]'
								AND myyja != ''
								ORDER BY myyja";
					$sresult = mysql_query($query) or pupe_error($query);
					$srow = mysql_fetch_array($sresult);

					echo "<td valign='top'>$srow[nimi]</td>";
					echo "<td valign='top'>$row[malli]</td>";
					echo "<td valign='top'>$row[mallitarkenne]</td>";
					echo "<td valign='top'>".tv1dateconv($row["saapumispvm"])."</td>";
					echo "<td  align='right' valign='top'>$row[saldo]</td>";
				}


				if ($lisatiedot == "TARK") echo "<td>".tv1dateconv($row["tulopvm"])."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["summa"]))."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kate"]))."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["katepros"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kateosuus"]))."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["vararvo"]))."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["varaston_kiertonop"]))."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kate_kertaa_kierto"]))."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.0f',$row["kpl"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["myyntierankpl"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["myyntieranarvo"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.0f',$row["rivia"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.0f',$row["puuterivia"]))."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["palvelutaso"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["ostoerankpl"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["ostoeranarvo"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.0f',$row["osto_rivia"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kustannus"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kustannus_osto"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kustannus_yht"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["total"]))."</td>";
				echo "</tr>\n";

				$saldoyht				+= $row["saldo"];
				$ryhmamyyntiyht 		+= $row["summa"];
				$ryhmakateyht   		+= $row["kate"];
				$ryhmanvarastonarvoyht 	+= $row["vararvo"];
				$rivilkmyht				+= $row["rivia"];
				$ryhmakplyht			+= $row["kpl"];
				$ryhmapuuteyht			+= $row["puutekpl"];
				$ryhmapuuterivityht		+= $row["puuterivia"];
				$ryhmaostotyht 	 		+= $row["osto_summa"];
				$ryhmaostotkplyht		+= $row["osto_kpl"];
				$ryhmaostotrivityht 	+= $row["osto_rivia"];
				$ryhmakustamyyyht		+= $row["kustannus"];
				$ryhmakustaostyht		+= $row["kustannus_osto"];
				$ryhmakustayhtyht		+= $row["kustannus_yht"];
				$totalyht				+= $row["total"];

			}

			//yhteens�rivi
			if ($ryhmamyyntiyht != 0) $kateprosenttiyht = round($ryhmakateyht / $ryhmamyyntiyht * 100,2);
			else $kateprosenttiyht = 0;

			if ($sumrow["yhtkate"] != 0) $kateosuusyht = round($ryhmakateyht / $sumrow["yhtkate"] * 100,2);
			else $kateosuusyht = 0;

			if ($ryhmanvarastonarvoyht != 0) $kiertonopeusyht = round(($ryhmamyyntiyht - $ryhmakateyht) / $ryhmanvarastonarvoyht,2);
			else $kiertonopeusyht = 0;

			if ($rivilkmyht != 0) $myyntieranarvoyht = round($ryhmamyyntiyht / $rivilkmyht,2);
			else $myyntieranarvoyht = 0;

			if ($rivilkmyht != 0) $myyntieranakplyht = round($ryhmakplyht / $rivilkmyht,2);
			else $myyntieranakplyht = 0;

			if ($ryhmapuuterivityht + $rivilkmyht != 0)	$palvelutasoyht = round(100 - ($ryhmapuuterivityht / ($ryhmapuuterivityht + $rivilkmyht) * 100),2);
			else $palvelutasoyht = 0;

			if ($ryhmaostotrivityht != 0) $ostoeranarvoyht = round($ryhmaostotyht / $ryhmaostotrivityht,2);
			else $ostoeranarvoyht = 0;

			if ($ryhmaostotrivityht != 0) $ostoeranakplyht = round($ryhmaostotkplyht / $ryhmaostotrivityht,2);
			else $ostoeranakplyht = 0;

			if ($ryhmamyyntiyht != 0 and $ryhmanvarastonarvoyht != 0) {
				$kate_kertaa_kierto = round(($ryhmakateyht / $ryhmamyyntiyht * 100) * (($ryhmamyyntiyht - $ryhmakateyht) / $ryhmanvarastonarvoyht), 2);
			}
			else {
				$kate_kertaa_kierto = 0;
			}

			echo "<tr>";

			if ($lisatiedot == "TARK") {
				echo "<td colspan='11' class='spec'>".t("Yhteens�").":</td>";
			}
			else {
				echo "<td colspan='6' class='spec'>".t("Yhteens�").":</td>";
			}

			if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>$saldoyht</td><td></td>";
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmamyyntiyht))."</td>";
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmakateyht))."</td>";
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$kateprosenttiyht))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$kateosuusyht))."</td>";
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmanvarastonarvoyht))."</td>";
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$kiertonopeusyht))."</td>";
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$kate_kertaa_kierto))."</td>";
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.0f',$ryhmakplyht))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$myyntieranakplyht))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$myyntieranarvoyht))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.0f',$rivilkmyht))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.0f',$ryhmapuuterivityht))."</td>";
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$palvelutasoyht))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ostoeranakplyht))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ostoeranarvoyht))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.0f',$ryhmaostotrivityht))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmakustamyyyht))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmakustaostyht))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmakustayhtyht))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$totalyht))."</td>";
			echo "</tr>\n";

			echo "</table>";
		}
	}

?>