<?php
/**
*
* UPnP STB discovery
*
* These functions are used to retrieve information about specific Sky STB based on IP Address.
*
* @author Alasdair Arthur - modified for OpenDay by NJW
* @version Beta 1.0
* @package discovery.php
*/

/**
 *
 * Perform UPnP search/discovery routines.
 *
 * This function calls a standalone disco.php file that then runs UDP broadcasts to find any compatible UPnP
 * devices on the local network. The functionality in the disco.php relies on PHP pcntl functions which are not
 * available outside of Unix/Linux environments.
 *
 * Consequently, the discovery functionality is hidden.
 *
 * @access Private
 * @return String
 */
function searchUPnP(){

    $hostip   = $_SERVER["SERVER_ADDR"];
    $thisfile = $_SERVER["SCRIPT_FILENAME"];
    $hostname = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : '';

    //$filedir = substr($thisfile,0,strripos($thisfile,'discover'));  // Get the current directory name
    $filedir= '/var/www/upnp/services/';

    exec('/usr/bin/php ' . $filedir . 'disco.php ' . $hostname . ' ' . $hostip, $out);
    // The output from the discovery script is returned in the $out array

    return _processBox($out);

}

/**
 *
 * Process Box
 *
 * The function takes in information retrieved by the UPnP discovery process outlined above and converts it into a
 * json packet containing information about every identified UPnP device on the network.
 *
 * @access Private
 * @param Array $arrOut Required. Information about all the UPnP devices found by discovery routines.
 * @return String
 */
function _processBox($arrOut) {

    $arrBoxes	= array();
    $arrRaw		= array();

    //Get raw data into suitable groups
    foreach ($arrOut as $id=>$entry) {
        $arrValues = explode('|',trim($entry));

        $arrRaw[$id] = array();

        foreach($arrValues as $vp) {
            $arrVP = explode('=', trim($vp));
            $arrRaw[$id][] = $arrVP;
        }
    }

    //Now get data in managable form... time to build useful info
    foreach($arrRaw as $key=>$arrService) {

        $arrNewService = array();

        foreach($arrService as $strData){
            $arrNewService[$strData[0]] = $strData[1];
        }

        if(! array_key_exists($arrNewService['Friendly_Name'], $arrBoxes)) {
            //Create array for basic box info
            $arrBox 					= array();

            $arrBox['Friendly_Name'] 	= $arrNewService['Friendly_Name'];
            $arrBox['Manufacturer'] 	= $arrNewService['Manufacturer'];
            $arrBox['Description'] 		= $arrNewService['Description'];
            $arrBox['Name'] 			= $arrNewService['Name'];
            $arrBox['Number'] 			= $arrNewService['Number'];
            $arrBox['UDN'] 				= $arrNewService['UDN'];
            $arrBox['BaseURL'] 			= $arrNewService['BaseURL'];

            $arrBox['Types']			= array();		//Holds services data

            //Create top level box info
            $arrBoxes[$arrNewService['Friendly_Name']] = $arrBox;
        }

        $arrDT 		= explode(":", $arrNewService['DeviceType']);
        $intPosn	= (count($arrDT) - 2);
        $strDeviceType 	= $arrDT[$intPosn];

        $arrType = array();
        $arrType['DeviceType'] 	= $arrNewService['DeviceType'];
        $arrType['DescURL'] 	= $arrNewService['DescURL'];

        $arrBoxes[$arrNewService['Friendly_Name']]['Types'][$strDeviceType] = $arrType;

    }

    return json_encode($arrBoxes);
}

/**
 *
 * Get UPnP Description
 *
 * @param String $intIPAddress Required. IPAddress (IPv4 fornat) of the UPnP device.
 * @return String.
 */
function getUPnPDescriptions($intIPAddress){
    $hostname 	= isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : '';
    $port 		= 49153;
    $timeout 	= 5;
    ini_set("user_agent","SKY");  // Set the user agent string

    // If a fixed ip address has been set for the session then use that and skip the discovery process
    $targetip = trim($intIPAddress);

    error_reporting(E_ERROR);

    $arrPages = array();
    $arrResult = array();

    // Skipping the discovery process means we don't know the name of the description.xml page, so try some options
    if ($fp = fsockopen($targetip, $port, $errno, $errstr, $timeout)) {  // Test that we can see the box, using a reasonably low timeout to speed the response
        fclose($fp);  // If we can see the box, close the socket and carry on to look for the descripton document
        $descurl = "http://" . $targetip . ":" . $port . "/description.xml";
        if ($xml = simplexml_load_file($descurl)) {
            $arrPages[] = $descurl;
        }
        else {
            for ($descnum = 0; $descnum <= 20; $descnum++) {
                $descurl = "http://" . $targetip . ":" . $port . "/description" . $descnum . ".xml";

                if ($xml = simplexml_load_file($descurl)) {
                    $arrPages[] = $descurl;
                }
            }
        }

        if (count($arrPages) == 0) {
            echo "<h3>Error: Cannot open description document at $targetip</h3>Tried description.xml to description20.xml<br />";
            exit;
        }
        else {

            foreach ($arrPages as $source) {
                $xml = simplexml_load_file($source);

                $friendlyName =  $xml->device->friendlyName[0];
                $deviceType = $xml->device->deviceType[0];

                $devUDN = $xml->device->UDN[0];
                $manufacturer = $xml->device->manufacturer[0];
                $model = $xml->device->modelDescription[0];
                $modelname = $xml->device->modelName[0];
                $modelnumber = $xml->device->modelNumber[0];
                $URLBase = $xml->URLBase[0];

                $arrResult[] 	= 'Friendly_Name='. $friendlyName . '|'
                                . 'Manufacturer='. $manufacturer .'|'
                                . 'Description='. $model .'|'
                                . 'Name='. $modelname .'|'
                                . 'Number='. $modelnumber .'|'
                                . 'UDN='. $devUDN .'|'
                                . 'BaseURL='. $URLBase .'|'
                                . 'DeviceType='. $deviceType .'|'
                                . 'DescURL='. trim($source) . '|';

            }
        }
    }
    else {
        echo "<h3>Error: Cannot connect to $targetip</h3>Error code: $errno - $errstr<br />";
        //exit;
        throw new Exception();
    }

    return _processBox($arrResult);

}

/**
 *
 * Retrieves the specified XML description file from a UPnP device.
 *
 * @param String $SCPDURL Required. The URL of the service description file including ports.
 * @return String
 */
function retrieveServiceDescription($SCPDURL){

    $strURL = trim($SCPDURL);

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_USERAGENT, 	'SKY');
    curl_setopt($ch, CURLOPT_URL,            $strURL );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);

    $rtn = curl_exec($ch);

    if(($rtn) === false) {
        $err = 'Curl error: ' . curl_error($ch);
        die($err);
    }

    curl_close($ch);

    return simplexml_load_string($rtn);

}