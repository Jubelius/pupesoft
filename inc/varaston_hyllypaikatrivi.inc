<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali k�sittely
	$tyyppi = 1; // oletetaan rivin n�kyvyys

	if (mysql_field_name($result, $i) == "tunnus" or
	    mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "varasto") {
		$ulos = "<td><select name='{$nimi}'>";
	
		$query = "	SELECT *
					FROM varastopaikat
					WHERE yhtio = '{$kukarow['yhtio']}'";
		$varasto_res = mysql_query($query) or pupe_error($query);
	
		while ($varasto_row = mysql_fetch_assoc($varasto_res)) {
	
			$sel = $varasto_row['tunnus'] == $trow[$i] ? ' selected' : '';
	
			$ulos .= "<option value='{$varasto_row['tunnus']}'{$sel}>{$varasto_row['nimitys']}</option>";
		}
	
		$ulos .= "</select></td>";
	
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "keraysvyohyke") {
		$ulos = "<td><select name='{$nimi}'>";
		$ulos .= "<option value=''>".t("Valitse")."</option>";
	
		$query = "SELECT tunnus, nimitys FROM keraysvyohyke WHERE yhtio = '{$kukarow['yhtio']}' AND nimitys != ''";
		$keraysvyohyke_result = mysql_query($query) or pupe_error($query);
	
		while ($keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_result)) {
	
			$sel = $keraysvyohyke_row['tunnus'] == $trow[$i] ? ' selected' : '';
	
			$ulos .= "<option value='{$keraysvyohyke_row['tunnus']}'{$sel}>{$keraysvyohyke_row['nimitys']}</option>";
		}
	
		$ulos .= "</td>";
		$jatko = 0;
	}
