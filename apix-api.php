#!/usr/bin/php
<?php

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}

	$pupesoft_polku = dirname(__FILE__);

	// otetaan tietokanta connect
	require ($pupesoft_polku."/inc/connect.inc");

	if (!isset($verkkolaskut_in) or $verkkolaskut_in == "" or !is_dir($verkkolaskut_in)) {
		die("VIRHE: verkkolaskut_in-kansio ei ole määritelty!");
	}

	// Asetukset
	$software = "Pupesoft";
	$version  = "1.0";

	// Haetaan api_keyt yhtion_parametreistä
	$sql_query = "	SELECT yhtion_parametrit.apix_tunnus, yhtion_parametrit.apix_avain, yhtio.nimi
					FROM yhtio
					JOIN yhtion_parametrit USING (yhtio)
					WHERE yhtio.yhtio = '{$kukarow['yhtio']}'
					AND yhtion_parametrit.apix_tunnus != ''
					AND yhtion_parametrit.apix_avain != ''";
	$apix_result = mysql_query($sql_query) or die("Virhe SQL kyselyssä");

	if (mysql_num_rows($maventa_result) == 0) {
		die("Apixia käyttäviä yrityksiä ei löytynyt!");
	}

	while ($apix_keys = mysql_fetch_assoc($apix_result)) {

	#$url = "https://test-terminal.apix.fi/receive";
	$url = "https://terminal.apix.fi/receive";
		$timestamp	= gmdate("YmdHis");

		// Muodostetaan apixin vaatima salaus ja url
		$digest_src = "$software+$version+".$apix_keys['apix_tunnus']."+".$timestamp."+".$apix_keys['apix_avain'];
		$dt	= substr(hash("sha256", $digest_src), 0, 64);
		$real_url = "$url?TraID={$apix_keys['apix_tunnus']}&t=$timestamp&soft=$software&ver=$version&d=SHA-256:$dt";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $real_url);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);
		curl_close($ch);

		if ($response != '') {
			$tiedosto = $verkkolaskut_in."apix_".md5(uniqid(mt_rand(), true))."_nimi.zip";
			$fd = fopen($tiedosto, "w") or die("Tiedostoa ei voitu luoda!");
			fwrite($fd, $response);

			$zip = new ZipArchive();

			if ($zip->open($tiedosto) === TRUE) {
				// Loopataan tiedostot läpi
				for ($i = 0; $i < $zip->numFiles; $i++) {
					$file = $zip->getNameIndex($i);

					// Puretaan vain .xml tiedostot ja nimetään se uudelleen.
					if ($zip->extractTo($verkkolaskut_in, substr($file, 0, -4).".xml")) {
						rename($verkkolaskut_in.substr($file, 0, -4).".xml", $verkkolaskut_in."apix_".md5(uniqid(mt_rand(), true)).".xml");
						echo "Haettiin lasku yritykselle: {$apix_keys['nimi']}\n";
					}
				}

				fclose($fd);

				// Poistetaan zippi
				unlink($tiedosto);
			}
			else {
				echo "Virhe, tiedoston purussa!\n";
			}
		}
		else {
			continue;
		}
	}
?>