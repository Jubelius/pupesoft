<?php
	
	require ("../inc/parametrit.inc");

	echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
		<!--

		function toggleAll(toggleBox) {

			var currForm = toggleBox.form;
			var isChecked = toggleBox.checked;
			var nimi = toggleBox.name;

			for (var elementIdx=1; elementIdx<currForm.elements.length; elementIdx++) {
				if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,7) == nimi && currForm.elements[elementIdx].value != '".t("Ei valintaa")."') {
					currForm.elements[elementIdx].checked = isChecked;
				}
			}
		}

		//-->
		</script>";
	
	
	if ($toim == "SUPER") {
		echo "<font class='head'>".t("Inventointien korjaus").":</font><hr>";
	}
	else {
		echo "<font class='head'>".t("Inventointipoikkeamat").":</font><hr>";
	}
	
	if ($tee == 'KORJAA') {
		
		$query = "	SELECT lasku.tunnus tosite, t1.tunnus varasto, t1.selite sel1,  t2.tunnus varastonmuutos, t2.selite sel2
					FROM lasku use index (yhtio_tila_tapvm)
					JOIN tiliointi t1 ON lasku.yhtio=t1.yhtio and lasku.tunnus=t1.ltunnus and t1.korjattu='' and t1.tilino='$yhtiorow[varasto]'
					JOIN tiliointi t2 ON lasku.yhtio=t2.yhtio and lasku.tunnus=t2.ltunnus and t2.korjattu='' and t2.tilino='$yhtiorow[varastonmuutos]'
					WHERE lasku.yhtio	= '$kukarow[yhtio]'
					and lasku.tila     	= 'X'
					and lasku.tapvm     = '$tapvm'
					and lasku.tapvm 	>= '$yhtiorow[tilikausi_alku]' 
					and lasku.tapvm 	<= '$yhtiorow[tilikausi_loppu]'
					and lasku.viite    	= '$ttunnus'";
		$kpitores = mysql_query($query) or pupe_error($query);
		$kpitorow = mysql_fetch_array($kpitores);
		
		if ($kpitorow["tosite"] > 0 and $kpitorow["varasto"] > 0 and $kpitorow["varastonmuutos"] > 0 and (float) $arvo != 0 and (float) $arvo != (float) $edarvo) {
			
			$arvo = (float) $arvo;
			
			$query = "	UPDATE tapahtuma 
						SET kplhinta 	= round($arvo/$kpl,2), 
						hinta			= round($arvo/$kpl,2),
						selite			= concat(selite, ' - Inventointia muokattu')
						where yhtio = '$kukarow[yhtio]' 
						and laji 	= 'inventointi' 
						and tunnus 	= '$ttunnus'";
			$upresult = mysql_query($query) or pupe_error($query);
						
			$query = "UPDATE tiliointi SET korjausaika=now(), korjattu='$kukarow[kuka]' WHERE tunnus=$kpitorow[varasto] AND yhtio='$kukarow[yhtio]'";
	        $result = mysql_query($query) or pupe_error($query);

			$query = "UPDATE tiliointi SET korjausaika=now(), korjattu='$kukarow[kuka]' WHERE tunnus=$kpitorow[varastonmuutos] AND yhtio='$kukarow[yhtio]'";
	        $result = mysql_query($query) or pupe_error($query);

			$query = " INSERT into tiliointi set
						yhtio    = '$kukarow[yhtio]',
						ltunnus  = '$kpitorow[tosite]',
						tilino   = '$yhtiorow[varasto]',
						kustp    = '',
						tapvm    = '$tapvm',
						summa    = '$arvo',
						vero     = '0',
						lukko    = '',
						selite   = 'KORJATTU: $kpitorow[sel1]',
						laatija  = '$kukarow[kuka]',
						laadittu = now()";
			$result = mysql_query($query) or pupe_error($query);

			$query = "INSERT into tiliointi set
						yhtio    = '$kukarow[yhtio]',
						ltunnus  = '$kpitorow[tosite]',
						tilino   = '$yhtiorow[varastonmuutos]',
						kustp    = '',
						tapvm    = '$tapvm',
						summa    = $arvo * -1,
						vero     = '0',
						lukko    = '',
						selite   = 'KORJATTU: $kpitorow[sel2]',
						laatija  = '$kukarow[kuka]',
						laadittu = now()";
			$result = mysql_query($query) or pupe_error($query);
			
			echo "<font class='message'>".t("Inventointi korjattu")."!</font><br><br>";
		}
		else {
			echo "<font class='error'>".t("Inventointia ei voitu korjata")."!</font><br><br>";
		}
		$tee = 'Y';
	}
	
	if ($tee == 'PERU') {
		
		$query = "	SELECT lasku.tunnus tosite, t1.tunnus varasto, t1.selite sel1,  t2.tunnus varastonmuutos, t2.selite sel2
					FROM lasku use index (yhtio_tila_tapvm)
					JOIN tiliointi t1 ON lasku.yhtio=t1.yhtio and lasku.tunnus=t1.ltunnus and t1.korjattu='' and t1.tilino='$yhtiorow[varasto]'
					JOIN tiliointi t2 ON lasku.yhtio=t2.yhtio and lasku.tunnus=t2.ltunnus and t2.korjattu='' and t2.tilino='$yhtiorow[varastonmuutos]'
					WHERE lasku.yhtio	= '$kukarow[yhtio]'
					and lasku.tila     	= 'X'
					and lasku.tapvm     = '$tapvm'
					and lasku.tapvm 	>= '$yhtiorow[tilikausi_alku]' 
					and lasku.tapvm 	<= '$yhtiorow[tilikausi_loppu]'
					and lasku.viite    	= '$ttunnus'";
		$kpitores = mysql_query($query) or pupe_error($query);
		$kpitorow = mysql_fetch_array($kpitores);
		
		if ($kpitorow["tosite"] > 0 and $kpitorow["varasto"] > 0 and $kpitorow["varastonmuutos"] > 0) {
						
			$query = "	UPDATE tapahtuma 
						SET kpl		= 0, 						
						kplhinta 	= 0, 
						hinta		= 0,
						selite		= concat(selite, ' - Inventointi peruttu')
						where yhtio = '$kukarow[yhtio]' 
						and laji 	= 'inventointi' 
						and tunnus 	= '$ttunnus'";
			$upresult = mysql_query($query) or pupe_error($query);
						
			$query = "UPDATE tiliointi SET korjausaika=now(), korjattu='$kukarow[kuka]' WHERE tunnus=$kpitorow[varasto] AND yhtio='$kukarow[yhtio]'";
	        $result = mysql_query($query) or pupe_error($query);

			$query = "UPDATE tiliointi SET korjausaika=now(), korjattu='$kukarow[kuka]' WHERE tunnus=$kpitorow[varastonmuutos] AND yhtio='$kukarow[yhtio]'";
	        $result = mysql_query($query) or pupe_error($query);
	
			$query = "	UPDATE sarjanumeroseuranta 
						SET myyntirivitunnus = 0,
						siirtorivitunnus 	 = 0,
						muuttaja			 = '$kukarow[kuka]',
						muutospvm			 = now(),
						inventointitunnus	 = 0 
						WHERE yhtio				= '$kukarow[yhtio]'
						and inventointitunnus	= $ttunnus
						and myyntirivitunnus 	= -1
						and siirtorivitunnus 	= -1";
	        $result = mysql_query($query) or pupe_error($query);
	
			$query = "	UPDATE tuotepaikat
						SET saldo = saldo-$kpl,
						saldoaika = now()
						WHERE yhtio   = '$kukarow[yhtio]' 
						and tuoteno   = '$tuoteno' 
						and hyllyalue = '$hyllyalue' 
						and hyllynro  = '$hyllynro' 
						and hyllytaso = '$hyllytaso' 
						and hyllyvali = '$hyllyvali'
						LIMIT 1";
			$result = mysql_query($query) or pupe_error($query);
			
			echo "$query<br>";
			
			if (mysql_affected_rows() == 0) {
				$query = "	UPDATE tuotepaikat
							SET saldo = saldo-$kpl,
							saldoaika = now()
							WHERE yhtio = '$kukarow[yhtio]' 
							and tuoteno	= '$tuoteno' 
							and oletus != '' 
							LIMIT 1";
				$result = mysql_query($query) or pupe_error($query);
			}
	
			echo "<font class='message'>".t("Inventointi peruttu")."!</font><br><br>";
		}
		else {
			echo "<font class='error'>".t("Inventointia ei voitu perua")."!</font><br><br>";
		}
		
		$tee = 'Y';
	}
	
	
	if ($tee == 'Y') {

		if ($tila == 'tulosta') {
			$tulostimet[0] = "Inventointipoikkeamat";
			if (count($komento) == 0) {
				require("../inc/valitse_tulostin.inc");
			}
		}

		//$prosmuutos   = (int) $prosmuutos;
		$kplmuutos = (int) $kplmuutos;
		if ((int) $prosmuutos == 0 and $kplmuutos == 0) {
			$kplmuutos = 1;
		}

		if ((int) $prosmuutos <> 0 or $kplmuutos <> 0) {

			$lisa = ""; // no hacking

			if ((int) $prosmuutos < 0 and substr($prosmuutos,0,1) == '-') {
				$prosmuutos   = (int) $prosmuutos;
				$lisa .= " and tuotepaikat.inventointipoikkeama <= '$prosmuutos' ";
			}
			elseif ((int) $prosmuutos > 0 and substr($prosmuutos,0,1) == '+') {
				$prosmuutos   = (int) $prosmuutos;
				$lisa .= " and tuotepaikat.inventointipoikkeama >= '$prosmuutos' ";
			}
			elseif ((int) $prosmuutos > 0) {
				$prosmuutos   = (int) $prosmuutos;
				$lisa .= " and (inventointipoikkeama <= '-$prosmuutos' or tuotepaikat.inventointipoikkeama >= '$prosmuutos') ";
			}

			if ($kplmuutos <> 0) {
				$lisa .= " and abs(tapahtuma.kpl) >= abs('$kplmuutos') ";
			}
			
			if ($sarjat != "") {
				$lisa .= " and tuote.sarjanumeroseuranta='S' ";
			}

			if (count($mul_osasto) > 0) {
				$sel_osasto = "('".str_replace(',','\',\'',implode(",", $mul_osasto))."')";
				$lisa .= " and tuote.osasto in $sel_osasto ";
			}
			if (count($mul_try) > 0) {
				$sel_tuoteryhma = "('".str_replace(',','\',\'',implode(",", $mul_try))."')";
				$lisa .= " and tuote.try in $sel_tuoteryhma ";
			}
			if (count($mul_tmr) > 0) {
				$sel_tuotemerkki = "('".str_replace(',','\',\'',implode(",", $mul_tmr))."')";
				$lisa .= " and tuote.tuotemerkki in $sel_tuotemerkki ";
			}

			if (!empty($varastot)) {
				$lisa .= " and varastopaikat.tunnus IN (" . implode(', ', $varastot) . ")";
	        }

			if ($vararvomuu != "") {
				$lisa .= " HAVING arvo != 0 ORDER BY arvo";
			}
			else {
				$lisa2 .= " ORDER BY sorttauskentta";	
			}

			$query = "	SELECT tuote.tuoteno, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso, tuote.nimitys, tuote.yksikko, 
						tuotepaikat.inventointiaika, tuotepaikat.inventointipoikkeama, tapahtuma.selite, tapahtuma.kpl, tapahtuma.tunnus ttunnus, tapahtuma.hinta,
						tuote.sarjanumeroseuranta,
						tapahtuma.laatija,
						tapahtuma.laadittu,
						(tapahtuma.hinta*tapahtuma.kpl) arvo,
						left(tapahtuma.laadittu, 10) tapvm,
						(SELECT group_concat(toim_tuoteno) FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno) as toim_tuoteno,
						concat(lpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta
						FROM tuote
						JOIN tapahtuma ON tapahtuma.yhtio = tuote.yhtio and tapahtuma.laji='Inventointi' and tapahtuma.tuoteno=tuote.tuoteno
						LEFT JOIN tuotepaikat ON tuotepaikat.yhtio=tapahtuma.yhtio and tuotepaikat.tuoteno=tapahtuma.tuoteno and tapahtuma.selite like concat('%',tuotepaikat.hyllyalue,'-',tuotepaikat.hyllynro,'-',tuotepaikat.hyllyvali,'-',tuotepaikat.hyllytaso,'%')						
						LEFT JOIN varastopaikat ON (varastopaikat.yhtio=tuotepaikat.yhtio and
	                	concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) and
	                	concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')))												
						WHERE tuote.yhtio = '$kukarow[yhtio]'
						and tuote.ei_saldoa = ''
						and tapahtuma.laadittu >= '$vva-$kka-$ppa 00:00:00'
						and tapahtuma.laadittu <= '$vvl-$kkl-$ppl 23:59:59'
						$lisa
						$lisa2";
			$saldoresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($saldoresult) == 0) {
				echo "<font class='error'>".t("Yht��n tuotetta ei l�ytynyt")."!</font><br><br>";
				$tee  = '';
				$tila = '';
			}
			elseif ($tila != 'tulosta'){
				echo "<table>";
				
				echo "<tr>";
				echo "<th>".t("Nimitys")."</th><th>".t("Varastopaikka")."</th><th>".t("Inventointiaika")."</th><th>".t("Kpl")."</th><th>".t("Poikkeamaprosentti")." %</th>";
				echo "</tr>";

				while ($tuoterow = mysql_fetch_array($saldoresult)) {
					echo "<tr><th colspan='5'>$tuoterow[tuoteno]</th></tr>";
					
					echo "<td>".asana('nimitys_',$tuoterow['tuoteno'],$tuoterow['nimitys'])."</td><td>$tuoterow[hyllyalue] $tuoterow[hyllynro] $tuoterow[hyllyvali] $tuoterow[hyllytaso]</td><td>".tv1dateconv($tuoterow["laadittu"], "P")."</td><td>$tuoterow[kpl]</td><td>$tuoterow[inventointipoikkeama]</td></tr>";
					
					echo "<tr><td colspan='5'>$tuoterow[selite]</td></tr>";
					
					$query = "	SELECT sum(tiliointi.summa) summa
								FROM lasku use index (yhtio_tila_tapvm)
								JOIN tiliointi ON lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus and tiliointi.korjattu='' and tiliointi.tilino='$yhtiorow[varasto]'
								WHERE lasku.yhtio	= '$kukarow[yhtio]'
								and lasku.tila     	= 'X'
								and lasku.tapvm     = '$tuoterow[tapvm]'
								and lasku.viite    	= '$tuoterow[ttunnus]'";
					$kpitores = mysql_query($query) or pupe_error($query);
					$kpitorow = mysql_fetch_array($kpitores);
					
					echo "<tr><td>".t("Varastonmuutos").": ".sprintf('%.2f', $tuoterow["kpl"]*$tuoterow["hinta"])."</td><td colspan='4'>".t("Kirjanpito").": ".sprintf('%.2f', $kpitorow["summa"])."</td></tr>";
					
					if ($tuoterow["sarjanumeroseuranta"] == "S") {
						$query = "	SELECT * 
									FROM sarjanumeroseuranta
									WHERE yhtio				= '$kukarow[yhtio]'							
									and myyntirivitunnus 	= '-1'
									and siirtorivitunnus	= '-1'
									and inventointitunnus	= '$tuoterow[ttunnus]'";
						$sarjares = mysql_query($query) or pupe_error($query);

						while ($sarjarow = mysql_fetch_array($sarjares)) {
							echo "<tr><td>".t("Snro").": </td><td colspan='4'>$sarjarow[sarjanumero]</td></tr>";
						}
					}
					
					if ($toim == "SUPER") {
						echo "<tr><td>".t("Korjaa inventointi").": </td><td colspan='4'>";
						echo "<form action='$PHP_SELF' method='post' autocomplete='off'>";												
						echo "<input type='hidden' name='tila'			value='$tila'>";
						echo "<input type='hidden' name='toim' 			value='$toim'>";
						echo "<input type='hidden' name='ppa' 			value='$ppa'>";
						echo "<input type='hidden' name='kka' 			value='$kka'>";
						echo "<input type='hidden' name='vva' 			value='$vva'>";
						echo "<input type='hidden' name='ppl' 			value='$ppl'>";
						echo "<input type='hidden' name='kkl' 			value='$kkl'>";
						echo "<input type='hidden' name='vvl' 			value='$vvl'>";
						echo "<input type='hidden' name='prosmuutos' 	value='$prosmuutos'>";
						echo "<input type='hidden' name='kplmuutos' 	value='$kplmuutos'>";
						echo "<input type='hidden' name='sarjat' 		value='$sarjat'>";
						echo "<input type='hidden' name='vararvomuu' 	value='$vararvomuu'>";
						echo "<input type='hidden' name='tee' 			value='KORJAA'>";
						echo "<input type='hidden' name='ttunnus' 		value='$tuoterow[ttunnus]'>";
						echo "<input type='hidden' name='tapvm' 		value='$tuoterow[tapvm]'>";
						echo "<input type='hidden' name='edarvo' 		value='$kpitorow[summa]'>";
						echo "<input type='hidden' name='kpl' 			value='$tuoterow[kpl]'>";
						echo "<input type='text' size='15' name='arvo' value='".sprintf('%.2f', $kpitorow["summa"])."'>";
						echo "<input type='submit' name='valmis' value='".t("Korjaa")."'>";
						echo "</form>";												
						echo "</td></tr>";
					}
					
					if ($toim == "SUPER" and $tuoterow["sarjanumeroseuranta"] == "S" and mysql_num_rows($sarjares) == abs($tuoterow["kpl"])) {
						echo "<tr><td>".t("Peru inventointi").": </td><td colspan='4'>";
						echo "<form action='$PHP_SELF' method='post' autocomplete='off'>";												
						echo "<input type='hidden' name='tila'			value='$tila'>";
						echo "<input type='hidden' name='toim' 			value='$toim'>";
						echo "<input type='hidden' name='ppa' 			value='$ppa'>";
						echo "<input type='hidden' name='kka' 			value='$kka'>";
						echo "<input type='hidden' name='vva' 			value='$vva'>";
						echo "<input type='hidden' name='ppl' 			value='$ppl'>";
						echo "<input type='hidden' name='kkl' 			value='$kkl'>";
						echo "<input type='hidden' name='vvl' 			value='$vvl'>";
						echo "<input type='hidden' name='prosmuutos' 	value='$prosmuutos'>";
						echo "<input type='hidden' name='kplmuutos' 	value='$kplmuutos'>";
						echo "<input type='hidden' name='sarjat' 		value='$sarjat'>";
						echo "<input type='hidden' name='vararvomuu' 	value='$vararvomuu'>";
						echo "<input type='hidden' name='tee' 			value='PERU'>";
						echo "<input type='hidden' name='tuoteno' 		value='$tuoterow[tuoteno]'>";
						echo "<input type='hidden' name='hyllyalue' 	value='$tuoterow[hyllyalue]'>";
						echo "<input type='hidden' name='hyllynro' 		value='$tuoterow[hyllynro]'>";
						echo "<input type='hidden' name='hyllyvali' 	value='$tuoterow[hyllyvali]'>";
						echo "<input type='hidden' name='hyllytaso' 	value='$tuoterow[hyllytaso]'>";
						echo "<input type='hidden' name='ttunnus' 		value='$tuoterow[ttunnus]'>";
						echo "<input type='hidden' name='tapvm' 		value='$tuoterow[tapvm]'>";
						echo "<input type='hidden' name='kpl' 			value='$tuoterow[kpl]'>";
						echo "<input type='submit' name='valmis' value='".t("Peru")."'>";
						echo "</form>";												
						echo "</td></tr>";
					}
					
					echo "<tr style='height: 5px;'></tr>";
				}
				echo "</table>";
			}

		}
		else {
			echo "<font class='error'>".t("Et sy�tt�nyt mit��n j�rkev��! Skarppaas v�h�n")."!</font><br><br>";
			$tee  = '';
			$tila = '';
		}

		if ($tila == 'tulosta') {
			$tee = 'TULOSTA';
		}
	}
	if ($tee == "TULOSTA") {
		if (mysql_num_rows($saldoresult) > 0 ) {
			if ($prosmuutos == 0) {
				$muutos = $kplmuutos;
				$yks = t("yks");
			}
			else {
				$muutos = $prosmuutos;
				$yks = "%";
			}
			//kirjoitetaan  faili levylle..
			//keksit��n uudelle failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$filenimi = "/tmp/Inventointilista-".md5(uniqid(mt_rand(), true)).".txt";
			$fh = fopen($filenimi, "w+");

			$pp = date('d');
			$kk = date('m');
			$vv = date('Y');

			$ots  = "".t("Inventointipoikkeamalista, poikkeama ")." $muutos $yks $pp.$kk.$vv $yhtiorow[nimi]\n\n";
			$ots .= sprintf ('%-14.14s', 	t("Paikka"));
			$ots .= sprintf ('%-21.21s', 	t("Tuoteno"));
			$ots .= sprintf ('%-21.21s', 	t("Toim.Tuoteno"));
			$ots .= sprintf ('%-10.10s',	t("Poikkeama"));
			$ots .= sprintf ('%-9.9s', 		t("Yksikk�"));
			$ots .= sprintf ('%-20.20', 	t("Inv.pvm"));
			$ots .= "\n";
			$ots .= "-------------------------------------------------------------------------------------------------------\n\n";
			fwrite($fh, $ots);
			$ots = chr(12).$ots;

			$rivit = 1;
			while ($row = mysql_fetch_array($saldoresult)) {
				if ($rivit >= 19) {
					fwrite($fh, $ots);
					$rivit = 1;
				}
				if ($yks == '%') {
					$row["yksikko"] = "%";
					$row["kpl"] = $row["inventointipoikkeama"];
				}

				//katsotaan onko tuotetta tilauksessa
				$query = "	SELECT sum(varattu) varattu, min(toimaika) toimaika
							FROM tilausrivi
							WHERE yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]' and varattu>0 and tyyppi='O'";
				$result1 = mysql_query($query) or pupe_error($query);
				$prow    = mysql_fetch_array($result1);

				if ($row["inventointiaika"]=='0000-00-00 00:00:00') {
					$row["inventointiaika"] = t("Ei inventoitu");
				}

				$prn  = sprintf ('%-14.14s', 	$row["hyllyalue"]." ".$row["hyllynro"]." ".$row["hyllyvali"]." ".$row["hyllytaso"]);
				$prn .= sprintf ('%-21.21s', 	$row["tuoteno"]);
				$prn .= sprintf ('%-21.21s', 	$row["toim_tuoteno"]);
				$prn .= sprintf ('%-10.10s',	$row["kpl"]);
				$prn .= sprintf ('%-9.9s', 		$row["yksikko"]);
				$prn .= sprintf ('%-15.15s', 	$row["inventointiaika"]);
				$prn .= "\n\n";
				fwrite($fh, $prn);
				$rivit++;
			}

			fclose($fh);

			//k��nnet��n kaunniksi
			$line = exec("a2ps -o ".$filenimi.".ps -r --medium=A4 --chars-per-line=105 --no-header --columns=1 --margin=0 --borders=0 $filenimi");
			//itse print komento...
			$line2 = exec("$komento[Inventointipoikkeamat] ".$filenimi.".ps");
			
			echo "<br>".t("Inventointipoikkeamalista tulostuu")."!<br><br>";

			$tee = '';

			//poistetaan tmp file samantien kuleksimasta...
			system("rm -f $filenimi");
			system("rm -f ".$filenimi.".ps");
		}
	}

	if ($tee == '') {
		if (!isset($kka))
			$kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		if (!isset($vva))
			$vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		if (!isset($ppa))
			$ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

		if (!isset($kkl))
			$kkl = date("m");
		if (!isset($vvl))
			$vvl = date("Y");
		if (!isset($ppl))
			$ppl = date("d");

		// piirrell��n formi
		echo "<form name='inve' action='$PHP_SELF' method='post' autocomplete='off'>";
		echo "	<input type='hidden' name='tee' value='Y'>
				<input type='hidden' name='toim' value='$toim'>";

		echo "<table>";
		echo "<tr><th colspan='1'>".t("Valitse toiminto")."</th><td colspan='2'>
				<select name='tila'>
				<option value='inventoi'>".t("N�yt� ruudulla")."</option>
				<option value='tulosta'>".t("Tulosta inventointipoikkeamalista")."</option>
				</select></td></tr>";

		echo "<input type='hidden' name='supertee' value='RAPORTOI'>";

		echo "<tr valign='top'><td><table><tr><td class='back'>";

		// n�ytet��n soveltuvat osastot
		$query = "SELECT avainsana.selite, ".avain('select')." FROM avainsana ".avain('join','OSASTO_')." WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='OSASTO' order by avainsana.selite+0";
		$res2  = mysql_query($query) or die($query);

		if (mysql_num_rows($res2) > 11) {
			echo "<div style='height:265;overflow:auto;'>";
		}

		echo "<table>";
		echo "<tr><th colspan='2'>".t("Tuoteosasto").":</th></tr>";
		echo "<tr><td><input type='checkbox' name='mul_osa' onclick='toggleAll(this);'></td><td nowrap>".t("Ruksaa kaikki")."</td></tr>";

		while ($rivi = mysql_fetch_array($res2)) {
			$mul_check = '';
			if ($mul_osasto!="") {
				if (in_array($rivi['selite'],$mul_osasto)) {
					$mul_check = 'CHECKED';
				}
			}

			echo "<tr><td><input type='checkbox' name='mul_osasto[]' value='$rivi[selite]' $mul_check></td><td>$rivi[selite] - $rivi[selitetark]</td></tr>";
		}

		echo "</table>";

		if (mysql_num_rows($res2) > 11) {
			echo "</div>";
		}

		echo "</table>";
		echo "</td>";

		echo "<td><table><tr><td valign='top' class='back'>";

		// n�ytet��n soveltuvat tryt
		$query = "SELECT avainsana.selite, ".avain('select')." FROM avainsana ".avain('join','TRY_')." WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='TRY' order by avainsana.selite+0";
		$res2  = mysql_query($query) or die($query);

		if (mysql_num_rows($res2) > 11) {
			echo "<div style='height:265;overflow:auto;'>";
		}

		echo "<table>";
		echo "<tr><th colspan='2'>".t("Tuoterym�").":</th></tr>";
		echo "<tr><td><input type='checkbox' name='mul_try' onclick='toggleAll(this);'></td><td nowrap>".t("Ruksaa kaikki")."</td></tr>";

		while ($rivi = mysql_fetch_array($res2)) {
			$mul_check = '';
			if ($mul_try!="") {
				if (in_array($rivi['selite'],$mul_try)) {
					$mul_check = 'CHECKED';
				}
			}

			echo "<tr><td><input type='checkbox' name='mul_try[]' value='$rivi[selite]' $mul_check></td><td>$rivi[selite] - $rivi[selitetark]</td></tr>";
		}

		echo "</table>";

		if (mysql_num_rows($res2) > 11) {
			echo "</div>";
		}

		echo "</table>";
		echo "</td>";

		echo "<td><table><tr><td valign='top' class='back'>";

		// n�ytet��n soveltuvat tuotemerkit
		$query = "	SELECT distinct tuotemerkki FROM tuote use index (yhtio_tuotemerkki) WHERE yhtio='$kukarow[yhtio]' and tuotemerkki != '' ORDER BY tuotemerkki";
		$res2  = mysql_query($query) or die($query);

		if (mysql_num_rows($res2) > 11) {
			echo "<div style='height:265;overflow:auto;'>";
		}

		echo "<table>";
		echo "<tr><th colspan='2'>".t("Tuotemerkki").":</th></tr>";
		echo "<tr><td><input type='checkbox' name='mul_tmr' onclick='toggleAll(this);'></td><td nowrap>".t("Ruksaa kaikki")."</td></tr>";

		while ($rivi = mysql_fetch_array($res2)) {
			$mul_check = '';
			if ($mul_tmr!="") {
				if (in_array($rivi['tuotemerkki'], $mul_tmr)) {
					$mul_check = 'CHECKED';
				}
			}

			echo "<tr><td><input type='checkbox' name='mul_tmr[]' value='$rivi[tuotemerkki]' $mul_check></td><td> $rivi[tuotemerkki] </td></tr>";
		}

		echo "</table>";

		if (mysql_num_rows($res2) > 11) {
			echo "</div>";
		}

		echo "</table>";
		echo "</td>";

		echo "</tr>";
		echo "</table>";
		
		echo "<table>";

		$query  = "SELECT tunnus, nimitys FROM varastopaikat WHERE yhtio='$kukarow[yhtio]'";
		$vares = mysql_query($query) or pupe_error($query);

		echo "<tr><th valign=top>" . t('Varastot') . "<br /><br /><span style='font-size: 0.8em;'>"
			. t('Saat kaikki varastot jos et valitse yht��n')
			. "</span></th>
		    <td colspan='3'>";

		$varastot = (isset($_POST['varastot']) && is_array($_POST['varastot'])) ? $_POST['varastot'] : array();

	    while ($varow = mysql_fetch_array($vares)) {
			$sel = '';
			if (in_array($varow['tunnus'], $varastot)) {
				$sel = 'checked';
			}

			echo "<input type='checkbox' name='varastot[]' value='{$varow['tunnus']}' $sel/>{$varow['nimitys']}<br />\n";
		}
		echo "</td></tr>";

		echo "<tr><th>".t("Sy�t� alkup�iv�m��r� (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppa' value='$ppa' size='3'></td>
				<td><input type='text' name='kka' value='$kka' size='3'></td>
				<td><input type='text' name='vva' value='$vva' size='5'></td>
				</tr><tr><th>".t("Sy�t� loppup�iv�m��r� (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppl' value='$ppl' size='3'></td>
				<td><input type='text' name='kkl' value='$kkl' size='3'></td>
				<td><input type='text' name='vvl' value='$vvl' size='5'></td>";


		echo "<tr><th>".t("Listaa tuotteet joilla poikkeamaprosentti on v�hint��n")."</th>
				<td colspan='3'><input type='text' size='15' name='prosmuutos' value='$prosmuutos' size='3'> ".t("prosenttia")."</td><td class='back'>".t("Lis�tyt tuotteet + merkill� ja v�hennetyt tuotteet - merkill�, tai absoluuttinen.")."</td></tr>";

		echo "<tr><th>".t("Listaa tuotteet joiden kappalem��r� on muuttunut v�hint��n")."</th>
				<td colspan='3'><input type='text' size='15' name='kplmuutos' value='$kplmuutos' size='3'> ".t("kappaletta")."</td></tr>";

		echo "<tr><th>".t("Listaa vain sarjanumerolliset tuotteet")."</th>
				<td colspan='3'><input type='checkbox' name='sarjat' $sel></td></tr>";
		
		echo "<tr><th>".t("Listaa vain varastonarvoon vaikuttaneet inventoinnit")."</th>
				<td colspan='3'><input type='checkbox' name='vararvomuu' $sel></td></tr>";

		echo "<tr><td class='back'><br><input type='submit' value='".t("Aja raportti")."'></td></tr></form></table>";
	}

	require ("../inc/footer.inc");
?>