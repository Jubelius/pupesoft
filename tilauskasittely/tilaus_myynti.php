<?php

if (file_exists("../inc/parametrit.inc")) {
	require ("../inc/parametrit.inc");
}
else {
	require ("parametrit.inc");
}

if (file_exists("../inc/alvpopup.inc")) {
	require ("../inc/alvpopup.inc");
}
else {
	require ("alvpopup.inc");
}

if (($kukarow["extranet"] != '' and $toim != 'EXTRANET') or ($kukarow["extranet"] == "" and $toim == "EXTRANET")) {
	//aika j�nn� homma jos t�nne jouduttiin
	exit;
}

// Extranet keississ� asiakasnumero tulee k�ytt�j�n takaa
// Haetaan asiakkaan tunnuksella
if ($kukarow["extranet"] != '') {
	$query  = "	SELECT *
				FROM asiakas
				WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		$extra_asiakas = mysql_fetch_array($result);
		$ytunnus = $extra_asiakas["ytunnus"];
		$asiakasid = $extra_asiakas["tunnus"];

		if ($kukarow["kesken"] != 0) {
			$tilausnumero = $kukarow["kesken"];
		}
	}
	else {
		echo t("VIRHE: K�ytt�j�tiedoissasi on virhe! Ota yhteys j�rjestelm�n yll�pit�j��n.")."<br><br>";
		exit;
	}
}


//jos jostain tullaan ilman $toim-muuttujaa
if ($toim == "") {
	$toim = "RIVISYOTTO";
}
elseif ($toim == "EXTRANET") {
	$otsikko = t("Extranet-Tilaus");
}
elseif ($toim == "TYOMAARAYS") {
	$otsikko = t("Ty�m��r�ys");
}
elseif ($toim == "VALMISTAVARASTOON") {
	$otsikko = t("Varastoonvalmistus");
}
elseif ($toim == "SIIRTOLISTA") {
	$otsikko = t("Varastosiirto");
}
elseif ($toim == "MYYNTITILI") {
	$otsikko = t("Myyntitili");
}
elseif ($toim == "VALMISTAASIAKKAALLE") {
	$otsikko = t("Asiakkaallevalmistus");
}
elseif ($toim == "TARJOUS") {
	$otsikko = t("Tarjous");
}
else {
	$otsikko = t("Myyntitilaus");
}

//korjataan hintaa ja aleprossaa
$hinta	= str_replace(',','.',$hinta);
$ale 	= str_replace(',','.',$ale);
$kpl 	= str_replace(',','.',$kpl);

// jos ei olla postattu mit��n, niin halutaan varmaan tehd� kokonaan uusi tilaus..
if ($kukarow["extranet"] == "" and count($_POST) == 0 and $from == '') {
	$tila				= '';
	$tilausnumero		= '';
	$laskurow			= '';
	$kukarow["kesken"]	= '';

	//varmistellaan ettei vanhat kummittele...
	$query	= "update kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
	$result = mysql_query($query) or pupe_error($query);
}

// asiakasnumero on annettu, etsit��n tietokannasta...
if (($kukarow["extranet"] != "" and $kukarow["kesken"] == 0) or ($kukarow["extranet"] == "" and $toim == "PIKATILAUS" and ($syotetty_ytunnus != '' or $asiakasid != ''))) {

	if (substr($ytunnus,0,1) == "�") {
		$ytunnus = $asiakasid;
	}
	else {
		$ytunnus = $syotetty_ytunnus;
	}

	if (file_exists("../inc/asiakashaku.inc")) {
		require ("../inc/asiakashaku.inc");
	}
	else {
		require ("asiakashaku.inc");
	}
}

//Luodaan otsikko
if (($toim == "PIKATILAUS" and (((int) $kukarow["kesken"] == 0 and $tuoteno != '') or $asiakasid != '')) or ($kukarow["extranet"] != "" and (int) $kukarow["kesken"] == 0)) {

	if ($ytunnus != '') {
		$nimi 			= $asiakasrow["nimi"];
		$nimitark 		= $asiakasrow["nimitark"];
		$osoite 		= $asiakasrow["osoite"];
		$postino 		= $asiakasrow["postino"];
		$postitp 		= $asiakasrow["postitp"];
		$maa 			= $asiakasrow["maa"];
		$tnimi 			= $asiakasrow["toim_nimi"];
		$tnimitark 		= $asiakasrow["toim_nimitark"];
		$tosoite 		= $asiakasrow["toim_osoite"];
		$tpostino 		= $asiakasrow["toim_postino"];
		$tpostitp 		= $asiakasrow["toim_postitp"];
		$toim_maa 		= $asiakasrow["toim_maa"];
		$verkkotunnus 	= $asiakasrow["verkkotunnus"];
		$poistumistoimipaikka_koodi       = $asiakasrow["poistumistoimipaikka_koodi"];
		$kuljetusmuoto                    = $asiakasrow["kuljetusmuoto"];
		$kauppatapahtuman_luonne          = $asiakasrow["kauppatapahtuman_luonne"];
		$aktiivinen_kuljetus_kansallisuus = $asiakasrow["aktiivinen_kuljetus_kansallisuus"];
		$aktiivinen_kuljetus              = $asiakasrow["aktiivinen_kuljetus"];
		$kontti                           = $asiakasrow["kontti"];
		$sisamaan_kuljetusmuoto           = $asiakasrow["sisamaan_kuljetusmuoto"];
		$sisamaan_kuljetus_kansallisuus   = $asiakasrow["sisamaan_kuljetus_kansallisuus"];
		$sisamaan_kuljetus                = $asiakasrow["sisamaan_kuljetus"];
		$maa_maara                        = $asiakasrow["maa_maara"];

		if($asiakasrow["spec_ytunnus"] != '') {
			$ytunnus 				= $asiakasrow["spec_ytunnus"];
			$asiakasrow["ytunnus"] 	= $asiakasrow["spec_ytunnus"];
		}

		if($asiakasrow["spec_tunnus"] != '') {
			$asiakasid 				= $asiakasrow["spec_tunnus"];
			$asiakasrow["tunnus"] 	= $asiakasrow["spec_tunnus"];
		}

		$toimvv = date("Y");
		$toimkk = date("m");
		$toimpp = date("d");

		$kervv = date("Y");
		$kerkk = date("m");
		$kerpp = date("d");

		$maksuehto 		= $asiakasrow["maksuehto"];
		$toimitustapa 	= $asiakasrow["toimitustapa"];

		// haetaan tomitustavan oletusmaksajan tiedot
		$apuqu = "	select *
					from toimitustapa use index (selite_index)
					where yhtio='$kukarow[yhtio]' and selite='$asiakasrow[toimitustapa]'";
		$meapu = mysql_query($apuqu) or pupe_error($apuqu);
		$apuro = mysql_fetch_array($meapu);
		$maksaja = $apuro['merahti'];

		if ($kukarow["myyja"] == 0) {
			$myyja = $kukarow["tunnus"];
		}
		else {
			$myyja = $kukarow["myyja"];
		}

		$alv 			= $asiakasrow["alv"];
		$ovttunnus 		= $asiakasrow["ovttunnus"];
		$toim_ovttunnus = $asiakasrow["toim_ovttunnus"];
		$chn 			= $asiakasrow["chn"];
		$maksuteksti 	= $asiakasrow[""];
		$tilausvahvistus= $asiakasrow["tilausvahvistus"];
		$laskutusvkopv 	= $asiakasrow["laskutusvkopv"];
		$vienti 		= $asiakasrow["vienti"];
		$ketjutus 		= $asiakasrow["ketjutus"];
		$valkoodi 		= $asiakasrow["oletus_valkoodi"];

		//annetaan extranet-tilaukselle aina paras prioriteetti, t�m� on hyv� porkkana.
		if ($kukarow["extranet"] != '') {
			$query  = "	SELECT distinct selite
						FROM avainsana
						WHERE yhtio='$kukarow[yhtio]' and laji = 'asiakasluokka' and selite != ''
						ORDER BY 1
						LIMIT 1";
			$prioresult = mysql_query($query) or pupe_error($query);
			$priorow = mysql_fetch_array($prioresult);

			$luokka 	= $priorow["selite"];
		}
		else {
			$luokka		= $asiakasrow["luokka"];
		}

		$erikoisale		= $asiakasrow["erikoisale"];

		$varasto = (int) $kukarow["varasto"];

	}
	else {
		//yhti�n oletusalvi!
		$xwquery = "select selite from avainsana where yhtio='$kukarow[yhtio]' and laji='alv' and selitetark!=''";
		$xwtres  = mysql_query($xwquery) or pupe_error($xwquery);
		$xwtrow  = mysql_fetch_array($xwtres);

		$alv = (float) $xwtrow["selite"];

		$ytunnus = "WEKAROTO";
		$varasto = (int) $kukarow["varasto"];
	}

	if ($valkoodi == '') {
		$valkoodi = $yhtiorow["valkoodi"];
	}

	$jatka	= "JATKA";
	$tee 	= "OTSIK";
	$override_ytunnus_check = "YES";

	require ("otsik.inc");
}

//Haetaan otsikon kaikki tiedot
if ((int) $kukarow["kesken"] != 0) {

	if ($kukarow["extranet"] == "" and $toim == "TYOMAARAYS") {
		$query  = "	select *
					from lasku, tyomaarays
					where lasku.tunnus='$kukarow[kesken]'
					and lasku.yhtio='$kukarow[yhtio]'
					and tyomaarays.yhtio=lasku.yhtio
					and tyomaarays.otunnus=lasku.tunnus";
	}
	else {
		$query 	= "	select *
					from lasku
					where tunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
	}
	$result  	= mysql_query($query) or pupe_error($query);
	$laskurow   = mysql_fetch_array($result);
}

//tietyiss� keisseiss� tilaus lukitaan (ei sy�tt�rivi� eik� muota muokkaa/poista-nappuloita)
$muokkauslukko = "";

if ($kukarow["extranet"] == "" and $toim == "MYYNTITILI" and $laskurow["alatila"] == "V") {
	$muokkauslukko = "LUKOSSA";
}



// Hyv�ksyt��n tajous ja tehd��n tilaukset
if ($kukarow["extranet"] == "" and $tee == "HYVAKSYTARJOUS") {

	///* Reload ja back-nappulatsekki *///
	if ($kukarow["kesken"] == '' or $kukarow["kesken"] == '0') {
		echo "<font class='error'> ".t("�l� perkele runkkaa systeemii")."! </font>";
		exit;
	}

	//Luodaan valituista riveist� suoraan normaali ostotilaus
	require("tilauksesta_ostotilaus.inc");

	$tilauksesta_ostotilaus = tilauksesta_ostotilaus($kukarow["kesken"]);
	if ($tilauksesta_ostotilaus != '') echo "$tilauksesta_ostotilaus<br><br>";

	// katsotaan ollaanko tehty JT-supereita..
	require("jt_super.inc");

	$jtsuper = jt_super($kukarow["kesken"]);
	if ($jtsuper != '') echo "$jtsuper<br><br>";

	// Kopsataan valitut rivit uudelle myyntitilaukselle
	require("tilauksesta_myyntitilaus.inc");


	//Ensin kaikki rivit paitsi k�siraha
	$tilrivilisa = " and (tuoteno!='$yhtiorow[ennakkomaksu_tuotenumero]' or (tuoteno='$yhtiorow[ennakkomaksu_tuotenumero]' and varattu < 0 )) ";

	$tilauksesta_myyntitilaus = tilauksesta_myyntitilaus($kukarow["kesken"], $tilrivilisa, '', '');
	if ($tilauksesta_myyntitilaus != '') echo "$tilauksesta_myyntitilaus<br><br>";

	//Ja sitten k�siraha omalle otsikolle
	$tilrivilisa = " and tuoteno='$yhtiorow[ennakkomaksu_tuotenumero]' and varattu > 0 ";

	$tilauksesta_myyntitilaus = tilauksesta_myyntitilaus($kukarow["kesken"], $tilrivilisa, '', '');
	if ($tilauksesta_myyntitilaus != '') echo "$tilauksesta_myyntitilaus<br><br>";


	$query = "UPDATE lasku SET alatila='B' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
	$result = mysql_query($query) or pupe_error($query);

	$query	= "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
	$result = mysql_query($query) or pupe_error($query);

	$aika=date("d.m.y @ G:i:s", time());
	echo "<font class='message'>$otsikko $kukarow[kesken] ".t("valmis")."!</font><br><br>";

	$tee				= '';
	$tilausnumero		= '';
	$laskurow			= '';
	$kukarow['kesken']	= '';
}

