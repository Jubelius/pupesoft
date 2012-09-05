<?php

$_GET['ohje'] = 'off';
$_GET['no_css'] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if(!isset($errors)) $errors = array();
if(!isset($tee)) $tee = '';

# Uusi suuntalava
# form.php / uusi
if (isset($uusi)) {
	$title = "Uusi suuntalava";

	# Haetaan tyypit
	$query = "	SELECT *
				FROM pakkaus
				WHERE yhtio = '{$kukarow['yhtio']}'";
	$pakkaus_result = pupe_query($query);

	while($rivi = mysql_fetch_assoc($pakkaus_result)) {
		$pakkaukset[] = $rivi;
	}

	# Haetaan keräysvyöhykkeet
	$keraysvyohyke_query = "SELECT tunnus, nimitys
							FROM keraysvyohyke
							WHERE yhtio = '{$kukarow['yhtio']}' AND nimitys != ''";
	$keraysvyohyke_result = pupe_query($keraysvyohyke_query);

	while($rivi = mysql_fetch_assoc($keraysvyohyke_result)) {
		$keraysvyohykkeet[] = $rivi;
	}

	# Uuden suuntalavan luominen
	if ($submit) {
		# Tarkistetaan parametrit
		if(!isset($kaytettavyys) or !isset($terminaalialue) or !isset($sallitaanko)) {
		 	$errors[] = "Virheelliset parametrit";
		}

		# Jos ei virheitä luodaan uusi suuntalava
		if(count($errors) == 0) {
			# TODO: Suuntalavan luominen ilman saapumista
			$otunnus = "";

			$tee = "eihalutamitankayttoliittymaapliis";

			# TODO: SSCC:n generointi
			$params = array(
					'sscc' => "TEE",
					'tyyppi' => $pakkaus,
					'keraysvyohyke' => $keraysvyohyke,
					'kaytettavyys' => $kaytettavyys,
					'usea_keraysvyohyke' => $sallitaanko,
					'hyllyalue' => $hyllyalue,
					'terminaalialue' => $terminaalialue
				);

			require ("../tilauskasittely/suuntalavat.inc");

			echo "lisaa_suuntalava(:saapuminen => $saapuminen, :params => $params)";
			$uusi_suuntalava = lisaa_suuntalava($otunnus, $params);
			echo "<br>Lisättiin lava! ".$uusi_suuntalava;
			echo "<pre>";
			var_dump($params);
			echo "</pre>";
		}
	}

	include('views/suuntalavat/form.php');
}
# Päivitetään suuntalava
# form.php / update
else if (isset($muokkaa) and is_numeric($muokkaa)) {

	# Tyyppi
	$query = "	SELECT *
				FROM pakkaus
				WHERE yhtio = '{$kukarow['yhtio']}'";
	$pakkaus_result = pupe_query($query);

	while($rivi = mysql_fetch_assoc($pakkaus_result)) {
		$pakkaukset[] = $rivi;
	}

	# Keräysvyöhyke
	$keraysvyohyke_query = "SELECT tunnus, nimitys
							FROM keraysvyohyke
							WHERE yhtio = '{$kukarow['yhtio']}' AND nimitys != ''";
	$keraysvyohyke_result = pupe_query($keraysvyohyke_query);

	while($rivi = mysql_fetch_assoc($keraysvyohyke_result)) {
		$keraysvyohykkeet[] = $rivi;
	}

	# Jos suuntalavalle on ehditty listätä tuotteita, disabloidaan keräysvyöhyke ja hyllyalue
	$query = "SELECT tunnus FROM tilausrivi WHERE suuntalava = '{$muokkaa}' and yhtio='artr'";
	$result = pupe_query($query);
	$disabled = (mysql_num_rows($result) != 0) ? ' disabled' : '';

	# Suuntalavan tiedot
	$query = "	SELECT
				suuntalavat.*,
				pakkaus.pakkaus,
				pakkaus.tunnus as ptunnus
				FROM suuntalavat
				LEFT JOIN pakkaus on (pakkaus.tunnus=suuntalavat.tyyppi)
				WHERE suuntalavat.tunnus='$muokkaa' and suuntalavat.yhtio='{$kukarow['yhtio']}'";
	$result = pupe_query($query);
	if(!$suuntalava = mysql_fetch_assoc($result)) exit("Virheellinen suuntalavan tunnus");

	echo "Alkuperäinen suuntalava:<pre>";
	var_dump($suuntalava);
	echo "</pre>";

	# Suuntalavan päivitys
	if ($submit) {

		# Tarkistetaan parametrit
		if(!isset($kaytettavyys) or !isset($terminaalialue) or !isset($sallitaanko)) {
		 	$errors[] = "Virheelliset parametrit";
		}

		# Jos ei virheitä niin päivitetään suuntalava
		if(count($errors) == 0) {
			$params = array(
					'suuntalavan_tunnus'	=> $suuntalava['tunnus'],
					'sscc'					=> $suuntalava['sscc'],
					'alkuhyllyalue'	 		=> '',
					'alukuhyllynro'	 		=> '',
					'alkuhyllyvali'	 		=> '',
					'alkuhyllytaso'	 		=> '',
					'loppuhyllyalue'		=> '',
					'loppuhyllnro'	 		=> '',
					'loppuhyllyvali'	 	=> '',
					'loppuhyllytaso'	 	=> '',
					'tyyppi'				=> $tyyppi,
					'keraysvyohyke'	 		=> $keraysvyohyke,
					'kaytettavyys'	 		=> $kaytettavyys,
					'terminaalialue'	 	=> $terminaalialue,
					'korkeus'	 			=> '',
					'paino'					=> '',
					'usea_keraysvyohyke'	=> $sallitaanko,
					'hyllyalue'	 			=> $hyllyalue
				);

			# TODO: Saapumisen hallinta
			#$otunnus = hae_saapumiset($suuntalava['tunnus']);

			# Ei tarvita käyttöliittymää
			$suuntalavat_ei_kayttoliittymaa = 'KYLLA';
			$tee = "eihalutamitankayttoliittymaapliis";
			$otunnus = '';

			require ("../tilauskasittely/suuntalavat.inc");
			echo "<br>Päivitetiin suuntalava lisaa_suuntalava(:saapuminen => $otunnus, :params => $params)";
			echo "<br>uusi suuntalava tunnus: ".lisaa_suuntalava($otunnus, $params);

			# Takaisin suuntalavat listaan
			echo "<META HTTP-EQUIV='Refresh'CONTENT='3;URL=suuntalavat.php'>";
			exit();
		}
	}
	include('views/suuntalavat/form.php');
}
# Suuntalava siirtovalmiiksi
#
else if ($tee == 'siirtovalmis' and isset($suuntalava)) {
	$title = t("Suuntalava siirtovalmiiksi");

	#$suuntalavat_ei_kayttoliittymaa = "KYLLA";
	#$tee = 'siirtovalmis';
	#$suuntalavan_tunnus = $suuntalava;
	#require ("../tilauskasittely/suuntalavat.inc");

	#include('views/suuntalavat/index.php');
}

