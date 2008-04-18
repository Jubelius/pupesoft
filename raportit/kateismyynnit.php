<?php
	///* T�m� skripti k�ytt�� slave-tietokantapalvelinta *///
	$useslave = 1;
	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("K�teismyynnit")." $myy:</font><hr>";

	// Tarkistetaan ett� jos ei ole t�sm�ys p��ll� niin lukitaan t�sm�yksen p�iv�m��r�t. Jos t�sm�ys on p��ll�, lukitaan normaalin raportin p�iv�m��r�t
	echo "	<script type='text/javascript' language='JavaScript'>
			<!--
				function disableDates() {
					if (document.getElementById('tasmays').checked != true) {
						document.getElementById('pp').disabled = true;
						document.getElementById('kk').disabled = true;
						document.getElementById('vv').disabled = true;

						document.getElementById('ppa').disabled = false;
						document.getElementById('kka').disabled = false;
						document.getElementById('vva').disabled = false;

						document.getElementById('ppl').disabled = false;
						document.getElementById('kkl').disabled = false;
						document.getElementById('vvl').disabled = false;
					}
					else {
						document.getElementById('pp').disabled = false;
						document.getElementById('kk').disabled = false;
						document.getElementById('vv').disabled = false;

						document.getElementById('ppa').disabled = true;
						document.getElementById('kka').disabled = true;
						document.getElementById('vva').disabled = true;

						document.getElementById('ppl').disabled = true;
						document.getElementById('kkl').disabled = true;
						document.getElementById('vvl').disabled = true;
					}
				}
			-->
			</script>";

	// Lockdown-funktio, joka tarkistaa onko kyseinen kassalipas jo t�sm�tty.
	function lockdown($vv, $kk, $pp, $tasmayskassa) {
		global $kukarow, $kassakone, $yhtiorow;

		if ($tasmayskassa == 'MUUT') {
			$row["nimi"] = 'MUUT';
		}
		else {
			$query = "SELECT nimi FROM kassalipas WHERE tunnus='$tasmayskassa' AND yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
			$row = mysql_fetch_array($result);
		}

		$tasmays_query = "	SELECT group_concat(distinct lasku.tunnus) ltunnukset
							FROM lasku
							JOIN tiliointi ON (tiliointi.yhtio = lasku.yhtio
							AND tiliointi.ltunnus = lasku.tunnus
							AND tiliointi.selite LIKE '%$row[nimi]%'
							AND tiliointi.korjattu = '')
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							AND lasku.tila = 'X'
							AND lasku.tapvm = '$vv-$kk-$pp'";
		$tasmays_result = mysql_query($tasmays_query) or pupe_error($tasmays_query);
		$tasmaysrow = mysql_fetch_array($tasmays_result);

		if ($tasmaysrow["ltunnukset"] != "") {
			$tasmatty = array();
			$tasmatty["ltunnukset"] = $tasmaysrow["ltunnukset"];
			$tasmatty["kassalipas"] = $row["nimi"];
			
			return $tasmatty;
		}
		else {
			return false;
		}
	}

	function tosite_print ($vv, $kk, $pp, $ltunnukset, $tulosta = null) {
		global $kukarow, $kassakone, $yhtiorow, $printteri;

			$kassat_temp = "";

			if (is_array($ltunnukset)) {
				$kassat_temp = $ltunnukset["ltunnukset"];
			}

			$tasmays_query = "	SELECT tiliointi.*, lasku.comments kommentti
								FROM lasku
								JOIN tiliointi ON (tiliointi.yhtio = lasku.yhtio
								AND tiliointi.ltunnus = lasku.tunnus
								AND tiliointi.korjattu = '')
								JOIN tili ON (tili.yhtio = tiliointi.yhtio
								AND tili.tilino = tiliointi.tilino)
								WHERE lasku.yhtio = '$kukarow[yhtio]'
								AND lasku.tunnus in ('$kassat_temp')
								ORDER BY tiliointi.tunnus, tiliointi.selite";
			$tasmays_result = mysql_query($tasmays_query) or pupe_error($tasmays_query);

			//kirjoitetaan  faili levylle..
			$filenimi = "/tmp/KATKIRJA.txt";
			$fh = fopen($filenimi, "w+");

			$linebreaker = "";
			$tilit = 0;
			$selite_count = 40;

			if (!is_array($kassakone) and strlen($kassakone) > 0) {
				$kassakone = unserialize(urldecode($kassakone));
			}

			if (is_array($kassakone)) {
				foreach($kassakone as $var) {
					$kassat .= "'".$var."',";
				}
				$kassat = substr($kassat,0,-1);
			}

			if ($kassat_temp != "" and !is_array($kassakone)) {
				$kassat = $kassat_temp;
			}

			$query = "SELECT kateistilitys, kassaerotus, kateisotto FROM kassalipas WHERE tunnus in ($kassat) AND yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
			$row = mysql_fetch_array($result);

			if (is_array($kassakone) and count($kassakone) > 0) {
				$tilit = count($kassakone);
			}
			
			for ($ii = 0; $ii < $tilit; $ii++) {
				$linebreaker .= "-----------";
				$selite_count += 10;
			}

			$edltunnus = "X";
			$edselitelen = 0;

			while ($tasmaysrow = mysql_fetch_array($tasmays_result)) {
				if ($tasmaysrow["tilino"] != $row["kateistilitys"] and $tasmaysrow["tilino"] != $row["kassaerotus"] and $tasmaysrow["tilino"] != $row["kateisotto"] and !stristr($tasmaysrow["selite"], t("erotus"))) {

					if ($edltunnus != $tasmaysrow["ltunnus"]) {

						$ots  = t("K�teismyynnin tosite")." ({$tasmaysrow["ltunnus"]}) $yhtiorow[nimi] $pp.$kk.$vv\n\n";
						$ots .= sprintf ('%-'.$selite_count.'.'.$selite_count.'s', t("Tapahtuma"));
						$ots .= sprintf ('%-13.13s', t("Summa"));
						$ots .= "\n";
						$ots .= "$linebreaker--------------------------------------------------------------------\n";
						fwrite($fh, $ots);
						$ots = chr(12).$ots;

						$edltunnus = $tasmaysrow["ltunnus"];
					}

					if ($edselitelen != strlen($tasmaysrow["selite"]) and $edselitelen != 0) {
						$prn = "\n";
						fwrite($fh, $prn);
						$rivit++;
					}

					$kommentti = $tasmaysrow["kommentti"];

					if ($rivit >= 60) {
						fwrite($fh, $ots);
						$rivit = 1;
					}
					$prn = sprintf ('%-'.$selite_count.'.'.$selite_count.'s', 	$tasmaysrow["selite"]);
					$prn .= str_replace(".",",",sprintf ('%-13.13s', 	$tasmaysrow["summa"]));
					$prn .= "\n";

					fwrite($fh, $prn);
					$rivit++;

					$edselitelen = strlen($tasmaysrow["selite"]);
				}
			}

			$prn  = "\n";
			$prn .= sprintf ('%-500.500s', 	$kommentti);
			$prn .= "\n\n";
			$rivit++;
			fwrite($fh, $prn);

			echo "<pre>",file_get_contents($filenimi),"</pre>";
			fclose($fh);

			if ($tulosta != null) {
				//haetaan tilausken tulostuskomento
				$query   = "SELECT * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$printteri'";
				$kirres  = mysql_query($query) or pupe_error($query);
				$kirrow  = mysql_fetch_array($kirres);
				$komento = $kirrow['komento'];

				//--no-header 
				$line = exec("a2ps -o $filenimi.ps -R --medium=A4 --chars-per-line=94 --columns=1 --margin=1 --borders=0 $filenimi");

				// itse print komento...
				$line = exec("$komento $filenimi.ps");

				//poistetaan tmp file samantien kuleksimasta...
				system("rm -f $filenimi");
				system("rm -f $filenimi.ps");
			}
	}

	// Tarkistetaan eri�v�tk� kassalippaiden pankki- ja luottokorttitilit
	if ($tasmays != '' and count($kassakone) > 1) {
		$kassat_temp = "";

		foreach($kassakone as $var) {
			$kassat_temp .= "'".$var."',";
		}

		$kassat_temp = substr($kassat_temp,0,-1);
		
		$query = "SELECT * FROM kassalipas WHERE yhtio='$kukarow[yhtio]' and tunnus in ($kassat_temp)";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 1) {
			
			$account_check = array();
			
			while ($row = mysql_fetch_array($result)) {
				$account_check["luottokortti"][] = $row["luottokortti"];
				$account_check["pankkikortti"][] = $row["pankkikortti"];
			}

			$foo = "";
			$foo = array_count_values($account_check["luottokortti"]);

			if (count($foo) > 1) {
				echo "<font class='error'>".t("Kassalippaiden pankki- ja luottokorttitilit eri�v�t").".</font><br>";
				echo "<font class='error'>".t("Tarkista tiedot ja kokeile uudelleen").".</font><br><br>";
				$tee = '';
			}
			else {
				$foo = "";
			}

			if ($foo == "") {
				$foo = "";
				$foo = array_count_values($account_check["pankkikortti"]);

				if (count($foo) > 1) {
					echo "<font class='error'>".t("Kassalippaiden pankki- ja luottokorttitilit eri�v�t").".</font><br>";
					echo "<font class='error'>".t("Tarkista tiedot ja kokeile uudelleen").".</font><br><br>";
					$tee = '';
				}
				else {
					$foo = "";
				}
			}
		}
	}

	// Jos t�sm�ys on p��ll� ja ei olla valittu mit��n kassalipasta -> error
	if ($tasmays != '' and count($kassakone) == 0 and $muutkassat == '') {
		echo "<font class='error'>".t("Valitse kassalipas")."!</font><br>";
		$tee = '';
	}

	// Jos t�sm�ys on p��ll� ja tilitett�vien sarakkeiden m��r� on jotain muuta kuin v�lilt� 1-9 -> error
	if ($tasmays != '' and ((int)$tilityskpl < 1 or (int)$tilityskpl > 9)) {
		echo "<font class='error'>".t("Tilitysten m��r� pit�� olla v�lilt� 1 - 9")."!</font><br>";
		$tee = '';
	}

	// Jos t�sm�ys on p��ll� ja ei olla annettu p�iv�m��r�� -> error
	if ($tasmays != '' and ($vv == '' or $kk == '' or $pp == '')) {
		echo "<font class='error'>".t("Sy�t� p�iv�m��r� (pp-kk-vvvv)")."</font><br>";
		$tee = '';
	}

	// Ei osata viel� t�sm�t� k�teissuorituksia
	if ($tasmays != '' and $katsuori != '') {
		echo "<font class='error'>".t("Sin� et osaa viel� t�sm�ytt�� k�teissuorituksia.")."</font><br>";
		$tee = '';
	}

	// Tarkistetaan ettei kassalippaiden tilej� puutu
	if ($tasmays != '' and count($kassakone) > 0) {
		$kassat_temp = "";

		foreach($kassakone as $var) {
			$kassat_temp .= "'".$var."',";
		}
		$kassat_temp = substr($kassat_temp,0,-1);

		$query = "SELECT * FROM kassalipas WHERE yhtio='$kukarow[yhtio]' and tunnus in ($kassat_temp) and kassa != '' and pankkikortti != '' and luottokortti != '' and kateistilitys != '' and kassaerotus != '' and kateisotto != ''";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != count($kassakone)) {
			echo "<font class='error'>".t("Ei voida t�sm�ytt��. Kassalippaan pakollisia tietoja puuttuu").".</font><br>";
			$tee = '';
		}
	}

	// Aloitetaan tili�inti
	if ($tee == "tiliointi") {
		
		$ktunnukset = "";
		
		$kassalipas_tunnus = unserialize(urldecode($kassalipas_tunnus));
		
		if (count($kassalipas_tunnus) > 0) {
			foreach ($kassalipas_tunnus as $key => $ktunnus) {
				$ktunnukset .= "'$ktunnus',";
			}
			$ktunnukset = substr($ktunnukset, 0, -1);
		}

		$query = "INSERT INTO lasku SET
					yhtio      = '$kukarow[yhtio]',
					tapvm      = '$vv-$kk-$pp',
					tila       = 'X',
					laatija    = '$kukarow[kuka]',
					luontiaika = now()";
		$result = mysql_query($query) or pupe_error($query);
		$laskuid = mysql_insert_id();

		$maksutapa = "";
		$kassalipas = "";
		$tilino = "";
		$pohjakassa = "";
		$loppukassa = "";
		$comments = "";
		$comments_yht = "";
		$tyyppi = "";

		foreach ($_POST as $kentta => $arvo) {

			if (stristr($kentta, "pohjakassa")) {
				if (stristr($kentta, "tyyppi")) {
					$tyyppi = $arvo;
					$comments .= "$arvo alkukassa: ";
				}
				else {
					$arvo = str_replace(".",",",sprintf('%.2f',$arvo));
					$comments .= "$arvo<br>";
					$pohjakassa += $arvo;
				}
			}
			else if (stristr($kentta,"yht_lopkas")) {
				$arvo = str_replace(".",",",sprintf('%.2f',$arvo));
				$comments .= "$tyyppi loppukassa: $arvo<br><br>";
			}

			if (stristr($kentta, "yht_")) {
				if ($kentta == "yht_kat") {
					$comments_yht .= "K�teinen yhteens�: ";
					$arvo = str_replace(".",",",sprintf('%.2f',$arvo));
					$comments_yht .= "$arvo<br>";
				}
				else if ($kentta == "yht_katot") {
					$comments_yht .= "K�teisotto yhteens�: ";
					$arvo = str_replace(".",",",sprintf('%.2f',$arvo));
					$comments_yht .= "$arvo<br>";
				}
				else if ($kentta == "yht_kattil") {
					$comments_yht .= "K�teistilitys yhteens�: ";
					$arvo = str_replace(".",",",sprintf('%.2f',$arvo));
					$comments_yht .= "$arvo<br>";
				}
			}
			
			if (stristr($kentta, "loppukassa")) {
				$loppukassa = $arvo;
			}

			if (stristr($arvo, "pankkikortti")) {
				$maksutapa = t("Pankkikortti");

				list ($maksutapa_devnull, $tilino, $kassalipas) = explode("#", $arvo);

				// Haetaan kassalipastiedot tietokannasta
				$query = "SELECT * FROM kassalipas WHERE yhtio='$kukarow[yhtio]' AND tunnus IN ($ktunnukset) AND pankkikortti = $tilino";
				$result = mysql_query($query) or pupe_error($query);
				$kassalipasrow = mysql_fetch_array($result);

				$tilino = $kassalipasrow["pankkikortti"];
			}
			elseif (stristr($arvo, "luottokortti")) {
				$maksutapa = t("Luottokortti");

				list ($maksutapa_devnull, $tilino, $kassalipas) = explode("#", $arvo);

				// Haetaan kassalipastiedot tietokannasta
				$query = "SELECT * FROM kassalipas WHERE yhtio='$kukarow[yhtio]' AND tunnus IN ($ktunnukset) AND luottokortti = $tilino";
				$result = mysql_query($query) or pupe_error($query);
				$kassalipasrow = mysql_fetch_array($result);

				$tilino = $kassalipasrow["luottokortti"];
			}
			elseif (stristr($arvo, "kateinen")) {
				$maksutapa = t("K�teinen");

				list ($maksutapa_devnull, $tilino, $kassalipas) = explode("#", $arvo);

				// Haetaan kassalipastiedot tietokannasta
				$query = "SELECT * FROM kassalipas WHERE yhtio='$kukarow[yhtio]' AND tunnus IN ($ktunnukset) AND kassa = $tilino AND nimi = '$kassalipas'";
				$result = mysql_query($query) or pupe_error($query);
				$kassalipasrow = mysql_fetch_array($result);

				$tilino = $kassalipasrow["kassa"];
			}

			// Tarkistetaan ettei arvo ole nolla ja jos kent�n nimi on joko solu tai erotus
			// Ei haluta tositteeseen nollarivej�
			if (abs(str_replace(",",".",$arvo)) > 0 and (stristr($kentta, "solu") or stristr($kentta, "erotus"))) {

				// Pilkut pisteiksi
				$arvo = str_replace(",",".",$arvo);

				// Aletaan rakentaa insertti�
				$query = "INSERT INTO tiliointi SET
							yhtio    = '$kukarow[yhtio]',
							ltunnus  = '$laskuid',";

				// Jos kent�n nimi on soluerotus niin se tili�id��n kassaerotustilille (eli t�sm�yserot), muuten normaalisti ylemp�n� parsetettu tilinumero
				if (stristr($kentta, "soluerotus")) {
					$query .= "tilino = '$kassalipasrow[kassaerotus]',";
				}
				else {
					$query .= "tilino   = '$tilino',";
				}

				$query .=  "kustp    = '',
							tapvm    = '$vv-$kk-$pp',";

				// Jos kentt� on soluerotus tai erotus niin kerrotaan arvo -1:ll�
				if (stristr($kentta, "soluerotus") or stristr($kentta, "erotus")) {
					$query .= "summa = $arvo * -1,";
				}
				else {
					$query .= "summa = '$arvo',";
				}

				$query .= "	vero     = '0',
							lukko    = '',
							selite   = '$kassalipas $maksutapa";

				// Jos kentt� on erotus niin lis�t��n selitteeseen "erotus"
				if (stristr($kentta, "erotus")) {
					$query .= " ".t("erotus")."',";
				}
				// Jos kentt� on soluerotus niin lis�t��n selitteeseen "kassaero"
				elseif (stristr($kentta, "soluerotus")) {
					$query .= " ".t("kassaero")."',";
				}
				else {
					$query .= "',";
				}

				$query .=  "laatija  = '$kukarow[kuka]',
							laadittu = now()";
				$result = mysql_query($query) or pupe_error($query);
			}

			// Jos kentt� on k�teistilitys, niin toinen tili�id��n k�teistilitys-tilille ja se summa my�s miinustetaan kassasta
			if (abs(str_replace(",",".",$arvo)) > 0 and stristr($kentta, "kateistilitys")) {
				$arvo = str_replace(",",".",$arvo);
				$query = "INSERT INTO tiliointi SET
							yhtio    = '$kukarow[yhtio]',
							ltunnus  = '$laskuid',
							tilino   = '$kassalipasrow[kateistilitys]',
							kustp    = '',
							tapvm    = '$vv-$kk-$pp',
							summa    = $arvo,
							vero     = '0',
							lukko    = '',
							selite   = '$kassalipas ".t("K�teistilitys pankkiin kassasta")."',
							laatija  = '$kukarow[kuka]',
							laadittu = now()";
				$result = mysql_query($query) or pupe_error($query);

				$query = "INSERT INTO tiliointi SET
							yhtio    = '$kukarow[yhtio]',
							ltunnus  = '$laskuid',
							tilino   = '$kassalipasrow[kassa]',
							kustp    = '',
							tapvm    = '$vv-$kk-$pp',
							summa    = $arvo * -1,
							vero     = '0',
							lukko    = '',
							selite   = '$kassalipas ".t("K�teistilitys pankkiin kassasta")."',
							laatija  = '$kukarow[kuka]',
							laadittu = now()";
				$result = mysql_query($query) or pupe_error($query);
			}

			// Jos kentt� on k�teisotto, niin toinen tili�id��n k�teisotto-tilille ja se summa my�s miinustetaan kassasta
			if (abs(str_replace(",",".",$arvo)) > 0 and stristr($kentta, "kateisotto")) {
				$arvo = str_replace(",",".",$arvo);
				$query = "INSERT INTO tiliointi SET
							yhtio    = '$kukarow[yhtio]',
							ltunnus  = '$laskuid',
							tilino   = '$kassalipasrow[kateisotto]',
							kustp    = '',
							tapvm    = '$vv-$kk-$pp',
							summa    = $arvo,
							vero     = '0',
							lukko    = '',
							selite   = '$kassalipas ".t("K�teisotto kassasta")."',
							laatija  = '$kukarow[kuka]',
							laadittu = now()";
				$result = mysql_query($query) or pupe_error($query);

				$query = "INSERT INTO tiliointi SET
							yhtio    = '$kukarow[yhtio]',
							ltunnus  = '$laskuid',
							tilino   = '$kassalipasrow[kassa]',
							kustp    = '',
							tapvm    = '$vv-$kk-$pp',
							summa    = $arvo * -1,
							vero     = '0',
							lukko    = '',
							selite   = '$kassalipas ".t("K�teisotto kassasta")."',
							laatija  = '$kukarow[kuka]',
							laadittu = now()";
				$result = mysql_query($query) or pupe_error($query);
			}
		}

		$pohjakassa = str_replace(".",",",sprintf('%.2f',$pohjakassa));

		$comments_yht .= "Loppukassa yhteens�: ";
		$loppukassa = str_replace(".",",",sprintf('%.2f',$loppukassa));
		$comments_yht .= "$loppukassa<br>";

		$query = "	UPDATE lasku SET comments = '$comments<br>".t("Alkukassa yhteens�").": $pohjakassa<br>$comments_yht'
					WHERE yhtio  = '$kukarow[yhtio]'
					AND tunnus = $laskuid";
		$result = mysql_query($query) or pupe_error($query);

		$tulosta = "kyll�";
		$lasku_id = array();
		$lasku_id["ltunnukset"] = $laskuid;
		tosite_print($vv, $kk, $pp, $lasku_id, $tulosta);
	}

	elseif ($tee != '') {

		//Jos halutaa failiin
		if ($printteri != '') {
			$vaiht = 1;
		}
		else {
			$vaiht = 0;
		}

		$kassat = "";
		$lisa   = "";

		if (is_array($kassakone)) {
			foreach($kassakone as $var) {
				$kassat .= "'".$var."',";
			}
			$kassat = substr($kassat,0,-1);
		}

		if ($muutkassat != '') {
			if ($kassat != '') {
				$kassat .= ",''";
			}
			else {
				$kassat = "''";
			}
		}

		if ($kassat != "") {
			$kassat = " and lasku.kassalipas in ($kassat) ";
		}
		else {
			$kassat = " and lasku.kassalipas = 'ei nayteta eihakat, akja'";
		}

		if ((int) $myyjanro > 0) {
			$query = "	SELECT tunnus
						FROM kuka
						WHERE yhtio	= '$kukarow[yhtio]'
						and myyja 	= '$myyjanro'";
			$result = mysql_query($query) or pupe_error($query);
			$row = mysql_fetch_array($result);

			$lisa = " and lasku.myyja='$row[tunnus]' ";
		}
		elseif ($myyja != '') {
			$lisa = " and lasku.laatija='$myyja' ";
		}

		$lisa .= " and lasku.vienti in (";

		if ($koti == 'KOTI' or ($koti=='' and $ulko=='')) {
			$lisa .= "''";
		}

		if ($ulko == 'ULKO') {
			if ($koti == 'KOTI') {
				$lisa .= ",";
				}
			$lisa .= "'K','E'";
		}
		$lisa .= ") ";

		if ($tasmays != '') {
			//ylikirjotetaan koko lis�, koska ei saa olla muita rajauksia
			$lisa = " and lasku.tapvm = '$vv-$kk-$pp'";
		}
		else {
			if ($vva == $vvl and $kka == $kkl and $ppa == $ppl) {
				$lisa .= " and lasku.tapvm = '$vva-$kka-$ppa'";
			}
			else {
				$lisa .= " and lasku.tapvm >= '$vva-$kka-$ppa' and lasku.tapvm <= '$vvl-$kkl-$ppl'";
			}
		}

		$myyntisaamiset_tilit = "'{$yhtiorow['kassa']}','{$yhtiorow['pankkikortti']}','{$yhtiorow['luottokortti']}',";

		if (count($kassakone) > 0) {
			$kassat_temp = "";

			foreach($kassakone as $var) {
				$kassat_temp .= "'".$var."',";
			}

			$kassat_temp = substr($kassat_temp,0,-1);

			$query = "SELECT * FROM kassalipas WHERE yhtio='$kukarow[yhtio]' and tunnus in ($kassat_temp)";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == count($kassakone)) {
				while ($row = mysql_fetch_array($result)) {
					if ($row["kassa"] != $yhtiorow["kassa"]) {
						$myyntisaamiset_tilit .= "'{$row['kassa']}',";
					}
					if ($row["pankkikortti"] != $yhtiorow["pankkikortti"]) {
						$myyntisaamiset_tilit .= "'{$row['pankkikortti']}',";
					}
					if ($row["luottokortti"] != $yhtiorow["luottokortti"]) {
						$myyntisaamiset_tilit .= "'{$row['luottokortti']}',";
					}
				}
			}
			else {
				die("virhe");
			}

		}

		$myyntisaamiset_tilit = substr($myyntisaamiset_tilit, 0, -1);

		//jos monta kassalipasta niin tungetaan t�m� queryyn.
		if (count($kassakone) > 1 and $tasmays != '') {
			$selecti = "if(tiliointi.tilino = kassalipas.kassa OR tiliointi.tilino = '$yhtiorow[kassa]', concat(kassalipas.nimi, ' kateinen'),
				if(tiliointi.tilino = kassalipas.pankkikortti OR tiliointi.tilino = '$yhtiorow[pankkikortti]', 'Pankkikortti',
				if(tiliointi.tilino = kassalipas.luottokortti OR tiliointi.tilino = '$yhtiorow[luottokortti]', 'Luottokortti', 'Muut'))) tyyppi, ";
		}
		else {
			$selecti = "if(tiliointi.tilino = kassalipas.kassa OR tiliointi.tilino = '$yhtiorow[kassa]', 'Kateinen',
				if(tiliointi.tilino = kassalipas.pankkikortti OR tiliointi.tilino = '$yhtiorow[pankkikortti]', 'Pankkikortti',
				if(tiliointi.tilino = kassalipas.luottokortti OR tiliointi.tilino = '$yhtiorow[luottokortti]', 'Luottokortti', 'Muut'))) tyyppi, ";
		}

		//Haetaan k�teislaskut
		$query = "	SELECT
					$selecti
					if(lasku.kassalipas = '', 'Muut', lasku.kassalipas) kassa,
					if(ifnull(kassalipas.nimi, '') = '', 'Muut', kassalipas.nimi) kassanimi,
					tiliointi.tilino,
					tiliointi.summa tilsumma,
					lasku.nimi,
					lasku.ytunnus,
					lasku.laskunro,
					lasku.tunnus,
					lasku.summa,
					lasku.laskutettu,
					lasku.tapvm,
					lasku.kassalipas,
					tiliointi.ltunnus,
					kassalipas.tunnus ktunnus
					FROM lasku use index (yhtio_tila_tapvm)
					JOIN maksuehto ON (maksuehto.yhtio=lasku.yhtio and lasku.maksuehto=maksuehto.tunnus and maksuehto.kateinen != '')
					LEFT JOIN tiliointi ON (tiliointi.yhtio=lasku.yhtio and tiliointi.ltunnus=lasku.tunnus and tiliointi.korjattu = '' and tiliointi.tilino in ($myyntisaamiset_tilit))
					LEFT JOIN kassalipas ON (kassalipas.yhtio=lasku.yhtio and kassalipas.tunnus=lasku.kassalipas)
					WHERE
					lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila = 'U' and lasku.alatila = 'X'
					$lisa
					$kassat
					ORDER BY kassa, kassanimi, tyyppi, lasku.tapvm, lasku.laskunro";
		$result = mysql_query($query) or pupe_error($query);

		$i = 1;

		if (mysql_num_rows($result) == 0) {
			$i = 2;
			echo "<font class='error'>".t("K�teismyyntej� ei l�ydy t�lle p�iv�lle")."</font>";
		}

		$ltunnukset = array();

		// Tarkistetaan ensiksi onko kassalippaat jo tili�ity lockdown-funktion avulla
		if ($tasmays != '') {
			$ltunnusx = array();
			if ($kassakone != '') {
				foreach ($kassakone as $kassax) {
					if ($ltunnusx = lockdown($vv, $kk, $pp, $kassax)) {
						$ltunnukset = array_merge($ltunnukset, $ltunnusx);
						$i++;
					}
				}
				if ($muutkassat != '') {
					if ($ltunnusx = lockdown($vv, $kk, $pp, $muutkassat)) {
						$ltunnukset = array_merge($ltunnukset, $ltunnusx);
						$i++;
					}
				}
			}
			elseif ($kassakone == '' and $muutkassat != '') {
				if ($ltunnusx = lockdown($vv, $kk, $pp, $muutkassat)) {
					$ltunnukset = array_merge($ltunnukset, $ltunnusx);
					$i++;
				}
			}
			
			if (count($ltunnukset) > 0) {
				tosite_print($vv, $kk, $pp, $ltunnukset);
				echo "$ltunnukset[kassalipas] ".t("on jo t�sm�tty. Tosite l�ytyy my�s")." <a href='".$palvelin2."muutosite.php?tee=E&tunnus=$ltunnukset[ltunnukset]'>".t("t��lt�")."</a><br>";
			}
		}

		if ($i > 1) {
			// Jos tositteita l�ytyy niin ei tehd� mit��n
		}
		else {
			if ($tasmays != '') {
				echo "<table><tr><td>";
				echo "<font class='head'>".t("T�sm�ys").":</font><br>";
				echo "<form method='post' action='$PHP_SELF' id='tasmaytysform' onSubmit='return verify();'>";
				echo "<input type='hidden' name='tee' value='tiliointi'>";
				echo "<table width='100%'>";
				echo "<tr>";
				if ($tilityskpl > 1) {
					for ($yyy = 1; $yyy < $tilityskpl; $yyy++) {
						$yyyy = $yyy + 1;
						echo "<td>&nbsp;</td>";
						}
					}
				echo "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td align='center' style='width:100px'>".strtoupper(t("Tilitys"))." 1</td>";
					if ($tilityskpl > 1) {
						for ($yyy = 1; $yyy < $tilityskpl; $yyy++) {
							$yyyy = $yyy + 1;
							echo "<td align='center' style='width:100px'>".strtoupper(t("Tilitys"))." $yyyy</td>";
						}
					}

				echo "<td align='center' style='width:100px'>".strtoupper(t("Myynti"))."</td><td align='center' style='width:100px'>".strtoupper(t("Erotus"))."</td></tr>";
				echo "";
				echo "</tr>";
				
				$row = mysql_fetch_array($result);

				echo "<input type='hidden' name='tyyppi_pohjakassa$i' id='tyyppi_pohjakassa$i' value='$row[kassanimi]'>";
				echo "<tr><td colspan='";
					if ($tilityskpl > 1) {
						echo $tilityskpl+2;
					}
					else {
						echo "3";
					}
				echo "' align='left' class='tumma'>$row[kassanimi] ".t("alkukassa").":</td>";
				echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='pohjakassa$i' name='pohjakassa$i' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");'></td>";
				if ($tilityskpl > 1) {
					for ($yy = 1; $yy < $tilityskpl; $yy++) {
						echo "<td class='tumma' style='width:100px'>&nbsp;</td>";
					}
				}
				echo "<td class='tumma' style='width:100px'>&nbsp;</td><td class='tumma' style='width:100px'>&nbsp;</td></tr></table>";
			}
			else {
				echo "<table><td>";
			}

			echo "<table width='100%' id='nayta$i' style='display:none;'><tr>
					<th nowrap>".t("Kassa")."</th>
					<th nowrap>".t("Asiakas")."</th>
					<th nowrap>".t("Ytunnus")."</th>
					<th nowrap>".t("Laskunumero")."</th>
					<th nowrap>".t("Pvm")."</th>
					<th nowrap>$yhtiorow[valkoodi]</th></tr>";

			if ($tasmays == '' and $vaiht == 1) {
				//kirjoitetaan  faili levylle..
				$filenimi = "/tmp/KATKIRJA.txt";
				$fh = fopen($filenimi, "w+");

				$ots  = t("K�teismyynnin p�iv�kirja")." $yhtiorow[nimi] $ppa.$kka.$vva-$ppl.$kkl.$vvl\n\n";
				$ots .= sprintf ('%-20.20s', t("Kassa"));
				$ots .= sprintf ('%-25.25s', t("Asiakas"));
				$ots .= sprintf ('%-10.10s', t("Y-tunnus"));
				$ots .= sprintf ('%-12.12s', t("Laskunumero"));
				$ots .= sprintf ('%-20.20s', t("Pvm"));
				$ots .= sprintf ('%-13.13s', "$yhtiorow[valkoodi]");
				$ots .= "\n";
				$ots .= "----------------------------------------------------------------------------------------------\n";
				fwrite($fh, $ots);
				$ots = chr(12).$ots;
			}

			$rivit = 1;
			$yhteensa = 0;
			$kassayhteensa = 0;

			$myynti_yhteensa = 0;
			$pankkikortti = "";
			$luottokortti = "";
			$edkassa = "";
			$edktunnus = "";
			$solu = "";
			$tilinumero = array();
			$kassalippaat = array();
			$kassalipas_tunnus = array();

			if ($tasmays != '') {
				if (mysql_num_rows($result) > 0) {
					mysql_data_seek($result, 0);
				}

				while ($row = mysql_fetch_array($result)) {

					if ($row["tyyppi"] == 'Pankkikortti') {
						$pankkikortti = true;
					}
					if ($row["tyyppi"] == 'Luottokortti') {
						$luottokortti = true;
					}

					if (stristr($row["tyyppi"], 'kateinen')) {

						if ($edkassa != $row["kassa"] or ($kateinen != $row["tilino"] and $kateinen != '')) {

							if (stristr($kateismaksu, 'kateinen')) {

								$kassalippaat[$edkassanimi] = $edkassanimi;
								$kassalipas_tunnus[$edkassanimi] = $edktunnus;

								if ($row["tilino"] != '') {
									$tilinumero["kateinen"] = $row["tilino"];
								}
								elseif ($kateinen != '') {
									$tilinumero["kateinen"] = $kateinen;
								}

								$solu = "kateinen";

								echo "</table><table width='100%'>";
								echo "<tr>";
								echo "<td colspan='";

								if ($tilityskpl > 1) {
									echo $tilityskpl+6;
								}
								else {
									echo "9";
								}

								echo "'";
								echo "' class='tumma'>$kateismaksu ".t("yhteens�").": <a href=\"javascript:toggleGroup('nayta$i')\">".t("N�yt� / Piilota")."</a></td>";
								echo "<input type='hidden' name='maksutapa$i' id='maksutapa$i' value='$solu#$tilinumero[kateinen]#$edkassanimi'>";
								echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu solu$i' name='solu$i' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");'></td>";

								if ($tilityskpl > 1) {
									$y = $i;
									for ($yy = 1; $yy < $tilityskpl; $yy++) {
										$y .= $i;
										echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu solu$y' name='solu$y' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");'></td>";
									}
								}

								echo "<td align='right' class='tumma' style='width:100px'><b><div id='$solu erotus$i'>".str_replace(".",",",sprintf('%.2f',$kateismaksuyhteensa))."</div></b></td>";
								echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu soluerotus$i' size='10' disabled></td></tr>";
								echo "<input type='hidden' id='erotus$i' name='erotus$i' value=''>";
								echo "<input type='hidden' id='soluerotus$i' name='soluerotus$i' value=''>";

								echo "<tr><td class='tumma' colspan='";
									if ($tilityskpl > 1) {
											echo $tilityskpl+6;
										}
										else {
											echo "9";
										}
								echo "'>$edkassanimi ".t("k�teisotto kassasta").":</td><td class='tumma' align='center'>";
								echo "<input type='text' name='kateisotto$i' id='kateisotto$i' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");'></td>";
								if ($tilityskpl > 1) {
									$y = $i;
									for ($yy = 1; $yy < $tilityskpl; $yy++) {
										$y .= $i;
										echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='kateisotto$y' name='kateisotto$y' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");'></td>";
									}
								}
								echo "<td class='tumma' style='width:100px'>&nbsp;</td><td class='tumma' style='width:100px'>&nbsp;</td></tr>";

								echo "<tr><td colspan='";
									if ($tilityskpl > 1) {
										echo $tilityskpl+6;
									}
									else {
										echo "9";
									}
								echo "' align='left' class='tumma'>$edkassanimi ".t("k�teistilitys pankkiin kassasta").":</td>";
								echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='kateistilitys$i' name='kateistilitys$i' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");'></td>";
								if ($tilityskpl > 1) {
									$y = $i;
									for ($yy = 1; $yy < $tilityskpl; $yy++) {
										$y .= $i;
										echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='kateistilitys$y' name='kateistilitys$y' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");'></td>";
									}
								}
								echo "<td class='tumma' style='width:100px'>&nbsp;</td><td class='tumma' style='width:100px'>&nbsp;</td></tr>";

								echo "<tr><td colspan='";
									if ($tilityskpl > 1) {
										echo $tilityskpl+6;
									}
									else {
										echo "9";
									}
								echo "' align='left' class='tumma'>$edkassanimi ".t("loppukassa").":</td>";
								echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='kassalippaan_loppukassa$i' name='kassalippaan_loppukassa$i' size='10' disabled></td>";
								echo "<input type='hidden' name='yht_lopkas$i' id='yht_lopkas$i' value=''>";
								if ($tilityskpl > 1) {
									$y = $i;
									for ($yy = 1; $yy < $tilityskpl; $yy++) {
										$y .= $i;
										echo "<td class='tumma' style='width:100px'>&nbsp;</td>";
									}
								}
								echo "<td class='tumma' style='width:100px'>&nbsp;</td><td class='tumma' style='width:100px'>&nbsp;</td></tr>";

								$i++;
							}
						}

						if ($edkassa != $row["kassa"] and $edkassa != '') {
							echo "<tr><td>&nbsp;</td></tr>";
							echo "<input type='hidden' name='tyyppi_pohjakassa$i' id='tyyppi_pohjakassa$i' value='$row[kassanimi]'>";
							echo "<tr><td colspan='";
								if ($tilityskpl > 1) {
									echo $tilityskpl+6;
								}
								else {
									echo "9";
								}
							echo "' align='left' class='tumma'>$row[kassanimi] ".t("alkukassa").":</td>";
							echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='pohjakassa$i' name='pohjakassa$i' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");'></td>";
							if ($tilityskpl > 1) {
								for ($yy = 1; $yy < $tilityskpl; $yy++) {
									echo "<td class='tumma' style='width:100px'>&nbsp;</td>";
								}
							}
							echo "<td class='tumma' style='width:100px'>&nbsp;</td><td class='tumma' style='width:100px'>&nbsp;</td></tr>";

							echo "</table><table id='nayta$i' style='display:none;' width='100%'>";
							echo "<tr>
									<th>".t("Kassa")."</th>
									<th>".t("Asiakas")."</th>
									<th>".t("Ytunnus")."</th>
									<th>".t("Laskunumero")."</th>
									<th>".t("Pvm")."</th>
									<th>$yhtiorow[valkoodi]</th></tr>";

							$kassayhteensa = 0;
							$kateismaksuyhteensa = 0;
						}

						echo "<tr class='aktiivi'>";
						echo "<td>$row[kassanimi]</td>";
						echo "<td>".substr($row["nimi"],0,23)."</td>";
						echo "<td>$row[ytunnus]</td>";
						echo "<td><a href='".$palvelin2."muutosite.php?tee=E&tunnus=$row[tunnus]'>$row[laskunro]</a></td>";
						echo "<td>".tv1dateconv($row["laskutettu"], "pitka")."</td>";
						echo "<td align='right'>".str_replace(".",",",sprintf('%.2f',$row['tilsumma']))."</td></tr>";

						$kateismaksu = $row['tyyppi'];
						$kateismaksuyhteensa += $row["tilsumma"];
						$yhteensa += $row["tilsumma"];
						$kassayhteensa += $row["tilsumma"];

						$kateinen    = $row["tilino"];
						$edktunnus = $row["ktunnus"];
						$edkassa 	 = $row["kassa"];
						$edkassanimi = $row["kassanimi"];
						$edkateismaksu = $kateismaksu;
					}
				}

				if ($edkassa != '') {

					if (stristr($kateismaksu, 'kateinen')) {

						if ($row["tilino"] != '') {
							$tilinumero["kateinen"] = $row["tilino"];
						}
						elseif ($kateinen != '') {
							$tilinumero["kateinen"] = $kateinen;
						}

						$solu = "kateinen";

						$kassalippaat[$edkassanimi] = $edkassanimi;
						$kassalipas_tunnus[$edkassanimi] = $edktunnus;

						echo "</table><table width='100%'>";
						echo "<tr><td colspan='";
							if ($tilityskpl > 1) {
								echo $tilityskpl+6;
							}
							else {
								echo "9";
							}
						echo "' class='tumma'>$kateismaksu ".t("yhteens�").": <a href=\"javascript:toggleGroup('nayta$i')\">".t("N�yt� / Piilota")."</a></td>";
						echo "<input type='hidden' name='maksutapa$i' value='$solu#$tilinumero[kateinen]#$edkassanimi'>";
						echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu solu$i' name='solu$i' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");'></td>";
						if ($tilityskpl > 1) {
							$y = $i;
							for ($yy = 1; $yy < $tilityskpl; $yy++) {
								$y .= $i;
								echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu solu$y' name='solu$y' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");'></td>";
							}
						}
						echo "<td align='right' class='tumma' style='width:100px'><b><div id='$solu erotus$i'>".str_replace(".",",",sprintf('%.2f',$kateismaksuyhteensa))."</div></b></td>";
						echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu soluerotus$i' name='soluerotus$i' size='10' disabled></td>";
						echo "</tr>";

						echo "<tr><td class='tumma' colspan='";
							if ($tilityskpl > 1) {
									echo $tilityskpl+6;
								}
								else {
									echo "9";
								}
						echo "'>$edkassanimi ".t("k�teisotto kassasta").":</td><td class='tumma' align='center'>";
						echo "<input type='text' name='kateisotto$i' id='kateisotto$i' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");'></td>";
						if ($tilityskpl > 1) {
							$y = $i;
							for ($yy = 1; $yy < $tilityskpl; $yy++) {
								$y .= $i;
								echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='kateisotto$y' name='kateisotto$y' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");'></td>";
							}
						}
						echo "<td class='tumma' style='width:100px'>&nbsp;</td><td class='tumma' style='width:100px'>&nbsp;</td></tr>";

						echo "<tr><td colspan='";
							if ($tilityskpl > 1) {
								echo $tilityskpl+6;
							}
							else {
								echo "9";
							}
						echo "' align='left' class='tumma'>$edkassanimi ".t("k�teistilitys pankkiin kassasta").":</td>";
						echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='kateistilitys$i' name='kateistilitys$i' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");'></td>";
						if ($tilityskpl > 1) {
							$y = $i;
							for ($yy = 1; $yy < $tilityskpl; $yy++) {
								$y .= $i;
								echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='kateistilitys$y' name='kateistilitys$y' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");'></td>";
							}
						}
						echo "<td class='tumma' style='width:100px'>&nbsp;</td><td class='tumma' style='width:100px'>&nbsp;</td></tr>";

						echo "<tr><td colspan='";
							if ($tilityskpl > 1) {
								echo $tilityskpl+6;
							}
							else {
								echo "9";
							}
						echo "' align='left' class='tumma'>$edkassanimi ".t("loppukassa").":</td>";
						echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='kassalippaan_loppukassa$i' name='kassalippaan_loppukassa$i' size='10' disabled></td>";
						echo "<input type='hidden' name='yht_lopkas$i' id='yht_lopkas$i' value=''>";
						if ($tilityskpl > 1) {
							$y = $i;
							for ($yy = 1; $yy < $tilityskpl; $yy++) {
								$y .= $i;
								echo "<td class='tumma' style='width:100px'>&nbsp;</td>";
							}
						}
						echo "<td class='tumma' style='width:100px'>&nbsp;</td><td class='tumma' style='width:100px'>&nbsp;</td></tr>";

						echo "<input type='hidden' id='erotus$i' name='erotus$i' value=''>";
						echo "<input type='hidden' id='soluerotus$i' name='soluerotus$i' value=''>";
						$i++;
					}
				}

				if (count($kassakone) > 1) {
					echo "<tr><td>&nbsp;</td></tr>";
				}

				if ($pankkikortti) {
					mysql_data_seek($result,0);
					$kateismaksuyhteensa = 0;
					$i++;

					echo "</table><table id='nayta$i' style='display:none' width='100%'>";
					echo "<tr>
							<th>".t("Kassa")."</th>
							<th>".t("Asiakas")."</th>
							<th>".t("Ytunnus")."</th>
							<th>".t("Laskunumero")."</th>
							<th>".t("Pvm")."</th>
							<th>$yhtiorow[valkoodi]</th></tr>";

					while ($row = mysql_fetch_array($result)) {

						if ($row["tyyppi"] == 'Pankkikortti') {

							if ($row["tilino"] != '') {
								$tilinumero["pankkikortti"] = $row["tilino"];
							}
							elseif ($kateinen != '') {
								$tilinumero["pankkikortti"] = $kateinen;
							}

							$solu = "pankkikortti";

							echo "<tr class='aktiivi'>";
							echo "<td>$row[kassanimi]</td>";
							echo "<td>".substr($row["nimi"],0,23)."</td>";
							echo "<td>$row[ytunnus]</td>";
							echo "<td><a href='".$palvelin2."muutosite.php?tee=E&tunnus=$row[tunnus]'>$row[laskunro]</a></td>";
							echo "<td>".tv1dateconv($row["laskutettu"], "pitka")."</td>";
							echo "<td align='right'>".str_replace(".",",",sprintf('%.2f',$row['tilsumma']))."</td></tr>";

							$kateinen    = $row["tilino"];
							$edkassa 	 = $row["kassa"];
							$edkassanimi = $row["kassanimi"];
							$edkateismaksu = $kateismaksu;

							$kateismaksu = $row['tyyppi'];
							$edktunnus = $row["ktunnus"];
							$kateismaksuyhteensa += $row["tilsumma"];
							$yhteensa += $row["tilsumma"];
							$kassayhteensa += $row["tilsumma"];

							$kateismaksu = "";
						}
					}

					$kassalippaat[$edkassanimi] = $edkassanimi;
					$kassalipas_tunnus[$edkassanimi] = $edktunnus;

					echo "</table><table width='100%'>";
					echo "<tr><input type='hidden' name='maksutapa$i' value='$solu#$tilinumero[pankkikortti]#";
						if (count($kassakone) > 1) {
							foreach ($kassalippaat as $key => $lipas) {
								if (reset($kassalippaat) == $lipas) {
									echo "$lipas";
								}
								else {
									echo " / $lipas";
								}
							}
						}
						else {
							echo "$edkassanimi";
						}
					echo "'>";
					echo "<td colspan='6' class='tumma'>".t("Pankkikortti yhteens�").": <a href=\"javascript:toggleGroup('nayta$i')\">".t("N�yt� / Piilota")."</a></td>";
					echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu solu$i' name='solu$i' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");'></td>";
					if ($tilityskpl > 1) {
						$y = $i;
						for ($yy = 1; $yy < $tilityskpl; $yy++) {
							$y .= $i;
							echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu solu$y' name='solu$y' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");'></td>";
						}
					}
					echo "<td align='right' class='tumma' style='width:100px'><b><div id='$solu erotus$i'>".str_replace(".",",",sprintf('%.2f',$kateismaksuyhteensa))."</div></b></td>";
					echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu soluerotus$i' name='soluerotus$i' size='10' disabled></td>";
					echo "<input type='hidden' id='erotus$i' name='erotus$i' value=''>";
					echo "<input type='hidden' id='soluerotus$i' name='soluerotus$i' value=''>";
					echo "</tr>";
				}

				if ($luottokortti) {
					mysql_data_seek($result,0);
					$kateismaksuyhteensa = 0;
					$i++;

					echo "</table><table id='nayta$i' style='display:none' width='100%'>";
					echo "<tr>
							<th>".t("Kassa")."</th>
							<th>".t("Asiakas")."</th>
							<th>".t("Ytunnus")."</th>
							<th>".t("Laskunumero")."</th>
							<th>".t("Pvm")."</th>
							<th>$yhtiorow[valkoodi]</th></tr>";

					while ($row = mysql_fetch_array($result)) {

						if ($row["tyyppi"] == 'Luottokortti') {

							if ($row["tilino"] != '') {
								$tilinumero["luottokortti"] = $row["tilino"];
							}
							elseif ($kateinen != '') {
								$tilinumero["luottokortti"] = $kateinen;
							}

							$solu = "luottokortti";

							echo "<tr class='aktiivi'>";
							echo "<td>$row[kassanimi]</td>";
							echo "<td>".substr($row["nimi"],0,23)."</td>";
							echo "<td>$row[ytunnus]</td>";
							echo "<td><a href='".$palvelin2."muutosite.php?tee=E&tunnus=$row[tunnus]'>$row[laskunro]</a></td>";
							echo "<td>".tv1dateconv($row["laskutettu"], "pitka")."</td>";
							echo "<td align='right'>".str_replace(".",",",sprintf('%.2f',$row['tilsumma']))."</td></tr>";

							$kateinen    = $row["tilino"];
							$edkassa 	 = $row["kassa"];
							$edkassanimi = $row["kassanimi"];
							$edkateismaksu = $kateismaksu;

							$kateismaksu = $row['tyyppi'];
							$edktunnus = $row["ktunnus"];
							$kateismaksuyhteensa += $row["tilsumma"];
							$yhteensa += $row["tilsumma"];
							$kassayhteensa += $row["tilsumma"];

							$kateismaksu = "";
						}
					}

					$kassalippaat[$edkassanimi] = $edkassanimi;
					$kassalipas_tunnus[$edkassanimi] = $edktunnus;

					echo "</table><table width='100%'>";
					echo "<tr>";
					echo "<input type='hidden' name='maksutapa$i' value='$solu#$tilinumero[luottokortti]#";
						if (count($kassakone) > 1) {
							foreach ($kassalippaat as $key => $lipas) {
								if (reset($kassalippaat) == $lipas) {
									echo "$lipas";
								}
								else {
									echo " / $lipas";
								}
							}
						}
						else {
							echo "$edkassanimi";
						}
					echo "'>";
					echo "<td colspan='6' class='tumma'>".t("Luottokortti yhteens�").": <a href=\"javascript:toggleGroup('nayta$i')\">".t("N�yt� / Piilota")."</a></td>";
					echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu solu$i' name='solu$i' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");'></td>";
					if ($tilityskpl > 1) {
						$y = $i;
						for ($yy = 1; $yy < $tilityskpl; $yy++) {
							$y .= $i;
							echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu solu$y' name='solu$y' size='10' autocomplete='off' onkeyup='update_summa(\"tasmaytysform\");'></td>";
						}
					}
					echo "<td align='right' class='tumma' style='width:100px'><b><div id='$solu erotus$i'>".str_replace(".",",",sprintf('%.2f',$kateismaksuyhteensa))."</div></b></td>";
					echo "<td class='tumma' align='center' style='width:100px'><input type='text' id='$solu soluerotus$i' name='soluerotus$i' size='10' disabled></td>";
					echo "<input type='hidden' id='erotus$i' name='erotus$i' value=''>";
					echo "<input type='hidden' id='soluerotus$i' name='soluerotus$i' value=''>";
					echo "</tr>";
				}
			}
			else {
				while ($row = mysql_fetch_array($result)) {

					if ((($edkassa != $row["kassa"] and $edkassa != '') or ($kateinen != $row["tilino"] and $kateinen != ''))) {
						echo "</table><table width='100%'>";
						echo "<tr><td colspan='7' class='tumma'>$edtyyppi ".t("yhteens�").": <a href=\"javascript:toggleGroup('nayta$i')\">".t("N�yt� / Piilota")."</a></td>";
						echo "<td align='right' class='tumma' style='width:100px'><b><div id='erotus$i'>".str_replace(".",",",sprintf('%.2f',$kateismaksuyhteensa))."</div></b></td></tr>";
						$i++;

						if ($edkassa == $row["kassa"]) {
							echo "</table><table id='nayta$i' style='display:none;' width='100%'>";
							echo "<tr>
									<th nowrap>".t("Kassa")."</th>
									<th nowrap>".t("Asiakas")."</th>
									<th nowrap>".t("Ytunnus")."</th>
									<th nowrap>".t("Laskunumero")."</th>
									<th nowrap>".t("Pvm")."</th>
									<th nowrap>$yhtiorow[valkoodi]</th></tr>";
						}

						if ($vaiht == 1) {
							$prn  = sprintf ('%-35.35s', 	$kateismaksu." ".t("yhteens�").":");
							$prn .= "...................................................";
							$prn .= str_replace(".",",",sprintf ('%-13.13s', sprintf('%.2f',$kateismaksuyhteensa)));
							$prn .= "\n";

							fwrite($fh, $prn);
							$rivit++;
						}
						$kateismaksuyhteensa = 0;
					}

					if ($edkassa != $row["kassa"] and $edkassa != '') {

						echo "<tr><th colspan='7'>$edkassanimi yhteens�:</th>";
						echo "<td align='right' class='tumma'><b>".str_replace(".",",",sprintf('%.2f',$kassayhteensa))."</b></td></tr>";
						echo "<tr><td>&nbsp;</td></tr>";
						echo "</table><table id='nayta$i' style='display:none;' width='100%'>";
						echo "<tr>
								<th>".t("Kassa")."</th>
								<th>".t("Asiakas")."</th>
								<th>".t("Ytunnus")."</th>
								<th>".t("Laskunumero")."</th>
								<th>".t("Pvm")."</th>
								<th>$yhtiorow[valkoodi]</th></tr>";

						if ($vaiht == 1) {
							$prn  = sprintf ('%-35.35s', 	$edkassanimi." ".t("yhteens�").":");
							$prn .= "............................................";
							$prn .= str_replace(".",",",sprintf ('%-13.13s', sprintf('%.2f',$kassayhteensa)));
							$prn .= "\n\n";

							fwrite($fh, $prn);
							$rivit++;
						}

						$kassayhteensa = 0;
						$kateismaksuyhteensa = 0;
					}

					echo "<tr class='aktiivi'>";
					echo "<td>$row[kassanimi]</td>";
					echo "<td>".substr($row["nimi"],0,23)."</td>";
					echo "<td>$row[ytunnus]</td>";
					echo "<td><a href='".$palvelin2."muutosite.php?tee=E&tunnus=$row[tunnus]'>$row[laskunro]</a></td>";
					echo "<td>".tv1dateconv($row["laskutettu"], "pitka")."</td>";
					echo "<td align='right'>".str_replace(".",",",sprintf('%.2f',$row['tilsumma']))."</td></tr>";

					$kateinen    = $row["tilino"];
					$edkassa 	 = $row["kassa"];
					$edkassanimi = $row["kassanimi"];
					$edkateismaksu = $kateismaksu;
					$edtyyppi = $row["tyyppi"];

					$kateismaksu = $row['tyyppi'];

					if ($vaiht == 1) {
						if ($rivit >= 60) {
							fwrite($fh, $ots);
							$rivit = 1;
						}
						$prn  = sprintf ('%-20.20s', 	$row["kassanimi"]);
						$prn .= sprintf ('%-25.25s', 	substr($row["nimi"],0,23));
						$prn .= sprintf ('%-10.10s', 	$row["ytunnus"]);
						$prn .= sprintf ('%-12.12s', 	$row["laskunro"]);
						$prn .= sprintf ('%-19.19s', 	tv1dateconv($row["laskutettu"], "pitka"));
						$prn .= str_replace(".",",",sprintf ('%-13.13s', 	$row["summa"]));
						$prn .= "\n";

						fwrite($fh, $prn);
						$rivit++;
					}

					$kateismaksuyhteensa += $row["tilsumma"];
					$yhteensa += $row["tilsumma"];
					$kassayhteensa += $row["tilsumma"];
				}

				if ($edkassa != '') {
					echo "</table><table width='100%'>";
					echo "<tr><td colspan='6' class='tumma'>$edtyyppi ".t("yhteens�").": <a href=\"javascript:toggleGroup('nayta$i')\">".t("N�yt� / Piilota")."</a></th>";
					echo "<td align='right' class='tumma' style='width:100px'><b><div id='erotus$i'>".str_replace(".",",",sprintf('%.2f',$kateismaksuyhteensa))."</div></b></td></tr>";

					echo "<tr><th colspan='6'>$edkassanimi yhteens�:</th>";
					echo "<td align='right' class='tumma'><b>".str_replace(".",",",sprintf('%.2f',$kassayhteensa))."</b></td></tr>";

					if ($vaiht == 1) {
						$prn  = sprintf ('%-35.35s', 	$kateismaksu." ".t("yhteens�").":");
						$prn .= "...................................................";
						$prn .= str_replace(".",",",sprintf ('%-13.13s', sprintf('%.2f',$kateismaksuyhteensa)));
						$prn .= "\n";

						fwrite($fh, $prn);
						$rivit++;

						$prn  = sprintf ('%-35.35s', 	$edkassanimi." ".t("yhteens�").":");
						$prn .= "...................................................";
						$prn .= str_replace(".",",",sprintf ('%-13.13s', sprintf('%.2f',$kassayhteensa)));
						$prn .= "\n\n";
						fwrite($fh, $prn);
					}

					$kassayhteensa = 0;
				}
			}
			if ($katsuori != '') {
				//Haetaan kassatilille laitetut suoritukset
				$query = "	SELECT suoritus.nimi_maksaja nimi, tiliointi.summa, lasku.tapvm
							FROM lasku use index (yhtio_tila_tapvm)
							JOIN tiliointi use index (tositerivit_index) ON (tiliointi.yhtio=lasku.yhtio and tiliointi.ltunnus=lasku.tunnus and tiliointi.tilino='$yhtiorow[kassa]')
							JOIN suoritus use index (tositerivit_index) ON (suoritus.yhtio=tiliointi.yhtio and suoritus.ltunnus=tiliointi.aputunnus)
							LEFT JOIN kuka ON (lasku.laatija=kuka.kuka and lasku.yhtio=kuka.yhtio)
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							and lasku.tila	= 'X'
							and lasku.tapvm >= '$vva-$kka-$ppa'
							and lasku.tapvm <= '$vvl-$kkl-$ppl'
							ORDER BY lasku.laskunro";
				$result = mysql_query($query) or pupe_error($query);

				$kassayhteensa = 0;

				if (mysql_num_rows($result) > 0) {
					echo "<br><table id='nayta$i' style='display:none'>";
					echo "<tr>
							<th nowrap>".t("Kassa")."</th>
							<th nowrap>".t("Asiakas")."</th>
							<th nowrap>".t("Ytunnus")."</th>
							<th nowrap>".t("Laskunumero")."</th>
							<th nowrap>".t("Pvm")."</th>
							<th nowrap>$yhtiorow[valkoodi]</th></tr>";

					while ($row = mysql_fetch_array($result)) {

						echo "<tr>";
						echo "<td>".t("K�teissuoritus")."</td>";
						echo "<td>".substr($row["nimi"],0,23)."</td>";
						echo "<td>$row[ytunnus]</td>";
						echo "<td><a href='".$palvelin2."muutosite.php?tee=E&tunnus=$row[tunnus]'>$row[laskunro]</a></td>";
						echo "<td>".tv1dateconv($row["laskutettu"], "pitka")."</td>";
						echo "<td align='right'>".str_replace(".",",",$row['summa'])."</td></tr>";

						if ($vaiht == 1) {
							if ($rivit >= 60) {
								fwrite($fh, $ots);
								$rivit = 1;
							}
							$prn  = sprintf ('%-20.20s', 	t("K�teissuoritus"));
							$prn .= sprintf ('%-25.25s', 	substr($row["nimi"],0,23));
							$prn .= sprintf ('%-10.10s', 	$row["ytunnus"]);
							$prn .= sprintf ('%-12.12s', 	$row["laskunro"]);
							$prn .= sprintf ('%-19.19s', 	tv1dateconv($row["laskutettu"], "pitka"));
							$prn .= str_replace(".",",",sprintf ('%-13.13s', 	$row["summa"]));
							$prn .= "\n";

							fwrite($fh, $prn);
							$rivit++;
						}
						$yhteensa += $row["summa"];
						$kassayhteensa += $row["summa"];
					}

					if ($kassayhteensa != 0) {
					}
				}
			}

			if ($tasmays != '') {
				echo "<tr><td colspan='8'>&nbsp;</td></tr>";
			}

			echo "</table>";
			echo "<table width='100%'>";
			echo "<input type='hidden' id='myynti_yhteensa_hidden' name='myynti_yhteensa' value='$yhteensa'>";
			echo "<tr><td align='left' colspan='3'><font class='head'>";

			if ($tasmays != '') {
				echo t("Myynti yhteens�");
			}
			else {
				echo t("Kaikki kassat yhteens�");
			}

			echo ":</font></td><td align='right'><input type='text' size='10'";

			echo "id='myynti_yhteensa' value='".sprintf('%.2f',$yhteensa);

			echo "' disabled></td></tr>";

			if ($tasmays != '') {
				echo "<tr><td align='left' colspan='3'><font class='head'>".t("Kassalippaassa k�teist�").":</td><td align='right'>";
				echo "<input type='text' id='kaikkiyhteensa' size='10' value='' disabled></td></tr>";
				echo "<tr><td align='left' colspan='3'><font class='head'>".t("Loppukassa yhteens�").":</td><td align='right'>";
				echo "<input type='text' name='loppukassa' id='loppukassa' size='10' disabled></td></tr>";
				echo "<tr><td>&nbsp;</td></tr>";
				echo "<tr><td align='left' colspan='3'><font class='head'>".t("Yhteenveto").":</td></tr>";
				echo "<tr><th colspan='3'>".t("Alkukassa").":</th><td class='tumma' align='right'>";
				echo "<input type='text' name='yht_alkukassa' id='yht_alkukassa' size='10' disabled></td></tr>";
				echo "<tr><th colspan='3'>".t("K�teinen").":</th><td class='tumma' align='right'>";
				echo "<input type='text' name='yht_kateinen' id='yht_kateinen' size='10' disabled></td></tr>";
				echo "<tr><th colspan='3'>".t("K�teisotto").":</th><td class='tumma' align='right'>";
				echo "<input type='text' name='yht_kateisotto' id='yht_kateisotto' size='10' disabled></td></tr>";
				echo "<tr><th colspan='3'>".t("K�teistilitys").":</th><td class='tumma' align='right'>";
				echo "<input type='text' name='yht_kateistilitys' id='yht_kateistilitys' size='10' disabled></td></tr>";
				echo "<tr><th colspan='3'>".t("Loppukassa").":</th><td class='tumma' align='right'>";
				echo "<input type='text' name='yht_loppukassa' id='yht_loppukassa' size='10' disabled></td></tr>";

				echo "<tr><td align='right' colspan='4'><input type='submit' value='".t("Hyv�ksy")."'></td></tr>";

				echo "<input type='hidden' name='loppukassa2' id='loppukassa2' value=''>";
				echo "<input type='hidden' name='yht_alkukas' id='yht_alkukas' value=''>";
				echo "<input type='hidden' name='yht_kat' id='yht_kat' value=''>";
				echo "<input type='hidden' name='yht_katot' id='yht_katot' value=''>";
				echo "<input type='hidden' name='yht_kattil' id='yht_kattil' value=''>";
				echo "<input type='hidden' name='kassalipas_tunnus' value='".urlencode(serialize($kassalipas_tunnus))."'>";
				echo "<input type='hidden' name='kassakone' value='".urlencode(serialize($kassakone))."'>";
				echo "<input type='hidden' name='pp' id='pp' value='$pp'>";
				echo "<input type='hidden' name='kk' id='kk' value='$kk'>";
				echo "<input type='hidden' name='vv' id='vv' value='$vv'>";
				echo "<input type='hidden' name='printteri' id='printteri' value='$printteri'>";
				echo "<input type='hidden' name='tilityskpl' id='tilityskpl' value='$tilityskpl'>";
				echo "</form>";
			}
			echo "</table>";
		}

		if ($tasmays == '' and $vaiht == 1) {
			$prn  = sprintf ('%-13.13s', 	t("Yhteens�").":");
			$prn .= ".........................................................................";
			$prn .= str_replace(".",",",sprintf ('%-15.15s', sprintf('%.2f',$yhteensa)));
			$prn .= "\n";
			fwrite($fh, $prn);

			echo "<pre>",file_get_contents($filenimi),"</pre>";
			fclose($fh);

			//haetaan tilausken tulostuskomento
			$query   = "SELECT * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$printteri'";
			$kirres  = mysql_query($query) or pupe_error($query);
			$kirrow  = mysql_fetch_array($kirres);
			$komento = $kirrow['komento'];

			$line = exec("a2ps -o $filenimi.ps -R --medium=A4 --chars-per-line=94 --no-header --columns=1 --margin=0 --borders=0 $filenimi");

			// itse print komento...
			$line = exec("$komento $filenimi.ps");

			//poistetaan tmp file samantien kuleksimasta...
			system("rm -f $filenimi");
			system("rm -f $filenimi.ps");
		}

		echo "</table>";
	}

	echo "	<script type='text/javascript' language='JavaScript'>
			<!--
				function update_summa(ID) {
					obj = document.getElementById(ID);
					var summa = 0;
					var temp = 0;
					var solusumma = 0;
					var solut = 0;
					var erotus = 0;
					var pointer = 1;
					var pointer2 = 1;
					var kala = '';
					var kassa = 0;
					var loppukas = 0;
					var yht_alku = 0;
					var yht_kat = 0;
					var yht_katot = 0;
					var yht_kattil = 0;
					var yht_loppu = 0;

			 		for (i=0; i<obj.length; i++) {
						if (obj.elements[i].value != '' && obj.elements[i].value != null) {
							//kala = kala+'\\n '+i+'. NIMI: '+obj.elements[i].id+' VALUE: '+obj.elements[i].value;

							if (obj.elements[i].id.substring(0,10) == ('pohjakassa') && !isNaN(obj.elements[i].id.substring(10,11))) {
								if (obj.elements[i].value != '' && obj.elements[i].value != null) {
									if (obj.elements[i].id.substring(10,11) != pointer2) {
										loppukas = 0;
									}

									pointer2 = obj.elements[i].id.substring(10,11);
									loppukas += Number(obj.elements[i].value.replace(\",\",\".\"));
									document.getElementById('kassalippaan_loppukassa'+pointer2).value = loppukas.toFixed(2);

									summa += Number(obj.elements[i].value.replace(\",\",\".\"));
									temp += Number(obj.elements[i].value.replace(\",\",\".\"));
									yht_alku += Number(obj.elements[i].value.replace(\",\",\".\"));
								}
							}
							else if (obj.elements[i].id.substring(0,23) == ('kassalippaan_loppukassa') && !isNaN(obj.elements[i].id.substring(23,24))) {
								if (obj.elements[i].value != '' && obj.elements[i].value != null) {
									pointer2 = obj.elements[i].id.substring(23,24);
									document.getElementById('yht_lopkas'+pointer).value = Number(obj.elements[i].value.replace(\",\",\".\"));
									yht_loppu += Number(obj.elements[i].value.replace(\",\",\".\"));
								}
							}
							else if (obj.elements[i].id.substring(0,13) == ('kateistilitys') && !isNaN(obj.elements[i].id.substring(13,14))) {
								if (obj.elements[i].value != '') {
									summa -= Number(obj.elements[i].value.replace(\",\",\".\"));
									yht_kattil += Number(obj.elements[i].value.replace(\",\",\".\"));

									pointer = obj.elements[i].id.substring(13,14);
									loppukas -= Number(obj.elements[i].value.replace(\",\",\".\"));
									document.getElementById('kassalippaan_loppukassa'+pointer).value = loppukas.toFixed(2);
								}
							}
							else if (obj.elements[i].value != '' && obj.elements[i].id == 'kaikkiyhteensa') {
								temp_value = Number(obj.elements[i].value.replace(\",\",\".\"));
								obj.elements[i].value = temp_value.toFixed(2);
							}
							else if (obj.elements[i].value != '' && obj.elements[i].id.substring(0,10) == ('kateisotto')) {
								summa -= Number(obj.elements[i].value.replace(\",\",\".\"));
								yht_katot += Number(obj.elements[i].value.replace(\",\",\".\"));

								pointer = obj.elements[i].id.substring(10,11);
								loppukas -= Number(obj.elements[i].value.replace(\",\",\".\"));
								document.getElementById('kassalippaan_loppukassa'+pointer).value = loppukas.toFixed(2);
							}
							else if (obj.elements[i].id.substring(0,8) == ('kateinen') && !isNaN(obj.elements[i].id.substring(13,14))) {
								if (pointer != obj.elements[i].id.substring(13,14)) {
									solut = 0;
								}

								if (obj.elements[i].value != '') {
									pointer = obj.elements[i].id.substring(13,14);

									if (document.getElementById('kateinen erotus'+pointer).innerHTML !== null && document.getElementById('kateinen erotus'+pointer).innerHTML != '') {
										erotus = Number(document.getElementById('kateinen erotus'+pointer).innerHTML.replace(\",\",\".\"));
										document.getElementById('erotus'+pointer).value = erotus;
									}
									else {
										erotus = 0;
									}

									solut += Number(obj.elements[i].value.replace(\",\",\".\"));
									kassa = Number(obj.elements[i].value.replace(\",\",\".\"));

									solusumma = solut.toFixed(2) - erotus.toFixed(2);

									kassa = Number(kassa.toFixed(2));
									yht_kat += kassa;
									summa += kassa;
									temp += kassa;

									loppukas += Number(obj.elements[i].value.replace(\",\",\".\"));
									document.getElementById('kassalippaan_loppukassa'+pointer).value = loppukas.toFixed(2);

									document.getElementById('kateinen soluerotus'+pointer).value = solusumma.toFixed(2);

									if (solusumma.toFixed(2) == 0.00) {
										document.getElementById('kateinen soluerotus'+pointer).style.color = 'darkgreen';
									}
									else {
										document.getElementById('kateinen soluerotus'+pointer).style.color = '#FF5555';
									}

									document.getElementById('soluerotus'+pointer).value = solusumma.toFixed(2);
								}
							}
							else if (obj.elements[i].id.substring(0,12) == ('pankkikortti') && !isNaN(obj.elements[i].id.substring(17,18))) {
								if (pointer != obj.elements[i].id.substring(17,18)) {
									solut = 0;
								}

								if (obj.elements[i].value != '') {
									pointer = obj.elements[i].id.substring(17,18);

									if (document.getElementById('pankkikortti erotus'+pointer).innerHTML != '') {
										erotus = Number(document.getElementById('pankkikortti erotus'+pointer).innerHTML.replace(\",\",\".\"));
										document.getElementById('erotus'+pointer).value = Number(document.getElementById('pankkikortti erotus'+pointer).innerHTML.replace(\",\",\".\"));
									}
									else {
										erotus = 0;
									}

									solut += Number(obj.elements[i].value.replace(\",\",\".\"));
									solusumma = solut - erotus;
									document.getElementById('pankkikortti soluerotus'+pointer).value = solusumma.toFixed(2);

									if (solusumma.toFixed(2) == 0.00) {
										document.getElementById('pankkikortti soluerotus'+pointer).style.color = 'darkgreen';
									}
									else {
										document.getElementById('pankkikortti soluerotus'+pointer).style.color = '#FF5555';
									}

									document.getElementById('soluerotus'+pointer).value = solusumma.toFixed(2);
								}
							}
							else if (obj.elements[i].id.substring(0,12) == ('luottokortti') && !isNaN(obj.elements[i].id.substring(17,18))) {
								if (pointer != obj.elements[i].id.substring(17,18)) {
									solut = 0;
								}

								if (obj.elements[i].value != '') {
									pointer = obj.elements[i].id.substring(17,18);

									if (document.getElementById('luottokortti erotus'+pointer).innerHTML != '') {
										erotus = Number(document.getElementById('luottokortti erotus'+pointer).innerHTML.replace(\",\",\".\"));
										document.getElementById('erotus'+pointer).value = Number(document.getElementById('luottokortti erotus'+pointer).innerHTML.replace(\",\",\".\"));
									}
									else {
										erotus = 0;
									}

									solut += Number(obj.elements[i].value.replace(\",\",\".\"));
									solusumma = solut - erotus;
									document.getElementById('luottokortti soluerotus'+pointer).value = solusumma.toFixed(2);

									if (solusumma.toFixed(2) == 0.00) {
										document.getElementById('luottokortti soluerotus'+pointer).style.color = 'darkgreen';
									}
									else {
										document.getElementById('luottokortti soluerotus'+pointer).style.color = '#FF5555';
									}

									document.getElementById('soluerotus'+pointer).value = solusumma.toFixed(2);
								}
							}

							summa = Math.round(summa*100)/100;
							temp = Math.round(temp*100)/100;
							document.getElementById('kaikkiyhteensa').value = temp.toFixed(2);
							document.getElementById('yht_alkukassa').value = yht_alku.toFixed(2);
							document.getElementById('yht_kateinen').value = yht_kat.toFixed(2);
							document.getElementById('yht_kateisotto').value = yht_katot.toFixed(2);
							document.getElementById('yht_kateistilitys').value = yht_kattil.toFixed(2);
							document.getElementById('yht_loppukassa').value = yht_loppu.toFixed(2);
							document.getElementById('loppukassa').value = summa.toFixed(2);
							document.getElementById('loppukassa2').value = summa.toFixed(2);

							document.getElementById('yht_alkukas').value = yht_alku.toFixed(2);
							document.getElementById('yht_kat').value = yht_kat.toFixed(2);
							document.getElementById('yht_katot').value = yht_katot.toFixed(2);
							document.getElementById('yht_kattil').value = yht_kattil.toFixed(2);
						}
					}
					//alert(kala);
				}

				function toggleGroup(id) {
					if (document.getElementById(id).style.display != 'none') {
						document.getElementById(id).style.display = 'none';
					}
					else {
						document.getElementById(id).style.display = 'block';
					}
				}

				function verify() {
					
					var error = false;
					
					obj = document.getElementById('tasmaytysform');
					
			 		for (i=0; i < obj.length; i++) {
						if (obj.elements[i].id.substring(0,10) == ('pohjakassa') || obj.elements[i].id.substring(0,13) == ('kateistilitys') || obj.elements[i].id == 'kaikkiyhteensa' || obj.elements[i].id.substring(0,8) == ('kateinen') || obj.elements[i].id.substring(0,12) == ('pankkikortti') || obj.elements[i].id.substring(0,12) == ('luottokortti') || obj.elements[i].id.substring(0,10) == ('kateisotto')) {
							if (obj.elements[i].value != '' && obj.elements[i].value != null && isNaN(obj.elements[i].value.replace(\",\",\".\"))) {
								error = true;
							}
						}
					}
					
					if (error == true) {
						msg = '".t("Tietueiden t�ytyy sis�lt�� vain numeroita").".';
						alert(msg);
						return false;
					}
					else {
						msg = '".t("Oletko varma?")."';
						return confirm(msg);
					}
				}
			-->
			</script>";

	//K�ytt�liittym�
	echo "<br>";
	echo "<table><form method='post' action='$PHP_SELF'>";

	if (!isset($kka))
		$kka = date("m");
	if (!isset($vva))
		$vva = date("Y");
	if (!isset($ppa))
		$ppa = date("d");


	if (!isset($kkl))
		$kkl = date("m");
	if (!isset($vvl))
		$vvl = date("Y");
	if (!isset($ppl))
		$ppl = date("d");

	echo "<tr><th>".t("Sy�t� myyj�numero")."</th><td colspan='3'><input type='text' size='10' name='myyjanro' value='$myyjanro'>";

	$query = "	SELECT tunnus, kuka, nimi, myyja
				FROM kuka
				WHERE yhtio = '$kukarow[yhtio]'
				ORDER BY nimi";
	$yresult = mysql_query($query) or pupe_error($query);

	echo "<tr><th>".t("TAI valitse k�ytt�j�")."</th><td colspan='3'><select name='myyja'>";
	echo "<option value='' >".t("Kaikki")."</option>";

	while($row = mysql_fetch_array($yresult)) {
		$sel = "";

		if ($row['kuka'] == $myyja) {
			$sel = 'selected';
		}

		echo "<option value='$row[kuka]' $sel>($row[kuka]) $row[nimi]</option>";
	}
	echo "</select></td></tr>";

	echo "<tr><td class='back'><br></td></tr>";

	if (!$tasmays) {
		$dis = "disabled";
		$dis2 = "";
	}
	else {
		$dis = "";
		$dis2 = "disabled";
	}

	if ($oikeurow['paivitys'] == 1) {

		if ($tasmays != '') {
			$sel = 'CHECKED';
		}
		if ($tilityskpl == '') {
			$tilityskpl = 3;
		}

		echo "<tr><th>".t("T�sm�� k�teismyynnit")."</th><td colspan='3'><input type='checkbox' id='tasmays' name='tasmays' $sel onClick='disableDates();'><br></td></tr>";
		echo "<tr><th>".t("Tilitett�vien sarakkeiden m��r�")."</th><td colspan='3'><input type='text' id='tilityskpl' name='tilityskpl' size='3' maxlength='1' value='$tilityskpl' autocomplete='off'><br></td></tr>";
		echo "<tr><th>".t("Sy�t� p�iv�m��r� (pp-kk-vvvv)")."</th>
				<td><input type='text' name='pp' id='pp' value='$pp' size='3' $dis autocomplete='off'></td>
				<td><input type='text' name='kk' id='kk' value='$kk' size='3' $dis autocomplete='off'></td>
				<td><input type='text' name='vv' id='vv' value='$vv' size='5' $dis autocomplete='off'></td></tr>";
		echo "<tr><td class='back'><br></td></tr>";
	}

	$query  = "	SELECT *
				FROM kassalipas
				WHERE yhtio='$kukarow[yhtio]'
				order by tunnus";
	$vares = mysql_query($query) or pupe_error($query);

	while ($varow = mysql_fetch_array($vares)) {
		$sel='';

		if ($kassakone[$varow["tunnus"]] != '') $sel = 'CHECKED';
		echo "<tr><th>".t("N�yt�")."</th><td colspan='3'><input type='checkbox' name='kassakone[$varow[tunnus]]' value='$varow[tunnus]' $sel> $varow[nimi]</td></tr>";
	}

	$sel='';
	if ($muutkassat != '') $sel = 'CHECKED';
	echo "<tr><th>".t("N�yt�")."</th><td colspan='3'><input type='checkbox' name='muutkassat' value='MUUT' $sel>".t("Muut kassat")."</td></tr>";

	$sel='';
	if ($katsuori != '') $sel = 'CHECKED';
	echo "<tr><th>".t("N�yt�")."</th><td colspan='3'><input type='checkbox' name='katsuori' value='MUUT' $sel>".t("K�teissuoritukset")."</td></tr>";

	echo "<tr><td class='back'><br></td></tr>";
	echo "<input type='hidden' name='tee' value='kaikki'>";

	echo "<tr><th>".t("Sy�t� alkup�iv�m��r� (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' id='ppa' value='$ppa' size='3' $dis2></td>
			<td><input type='text' name='kka' id='kka' value='$kka' size='3' $dis2></td>
			<td><input type='text' name='vva' id='vva' value='$vva' size='5' $dis2></td></tr>";

	echo "<tr><th>".t("Sy�t� loppup�iv�m��r� (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' id='ppl' value='$ppl' size='3' $dis2></td>
			<td><input type='text' name='kkl' id='kkl' value='$kkl' size='3' $dis2></td>
			<td><input type='text' name='vvl' id='vvl' value='$vvl' size='5' $dis2></td></tr>";

	$chk1 = '';
	$chk2 = '';

	if ($koti == 'KOTI')
		$chk1 = "CHECKED";

	if ($ulko == 'ULKO')
		$chk2 = "CHECKED";

	if ($chk1 == '' and $chk2 == '') {
		$chk1 = 'CHECKED';
	}


	echo "<tr><th>".t("Kotimaan myynti")."</th>
			<td colspan='3'><input type='checkbox' name='koti' value='KOTI' $chk1></td></tr>";

	echo "<tr><th>".t("Vienti")."</th>
			<td colspan='3'><input type='checkbox' name='ulko' value='ULKO' $chk2></td></tr>";

	$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]'";
	$kires = mysql_query($query) or pupe_error($query);

	echo "<tr><th>".t("Valitse tulostuspaikka").":</th>";

	echo "<td colspan='3'><select name='printteri'>";
	echo "<option value=''>".t("Ei kirjoitinta")."</option>";

	while ($kirow=mysql_fetch_array($kires)) {
		$select = '';

		if ($kirow["tunnus"] == $printteri)
			$select = "SELECTED";

		echo "<option value='$kirow[tunnus]' $select>$kirow[kirjoitin]</option>";
	}
	echo "</select>";
	echo "</td>";
	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td></tr></table>";
	echo "</td></table></form>";


	require ("../inc/footer.inc");
?>
