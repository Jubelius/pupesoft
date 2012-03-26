<?php

	$query = "	SELECT otunnus, tilausrivi, sscc, sscc_ulkoinen, kpl
				FROM kerayserat
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND nro 	= '{$kerayseran_numero}'
				AND tila    = 'K'";
	$kardex_res = pupe_query($query);

	if (mysql_num_rows($kardex_res) > 0) {

		$sisalto   = "";
		$kardex_ok = TRUE;

		while ($kardex_row = mysql_fetch_assoc($kardex_res)) {

			$query = "	SELECT toimitustapa
						FROM lasku
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$kardex_row['otunnus']}'";
			$lahto_chk_res = pupe_query($query);
			$lahto_chk_row = mysql_fetch_assoc($lahto_chk_res);

			// haetaan toimitustavan tiedot
			$query = "	SELECT rahtikirja
						FROM toimitustapa
						WHERE yhtio = '$kukarow[yhtio]'
						AND selite  = '{$lahto_chk_row['toimitustapa']}'";
			$toitares = pupe_query($query);
			$toitarow = mysql_fetch_assoc($toitares);

			if (($toitarow["rahtikirja"] == 'rahtikirja_unifaun_ps_siirto.inc' and $unifaun_ps_host != "" and $unifaun_ps_user != "" and $unifaun_ps_pass != "" and $unifaun_ps_path != "") OR
				($toitarow["rahtikirja"] == 'rahtikirja_unifaun_uo_siirto.inc' and $unifaun_uo_host != "" and $unifaun_uo_user != "" and $unifaun_uo_pass != "" and $unifaun_uo_path != "")) {

				// Jos Unifaun on k�yt�ss� niin ulkoinen SSCC ei saa olla tyhj�
				if ((is_numeric($kardex_row['sscc_ulkoinen']) and (int) $kardex_row['sscc_ulkoinen'] == 0) or (!is_numeric($kardex_row['sscc_ulkoinen']) and (string) $kardex_row['sscc_ulkoinen'] == "")) {
					$kardex_ok = FALSE;
					break;
				}

				$kardex_sscc = $kardex_row['sscc_ulkoinen'];
			}
			else {
				$kardex_sscc = $kardex_row['sscc'];
			}

			$query = "	SELECT tunnus, tuoteno, nimitys, CONCAT(hyllyalue, '-', hyllynro, '-', hyllyvali, '-', hyllytaso) AS hyllypaikka
						FROM tilausrivi
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$chk_sscc_row['otunnus']}'";
			$tilriv_tiedot_res = pupe_query($query);
			$tilriv_tiedot_row = mysql_fetch_assoc($tilriv_tiedot_res);

			// index 0, tilaustyyppi = aina 4
			// index 1, pakkaus
			// index 2, tuoteno
			// index 3, nimitys
			// index 4, kpl
			// index 5, hyllypaikka
			// index 6, tilausrivin tunnus

			$sisalto .= "4;{$kardex_sscc};{$tilriv_tiedot_row['tuoteno']};{$tilriv_tiedot_row['nimitys']};-{$kardex_row['kpl']};{$tilriv_tiedot_row['hyllypaikka']};{$tilriv_tiedot_row['tunnus']};\r\n";
		}

		if ($kardex_ok) {
			$kardexnimi = "/tmp/kardex-".md5(uniqid(mt_rand(), true)).".txt";
			$fh = fopen($kardexnimi, "w+");
			fputs($fh, $sisalto);
			fclose($fh);

			$ftphost = $kardex_host;
			$ftpuser = $kardex_user;
			$ftppass = $kardex_pass;
			$ftppath = $kardex_path;
			$ftpport = $kardex_port;
		    $ftpfail = $kardex_fail;
			$ftpfile = $kardexnimi;

			require('inc/ftp-send.inc');
		}
		elseif ($kardex_fail_sscc != "") {
			// Ty�nnet��n uudelleenk�sitelt�vien joukkoon jos ulkoinen SSCC puuttuu
			$kardexnimi = $kardex_fail_sscc."/".$kerayseran_numero.".txt";

			$fh = fopen($kardexnimi, "w+");
			fputs($fh, $kerayseran_numero);
			fclose($fh);
		}
	}
?>