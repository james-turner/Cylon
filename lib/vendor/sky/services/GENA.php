<?php
/**
* GENA Subscription
*
* Allows clients to subscribe & unsubscribe to either the SkyPlay or SkyBook services.
* These functions <b>DO NOT</b> provide capabilities to receive notifications, only request that they are started/stopped
* on specific ports.
*
* @author Alasdair Arthur - modified for OpenDay by NJW
* @version Beta 1.0
* @package GENA.php
*
*
*/

/**
 *
 * Initiates a subscription to a specified service in the STB. Now hidden by subSetup function.
 * @param String $host Required. The IP Address of the STB.
 * @param Integer $hostport Required. The port of the STB.
 * @param String $serviceType Required. The name of the service being subscribed to. SkyPlay or SkyBook.
 * @param String $controlURL Required. UPnP defined control URL for the service.
 * @param String $listener Required. The IP address of the client requesting the subscription.
 * @param Integer $listport Required. The port the client will be listening on.
 * @param String $uuid Required. The unique ID of the STB.
 * @param Integer $timeout Required. The time in seconds the subscription will last.
 * @param Integer $debug Debug level.
 * @return String The subscription ID.
 * @access Private
 * @author Alasdair Arthur - modified for OpenDay by NJW
 */
function _subSetUp($host, $hostport, $serviceType, $controlURL, $listener, $listport, $uuid, $timeout, $debug=0) {
    $strSID = "uuid:";
    $method = "SUBSCRIBE";

    if ($debug > 1) echo "\n>>>> SUBSCRIBE ===========\nhost: $host\nport: $hostport\nlistener: $listener\nlistport: $listport\nuuid: $uuid\ntimeout: $timeout\n===========\n";
    $buf = '';

    $fp = fsockopen($host, $hostport) or die("Unable to open socket");

    $strSubdata = "$method $controlURL HTTP/1.1\r\n";
    $strSubdata .= "NT: upnp:event\r\n";
    $strSubdata .= "HOST: " . $host . ":" . $hostport ."\r\n";
    $strSubdata .= "CALLBACK: <http://" . $listener . ":" . $listport . "/" . $uuid . "/" . $serviceType . ">\r\n";
    $strSubdata .= "TIMEOUT: Second-" . $timeout . "\r\n";
    $strSubdata .= "Content-length: 0\r\n\r\n";

    fputs($fp, $strSubdata);

    if ($debug > 0)  {echo "SUBSCRIBE request sent:\n$strSubdata\n\n";}


    while (!feof($fp))
    $buf .= fgets($fp,128);

    fclose($fp);

    if ($debug > 1) echo "Initial SUBSCRIBE response:\n\n$buf\n\n";

    if (preg_match("/([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}/", $buf, $matches)) {
        $strSID .= $matches[0];
        if ($debug > 0) echo "\nSUBSCRIBE successful: SID = $strSID\n\n";
        }
    else {
        $strSID = "Failed";
        echo "\nError: SUBSCRIBE failed. Server response follows:\n\n$buf\n";
        }

    return($strSID);
}

/**
 *
 * Close the updates subscription with the STB
 * @param String $Host Required. The IP Address of the STB.
 * @param Integer $HostPort Required. The port of the STB.
 * @param String $ControlURL Required. UPnP defined control URL for the service.
 * @param String $SID Required. The ID of the subscription to be cancelled.
 * @param Integer $Debug Debug level. Defaults to 0 - No debug output.
 * @return Void
 * @access Private
 * @author Alasdair Arthur - modified for OpenDay by NJW
 */
