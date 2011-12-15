<?php
/**
* Misc Services
*
* Misc services used across various pages/classes/services
*
* @author Various
* @version Beta 1.0
* @package miscServices.php
*
*
*/

/**
 *
 * Converts recordset from DB into json or csv file
 * @param Recordset $rs Required. Source recordset
 * @param String $type Output format either json or csv
 * @param String $jsonmain Required. Top level name of json string
 * @author http://www.barattalo.it/2010/01/25/10-php-usefull-functions-for-mysql-stuff
 * @return String
 */
function convertResult($rs, $type, $jsonmain="") {
    // http://www.barattalo.it/2010/01/25/10-php-usefull-functions-for-mysql-stuff/
    // receive a recordset and convert it to csv
    // or to json based on "type" parameter.
    $jsonArray = array();
    $csvString = "";
    $csvcolumns = "";
    $count = 0;
    while($r = mysql_fetch_row($rs)) {
        for($k = 0; $k < count($r); $k++) {
            $jsonArray[$count][mysql_field_name($rs, $k)] = $r[$k];
            $csvString.=",\"".$r[$k]."\"";
        }
        if (!$csvcolumns) for($k = 0; $k < count($r); $k++) $csvcolumns.=($csvcolumns?",":"").mysql_field_name($rs, $k);
        $csvString.="\n";
        $count++;
    }
    $jsondata = "{\"$jsonmain\":".json_encode($jsonArray)."}";
    $csvdata = str_replace("\n,","\n",$csvcolumns."\n".$csvString);

    return ($type=="csv"?$csvdata:$jsondata);
}

/**
 *
 * Converts array into pretty printed display
 * @param Array $arrayname Required.
 * @param String $tab
 * @param Integer $indent
 * @return Void
 */
function displayArrayContentFunction($arrayname,$tab="&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp",$indent=0) {
 $curtab ="";
 $returnvalues = "";
 while(list($key, $value) = each($arrayname)) {
  for($i=0; $i<$indent; $i++) {
   $curtab .= $tab;
   }
  if (is_array($value)) {
   $returnvalues .= "$curtab$key : Array: <br />$curtab{<br />\n";
   $returnvalues .= displayArrayContentFunction($value,$tab,$indent+1)."$curtab}<br />\n";
   }
  else $returnvalues .= "$curtab$key => $value<br />\n";
  $curtab = NULL;
  }
 return $returnvalues;
}

/**
 *
 * Returns list of channels by region and sub-bouquet from Sky on-line EPG. Defaults to HD London region.
 * @param Integer $regionid Region ID code
 * @param Integer $subbouquet No idea, but sounds sweet
 * @return String
 */
function getChannelList($regionid=4101,$subbouquet=1) {

    //Defaults to London HD

    // Set up the stream context details including proxy and user agen settings
    $context = stream_context_create (
        array (
            'http' => array (
                'proxy' => '',
                'request_fulluri' => true,
                'user_agent' => 'SKY test harness',
                'timeout' => 15
            )
        )
    );

    $url = "http://epgservices.sky.com/5.1.1/api/2.0/region/json/$regionid/$subbouquet/";

    if ($handle = fopen($url,'rb',false,$context)) {
        $contents = '';
        while (!feof($handle)) {
        $contents .= fread($handle, 8192);
        }
        fclose($handle);
        }
    else {
        echo "<p>Error - could not open channel list for region $regionid at:<br> $url</p>";
        exit;
        }

    $json_a=json_decode($contents,true);

    $channellist = ($json_a['init']['channels']);

    $data = '{"Channels" : ' . json_encode($channellist) . '}';

    return $data;

}

/**
 *
 * Converts integer duration into correct format
 * @author Alasdair Arthur
 * @param Integer $dur Required. Duration in seconds
 * @return String
 */
function convDurPOD($dur)
{
    // Put the time into the correct format
    $durH = floor($dur/3600);
    $durM = floor(($dur-($durH*3600))/60);
    $durS = floor($dur-($durH*3600)-($durM*60));
    $StrDur = 'PT' . str_pad($durH,2,'0', STR_PAD_LEFT) . 'H' . str_pad($durM,2,'0', STR_PAD_LEFT)  . 'M' . str_pad($durS,2,'0', STR_PAD_LEFT) . 'S';

    return $StrDur;
}


function GetMyIp() {

    return ($_SERVER['SERVER_ADDR']);

}