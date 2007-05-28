<?php
	require ("inc/parametrit.inc");
	
	//Verkkolaskuille ei ole vakiopaikkaa! Anna t�ss� polut miss� haluat laskuja s�ilytt��
	$poistetut='/home/jarmo/einv/hylatyt';
	$vaarat="/home/jarmo/einv/error";
	$oikeat="/home/jarmo/einv";
	
	if (isset($tunnus)) {
		$toim='toimi';
		$lopetus='verkkolaskuvirheet.php';
		require ("yllapito.php");
	}
	
	if (isset($tiedosto)) {
		if ($tapa=='') {
			$xmlstr=file_get_contents($vaarat."/".$tiedosto);
			$xml = simplexml_load_string($xmlstr);
			$result=$xml->xpath('Group2/NAD[@e3035="II"]');
			$result2=$xml->xpath('Group2/Group3/RFF[@eC506.1153="VA"]');
			$result3=$xml->xpath('Group1/RFF[@eC506.1153="ZEB"]');
			$result4=$xml->xpath('Group2/FII[@e3035="BF"]');

			$query = "	SELECT * FROM toimi LIMIT 1";
			$resultx = mysql_query($query) or pupe_error($query);
			$trow = mysql_fetch_array($resultx);

			for ($i=0; $i < mysql_num_fields($resultx) - 1; $i++) {

				if (mysql_field_name($resultx, $i) == "ovttunnus") 
					$t[$i]=$result[0]['eC082.3039'];
				if (mysql_field_name($resultx, $i) == "ytunnus") {
					if (strlen($result2[0]['eC506.1154']) > 8) $t[$i]=substr($result2[0]['eC506.1154'],2);
					else $t[$i]=$result[0]['eC506.1154'];
					if ($t[$i] == '') $t[$i] = substr($result[0]['eC082.3039'],4,8);
					$t[$i] = (int) $t[$i];
				}
				if (mysql_field_name($resultx, $i) == "nimi")
					$t[$i]=utf8_decode($result[0]['eC080.3036.1']);
				if (mysql_field_name($resultx, $i) == "osoite")
					$t[$i]=utf8_decode($result[0]['eC059.3042.1']);
				if (mysql_field_name($resultx, $i) == "postino")
					$t[$i]=$result[0]['e3251'];
				if (mysql_field_name($resultx, $i) == "postitp")
					$t[$i]=utf8_decode($result[0]['e3164']);
				if (mysql_field_name($resultx, $i) == "tilinumero") {
					$t[$i]=$result4[0]['eC078.3194'];
					if ((int) substr($t[$i],0,2) == 0) {  //Tuolla oli maa --> T�m� on iban
						$t[$i] = substr($t[$i],4); // J�tet��n 4 ekaa pois.
					}
				}
				if (mysql_field_name($resultx, $i) == "maa")
					$t[$i]='FI';
			}
			$toim='toimi';
			$uusi='1';
			$lopetus='verkkolaskuvirheet.php';
			
			echo "<form action = 'yllapito.php' method = 'post'>";
			echo "<input type = 'hidden' name = 'toim' value = '$toim'>";
			echo "<input type = 'hidden' name = 'tunnus' value = '$tunnus'>";
			echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";
			echo "<input type = 'hidden' name = 'upd' value ='1'>";
			// Kokeillaan geneerist�
			$query = "	SELECT *
						FROM $toim
						WHERE tunnus = '$tunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$trow = mysql_fetch_array($result);

			echo "<table>";

			for ($i=0; $i < mysql_num_fields($result) - 1; $i++) {
				$nimi = "t[$i]";
				$trow[$i] = $t[$i];					
		
				require "inc/$toim"."rivi.inc";

				// N�it� kentti� ei ikin� saa p�ivitt�� k�ytt�liittym�st�
				if (mysql_field_name($result, $i) == "laatija" or
				mysql_field_name($result, $i) == "muutospvm" or
				mysql_field_name($result, $i) == "muuttaja" or
				mysql_field_name($result, $i) == "luontiaika") {
					$tyyppi = 2;
				}
				
				if 	(mysql_field_len($result,$i)>10) $size='35';
				elseif	(mysql_field_len($result,$i)<5)  $size='5';
				else	$size='10';
			 	echo "<tr>";

				if ($tyyppi > 0) {
			 		echo "<th align='left'>".t(mysql_field_name($result, $i))."</th>";
				}
				else {
					echo "<td class='back'></td>";
				}

				if ($jatko == 0) {
					echo $ulos;
				}
				else {
					$mita = 'text';
					
					if ($tyyppi == "2") {
						$mita='hidden';
						echo "<td>";
					}
					elseif ($tyyppi != 1) {
						$mita='hidden';
						echo "<td class = 'back'>";
					}
					else {
						echo "<td>";
					}

					echo "<input type = '$mita' name = '$nimi'
						value = '$trow[$i]' size='$size' maxlength='" . mysql_field_len($result,$i) ."'>";

					if($tyyppi == 2) {
						echo "$trow[$i]";
					}

					echo "</td>";
				}

				echo "<td class='back'><font class='error'>$virhe[$i]</font></td></tr>\n";
			}
			echo "</table>";
			echo "<br><input type = 'submit' value = '".t("Perusta $toim")."'>";
			echo "</form>";
			require "inc/footer.inc";
			exit;
		}
		if ($tapa == 'U') {
			passthru("mv $vaarat/$tiedosto $oikeat/");
			echo "<font class='message'>Tiedosto k�sitell��n uudestaan</font><br>";
		}
		 
		if ($tapa == 'P') {
			passthru("mv $vaarat/$tiedosto $poistetut/");
			echo "<font class='message'>Tiedosto hyl�ttiin</font><br>";
		}
	}
	
	$laskuri = 0;
	$valitutlaskut = 0;
	echo "<font class='head'>".t("Hyl�tyt verkkolaskut")."</font><hr>";
	if ($handle = opendir($vaarat)) {
		echo "<table><tr>";
		echo "<th>Toiminto</th><th>ly & ovt</th><th>Nimi</th><th>Maskutili & summa</th></tr><tr>";
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$tunnistus = '';
				$xmlstr=file_get_contents($vaarat."/".$file);
				$xml = simplexml_load_string($xmlstr);
				
				$ok=0; //Ei t�m�n yrityksen lasku
				$result=$xml->xpath('Group2/NAD[@e3035="IV"]');
				$tunnistus = (string) $result[0]['eC082.3039'];
				if ((string) $result[0]['eC082.3039'] != $yhtiorow['ovttunnus']) {
					$result=$xml->xpath('Group2/NAD[@e3035="MR"]');
					$tunnistus = (string) $result[0]['eC082.3039'];
					if ((string) $result[0]['eC082.3039'] == $yhtiorow['verkkotunnus_vas']) $ok=1;
				}
				else
					$ok=1;
				
				if ($ok==1) {
					$ok=0;
					echo "<tr><td>";
					$xresult=$xml->xpath('Group2/NAD[@e3035="II"]');
					$xresult2=$xml->xpath('Group2/Group3/RFF[@eC506.1153="VA"]');
					$xresult3=$xml->xpath('Group1/RFF[@eC506.1153="ZEB"]');
					$xresult4=$xml->xpath('Group2/FII[@e3035="BF"]');
					
					$xlaskun_summa_eur = $xml->xpath('Group48/MOA[@eC516.5025="9" and @eC516.6345="EUR"]/@eC516.5004');
					if ((float) $xlaskun_summa_eur[0] == 0) {
						$xlaskun_summa_eur = $xml->xpath('Group48/MOA[@eC516.5025="9" and @eC516.6345=""]/@eC516.5004');
					}
					if ((float) $xlaskun_summa_eur[0] == 0) {
						$xlaskun_summa_eur = $xml->xpath('Group48/MOA[@eC516.5025="9"]/@eC516.5004');
					}
					$laskun_summa_eur		= (float) $xlaskun_summa_eur[0];
					
					$query  = "SELECT * FROM toimi WHERE ovttunnus='$xresult[0][eC082.3039]' and yhtio='$yhtiorow[yhtio]'";
					$result = mysql_query($query) or die ("$query<br><br>".mysql_error());
					if (mysql_num_rows($result) == 1) $ok = 1;
						
					if ($ok == 0) {
						// Yritet��n laventaa ytunnuksella
						$ytunnus = (int) substr($xresult[0]['eC082.3039'],4,8);
						$query  = "SELECT * FROM toimi WHERE ytunnus='$ytunnus' and yhtio='$yhtiorow[yhtio]'";
						$result = mysql_query($query) or die ("$query<br><br>".mysql_error());
						if (mysql_num_rows($result) == 1) $ok = 1;
					}
					
					if ($ok == 0) {
						// Yritet��n tarkentaa nimell�
						$query = "SELECT * FROM toimi WHERE ytunnus='$ytunnus' and yhtio='$yhtiorow[yhtio]' and nimi='".utf8_decode($xresult[0]['eC080.3036.1'])."'";
						$result = mysql_query($query) or die ("$query<br><br>".mysql_error());
						if (mysql_num_rows($result) == 1) $ok = 1;
					}

					if ($ok == 0) {
						// kokeillaan pelk�ll� nimell�
						$query = "SELECT * FROM toimi WHERE yhtio='$yhtiorow[yhtio]' and nimi='".utf8_decode($xresult[0]['eC080.3036.1'])."'";
						$result = mysql_query($query) or die ("$query<br><br>".mysql_error());	
						if (mysql_num_rows($result) == 1) $ok = 1;
					}

					//Olisiko toimittaja sittenkin jossain (v��rin perustettu)
					if ($ok == 0) {
						$siivottu = utf8_decode($xresult[0]['eC080.3036.1']);
						$siivottu = preg_replace('/\b(oy|ab|ltd)\b/i', '', strtolower($siivottu));
						$siivottu = preg_replace('/^\s*/', '', $siivottu);
						$siivottu = preg_replace('/\s*$/', '', $siivottu);
						$query = "SELECT tunnus,nimi FROM toimi WHERE yhtio='$yhtiorow[yhtio]' and nimi like '%$siivottu%'";
						$lahellaresult = mysql_query($query) or die ("$query<br><br>".mysql_error());
					}
										
					if ($ok == 0) {
						if (mysql_num_rows($lahellaresult) > 0) {
							echo "<form action='$PHP_SELF' method='post'><select name='tunnus'>";
							while ($lahellarow=mysql_fetch_array($lahellaresult)) {
								echo "<option value='$lahellarow[tunnus]'>$lahellarow[nimi]";
							}
							echo "</select><input type='submit' value ='P�ivit� toimittaja'></form>";
						}
						echo "<form action='$PHP_SELF' method='post'>
						<input type='hidden' name= 'tiedosto' value ='$file'>
						<input type='submit' value ='Perusta toimittaja'></form>";
					}
					else echo "<form action='$PHP_SELF' method='post'>
						<input type='hidden' name= 'tiedosto' value ='$file'>
						<input type='hidden' name= 'tapa' value ='U'>
						<input type='submit' value ='K�sittele uudestaan'></form>";
					echo "<form action='$PHP_SELF' method='post'>
						<input type='hidden' name= 'tiedosto' value ='$file'>
						<input type='hidden' name= 'tapa' value ='P'>
						<input type='submit' value ='Hylk��'></form>";
					echo "</td>";
					//echo "koko " . sizeof($result4)."\n";
					//echo $result4[0]->asXml()."\n";
					echo "<td>".$xresult[0]['eC082.3039']."<br>";
					if (strlen($xresult2[0]['eC506.1154']) > 8) {
						echo "(".substr($xresult2[0]['eC506.1154'],0,2).")";
						echo substr($xresult2[0]['eC506.1154'],2)."<br>";
					}	 
					echo $xresult3[0]['eC506.4000']."</td>";
					echo "<td>".utf8_decode($xresult[0]['eC080.3036.1'])."<br>";
					echo utf8_decode($xresult[0]['eC059.3042.1'])."<br>";
					echo $xresult[0]['e3251']." ".utf8_decode($xresult[0]['e3164'])."</td>";
					echo "<td>".$xresult4[0]['eC078.3194']."<br>";
					echo $laskun_summa_eur. "<br>";
					$ebid = $xresult3[0]['eC506.1154'];
					require "inc/ebid.inc";
					echo "<a href='$url'>".t("N�yt� lasku")."</a></td>";
					$valitutlaskut++;
				}
				//else echo "<td>Yrityksen: ". $tunnistus ." lasku</td>";
				echo "</tr>";
				$laskuri++;
			}
		}
		closedir($handle);
		echo "</table>";
	}
	if ($valitutlaskut == 0) {
		echo "<font class='message'>Ei hyl�ttyj� laskuja</font><br>";
	}
	echo $laskuri;
	require "inc/footer.inc";
?>