# Lista suuntalavoista
# index.php
else {

	$title = t("Suuntalavat");
	$suuntalavat = array();

	$hakuehto = !empty($hae) ? "and suuntalavat.sscc = '".mysql_real_escape_string($hae)."'" : "";

	# Haetaan 'validit' suuntalavat
	$query = "	SELECT
				suuntalavat.sscc,
				ifnull(keraysvyohyke.nimitys, '-') as keraysvyohyke,
				ifnull(pakkaus.pakkaus, '-') as tyyppi,
				count(tilausrivi.tunnus) as rivit,
				suuntalavat.tunnus
				FROM suuntalavat
				LEFT JOIN tilausrivi on (tilausrivi.yhtio = suuntalavat.yhtio and tilausrivi.suuntalava = suuntalavat.tunnus)
				LEFT JOIN pakkaus on (pakkaus.tunnus = suuntalavat.tyyppi)
				LEFT JOIN keraysvyohyke on (keraysvyohyke.tunnus = suuntalavat.keraysvyohyke)
				WHERE suuntalavat.tila='' and suuntalavat.sscc!='Suoratoimitus' $hakuehto and suuntalavat.yhtio='{$kukarow['yhtio']}'
				GROUP BY 1,2,3";
	$result = pupe_query($query);

	while($rivi = mysql_fetch_assoc($result)) {
		$suuntalavat[] = $rivi;
	}

	if (empty($suuntalavat)) {
		$errors[] = "Suuntalavaa ei löytynyt.";
	}

	include('views/suuntalavat/index.php');
}

# Virheet
if (isset($errors)) {
	echo "<span class='error'>";
	foreach($errors as $virhe) {
		echo "{$virhe}<br>";
	}
	echo "</span>";
}

require('inc/footer.inc');