<?php

	$laskuri = 0;

	foreach ($dnslajitelma as $key => $lajitelma) {

		$tiedoston_sijainti = "{$pupe_root_polku}/dataout/dnsassortment_".date("Y_m_d_Hi")."-$laskuri.xml";

		$xmlstr  = '<?xml version="1.0" encoding="iso-8859-1"?>';
		$xmlstr .= '<assortments>';
		$xmlstr .= '</assortments>';

		$xml = new SimpleXMLElement($xmlstr);

		$customer = $xml->addChild('customer');
		$customer->addChild('case','pupesoft');
		$customer->addChild('name', utf8_encode(xmlentities($yhtiorow["nimi"])));
		$customer->addChild('address', utf8_encode(xmlentities($yhtiorow["osoite"])));
		$customer->addChild('timestamp', date("Y-m-d H:i"));

		// HINNASTOJEN PERUSTIEDOT
		$information = $xml->addChild('information');

		// Lajitelma/variaatio
		$product = $information->addChild('product');
		$product->addChild('code', $key);

		// Loopataan lajitelman tiedot
		foreach ($lajitelma as $tiedot) {

			$assortment = $product->addChild('assortment');
				$assortment->addChild('code', utf8_encode(xmlentities($tiedot["tuoteno"])));
				$assortment->addChild('assortmentname', utf8_encode(xmlentities($tiedot["nimitys"])));
				$assortment->addChild('assortmentnameswe', utf8_encode(xmlentities($tiedot["nimi_swe"])));
				$assortment->addChild('assortmentnameeng', utf8_encode(xmlentities($tiedot["nimi_eng"])));
				$assortment->addChild('ean', utf8_encode(xmlentities($tiedot["ean"])));
				$assortment->addChild('assortmentprice',$tiedot["myyntihinta"]);
				$assortment->addChild('kuluttajahinta',$tiedot["myymalahinta"]);
				$properties = $assortment->addChild('properties');

				// Loopataan lajitelman parametrit
				foreach ($tiedot["parametrit"] as $parametrit) {
					$propertytype = $properties->addChild('propertytype');
						$propertytype->addChild('propertytypename', utf8_encode(xmlentities($parametrit["nimi"])));
							$property = $propertytype->addChild('property');
							$property->addChild('propertyname', utf8_encode(xmlentities($parametrit["arvo"])));
				}
		}

		$laskuri++;

		$xml->asXML($tiedoston_sijainti);

		$ftpfile = realpath($tiedoston_sijainti);
		$todnimi = basename($tiedoston_sijainti);

		require ("{$pupe_root_polku}/inc/ftp-send.inc");
	}
