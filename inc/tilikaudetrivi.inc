<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali k�sittely
	$tyyppi = 1; // oletetaan rivin n�kyvyys
	if ($i == 0) { //yhti�t� ei n�ytet�
		$tyyppi = 0;
	}
	
	if (mysql_field_name($result, $i) == "tilikausi_alku" or 
		mysql_field_name($result, $i) == "tilikausi_loppu") {
		$tyyppi = 1.5;
	}
	
	
?>
