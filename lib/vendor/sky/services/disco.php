<?php

error_reporting(E_ERROR);

declare(ticks=1);
pcntl_signal(SIGALRM, "sig_alarm");


define('PROCESS_COUNT', '1');
$children = array();


if ($argc == 1) {
    $hostname    = '192.168.0.11';  // Default ip address if none passed
    $listener_ip = '192.168.0.11';  // Default ip address if none passed
    }
else {
    $hostname    = $argv[1];
    $listener_ip = $argv[2];
}

$listener_port = '49999';  // This is an arbitrary port number: you can use what you like.

$arrBoxes	= array();

pcntl_alarm(0);

for($i = 0; $i < PROCESS_COUNT; $i++) {
  if(($pid = pcntl_fork()) == 0) {
    exit(child_main());
  }
  else {
    $children[] = $pid;
  }
}

while($children) {

	pcntl_alarm(4);   // Set timeout period for alarm that will end the discovery waiting period

	$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
	socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

	socket_bind($socket, $GLOBALS["listener_ip"], $GLOBALS["listener_port"]);

	ini_set("user_agent","SKY");

	$from = '';
	$port = 0;
	$timeout = 1;

	usleep(700);  // Wait for a while - some boxes seem to need time to recover from responding to the search before being able to respond again

	while (true) {
		socket_recvfrom($socket, $response, 1024, 0, $from, $port);

		if (strripos($response, 'X-User-Agent: redsonic') > 0) {

			// Extract the ip address
			preg_match('/(\d{1,3}(\.\d{1,3}){3})/', $response, $matches);
			$stbipaddr = $matches[0];  // The match is in the first element of the array

			$urlstart = strripos($response, 'LOCATION:')+10;
			$urlend = strripos($response, '.xml')+4;

			$descurl = substr($response, $urlstart, $urlend-$urlstart);  // Get the description url
	
/*
*			At this point we need to read the description xml file of the STB that has just responded to us.
*			However, the simplexml_load_file function does not handle timeouts and will hang for a long time
*			if UPnP is not responding on the STB. Note that this can happen even though the same box is responding
*			to discovery requests.
*			In order to avoid this we need to check that the STB is responding to requests for the description
*			document's url before we invoke simplexml_load_file. This is done firstly by checking that fopen does
*			not generate an error and secondly that the socket does not show a timed out status. If both these tests are
*			passed then we carry on the retrieve the decription xml document, otherwise we ignore this STB and move on.
*/

			ini_set('default_socket_timeout', $timeout);
			if ($fp = fopen($descurl, 'r')) {

				stream_set_timeout($fp, $timeout);
				stream_set_blocking($fp, 0);
				$info = socket_get_status($fp); 

				if (!$info['timed_out']) { 

					fclose($fp);  // If we can open the url, close the socket and open it again as xml

					$xml = simplexml_load_file($descurl);

					$friendlyName =  $xml->device->friendlyName[0];
					$deviceType = $xml->device->deviceType[0];
					
					$devUDN = $xml->device->UDN[0];
					$manufacturer = $xml->device->manufacturer[0];
					$model = $xml->device->modelDescription[0];
					$plainmodel = $model;

					$decModel = hexdec($model);

					switch ($decModel) {
						case (($decModel >= hexdec('4F3000')) && ($decModel <= hexdec('4F30FF'))):
							$model .= " - DRX780, 320GB disc (140/174)";
							break;
						case (($decModel >= hexdec('4F3100')) && ($decModel <= hexdec('4F314F'))):
							$model .= " - DRX890, 500GB disc (245/245)";
							break;
						case (($decModel >= hexdec('4F31A0')) && ($decModel <= hexdec('4F31FF'))):
							$model .= " - DRX890, 500GB disc (245/245)";
							break;
						case (($decModel >= hexdec('4F3150')) && ($decModel <= hexdec('4F315F'))):
							$model .= " - DRX895, 1500GB disc (400/1092)";
							break;
						case (($decModel >= hexdec('4F3160')) && ($decModel <= hexdec('4F319F'))):
							$model .= " - DRX895, 2000GB disc (896/1093)";
							break;
						case (($decModel >= hexdec('973000')) && ($decModel <= hexdec('973004'))):
							$model .= " - Samsung HD, 300GB disc (140/154)";
							break;
						case (($decModel >= hexdec('973005')) && ($decModel <= hexdec('9730AF'))):
							$model .= " - Samsung HD, 320GB disc (140/174)";
							break;
						case (($decModel >= hexdec('9730B0')) && ($decModel <= hexdec('9730FF'))):
							$model .= " - Samsung HD, 500GB disc (245/245)";
							break;
						case (($decModel >= hexdec('9F3000')) && ($decModel <= hexdec('9F3003'))):
							$model .= " - Pace HD, 300GB disc (140/154)";
							break;
						case (($decModel >= hexdec('9F3004')) && ($decModel <= hexdec('9F30FF'))):
							$model .= " - Pace HD, 320GB disc (140/174)";
							break;
						default: $model .= " - Model not recognised"; break;
					}

					$modelname = $xml->device->modelName[0];

					$order = array("\r\n", "\n", "\r", "#");
					$modelnumber = str_replace($order, ' ', $xml->device->modelNumber[0]);  // Deal with possible LF and CR characters in the string. Seen on some test builds.

					$URLBase = $xml->URLBase[0];
					//Build name-value pair string
					echo 'Friendly_Name='. $friendlyName . '|'
					. 'Manufacturer='. $manufacturer .'|'
					. 'Description='. $model .'|'
					. 'Name='. $modelname .'|'
					. 'Number='. $modelnumber .'|'
					. 'UDN='. $devUDN .'|'
					. 'BaseURL='. $URLBase .'|'
					. 'DeviceType='. $deviceType .'|'
					. 'DescURL='. trim($descurl) . '|';

					if (preg_match("/Sky/", $deviceType)) {
						$occluded = "oui";
						}
					else {
						$occluded = "non";
						}

					echo "\n";
				}  // If not timed_out
			}  // If fopen
		}  // If UA
	}  // While true
}  // While children
 
pcntl_alarm(0);

function child_main()
{
	$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
	socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);

	socket_bind($sock,$GLOBALS["listener_ip"],$GLOBALS["listener_port"]);

	$sock_data = socket_connect($sock, '239.255.255.250', 1900);

	$msg = "M-SEARCH * HTTP/1.1\r\nHOST: 239.255.255.250:1900\r\nST: upnp:rootdevice\r\nMAN: \"ssdp:discover\"\r\nMX: 3\r\n\r\n";
	$sock_data = socket_write($sock, $msg, strlen($msg)); //Send data

  return  1;
}


function sig_alarm($signal)
{
  global $children;

  foreach ($children as $pid) {
    posix_kill($pid, SIGINT);
  }
  exit;
}


?>