<?php

	echo "<font class='head'>".t("ABC-Analyysi�: ABC-Luokkayhteenveto")." $yhtiorow[nimi]<hr></font>";

	if ($toim == "kate") {
		$abcwhat = "kate";
		$abcchar = "TK";
	}
	else {
		$abcwhat = "summa";
		$abcchar = "TM";
	}

	// tutkaillaan saadut muuttujat
	$osasto = trim($osasto);
	$try    = trim($try);

	if ($osasto == "")	$osasto = trim($osasto2);
	if ($try    == "")	$try = trim($try2);

	if ($ed == 'on')	$chk = "CHECKED";
	else				$chk = "";

	// piirrell��n formi
	echo "<form action='$PHP_SELF' method='post' autocomplete='OFF'>";
	echo "<input type='hidden' name='tee' value='YHTEENVETO'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Sy�t� tai valitse osasto").":</th>";
	echo "<td><input type='text' name='osasto' size='10'></td>";

	$query = "	SELECT distinct selite, selitetark
				FROM avainsana
				WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO'";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='osasto2'>";
	echo "<option value=''>".t("Osasto")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		if ($osasto == $srow[0]) $sel = "selected";
		else $sel = "";
		echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
	}

	echo "</select></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Sy�t� tai valitse tuoteryhm�").":</th>";
	echo "<td><input type='text' name='try' size='10'></td>";

	$query = "	SELECT distinct selite, selitetark
				FROM avainsana
				WHERE yhtio='$kukarow[yhtio]' and laji='TRY'";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='try2'>";
	echo "<option value=''>".t("Tuoteryhm�")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		if ($try == $srow[0]) $sel = "selected";
		else $sel = "";
		echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
	}

	echo "</select></td><tr>";

	echo "</table>";

	echo "<br><input type='submit' value='".t("Aja raportti")."'>";

	echo "</form>";


	if ($tee == "YHTEENVETO") {

		echo "<table>";

		echo "<tr>";
		echo "<th nowrap>".t("ABC")."<br>".t("Luokka")."</th>";
		echo "<th nowrap>".t("Myynti")."<br>".t("tot")."</th>";
		echo "<th nowrap>".t("Myynti")."<br>x</th>";
		echo "<th nowrap>".t("Myynti")."<br>".t("min")."</th>";
		echo "<th nowrap>".t("Kate")."<br>".t("tot")."</th>";
		echo "<th nowrap>".t("Kate")."<br>%</th>";
		echo "<th nowrap>".t("Osuus")." %<br>".t("kat").".</th>";
		echo "<th nowrap>".t("Tuotteita")."<br>".t("KPL")."</th>";
		echo "<th nowrap>".t("Varast").".<br>".t("arvo")."</th>";
		echo "<th nowrap>".t("Varast").".<br>".t("kiert").".</th>";
		echo "<th nowrap>".t("Myyer�")."<br>".t("KPL")."</th>";
		echo "<th nowrap>".t("Myyer�")."<br>$yhtiorow[valkoodi]</th>";
		echo "<th nowrap>".t("Myyty")."<br>".t("rivej�")."</th>";
		echo "<th nowrap>".t("Puute")."<br>".t("rivej�")."</th>";
		echo "<th nowrap>".t("Palvelu")."-<br>".t("taso")." %</th>";
		echo "<th nowrap>".t("Ostoer�")."<br>".t("KPL")."</th>";
		echo "<th nowrap>".t("Ostoer�")."<br>$yhtiorow[valkoodi]</th>";
		echo "<th nowrap>".t("Ostettu")."<br>".t("rivej�")."</th>";
		echo "<th nowrap>".t("Myynn").".<br>".t("kustan").".</th>";
		echo "<th nowrap>".t("Oston")."<br>".t("kustan").".</th>";
		echo "<th nowrap>".t("Kustan").".<br>".t("yht")."</th>";

		echo "</tr>\n";

		$osastolisa = $trylisa = "";

		if ($osasto != '') {
			$osastolisa = " and osasto='$osasto' ";
		}
		if ($try != '') {
			$trylisa = " and try='$try' ";
		}

		//kauden yhteismyynnit ja katteet
		$query = "	SELECT
					sum(summa) yhtmyynti,
					sum(kate)  yhtkate
					FROM abc_aputaulu
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi='$abcchar'
					$osastolisa
					$trylisa";
		$sumres = mysql_query($query) or pupe_error($query);
		$sumrow = mysql_fetch_array($sumres);

		if ($sumrow["yhtkate"] == 0) {
			$sumrow["yhtkate"] = 0.01;
		}

		//haetaan luokkien arvot
		$query = "	SELECT
					luokka,
					count(tuoteno)						tuotelkm,
					max(summa) 							max,
					min(summa)	 						min,
					sum(rivia) 							rivia,
					sum(kpl) 							kpl,
					sum(summa) 							summa,
					sum(kate) 							kate,
					sum(puutekpl) 						puutekpl,
					sum(puuterivia) 					puuterivia,
					sum(osto_rivia) 					osto_rivia,
					sum(osto_kpl) 						osto_kpl,
					sum(osto_summa) 					osto_summa,
					sum(vararvo) 						vararvo,
					sum(kustannus) 						kustannus,
					sum(kustannus_osto) 				kustannus_osto,
					sum(kustannus_yht) 					kustannus_yht,
					sum(osto_summa)/sum(osto_rivia) 	ostoeranarvo,
					sum(osto_kpl)/sum(osto_rivia) 		ostoeranakpl,
					sum(summa)/sum(rivia) 				myyntieranarvo,
					sum(kpl)/sum(rivia) 				myyntieranakpl,
					sum(kate)/sum(summa)*100 			kateprosentti,
					(sum(summa)-sum(kate))/sum(vararvo) kiertonopeus,
					sum(kate)/$sumrow[yhtkate] * 100	kateosuus,
					100 - ((sum(puuterivia)/(sum(puuterivia)+sum(rivia))) * 100) palvelutaso
					FROM abc_aputaulu
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi='$abcchar'
					$osastolisa
					$trylisa
					GROUP BY luokka
					ORDER BY luokka, $abcwhat desc";
		$res = mysql_query($query) or pupe_error($query);

		$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');
		$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00,0.00);

		while ($row = mysql_fetch_array($res)) {

			echo "<tr>";

			$l = $row["luokka"];

			echo "<td><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$row[luokka]'>$ryhmanimet[$l]</a></td>";
			echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["summa"]))."</td>";
			echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["max"]))."</td>";
			echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["min"]))."</td>";
			echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kate"]))."</td>";
			echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kateprosentti"]))."</td>";
			echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kateosuus"]))."</td>";
			echo "<td align='right'>".str_replace(".",",",sprintf('%.0f',$row["tuotelkm"]))."</td>";
			echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["vararvo"]))."</td>";
			echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kiertonopeus"]))."</td>";
			echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["myyntieranakpl"]))."</td>";
			echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["myyntieranarvo"]))."</td>";
			echo "<td align='right'>".str_replace(".",",",sprintf('%.0f',$row["rivia"]))."</td>";
			echo "<td align='right'>".str_replace(".",",",sprintf('%.0f',$row["puuterivia"]))."</td>";
			echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["palvelutaso"]))."</td>";
			echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["ostoeranakpl"]))."</td>";
			echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["ostoeranarvo"]))."</td>";
			echo "<td align='right'>".str_replace(".",",",sprintf('%.0f',$row["osto_rivia"]))."</td>";
			echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kustannus"]))."</td>";
			echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kustannus_osto"]))."</td>";
			echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kustannus_yht"]))."</td>";
			echo "</tr>\n";

			$tuotelkmyht			+= $row["tuotelkm"];
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

		}


		//yhteens�rivi
		$kateprosenttiyht 	= round ($ryhmakateyht / $ryhmamyyntiyht * 100,2);
		$kateosuusyht     	= round ($ryhmakateyht / $sumrow["yhtkate"] * 100,2);
		$kiertonopeusyht  	= round (($ryhmamyyntiyht - $ryhmakateyht) / $ryhmanvarastonarvoyht,2);
		$myyntieranarvoyht  = round ($ryhmamyyntiyht / $rivilkmyht,2);
		$myyntieranakplyht  = round ($ryhmakplyht / $rivilkmyht,2);
		$palvelutasoyht 	= round (100 - ($ryhmapuuterivityht / ($ryhmapuuterivityht + $rivilkmyht) * 100),2);
		$ostoeranarvoyht	= round ($ryhmaostotyht / $ryhmaostotrivityht,2);
		$ostoeranakplyht 	= round ($ryhmaostotkplyht / $ryhmaostotrivityht,2);


		echo "<tr>";
		echo "<td>".t("Yhteens�").":</td>";
		echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ryhmamyyntiyht))."</td>";
		echo "<td align='right' class='spec'></td>";
		echo "<td align='right' class='spec'></td>";
		echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ryhmakateyht))."</td>";
		echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$kateprosenttiyht))."</td>";
		echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$kateosuusyht))."</td>";
		echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.0f',$tuotelkmyht))."</td>";
		echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ryhmanvarastonarvoyht))."</td>";
		echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$kiertonopeusyht))."</td>";
		echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$myyntieranakplyht))."</td>";
		echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$myyntieranarvoyht))."</td>";
		echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.0f',$rivilkmyht))."</td>";
		echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.0f',$ryhmapuuterivityht))."</td>";
		echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$palvelutasoyht))."</td>";
		echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ostoeranakplyht))."</td>";
		echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ostoeranarvoyht))."</td>";
		echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.0f',$ryhmaostotrivityht))."</td>";
		echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ryhmakustamyyyht))."</td>";
		echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ryhmakustaostyht))."</td>";
		echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ryhmakustayhtyht))."</td>";
		echo "</tr>\n";

		echo "</table>";
	}

?>