function _subKill($Host, $Hostport, $ControlURL, $SID, $Debug=0) {

    $method = "UNSUBSCRIBE";

    if ($Debug > 1) echo "\n>>>> UNSUBSCRIBE ===========\nSID: $SID\nhost: $Host\nport: $Hostport\n===========\n";
    $buf = '';

    $fp = fsockopen($Host, $Hostport) or die("Unable to open socket");

    fputs($fp, "$method $ControlURL HTTP/1.1\r\n");
    fputs($fp, "SID: " . $SID . "\r\n");
    fputs($fp, "HOST: " . $Host . ":" . $Hostport ."\r\n");
    fputs($fp, "Content-length: 0\r\n");
    fputs($fp, "Connection: close\r\n\r\n");

    while (!feof($fp))
    $buf .= fgets($fp,128);

    if ($Debug > 1) echo "Closing subscription. Unsubscribe response:\n" . $buf;

    fclose($fp);

    return;
}

/**
 *
 * Parse the xml document listing container and update id numbers and return the csv string
 * @param String $updateXML Required. This is the XML supplied by the subscription notice.
 * @param Integer $debug Debug level. Defaults to 0 - No debug output.
 * @return Void
 * @access Private
 * @author Alasdair Arthur - modified for OpenDay by NJW
 */
function _ParseUpdateNotification($updateXML, $debug=0) {

    if ($debug > 0)  {echo $updateXML . "\n";}

    $xml = new SimpleXMLElement($updateXML);

    echo $GLOBALS["uuid"] . ",";
    echo gmdate('Y-m-d\TH:i:s\Z') . ",";
    echo $xml->InstanceID->attributes()->val . ",\"";
    echo $xml->InstanceID->TransportPlaySpeed->attributes()->val . "\",\"";
    echo $xml->InstanceID->TransportState->attributes()->val . "\",\"";
    echo $xml->InstanceID->TransportStatus->attributes()->val . "\",\"";
    echo $xml->InstanceID->CurrentTrackURI->attributes()->val . "\",\"";
    echo $xml->InstanceID->AVTransportURI->attributes()->val . "\",\"";
    if (preg_match('/xsi:..(.*$)/', $xml->InstanceID->CurrentTrackURI->attributes()->val, $matches)) {
        echo lookupchannel($matches[1]) . "\",\"";
        }
    elseif (preg_match('/pvr\/(.*$)/', $xml->InstanceID->CurrentTrackURI->attributes()->val, $matches)) {
        $bookingID = "BOOK:" . hexdec($matches[1]);
//		echo "Recording $bookingID \",\"";
        echo substr(getBookingSummary($GLOBALS["host"], $bookingID),0,-1) . "\",\"";
//		var_dump(getBookingSummary($GLOBALS["host"], $bookingID) . "\",\"");
    }
    echo $xml->InstanceID->CurrentTransportActions->attributes()->val . "\",\"";
    echo $xml->InstanceID->AbsoluteTimePosition->attributes()->val . "\"\n";

    return;
}

/**
 *
 * Send an email notification when a recording completes
 * @param String $to Required. The destination email address to send notifications.
 * @param String $title Required. Programme title.
 * @param String $strRecCompletionDesc Required.
 * @param String $dandt Required. Date and time of programme in human readable format.
 * @param String $host Required. IP Address of STB performing action.
 * @param Integer $debug Debug level. Defaults to 0 - No debug output.
 * @return Void
 * @access Public
 * @author Alasdair Arthur - modified for OpenDay by NJW
 */
function SendNotification($to, $title, $strRecCompletionDesc, $dandt, $host, $debug=0) {

    if ($strRecCompletionDesc == "Added") {
        $subject = "Booking for \"$title\" added";
        $body =  "A booking for \"$title\" at $dandt has been added to STB $host.";
        }
    else {
        $subject = "\"$title\" completed";
        $body =  "The recording of \"$title\" has completed on STB $host.";
        $body .= "The completion state was\n$strRecCompletionDesc.";
        }

    if (mail($to, $subject, $body)) {
        if ($debug > 0) echo date('Y-m-d H:i:s') . "\nMessage successfully sent\n";
    } else {
        if ($debug > 0) echo date('Y-m-d H:i:s') . "\nMessage send failed\n";
    }

    return;
}