<?php

	require ("../inc/parametrit.inc");

	enable_jquery();

	echo "	<script type='text/javascript' src='https://www.google.com/jsapi'></script>";
	echo "	<script type='text/javascript' language='JavaScript'>
				<!--

				google.load('visualization', '1', {packages:['corechart']});

				google.setOnLoadCallback(drawChart);

				function drawChart() {
					var data = new google.visualization.DataTable();

					data.addColumn('string', 'Klo');
					data.addColumn('number', 'Kerätty');
					data.addColumn('number', 'Keräyksessä');
					data.addColumn('number', 'Aloittamatta');
					data.addColumn('number', 'Siirretty');

					data.addRows([
						['08.00', 36, 0, 0, 0],
						['09.00', 119, 0, 0, 0],
						['10.00', 122, 0, 0, 0],
						['11.00', 53, 0, 0, 0],
						['12.00', 295, 0, 0, 0],
						['13.00', 31, 0, 0, 0],
						['14.00', 160, 0, 0, 0],
						['15.00', 7, 0, 0, 0],
						['16.00', 617, 190, 0, 30],
						['17.00', 289, 95, 6, 0],
						['18.00', 455, 118, 109, 0],
						['19.00', 398, 194, 299, 0]
					]);

					var options = {
						width: 800, height: 320,
						title: 'Keräilyn kuormitus lähdön ajan mukaan',
						hAxis: {title: 'Klo', titleTextStyle: {color: 'black'}},
						vAxis: {title: 'Rivit', titleTextStyle: {color: 'black'}},
						isStacked: true,
						backgroundColor: '#DDD',
						tooltip: {
							showColorCode: true
						},
						series: {
							0:{
								color: 'green', 
								visibleInLegend: true
							}, 
							1:{
								color: 'blue', 
								visibleInLegend: true
							}, 
							2:{
								color: 'red', 
								visibleInLegend: true
							}, 
							3:{
								color: '#FE0', 
								visibleInLegend: true
							}
						}
					};

					var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
					chart.draw(data, options);
				}

				$(document).ready(function() {

					$('th.keraysvyohyke').click(function() {
						var id = $(this).attr('id');

						if ($('tr[class^=\"asiakas_'+id+'\"]').is(':visible') === false && $('tr[class^=\"rivit_'+id+'\"]').is(':visible') === false) {
							$('tr.era_'+id).toggle();
						}
					});

					$('td.erat').click(function() {
						var id = this.id.split(\"_\");

						if ($('tr[class^=\"rivit_'+id[1]+'_'+id[2]+'\"]').is(':visible') === false) {
							$(this).toggleClass('tumma');
							$('tr.asiakas_'+id[1]+'_'+id[2]).toggle();
						}
					});

					$('td.asiakas').click(function() {
						var id = this.id.split(\"_\");

						if ($('tr.rivit_'+id[1]+'_'+id[2]+'_'+id[3]).length > 0) {
							$(this).toggleClass('tumma');
							$('tr.rivit_'+id[1]+'_'+id[2]+'_'+id[3]).toggle();
						}
					});
				});

				//-->
			</script>";	

	echo "<font class='head'>",t("Keräysvyöhykekuormitus"),"</font><hr>";

	echo "<table>";
	echo "<tr>";
	echo "<th>",t("Varasto"),"</th>";
	echo "<th>",t("Keräysvyöhyke"),"</th>";
	echo "<th>",t("Kuljetusliike"),"</th>";
	echo "<th>",t("Prioriteetti"),"</th>";
	echo "<th>",t("Tila"),"</th>";
	echo "<th>",t("Volyymisuure"),"</th>";
	echo "</tr>";

	echo "<tr>";

	echo "<td>";
	echo "<input type='checkbox' checked/> Unikko<br />";
	echo "<input type='checkbox' /> Veikkola";
	echo "</td>";

	echo "<td>";
	echo "<input type='checkbox' checked /> Pientavara<br />";
	echo "<input type='checkbox' checked /> Kardex<br />";
	echo "<input type='checkbox' checked /> Kone<br />";
	echo "<input type='checkbox' checked /> Painavat";
	echo "</td>";

	echo "<td>";
	echo "<input type='checkbox' checked /> Posten logistik<br />";
	echo "<input type='checkbox' /> Kaukokiito<br />";
	echo "<input type='checkbox' /> Schenker<br />";
	echo "<input type='checkbox' /> Transpori<br />";
	echo "</td>";

	echo "<td>";
	echo "<input type='checkbox' checked /> 5<br />";
	echo "<input type='checkbox' checked /> 7<br />";
	echo "<input type='checkbox' checked /> 9";
	echo "</td>";

	echo "<td>";
	echo "<input type='checkbox' checked /> Aloittamatta<br />";
	echo "<input type='checkbox' /> Keräyksessä<br />";
	echo "<input type='checkbox' /> Kerätty";
	echo "</td>";

	echo "<td>";
	echo "<input type='radio' name='radio[]' checked /> Rivit<br />";
	echo "<input type='radio' name='radio[]' /> Kg<br />";
	echo "<input type='radio' name='radio[]' /> Litrat<br />";
	echo "</td>";

	echo "</tr>";
	echo "</table>";

	echo "<br /><br />";

	echo "<div id='chart_div'></div>";

	echo "<br /><br />";

	$query = "	SELECT keraysvyohyke.nimitys AS 'ker_nimitys',
				GROUP_CONCAT(DISTINCT lasku.tunnus) AS 'tilaukset',
				COUNT(DISTINCT lasku.tunnus) AS 'tilatut',
				COUNT(DISTINCT tilausrivi.tunnus) AS 'suunnittelussa',
				SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', 1, 0)) AS 'keratyt',
				ROUND(SUM(tilausrivi.varattu * tuote.tuotemassa), 0) AS 'kg_suun',
				ROUND(SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', tilausrivi.varattu * tuote.tuotemassa, 0)), 0) AS 'kg_ker',
				ROUND(SUM(tilausrivi.varattu * (tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys * 1000)), 0) AS 'litrat_suun',
				ROUND(SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', (tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys * 1000), 0)), 0) AS 'litrat_ker',
				ROUND((COUNT(DISTINCT lasku.tunnus) * keraysvyohyke.tilauksen_tyoaikavakio_min_per_tilaus + COUNT(DISTINCT tilausrivi.tunnus) * keraysvyohyke.kerailyrivin_tyoaikavakio_min_per_rivi) / 60, 1) AS 'kapasiteettitarve'
				FROM lasku
				JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D')
				JOIN varaston_hyllypaikat vh ON (vh.yhtio = tilausrivi.yhtio AND vh.hyllyalue = tilausrivi.hyllyalue AND vh.hyllynro = tilausrivi.hyllynro AND vh.hyllyvali = tilausrivi.hyllyvali AND vh.hyllytaso = tilausrivi.hyllytaso)
				JOIN keraysvyohyke ON (keraysvyohyke.yhtio = vh.yhtio AND keraysvyohyke.tunnus = vh.keraysvyohyke)
				JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
				JOIN lahdot ON (lahdot.yhtio = lasku.yhtio AND lahdot.tunnus = lasku.toimitustavan_lahto AND lahdot.aktiivi IN ('', 'P'))
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND ((lasku.tila = 'N' AND lasku.alatila = 'A') OR (lasku.tila = 'L' AND lasku.alatila IN ('A','B','C')))
				GROUP BY keraysvyohyke.nimitys
				ORDER BY 1";
	$result = pupe_query($query);

	echo "<table>";
	echo "<tr>";
	echo "<th>",t("Keräysvyöhyke"),"</th>";
	echo "<th>",t("Tilaukset"),"</th>";
	echo "<th>",t("Rivit"),"</th>";
	echo "<th>",t("Kilot"),"</th>";
	echo "<th>",t("Litrat"),"</th>";
	echo "<th>",t("Keräyserän aloitusaika"),"</th>";
	echo "<th>",t("Keräilykapasiteettitarve"),"</th>";
	echo "</tr>";

	$i = 1;
	$max_i = mysql_num_rows($result);

	while ($row = mysql_fetch_assoc($result)) {
		echo "<tr>";
		echo "<th class='keraysvyohyke' id='{$i}'>{$row['ker_nimitys']}</th>";
		echo "<td>";

		$query = "	SELECT SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', 1, 0)) AS 'keratyt'
					FROM lasku 
					JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D')
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					AND lasku.tunnus IN ({$row['tilaukset']})";
		$chk_res = pupe_query($query);

		$chk = 0;

		while ($chk_row = mysql_fetch_assoc($chk_res)) {
			if ($chk_row['keratyt'] != 0) {
				$chk++;
			}
		}

		echo "{$chk} / {$row['tilatut']}";
		echo "</td>";

		echo "<td>{$row['keratyt']} / {$row['suunnittelussa']}</td>";
		echo "<td>{$row['kg_ker']} / {$row['kg_suun']}</td>";

		echo "<td>{$row['litrat_ker']} / {$row['litrat_suun']}</td>";
		echo "<td></td>";
		echo "<td>{$row['kapasiteettitarve']} h</td>";
		echo "</tr>";

		$query = "	SELECT kuka.nimi AS 'keraaja', 
					GROUP_CONCAT(kerayserat.otunnus) AS 'otunnukset',
					MIN(SUBSTRING(kerayserat.luontiaika, 12, 5)) AS 'aloitusaika',
					ROUND(SUM(tilausrivi.varattu * tuote.tuotemassa), 0) AS 'kg',
					COUNT(DISTINCT kerayserat.tilausrivi) AS 'rivit',
					COUNT(DISTINCT kerayserat.otunnus) AS 'tilaukset'
					FROM kerayserat
					JOIN kuka ON (kuka.yhtio = kerayserat.yhtio AND kuka.kuka = kerayserat.laatija)
					JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi AND tilausrivi.tyyppi != 'D')
					JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
					WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
					AND kerayserat.otunnus IN ({$row['tilaukset']})
					GROUP BY 1
					ORDER BY 1";
		$era_res = pupe_query($query);

		if (mysql_num_rows($era_res)> 0) {

			$x = 1;

			$max_x = mysql_num_rows($era_res);

			while ($era_row = mysql_fetch_assoc($era_res)) {
				echo "<tr class='era_{$i}' style='display:none;'>";
				echo "<td class='erat' id='erat_{$i}_{$x}'>{$era_row['keraaja']}</td>";
				echo "<td>{$era_row['tilaukset']}</td>";
				echo "<td>{$era_row['rivit']}</td>";
				echo "<td>{$era_row['kg']}</td>";
				echo "<td></td>";
				echo "<td>{$era_row['aloitusaika']}</td>";
				echo "<td></td>";
				echo "</tr>";

				echo "<tr class='asiakas_{$i}_{$x}' style='display:none;'>";
				echo "<th>",t("Tila"),"</th>";
				echo "<th>",t("Prio"),"</th>";
				echo "<th colspan='2'>",t("Toimitusasiakas"),"</th>";
				echo "<th>",t("Lähtö"),"</th>";
				echo "<th>",t("Toimitustapa"),"</th>";
				echo "<th></th>";
				echo "</tr>";

				$query = "	SELECT lasku.prioriteettinro,
							CONCAT(lasku.nimi, ' ', lasku.nimitark) AS 'nimi',
							lasku.toimitustavan_lahto,
							lasku.toimitustapa,
							lasku.tunnus,
							lasku.tila, lasku.alatila
							FROM lasku
							WHERE lasku.yhtio = '{$kukarow['yhtio']}'
							AND lasku.tunnus IN ({$era_row['otunnukset']})
							AND lasku.tila = 'L'
							AND lasku.alatila IN ('A', 'B', 'C')
							ORDER BY 1,2,3";
				$asiakas_res = pupe_query($query);

				$y = 1;
				$max_y = mysql_num_rows($asiakas_res);

				while ($asiakas_row = mysql_fetch_assoc($asiakas_res)) {
					echo "<tr class='asiakas_{$i}_{$x}' style='display:none;'>";

					echo "<td>";

					if ($asiakas_row['tila'] == 'L' and $asiakas_row['alatila'] == 'A') {
						echo t("Aloitettu");
					}
					else {
						echo t("Kerätty");
					}

					echo "</td>";

					echo "<td>{$asiakas_row['prioriteettinro']}</td>";
					echo "<td colspan='2' class='asiakas' id='asiakas_{$i}_{$x}_{$y}'>{$asiakas_row['nimi']}</td>";
					echo "<td>{$asiakas_row['toimitustavan_lahto']}</td>";
					echo "<td>{$asiakas_row['toimitustapa']}</td>";
					echo "<td></td>";
					echo "</tr>";

					$query = "	SELECT tilausrivi.tuoteno, 
								tuote.nimitys, 
								CONCAT(tilausrivi.hyllyalue, '-', tilausrivi.hyllynro, '-', tilausrivi.hyllyvali, '-', tilausrivi.hyllytaso) AS 'kerayspaikka',
								ROUND(SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', tilausrivi.varattu, 0)), 0) AS 'keratty',
								ROUND(SUM(tilausrivi.varattu), 0) AS 'tilattu'
								FROM kerayserat
								JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi AND tilausrivi.tyyppi != 'D')
								JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
								WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
								AND kerayserat.otunnus = '{$asiakas_row['tunnus']}'
								GROUP BY 1,2,3";
					$rivi_res = pupe_query($query);

					if (mysql_num_rows($rivi_res) > 0) {
						echo "<tr class='rivit_{$i}_{$x}_{$y}' style='display:none;'>";
						echo "<th>",t("Tuotenro"),"</th>";
						echo "<th>",t("Tuotekuvaus"),"</th>";
						echo "<th>",t("Keräyspaikka"),"</th>";
						echo "<th>",t("Kerätty / Tilattu"),"</th>";
						echo "<th></th>";
						echo "<th></th>";
						echo "<th></th>";
						echo "</tr>";

						while ($rivi_row = mysql_fetch_assoc($rivi_res)) {
							echo "<tr class='rivit_{$i}_{$x}_{$y}' style='display:none;'>";
							echo "<td>{$rivi_row['tuoteno']}</td>";
							echo "<td>{$rivi_row['nimitys']}</td>";
							echo "<td>{$rivi_row['kerayspaikka']}</td>";
							echo "<td>{$rivi_row['keratty']} / {$rivi_row['tilattu']}</td>";
							echo "<td></td>";
							echo "<td></td>";
							echo "<td></td>";
							echo "</tr>";
						}						

						if ($y != $max_y) {
							echo "<tr class='rivit_{$i}_{$x}_{$y}' style='display:none;'>";
							echo "<td colspan='5' class='back'>&nbsp;</td>";
							echo "</tr>";
						}
					}

					$y++;
				}

				if ($i == $max_i) {
					echo "<tr class='asiakas_{$i}_{$x}' style='display:none;'>";
					echo "<td colspan='5' class='back'>&nbsp;</td>";
					echo "</tr>";
				}

				$x++;
			}

			echo "<tr class='era_{$i}' style='display:none;'>";
			echo "<td colspan='5' class='back'>&nbsp;</td>";
			echo "</tr>";
		}

		$i++;
	}

	echo "</table>";

	require ("inc/footer.inc");
