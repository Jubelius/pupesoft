<?php

	//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
	$useslave = 1;

	// DataTables p��lle
	$pupe_DataTables = "selaasoppareita";

	require('../inc/parametrit.inc');

	pupe_DataTables(array(array($pupe_DataTables, 14, 14, true, true)));

	$query_ale_lisa = generoi_alekentta('M');

	echo "<font class='head'>".t("Selaa Sopimuksia")."</font><hr>";

	// Tehd��n taulukko
	echo "<table class='display dataTable' id='$pupe_DataTables'>";
	echo "<thead>";
	echo "<tr>";
	echo "<th>".t("Sopimus")."</th>";
	echo "<th>".t("Asiakkaan")."<br>".t("Tilausnumero")."</th>";
	echo "<th>".t("Asiakas")."</th>";
	echo "<th>".t("Tuoteno")."</th>";
	echo "<th>".t("Nimitys")."</th>";
	echo "<th>".t("Kommentti")."</th>";
	echo "<th>".t("Sis�inen")."<br>".t("Kommentti")."</th>";
	echo "<th>".t("Alku pvm")."</th>";
	echo "<th>".t("Loppu pvm")."</th>";
	echo "<th>".t("Kpl")."</th>";
	echo "<th>".t("Hinta")."</th>";
	echo "<th>".t("Rivihinta")."</th>";
	echo "<th>".t("Sarjanumero")."</th>";
	echo "<th>".t("Vasteaika")."</th>";
	echo "</tr>";

	// Hakukent�t
	echo "<tr>";
	echo "<td><input type='text' class='search_field' name='search_tilausnumero'/></td>";
	echo "<td><input type='text' class='search_field' name='search_asiakkaan_tilausnumero'/></td>";
	echo "<td><input type='text' class='search_field' name='search_asiakas'/></td>";
	echo "<td><input type='text' class='search_field' name='search_tuoteno/'></td>";
	echo "<td><input type='text' class='search_field' name='search_nimitys'/></td>";
	echo "<td><input type='text' class='search_field' name='search_kommentti'/></td>";
	echo "<td><input type='text' class='search_field' name='search_siskommentti'/></td>";
	echo "<td><input type='text' class='search_field' name='search_rivinsopimus_alku'/></td>";
	echo "<td><input type='text' class='search_field' name='search_rivinsopimus_loppu'/></td>";
	echo "<td><input type='text' class='search_field' name='search_kpl'/></td>";
	echo "<td><input type='text' class='search_field' name='search_hinta'/></td>";
	echo "<td><input type='text' class='search_field' name='search_summa'/></td>";
	echo "<td><input type='text' class='search_field' name='search_sarjanumero'/></td>";
	echo "<td><input type='text' class='search_field' name='search_vasteaika'/></td>";
	echo "</tr>";
	echo "</thead>";
	echo "<tbody>";

	$query = "	SELECT lasku.tunnus tilaus,
				concat(lasku.ytunnus, '<br>', lasku.nimi) asiakas,
				lasku.asiakkaan_tilausnumero,
				lasku.valkoodi,
				laskun_lisatiedot.sopimus_alkupvm,
				laskun_lisatiedot.sopimus_loppupvm,
				if (tilausrivi.kerayspvm = '0000-00-00', if(laskun_lisatiedot.sopimus_loppupvm = '0000-00-00', '', laskun_lisatiedot.sopimus_loppupvm), tilausrivi.kerayspvm) rivinsopimus_alku,
				if (tilausrivi.toimaika = '0000-00-00', if(laskun_lisatiedot.sopimus_alkupvm = '0000-00-00', '', laskun_lisatiedot.sopimus_loppupvm), tilausrivi.toimaika) rivinsopimus_loppu,
				tilausrivi.nimitys,
				tilausrivi.tuoteno,
				round(tilausrivi.hinta * tilausrivi.varattu * {$query_ale_lisa}, {$yhtiorow["hintapyoristys"]}) rivihinta,
				tilausrivi.varattu,
				tilausrivi.hinta,
				tilausrivi.kommentti,
				tilausrivin_lisatiedot.sopimuksen_lisatieto1 sarjanumero,
				tilausrivin_lisatiedot.sopimuksen_lisatieto2 vasteaika,
				laskun_lisatiedot.sopimus_lisatietoja sisainen_kommentti
				FROM lasku use index (tila_index)
				JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus and (laskun_lisatiedot.sopimus_loppupvm >= now() or laskun_lisatiedot.sopimus_loppupvm = '0000-00-00'))
				JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi = '0')
				JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
				WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
				AND tila = '0'
				AND alatila != 'D'
				ORDER by lasku.tunnus, rivinsopimus_alku ASC, rivinsopimus_loppu ASC";
	$result = pupe_query($query);

	while ($rivit = mysql_fetch_assoc($result)) {
		echo "<tr class='aktiivi'>";
		echo "<td nowrap>{$rivit["tilaus"]}</td>";
		echo "<td>{$rivit["asiakkaan_tilausnumero"]}</td>";
		echo "<td>{$rivit["asiakas"]}</td>";
		echo "<td nowrap>{$rivit["tuoteno"]}</td>";
		echo "<td>{$rivit["nimitys"]}</td>";
		echo "<td>{$rivit["kommentti"]}</td>";
		echo "<td>{$rivit["sisainen_kommentti"]}</td>";
		echo "<td nowrap>{$rivit["rivinsopimus_alku"]}</td>";
		echo "<td nowrap>{$rivit["rivinsopimus_loppu"]}</td>";
		echo "<td nowrap>{$rivit["varattu"]}</td>";
		echo "<td nowrap align='right'>".hintapyoristys($rivit["hinta"])."</td>";
		echo "<td nowrap align='right'>{$rivit["rivihinta"]}</td>";
		echo "<td>{$rivit['sarjanumero']}</td>";
		echo "<td>{$rivit['vasteaika']}</td>";
		echo "</tr>";
	}

	echo "</tbody>";
	echo "</table>";

	require ("inc/footer.inc");