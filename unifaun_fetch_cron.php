<?php

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}

	if (trim($argv[1]) == '') {
		echo "Et antanut yhtiötä!\n";
		exit;
	}

	require ("inc/connect.inc");
	require ("inc/functions.inc");

	if (trim($unifaun_retd) == '') {
		echo "Unifaun return-kansio puuttuu!\n";
		exit;
	}

	if (!is_dir($unifaun_retd)) {
		echo "Unifaun return-kansio virheellinen!\n";
		exit;
	}

	$kukarow['yhtio'] = (string) $argv[1];
	$kukarow['kuka']  = 'cron';
	$kukarow['kieli'] = 'fi';

	$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

	if ($handle = opendir($unifaun_retd)) {

		while (($file = readdir($handle)) !== FALSE) {

			if (is_file($unifaun_retd."/".$file)) {

				/*
 				 * pupessa tilausnumerona lähetettiin tilausnumero_ssccvanha esim.: 6215821_1025616
				 */

				/* Normaalisanoma ilman viitettä
				 * tilnro;sscc_ulkoinen;rahtikirjanro;datetime
				 * 12345;373325380188609457;1000017762;2012-01-20 13:51:50
				 */

				/* Normaalisanoma viitteen kanssa
				 * tilnro;sscc_ulkoinen;rahtikirjanro;datetime;reference
				 * 12345;373325380188609457;1000017762;2012-01-20 13:51:50;77777777
				 */

				/* Sanomien erikoiskeissit (Itella, TNT, DPD, Matkahuolto)
				 * tilnro;ensimmäinen kollitunniste on lähetysnumero;sama ensimmäinen kollitunniste on rahtikirjanumerona;timestamp
				 * 199188177;MA1234567810000009586;MA1234567810000009586;2012-01-23 10:58:57 (Kimi: MAtkahuolto)
				 *
				 * tilnro;sscc_ulkoinen;LOGY rahtikirjanro;timestamp
				 * 12345;373325380188816602;200049424052;2012-01-23 10:59:03 (Kimi: Kaukokiito, Kiitolinja ja Vr Transpoint; SSCC + LOGY-rahtikirjanumero)
				 *
				 *
				 * 555555;JJFI65432110000070773;;2012-01-24 11:12:56; (Kimi: Itella)
				 *
				 *
				 * 14656099734;1;GE249908410WW;2012-01-24 11:12:49;52146882 (Kimi: TNT)
				 */

				list($tilausnumero_sscc, $sscc_ulkoinen, $rahtikirjanro, $timestamp, $viite) = explode(";", file_get_contents($unifaun_retd."/".$file));

				$sscc_ulkoinen = $sscc_ulkoinen == 1 ? 0 : $sscc_ulkoinen;

				list($tilausnumero, $sscc) = explode("_", $tilausnumero_sscc);

				$query = "	UPDATE kerayserat SET
							sscc_ulkoinen = '{$sscc_ulkoinen}',
							muutospvm = now(),
							muuttaja = 'cron'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND sscc = '{$sscc}'
							AND otunnus = '{$tilausnumero}'";
				$upd_res = pupe_query($query);

				rename($unifaun_retd."/".$file, $unifaun_retd."/ok/".$file);
			}
		}
	}
