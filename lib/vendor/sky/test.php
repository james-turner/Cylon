<?php

require_once dirname(__FILE__) . '/STB.php';

$stb = new STB('10.80.4.90');
$stb->DoPause();
sleep(4);
$stb->DoPlay();

$stb->

