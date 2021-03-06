<?php

if(!function_exists("avainsanatarkista")) {
	function avainsanatarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $ulosarray, $varastot_array;

		static $seliteerrori, $tem_kieli, $tem_laji, $tem_nakyvyys, $tem_selite, $seliteind, $tem_selitetark, $tem_selitetark_2, $tem_selitetark_3;

		if (mysql_field_name($result, $i) == "kieli") {
			$tem_kieli = trim($t[$i]);
		}

		if (mysql_field_name($result, $i) == "laji") {
			$tem_laji = trim($t[$i]);

			// Synkattavien avainsanojen lajia ei saa muuttaa, muuten synkkaus menee out of sync
			if (isset($trow["laji"]) and $trow["laji"] != $tem_laji and stripos($yhtiorow["synkronoi"], "avainsana") !== FALSE) {
				$abulisa = preg_match("/(^|,)(avainsana\|*([\|a-zA-Z_\-]*))($|,)/i", $yhtiorow["synkronoi"], $regs);
				$tem_lajit = explode("|",strtolower($regs[3]));

				if (in_array(strtolower($trow["laji"]), $tem_lajit)) {
					$virhe[$i] = t("Synkronoitavien avainsanojen lajia ei saa muuttaa")."!";
				}
			}
		}

		if (mysql_field_name($result, $i) == "nakyvyys") {
			$tem_nakyvyys = trim($t[$i]);
		}

		if (mysql_field_name($result, $i) == "selite") {
			$tem_selite = trim($t[$i]);
			$seliteind = $i;

			if ($tem_selite == '') {
				$seliteerrori = 1;
			}

			if ($tem_laji == 'LABEL') {
				if (trim($tem_selite) == '') {
					$virhe[$i] = t("Selite on pakollinen ja se pit�� olla muodossa #123 tai #112233");
				}
				elseif ($tem_selite{0} != '#') {
					$virhe[$i] = t("Selitteen ensimm�inen kirjain pit�� olla #");
				}
				elseif (strlen($tem_selite) != 4 and strlen($tem_selite) != 7) {
					$virhe[$i] = t("Selite t�ytyy olla 4 tai 7 merkki� pitk�").' '.strlen($tem_selite);
				}
			}


			if (strtoupper($tem_laji) == "ALVULK") {
				//	ALV pit�� olla pisteell�, ei pilkulla
				$t[$i] = round((float) str_replace(",", ".", $tem_selite), 2);
			}

			if (strlen($tem_selite) > 3 and $tem_laji == 'Y') {
				$virhe[$i] = t("Selite on liian pitk�, maksimipituus on kolme merkki�")."!";
			}

			if (strlen($tem_selite) > 1 and $tem_laji == 'ASIAKASLUOKKA') {
				$virhe[$i] = t("Selite on liian pitk�, maksimipituus on yksi merkki")."!";
			}

			if (!is_numeric($tem_selite) and in_array($tem_laji, array("TRY", "OSASTO", "PIIRI", "VALMISTUSLINJA"))) {
				$virhe[$i] = t("Selitteen on oltava numeerinen")."!";
			}

			if (($tem_laji == 'VAKIOVIESTI' or $tem_laji == 'VAKIOVIESTI_TIL') and trim($tem_selite) == '') {
				$virhe[$i] = t("Selite on pakollinen");
			}

			if ($tem_laji == "PARAMETRI") {
				preg_match_all('/([a-zA-Z_])+/', $tem_selite, $match);

				if ($match[0][0] != $tem_selite) {
					$virhe[$i] = t("Vain aakkoset ja alaviivat ovat sallittuja merkkej�");
				}
			}

			if ($tem_selite != '' and ($tem_laji == 'TRY' or $tem_laji == 'OSASTO')) {
				$query = "	SELECT tunnus
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							AND kieli   = '$tem_kieli'
							AND laji    = '$tem_laji'
							AND selite  = '$tem_selite'
							and tunnus != '$tunnus'";
				$results = pupe_query($query);

				if (mysql_num_rows($results) > 0) {
					$virhe[$seliteind] = $virhe[$i] = t("Avainsana annetuilla tiedoilla l�ytyy kannasta. Duplikaatit ovat kiellettyj�!");
				}
			}
		}

		if (mysql_field_name($result, $i) == "selitetark") {
			$tem_selitetark = trim($t[$i]);

			if ($seliteerrori == 1 and trim($t[$i]) == '' and $tem_laji != 'VAKIOVIESTI' and $tem_laji != 'VAKIOVIESTI_TIL') {
				$virhe[$i] = t("Selite ja selitetark ei voi olla tyhji�")."!";
			}

			if ($tem_laji == "VARASTORYHMA" and !is_array($varastot_array) and count($varastot_array) == 0) {
				$virhe[$i] = t("Et valinnut yht��n varastoa")."!";
			}
			elseif ($tem_laji == 'VARASTORYHMA') {
				if (is_array($varastot_array) and count($varastot_array) > 0) {
					$varastot = '';
					foreach($varastot_array as $varasto_apu) {
						$varastot .= "$varasto_apu,";
					}
					$varastot = substr($varastot, 0, -1);
					$t[$i] = $varastot;
				}
			}
		}

		if (mysql_field_name($result, $i) == "selitetark_2") {
			$tem_selitetark_2 = trim($t[$i]);
		}

		if (mysql_field_name($result, $i) == "selitetark_3") {
			$tem_selitetark_3 = trim($t[$i]);
		}

		if (mysql_field_name($result, $i) == 'tunnus') {

			$query = "	SELECT kieli, selite, laji
						FROM avainsana
						WHERE yhtio 		= '$kukarow[yhtio]'
						AND kieli   		= '$tem_kieli'
						AND laji    		= '$tem_laji'
						AND nakyvyys    	= '$tem_nakyvyys'
						AND selite  		= '$tem_selite'
						AND selitetark  	= '$tem_selitetark'
						AND selitetark_2	= '$tem_selitetark_2'
						AND selitetark_3	= '$tem_selitetark_3'
						and tunnus 		   != '$tunnus'";
			$results = pupe_query($query);

			if (mysql_num_rows($results) > 0) {
				$virhe[$seliteind] = $virhe[$i] = t("Avainsana annetuilla tiedoilla l�ytyy kannasta. Duplikaatit ovat kiellettyj�!");
			}
		}
	}
}
