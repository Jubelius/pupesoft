<?php

	require ("inc/parametrit.inc");
	
	echo "<font class='head'>".t("Mysqlalias-avainsanojen yll�pito")."</font><hr>";
	
	if ($upd == 1) {

		list($xtaulu, $xalias_set) = explode("###", $taulu);

		// Luodaan puskuri, jotta saadaan taulukot kuntoon
		$query = "	SELECT *
					FROM $xtaulu
					WHERE tunnus = '0'";
		$result = mysql_query($query) or pupe_error($query);
		$trow = mysql_fetch_array($result);

		for ($i=1; $i < mysql_num_fields($result)-1; $i++) {

			// Tarkistetaan saako k�ytt�j� p�ivitt�� t�t� kentt��
			$al_nimi   = mysql_field_name($result, $i);

			$query = "	DELETE
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji	= 'MYSQLALIAS'
						and selite	= '$xtaulu.$al_nimi'
						and selitetark_2 = '$xalias_set'";
			$al_res = mysql_query($query) or pupe_error($query);
			
			if ($mysqlaliasbox[$al_nimi] != "" or trim($mysqlalias[$al_nimi]) != "") {
				$query = "	INSERT INTO avainsana
							SET yhtio = '$kukarow[yhtio]',
							laji	= 'MYSQLALIAS',
							selite	= '$xtaulu.$al_nimi',
							selitetark = '".trim($mysqlalias[$al_nimi])."',
							selitetark_2 = '$xalias_set'";
				$al_res = mysql_query($query) or pupe_error($query);				
			}
		}
	}
	
	
	// Nyt selataan
	$query  = "SHOW TABLES FROM $dbkanta";
	$tabresult = mysql_query($query) or pupe_error($query);

	$sel[$taulu] = "SELECTED";
	
	echo "<form method = 'post'>";
	echo "<select name = 'taulu'>";
	
	while ($tables = mysql_fetch_array($tabresult)) {
		if (file_exists("inc/".$tables[0].".inc")) {

			$query = "	SELECT distinct selitetark_2
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji	= 'MYSQLALIAS'
						and selite	like '".$tables[0].".%'
						and selitetark_2 != ''";
			$al_res = mysql_query($query) or pupe_error($query);
			
			echo "<option value='$tables[0]' ".$sel[$tables[0]].">$tables[0]</option>";
			
			if (mysql_num_rows($al_res) > 0) {
				while ($alrow = mysql_fetch_array($al_res)) {
					echo "<option value='$tables[0]###$alrow[selitetark_2]' ".$sel[$tables[0]."###".$alrow["selitetark_2"]].">$tables[0] - $alrow[selitetark_2]</option>";
				}
			}
		}
	}
	
	echo "</select>";
	echo "<input type='submit' value='".t("Valitse")."'>";
	echo "</form><br><br>";
	
	
	// Nyt n�ytet��n vanha tai tehd��n uusi(=tyhj�)
	if ($taulu != "") {	
		echo "<form method = 'post'>";
		echo "<input type = 'hidden' name = 'taulu' value = '$taulu'>";
		echo "<input type = 'hidden' name = 'upd' value = '1'>";

		list($taulu, $alias_set) = explode("###", $taulu);

		// Kokeillaan geneerist�
		$query = "	SELECT *
					FROM $taulu
					WHERE tunnus = 0";
		$result = mysql_query($query) or pupe_error($query);
		$trow = mysql_fetch_array($result);
		
		echo "<table><tr><td class='back' valign='top'>";
		echo "<table>";

		for ($i=0; $i < mysql_num_fields($result) - 1; $i++) {

			$nimi = "t[$i]";

			if (isset($t[$i])) {
				$trow[$i] = $t[$i];
			}

			if (strlen($trow[$i]) > 35) {
				$size = strlen($trow[$i])+2;
			}
			elseif (mysql_field_len($result,$i)>10) {
				$size = '35';
			}
			elseif (mysql_field_len($result,$i)<5) {
				$size = '5';
			}
			else {
				$size = '10';
			}

			$maxsize = mysql_field_len($result,$i); // Jotta t�t� voidaan muuttaa

			require ("inc/$taulu"."rivi.inc");

			// N�it� kentti� ei ikin� saa p�ivitt�� k�ytt�liittym�st�
			if (mysql_field_name($result, $i) == "laatija" or
				mysql_field_name($result, $i) == "muutospvm" or
				mysql_field_name($result, $i) == "muuttaja" or
				mysql_field_name($result, $i) == "luontiaika") {
				$tyyppi = 2;
			}

			//Haetaan tietokantasarakkeen nimialias
			$al_nimi = mysql_field_name($result, $i);
			$otsikko = "";
			$box 	 = "CHK";

			$query = "	SELECT *
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji='MYSQLALIAS'
						and selite='$taulu.$al_nimi'
						and selitetark_2 = '$alias_set'";
			$al_res = mysql_query($query) or pupe_error($query);

			if(mysql_num_rows($al_res) > 0) {
				$al_row = mysql_fetch_array($al_res);

				$otsikko = $al_row["selitetark"];				
				$box = "CHECKED";
			}

			// $tyyppi --> 0 rivi� ei n�ytet� ollenkaan
			// $tyyppi --> 1 rivi n�ytet��n normaalisti
			// $tyyppi --> 1.5 rivi n�ytet��n normaalisti ja se on p�iv�m��r�kentt�
			// $tyyppi --> 2 rivi n�ytet��n, mutta sit� ei voida muokata, eik� sen arvoa p�vitet�
			// $tyyppi --> 3 rivi n�ytet��n, mutta sit� ei voida muokata, mutta sen arvo p�ivitet��n
			// $tyyppi --> 4 rivi� ei n�ytet� ollenkaan, mutta sen arvo p�ivitet��n
			// $tyyppi --> 5 liitetiedosto

			if (($tyyppi > 0 and $tyyppi < 4) or $tyyppi == 5) {
				echo "<tr>";
				echo "<th align='left'>".mysql_field_name($result, $i)."</th>";
				
				echo "<th><input type='checkbox' name='mysqlaliasbox[".mysql_field_name($result, $i)."]' $box></th>";
				
				echo "<th align='left'><input type='text' size='30' name='mysqlalias[".mysql_field_name($result, $i)."]' value='$otsikko'></th>";
			}

			if ($jatko == 0) {
				echo $ulos;
			}
			elseif ($tyyppi == 1) {
				echo "<td><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size' maxlength='$maxsize'></td>";
			}
			elseif ($tyyppi == 1.5) {
				$vva = substr($trow[$i],0,4);
				$kka = substr($trow[$i],5,2);
				$ppa = substr($trow[$i],8,2);

				echo "<td>
						<input type = 'text' name = 'tpp[$i]' value = '$ppa' size='3' maxlength='2'>
						<input type = 'text' name = 'tkk[$i]' value = '$kka' size='3' maxlength='2'>
						<input type = 'text' name = 'tvv[$i]' value = '$vva' size='5' maxlength='4'></td>";
			}
			elseif ($tyyppi == 2) {
				echo "<td>$trow[$i]</td>";
			}
			elseif($tyyppi == 3) {
				echo "<td>$trow[$i]<input type = 'hidden' name = '$nimi' value = '$trow[$i]'></td>";
			}
			elseif($tyyppi == 4) {
				echo "<input type = 'hidden' name = '$nimi' value = '$trow[$i]'>";
			}
			elseif($tyyppi == 5) {
				echo "<td><input type = 'file' name = 'liite_$i'></td>";
			}

			if (isset($virhe[$i])) {
				echo "<td class='back'><font class='error'>$virhe[$i]</font></td>\n";
			}

			if (($tyyppi > 0 and $tyyppi < 4) or $tyyppi == 5) {
				echo "</tr>";
			}
		}
		echo "</table><br>";
		echo "<input type='submit' value='".t("P�ivit�")."'>";
		echo "</form>";		
	}
	
	require ("inc/footer.inc");

?>