// Hyl�t��n tarjous
if ($kukarow["extranet"] == "" and $tee == "HYLKAATARJOUS") {

	///* Reload ja back-nappulatsekki *///
	if ($kukarow["kesken"] == '' or $kukarow["kesken"] == '0') {
		echo "<font class='error'> ".t("�l� perkele runkkaa systeemii")."! </font>";
		exit;
	}

	$query = "UPDATE lasku SET alatila='X' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
	$result = mysql_query($query) or pupe_error($query);

	$query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]'";
	$result = mysql_query($query) or pupe_error($query);

	$query	= "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
	$result = mysql_query($query) or pupe_error($query);

	$aika=date("d.m.y @ G:i:s", time());
	echo "<font class='message'>$otsikko $kukarow[kesken] ".t("valmis")."!</font><br><br>";

	$tee				= '';
	$tilausnumero		= '';
	$laskurow			= '';
	$kukarow['kesken']	= '';
}

// Laskutetaan myyntitili
if ($kukarow["extranet"] == "" and $tee == "LASKUTAMYYNTITILI") {
	$tilatapa = "LASKUTA";

	require ("laskuta_myyntitilirivi.inc");
}

// Laitetaan myyntitili takaisin lep��m��n
if ($kukarow["extranet"] == "" and $tee == "LEPAAMYYNTITILI") {
	$tilatapa = "LEPAA";

	require ("laskuta_myyntitilirivi.inc");
}

// Poistetaan tilaus
if ($tee == 'POISTA') {

	// poistetaan tilausrivit, mutta j�tet��n PUUTE rivit analyysej� varten...
	$query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]' and var<>'P'";
	$result = mysql_query($query) or pupe_error($query);

	$query = "UPDATE lasku SET tila='D', alatila='L', comments='$kukarow[nimi] ($kukarow[kuka]) ".t("mit�t�i tilauksen")." ".date("d.m.y @ G:i:s")."' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
	$result = mysql_query($query) or pupe_error($query);

	$query	= "update kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
	$result = mysql_query($query) or pupe_error($query);

	if ($kukarow["extranet"] == "") {
		echo "<font class='message'>".t("Tilaus")." $kukarow[kesken] ".t("mit�t�ity")."!</font><br><br>";
	}

	$tee				= '';
	$tilausnumero		= '';
	$laskurow			= '';
	$kukarow['kesken']	= '';

	if ($kukarow["extranet"] != "") {
		echo "<font class='head'>$otsikko</font><hr><br><br>";
		echo "<font class='message'>".t("Tilauksesi poistettiin")."!</font><br><br>";

		$tee = "SKIPPAAKAIKKI";
	}
}

//Lis�t��n t�n asiakkaan valitut JT-rivit t�lle tilaukselle
if ($tee == "JT_TILAUKSELLE" and $tila == "jttilaukseen") {
	$tilaus_on_jo 	= "KYLLA";

	require("jtselaus.php");

	$tyhjenna 	= "JOO";
	$tee 		= "";
}

//Tyhjennt��n sy�tt�kent�t
if (isset($tyhjenna)) {
	$tuoteno	= '';
	$kpl		= '';
	$var		= '';
	$hinta		= '';
	$netto		= '';
	$ale		= '';
	$rivitunnus	= '';
	$kommentti	= '';
	$kerayspvm	= '';
	$toimaika	= '';
	$paikka		= '';
	$paikat		= '';
	$alv		= '';
}

// Tilaus valmis
if ($tee == "VALMIS") {

	///* Reload ja back-nappulatsekki *///
	if ($kukarow["kesken"] == '' or $kukarow["kesken"] == '0') {
		echo "<font class='error'> ".t("�l� perkele runkkaa systeemii")."! </font>";
		exit;
	}

	// Tulostetaan tarjous
	if($kukarow["extranet"] == "" and $toim == "TARJOUS") {
		//Tulostetaan valitut paperit
		$otunnus = $laskurow["tunnus"];
		$komento["Tarjous"] 			= "lpr -P atkxerox";
		$komento["Myyntisopimus"]		= "lpr -P atkxerox";
		$komento["Osamaksusopimus"]		= "lpr -P atkxerox";
		$komento["Luovutustodistus"]	= "lpr -P atkxerox";


		require_once ("tulosta_tarjous.inc");

		tulosta_tarjous($otunnus, $komento["Tarjous"], $kieli,  $tee);

		require_once ("tulosta_myyntisopimus.inc");

		tulosta_myyntisopimus($otunnus, $komento["Myyntisopimus"], $kieli, $tee);

		require_once ("tulosta_luovutustodistus.inc");

		tulosta_luovutustodistus($otunnus, $komento["Luovutustodistus"], $kieli, $tee);

		require_once ("tulosta_osamaksusoppari.inc");

		tulosta_osamaksusoppari($otunnus, $komento["Osamaksusopimus"], $kieli, $tee);

		$query = "UPDATE lasku SET alatila='A' where yhtio='$kukarow[yhtio]' and alatila='' and tunnus='$kukarow[kesken]'";
		$result = mysql_query($query) or pupe_error($query);

		// Tehd��n asiakasmemotapahtuma
		$kysely = "	INSERT INTO kalenteri
					SET tapa 		= 'Tarjous asiakkaalle',
					asiakas  	 	= '$laskurow[ytunnus]',
					liitostunnus	= '$laskurow[liitostunnus]',
					henkilo  		= '',
					kuka     		= '$kukarow[kuka]',
					yhtio    		= '$kukarow[yhtio]',
					tyyppi   		= 'Memo',
					pvmalku  		= now(),
					kentta01 		='Tarjous $laskurow[tunnus] tulostettu.\n$laskurow[viesti]\n$laskurow[comments]\n$laskurow[sisviesti2]'";
		$result = mysql_query($kysely) or pupe_error($kysely);

		// Tehd��n myyj�lle muistutus
		$kysely = "	INSERT INTO kalenteri
					SET
					asiakas  	 	= '$laskurow[ytunnus]',
					liitostunnus	= '$laskurow[liitostunnus]',
					kuka     		= '$kukarow[kuka]',
					yhtio    		= '$kukarow[yhtio]',
					tyyppi   		= 'Muistutus',
					tapa     		= 'Tarjous asiakkaalle',
					kentta01 		= 'Muista tarjous $laskurow[tunnus]!',
					kuittaus 		= 'K',
					pvmalku  		= date_add(now(), INTERVAL 7 day)";
		$result = mysql_query($kysely) or pupe_error($kysely);

		$query	= "update kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
		$result = mysql_query($query) or pupe_error($query);
	}
	// Ty�m��r�ys valmis
	elseif ($kukarow["extranet"] == "" and $toim == "TYOMAARAYS") {
		require("../tyomaarays/tyomaarays.inc");
	}
	// Siirtolista, myyntitili, valmistus valmis
	elseif ($kukarow["extranet"] == "" and ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON" or $toim == "SIIRTOLISTA" or $toim == "MYYNTITILI")) {
		require ("tilaus-valmis-siirtolista.inc");
	}
	// Myyntitilaus valmis
	else {

		//Jos k�ytt�j� on extranettaaja ja h�n ostellut tuotteita useista eri maista niin laitetaan tilaus holdiin
		if ($kukarow["extranet"] != "" and $toimitetaan_ulkomaailta == "YES") {
			$kukarow["taso"] = 2;
		}

		//katotaan onko asiakkaalla yli 30 p�iv�� vanhoja maksamattomia laskuja
		if ($kukarow['extranet'] != '' and ($kukarow['saatavat'] == 0 or $kukarow['saatavat'] == 2)) {
			$saaquery =	"SELECT
						lasku.ytunnus,
						sum(if(TO_DAYS(NOW())-TO_DAYS(erpcm) > 30, summa-saldo_maksettu, 0)) dd
						FROM lasku use index (yhtio_tila_mapvm)
						WHERE tila = 'U'
						AND alatila = 'X'
						AND mapvm = '0000-00-00'
						AND erpcm != '0000-00-00'
						AND lasku.ytunnus = '$laskurow[ytunnus]'
						AND lasku.yhtio = '$kukarow[yhtio]'
						GROUP BY 1
						ORDER BY 1";
			$saaresult = mysql_query($saaquery) or pupe_error($saaquery);
			$saarow = mysql_fetch_array($saaresult);

			//ja jos on niin ne siirret��n tilaus holdiin
			if ($saarow['dd'] > 0) {
				$kukarow["taso"] = 2;
			}
		}

		// Extranetk�ytt�j� jonka tilaukset on hyv�ksytett�v� meid�n myyjill�
		if ($kukarow["extranet"] != "" and $kukarow["taso"] == 2) {
			$query  = "	update lasku set
						tila = 'N',
						alatila='F'
						where yhtio='$kukarow[yhtio]'
						and tunnus='$kukarow[kesken]'
						and tila = 'N'
						and alatila = ''";
			$result = mysql_query($query) or pupe_error($query);


			// tilaus ei en�� kesken...
			$query	= "update kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
			$result = mysql_query($query) or pupe_error($query);

		}
		else {
			// katsotaan ollaanko tehty JT-supereita..
			require("jt_super.inc");
			$jt_super = jt_super($kukarow["kesken"]);

			if ($kukarow["extranet"] != "") {
				echo "$jt_super<br><br>";

				//Pyydet��n tilaus-valmista olla echomatta mit��n
				$silent = "SILENT";
			}

			// tulostetaan l�hetteet ja tilausvahvistukset tai sis�inen lasku..
			require("tilaus-valmis.inc");
		}
	}

	if ($kukarow["extranet"] == "") {
		$aika=date("d.m.y @ G:i:s", time());
		echo "<font class='message'>$otsikko $kukarow[kesken] ".t("valmis")."! ($aika) $kaikkiyhteensa $laskurow[valkoodi]</font><br><br>";
	}

	$tee				= '';
	$tilausnumero		= '';
	$laskurow			= '';
	$kukarow['kesken']	= '';

	if ($kukarow["extranet"] != "") {
		echo "<font class='head'>$otsikko</font><hr><br><br>";
		echo "<font class='message'>".t("Tilaus valmis. Kiitos tilauksestasi")."!</font><br><br>";

		$tee = "SKIPPAAKAIKKI";
	}
}

if ($kukarow["extranet"] == "" and $toim == "TYOMAARAYS" and ($tee == "VAHINKO" or $tee == "LEPAA")) {
	require("../tyomaarays/tyomaarays.inc");
}

