<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if (!isset($alusta_tunnus, $liitostunnus, $selected_row)) exit;

$alusta_tunnus = (int) $alusta_tunnus;
$liitostunnus = (int) $liitostunnus;
$selected_row = 0;

$error = array(
	'varalle' => ''
);

if (isset($submit) and trim($submit) != '') {

	if ($submit == 'submit') {
		# Koodi ei saa olla tyhj�!
		if ($koodi != '') {

			# Tarkistetaan hyllypaikka ja varmistuskoodi
			$kaikki_ok = tarkista_varaston_hyllypaikka($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso, $koodi);

			# Jos hyllypaikka ok, laitetaan koko suuntalava varastoon
			if ($kaikki_ok) {

				# Haetaan saapumiset?
				$saapumiset = hae_saapumiset($alusta_tunnus);

				# P�ivitet��n hyllypaikat
				$paivitetyt_rivit = paivita_hyllypaikat($alusta_tunnus, $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso);

				if ($paivitetyt_rivit > 0) {
					# Hylly arrayksi...
					$hylly = array(
						"hyllyalue" => $hyllyalue,
						"hyllynro" => $hyllynro,
						"hyllyvali" => $hyllyvali,
						"hyllytaso" => $hyllytaso);

					# Vied��n varastoon keikka kerrallaan.
					foreach($saapumiset as $saapuminen) {
						# Saako keikan vied� varastoon
						if (saako_vieda_varastoon($saapuminen, 'kalkyyli', 1) == 1) {
							# Ei saa vied� varastoon, skipataan?
							$varastovirhe = true;
							continue;
						} else {
							vie_varastoon($saapuminen, $alusta_tunnus, $hylly);
						}
					}
					# Jos kaikki meni ok
					if (isset($varastovirhe)) {
						$error['varalle'] .= "Virhe varastoonvienniss�";
					} else {
						echo "<META HTTP-EQUIV='Refresh'CONTENT='2;URL=alusta.php'>";
						exit;
					}
				}
				else {
					$error['varalle'] = "Yht��n tuotetta ei l�ytynyt suuntalavalta";
				}
			}
			else {
				$error['varalle']  = "Virheellinen varmistukoodi tai tuotepaikka.";
			}
		}
		else {
			$error['varalle'] = "Varmistukoodi ei voi olla tyhj�";
		}
	}
	# Takaisin
	elseif ($submit == 'cancel') {
		$url = "?alusta_tunnus={$alusta_tunnus}&liitostunnus={$liitostunnus}";

		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=suuntalavan_tuotteet.php{$url}'>";
		exit;
	}
}

# Haetaan SSCC
$sscc_query = mysql_query("	SELECT sscc
							FROM suuntalavat
							WHERE tunnus='{$alusta_tunnus}'
							AND yhtio='{$kukarow['yhtio']}'");
$sscc = mysql_fetch_assoc($sscc_query);

include("kasipaate.css");
echo "
	<table border='0'>
		<tr>
			<td><h1>",t("SUUNTALAVAVARALLE", $browkieli),"</h1>
				<form name='varalleformi' method='post' action=''>
				<table>
					<tr>
						<td>",t("Suuntalava", $browkieli),"</td>
						<td colspan='3'>{$sscc['sscc']}</td>
					</tr>
					<tr>
						<td>",t("Alue", $browkieli),"</td>
						<td><input type='text' name='hyllyalue' value='{$hyllyalue}' /></td>
					</tr>
					<tr>
						<td>",t("Nro", $browkieli),"</td>
						<td><input type='text' name='hyllynro' value='{$hyllynro}' /></td>
					<tr>
						<td>",t("V�li", $browkieli),"</td>
						<td><input type='text' name='hyllyvali' value='{$hyllyvali}' /></td>
					<tr>
						<td>",t("Taso", $browkieli),"</td>
						<td><input type='text' name='hyllytaso' value='{$hyllytaso}' /></td>
					</tr>
					<tr>
						<td>",t("Koodi", $browkieli),"</td>
						<td colspan='2'><input type='text' name='koodi' value='{$koodi}' size='7' />
					</tr>
					<tr>
						<td nowrap>
							<button name='submit' value='submit' onclick='submit();'>",t("OK", $browkieli),"</button>
						</td>
						<td nowrap>
							<button name='submit' value='cancel' onclick='submit();'>",t("Takaisin", $browkieli),"</button>
						</td>
					</tr>
					<tr><td>&nbsp;</td></tr>
				</table>
				<span class='error'>{$error['varalle']}</span>
				<input type='hidden' name='alusta_tunnus' value='{$alusta_tunnus}' />
				<input type='hidden' name='liitostunnus' value='{$liitostunnus}' />
				<input type='hidden' name='selected_row' value='{$selected_row}' />
				</form>
			</td>
		</tr>
	</table>";

#require('inc/footer.inc');