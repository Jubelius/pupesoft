<?php

$otsikko 		= 'Toimitustavan l�hd�t';
$otsikko_nappi 	= 'toimitusaikojen l�ht�';

$kentat 	= "tunnus, liitostunnus, if(lahdon_viikonpvm = 0,'Sunnuntai',if(lahdon_viikonpvm = 1,'Maanantai',if(lahdon_viikonpvm = 2,'Tiistai',if(lahdon_viikonpvm = 3,'Keskiviikko',if(lahdon_viikonpvm = 4,'Torstai',if(lahdon_viikonpvm = 5,'Perjantai','Lauantai')))))) as 'Viikonp�iv�', lahdon_kellonaika as 'L�hd�n kellonaika', terminaalialue, if(aktiivi='','K�yt�ss�','Ei k�yt�ss�') as 'Status'";
$jarjestys 	= "lahdon_viikonpvm,terminaalialue, lahdon_kellonaika";

?>