<?php

require_once("clsWAV.php");
echo("Starting tests...\n");

$wav = new \phpaudio\WAV('/var/projects/mp3/wav/data/Mulaw.wav');
if ($wav->getError() != null && $wav->getError() != "") 
	echo($wav->getError() . "\n");
else 
	var_dump($wav->getHeaderData());



echo("Finished tests..\n");
?>