//Muutetaan otsikkoa
if ($kukarow["extranet"] == "" and ($tee == "OTSIK" or (($toim == "MYYNTITILI" or $toim == "SIIRTOLISTA" or $toim == "RIVISYOTTO" or $toim == "TYOMAARAYS" or $toim == "TARJOUS" or $toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") and $laskurow["ytunnus"] == ''))) {

	//T�m� jotta my�s rivisy�t�n alkuhomma toimisi
	$tee = "OTSIK";

	if ($toim == "VALMISTAVARASTOON" or $toim == "SIIRTOLISTA") {
		require("otsik_siirtolista.inc");
	}
	else {
		require ('otsik.inc');
	}

	//T�ss� halutaan jo hakea uuden tilauksen tiedot
	$query   	= "	select *
					from lasku
					where tunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
	$result  	= mysql_query($query) or pupe_error($query);
	$laskurow   = mysql_fetch_array($result);
}

//lis�t��n rivej� tiedostosta
if ($tee == 'mikrotila' or $tee == 'file') {

	if ($kukarow["extranet"] == "" and $toim == "SIIRTOLISTA") {
		require('mikrotilaus_siirtolista.inc');
	}
	else {
		require('mikrotilaus.inc');
	}

	if($tee == 'Y') {
		$tee = "";
	}
}

if ($tee == 'osamaksusoppari') {
	require('osamaksusoppari.inc');
}



if ($kukarow["extranet"] == "" and $tee == 'jyvita') {
	require("jyvita_riveille.inc");
}


// n�ytet��n tilaus-ruutu...
if ($tee == '') {
	$focus = "tuotenumero";
	$formi = "tilaus";

	echo "<font class='head'>$otsikko</font><hr>";

	//katsotaan ett� kukarow kesken ja $kukarow[kesken] stemmaavat kesken��n
	if ($tilausnumero != $kukarow["kesken"] and ($tilausnumero != '' or (int) $kukarow["kesken"] != 0) and $aktivoinnista != 'true') {
		echo "<br><br><br>".t("VIRHE: Sinulla on useita tilauksia auki")."! ".t("K�y aktivoimassa tilaus uudestaan Tilaukset-ohjelmasta").".<br><br><br>";
		exit;
	}
	if ($kukarow['kesken'] != '0') {
		$tilausnumero=$kukarow['kesken'];
	}

	// T�ss� p�ivitet��n 'pikaotsikkoa' jos kenttiin on jotain sy�tetty
	if ($toimitustapa != '' or $tilausvahvistus != '' or $viesti != '' or $myyjanro != '' or $myyja != '') {

		if ($myyjanro != '') {
			$apuqu = "	select *
						from kuka use index (yhtio_myyja)
						where yhtio='$kukarow[yhtio]' and myyja='$myyjanro'";
			$meapu = mysql_query($apuqu) or pupe_error($apuqu);

			if (mysql_num_rows($meapu)==1) {
				$apuro = mysql_fetch_array($meapu);
				$myyja = $apuro['tunnus'];
			}
			else {
				echo "<font class='error'>".t("Sy�tt�m�si myyj�numero")." $myyjanro ".t("ei l�ytynyt")."!</font><br><br>";
			}
		}

		$query  = "	update lasku set
					toimitustapa	= '$toimitustapa',
					viesti 			= '$viesti',
					tilausvahvistus = '$tilausvahvistus',
					myyja			= '$myyja'
					where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
		$result = mysql_query($query) or pupe_error($query);

		//Haetaan laskurow uudestaan
		$query   	= "	select *
						from lasku
						where tunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
		$result  	= mysql_query($query) or pupe_error($query);
		$laskurow   = mysql_fetch_array($result);
	}

	if ($laskurow["liitostunnus"] > 0) { // jos asiakasnumero on annettu
		echo "<table>";
		echo "<tr>";

		if ($kukarow["extranet"] == "") {
			echo "	<form action='$PHP_SELF' method='post'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='tee' value='OTSIK'>
					<input type='hidden' name='toim' value='$toim'>
					<td class='back'><input type='submit' value='".t("Muuta Otsikkoa")."'></td>
					</form>";
		}

		echo "	<form action='tuote_selaus_haku.php' method='post'>
				<input type='hidden' name='toim_kutsu' value='$toim'>
				<td class='back'><input type='submit' value='".t("Selaa tuotteita")."'></td>
				</form>";

		// aivan karseeta, mutta joskus pit�� olla n�in asiakasyst�v�llinen... toivottavasti ei h�iritse ket��n
		if ($kukarow["extranet"] == "" and $kukarow["yhtio"] == "artr") {
			echo 	"<form action='../arwidson/yhteensopivuus.php' method='post'>
					<input type='hidden' name='toim_kutsu' value='$toim'>
					<td class='back'><input type='submit' value='".t("Malliselain")."'></td>
					</form>";
		}

		if ($kukarow["extranet"] == "" and $toim == "TYOMAARAYS") {
			echo "	<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='tee' value='VAHINKO'>
					<input type='hidden' name='toim' value='$toim'>
					<td class='back'><input type='Submit' value='".t("Lis�� vahinkotiedot")."'></td>
					</form>";
		}

		echo "<form action='$PHP_SELF' method='post'>
			<input type='hidden' name='tee' value='mikrotila'>
			<input type='hidden' name='tilausnumero' value='$tilausnumero'>
			<input type='hidden' name='toim' value='$toim'>
			<td class='back'><input type='Submit' value='".t("Lue tilausrivit tiedostosta")."'></td>
			</form>";


		if ($kukarow["extranet"] == "" and $toim == "TARJOUS") {
			echo "<form action='$PHP_SELF' method='post'>
				<input type='hidden' name='tee' value='osamaksusoppari'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='toim' value='$toim'>
				<td class='back'><input type='Submit' value='".t("Tee rahoituslaskelma")."'></td>
				</form>";
		}

		echo "</tr></table><br>\n";
	}

	//Is�tabeli
	echo "<table><tr><td class='back' valign='top'>";

	// kirjoitellaan otsikko
	echo "<table>";

	// t�ss� alotellaan koko formi.. t�m� pit�� kirjottaa aina
	echo "	<form name='tilaus' action='$PHP_SELF' method='post' autocomplete='off'>
			<input type='hidden' name='tilausnumero' value='$tilausnumero'>
			<input type='hidden' name='toim' value='$toim'>";

	if ($laskurow["liitostunnus"] > 0) { // jos asiakasnumero on annettu

		echo "<tr>";
		echo "<th align='left'>".t("Asiakas").":</th>";

		if ($kukarow["extranet"] == "") {
			echo "<td><a href='../crm/asiakasmemo.php?ytunnus=$laskurow[ytunnus]'>$laskurow[ytunnus] $laskurow[nimi]</a><br>$laskurow[toim_nimi]</td>";
		}
		else {
			echo "<td>$laskurow[ytunnus] $laskurow[nimi]<br>$laskurow[toim_nimi]</td>";
		}

		echo "<th align='left'>".t("Toimitustapa").":</th>";

		if ($toim != "VALMISTAVARASTOON") {

			$extralisa = "";
			if ($kukarow["extranet"] != "") {
				$extralisa = " and (extranet = 'K' or selite = '$laskurow[toimitustapa]') ";
			}

			$query = "	SELECT tunnus, selite
						FROM toimitustapa
						WHERE yhtio = '$kukarow[yhtio]' $extralisa
						ORDER BY jarjestys,selite";
			$tresult = mysql_query($query) or pupe_error($query);

			echo "<td><select name='toimitustapa' onchange='submit()'>";

			while($row = mysql_fetch_array($tresult)) {
				$sel = "";
				if ($row["selite"] == $laskurow["toimitustapa"]) {
					$sel = 'selected';
				}
				echo "<option value='$row[selite]' $sel>$row[selite]";
			}
			echo "</select></td>";

		}
		else {
			echo "<td></td>";
		}

		echo "</td></tr>\n";

		echo "<tr>";
		echo "<th align='left'>".t("Tilausnumero").":</th>";
		echo "<td>$kukarow[kesken]</td>";


		echo "<th>".t("Tilausviite").":</th><td>";
		echo "<input type='text' size='30' name='viesti' value='$laskurow[viesti]'><input type='submit' value='".t("Tallenna")."'></td></tr>\n";

		echo "<tr>";
		echo "<th>".t("Tilausvahvistus").":</th>";

		if ($toim != "VALMISTAVARASTOON") {
			$extralisa = "";
			if ($kukarow["extranet"] != "") {
				$extralisa = "  and selite not like '%E%' and selite not like '%O%' ";
				if ($kukarow['hinnat'] != 0) {
					$hinnatlisa = " and selite not like '1%' ";
				}
			}

			$query = "	SELECT selite, selitetark
						FROM avainsana use index (yhtio_laji_selite)
						WHERE yhtio = '$kukarow[yhtio]' and laji = 'TV' $extralisa $hinnatlisa
						ORDER BY jarjestys, selite";
			$tresult = mysql_query($query) or pupe_error($query);

			echo "<td><select name='tilausvahvistus' onchange='submit()'>";
			echo "<option value=' '>".t("Ei Vahvistusta")."</option>";

			while($row = mysql_fetch_array($tresult)) {
				$sel = "";
				if ($row[0]== $laskurow["tilausvahvistus"]) $sel = 'selected';
				echo "<option value='$row[0]' $sel>$row[1]</option>";
			}
			echo "</select></td>";
		}
		else {
			echo "<td></td>";
		}

		if ($kukarow["extranet"] == "") {
			echo "<th align='left'>".t("Myyj�nro").":</th>";
			echo "<td><input type='text' name='myyjanro' size='8'> tai ";
			echo "<select name='myyja' onchange='submit()'>";

			$query = "	SELECT tunnus, kuka, nimi, myyja
						FROM kuka use index (yhtio_myyja)
						WHERE yhtio = '$kukarow[yhtio]'
						ORDER BY nimi";

			$yresult = mysql_query($query) or pupe_error($query);

			while ($row = mysql_fetch_array($yresult)) {
				$sel = "";
				if ($laskurow['myyja'] == '' or $laskurow['myyja'] == 0) {
					if ($row['nimi'] == $kukarow['nimi']) {
						$sel = 'selected';
					}
				}
				else {
					if ($row['tunnus'] == $laskurow['myyja']) {
						$sel = 'selected';
					}
				}
				echo "<option value='$row[tunnus]' $sel>$row[nimi]</option>";
			}
			echo "</select></td></tr>";

			$query = "	SELECT a.fakta, l.ytunnus, round(a.luottoraja,0) luottoraja, a.luokka
						FROM asiakas a, lasku l
						WHERE l.tunnus='$kukarow[kesken]' and l.yhtio='$kukarow[yhtio]' and a.yhtio = l.yhtio and a.ytunnus = l.ytunnus";
			$faktaresult = mysql_query($query) or pupe_error($query);
			$faktarow = mysql_fetch_array($faktaresult);

			// kommentoidaan toistaiseks
			// if ($kukarow['kassamyyja'] == '') {
			// 	$query = "	select round(sum(if(erpcm <= mapvm, summa, 0)) / sum(summa-saldo_maksettu) * 100,0) maksunopeus
			// 				from lasku use index (yhtio_tila_ytunnus_tapvm)
			// 				where yhtio = '$kukarow[yhtio]'
			// 				and ytunnus = '$laskurow[ytunnus]'
			// 				and tila = 'u'
			// 				and alatila = 'x'
			// 				and tapvm > date_sub(now(), interval 1 year)
			// 				and summa > 0
			// 				and erpcm > '0000-00-00'
			// 				and mapvm > '0000-00-00'";
			// 	$tresult = mysql_query($query) or pupe_error($query);
			// 	$row = mysql_fetch_array($tresult);
			// 	$maksunopeus = (int) $row["maksunopeus"];
			//
			// 	$query = "	select round(sum(summa-saldo_maksettu),0) as summa
			// 				from lasku use index (yhtio_tila_mapvm)
			// 				where tila = 'u'
			// 				and alatila = 'x'
			// 				and mapvm = '0000-00-00'
			// 				and yhtio = '$kukarow[yhtio]'
			// 				and ytunnus = '$laskurow[ytunnus]'";
			// 	$tresult = mysql_query($query) or pupe_error($query);
			// 	$row = mysql_fetch_array($tresult);
			// 	$avoimetlaskut = (int) $row["summa"];
			//
			//
			// 	$maksukuvaus = t("Hyv�");
			// 	$fontcolor = "";
			//
			// 	if ($maksunopeus > 10) {
			// 		$maksukuvaus = t("V�ltt�v�");
			// 		$fontcolor = "message";
			// 	}
			//
			// 	if ($maksunopeus > 50) {
			// 		$maksukuvaus = t("Huono");
			// 		$fontcolor = "error";
			// 	}
			//
			// 	echo "<tr><th>".t("Maksuvalmius").":</th><td><font class='$fontcolor'>$maksukuvaus</font></td><th>".t("Avoimet")." / ".t("Limiitti").":</th><td><font class='$fontcolor'>$avoimetlaskut $yhtiorow[valkoodi] / $faktarow[luottoraja] $yhtiorow[valkoodi]</font></td></tr>\n";
			//
			// }

			echo "<tr><th>".t("Asiakasfakta").":</th><td colspan='3'>";

			//jos asiakkaalla on luokka K niin se on myyntikiellossa ja siit� herjataan
			if ($faktarow["luokka"]== 'K') {
				echo "<font class='error'>".t("HUOM!!!!!! Asiakas on myyntikiellossa")."!!!!!<br></font>";
			}

			echo "$faktarow[fakta]&nbsp;</td></tr>\n";
		}
		else {
			echo "</tr>";
		}
	}
	elseif ($kukarow["extranet"] == "") {
		// asiakasnumeroa ei ole viel� annettu, n�ytet��n t�ytt�kent�t

		if ($kukarow["oletus_asiakas"] != 0) {
			$yt = $kukarow["oletus_asiakas"];
		}
		if ($kukarow["myyja"] != 0) {
			$my = $kukarow["myyja"];
		}

		echo "<tr>
			<th align='left'>".t("Asiakas")."</th>
			<td><input type='text' size='10' maxlength='10' name='syotetty_ytunnus' value='$yt'></td>
			</tr>";
		echo "<tr>
			<th align='left'>".t("Myyj�nro")."</th>
			<td><input type='text' size='10' maxlength='10' name='myyjanro' value='$my'></td>
			</tr>";
	}

	echo "</table>";

	if ($kukarow['extranet'] == '' and $kukarow['kassamyyja'] == '' and $laskurow['liitostunnus'] > 0 and ($toim == "RIVISYOTTO" or $toim == "PIKATILAUS" or $toim == "EXTRANET")) {
		$sytunnus = $laskurow['ytunnus'];
		$eiliittymaa = 'ON';
		require ("../raportit/saatanat.php");

		if ($ylikolkyt > 0) {
			echo "<font class='error'>".t("HUOM!!!!!! Asiakkaalla on yli 30 p�iv�� sitten er��ntyneit� laskuja, olkaa yst�v�llinen ja ottakaa yhteytt� myyntireskontran hoitajaan")."!!!!!<br></font>";
		}
	}

	echo "<hr><br>";

	if($kukarow["extranet"] == "" and $toim == "TYOMAARAYS") {
		$tee_tyomaarays = "MAARAAIKAISHUOLLOT";
		//require('../tyomaarays/tyomaarays.inc');
	}


	//Kuitataan OK-var riville
	if ($kukarow["extranet"] == "" and $tila == "OOKOOAA") {
		$query = "	UPDATE tilausrivi
					SET var = 'O'
					WHERE tunnus = '$rivitunnus'";
		$result = mysql_query($query) or pupe_error($query);

		$tapa 		= "";
		$rivitunnus = "";
	}

	if ($kukarow["extranet"] == "" and $tila == "LISLISAV") {
		//P�ivitet��n is�n perheid nollaksi jotta voidaan lis�t� lis�� lis�varusteita
		$query = "	update tilausrivi set
					perheid=0
					where yhtio = '$kukarow[yhtio]'
					and tunnus = '$rivitunnus'
					LIMIT 1";
		$updres = mysql_query($query) or pupe_error($query);

		$tila 		= "";
		$tapa 		= "";
		$rivitunnus = "";
	}

	if ($kukarow["extranet"] == "" and $tila == "MYYNTITILIRIVI") {
		$tilatapa = "PAIVITA";

		require("laskuta_myyntitilirivi.inc");
	}

	// ollaan muokkaamassa rivin tietoja, haetaan rivin tiedot ja poistetaan rivi..
	if ($tila == 'MUUTA') {

		$query	= "	select *
					from tilausrivi
					where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]' and tunnus='$rivitunnus'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 1) {

			$tilausrivi  = mysql_fetch_array($result);

			// Poistetaan muokattava tilausrivi
			$query = "	DELETE from tilausrivi
						WHERE tunnus = '$rivitunnus'";
			$result = mysql_query($query) or pupe_error($query);

			// Poistetaan my�s tuoteperheen lapset, paitsi jos ne ovat tehdasli�varusteita
			if ($tapa != "VAIHDA" or ($tilausrivi["var"] == "T" and substr($paikka,0,3) != "###")) {

				//Pidet��n valitut lis�varusteetmuistissa vaikka is�rivi� muokataan
				$query = "	SELECT
							group_concat(tuoteno) tuotteet,
							group_concat(varattu) kappaleet
							FROM tilausrivi
							WHERE perheid = '$rivitunnus' and otunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]' and var='V'";
				$result = mysql_query($query) or pupe_error($query);

				$pehrelias = "";

				if (mysql_num_rows($result) > 0 and $tapa == "MUOKKAA") {
					echo "<input type='hidden' name='perheid' value = '$tilausrivi[perheid]'>";

					$pehrelias = " and var != 'V'";
				}

				$query = "	DELETE from tilausrivi
							WHERE perheid = '$rivitunnus' and otunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]' $pehrelias";
				$result = mysql_query($query) or pupe_error($query);
			}

			// Jos muokkaamme tilausrivin paikkaa ja se on fl�g�tty toimittajalta tilattavaksi niin laitetaan $paikka-muuttuja kuntoon
			if ($tapa != "VAIHDA" and $tilausrivi["var"] == "T" and substr($paikka,0,3) != "###") {
				$paikka = "###".$tilausrivi["tilaajanrivinro"];
			}

			//haetaan tuotteen alv matikkaa varten
			$query = "	SELECT alv, myyntihinta, nettohinta
						FROM tuote
						WHERE tuoteno = '$tilausrivi[tuoteno]' and yhtio='$kukarow[yhtio]'";
			$tuoteresult = mysql_query($query) or pupe_error($query);
			$tuoterow = mysql_fetch_array($tuoteresult);

			if ($tuoterow["alv"] != $tilausrivi["alv"] and $yhtiorow["alv_kasittely"] == "" and $tilausrivi["alv"] < 500) {
				$hinta = sprintf('%.2f',round($tilausrivi["hinta"] / (1+$tilausrivi['alv']/100) * (1+$tuoterow["alv"]/100),2));
			}
			else {
				$hinta	= $tilausrivi["hinta"];
			}

			$tuoteno 	= $tilausrivi['tuoteno'];

			if ($tilausrivi["var"] == "J" or $tilausrivi["var"] == "S" or $tilausrivi["var"] == "T") {
				$kpl	= $tilausrivi['jt'];
			}
			elseif ($tilausrivi["var"] == "P") {
				$kpl	= $tilausrivi['tilkpl'];
			}
			else {
				$kpl	= $tilausrivi['varattu'];
			}

			$netto		= $tilausrivi['netto'];
			$ale		= $tilausrivi['ale'];
			$alv 		= $tilausrivi['alv'];
			$kommentti	= $tilausrivi['kommentti'];
			$kerayspvm	= $tilausrivi['kerayspvm'];
			$toimaika	= $tilausrivi['toimaika'];

			$hyllyalue	= $tilausrivi['hyllyalue'];
			$hyllynro	= $tilausrivi['hyllynro'];
			$hyllytaso	= $tilausrivi['hyllytaso'];
			$hyllyvali	= $tilausrivi['hyllyvali'];

			$rivinumero	= $tilausrivi['tilaajanrivinro'];

			if ($tilausrivi['hinta'] == '0.00') $hinta = '';

			//T�m� oli huono idea
			//if ($tilausrivi['ale']   == '0.00') $ale	= '';

			if ($tapa == "MUOKKAA") {
				$var	= $tilausrivi["var"];

				//Jos lasta muokataan, niin s�ilytet��n sen perheid
				if ($tilausrivi["tunnus"] != $tilausrivi["perheid"] and $tilausrivi["perheid"] != 0) {
					echo "<input type='hidden' name='perheid' value = '$tilausrivi[perheid]'>";
				}

				$tila	= "MUUTA";

			}
			elseif ($tapa == "JT") {
				$var 	= "J";
				$paikka	= $hyllyalue."#".$hyllynro."#".$hyllyvali."#".$hyllytaso;
				$tila	= "";
			}
			elseif ($tapa == "VAIHDA") {
				$perheid = $tilausrivi['perheid'];
				$tila	 = "";
			}
			elseif ($tapa == "POISTA") {
				$tuoteno	= '';
				$kpl		= '';
				$var		= '';
				$hinta		= '';
				$netto		= '';
				$ale		= '';
				$rivitunnus	= '';
				$kommentti	= '';
				$kerayspvm	= '';
				$toimaika	= '';
				$paikka		= '';
				$alv		= '';
				$perheid	= '';
			}
		}
	}

	//Lis�t��n tuote tiettyyn tuoteperheeseen/reseptiin
	if ($kukarow["extranet"] == "" and $tila == "LISAARESEPTIIN") {
		echo "<input type='hidden' name='perheid' value = '$perheid'>";
	}

	if ($tuoteno != '') {
		$multi = "TRUE";

		if (file_exists("../inc/tuotehaku.inc")) {
			require ("../inc/tuotehaku.inc");
		}
		else {
			require ("tuotehaku.inc");
		}
	}

	//Lis�t��n ennakkomaksurivit
	if($ennakkomaksu > 0 and $tila == "lisaa_ennakkomaksu") {
		$tuoteno_array = array();
		$hinta_array   = array();
		$kpl_array	   = array();

		$tuoteno_array[] = $yhtiorow["ennakkomaksu_tuotenumero"];
		$tuoteno_array[] = $yhtiorow["ennakkomaksu_tuotenumero"];

		$hinta_array[$yhtiorow["ennakkomaksu_tuotenumero"]] = $ennakkomaksu;

		$kpl_array[$yhtiorow["ennakkomaksu_tuotenumero"]] =  1;

		$ennakkomaksu = 0;
	}

	if($ennakkomaksu == 0 and $tila == "lisaa_ennakkomaksu") {
		$query = "	UPDATE tilausrivi set tyyppi='D'
					WHERE yhtio='$kukarow[yhtio]'
					and otunnus = '$kukarow[kesken]'
					and tuoteno='$yhtiorow[ennakkomaksu_tuotenumero]'";
		$result = mysql_query($query) or pupe_error($query);

		$tila = "";
	}

	//Lis�t��n rivi
	if ((trim($tuoteno) != '' or is_array($tuoteno_array)) and ($kpl != '' or is_array($kpl_array)) and $tila != "MUUTA" and $ulos == '') {

		if (!is_array($tuoteno_array) and trim($tuoteno) != "") {
			$tuoteno_array[] = $tuoteno;
		}

		//K�ytt�j�n sy�tt�m� hinta ja ale ja netto, pit�� s�il�� jotta tuotehaussakin voidaan sy�tt�� n�m�
		$kayttajan_hinta	= $hinta;
		$kayttajan_ale		= $ale;
		$kayttajan_netto 	= $netto;
		$kayttajan_var		= $var;
		$kayttajan_kpl		= $kpl;
		$kayttajan_alv		= $alv;
		$lisatty 			= 0;

		foreach($tuoteno_array as $tuoteno) {

			//Ennakkomaskun toinen rivi k��nnet��n aina negatiiviseksi
			if($lisatty == 1 and trim(strtoupper($tuoteno)) == trim(strtoupper($yhtiorow["ennakkomaksu_tuotenumero"]))) {
				$kpl_array[$yhtiorow["ennakkomaksu_tuotenumero"]] = $kpl_array[$yhtiorow["ennakkomaksu_tuotenumero"]] * -1;
			}

			$query	= "	select *
						from tuote
						where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) > 0) {
				//Tuote l�ytyi
				$trow = mysql_fetch_array($result);

				//extranettajille ei myyd� tuotteita joilla ei ole myyntihintaa
				if ($kukarow["extranet"] != '' and $trow["myyntihinta"] == 0) {
					$varaosavirhe = t("VIRHE: Tuotenumeroa ei l�ydy j�rjestelm�st�!");
					$trow 	 = "";
					$tuoteno = "";
					$kpl	 = 0;
				}
			}
			elseif ($kukarow["extranet"] != '') {
				$varaosavirhe = t("VIRHE: Tuotenumeroa ei l�ydy j�rjestelm�st�!");
				$tuoteno = "";
				$kpl	 = 0;
			}
			else {
				//Tuotetta ei l�ydy, aravataan muutamia muuttujia
				$trow["alv"] = $laskurow["alv"];
			}

			if ($toimaika == "" or $toimaika == "0000-00-00") {
				$toimaika = $laskurow["toimaika"];
			}

			if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
				$kerayspvm = $laskurow["kerayspvm"];
			}

			if ($laskurow["varasto"] != 0) {
				$varasto = (int) $laskurow["varasto"];
			}

			//Ennakkotilauksen ja Tarjoukset eiv�t varaa saldoa
			if ($laskurow["tilaustyyppi"] == "E" or $laskurow["tilaustyyppi"] == "T" or $laskurow["tila"] == "V") {
				$varataan_saldoa = "EI";
			}
			else {
				$varataan_saldoa = "";
			}

			//Tehd��n muuttujaswitchit

			if (is_array($hinta_array)) {
				$hinta = $hinta_array[$tuoteno];
			}
			else {
				$hinta = $kayttajan_hinta;
			}

			if (is_array($ale_array)) {
				$ale = $ale_array[$tuoteno];
			}
			else {
				$ale = $kayttajan_ale;
			}

			if (is_array($netto_array)) {
				$netto = $netto_array[$tuoteno];
			}
			else {
				$netto = $kayttajan_netto;
			}

			if (is_array($var_array)) {
				$var = $var_array[$tuoteno];
			}
			else {
				$var = $kayttajan_var;
			}

			if (is_array($kpl_array)) {
				$kpl = $kpl_array[$tuoteno];
			}
			else {
				$kpl = $kayttajan_kpl;
			}

			if (is_array($alv_array)) {
				$alv = $alv_array[$tuoteno];
			}
			else {
				$alv = $kayttajan_alv;
			}

			//Extranettaajat eiv�t voi hyvitell� itselleen tuotteita
			if ($kukarow["extranet"] != '') {
				$kpl = abs($kpl);
			}

			if ($tuoteno != '' and $kpl != 0) {
				require ('lisaarivi.inc');
			}

			$hinta 	= '';
			$ale 	= '';
			$netto 	= '';
			$var 	= '';
			$kpl 	= '';
			$alv 	= '';
			$paikka	= '';
			$lisatty++;
		}

		if ($lisavarusteita == "ON" and $perheid != '') {
			//P�ivitet��n is�lle perheid jotta tiedet��n, ett� lis�varusteet on nyt lis�tty
			$query = "	update tilausrivi set
						perheid		= '$perheid'
						where yhtio = '$kukarow[yhtio]'
						and tunnus 	= '$perheid'";
			$updres = mysql_query($query) or pupe_error($query);
		}

		$tuoteno			= '';
		$kpl				= '';
		$var				= '';
		$hinta				= '';
		$netto				= '';
		$ale				= '';
		$rivitunnus			= '';
		$kerayspvm			= '';
		$toimaika			= '';
		$alv				= '';
		$paikka 			= '';
		$paikat				= '';
		$kayttajan_hinta	= '';
		$kayttajan_ale		= '';
		$kayttajan_netto 	= '';
		$kayttajan_var		= '';
		$kayttajan_kpl		= '';
		$kayttajan_alv		= '';
	}

	//Sy�tt�rivi
	if ($muokkauslukko == "") {
		require ("syotarivi.inc");
	}

	 // erikoisceisi, jos halutaan pieni tuotekysely tilaustaulussa...
	if ($tuoteno != '' and $kpl == '') {
		$query	= "select * from tuote where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result)!=0) {
			$tuote = mysql_fetch_array($result);

			//kursorinohjausta
			$kentta = 'kpl';

			echo "<br>
				<table>
				<tr><th>".t("Nimitys")."</th><td align='right'>$tuote[nimitys]</td></tr>
				<tr><th>".t("Hinta")."</th><td align='right'>$tuote[myyntihinta] $yhtiorow[valkoodi]</td></tr>
				<tr><th>".t("Nettohinta")."</th><td align='right'>$tuote[nettohinta] $yhtiorow[valkoodi]</td></tr>";

			$query = "select * from tuotepaikat where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
			$tres  = mysql_query($query) or pupe_error($query);

			while ($salrow = mysql_fetch_array($tres)) {
				$query = "select * from varastopaikat where yhtio='$kukarow[yhtio]' and alkuhyllyalue<='$salrow[hyllyalue]' and loppuhyllyalue>='$salrow[hyllyalue]' and alkuhyllynro<='$salrow[hyllynro]' and loppuhyllynro>='$salrow[hyllynro]'";
				$nimre = mysql_query($query) or pupe_error($query);
				$nimro = mysql_fetch_array($nimre);

				$query = "	select sum(varattu)
							from tilausrivi
							where hyllyalue='$salrow[hyllyalue]'
							and hyllynro='$salrow[hyllynro]'
							and hyllytaso='$salrow[hyllytaso]'
							and hyllyvali='$salrow[hyllyvali]'
							and yhtio='$kukarow[yhtio]'
							and tuoteno='$tuoteno'
							and tyyppi in ('L','G','V')
							and varattu>0";
				$sres  = mysql_query($query) or pupe_error($query);
				$srow = mysql_fetch_array($sres);

				$oletus='';
				if ($salrow['oletus']!='') {
					$oletus = "<br>(".t("oletusvarasto").")";
				}

				$varastomaa = '';
				if (strtoupper($nimro['maa']) != strtoupper($yhtiorow['maakoodi'])) {
					$varastomaa = "<br>".strtoupper($nimro['maa']);
				}

				echo "<tr><th>".t("Saldo")." $nimro[nimitys] $oletus $varastomaa</th><td align='right'><font class='info'>$salrow[saldo]<br>- $srow[0]<br>---------<br></font>".sprintf("%01.2f",$salrow['saldo'] - $srow[0])."</td></tr>";
			}

			echo "</table>";
		}
	}

	// jos ollaan jo saatu tilausnumero aikaan listataan kaikki tilauksen rivit..
	if ((int) $kukarow["kesken"] != 0) {

		if ($toim == "TYOMAARAYS") {
			 $order 	= "ORDER BY tuotetyyppi, tuote.ei_saldoa DESC, tunnus DESC";
			 $tilrivity	= "'L'";
		}
		elseif ($toim == "TARJOUS") {
			$order = "ORDER by sorttauskentta desc, tunnus";
			$tilrivity	= "'T'";
		}
		elseif ($toim == "SIIRTOLISTA" or $toim == "MYYNTITILI") {
			$order = "ORDER by sorttauskentta desc, tunnus";
			$tilrivity	= "'G'";
		}
		elseif ($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE") {
			$order = "ORDER BY tilausrivi.perheid desc, tunnus";
			$tilrivity	= "'V','W'";
		}
		else {
			$order = "ORDER by sorttauskentta desc, tunnus";
			$tilrivity	= "'L','E'";
		}

		$ennakkolisa = "";
		if($yhtiorow["ennakkomaksu_tuotenumero"] != '' and $toim == "TARJOUS") {
			$ennakkolisa = " and tilausrivi.tuoteno != '$yhtiorow[ennakkomaksu_tuotenumero]' ";
		}

		// Tilausrivit (yhti�n ennakkomaksutuotetta ei aina n�ytet�)
		$query  = "	SELECT tilausrivi.*,
					if (tuotetyyppi='K','Ty�','Varaosa') tuotetyyppi,
					if(tilausrivi.perheid=0, tilausrivi.tunnus, tilausrivi.perheid) as sorttauskentta,
					tuote.myyntihinta
					FROM tilausrivi
					LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tilausrivi.tuoteno=tuote.tuoteno
					WHERE tilausrivi.yhtio='$kukarow[yhtio]'
					and tilausrivi.otunnus='$kukarow[kesken]'
					and tilausrivi.tyyppi in ($tilrivity)
					$ennakkolisa
					$order";
		$result = mysql_query($query) or pupe_error($query);

		//Oletetaan, ett� tilaus on ok, $tilausok muuttujaa summataa alempana jos jotain virheit� ilmenee
		$tilausok 		= 0;
		$rivilaskuri 	= mysql_num_rows($result);

		if ($rivilaskuri != 0) {
			$rivino = $rivilaskuri+1;

			echo "<br><table>";

			if ($toim != "TYOMAARAYS") {
				echo "<tr><td class='back' colspan='10'>".t("Tilausrivit").":</td></tr>";
				echo "<tr><th>".t("#")."</th>";

				if ($kukarow["resoluutio"] == 'I' or $kukarow['extranet'] != '') {
					echo "<th>".t("Nimitys")."</th>";
				}

				if($kukarow['extranet'] == '') {
					echo "	<th>".t("Paikka")."</th>";
				}

				echo "	<th>".t("Tuotenumero")."</th>
						<th>".t("Kpl")."</th>
						<th>".t("Var")."</th>
						<th>".t("Hinta")."</th>";

				if ($kukarow['hinnat'] == 0 or $kukarow['extranet'] == '') {
					echo "<th>".t("Ale%")."</th>";
				}

				echo "	<th>".t("Netto")."</th>
						<th>".t("Summa")."</th>
						<th>".t("Alv")."</th>
						<td class='back'></td>
						<td class='back'></td>
						</tr>";
			}

			$bruttoyhteensa	= 0;
			$tuotetyyppi	= "";
			$varaosatyyppi	= "";
			$vanhaid 		= "KALA";

			$nettoyhteensa						= 0;
			$yhteensa							= 0;
			$kotimaan_varastot_yhteensa 		= 0;
			$ulkomaan_varastot_yhteensa 		= 0;
			$nettokotimaan_varastot_yhteensa 	= 0;
			$nettoulkomaan_varastot_yhteensa 	= 0;

			$toimitetaan_ulkomaailta 			= 0;

			while ($row = mysql_fetch_array($result)) {

				if ($toim == "TYOMAARAYS") {
					if ($tuotetyyppi == "" and $row["tuotetyyppi"] == 'Ty�') {
						$tuotetyyppi = 1;

						echo "<tr><td class='back' colspan='10'>".t("Ty�t").":</td></tr>";
						echo "<tr><th>".t("#")."</th>";

						if ($kukarow["resoluutio"] == 'I') {
							echo "<th>".t("Nimitys")."</th>";
						}

						echo "	<th>".t("Paikka")."</th>
								<th>".t("Tuotenumero")."</th>
								<th>".t("Kpl")."</th>
								<th>".t("Var")."</th>
								<th>".t("Hinta")."</th>
								<th>".t("Ale%")."</th>
								<th>".t("Netto")."</th>
								<th>".t("Summa")."</th>
								<th>".t("Alv")."</th>
								<td class='back'></td>
								<td class='back'></td>
								</tr>";
					}

					if ($varaosatyyppi == "" and $row["tuotetyyppi"] == 'Varaosa') {
						$varaosatyyppi = 1;

						if ($tuotetyyppi == 1) {
							echo "<tr><td class='back' colspan='10'><br></td></tr>";
						}

						echo "<tr><td class='back' colspan='10'>".t("Varaosat ja Tarvikkeet").":</td></tr>";
						echo "<tr><th>".t("#")."</th>";

						if ($kukarow["resoluutio"] == 'I') {
							echo "<th>".t("Nimitys")."</th>";
						}

						echo "	<th>".t("Paikka")."</th>
								<th>".t("Tuotenumero")."</th>
								<th>".t("Kpl")."</th>
								<th>".t("Var")."</th>
								<th>".t("Hinta")."</th>
								<th>".t("Ale%")."</th>
								<th>".t("Netto")."</th>
								<th>".t("Summa")."</th>
								<th>".t("Alv")."</th>
								<td class='back'></td>
								<td class='back'></td>
								</tr>";
					}
				}

				$rivino--;

				if ($toim == "MYYNTITILI" and $laskurow["alatila"] == "V") {
					$row['varattu'] = $row['kpl'];
				}

				// T�n rivin rivihinta
				$summa	= $row["hinta"]*($row["varattu"]+$row["jt"])*(1-$row["ale"]/100);

				// Jos halutaan tulostaa tietyille extranettaajille bruttomyyntihintoja ruudulle
				$brutto = 0;
				if ($kukarow['hinnat'] == 1 and $kukarow['extranet'] != '') {
					$hinta = $row["myyntihinta"];

					require('alv.inc');

					$brutto = $hinta*($row["varattu"]+$row["jt"]);
				}

				if ($row["var"] == "P") {
					$class = " class='spec' ";
				}
				elseif ($row["var"] == "J") {
					$class = " class='green' ";
				}
				else {
					//Jos rivi ei ole puute eik� jt niin tutkitaan sen hintaa ja maata josta se myyd��n
					//Suoratoimitusriveille joudumme tutkimaan hieman toimittajan kautta mist� maasta ne myyd��n
					if ($row["tilaajanrivinro"] > 0 and $row["var"] == "S") {
						$query = "	SELECT tyyppi_tieto, maa, maakoodi
									FROM toimi
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus = '$row[tilaajanrivinro]'";
						$sres1 = mysql_query($query) or pupe_error($query);
						$srow1 = mysql_fetch_array($sres1);
					}
					else {
						$srow1 = array();

						$srow1["tyyppi_tieto"] 	= $yhtiorow["yhtio"];
						$srow1["maa"] 			= $yhtiorow["maakoodi"];
					}

					// Luetaan varaston tiedoista miss� maassa se sijaitsee
					$query = "	SELECT maa
								FROM varastopaikat
								WHERE yhtio = '$srow1[tyyppi_tieto]'
								and concat(rpad(upper('$row[hyllyalue]'), 3, '0'),lpad('$row[hyllynro]', 2, '0')) >= concat(rpad(upper(alkuhyllyalue) ,3,'0'),lpad(alkuhyllynro ,2,'0'))
								and concat(rpad(upper('$row[hyllyalue]'), 3, '0'),lpad('$row[hyllynro]', 2, '0')) <= concat(rpad(upper(loppuhyllyalue) ,3,'0'),lpad(loppuhyllynro ,2,'0'))";
					$sres2  = mysql_query($query) or pupe_error($query);
					$srow2 = mysql_fetch_array($sres2);

					//Jos varastoalueen maakoodi on tyhj� niin k�ytet��n toimittajan maakoodia
					if (trim($srow2["maa"]) == "") {
						$srow2["maa"] = trim($srow1["maakoodi"]);
					}

					//Jos toimittajan maakoodi on tyhj� niin k�ytet��n yhti�n maakoodia
					if (trim($srow2["maa"]) == "") {
						$srow2["maa"] = trim($yhtiorow["maakoodi"]);
					}

					$class = '';

					// Summailaan tilauksen loppusummaa
					if ($row["netto"] != 'N') {
						$yhteensa += $summa; // lasketaan tilauksen loppusummaa MUUT RIVIT..

						//Kotimaiset varastot EI-nettorivit
						if(strtoupper($srow2["maa"]) == strtoupper(trim($yhtiorow["maakoodi"]))) {
							$kotimaan_varastot_yhteensa += $summa;
						}
						//Ulkomaiset varastot EI-nettorivit
						else {
							$ulkomaan_varastot_yhteensa += $summa;
						}
					}
					else {
						$nettoyhteensa += $summa; // lasketaan tilauksen loppusummaa NETTORIVIT..

						//Kotimaiset varastot nettorivit
						if(strtoupper($srow2["maa"]) == strtoupper(trim($yhtiorow["maakoodi"]))) {
							$nettokotimaan_varastot_yhteensa += $summa;
						}
						//Ulkomaiset varastot nettorivit
						else {
							$nettoulkomaan_varastot_yhteensa += $summa;
						}

					}

					//tutkitaan yleisesti onko k�ytt�j� tilannut tuotteita ulkomaassa sijaitsevasta varastosta
					if(strtoupper($srow2["maa"]) != strtoupper(trim($yhtiorow["maakoodi"]))) {
						$toimitetaan_ulkomaailta++;
					}

					$bruttoyhteensa += $brutto;
				}

				if ($row["hinta"] == 0.00) 	$row["hinta"] = '';
				if ($summa == 0.00) 		$summa = '';
				if ($row["ale"] == 0.00) 	$row["ale"] = '';
				if ($row["alv"] >= 500) 	$row["alv"] = t("M.V.");

				if ($row["hyllyalue"] == "") {
					$row["hyllyalue"] = "";
					$row["hyllynro"]  = "";
					$row["hyllyvali"] = "";
					$row["hyllytaso"] = "";
				}

				if ($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE") {
					if ($vanhaid != $row["perheid"] and $vanhaid != 'KALA') {
						echo "<tr><td class='back' colspan='10'><br></td></tr>";

						if ($row["perheid"] != 0 and $row["tyyppi"] == "W") {
							$class = " class='spec' ";
						}
					}
					elseif ($vanhaid == 'KALA' and $row["perheid"] != 0 and $row["tyyppi"] == "W") {
						$class = " class='spec' ";
					}
				}

				$vanhaid = $row["perheid"];

				if ($muokkauslukko == "") {
					require('tarkistarivi.inc');
				}

				// Tuoteperheiden lapsille ei n�ytet� rivinumeroa
				if ($row["perheid"] == 0 or $row["perheid"] == $row["tunnus"]) {
					echo "<tr><td>$rivino</td>";
				}
				else {
					echo "<tr><td class='back'></td>";
				}

				// Tuotteen nimitys n�ytet��n vain jos k�ytt�j�n resoluution on iso
				if ($kukarow["resoluutio"] == 'I' or $kukarow['extranet'] != '') {
					echo "<td $class align='left'>$row[nimitys]</td>";
				}

				if ($kukarow['extranet'] == '' and $toim == "MYYNTITILI" and $laskurow["alatila"] == "V") {

					 if ($row["kpl"] != 0 and ($row["perheid"] == 0 or $row["perheid"] == $row["tunnus"])) {
						$tilatapa = "VALITSE";

						require('laskuta_myyntitilirivi.inc');
					}
					else {
						echo "<td $class align='left'></td>";
					}
				}
				elseif ($kukarow['extranet'] == '' and $trow["ei_saldoa"] == "") {
					if ($paikat != '') {
						echo "	<form action='$PHP_SELF' method='post'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
								<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
								<input type='hidden' name='tila' value = 'MUUTA'>
								<input type='hidden' name='tapa' value = 'VAIHDA'>";
						echo "<td $class align='left'>$paikat</td>";
						echo "</form>";
					}
					else {
						$query = "select * from varastopaikat where yhtio='$kukarow[yhtio]' and alkuhyllyalue<='$row[hyllyalue]' and loppuhyllyalue>='$row[hyllyalue]' and alkuhyllynro<='$row[hyllynro]' and loppuhyllynro>='$row[hyllynro]'";
						$varastore = mysql_query($query) or pupe_error($query);
						$varastoro = mysql_fetch_array($varastore);

						if (strtoupper($varastoro['maa']) != strtoupper($yhtiorow['maakoodi'])) {
							echo "<td $class align='left'><font color='#0000FF'>".strtoupper($varastoro['maa'])." $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</font></td>";
						}
						else {
							echo "<td $class align='left'>$row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</td>";
						}
					}
				}
				elseif($kukarow['extranet'] == '') {
					echo "<td $class align='left'></td>";
				}

				if($kukarow['extranet'] == '') {
					echo "	<td $class ><a href='../tuote.php?tee=Z&tuoteno=$row[tuoteno]'>$row[tuoteno]</a>";
				}
				else {
					echo "	<td $class >$row[tuoteno]";
				}

				$query = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]'";
				$sarjares = mysql_query($query) or pupe_error($query);
				$sarjarow = mysql_fetch_array($sarjares);

				if ($sarjarow["sarjanumeroseuranta"] != "" and $row["var"] != 'T') {
					if ($sarjarow["sarjanumeroseuranta"] == "M" and $row["varattu"] < 0) {
						$query = "select count(*) kpl from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]' and ostorivitunnus='$row[tunnus]'";
					}
					else {
						$query = "select count(*) kpl from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]' and myyntirivitunnus='$row[tunnus]'";
					}
					$sarjares = mysql_query($query) or pupe_error($query);
					$sarjarow = mysql_fetch_array($sarjares);

					if ($sarjarow["kpl"] == abs($row["varattu"])) {
						echo " (<a href='sarjanumeroseuranta.php?tuoteno=$row[tuoteno]&myyntirivitunnus=$row[tunnus]&from=$toim' style='color:00FF00'>sarjanro OK</font></a>)";
					}
					else {
						echo " (<a href='sarjanumeroseuranta.php?tuoteno=$row[tuoteno]&myyntirivitunnus=$row[tunnus]&from=$toim'>sarjanro</a>)";

						if ($laskurow['sisainen'] != '' or $laskurow['ei_lahetetta'] != '') {
							$tilausok++;
						}
					}
				}

				echo "</td>";

				if ($toim == "MYYNTITILI" and $laskurow["alatila"] == "V" and $row["kpl"] != 0 and ($row["perheid"] == 0 or $row["perheid"] == $row["tunnus"])) {
					echo "<td $class align='right'><input type='text' size='5' name='kpl' value='$row[varattu]'></td>";
					echo "</form>";
				}
				elseif ($toim == "MYYNTITILI" and $laskurow["alatila"] == "V" and ($row["perheid"] == 0 or $row["perheid"] == $row["tunnus"])) {
					echo "<td $class align='right'>".t("Laskutettu")."</td>";
					echo "</form>";
				}
				else {
					if ($row["var"] == 'J' or $row["var"] == 'S' or $row["var"] == 'T') {
						$kpl_ruudulle = $row['jt'];
					}
					elseif($row["var"] == 'P') {
						$kpl_ruudulle = $row['tilkpl'];
					}
					else {
						$kpl_ruudulle = $row['varattu'];
					}

					echo "<td $class align='right'>$kpl_ruudulle</td>";
				}

				echo "<td $class align='center'>$row[var]</td>";

				if ($kukarow['hinnat'] == 1 and $kukarow['extranet'] != '') {
					echo "<td $class align='right'>$bruttohinta</td>";
				}
				else {
					echo "<td $class align='right'>$row[hinta]</td>";
					echo "<td $class align='right'>$row[ale]</td>";
				}

				echo "	<td $class align='center'>$row[netto]</td>";

				if ($kukarow['hinnat'] == 1 and $kukarow['extranet'] != '') {
					echo "	<td $class align='right'>".sprintf('%.2f',$brutto)."</td>";
				}
				else {
					echo "	<td $class align='right'>".sprintf('%.2f',$summa)."</td>";
				}
				echo "	<td $class align='right'>$row[alv]</td>";

				if ($muokkauslukko == "") {

					echo "	<form action='$PHP_SELF' method='post'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
							<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
							<input type='hidden' name='tila' value = 'MUUTA'>
							<input type='hidden' name='tapa' value = 'MUOKKAA'>
							<td class='back'><input type='Submit' Style='{font-size: 8pt;}' value='".t("Muokkaa")."'></td>
							</form>";

					echo "	<form action='$PHP_SELF' method='post'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
							<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
							<input type='hidden' name='tila' value = 'MUUTA'>
							<input type='hidden' name='tapa' value = 'POISTA'>
							<td class='back' nowrap><input type='Submit' Style='{font-size: 8pt;}' value='".t("Poista")."'></td>
							</form>";

					if ($laskurow["tila"] == "V" and $row["tunnus"] == $row["perheid"] and $row["perheid"] != 0) {
						echo "	<form action='$PHP_SELF' method='post'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
								<input type='hidden' name='tila' value = 'LISAARESEPTIIN'>
								<input type='hidden' name='perheid' value = '$row[perheid]'>
								<td class='back'><input type='Submit' Style='{font-size: 8pt;}' value='".t("Lis�� reseptiin")."'></td>
								</form>";
					}


					if ($row["var"] == "P") {
						echo "	<form action='$PHP_SELF' method='post'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
								<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
								<input type='hidden' name='tila' value = 'MUUTA'>
								<input type='hidden' name='tapa' value = 'JT'>
								<td class='back' nowrap><input type='Submit' Style='{font-size: 8pt;}' value='".t("J�lkitoim")."'></td>
								</form>";
					}

					if ($saako_hyvaksya > 0) {
						echo "	<form action='$PHP_SELF' method='post'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
								<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
								<input type='hidden' name='tila' value = 'OOKOOAA'>
								<td class='back' nowrap><input type='Submit' Style='{font-size: 8pt;}' value='".t("Hyv�ksy")."'></td>
								</form>";
					}
				}

				if ($varaosavirhe != '') {
					echo "<td class='back'><font class='error'>$varaosavirhe</font></td>";
				}

				if ($row['kommentti'] != '') {
					$cspan="";
					if ($kukarow['hinnat'] == 1 and $kukarow['extranet'] != '') {
						$cspan=7;
					}
					elseif ($kukarow['hinnat'] == 0 and $kukarow['extranet'] != '') {
						$cspan=8;
					}
					elseif($kukarow["resoluutio"] == "I") {
						$cspan=9;
					}
					else{
						$cspan=8;
					}

					echo "</tr><tr><th colspan='2'> * ".t("Kommentti").":</th><td colspan='$cspan'>$row[kommentti]</td>";
				}

				$varaosavirhe = "";

				if ($kukarow["extranet"] == "" and $toim == "TARJOUS" and $row["var"] == "T" and $riviok == 0) {
					//Tutkitaan tuotteiden lis�varusteita
					$query  = "	SELECT *
								FROM tuoteperhe
								JOIN tuote ON tuote.yhtio=tuoteperhe.yhtio and tuote.tuoteno=tuoteperhe.tuoteno
								WHERE tuoteperhe.yhtio = '$kukarow[yhtio]'
								and tuoteperhe.isatuoteno = '$row[tuoteno]'
								and tuoteperhe.tyyppi = 'L'
								order by tuoteperhe.tuoteno";
					$lisaresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($lisaresult) > 0 and $row["perheid"] == 0) {
						echo "</tr>";

						echo "	<form name='tilaus' action='$PHP_SELF' method='post' autocomplete='off'>
									<input type='hidden' name='tilausnumero' value='$tilausnumero'>
									<input type='hidden' name='toim' value='$toim'>
									<input type='hidden' name='lisavarusteita' value='ON'>
									<input type='hidden' name='var' value='V'>
									<input type='hidden' name='perheid' value='$row[tunnus]'>";

						$lislask = 0;
						while ($prow = mysql_fetch_array($lisaresult)) {


							echo "<tr><td class='back'></td>";
							echo "<td>$prow[nimitys]</td>";
							echo "<td><input type='text' name='tuoteno_array[$prow[tuoteno]]' size='15' maxlength='20' value='$prow[tuoteno]'></td>";
							echo "<td><input type='text' name='kpl_array[$prow[tuoteno]]' size='5' maxlength='5'></td>";

							/*
							echo "	<td><input type='text' name='var_array[$prow[tuoteno]]' size='2' maxlength='1'></td>
									<td><input type='text' name='hinta_array[$prow[tuoteno]]' size='5' maxlength='12'></td>
									<td><input type='text' name='ale_array[$prow[tuoteno]]' size='5' maxlength='6'></td>
									<td><input type='text' name='netto_array[$prow[tuoteno]]' size='2' maxlength='1'></td>
									<td></td>
									<td></td>";
							*/

							echo "	<td></td>
									<td></td>
									<td></td>
									<td></td>
									<td></td>
									<td></td>";

							$lislask++;

							if ($lislask == mysql_num_rows($lisaresult)) {
								echo "	<td class='back'><input type='submit' Style='{font-size: 8pt;}' value='".t("Lis��")."'></td>";
							}
						}

						echo "</form>";
					}
					elseif($kukarow["extranet"] == "" and mysql_num_rows($lisaresult) > 0 and $prow["perheid"] == $prow["tunnus"]) {
						echo "	<form name='tilaus' action='$PHP_SELF' method='post' autocomplete='off'>
								<input type='hidden' name='tilausnumero' value='$tilausnumero'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tila' value='LISLISAV'>
								<input type='hidden' name='rivitunnus' value='$row[tunnus]'>
								<td class='back'><input type='submit' Style='{font-size: 8pt;}' value='".t("Lis�� lis�varusteita tuotteelle")."'></td>
								</form>";
					}
				}
				echo "</tr>";
			}


			//kaikki yhteens� ARVO
			if ($kukarow['hinnat'] == 1 and $kukarow['extranet'] != '') {
				$arvo 			= $bruttoyhteensa;
				$arvo_kotimaa 	= $bruttoyhteensa;
				$arvo_ulkomaa 	= $bruttoyhteensa;
			}
			else {
				$arvo 			= $yhteensa + $nettoyhteensa;
				$arvo_kotimaa 	= $kotimaan_varastot_yhteensa + $nettokotimaan_varastot_yhteensa;
				$arvo_ulkomaa	= $ulkomaan_varastot_yhteensa + $nettoulkomaan_varastot_yhteensa;
			}


			$ycspan="";

			if ($kukarow['hinnat'] == 1 and $kukarow['extranet'] != '') {
				$ycspan=3;
			}
			elseif ($kukarow['hinnat'] == 0 and $kukarow['extranet'] != '') {
				$ycspan=4;
			}
			elseif($kukarow["resoluutio"] == "I") {
				$ycspan=5;
			}
			else{
				$ycspan=4;
			}

			//Jos myyj� on myym�ss� olkomaan varastoista liiabn pienell� summalla
			if ($kukarow["extranet"] == "" and $arvo_ulkomaa != 0 and $arvo_ulkomaa <= $yhtiorow["suoratoim_ulkomaan_alarajasumma"]) {
				$ulkom_huom = "<font class='error'>".t("HUOM! Summa on liian pieni ulkomaantoimitukselle. Raja on").": $yhtiorow[suoratoim_ulkomaan_alarajasumma] $yhtiorow[valkoodi] --></font>";
			}
			else {
				$ulkom_huom = "";
			}

			//Jos myyj� ei ole extranetaaja ja h�n myy useasta eri varastosta jotka sijaitsee useissa eri maissa niin n�ytet��n maakohtaiset summat
			if ($kukarow['extranet'] == '' and $arvo_ulkomaa != 0) {
				echo "<tr>
					<td class='back' colspan='$ycspan'></td>
					<td class='back' colspan='4' align='right'>".t("Kotimaan varastoista").":</td>
					<td class='spec' align='right'>".sprintf("%.2f",$arvo_kotimaa)."</td>
					<td class='spec'>$laskurow[valkoodi]</td></tr>";
				echo "<tr>
					<td class='back' colspan='$ycspan' align='right'>$ulkom_huom</td>
					<td class='back' colspan='4' align='right'>".t("Ulkomaan varastoista").":</td>
					<td class='spec' align='right'>".sprintf("%.2f",$arvo_ulkomaa)."</td>
					<td class='spec'>$laskurow[valkoodi]</td></tr>";
				echo "<tr>
					<td class='back' colspan='$ycspan'></td>
					<td class='back' colspan='4' align='right'>".t("Arvo yhteens�").":</td>
					<td class='spec' align='right'>".sprintf("%.2f",$arvo)."</td>
					<td class='spec'>$laskurow[valkoodi]</td></tr>";
			}
			else {
				echo "<tr>
					<td class='back' colspan='$ycspan'></td>
					<td class='back' colspan='4' align='right'>".t("Tilauksen arvo").":</td>
					<td class='spec' align='right'>".sprintf("%.2f",$arvo)."</td>
					<td class='spec'>$laskurow[valkoodi]</td></tr>";
			}

			//erikoisalennus annetaan vain riveille joilla EI ole NETTOHINTAA
			if ($laskurow['erikoisale'] > 0 and $kukarow['hinnat'] != 1) {

				//Kaikki myynti josta lasketaan erikoisalennus
				$apu1 = (float) $laskurow['erikoisale']/100;	// erikoisale prosentti
				$apu2 = round($yhteensa*$apu1,2); 				// erikoisalen m��r�
				$apu3 = round((1-$apu1)*$yhteensa,2);			// loppusumma

				//Pelk�st��n kotimaan myynti josta lasketaan erikoisalennus
				$kotimaa_apu1 = (float) $laskurow['erikoisale']/100;					// erikoisale prosentti
				$kotimaa_apu2 = round($kotimaan_varastot_yhteensa*$kotimaa_apu1,2); 	// erikoisalen m��r�
				$kotimaa_apu3 = round((1-$kotimaa_apu1)*$kotimaan_varastot_yhteensa,2);	// loppusumma

				//Pelk�st��n ulkomaan myynti josta lasketaan erikoisalennus
				$ulkomaa_apu1 = (float) $laskurow['erikoisale']/100;					// erikoisale prosentti
				$ulkomaa_apu2 = round($ulkomaan_varastot_yhteensa*$ulkomaa_apu1,2); 	// erikoisalen m��r�
				$ulkomaa_apu3 = round((1-$ulkomaa_apu1)*$ulkomaan_varastot_yhteensa,2);	// loppusumma


				echo "<tr>
						<td class='back' colspan='$ycspan'></td>
						<td class='back' colspan='4' align='right'>".t("Erikoisalennus")." ($laskurow[erikoisale]%):</td>
						<td class='spec' align='right'>".sprintf("%.2f",$apu2)."</td>
						<td class='spec'>$laskurow[valkoodi]</td></tr>";

				//Kakki yhteens�
				$kaikkiyhteensa 		= $apu3 + $nettoyhteensa;
				$kotimaa_kaikkiyhteensa = $kotimaa_apu3 + $nettokotimaan_varastot_yhteensa;
				$ulkomaa_kaikkiyhteensa = $ulkomaa_apu3 + $nettoulkomaan_varastot_yhteensa;

				echo "<tr>
					<td class='back' colspan='$ycspan'></td>
					<td class='back' colspan='4' align='right'>".t("Yhteens� kotimaa").":</td>
					<td class='spec' align='right'>".sprintf("%.2f",$kotimaa_kaikkiyhteensa)."</td>
					<td class='spec'>$laskurow[valkoodi]</td></tr>";

				echo "<tr>
					<td class='back' colspan='$ycspan' align='right'>$ulkom_huom</td>
					<td class='back' colspan='4' align='right'>".t("Yhteens� ulkomaa").":</td>
					<td class='spec' align='right'>".sprintf("%.2f",$ulkomaa_kaikkiyhteensa)."</td>
					<td class='spec'>$laskurow[valkoodi]</td></tr>";
				echo "<tr>
					<td class='back' colspan='$ycspan'></td>
					<td class='back' colspan='4' align='right'>".t("Yhteens�").":</td>
					<td class='spec' align='right'>".sprintf("%.2f",$kaikkiyhteensa)."</td>
					<td class='spec'>$laskurow[valkoodi]</td></tr>";

			}
			else {
				$kaikkiyhteensa 		= $arvo;
				$kotimaa_kaikkiyhteensa = $arvo_kotimaa;
				$ulkomaa_kaikkiyhteensa = $arvo_ulkomaa;
			}

			//annetaan mahdollisuus antaa loppusumma joka jyvitet��n riveille arvoosuuden mukaan
			if ($kukarow["extranet"] == "" and $kukarow['kassamyyja'] != '') {

				if ($jyvsumma== '') {
					$jyvsumma='0.00';
				}

				echo "	<tr>
						<td class='back'>&nbsp;</td>
						</tr>
						<tr>
						<form name='pyorista' action='$PHP_SELF' method='post' autocomplete='off'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>
						<input type='hidden' name='tee' value='jyvita'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='arvo' value='$arvo'>

						<td class='back' colspan='$ycspan'></td>
						<th colspan='4'>".t("Py�rist� loppusummaa").":</th>
						<td class='spec'><input type='text' size='7' name='jyvsumma' value='$jyvsumma'></td>
						<td class='back' colspan='2'><input type='submit' value='".t("Jyvit�")."'></td>
						</tr>
						</form>";
			}

			//annetaan ennakkomaksukentt�
			if ($kukarow["extranet"] == "" and $yhtiorow["ennakkomaksu_tuotenumero"] != '' and $toim == "TARJOUS") {

				echo "	<tr>
						<td class='back'>&nbsp;</td>
						</tr>
						<tr>
						<form action='$PHP_SELF' method='post' autocomplete='off'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>
						<input type='hidden' name='tila' value='lisaa_ennakkomaksu'>
						<input type='hidden' name='toim' value='$toim'>";

				$query = "	SELECT *, round((varattu+jt+kpl) * hinta * (1-(ale/100)),2) rivihinta
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]'
							and otunnus = '$kukarow[kesken]'
							and tuoteno = '$yhtiorow[ennakkomaksu_tuotenumero]'
							and tyyppi != 'D'";
				$yresult = mysql_query($query) or pupe_error($query);

				$ennakkosumma 		= 0;
				$ennakkokpl			= 0;
				$ennakkosumma_plus	= 0;
				$ennakkoerror		= 0;

				while ($row = mysql_fetch_array($yresult)) {
					$ennakkosumma += $row["rivihinta"];

					if($row["rivihinta"] > 0) {
						$ennakkosumma_plus += $row["rivihinta"];
					}

					$ennakkokpl++;
				}

				if(round($ennakkosumma,2) != 0) {
					echo "K�sirahaongelmia!<br>";
					$ennakkoerror++;
				}
				if($ennakkokpl !=0 and $ennakkokpl != 2) {
					echo "K�sirahaongelmia taas!<br>";
					$ennakkoerror++;
				}

				if($ennakkosumma_plus >= 0 and $ennakkoerror == 0) {
					echo "	<td class='back' colspan='$ycspan'></td>
							<th colspan='4'>".t("Ennakkomaksu/K�siraha").":</th>
							<td><input type='text' name='ennakkomaksu' value='$ennakkosumma_plus' size='7'></td>
							<td class='back' colspan='2'><input type='submit' value='".t("Tallenna")."'></td>
							</tr>
							</form>";
				}
			}

			echo "</table>";
		}
		else {
			$tilausok++;
		}

		// JT-rivik�ytt�liittym�
		if ($estetaankomyynti == '' and $muokkauslukko == "" and $rivilaskuri == 0 and $laskurow["liitostunnus"] > 0 and $kukarow["kassamyyja"] == '' and $toim != "TYOMAARAYS" and $toim != "VALMISTAVARASTOON" and $toim != "MYYNTITILI" and $toim != "TARJOUS") {
			//katotaan eka halutaanko asiakkaan jt-rivej� n�kyviin
			$asjtq = "select tunnus from asiakas where yhtio = '$kukarow[yhtio]' and ytunnus = '$laskurow[ytunnus]' and jtrivit = 1";
			$asjtapu = mysql_query($asjtq) or pupe_error($asjtq);

			if (mysql_num_rows($asjtapu) == 0) {

				echo "<br>";

				$toimittaja		= "";
				$toimittajaid	= "";

				$asiakasno 		= $laskurow["ytunnus"];
				$asiakasid		= $laskurow["liitostunnus"];

				$automaaginen 	= "";
				$tyyppi 		= "T";
				$tee			= "JATKA";
				$tuotenumero	= "";
				$toimi			= "";
				$tilaus_on_jo 	= "KYLLA";
				$superit		= "";

				require ('jtselaus.php');
			}
	    }
	}

	echo "</td>";

	echo "<td class='back'>";

	if ($rivilaskuri > 0) {
		//require ("tarjous_ruudulle.inc");
	}

	echo "</td>";

	echo "</tr>";
	echo "</table>";

	// tulostetaan loppuun parit napit..
	if ((int) $kukarow["kesken"] != 0) {
		echo "<br><table width='100%'><tr>";

		if ($kukarow["extranet"] == "" and $toim == "MYYNTITILI" and $laskurow["alatila"] == "V") {
			echo "	<td class='back'>
					<form name='valmis' action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tee' value='LASKUTAMYYNTITILI'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='submit' value='* ".t("Laskuta valitut rivit")." *'>
					</form></td>";

			echo "	<td class='back'>
					<form name='valmis' action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tee' value='LEPAAMYYNTITILI'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='submit' value='* ".t("J�t� myyntitili lep��m��n")." *'>
					</form></td>";

		}


		if($kukarow["extranet"] == "" and $muokkauslukko == "" and $toim == "TYOMAARAYS") {
			echo "	<td class='back'>
					<form name='valmis' action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tee' value='LEPAA'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='submit' value='* ".t("Ty�m��r�ys lep��m��n")." *'>
					</form></td>";

		}

		if($kukarow["extranet"] == "" and $muokkauslukko == "" and $toim == "TARJOUS"  and $laskurow["liitostunnus"] > 0 and $tilausok == 0 and $rivilaskuri > 0) {
			echo "	<td class='back'>
					<form name='valmis' action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tee' value='HYVAKSYTARJOUS'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='submit' value='".t("Hyv�ksy tarjous")."'>
					</form>

					<form name='valmis' action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tee' value='HYLKAATARJOUS'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='submit' value='".t("Hylk�� tarjous")."'>
					</form>

					</td>";
		}

		echo "<td class='back' valign='top'>";

		//N�ytet��n tilaus valmis nappi
		if ($muokkauslukko == "" and $laskurow["liitostunnus"] > 0 and $tilausok == 0 and $rivilaskuri > 0) {

			// Jos myyj� myy todella pienell� summalta varastosta joka sijaitsee ulkmailla niin herjataan heiman
			$javalisa = "";
			if ($kukarow["extranet"] == "" and $ulkomaa_kaikkiyhteensa != 0 and $ulkomaa_kaikkiyhteensa <= $yhtiorow["suoratoim_ulkomaan_alarajasumma"]) {
				echo "<SCRIPT LANGUAGE=JAVASCRIPT>
						function ulkomaa_verify(){
								msg = '".t("Olet toimittamassa ulkomailla sijaitsevasta varastosta tuotteita")." $ulkomaa_kaikkiyhteensa $yhtiorow[valkoodi]! ".t("Oletko varma, ett� t�m� on fiksua")."?';
								return confirm(msg);
						}
				</SCRIPT>";

				 $javalisa = "onSubmit = 'return ulkomaa_verify()'";
			}

			echo "
				<form action='$PHP_SELF' method='post' $javalisa>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='tee' value='VALMIS'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='kaikkiyhteensa' value='$kaikkiyhteensa'>";

			if($toimitetaan_ulkomaailta > 0) {
				echo "<input type='hidden' name='toimitetaan_ulkomaailta' value='YES'>";
			}

			// otetaan maksuehto selville.. k�teinen muuttaa asioita
			$query = "	select *
						from maksuehto
						where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
			$result = mysql_query($query) or pupe_error($query);

			$kateinen = "";

			if (mysql_num_rows($result)==1) {
				$maksuehtorow = mysql_fetch_array($result);
				// jos kyseess� on k�teiskauppaa
				if ($maksuehtorow['kateinen']!='') {
					$kateinen = "X";
				}
			}

			if ($kukarow["extranet"] == "" and $kateinen == 'X' and $kukarow["kassamyyja"] != '') {
				echo t("Valitse kuittikopion tulostuspaikka").":<br>";
				echo "<select name='valittu_kopio_tulostin'>";
				echo "<option value=''>".t("Ei kirjoitinta")."</option>";

				$querykieli = "	select *
								from kirjoittimet
								where yhtio = '$kukarow[yhtio]'
								ORDER BY kirjoitin";
				$kires = mysql_query($querykieli) or pupe_error($querykieli);

				while ($kirow=mysql_fetch_array($kires)) {
					echo "<option value='$kirow[tunnus]'>$kirow[kirjoitin]</option>";
				}

				echo "</select><br><br></td></tr><tr><td class='back'>";
			}



			echo "<input type='submit' value='$otsikko ".t("valmis")."'>";
			echo "</form>";
		}

		if ($muokkauslukko == "") {
			echo "<SCRIPT LANGUAGE=JAVASCRIPT>
						function verify(){
								msg = '".t("Haluatko todella poistaa t�m�n tietueen?")."';
								return confirm(msg);
						}
				</SCRIPT>";

			echo "</td><td align='right' class='back' valign='top'>
					<form name='valmis' action='$PHP_SELF' method='post' onSubmit = 'return verify()'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tee' value='POISTA'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='submit' value='* ".t("Mit�t�i koko")." $otsikko *'>
					</form></td></tr></table>";
		}
	}
}

if (file_exists("../inc/footer.inc")) {
	require ("../inc/footer.inc");
}
else {
	require ("footer.inc");
}

?>