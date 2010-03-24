<?php

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	if (strpos($_SERVER['SCRIPT_NAME'], "muokkaatilaus.php") !== FALSE) {
		require("inc/parametrit.inc");
	}

	if ($tee == 'MITATOI_TARJOUS') {
		unset($tee);
	}

	if (isset($tee)) {
		if ($tee == "lataa_tiedosto") {
			readfile("/tmp/".$tmpfilenimi);
			exit;
		}
	}
	else {

		// scripti balloonien tekemiseen
		js_popup();
		enable_ajax();

		echo "	<script type='text/javascript' language='JavaScript'>
				<!--
					function verify() {
						msg = '".t("Oletko varma?")."';
						return confirm(msg);
					}
				-->
				</script>";

		$toim = strtoupper($toim);

		if ($toim == "" or $toim == "SUPER") {
			$otsikko = t("myyntitilausta");
		}
		elseif ($toim == "ENNAKKO") {
			$otsikko = t("ennakkotilausta");
		}
		elseif ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYSSUPER") {
			$otsikko = t("ty�m��r�yst�");
		}
		elseif ($toim == "REKLAMAATIO") {
			$otsikko = t("reklamaatiota");
		}
		elseif ($toim == "SIIRTOTYOMAARAYS" or $toim == "SIIRTOTYOMAARAYSSUPER") {
			$otsikko = t("sis�ist� ty�m��r�yst�");
		}
		elseif ($toim == "VALMISTUS" or $toim == "VALMISTUSSUPER") {
			$otsikko = t("valmistusta");
		}
		elseif ($toim == "SIIRTOLISTA" or $toim == "SIIRTOLISTASUPER") {
			$otsikko = t("varastosiirtoa");
		}
		elseif ($toim == "MYYNTITILI" or $toim == "MYYNTITILISUPER" or $toim == "MYYNTITILITOIMITA") {
			$otsikko = t("myyntitili�");
		}
		elseif ($toim == "TARJOUS" or $toim == "TARJOUSSUPER") {
			$otsikko = t("tarjousta");
		}
		elseif ($toim == "LASKUTUSKIELTO") {
			$otsikko = t("laskutuskieltoa");
		}
		elseif ($toim == "EXTRANET") {
			$otsikko = t("extranet-tilausta");
		}
		elseif ($toim == "OSTO" or $toim == "OSTOSUPER") {
			$otsikko = t("osto-tilausta");
		}
		elseif ($toim == "HAAMU") {
			$otsikko = t("ty�/tarvikeostoa");
		}
		elseif ($toim == "YLLAPITO") {
			$otsikko = t("yll�pitosopimusta");
		}
		elseif ($toim == "PROJEKTI") {
			$otsikko = t("tilauksia");
		}
		elseif ($toim == "VALMISTUSMYYNTI" or $toim == "VALMISTUSMYYNTISUPER") {
			$otsikko = t("tilauksia ja valmistuksia");
		}
		elseif ($toim == "JTTOIMITA") {
			$otsikko = t("JT-tilausta");
		}
		elseif ($toim == "HYPER") {
			$otsikko = t("tilauksia");
		}
		else {
			$otsikko = t("myyntitilausta");
			$toim = "";
		}

		if (($toim == "TARJOUS" or $toim == "TARJOUSSUPER") and $tee == '' and $kukarow["kesken"] != 0 and $tilausnumero != "") {
			$query_tarjous = "	UPDATE 	lasku
								SET		alatila = tila,
								 		tila = 'D',
										muutospvm = now(),
										comments = CONCAT(comments, ' $kukarow[nimi] ($kukarow[kuka]) ".t("mit�t�i tilauksen")." ohjelmassa muokkaatilaus.php now()')
								WHERE	yhtio = '$kukarow[yhtio]'
								AND		tunnus = $tilausnumero";
			$result_tarjous = mysql_query($query_tarjous) or pupe_error($query_tarjous);

			echo "<font class='message'>".t("Mit�t�itiin lasku")." $tilausnumero</font><br><br>";
		}

		if (strpos($_SERVER['SCRIPT_NAME'], "muokkaatilaus.php") !== FALSE) {

			echo "<font class='head'>".t("Muokkaa")." $otsikko<hr></font>";

			// Tehd��n popup k�ytt�j�n lep��m�ss� olevista tilauksista
			if ($toim == "SIIRTOLISTA" or $toim == "SIIRTOLISTASUPER" or $toim == "MYYNTITILI" or $toim == "MYYNTITILISUPER") {
				$query = "	SELECT *
							FROM lasku use index (tila_index)
							WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and alatila='' and tila = 'G'";
				$eresult = mysql_query($query) or pupe_error($query);
			}
			elseif ($toim == "SIIRTOTYOMAARAYS" or $toim == "SIIRTOTYOMAARAYSSUPER") {
				$query = "	SELECT *
							FROM lasku use index (tila_index)
							WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and alatila='' and tila = 'S'";
				$eresult = mysql_query($query) or pupe_error($query);
			}
			elseif ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYSSUPER") {
				$query = "	SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='A' and alatila='' and tilaustyyppi='A'";
				$eresult = mysql_query($query) or pupe_error($query);
			}
			elseif ($toim == "REKLAMAATIO") {
				$query = "	SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='C' and alatila='' and tilaustyyppi='R'";
				$eresult = mysql_query($query) or pupe_error($query);
			}
			elseif ($toim == "TARJOUS" or $toim == "TARJOUSSUPER") {
				$query = "	SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='T' and alatila in ('','A') and tilaustyyppi='T'";
				$eresult = mysql_query($query) or pupe_error($query);
			}
			elseif ($toim == "OSTO") {
				$query = "	SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='O' and tilaustyyppi = '' and alatila = ''";
				$eresult = mysql_query($query) or pupe_error($query);
			}
			elseif ($toim == "OSTOSUPER") {
				$query = "	SELECT lasku.*
							FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika)
							JOIN lasku use index (primary) ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and lasku.tila = 'O' and lasku.alatila = 'A' and (lasku.laatija='$kukarow[kuka]' or lasku.tunnus='$kukarow[kesken]')
							WHERE tilausrivi.yhtio 			= '$kukarow[yhtio]'
							and tilausrivi.tyyppi 			= 'O'
							and tilausrivi.laskutettuaika 	= '0000-00-00'
							and tilausrivi.uusiotunnus 		= 0
							GROUP by lasku.tunnus
							ORDER by lasku.tunnus";
				$eresult = mysql_query($query) or pupe_error($query);
			}
			elseif ($toim == "HAAMU") {
				$query = "	SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='O' and tilaustyyppi = 'O' and alatila = ''";
				$eresult = mysql_query($query) or pupe_error($query);
			}
			elseif ($toim == "ENNAKKO") {
				$query = "	SELECT lasku.*
							FROM lasku use index (tila_index)
							LEFT JOIN tilausrivi use index (yhtio_otunnus) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and tilausrivi.tyyppi = 'E')
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							and (lasku.laatija = '$kukarow[kuka]' or lasku.tunnus = '$kukarow[kesken]')
							and lasku.tila in ('E', 'N')
							and lasku.alatila in ('','A','J')
							and lasku.tilaustyyppi = 'E'
							GROUP BY lasku.tunnus";
				$eresult = mysql_query($query) or pupe_error($query);
			}
			elseif ($toim == "VALMISTUS" or $toim == "VALMISTUSSUPER") {
				$query = "	SELECT *
							FROM lasku use index (tila_index)
							WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and alatila='' and tila = 'V'";
				$eresult = mysql_query($query) or pupe_error($query);
			}
			elseif ($toim == "" or $toim == "SUPER") {
				$query = "	SELECT *
							FROM lasku use index (tila_index)
							WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]') and alatila='' and tila in ('N','E')";
				$eresult = mysql_query($query) or pupe_error($query);
			}
			elseif ($toim == "LASKUTUSKIELTO") {
				$query = "	SELECT lasku.*
							FROM lasku use index (tila_index)
							JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus and lasku.chn = '999'
							WHERE lasku.yhtio = '$kukarow[yhtio]' and (lasku.laatija='$kukarow[kuka]' or lasku.tunnus='$kukarow[kesken]') and tila in ('N','L') and alatila != 'X'";
				$eresult = mysql_query($query) or pupe_error($query);
			}
			elseif ($toim == "YLLAPITO") {
				$query = "	SELECT lasku.*
							FROM lasku use index (tila_index)
							WHERE lasku.yhtio = '$kukarow[yhtio]' and (lasku.laatija='$kukarow[kuka]' or lasku.tunnus='$kukarow[kesken]') and tila = '0' and alatila not in ('V','D')";
				$eresult = mysql_query($query) or pupe_error($query);
			}


			if ($toim != "MYYNTITILITOIMITA" and $toim != "EXTRANET" and $toim != "VALMISTUSMYYNTI" and $toim != "VALMISTUSMYYNTISUPER") {
				if (isset($eresult) and  mysql_num_rows($eresult) > 0) {
					// tehd��n aktivoi nappi.. kaikki mit� n�ytet��n saa aktvoida, joten tarkkana queryn kanssa.
					if ($toim == "" or $toim == "SUPER" or $toim == "ENNAKKO" or $toim == "LASKUTUSKIELTO") {
						$aputoim1 = "RIVISYOTTO";
						$aputoim2 = "PIKATILAUS";

						$lisa1 = t("Rivisy�tt��n");
						$lisa2 = t("Pikatilaukseen");
					}
					elseif ($toim == "VALMISTUS" or $toim == "VALMISTUSSUPER") {
						$aputoim1 = "VALMISTAASIAKKAALLE";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($toim == "MYYNTITILISUPER") {
						$aputoim1 = "MYYNTITILI";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($toim == "SIIRTOLISTASUPER") {
						$aputoim1 = "SIIRTOLISTA";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($toim == "TARJOUSSUPER") {
						$aputoim1 = "TARJOUS";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($toim == "TYOMAARAYSSUPER") {
						$aputoim1 = "TYOMAARAYS";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($toim == "OSTO" or $toim == "OSTOSUPER") {
						$aputoim1 = "";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($toim == "HAAMU") {
						$aputoim1 = "HAAMU";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					else {
						$aputoim1 = $toim;
						$aputoim2 = "";

						$lisa1 = t("Muokkaa");
						$lisa2 = "";
					}

					if ($toim == "OSTO" or $toim == "OSTOSUPER") {
						echo "<form method='post' action='tilauskasittely/tilaus_osto.php'>";
					}
					else {
						echo "<form method='post' action='tilauskasittely/tilaus_myynti.php'>";
					}

					echo "	<input type='hidden' name='toim' value='$aputoim1'>
							<input type='hidden' name='tee' value='AKTIVOI'>";

					echo "<br><table>
							<tr>
							<th>".t("Kesken olevat").":</th>
							<td><select name='tilausnumero'>";

					while ($row = mysql_fetch_assoc($eresult)) {
						$select="";
						//valitaan keskenoleva oletukseksi..
						if ($row['tunnus'] == $kukarow["kesken"]) {
							$select="SELECTED";
						}
						echo "<option value='$row[tunnus]' $select>$row[tunnus]: $row[nimi] ($row[luontiaika])</option>";
					}

					echo "</select></td>";

					if ($toim == "" or $toim == "SUPER" or $toim == "ENNAKKO" or $toim == "LASKUTUSKIELTO") {
						echo "<td class='back'><input type='submit' name='$aputoim2' value='$lisa2'></td>";
					}

					echo "<td class='back'><input type='submit' name='$aputoim1' value='$lisa1'></td>";
					echo "</tr></table></form>";
				}
				else {
					echo t("Sinulla ei ole aktiivisia eik� kesken olevia tilauksia").".<br>";
				}
			}
		}

		if (strpos($_SERVER['SCRIPT_NAME'], "muokkaatilaus.php") !== FALSE) {
			// N�ytet��n muuten vaan sopivia tilauksia
			echo "<br><br>
					<form action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='asiakastiedot' value='$asiakastiedot'>
					<input type='hidden' name='limit' value='$limit'>
					<font class='head'>".t("Etsi")." $otsikko<hr></font>
					".t("Sy�t� tilausnumero, nimen tai laatijan osa").":
					<input type='text' name='etsi'>
					<input type='Submit' value = '".t("Etsi")."'>
					</form><br><br>";

			// pvm 30 pv taaksep�in
			$dd = date("d",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
			$mm = date("m",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
			$yy = date("Y",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));

			$haku='';
			if (is_string($etsi))  $haku="and (lasku.nimi like '%$etsi%' or lasku.laatija like '%$etsi%')";
			if (is_numeric($etsi)) $haku="and (lasku.tunnus like '$etsi%' or lasku.ytunnus like '$etsi%')";

			$seuranta = "";
			$seurantalisa = "";

			$kohde = "";
			$kohdelisa = "";

			if ($yhtiorow["tilauksen_seuranta"] !="") {
				$seuranta = " seuranta, ";
				$seurantalisa = "LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus";
			}

			if ($yhtiorow["tilauksen_kohteet"] != "") {
				$kohde = " asiakkaan_kohde.kohde kohde, ";
				$kohdelisa = "LEFT JOIN asiakkaan_kohde ON asiakkaan_kohde.yhtio=laskun_lisatiedot.yhtio and asiakkaan_kohde.tunnus=laskun_lisatiedot.asiakkaan_kohde";
			}

			if ($kukarow['resoluutio'] == 'I' and $toim != "SIIRTOLISTA" and $toim != "SIIRTOLISTASUPER" and $toim != "MYYNTITILI" and $toim != "MYYNTITILISUPER" and $toim != "EXTRANET" and $toim != "TARJOUS") {
				$toimaikalisa = ' lasku.toimaika, ';
			}


			if ($limit == "") {
				$rajaus = "LIMIT 50";
			}
			else {
				$rajaus	= "";
			}
		}

		if ($asiakastiedot == "KAIKKI") {
			$asiakasstring = " concat_ws('<br>', lasku.ytunnus, concat_ws(' ',lasku.nimi, lasku.nimitark), if(lasku.nimi!=lasku.toim_nimi, concat_ws(' ',lasku.toim_nimi, lasku.toim_nimitark), NULL), if(lasku.postitp!=lasku.toim_postitp, lasku.toim_postitp, NULL)) ";
			$assel1 = "";
			$assel2 = "CHECKED";
		}
		else {
			$asiakasstring = " concat(lasku.ytunnus, '<br>', lasku.nimi) ";
			$assel1 = "CHECKED";
			$assel2 = "";
		}

		echo "<br><form action='$PHP_SELF' method='post'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='limit' value='$limit'>
				".t("N�yt� vain laskutustiedot")." <input type='radio' name='asiakastiedot' value='NORMI' onclick='submit();' $assel1>
				".t("N�yt� my�s toimitusasiakkaan tiedot")." <input type='radio' name='asiakastiedot' value='KAIKKI' onclick='submit();' $assel2>
				</form>";

		// Etsit��n muutettavaa tilausta
		if ($toim == 'HYPER') {

			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, ";

			if ($kukarow['hinnat'] == 0) $query .= " round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo, round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa, ";

			$query .= "	$toimaikalisa alatila, tila, lasku.tunnus, lasku.mapvm, lasku.tilaustyyppi
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						LEFT JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D')
						WHERE lasku.yhtio = '$kukarow[yhtio]' and
						(((tila='V' and alatila in ('','A','B','J')) or (lasku.tila in ('L','N') and lasku.alatila in ('A','')))
						or (lasku.tila = '0' and lasku.alatila NOT in ('D'))
						or (lasku.tila = 'N' and lasku.alatila = 'F')
						or (lasku.tila = 'V' and lasku.alatila in ('','A','B','C','J'))
						or (lasku.tila = 'V' and lasku.alatila in ('','A','B','J'))
						or (lasku.tila = 'T' and lasku.tilaustyyppi = 'T' and lasku.alatila in ('','A'))
						or (lasku.tila = 'T' and lasku.tilaustyyppi = 'T' and lasku.alatila in ('','A','X'))
						or (lasku.tila in ('A','L','N') and lasku.tilaustyyppi = 'A' and lasku.alatila != 'X')
						or (lasku.tila in ('L','N') and lasku.alatila != 'X')
						or (lasku.tila in ('L','N') and lasku.alatila in ('A',''))
						or (lasku.tila in ('L','N','C') and tilaustyyppi = 'R' and alatila in ('','A','B','C','J','D'))
						or (lasku.tila in ('N','L') and lasku.alatila != 'X' and lasku.chn = '999')
						or (lasku.tila in ('R','L','N','A') and alatila NOT in ('X') and lasku.tilaustyyppi != '9')
						or (lasku.tila = 'E' and tilausrivi.tyyppi = 'E')
						or (lasku.tila = 'G' and lasku.alatila in ('','A','B','C','D','J','T'))
						or (lasku.tila = 'G' and lasku.alatila in ('','A','J'))
						or (lasku.tila = 'G' and lasku.tilaustyyppi = 'M' and lasku.alatila in ('','A','B','C','J'))
						or (lasku.tila = 'G' and lasku.tilaustyyppi = 'M' and lasku.alatila in ('','A','B','J'))
						or (lasku.tila = 'N' and lasku.alatila = 'U')
						or (lasku.tila = 'S' and lasku.alatila in ('','A','B','J','C'))
						or (lasku.tila in ('L','N','V') and lasku.alatila NOT in ('X','V'))
						or (lasku.tila = 'G' and lasku.tilaustyyppi = 'M' and lasku.alatila = 'V'))
						$haku
						GROUP BY lasku.tunnus
						ORDER BY lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(if(lasku.alatila='X', 0, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)))),2) arvo,
								round(sum(if(lasku.alatila='X', 0, tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)))),2) summa,
								round(sum(if(lasku.alatila!='X', 0, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)))),2) jt_arvo,
								round(sum(if(lasku.alatila!='X', 0, tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)))),2) jt_summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('L', 'N') and lasku.alatila != 'X'";
				$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 5;
		}
		elseif ($toim == 'SUPER') {

			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, ";

			if ($kukarow['hinnat'] == 0) $query .= " round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo, round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa, ";

			$query .= "	$toimaikalisa alatila, tila, lasku.tunnus, lasku.mapvm, lasku.tilaustyyppi
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						LEFT JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D')
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('L', 'N') and lasku.alatila != 'X'
						$haku
						GROUP BY lasku.tunnus
						ORDER BY lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(if(lasku.alatila='X', 0, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)))),2) arvo,
								round(sum(if(lasku.alatila='X', 0, tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)))),2) summa,
								round(sum(if(lasku.alatila!='X', 0, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)))),2) jt_arvo,
								round(sum(if(lasku.alatila!='X', 0, tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)))),2) jt_summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('L', 'N') and lasku.alatila != 'X'";
				$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 5;
		}
		elseif ($toim == 'ENNAKKO') {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, viesti tilausviite, $toimaikalisa alatila, tila, lasku.tunnus, tilausrivi.tyyppi trivityyppi
						FROM lasku use index (tila_index)
						LEFT JOIN tilausrivi use index (yhtio_otunnus) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and tilausrivi.tyyppi = 'E')
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila in ('E','N')
						and lasku.tilaustyyppi = 'E'
						$haku
						GROUP BY lasku.tunnus
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi = 'E')
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila = 'E'";
				$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 4;
		}
		elseif ($toim == "SIIRTOLISTA") {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring varasto, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, lasku.viesti tilausviite, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.alatila in ('','A','J')
						$haku
						order by lasku.luontiaika desc
						$rajaus";
			$miinus = 3;
		}
		elseif ($toim == "SIIRTOLISTASUPER") {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring varasto, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, lasku.viesti tilausviite, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.alatila in ('','A','B','C','D','J','T')
						$haku
						order by lasku.luontiaika desc
						$rajaus";
			$miinus = 3;
		}
		elseif ($toim == "MYYNTITILI") {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, lasku.viesti tilausviite, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.tilaustyyppi = 'M' and lasku.alatila in ('','A','B','J')
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.tilaustyyppi = 'M' and lasku.alatila in ('','A','B','J')";
				$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}
			$miinus = 3;
		}
		elseif ($toim == "MYYNTITILISUPER") {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, lasku.viesti tilausviite, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.tilaustyyppi = 'M' and lasku.alatila in ('','A','B','C','J')
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.tilaustyyppi = 'M' and lasku.alatila in ('','A','B','C','J')";
				$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 3;
		}
		elseif ($toim == "MYYNTITILITOIMITA") {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, lasku.viesti tilausviite, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.tilaustyyppi = 'M' and lasku.alatila = 'V'
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			 // haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
				 				round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
				 				round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
				 				count(distinct lasku.tunnus) kpl
				 				FROM lasku use index (tila_index)
				 				JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
				 				WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.tilaustyyppi = 'M' and lasku.alatila = 'V'";
				 $sumresult = mysql_query($sumquery) or pupe_error($sumquery);
				 $sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 3;
		}
		elseif ($toim == "JTTOIMITA") {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='N' and lasku.alatila='U'
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='N' and lasku.alatila='U'";
				$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 3;
		}
		elseif ($toim == 'VALMISTUS') {
			$query = "	SELECT lasku.tunnus tilaus, lasku.nimi varastoon, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, lasku.viesti tilausviite, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.tilaustyyppi
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila = 'V'
						and lasku.alatila in ('','A','B','J')
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus) and tilausrivi.tyyppi IN ('L','W')
								WHERE lasku.yhtio = '$kukarow[yhtio]'
								and lasku.tila = 'V'
								and lasku.alatila in ('','A','B','J')
								and lasku.tilaustyyppi != 'W'";
				$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 4;
		}
		elseif ($toim == "VALMISTUSSUPER") {
			$query = "	SELECT lasku.tunnus tilaus, lasku.nimi varastoon, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, lasku.viesti tilausviite, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.tilaustyyppi
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila = 'V'
						and lasku.alatila in ('','A','B','C','J')
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus) and tilausrivi.tyyppi IN ('L','W')
								WHERE lasku.yhtio = '$kukarow[yhtio]'
								and lasku.tila = 'V'
								and lasku.alatila in ('','A','B','C','J')
								and lasku.tilaustyyppi != 'W'";
				$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 4;
		}
		elseif ($toim == "VALMISTUSMYYNTI") {
			$query = "	SELECT lasku.tunnus tilaus, $seuranta $asiakasstring asiakas, $kohde lasku.viesti, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, kuka.extranet extra, lasku.tilaustyyppi
						FROM lasku use index (tila_index)
						LEFT JOIN kuka ON lasku.yhtio=kuka.yhtio and lasku.laatija=kuka.kuka
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						$seurantalisa
						$kohdelisa
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and ((tila='V' and alatila in ('','A','B','J')) or (lasku.tila in ('L','N') and lasku.alatila in ('A','')))
						$haku
						HAVING extra = '' or extra is null
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus) and tilausrivi.tyyppi IN ('L','W')
								WHERE lasku.yhtio = '$kukarow[yhtio]'
								and ((tila='V' and alatila in ('','A','B','J')) or (lasku.tila in ('L','N') and lasku.alatila in ('A','')))
								and tilaustyyppi != 'W'";
				$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 5;
		}
		elseif ($toim == "VALMISTUSMYYNTISUPER") {
			$query = "	SELECT lasku.tunnus tilaus, $seuranta $asiakasstring asiakas, $kohde lasku.viesti, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, kuka.extranet extra, tilaustyyppi
						FROM lasku use index (tila_index)
						LEFT JOIN kuka ON lasku.yhtio=kuka.yhtio and lasku.laatija=kuka.kuka
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						$seurantalisa
						$kohdelisa
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and tila in ('L','N','V')
						and alatila not in ('X','V')
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus) and tilausrivi.tyyppi IN ('L','W')
								WHERE lasku.yhtio = '$kukarow[yhtio]'
								and tila in ('L','N','V')
								and alatila not in ('X','V')
								and tilaustyyppi != 'W'";
				$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 5;
		}
		elseif ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYSSUPER") {

			if ($toim == "TYOMAARAYSSUPER") {
				$tyomalatlat = " and lasku.alatila != 'X' ";
			}
			else {
				$tyomalatlat = " and lasku.alatila in ('','A','B','C','J') ";
			}

			$query = "	SELECT lasku.tunnus tilaus,
						concat_ws('<br>', lasku.ytunnus, lasku.nimi, if (lasku.tilausyhteyshenkilo='', NULL, lasku.tilausyhteyshenkilo), if (lasku.viesti='', NULL, lasku.viesti), concat_ws(' ', ifnull((SELECT selitetark_2 FROM avainsana WHERE avainsana.yhtio=tyomaarays.yhtio and avainsana.laji = 'sarjanumeron_li' and avainsana.selite = 'MERKKI' and avainsana.selitetark=tyomaarays.merkki LIMIT 1), tyomaarays.merkki), tyomaarays.mallivari)) asiakas, lasku.luontiaika,
						if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa alatila, lasku.tila, lasku.tunnus, lasku.tilaustyyppi
						FROM lasku use index (tila_index)
						LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('A','L','N') and lasku.tilaustyyppi='A' $tyomalatlat
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
		    					round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
		    					round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
		    					count(distinct lasku.tunnus) kpl
		    					FROM lasku use index (tila_index)
		    					JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
		    					WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('A','L','N') and lasku.tilaustyyppi='A' and lasku.alatila in ('','A','B','C','J')";
		    	$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
		    	$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 4;
		}
		elseif ($toim == "REKLAMAATIO") {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.tilaustyyppi
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('L','N','C') and lasku.tilaustyyppi='R' and lasku.alatila in ('','A','B','C','J','D')
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
				   				round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
						    	round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
						    	count(distinct lasku.tunnus) kpl
						    	FROM lasku use index (tila_index)
						    	JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
						    	WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('L','N','C') and lasku.tilaustyyppi='R' and lasku.alatila in ('','A','B','C','J','D')";
				$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 4;
		}
		elseif ($toim == "SIIRTOTYOMAARAYS" or $toim == "SIIRTOTYOMAARAYSSUPER") {
			$query = "	SELECT lasku.tunnus tilaus,
						concat_ws('<br>',lasku.nimi,lasku.tilausyhteyshenkilo,lasku.viesti, concat_ws(' ', ifnull((SELECT selitetark_2 FROM avainsana WHERE avainsana.yhtio=tyomaarays.yhtio and avainsana.laji = 'sarjanumeron_li' and avainsana.selite = 'MERKKI' and avainsana.selitetark=tyomaarays.merkki LIMIT 1), tyomaarays.merkki), tyomaarays.mallivari)) asiakas,
						lasku.ytunnus, lasku.luontiaika,
						if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa alatila, lasku.tila, lasku.tunnus
						FROM lasku use index (tila_index)
						LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='S' and alatila in ('','A','B','J','C')
						$haku
						order by lasku.luontiaika desc
						$rajaus";
			$miinus = 3;
		}
		elseif ($toim == "TARJOUS") {
			$query = "	SELECT if(tunnusnippu>0,tunnusnippu,lasku.tunnus) tarjous, $asiakasstring asiakas, $seuranta $kohde concat_ws('<br>', lasku.luontiaika, lasku.muutospvm) Pvm,
						if(if(lasku.olmapvm != '0000-00-00', lasku.olmapvm, date_add(lasku.muutospvm, interval $yhtiorow[tarjouksen_voimaika] day)) >= now(), '<font class=\"green\">Voimassa</font>', '<font class=\"red\">Er��ntynyt</font>') voimassa,
						DATEDIFF(if(lasku.olmapvm != '0000-00-00', lasku.olmapvm, date_add(lasku.muutospvm, INTERVAL $yhtiorow[tarjouksen_voimaika] day)), now()) pva,
						if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija,
						$toimaikalisa alatila, tila, lasku.tunnus, tunnusnippu, lasku.liitostunnus
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						$seurantalisa
						$kohdelisa
						WHERE lasku.yhtio = '$kukarow[yhtio]' and tila ='T' and tilaustyyppi='T' and alatila in ('','A')
						$haku
						ORDER BY lasku.tunnus desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila ='T' and lasku.tilaustyyppi='T' and lasku.alatila in ('','A')";
				$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 5;
		}
		elseif ($toim == "TARJOUSSUPER") {
			$query = "	SELECT if(tunnusnippu>0,tunnusnippu,lasku.tunnus) tarjous, $asiakasstring asiakas, $seuranta $kohde concat_ws('<br>', lasku.luontiaika, lasku.muutospvm) Pvm,
						if(if(lasku.olmapvm != '0000-00-00', lasku.olmapvm, date_add(lasku.muutospvm, interval $yhtiorow[tarjouksen_voimaika] day)) >= now(), '<font class=\"green\">Voimassa</font>', '<font class=\"red\">Er��ntynyt</font>') voimassa,
						DATEDIFF(if(lasku.olmapvm != '0000-00-00', lasku.olmapvm, date_add(lasku.muutospvm, INTERVAL $yhtiorow[tarjouksen_voimaika] day)), now()) pva,
						if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija,
						$toimaikalisa alatila, tila, lasku.tunnus, tunnusnippu
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						$seurantalisa
						$kohdelisa
						WHERE lasku.yhtio = '$kukarow[yhtio]' and tila ='T' and tilaustyyppi='T' and alatila in ('','A','X')
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan kaikkien avoimien tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila ='T' and lasku.tilaustyyppi='T' and lasku.alatila in ('','A')";
				$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 4;
		}
		elseif ($toim == "EXTRANET") {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila = 'N' and lasku.alatila = 'F'
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila = 'N' and lasku.alatila = 'F'";
				$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 3;
		}
		elseif ($toim == "LASKUTUSKIELTO") {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('N','L') and lasku.alatila != 'X' and lasku.chn = '999'
						$haku
						order by lasku.luontiaika desc
						$rajaus";

		   // haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
		   						round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
				   				round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
				   				count(distinct lasku.tunnus) kpl
				   				FROM lasku use index (tila_index)
				   				JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
				   				WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('N','L') and lasku.alatila != 'X' and lasku.chn = '999'";
				   $sumresult = mysql_query($sumquery) or pupe_error($sumquery);
				   $sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 3;
		}
		elseif ($toim == 'OSTO') {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus,
							(SELECT count(*)
							FROM tilausrivi AS aputilausrivi use index (yhtio_otunnus)
							WHERE aputilausrivi.yhtio = lasku.yhtio
							AND aputilausrivi.otunnus = lasku.tunnus
							AND aputilausrivi.uusiotunnus > 0
							AND aputilausrivi.kpl <> 0
							AND aputilausrivi.tyyppi = 'O') varastokpl
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila		= 'O'
						and lasku.alatila	= ''
						and lasku.tilaustyyppi	= ''
						$haku
						ORDER by lasku.luontiaika desc
						$rajaus";
			$miinus = 4;
		}
		elseif ($toim == 'OSTOSUPER') {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus,
							(SELECT count(*)
							FROM tilausrivi AS aputilausrivi use index (yhtio_otunnus)
							WHERE aputilausrivi.yhtio = tilausrivi.yhtio
							AND aputilausrivi.otunnus = tilausrivi.otunnus
							AND aputilausrivi.uusiotunnus > 0
							AND aputilausrivi.kpl <> 0
							AND aputilausrivi.tyyppi = 'O') varastokpl
						FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika)
						JOIN lasku use index (primary) ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and lasku.tila = 'O' and lasku.alatila != ''
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE tilausrivi.yhtio 			= '$kukarow[yhtio]'
						and tilausrivi.tyyppi 			= 'O'
						and tilausrivi.laskutettuaika 	= '0000-00-00'
						and tilausrivi.uusiotunnus 		= 0
						and lasku.tilaustyyppi			= ''
						$haku
						GROUP by lasku.tunnus
						ORDER by lasku.luontiaika desc
						$rajaus";
			$miinus = 4;
		}
		elseif ($toim == 'HAAMU') {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus,
							(SELECT count(*)
							FROM tilausrivi AS aputilausrivi use index (yhtio_otunnus)
							WHERE aputilausrivi.yhtio = lasku.yhtio
							AND aputilausrivi.otunnus = lasku.tunnus
							AND aputilausrivi.uusiotunnus > 0
							AND aputilausrivi.kpl <> 0
							AND aputilausrivi.tyyppi = 'O') varastokpl
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila			= 'O'
						and lasku.alatila		= ''
						and lasku.tilaustyyppi	= 'O'
						$haku
						ORDER by lasku.luontiaika desc
						$rajaus";
			$miinus = 4;
		}
		elseif ($toim == 'PROJEKTI') {
			$query = "	SELECT if(lasku.tunnusnippu > 0 and lasku.tunnusnippu!=lasku.tunnus, concat(lasku.tunnus,',',lasku.tunnusnippu), lasku.tunnus) tilaus, $seuranta lasku.nimi asiakas, $kohde lasku.ytunnus, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.tunnusnippu, lasku.liitostunnus
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						$seurantalisa
						$kohdelisa
						WHERE lasku.yhtio = '$kukarow[yhtio]' and tila IN ('R','L','N','A') and alatila NOT IN ('X') and lasku.tilaustyyppi!='9'
						$haku
						ORDER by lasku.tunnusnippu desc, tunnus asc
						$rajaus";
			$miinus = 5;
		}
		elseif ($toim == 'YLLAPITO') {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka != kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi) laatija, concat_ws('###', sopimus_alkupvm, sopimus_loppupvm) sopimuspvm, lasku.alatila, lasku.tila, lasku.tunnus, tunnusnippu, sopimus_loppupvm
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio=lasku.yhtio and kuka1.kuka=lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio=lasku.yhtio and kuka2.tunnus=lasku.myyja)
						LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio=lasku.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and tila = '0' and alatila NOT IN ('D')
						$haku
						ORDER by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
								JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio=lasku.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus and (laskun_lisatiedot.sopimus_loppupvm >= now() or laskun_lisatiedot.sopimus_loppupvm = '0000-00-00'))
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('0') and lasku.alatila != 'D'";
				$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 5;
		}
		else {
			$query = "	SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika,
						if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija,
						$seuranta $kohde  $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, kuka1.extranet extra, lasku.mapvm, lasku.tilaustyyppi
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio=lasku.yhtio and kuka1.kuka=lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio=lasku.yhtio and kuka2.tunnus=lasku.myyja)
						$seurantalisa
						$kohdelisa
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila in ('L','N')
						and lasku.alatila in ('A','')
						$haku
						HAVING extra = '' or extra is null
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			if ($kukarow['hinnat'] == 0) {
				$sumquery = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
								count(distinct lasku.tunnus) kpl
								FROM lasku use index (tila_index)
								JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in('L','N') and lasku.alatila in ('A','')";
				$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
				$sumrow = mysql_fetch_assoc($sumresult);
			}

			$miinus = 6;
		}
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 0) {

			if (strpos($_SERVER['SCRIPT_NAME'], "muokkaatilaus.php") !== FALSE) {
				if(@include('Spreadsheet/Excel/Writer.php')) {

					//keksit��n failille joku varmasti uniikki nimi:
					list($usec, $sec) = explode(' ', microtime());
					mt_srand((float) $sec + ((float) $usec * 100000));
					$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

					$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
					$workbook->setVersion(8);
					$worksheet = $workbook->addWorksheet('Sheet 1');

					$format_bold = $workbook->addFormat();
					$format_bold->setBold();

					$excelrivi = 0;
				}
			}

			echo "<table>";
			echo "<tr>";

			for ($i=0; $i < mysql_num_fields($result)-$miinus; $i++) {
				echo "<th align='left'>".t(mysql_field_name($result,$i))."</th>";

				if(isset($workbook)) {
					$worksheet->write($excelrivi, $i, ucfirst(t(mysql_field_name($result,$i))), $format_bold);
				}
			}
			$excelrivi++;

			echo "<th align='left'>".t("tyyppi")."</th><td class='back'></td></tr>";

			$lisattu_tunnusnippu  = array();

			while ($row = mysql_fetch_assoc($result)) {

				if ($toim == 'HYPER') {

					if ($row["tila"] == 'E' and $row["trivityyppi"] == 'E') {
						$whiletoim = 'ENNAKKO';
					}
					elseif ($row["tila"] == 'N' and $row["alatila"] == 'U') {
						$whiletoim = "JTTOIMITA";
					}
					elseif ($row["tila"] == 'N' and $row["alatila"] == 'F') {
						$whiletoim = "EXTRANET";
					}
					elseif (in_array($row["tila"], array('N','L')) and $row["alatila"] != 'X' and $row["chn"] == '999') {
						$whiletoim = "LASKUTUSKIELTO";
					}
					elseif ($row["tila"] == 'G' and in_array($row["alatila"], array('','A','B','J')) and $row["tilaustyyppi"] == 'M') {
						$whiletoim = "MYYNTITILI";
					}
					elseif ($row["tila"] == 'G' and in_array($row["alatila"], array('','A','B','C','J')) and $row["tilaustyyppi"] == 'M') {
						$whiletoim = "MYYNTITILISUPER";
					}
					elseif ($row["tila"] == 'G' and $row["alatila"] == 'V' and $row["tilaustyyppi"] == 'M') {
						$whiletoim = "MYYNTITILITOIMITA";
					}
					elseif ($row["tila"] == 'T' and $row["tilaustyyppi"] == 'T' and in_array($row["alatila"], array('','A'))) {
						$whiletoim = "TARJOUS";
					}
					elseif ($row["tila"] == 'T' and $row["tilaustyyppi"] == 'T' and in_array($row["alatila"], array('','A','X'))) {
						$whiletoim = "TARJOUSSUPER";
					}
					elseif (in_array($row["tila"], array('A','L','N')) and $row["tilaustyyppi"] == 'A' and in_array($row["alatila"], array('','A','B','C','J'))) {
						$whiletoim = "TYOMAARAYS";
					}
					elseif (in_array($row["tila"], array('A','L','N')) and $row["tilaustyyppi"] == 'A' and $row["alatila"] != 'X') {
						$whiletoim = "TYOMAARAYSSUPER";
					}
					elseif (in_array($row["tila"], array('L','N','C')) and $row["tilaustyyppi"] == 'R' and in_array($row["alatila"], array('','A','B','C','J','D'))) {
						$whiletoim = "REKLAMAATIO";
					}
					elseif ($row["tila"] == 'G' and in_array($row["alatila"], array('','A','J'))) {
						$whiletoim = "SIIRTOLISTA";
					}
					elseif ($row["tila"] == 'G' and in_array($row["alatila"], array('','A','B','C','D','J','T'))) {
						$whiletoim = "SIIRTOLISTASUPER";
					}
					elseif ($row["tila"] == 'V' and in_array($row["alatila"], array('','A','B','J'))) {
						$whiletoim = 'VALMISTUS';
					}
					elseif ($row["tila"] == 'V' and in_array($row["alatila"], array('','A','B','C','J'))) {
						$whiletoim = "VALMISTUSSUPER";
					}
					elseif (($row["tila"] == 'V' and in_array($row["alatila"], array('','A','B','J'))) or (in_array($row["tila"], array('L','N')) and in_array($row["alatila"], array('A','')))) {
						$whiletoim = "VALMISTUSMYYNTI";
					}
					elseif (in_array($row["tila"], array('L','N','V')) and !in_array($row["alatila"], array('X','V'))) {
						$whiletoim == "VALMISTUSMYYNTISUPER";
					}
					elseif ($row["tila"] == 'S' and in_array($row["alatila"], array('','A','B','J','C'))) {
						$whiletoim = "SIIRTOTYOMAARAYS";
					}
					elseif ($row["tila"] == '0' and $row["alatila"] != 'D') {
						$whiletoim = 'YLLAPITO';
					}
					elseif (in_array($row["tila"], array('L','N')) and in_array($row["alatila"], array('A',''))) {
						$whiletoim = '';
					}
					if (in_array($row["tila"], array('L','N')) and $row["alatila"] != 'X') {
						$whiletoim = 'SUPER';
					}
					elseif (in_array($row["tila"], array('R','L','N','A')) and $row["alatila"] != 'X' and $row["tilaustyyppi"] != '9') {
						$whiletoim = 'PROJEKTI';
					}
				}
				else {
					$whiletoim = $toim;
				}

				$piilotarivi = "";
				$pitaako_varmistaa = "";

				// jos kyseess� on "odottaa JT tuotteita rivi"
				if ($row["tila"] == "N" and $row["alatila"] == "T") {
					$query = "SELECT tunnus from tilausrivi where yhtio='$kukarow[yhtio]' and tyyppi='L' and otunnus='$row[tilaus]'";
					$countres = mysql_query($query) or pupe_error($query);

					// ja sill� ei ole yht��n rivi�
					if (mysql_num_rows($countres) == 0) {
						$piilotarivi = "kylla";
					}
				}

				//	Nipuista vain se viimeisin jos niin halutaan
				if ($row["tunnusnippu"] > 0 and ($whiletoim == "PROJEKTI" or $whiletoim == "TARJOUS")) {

					//	Tunnusnipuista n�ytet��n vaan se eka!
					// ja sill� ei ole yht��n rivi�
					if (array_search($row["tunnusnippu"], $lisattu_tunnusnippu) !== false) {
						$piilotarivi = "kylla";
					}
					else {
						$lisattu_tunnusnippu[] = $row["tunnusnippu"];
					}
				}

				if ($piilotarivi == "") {

					// jos kyseess� on "odottaa JT tuotteita rivi ja kyseessa on toim=JTTOIMITA"
					if ($row["tila"] == "N" and $row["alatila"] == "U") {

						if ($yhtiorow["varaako_jt_saldoa"] != "") {
							$lisavarattu = " + tilausrivi.varattu";
						}
						else {
							$lisavarattu = "";
						}

						$query = "	SELECT tilausrivi.tuoteno, tilausrivi.jt $lisavarattu jt
									from tilausrivi
									where tilausrivi.yhtio	= '$kukarow[yhtio]'
									and tilausrivi.tyyppi	= 'L'
									and tilausrivi.otunnus	= '$row[tilaus]'";
						$countres = mysql_query($query) or pupe_error($query);

						$jtok = 0;

						while ($countrow = mysql_fetch_assoc($countres)) {
							list( , , $jtapu_myytavissa) = saldo_myytavissa($countrow["tuoteno"], "JTSPEC", 0, "");

							if ($jtapu_myytavissa < $countrow["jt"]) {
								$jtok--;
							}
						}
					}

					echo "<tr class='aktiivi'>";

					for ($i=0; $i<mysql_num_fields($result)-$miinus; $i++) {

						$fieldname = mysql_field_name($result,$i);

						if ($whiletoim == "YLLAPITO" and $row["sopimus_loppupvm"] < date("Y-m-d") and $row["sopimus_loppupvm"] != '0000-00-00') {
							$class = 'tumma';
						}
						else {
							$class = '';
						}

						if ($fieldname == 'luontiaika' or $fieldname == 'toimaika') {
							echo "<td class='$class' valign='top' align='right'>".tv1dateconv($row[$fieldname], "PITKA", "LYHYT")."</td>";
						}
						elseif ($fieldname == 'sopimuspvm') {

							list($sopalk, $soplop) = explode("###", $row[$fieldname]);

							if ($soplop == "0000-00-00") {
								$soplop = t("Toistaiseksi");
							}
							else {
								$soplop = tv1dateconv($soplop);
							}

							echo "<td class='$class' valign='top' align='right'>".tv1dateconv($sopalk)." - $soplop</td>";
						}
						elseif ($fieldname == 'Pvm') {
							list($aa, $bb) = explode('<br>', $row[$fieldname]);

							echo "<td class='$class' valign='top'>".tv1dateconv($aa, "PITKA", "LYHYT")."<br>".tv1dateconv($bb, "PITKA", "LYHYT")."</td>";
						}
						elseif ($fieldname == "tilaus") {

							$query_comments = "	SELECT group_concat(concat_ws('<br>', comments, sisviesti2) SEPARATOR '<br><br>') comments
												FROM lasku use index (primary)
												WHERE yhtio = '$kukarow[yhtio]'
												AND tunnus in (".$row[$fieldname].")
												AND (comments != '' OR sisviesti2 != '')";
							$result_comments = mysql_query($query_comments) or pupe_error($query_comments);
							$row_comments = mysql_fetch_assoc($result_comments);

							if (trim($row_comments["comments"]) != "") {

								echo "<td class='$class' align='right' valign='top'>";
								echo "<div id='div_kommentti".$row[$fieldname]."' class='popup' style='width: 500px;'>";
								echo $row_comments["comments"];
								echo "</div>";
								echo "<a class='tooltip' id='kommentti".$row[$fieldname]."'>".str_replace(",", "<br>*", $row[$fieldname])."</a></td>";
							}
							else {
								echo "<td class='$class' align='right' valign='top'>".str_replace(",", "<br>*", $row[$fieldname])."</td>";
							}
						}
						elseif ($fieldname == "seuranta") {

							$img = "mini-comment.png";
							$linkkilisa = "";
							$query_comments = "	SELECT group_concat(tunnus) tunnukset
												FROM lasku
												WHERE yhtio = '$kukarow[yhtio]'
												AND lasku.tila != 'S'
												AND tunnusnippu = '$row[tunnusnippu]' and tunnusnippu>0";
							$ares = mysql_query($query_comments) or pupe_error($query_comments);

							if (mysql_num_rows($ares) > 0) {
								$arow = mysql_fetch_assoc($ares);

								if ($arow["tunnukset"] != "") {
									//	Olisiko meill� kalenterissa kommentteja?
									$query_comments = "	SELECT tunnus
														FROM kalenteri
														WHERE yhtio = '$kukarow[yhtio]'
														AND tyyppi = 'Memo'
														AND otunnus IN ($arow[tunnukset])";
									$result_comments = mysql_query($query_comments) or pupe_error($query_comments);

									$nums="";
									if (mysql_num_rows($result_comments) > 0) {
										$img = "info.png";
										$linkkilisa = "onmouseover=\"popUp(event, 'asiakasmemo_".$row[$fieldname]."', '0', '0', '{$palvelin2}crm/asiakasmemo.php?tee=NAYTAMUISTIOT&liitostunnus=$row[liitostunnus]&tunnusnippu=$row[tunnusnippu]', false, true); return false;\" onmouseout=\"popUp(event, 'asiakasmemo_".$row[$fieldname]."'); return false;\"";
									}
								}
							}

							echo "<td class='$class' valign='top' NOWRAP>".$row[$fieldname]." <div style='float: right;'><img src='pics/lullacons/$img' class='info' $linkkilisa onclick=\"window.open('{$palvelin2}crm/asiakasmemo.php?tee=NAYTA&liitostunnus=$row[liitostunnus]&tunnusnippu=$row[tunnusnippu]&from=muokkaatilaus.php');\"> $nums</div></td>";
						}
						elseif (is_numeric($row[$fieldname])) {
							echo "<td class='$class' align='right' valign='top'>".$row[$fieldname]."</td>";
						}
						else {
							echo "<td class='$class' valign='top'>".$row[$fieldname]."</td>";
						}

						if (isset($workbook)) {
							if (mysql_field_type($result,$i) == 'real') {
								$worksheet->writeNumber($excelrivi, $i, sprintf("%.02f", $row[$fieldname]));
							}
							else {
								$worksheet->writeString($excelrivi, $i, $row[$fieldname]);
							}
						}
					}

					if ($row["tila"] == "N" and $row["alatila"] == "U") {
						if ($jtok == 0) {
							echo "<td class='$class' valign='top'><font style='color:#00FF00;'>".t("Voidaan toimittaa")."</font></td>";

							if(isset($workbook)) {
								$worksheet->writeString($excelrivi, $i, "Voidaan toimittaa");
								$i++;
							}
						}
						else {
							echo "<td class='$class' valign='top'><font style='color:#FF0000;'>".t("Ei voida toimittaa")."</font></td>";

							if(isset($workbook)) {
								$worksheet->writeString($excelrivi, $i, t("Ei voida toimittaa"));
								$i++;
							}
						}
					}
					else {

						$laskutyyppi = $row["tila"];
						$alatila	 = $row["alatila"];

						//tehd��n selv�kielinen tila/alatila
						require "inc/laskutyyppi.inc";

						$tarkenne = " ";

						if ($row["tila"] == "V" and $row["tilaustyyppi"] == "V") {
							$tarkenne = " (".t("Asiakkaalle").") ";
						}
						elseif ($row["tila"] == "V" and  $row["tilaustyyppi"] == "W") {
							$tarkenne = " (".t("Varastoon").") ";
						}
						elseif(($row["tila"] == "N" or $row["tila"] == "L") and $row["tilaustyyppi"] == "R") {
							$tarkenne = " (".t("Reklamaatio").") ";
						}
						elseif(($row["tila"] == "N" or $row["tila"] == "L") and $row["tilaustyyppi"] == "A") {
							$laskutyyppi = "Ty�m��r�ys";
						}

						if ($row["varastokpl"] > 0) {
							$varastotila = "<font class='info'><br>".t("Viety osittain varastoon")."</font>";
						} else {
							$varastotila = "";
						}

						echo "<td class='$class' valign='top'>".t("$laskutyyppi")."$tarkenne".t("$alatila")." $varastotila</td>";

						if(isset($workbook)) {
							$worksheet->writeString($excelrivi, $i, t("$laskutyyppi")."$tarkenne".t("$alatila")." $varastotila");
							$i++;
						}
					}

					$excelrivi++;

					// tehd��n aktivoi nappi.. kaikki mit� n�ytet��n saa aktvoida, joten tarkkana queryn kanssa.
					if ($whiletoim == "" or $whiletoim == "SUPER" or $whiletoim == "EXTRANET" or $whiletoim == "ENNAKKO" or $whiletoim == "JTTOIMITA" or $whiletoim == "LASKUTUSKIELTO"or (($whiletoim == "VALMISTUSMYYNTI" or $whiletoim == "VALMISTUSMYYNTISUPER") and $row["tila"] != "V")) {
						$aputoim1 = "RIVISYOTTO";
						$aputoim2 = "PIKATILAUS";

						$lisa1 = t("Rivisy�tt��n");
						$lisa2 = t("Pikatilaukseen");
					}
					elseif (($whiletoim == "VALMISTUS" or $whiletoim == "VALMISTUSSUPER" or $whiletoim == "VALMISTUSMYYNTI" or $whiletoim == "VALMISTUSMYYNTISUPER") and $row["tila"] == "V" and $row["tilaustyyppi"] == "V") {
						$aputoim1 = "VALMISTAASIAKKAALLE";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif (($whiletoim == "VALMISTUS" or $whiletoim == "VALMISTUSSUPER" or $whiletoim == "VALMISTUSMYYNTI" or $whiletoim == "VALMISTUSMYYNTISUPER") and $row["tila"] == "V" and $row["tilaustyyppi"] != "V") {
						$aputoim1 = "VALMISTAVARASTOON";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($whiletoim == "MYYNTITILISUPER" or $whiletoim == "MYYNTITILITOIMITA") {
						$aputoim1 = "MYYNTITILI";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($whiletoim == "SIIRTOLISTASUPER") {
						$aputoim1 = "SIIRTOLISTA";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($whiletoim == "TARJOUSSUPER") {
						$aputoim1 = "TARJOUS";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($whiletoim == "TYOMAARAYSSUPER") {
						$aputoim1 = "TYOMAARAYS";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($whiletoim == "OSTO" or $whiletoim == "OSTOSUPER") {
						$aputoim1 = "";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif($whiletoim=="PROJEKTI") {
						if($row["tila"] == "A") {
							$aputoim1 = "TYOMAARAYS";
						}
						elseif($row["tila"] == "R") {
							$aputoim1 = "PROJEKTI";
						}
						else {
							$aputoim1 = "RIVISYOTTO";
						}

						$lisa1 = t("Rivisy�tt��n");
					}
					else {
						$aputoim1 = $whiletoim;
						$aputoim2 = "";

						$lisa1 = t("Muokkaa");
						$lisa2 = "";
					}

					// tehd��n alertteja
					if ($row["tila"] == "L" and $row["alatila"] == "A") {
						$pitaako_varmistaa = t("Ker�yslista on jo tulostettu! Oletko varma, ett� haluat viel� muokata tilausta?");
					}

					if ($row["tila"] == "G" and $row["alatila"] == "A") {
						$pitaako_varmistaa = t("Siirtolista on jo tulostettu! Oletko varma, ett� haluat viel� muokata siirtolistaa?");
					}

					$button_disabled = "";

					if (($row["tila"] == "L" or $row["tila"] == "N") and $row["mapvm"] != '0000-00-00' and $row["mapvm"] != '') {
						$button_disabled = "disabled";
					}

					// tehd��n alertti jos sellanen ollaan m��ritelty
					$javalisa = "";
					if ($pitaako_varmistaa != "") {
						echo "	<script language=javascript>
								function lahetys_verify() {
									msg = '$pitaako_varmistaa';
									return confirm(msg);
								}
								</script>";
						$javalisa = "onSubmit = 'return lahetys_verify()'";
					}

					echo "<td class='back' nowrap>";

					if ($whiletoim == "OSTO" or $whiletoim == "OSTOSUPER" or $whiletoim == "HAAMU") {
						echo "<form method='post' action='tilauskasittely/tilaus_osto.php' $javalisa>";
					}
					else {
						echo "<form method='post' action='tilauskasittely/tilaus_myynti.php' $javalisa>";
					}

					//	Projektilla hyp�t��n aina p��otsikolle..
					if ($whiletoim == "PROJEKTI") {
						echo "	<input type='hidden' name='projektilla' value='$row[tunnusnippu]'>";
					}
					echo "	<input type='hidden' name='toim' value='$aputoim1'>
							<input type='hidden' name='tee' value='AKTIVOI'>
							<input type='hidden' name='tilausnumero' value='$row[tunnus]'>";

					if ($whiletoim == "" or $whiletoim == "SUPER" or $whiletoim == "EXTRANET" or $whiletoim == "ENNAKKO" or $whiletoim == "JTTOIMITA" or $whiletoim == "LASKUTUSKIELTO"or (($whiletoim == "VALMISTUSMYYNTI" or $whiletoim == "VALMISTUSMYYNTISUPER") and $row["tila"] != "V")) {
						echo "<input type='submit' name='$aputoim2' value='$lisa2' $button_disabled>";
					}

					echo "<input type='submit' name='$aputoim1' value='$lisa1' $button_disabled>";
					echo "</form></td>";

					if ($whiletoim == "TARJOUS" or $whiletoim == "TARJOUSSUPER" and $kukarow["kesken"] != 0) {
						echo "<td class='back'><form method='post' action='muokkaatilaus.php' onSubmit='return verify();'>";
						echo "<input type='hidden' name='toim' value='$whiletoim'>";
						echo "<input type='hidden' name='tee' value='MITATOI_TARJOUS'>";
						echo "<input type='hidden' name='tilausnumero' value='$row[tunnus]'>";
						echo "<input type='submit' name='$aputoim1' value='".t("Mit�t�i")."'>";
						echo "</form></td>";
					}

					echo "</tr>";
				}
			}

			echo "</table>";


			if (strpos($_SERVER['SCRIPT_NAME'], "muokkaatilaus.php") !== FALSE) {
				if (is_array($sumrow)) {
					echo "<br><table>";
					echo "<tr><th>".t("Arvo yhteens�")." ($sumrow[kpl] ".t("kpl")."): </th><td align='right'>$sumrow[arvo] $yhtiorow[valkoodi]</td></tr>";

					if (isset($sumrow["jt_arvo"]) and $sumrow["jt_arvo"] != 0) {
						echo "<tr><th>".t("Muu tilauskanta").":</th><td align='right'>$sumrow[jt_arvo] $yhtiorow[valkoodi]</td></tr>";


						echo "<tr><th>".t("Yhteens�")."</th><td align='right'>".sprintf('%.2f', $sumrow["jt_arvo"]+$sumrow["arvo"])." $yhtiorow[valkoodi]</td></tr>";

						echo "<tr><td class='back'><br></td></tr>";
					}

					echo "<tr><th>".t("Summa yhteens�").": </th><td align='right'>$sumrow[summa] $yhtiorow[valkoodi]</td></tr>";

					if (isset($sumrow["jt_summa"]) and $sumrow["jt_summa"] != 0) {
						echo "<tr><th>".t("Muu tilauskanta").":</th><td align='right'>$sumrow[jt_summa] $yhtiorow[valkoodi]</td></tr>";

						echo "<tr><th>".t("Yhteens�")."</th><td align='right'>".sprintf('%.2f', $sumrow["jt_summa"]+$sumrow["summa"])." $yhtiorow[valkoodi]</td></tr>";
					}

					echo "</table>";
				}

				if (mysql_num_rows($result) == 50) {
					// N�ytet��n muuten vaan sopivia tilauksia
					echo "<br>
							<form action='$PHP_SELF' method='post'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='etsi' value='$etsi'>
							<input type='hidden' name='asiakastiedot' value='$asiakastiedot'>
							<input type='hidden' name='limit' value='NO'>
							<table>
							<tr><th>".t("Listauksessa n�kyy 50 ensimm�ist�")." $otsikko.</th>
							<td class='back'><input type='Submit' value = '".t("N�yt� kaikki")."'></td></tr>
							</table>
							</form>";
				}

				if (isset($workbook)) {

					// We need to explicitly close the workbook
					$workbook->close();

					echo "<form method='post' action='$PHP_SELF'>";
					echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
					echo "<input type='hidden' name='kaunisnimi' value='Tilauslista.xls'>";
					echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
					echo "<br><table>";
					echo "<tr><th>".t("Tallenna lista").":</th>";
					echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr>";
					echo "</table></form><br>";
				}
			}
		}
		else {
			echo t("Ei tilauksia")."...<br>";
		}

		if (strpos($_SERVER['SCRIPT_NAME'], "muokkaatilaus.php") !== FALSE) {
			require ("inc/footer.inc");
		}
	}
?>