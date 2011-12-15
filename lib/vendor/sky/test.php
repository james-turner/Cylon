<?php

require_once dirname(__FILE__) . '/STB.php';

$stb = new STB('10.80.4.90');
//$stb->DoPause();
//sleep(4);
//$stb->DoPlay();

//$stb->ChannelChange(1402);

$doc = new DOMDocument();
$doc->loadXML($stb->GetMediaInfo());


$xpath = new DOMXPath($doc);

$nodeList = $xpath->query("//CurrentURI");
$channelData = parse_url($nodeList->item(0)->nodeValue);
var_dump(hexdec($channelData['host']));