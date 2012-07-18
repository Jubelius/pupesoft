<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

# N�m� on pakollisia
if (!isset($alusta_tunnus, $liitostunnus, $selected_row)) exit;

$alusta_tunnus = (int) $alusta_tunnus;
$liitostunnus = (int) $liitostunnus;
$selected_row = (int) $selected_row;

# Urlin rakennus
$data = array(
	'alusta_tunnus' => $alusta_tunnus,
	'liitostunnus' => $liitostunnus,
	'selected_row' => $selected_row
);
$url = http_build_query($data);

# Virheet
$error = array(
	'vahvista' => ''
);

# Haetaan suuntalavan tuotteet
$res = suuntalavan_tuotteet(array($alusta_tunnus), $liitostunnus, "", "", "", $selected_row);
$row = mysql_fetch_assoc($res);

# Haetaan saapumiset6881
$saapumiset = hae_saapumiset($alusta_tunnus);
echo "<pre>";
var_dump($saapumiset);
echo "</pre>";

# Jos parametrina hylly, eli ollaan muutettu tuotteen ker�yspaikkaa
if(isset($hylly)) {
	$hylly = explode(",", $hylly);
	$row['hyllyalue'] = $hylly[0];
	$row['hyllynro'] = $hylly[1];
	$row['hyllyvali'] = $hylly[2];
	$row['hyllytaso'] = $hylly[3];
}

# Tullaan nappulasta
if (isset($submit) and trim($submit) != '') {

	if ($submit == 'cancel') {
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=suuntalavan_tuotteet.php?{$url}'>";
		exit;
	}
	elseif ($submit == 'new') {
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=uusi_kerayspaikka.php?{$url}'>";
		exit;
	}
	elseif ($submit == 'submit') {

		# Tarkistetaan varmistuskoodi
		if(!empty($koodi) && tarkista_varaston_hyllypaikka($row['hyllyalue'], $row['hyllynro'], $row['hyllyvali'], $row['hyllytaso'], $koodi)) {
			echo "varmistuskoodi ja hylly ok<br>";

			# Hylly array
			$hylly = array(
				"hyllyalue" => $row['hyllyalue'],
				"hyllynro" 	=> $row['hyllynro'],
				"hyllyvali" => $row['hyllyvali'],
				"hyllytaso" => $row['hyllytaso']);

			# Jos m��r�� pienennet��n, niin splitataan ( $maara < $row['varattu'])
			if($maara < $row['varattu']) {
				echo "SPLITATAAN";
				# P�ivitet��n alkuper�isen rivin kpl
				$ok = paivita_tilausrivin_kpl($selected_row, ($row['varattu'] - $maara));

				# Splitataan rivi, $pois_suuntalavalta = false
				$uuden_rivin_id = splittaa_tilausrivi($selected_row, $maara, false, false);

				echo "<br>Uuden rivin id: ".$uuden_rivin_id;

				# Splitattu rivi hyllyyn
				# Kaikki OK
				# Vahvista ker�ys

				$saapuminen = hae_saapumiset($alusta_tunnus);
				echo "vied��n varastoon saapuminen: ".$saapuminen[0];
				echo "<br>{$alusta_tunnus}";
				var_dump($hylly);
				echo "<br>uuden_rivin_id: ".$uuden_rivin_id;

				# Vied��n splitattu rivi varastoon
				vie_varastoon($saapuminen[0], $alusta_tunnus, $hylly, $uuden_rivin_id);

				# Palataan suuntalavan_tuotteet sivulle
				echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=suuntalavan_tuotteet.php?{$url}'>";

			}
			# Jos nostetaan niin tehd��n insertti erotukselle..
			elseif($maara > $row['varattu']) {
				# Herjataan varmistuskysymys
				# alert("Oleteko varma!....")
				echo "Olet tulouttamassa enemm�n kuin rivill� alunperin oli. Oletko varma?";

				# Tehd��n insertti erotukselle
				$kopioitu_tilausrivi = kopioi_tilausrivi($selected_row);
				echo "Kopioitu: ".$kopioitu_tilausrivi;

				# P�ivit� kopioidun kpl (maara - varattu)
				paivita_tilausrivin_kpl($kopioitu_tilausrivi, ($maara - $row['varattu']));

				$saapuminen = hae_saapumiset($alusta_tunnus);

				# Vied��n molemmat rivit hyllyyn
				vie_varastoon($saapuminen[0], $alusta_tunnus, $hylly, $selected_row);
				echo "<br>Vietiin varastoon rivi: ".$selected_row;
				vie_varastoon($saapuminen[0], $alusta_tunnus, $hylly, $kopioitu_tilausrivi);
				echo "<br>Vietiin varastoon rivi: ".$selected_row;
			}
			# M��r�t samat
			else {
				echo "EI SPLITATA";
				# Kaikki OK
				# Vahvista ker�ys

				$saapuminen = hae_saapumiset($alusta_tunnus);

				echo "vied��n varastoon saapuminen: ".$saapuminen[0];
				echo "<br>{$alusta_tunnus}";
				var_dump($hylly);

				# Vied��n varastoon
				vie_varastoon($saapuminen[0], $alusta_tunnus, $hylly, $selected_row);

				# Jos tuotteita j�lell�, menn��jn takaisin suuntalavan tuotteet sivulle
				echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=suuntalavan_tuotteet.php?{$url}'>";

				# Jos oli viimeinen tuote, palataan alusta sivulle
				#
				exit;
			}

		}
		# V��r� varmistuskoodi
		else {
			$error['vahvista'] =  "V��r� varmistuskoodi tai hyllypaikka";
		}
	}
}

echo "
	<style type='text/css'>
	<!--
		A, A:visited	{color: #c0c0c0; text-decoration:none;}
		.error		{color: #ff6666;}
	-->
	</style>

	<table border='0'>
		<tr>
			<td><h1>",t("Vahvista ker�yspaikka", $browkieli),"</h1>
				<form name='vahvistaformi' method='post' action=''>
				<table>
					<tr>
						<td>",t("Tuote", $browkieli),"</td>
						<td colspan='2'>{$row['tuoteno']}</td>
					</tr>
					<tr>
						<td>",t("Toim. Tuotekoodi", $browkieli),"</td>
						<td colspan='2'>{$row['toim_tuoteno']}</td>
					</tr>
					<tr>
						<td>",t("M��r�", $browkieli),"</td>
						<td><input type='text' name='maara' value='' size='7' />
						<td>{$row['varattu']} {$row['yksikko']}</td>
					</tr>
					<tr>
						<td>",t("Ker�yspaikka", $browkieli),"</td>
						<td colspan='2'>{$row['hyllyalue']} {$row['hyllynro']} {$row['hyllyvali']} {$row['hyllytaso']}</td>
					</tr>
					<tr>
						<td>",t("Koodi", $browkieli),"</td>
						<td colspan='2'><input type='text' name='koodi' value='' size='7' />
					</tr>
					<tr>
						<td nowrap>
							<button name='submit' value='submit' onclick='submit();'>",t("Vahvista", $browkieli),"</button>
						</td>
						<td nowrap>
							<button name='submit' value='cancel' onclick='submit();'>",t("Takaisin", $browkieli),"</button>
						</td>
						<td>
							<button name='submit' value='new'>",t("Uusi ker�yspaikka", $browkieli),"</button>
						</td>
					</tr>
					<tr><td>&nbsp;</td></tr>
				</table>
				<span class='error'>{$error['vahvista']}</span>
				<input type='hidden' name='alusta_tunnus' value='{$alusta_tunnus}' />
				<input type='hidden' name='liitostunnus' value='{$liitostunnus}' />
				<input type='hidden' name='selected_row' value='{$selected_row}' />
				</form>
			</td>
		</tr>
	</table>";

echo "<pre>";

require('inc/footer.inc');