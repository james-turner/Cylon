<?php
/**
 * stbClass
 *
 * stbClass used to provide access to single Darwin UPnP set top box.
 *
 * @author Nick Wootton
 * @version Beta 1.0
 * @package STB.php
 *
 */

require_once dirname(__FILE__) . '/services/miscServices.php';
require_once dirname(__FILE__) . '/services/discovery.php';
require_once dirname(__FILE__) . '/services/GENA.php';

/**
 *
 * stb class used to represent a single instance of a Darwin STB.
 * Various function details outlined below are taken from the work done by Alasdair Arthur from the 'STB Enhancements for Super Planner' document.
 *
 * @author Nick Wootton
 * @version Beta 1.0
 *
 */
class STB
{
    /**
     * Constructor used to create an instance of the class that corresponds directly to a specific STB via IP Address
     * @param Required String $IPAddress
     * @return Class Object  stb class object
     */
    function __construct($IPAddress)
    {
        $this->ipaddress = $IPAddress;

        $data = json_decode(getUPnPDescriptions($IPAddress));

        foreach ($data as $stb) {

            $this->id = $stb->Friendly_Name;
            $this->manufacturer = $stb->Manufacturer;
            $this->description = $stb->Description;
            $this->name = $stb->Name;
            $this->number = $stb->Number;
            $this->udn = $stb->UDN;
            $this->baseURL = $stb->BaseURL;
            $this->types = array();

            foreach ($stb->Types as $oServices) {
                $arrService = array();
                $arrService['DeviceType'] = $oServices->DeviceType;
                $arrService['DescURL'] = $oServices->DescURL;

                $this->types[] = $arrService;
            }
        }
    }

    /**
     *
     * Public constructor for all get methods
     * @param String $varName
     */
    public function __get($varName)
    {
        if (method_exists($this, $MethodName = 'get_' . $varName))
            return $this->$MethodName();
        else
            trigger_error($varName . ' is not avaliable .', E_USER_ERROR);
    }

    /**
     *
     * Public constructor for all set methods
     * @param String $varName
     * @param String $value
     */
    public function __set($varName, $value)
    {
        if (method_exists($this, $MethodName = 'set_' . $varName))
            return $this->$MethodName($value);
        else
            trigger_error($varName . ' is not avaliable .', E_USER_ERROR);
    }

    //Basic functions
    /**
     *
     * Returns IP Address of the class
     * @return String IPv4 standard IP Address
     */
    function get_ipaddress()
    {
        return $this->ipaddress;
    }

    /**
     *
     * Set IPAddress of the class
     * @param String $ipaddress IPv4 standard IP Address
     * @access Private
     */
    function set_ipaddress($ipaddress)
    {
        $this->ipaddress = $ipaddress;
    }

    /**
     *
     * Get the friendly name of the STB
     * @return String User friendly ID for the STB
     */
    function get_id()
    {
        return $this->id;
    }

    /**
     *
     * Set friendly name of STB
     * @param String $id User friendly ID for the STB
     * @access Private
     */
    function set_id($id)
    {
        $this->id = $id;
    }

    /**
     *
     * Returns name of STB manufacturer
     * @return String The name of the STB manufacturer
     */
    function get_manufacturer()
    {
        return $this->manufacturer;
    }

    /**
     *
     * Set name of STB manufacturer
     * @param String $manufacturer The name of the STB manufacturer
     * @access Private
     */
    function set_manufacturer($manufacturer)
    {
        $this->manufacturer = $manufacturer;
    }

    /**
     *
     * Get STB description
     * @return String Basic code description for the STB
     */
    function get_description()
    {
        return $this->description;
    }

    /**
     *
     * Set STB description
     * @param String $description Basic code description for the STB
     * @access Private
     */
    function set_description($description)
    {
        $this->description = $description;
    }

    /**
     *
     * Get STB name
     * @return String Basic name of the STB
     */
    function get_name()
    {
        return $this->name;
    }

    /**
     *
     * Set STB name info
     * @param String $name Basic name of the STB
     * @access Private
     */
    function set_name($name)
    {
        $this->name = $name;
    }

    /**
     *
     * Get STB model number
     * @return String Model and firmware infomation of the STB
     */
    function get_number()
    {
        return $this->number;
    }

    /**
     *
     * Set STB model number
     * @param String $number Model and firmware infomation of the STB
     * @access Private
     */
    function set_number($number)
    {
        $this->number = $number;
    }

    /**
     *
     * Get STB unique device number
     * @return String Unique UUID code for the STB
     */
    function get_udn()
    {
        return $this->udn;
    }

    /**
     *
     * Set STB unique device number
     * @param String $udn Unique UUID code for the STB
     * @access Private
     */
    function set_udn($udn)
    {
        $this->udn = $udn;
    }

    /**
     *
     * Get Base URL to access this STB - includes port
     * @return String The current, full URL of the STB including port number for accessing services
     */
    function get_baseURL()
    {
        return $this->baseURL;
    }

    /**
     *
     * Set Base URL to access this STB - includes port
     * @param String $baseURL The current, full URL of the STB including port number for accessing services
     * @access Private
     */
    function set_baseURL($baseURL)
    {
        $this->baseURL = $baseURL;
    }

    /**
     *
     * Get array of services available from this STB
     * @return Array An array listing the UPnP services available on this STB and the description document names/locations
     */
    function get_types()
    {
        return $this->types;
    }

    /**
     *
     * Set array of services available from this STB
     * @param array $types An array listing the UPnP services available on this STB and the description document names/locations
     * @access Private
     */
    function set_types($types)
    {
        $this->types = $types;
    }

    /**
     *
     * Extracts STB Port number from values returned in BaseURL data
     * @return Integer
     */
    function get_port()
    {
        $arrSTBURL = explode(':', $this->get_baseURL());

        return rtrim($arrSTBURL[2], "/");
    }


    //Start Generic UPnP Access Functions
    /**
     *
     * Gateway function to access ALL UPnP service requests
     * @param Required String $action The name of the action to perform eg. Browse
     * @param Required String $args A String of additional variables to pass.
     * @param Required String $service Which STB service is needed to perform this action eg SkyBook.
     * @access Private
     */
    function processUPnP($action, $args, $service)
    {
        try {
            //Build Header & SOAP/XML Request
            $arrData = $this->_buildUPnPCommand($action, $args, $service);

            //Construct URL
            $strURL = $this->_buildURL($service);

            //Send request
            $result = $this->_sendUPnPCommand($strURL, $arrData['doc'], $arrData['hdr']);

            //Do something with result....
            return $result;
        }
        catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            die;
        }

    }

    /**
     *
     * Build URL for requested service call
     * @param Required String $service The name of the STB service that will perform the function eg SkyBoojk
     * @return String $strURL The URL of this service
     * @access private
     */
    function _buildURL($service)
    {

        $strURL = $this->get_baseURL();

        switch ($service) {
            case "SkyPlay":
                $strURL .= 'SkyPlay2';
                break;
            case "SkyCM":
                $strURL .= 'SkyCM2';
                break;
            case "SkyRC":
                $strURL .= 'SkyRC2';
                break;
            case "SkyBook":
                $strURL .= 'SkyBook2';
                break;
            case "SkyBrowse":
                $strURL .= 'SkyBrowse2';
                break;
        }

        return $strURL;

    }

    /**
     *
     * Send SOAP command via cURL to STB
     * @param Required String $strURL The URL of the service being accessed
     * @param Required String $soap_request The SOAP request
     * @param Required String $header The SOAP header
     * @access private
     * @return String A SOAP response.
     */
    function _sendUPnPCommand($strURL, $soap_request, $header)
    {

        $soap_do = curl_init();

        curl_setopt($soap_do, CURLOPT_USERAGENT, 'SKY');
        curl_setopt($soap_do, CURLOPT_URL, $strURL);
        curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($soap_do, CURLOPT_POST, true);
        curl_setopt($soap_do, CURLOPT_POSTFIELDS, trim($soap_request));
        curl_setopt($soap_do, CURLOPT_HTTPHEADER, $header);

        $rtn = curl_exec($soap_do);

        if (($rtn) === false) {
            $err = 'Curl error: ' . curl_error($soap_do);
            die($err);
        }

        curl_close($soap_do);

        return $rtn;
    }

    /**
     *
     * Build SOAP request
     * @param Required String $strAction The name of the action to perform eg. Browse
     * @param Required String $strArguments A list of additional arguments
     * @param Required String $strService The STB service required to perform this action eg SkyBook.
     * @access private
     * @return array
     */
    function _buildUPnPCommand($strAction, $strArguments, $strService)
    {

        /*
        * This code MUST be left aligned left - otherwise the SOAPData blocks will NOT be created correctly
        */

        $arrMsg = array();

        if (strLen(trim($strArguments)) > 0) {

            $arrMsg['doc'] = <<<SOAPData
<?xml version="1.0" encoding="utf-8"?>
<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
<s:Body>
<u:$strAction xmlns:u="urn:schemas-nds-com:service:$strService:2">$strArguments</u:$strAction>
</s:Body>
</s:Envelope>
SOAPData;
        }
        else {
            $arrMsg['doc'] = <<<SOAPData
<?xml version="1.0" encoding="utf-8"?>
<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
<s:Body>
<u:$strAction xmlns:u="urn:schemas-nds-com:service:$strService:2"/>
</s:Body>
</s:Envelope>
SOAPData;
        }

        $arrMsg['hdr'] = array();
        $arrMsg['hdr'][] = 'Content-type: text/xml;charset="utf-8"';
        $arrMsg['hdr'][] = 'SOAPACTION: "urn:schemas-nds-com:service:' . $strService . ':2#' . $strAction . '"';

        return $arrMsg;
    }

    /**
     *
     * Extracts actual STB response from SOAP
     * @param String $prmResponseXML The full SOAP response from the STB
     * @return Array
     * @access Private
     */
    function parseUPnPResponse($prmResponseXML)
    {
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->loadXML($prmResponseXML);
        $respItems = $doc->getElementsByTagName('Body')->item(0)->childNodes->item(0)->childNodes;
        $arrResponse = array();
        foreach ($respItems as $item) {
            $arrResponse[$item->nodeName] = $item->nodeValue;
        }
        return $arrResponse;
    }

    //Start SkyBook Functions
    /**
     *
     * To Do
     * @service SkyBook
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/GetSortCapabilitiesBook/ </code>
     * @sample PHP: <code> $oSTB->GetSortCapabilitiesBook(); </code>
     * @return String SOAP response
     */
    function GetSortCapabilitiesBook()
    {
        //Define variables
        $service = 'SkyBook';
        $action = "GetSortCapabilities";
        $args = "";

        return $this->processUPnP($action, $args, $service);

    }

    /**
     *
     * To Do
     * @param String $DataTypeID Required.
     * @service SkyBook
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/GetPropertyList/13456/ </code>
     * @sample PHP: <code> $oSTB->GetPropertyList(123456); </code>
     * @return String SOAP response
     */
    function GetPropertyList($DataTypeID)
    {
        //Define variables
        $service = 'SkyBook';
        $action = "GetPropertyList";
        $args = "<DataTypeID>" . $DataTypeID . "</DataTypeID>";

        return $this->processUPnP($action, $args, $service);

    }

    /**
     *
     * To Do
     * @param String $DataTypeID Required.
     * @param String $Filter Required.
     * @service SkyBook
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/GetAllowedValues/12345/5453/ </code>
     * @sample PHP: <code> $oSTB->GetAllowedValues(12345,5453); </code>
     * @return String SOAP response
     */
    function GetAllowedValues($DataTypeID, $Filter)
    {
        //Define variables
        $service = 'SkyBook';
        $action = "GetAllowedValues";
        $args = "<DataTypeID>" . trim($DataTypeID) . "</DataTypeID>";
        $args .= "<Filter>" . trim($Filter) . "</Filter>";

        return $this->processUPnP($action, $args, $service);

    }

    /**
     *
     * Returns StateUpdate ID
     * @service SkyBook
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/GetStateUpdateID/ </code>
     * @sample PHP: <code> $oSTB->GetStateUpdateID(); </code>
     * @return String SOAP response
     */
    function GetStateUpdateID()
    {
        //Define variables
        $service = 'SkyBook';
        $action = "GetStateUpdateID";
        $args = "";

        return $this->processUPnP($action, $args, $service);

    }

    /**
     *
     * Not currently supported. Access level set to Private.
     * @param Required String $Filter A comma-separated list of property specifiers (including namespaces) to indicate which metadata properties are to be returned in the results from browsing or searching.
     * @param Integer $StartingIndex Starting zero based offset to enumerate children under the container specified by ObjectID
     * @param Integer $RequestedCount Requested number of entries under the object specified by ObjectID. RequestedCount =0 means request all entries. If this RequestedCount is set to a value greater than 25 then only 25 entries will be returned
     * @param String $SortCriteria Sort is not supported in current version (Releases 1 and 2).
     * @service SkyBook
     * @access Private
     * @return String SOAP response
     */
    function BrowseRecordSchedules($Filter, $StartingIndex, $RequestedCount, $SortCriteria)
    {
        //Define variables
        $service = 'SkyBook';
        $action = "BrowseRecordSchedules";
        $args = "<Filter>" . trim($Filter) . "</Filter>";
        $args .= "<StartingIndex>" . trim($StartingIndex) . "</StartingIndex>";
        $args .= "<RequestedCount>" . trim($RequestedCount) . "</RequestedCount>";
        $args .= "<SortCriteria>" . trim($SortCriteria) . "</SortCriteria>";

        return $this->processUPnP($action, $args, $service);

    }

    /**
     *
     * Not currently supported. Access level set to Private.
     * @param String $RecordScheduleID Required. The identifier of the SRS object representing the booking. The identifier is a hexadecimal number that, converted to decimal, corresponds to the booking id. In this example RSE:29009868 (hex) is the same Planner object as BOOK:687904872 (decimal).
     * @param String $Filter Required. A comma-separated list of property specifiers (including namespaces) to indicate which metadata properties are to be returned in the results from browsing or searching.
     * @param Integer $StartingIndex Starting zero based offset to enumerate children under the container specified by ObjectID
     * @param Integer $RequestedCount Requested number of entries under the object specified by ObjectID. RequestedCount =0 means request all entries. If this RequestedCount is set to a value greater than 25 then only 25 entries will be returned
     * @param String $SortCriteria Sort is not supported in current version (Releases 1 and 2).
     * @service SkyBook
     * @access Private
     * @return String SOAP response
     */
    function BrowseRecordTasks($RecordScheduleID, $Filter, $StartingIndex, $RequestedCount, $SortCriteria)
    {
        //Define variables
        $service = 'SkyBook';
        $action = "BrowseRecordTasks";
        $args = "<RecordScheduleID>" . trim($RecordScheduleID) . "</RecordScheduleID>";
        $args .= "<Filter>" . trim($Filter) . "</Filter>";
        $args .= "<StartingIndex>" . trim($StartingIndex) . "</StartingIndex>";
        $args .= "<RequestedCount>" . trim($RequestedCount) . "</RequestedCount>";
        $args .= "<SortCriteria>" . trim($SortCriteria) . "</SortCriteria>";

        return $this->processUPnP($action, $args, $service);

    }

    /**
     *
     * Takes in basic programme info and sets up a recording or reminder event
     * @param Integer $ChannelID Required. The channel ID value or cid
     * @param Integer $EventID Required. The event ID value or eid
     * @param Integer $Date Required. Start date of programme
     * @param Integer $Duration Required. Duration in seconds of the programme
     * @param Bit $Type The booking type (1 = Recording, 0 = Reminder)
     * @param Integer $SeriesID ID number of the series
     * @param Bit $Keep Keep flag (1 = set to keep, 0 = not set to keep)
     * @param Bit $SL Series Link flag to indicate whether or not to record the entire series
     * @service SkyBook
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/CreateRecordSchedule/2002/1234/4100010/3600/?Type=1 </code>
     * @sample PHP: <code> $oSTB->CreateRecordSchedule(2002,1234,4100010,3600,1); </code>
     * @return String A SOAP response.
     */
    function CreateRecordSchedule($ChannelID, $EventID, $Date, $Duration, $SeriesID, $Type = 1, $Keep = 0, $SL = 0)
    {

        //Convert from DEc to Hex
        $hexChannelid = dechex($ChannelID);
        $hexEventid = dechex($EventID);
        $SeriesID = dechex($SeriesID);

        //Build time in correct format - function written by Alasdair Arthur
        $schedtime = gmdate("Ymd", $Date) . "T" . gmdate("His", $Date) . "Z--" . convDurPOD($Duration);

        if ($SL == 0) {
            $Elements = "<?xml version=\"1.0\" encoding=\"UTF-8\"?> <srs xmlns=\"urn:schemas-upnp-org:av:srs\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\" urn:schemas-upnp-org:av:srs http://www.upnp.org/schemas/av/srs.xsd\"><item id=\"\"><title>BOOKING0</title><class>OBJECT.RECORDSCHEDULE.DIRECT.PROGRAMCODE</class><desiredPriority type=\"PREDEF\">DEFAULT</desiredPriority><desiredRecordQuality type=\"DEFAULT\"></desiredRecordQuality><scheduledProgramCode type=\"nds.com_URI\">xsi://$hexChannelid;$hexEventid~$schedtime?keep=$Keep&amp;type=$Type</scheduledProgramCode></item></srs>";
        }
        else {
            $Elements = "<?xml version=\"1.0\" encoding=\"UTF-8\"?> <srs xmlns=\"urn:schemas-upnp-org:av:srs\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\" urn:schemas-upnp-org:av:srs http://www.upnp.org/schemas/av/srs.xsd\"><item id=\"\"><title>BOOKING0</title><class>OBJECT.RECORDSCHEDULE.DIRECT.PROGRAMCODE</class><desiredPriority type=\"PREDEF\">DEFAULT</desiredPriority><desiredRecordQuality type=\"DEFAULT\"></desiredRecordQuality><scheduledProgramCode type=\"nds.com_URI\">xsi://$hexChannelid;$hexEventid~$schedtime?keep=$Keep&amp;type=$Type&amp;seriesID=$SeriesID</scheduledProgramCode></item></srs>";
        }

        //Define variables
        $service = 'SkyBook';
        $action = "CreateRecordSchedule";
        $args = "<Elements>" . htmlspecialchars($Elements) . "</Elements>";

        return $this->processUPnP($action, $args, $service);

    }

    /**
     *
     * Not currently supported. Access level set to Private. Use destroy object instead
     * @param String $RecordScheduleID Required. The identifier of the SRS object representing the booking. The identifier is a hexadecimal number that, converted to decimal, corresponds to the booking id. In this example RSE:29009868 (hex) is the same Planner object as BOOK:687904872 (decimal).
     * @service SkyBook
     * @access Private
     * @return String SOAP response
     */
    function DeleteRecordSchedule($RecordScheduleID)
    {
        //Define variables
        $service = 'SkyBook';
        $action = "DeleteRecordSchedule";
        $args = "<RecordScheduleID>" . trim($RecordScheduleID) . "</RecordScheduleID>";

        return $this->processUPnP($action, $args, $service);

    }

    /**
     *
     * Not currently supported. Access level set to Private.
     * @param String $RecordScheduleID Required. The identifier of the SRS object representing the booking. The identifier is a hexadecimal number that, converted to decimal, corresponds to the booking id. In this example RSE:29009868 (hex) is the same Planner object as BOOK:687904872 (decimal).
     * @param String $Filter Required. A comma-separated list of property specifiers (including namespaces) to indicate which metadata properties are to be returned in the results from browsing or searching.
     * @service SkyBook
     * @access Private
     * @return String SOAP response
     */
    function GetRecordSchedule($RecordScheduleID, $Filter)
    {
        //Define variables
        $service = 'SkyBook';
        $action = "GetRecordSchedule";
        $args = "<RecordScheduleID>" . trim($RecordScheduleID) . "</RecordScheduleID>";
        $args .= "<Filter>" . trim($Filter) . "</Filter>";

        return $this->processUPnP($action, $args, $service);

    }

    /**
     *
     * Not currently supported. Access level set to Private.
     * @param String $RecordTaskID Required.
     * @param String $Filter Required. A comma-separated list of property specifiers (including namespaces) to indicate which metadata properties are to be returned in the results from browsing or searching.
     * @service SkyBook
     * @access Private
     * @return String SOAP response
     */
    function GetRecordTask($RecordTaskID, $Filter)
    {
        //Define variables
        $service = 'SkyBook';
        $action = "GetRecordTask";
        $args = "<RecordTaskID>" . trim($RecordTaskID) . "</RecordTaskID>";
        $args .= "<Filter>" . trim($Filter) . "</Filter>";

        return $this->processUPnP($action, $args, $service);

    }

    /**
     *
     * Not currently supported. Access level set to Private.
     * @param String $RecordTaskID Required.
     * @service SkyBook
     * @access Private
     * @return String SOAP response
     */
    function EnableRecordTask($RecordTaskID)
    {
        //Define variables
        $service = 'SkyBook';
        $action = "EnableRecordTask";
        $args = "<RecordTaskID>" . trim($RecordTaskID) . "</RecordTaskID>";

        return $this->processUPnP($action, $args, $service);

    }

    /**
     *
     * Not currently supported. Access level set to Private.
     * @param String $RecordTaskID Required.
     * @service SkyBook
     * @access Private
     * @return String SOAP response
     */
    function DisableRecordTask($RecordTaskID)
    {
        //Define variables
        $service = 'SkyBook';
        $action = "DisableRecordTask";
        $args = "<$RecordTaskID>" . trim($$RecordTaskID) . "</$RecordTaskID>";

        return $this->processUPnP($action, $args, $service);

    }

    /**
     *
     * Not currently supported. Access level set to Private.
     * @param String $RecordTaskID Required.
     * @service SkyBook
     * @access Private
     * @return String SOAP response
     */
    function UnlinkRecordTask($RecordTaskID)
    {
        //Define variables
        $service = 'SkyBook';
        $action = "X_NDS_UnlinkRecordTask";
        $args = "<$RecordTaskID>" . trim($$RecordTaskID) . "</$RecordTaskID>";

        return $this->processUPnP($action, $args, $service);

    }

    //End SkyBook Functions

    //Start SkyBrowse Functions
    /**
     *
     * To Do
     * @service SkyBrowse
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/GetSearchCapabilities/ </code>
     * @sample PHP: <code> $oSTB->GetSearchCapabilities(); </code>
     * @return String SOAP response
     */
    function GetSearchCapabilities()
    {
        //Define variables
        $service = 'SkyBrowse';
        $action = "GetSearchCapabilities";
        $args = "";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * To Do
     * @service SkyBrowse
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/GetSortCapabilitiesBrowse/ </code>
     * @sample PHP: <code> $oSTB->GetSortCapabilitiesBrowse(); </code>
     * @return String SOAP response
     */
    function GetSortCapabilitiesBrowse()
    {
        //Define variables
        $service = 'SkyBrowse';
        $action = "GetSortCapabilities";
        $args = "";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * To Do
     * @service SkyBrowse
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/GetFeatureList/ </code>
     * @sample PHP: <code> $oSTB->GetFeatureList(); </code>
     * @return String SOAP response
     */
    function GetFeatureList()
    {
        //Define variables
        $service = 'SkyBrowse';
        $action = "GetFeatureList";
        $args = "";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * To Do
     * @service SkyBrowse
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/GetSystemUpdateID/ </code>
     * @sample PHP: <code> $oSTB->GetSystemUpdateID(); </code>
     * @return String SOAP response
     */
    function GetSystemUpdateID()
    {
        //Define variables
        $service = 'SkyBrowse';
        $action = "GetSystemUpdateID";
        $args = "";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * Will return an xml document listing the Planner contents. This is really a fixed version of the Search function elsewhere in this class.
     * @param String $ObjectID Object currently being browsed. An ObjectID value of zero (default) corresponds to the root object of the Content Directory.
     * @service SkyBrowse
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/Browse/ </code>
     * @sample PHP: <code> $stb->Browse() </code>
     * @return String A SOAP response.
     */
    function Browse($ObjectID = 0)
    {
        //Define variables
        $service = 'SkyBrowse';

        $action = "Browse";

        $args = "<ObjectID>" . trim($ObjectID) . "</ObjectID>";
        $args .= "<BrowseFlag>BrowseDirectChildren</BrowseFlag>";
        $args .= "<Filter>*</Filter>";
        $args .= "<StartingIndex>0</StartingIndex>";
        $args .= "<RequestedCount>0</RequestedCount>";
        $args .= "<SortCriteria />";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * Search/filter the contents of the STB planner
     *
     * Selective Search Filters:
     * <ol>
     *     <li>Retrieve current and completed recordings
     *         <code>(vx:X_bookingType = "2") and (vx:X_recStatus@failed = "0")
     * and ((vx:X_recStatus = "3") or (vx:X_recStatus = "4") or (vx:X_recStatus = "5"))</code>
     *     </li>
     *
     *     <li>Retrieve future recording bookings
     *         <code>(vx:X_bookingType = "2") and ((vx:X_recStatus = "1") or (vx:X_recStatus = "2"))</code>
     *     </li>
     *
     *     <li>Retrieve future reminder bookings
     *     <code>(vx:X_bookingType = "1") and (vx:X_reminderStatus = "1")</code></li>
     * </ol>
     *
     *    Each one is used by passing the appropriate query String as the SearchCriteria parameter in the Search request.
     *
     * @param String $ContainerID Required. Object currently being browsed. An ObjectID value of zero corresponds to the root object of the Content Directory.
     * @param String $SearchCriteria Required. One of three pre-defined search criteria.
     * @param String $Filter Required. A comma-separated list of property specifiers (including namespaces) to indicate which metadata properties are to be returned in the results from browsing or searching.
     * @param Integer $StartingIndex Starting zero based offset to enumerate children under the container specified by ObjectID
     * @param Integer $RequestedCount Requested number of entries under the object specified by ObjectID. RequestedCount =0 means request all entries. If this RequestedCount is set to a value greater than 25 then only 25 entries will be returned
     * @param String $SortCriteria Required. Sort is not supported in current version (Releases 1 and 2).
     * @service SkyBrowse
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/Search/ </code>
     * @sample PHP: <code> $oSTB->Search(); </code>
     * @return String A SOAP response.
     */
    function Search($ContainerID, $SearchCriteria, $Filter, $StartingIndex, $RequestedCount, $SortCriteria)
    {
        //Define variables
        $service = 'SkyBrowse';

        $action = "Search";

        $args = "<ContainerID>" . trim($ContainerID) . "</ContainerID>";
        $args .= "<SearchCriteria>" . trim($SearchCriteria) . "</SearchCriteria>";
        $args .= "<Filter>" . trim($Filter) . "</Filter>";
        $args .= "<StartingIndex>" . trim($StartingIndex) . "</StartingIndex>";
        $args .= "<RequestedCount>" . trim($RequestedCount) . "</RequestedCount>";
        $args .= "<SortCriteria>" . trim($SortCriteria) . "</SortCriteria>";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * Used to delete a recording, recording booking and a reminder
     * @param String $ObjectID Required. Object unique ID
     * @service SkyBrowse
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/DestroyObject/123456/ </code>
     * @sample PHP: <code> $oSTB->DestroyObject(123456); </code>
     */
    function DestroyObject($ObjectID)
    {
        //Define variables
        $service = 'SkyBrowse';
        $action = "DestroyObject";
        $args = "<ObjectID>" . trim($ObjectID) . "</ObjectID>";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * Returns info based on IDs supplied via subscription services
     * @param String $ContainerID Required. Object currently being browsed. An ObjectID value of zero corresponds to the root object of the Content Directory.
     * @param String $ReqUpdateID Required.
     * @param Integer $StartingIndex Starting zero based offset to enumerate children under the container specified by ObjectID
     * @param Integer $RequestedCount Requested number of entries under the object specified by ObjectID. RequestedCount =0 means request all entries. If this RequestedCount is set to a value greater than 25 then only 25 entries will be returned
     * @service SkyBrowse
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/GetUpdateData/12/34/56/78/ </code>
     * @sample PHP: <code> $oSTB->GetUpdateData(12,34,56,78); </code>
     * @return String A SOAP response.
     */
    function GetUpdateData($ContainerID, $ReqUpdateID, $StartingIndex, $RequestedCount)
    {
        //Define variables
        $service = 'SkyBrowse';
        $action = "X_NDS_GetUpdateData";
        $args = "<ContainerID>" . trim($ContainerID) . "</ContainerID>";
        $args .= "<ReqUpdateID>" . trim($ReqUpdateID) . "</ReqUpdateID>";
        $args .= "<StartingIndex>" . trim($StartingIndex) . "</StartingIndex>";
        $args .= "<RequestedCount>" . trim($RequestedCount) . "</RequestedCount>";

        return $this->processUPnP($action, $args, $service);
    }

    //End SkyBrowse Functions

    //Start SkyCM Functions
    /**
     *
     * To Do
     * @service SkyCM
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/GetProtocolInfo/ </code>
     * @sample PHP: <code> $oSTB->GetProtocolInfo(); </code>
     * @return String A SOAP response.
     */
    function GetProtocolInfo()
    {
        //Define variables
        $service = 'SkyCM';
        $action = "GetProtocolInfo";
        $args = "";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * To Do
     * @service SkyCM
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/GetCurrentConnectionIDs/ </code>
     * @sample PHP: <code> $oSTB->GetCurrentConnectionIDs(); </code>
     * @return String A SOAP response.
     */
    function GetCurrentConnectionIDs()
    {
        //Define variables
        $service = 'SkyCM';
        $action = "GetCurrentConnectionIDs";
        $args = "";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * To Do
     * @param String $ConnectionID Required.
     * @service SkyCM
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/GetCurrentConnectionInfo/12345/ </code>
     * @sample PHP: <code> $oSTB->GetCurrentConnectionInfo(12345); </code>
     * @return String A SOAP response.
     */
    function GetCurrentConnectionInfo($ConnectionID)
    {
        //Define variables
        $service = 'SkyCM';
        $action = "GetCurrentConnectionInfo";
        $args = "<ConnectionID>" . trim($ConnectionID) . "</ConnectionID>";

        return $this->processUPnP($action, $args, $service);
    }

    //End SkyCM Functions

    //Start SkyPlay Functions
    /**
     *
     * The GetTransportInfo action is used to retrieve state variables relating to the playback state of the currently playing content, such as whether it is in normal playback, paused, in a trick mode and so on.
     * @param String $InstanceID Refers to the media. Always value of zero (0).
     * @service SkyPlay
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/GetTransportInfo/ </code>
     * @sample PHP: <code> $oSTB->GetTransportInfo(); </code>
     * @return String SOAP response
     */
    function GetTransportInfo($InstanceID = 0)
    {
        //Define variables
        $service = 'SkyPlay';
        $action = "GetTransportInfo";
        $args = "<InstanceID>" . trim($InstanceID) . "</InstanceID>";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * The GetMediaInfo action is used to retrieve state variables relating to the identity of currently playing content. Note that for the initial release the only state variable which is actually updated is AVTransportURI.
     * @param String $InstanceID Refers to the media. Always value of zero (0).
     * @service SkyPlay
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/GetMediaInfo/ </code>
     * @sample PHP: <code> $oSTB->GetMediaInfo(); </code>
     * @return String SOAP response
     */
    function GetMediaInfo($InstanceID = 0)
    {
        //Define variables
        $service = 'SkyPlay';
        $action = "GetMediaInfo";
        $args = "<InstanceID>" . trim($InstanceID) . "</InstanceID>";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * As per GetMediaInfo() but with additional data relating to the 'next' media item if supported
     * @param String $InstanceID Refers to the media. Always value of zero (0).
     * @service SkyPlay
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/GetMediaInfoExt/ </code>
     * @sample PHP: <code> $oSTB->GetMediaInfoExt(); </code>
     * @return String SOAP response
     */
    function GetMediaInfoExt($InstanceID = 0)
    {
        //Define variables
        $service = 'SkyPlay';
        $action = "GetMediaInfo_Ext";
        $args = "<InstanceID>" . trim($InstanceID) . "</InstanceID>";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * Changes channel on STB. If no channel and instance are passed, will default to Sky1
     * @param Integer $cid Channel number to change to
     * @param String $InstanceID Use zero (0) as this will correspond to the currently playing stream.
     * @service SkyPlay
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/ChannelChange/
     * http://upnp_api.remote/ChannelChange/?cid=4050
     * http://upnp_api.remote/ChannelChange/?cid=4050&inatnceid=0 </code>
     * @sample PHP: <code> $oSTB->ChannelChange();
     * $oSTB->ChannelChange(4050);
     * $oSTB->ChannelChange(4050,0); </code>
     * @return String SOAP response
     */
    function ChannelChange($cid = 1402, $InstanceID = 0)
    {
        //Simple version of function used for changing channels

        //Convert cid to hex for call
        $strHex = dechex($cid);

        //Define variables
        $service = 'SkyPlay';
        $action = "SetAVTransportURI";
        $args = "<InstanceID>" . trim($InstanceID) . "</InstanceID>";
        $args .= "<CurrentURI>xsi://" . trim($strHex) . "</CurrentURI>";
        $args .= "<CurrentURIMetaData>NOT_IMPLEMENTED</CurrentURIMetaData>";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * Used to switch between media stored on PVR
     * @param String $CatalogueID Required.
     * @param Integer $Position Start position of playback in seconds from beginning of recording.
     * @param Integer $Speed Playback speed (-30,-12,-6,-2,2,6,12,30)
     * @param String $InstanceID Use zero (0) as will control current playback
     * @service SkyPlay
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/MediaChange/123456/0/
     * http://upnp_api.remote/MediaChange/123456/0/?Speed=16&InstanceID=0 </code>
     * @sample PHP: <code> $oSTB->MediaChange(123456,0);
     * $oSTB->MediaChange(123456,120,16,0); </code>
     * @return String SOAP response
     */
    function MediaChange($CatalogueID, $Position, $Speed = 1, $InstanceID = 0)
    {
        //More complex version used for PVR control
        $strURL = $CatalogueID . '?position=' . $Position . '&amp;speed=' . $Speed;

        //Define variables
        $service = 'SkyPlay';
        $action = "SetAVTransportURI";
        $args = "<InstanceID>" . trim($InstanceID) . "</InstanceID>";
        $args .= "<CurrentURI>file://pvr/" . trim($strURL) . "</CurrentURI>";
        $args .= "<CurrentURIMetaData>NOT_IMPLEMENTED</CurrentURIMetaData>";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * To Do
     * @param String $InstanceID Refers to the media. Value of zero (0) equates to currently playing media.
     * @service SkyPlay
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/GetPositionInfo/ </code>
     * @sample PHP: <code> $oSTB->GetPositionInfo(); </code>
     * @return String SOAP response
     */
    function GetPositionInfo($InstanceID = 0)
    {
        //Define variables
        $service = 'SkyPlay';
        $action = "GetPositionInfo";
        $args = "<InstanceID>" . trim($InstanceID) . "</InstanceID>";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * To Do
     * @param String $InstanceID Refers to the media. Value of zero (0) equates to currently playing media.
     * @service SkyPlay
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/GetDeviceCapabilities/ </code>
     * @sample PHP: <code> $oSTB->GetDeviceCapabilities(); </code>
     * @return String SOAP response
     */
    function GetDeviceCapabilities($InstanceID = 0)
    {
        //Define variables
        $service = 'SkyPlay';
        $action = "GetDeviceCapabilities";
        $args = "<InstanceID>" . trim($InstanceID) . "</InstanceID>";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * To Do
     * @param String $InstanceID Refers to the media. Value of zero (0) equates to currently playing media.
     * @service SkyPlay
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/GetTransportSettings/ </code>
     * @sample PHP: <code> $oSTB->GetTransportSettings(); </code>
     * @return String SOAP response
     */
    function GetTransportSettings($InstanceID = 0)
    {
        //Define variables
        $service = 'SkyPlay';
        $action = "GetTransportSettings";
        $args = "<InstanceID>" . trim($InstanceID) . "</InstanceID>";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * Stops specified instance
     * @param String $InstanceID Refers to the media. Value of zero (0) equates to currently playing media.
     * @service SkyPlay
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/DoStop/ </code>
     * @sample PHP: <code> $oSTB->DoStop(); </code>
     * @return String SOAP response
     */
    function DoStop($InstanceID = 0)
    {
        //Define variables
        $service = 'SkyPlay';
        $action = "Stop";
        $args = "<InstanceID>" . trim($InstanceID) . "</InstanceID>";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * Controls speed of media playback
     * @param String $InstanceID Refers to the media. Value of zero (0) equates to currently playing media.
     * @param Integer $Speed Refers to the playback speed (-30,-12,-6,-2,1,1/2,2,6,12,30, 'play', 'stop', 'pause' ). defaults to 1 - normal playback.
     * @service SkyPlay
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/DoPlay/
     * http://upnp_api.remote/DoPlay/?Speed=30
     * http://upnp_api.remote/DoPlay/?Speed=-2 </code>
     * @sample PHP: <code> $oSTB->DoPlay();
     * $oSTB->DoPlay(0,30);
     * $oSTB->DoPlay(0,-2,); </code>
     * @return String SOAP response
     */
    function DoPlay($InstanceID = 0, $Speed = 1)
    {
        //Define variables
        $service = 'SkyPlay';
        $action = "Play";
        $args = "<InstanceID>" . trim($InstanceID) . "</InstanceID>";
        $args .= "<Speed>" . trim($Speed) . "</Speed>";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * Pauses specified instance
     * @param String $InstanceID Refers to the media. Value of zero (0) equates to currently playing media.
     * @service SkyPlay
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/DoPause/ </code>
     * @sample PHP: <code> $oSTB->DoPause(); </code>
     * @return String SOAP response
     */
    function DoPause($InstanceID = 0)
    {
        //Define variables
        $service = 'SkyPlay';
        $action = "Pause";
        $args = "<InstanceID>" . trim($InstanceID) . "</InstanceID>";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * Not currently supported. Access level set to Private.
     * @param String $InstanceID Refers to the media. Value of zero (0) equates to currently playing media.
     * @param String $strUnit
     * @param String $strTarget
     * @service SkyPlay
     * @access Private
     * @return String SOAP response
     */
    function DoSeek($InstanceID = 0, $strUnit = 1, $strTarget = 1)
    {
        //Define variables
        $service = 'SkyPlay';
        $action = "Play";
        $args = "<InstanceID>" . trim($InstanceID) . "</InstanceID>";
        $args .= "<Unit>" . trim($strUnit) . "</Unit>";
        $args .= "<Target>" . trim($strTarget) . "</Target>";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * Skips to next section in specified instance
     * @param String $InstanceID Refers to the media. Value of zero (0) equates to currently playing media.
     * @service SkyPlay
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/DoNext/ </code>
     * @sample PHP: <code> $oSTB->DoNext(); </code>
     * @return String SOAP response
     */
    function DoNext($InstanceID = 0)
    {
        //Define variables
        $service = 'SkyPlay';
        $action = "Next";
        $args = "<InstanceID>" . trim($InstanceID) . "</InstanceID>";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * Skips to previous section in specified instance
     * @param String $InstanceID Refers to the media. Value of zero (0) equates to currently playing media.
     * @service SkyPlay
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/DoPrevious/ </code>
     * @sample PHP: <code> $oSTB->DoPrevious(); </code>
     * @return String SOAP response
     */
    function DoPrevious($InstanceID = 0)
    {
        //Define variables
        $service = 'SkyPlay';
        $action = "Previous";
        $args = "<InstanceID>" . trim($InstanceID) . "</InstanceID>";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * Returns a list of actions that can be applied to the current media instance
     * @param String $InstanceID Refers to the media. Value of zero (0) equates to currently playing media.
     * @service SkyPlay
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/GetCurrentTransportActions/ </code>
     * @sample PHP: <code> $oSTB->GetCurrentTransportActions(); </code>
     * @return String SOAP response
     */
    function GetCurrentTransportActions($InstanceID = 0)
    {
        //Define variables
        $service = 'SkyPlay';
        $action = "GetCurrentTransportActions";
        $args = "<InstanceID>" . trim($InstanceID) . "</InstanceID>";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * Is used to retrieve the state variable relating to the CA condition which exists for the currently playing content, such as whether it is playing normally or is blocked due to parental controls, ratings and so on.
     * @param String $InstanceID Refers to the media. Value of zero (0) equates to currently playing media.
     * @service SkyPlay
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/GetCACondition/ </code>
     * @sample PHP: <code> $oSTB->GetCACondition(); </code>
     * @return String SOAP response
     */
    function GetCACondition($InstanceID = 0)
    {
        //Define variables
        $service = 'SkyPlay';
        $action = "X_NDS_GetCACondition";
        $args = "<InstanceID>" . trim($InstanceID) . "</InstanceID>";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * Allowing submission of a PIN if the condition ERROR_PIN_REQUIRED has arisen on the box.
     * @param String $InstanceID Refers to the media. Always zero (0).
     * @param Integer $PINCode
     * @service SkyPlay
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/SetBoxPin/1234/
     * http://upnp_api.remote/SetBoxPin/1234/?InstanceID=0 </code>
     * @sample PHP: <code> $oSTB->SetBoxPin(1234,0);
     * $oSTB->SetBoxPin(1234); </code>
     * @return String SOAP response
     */
    function SetBoxPin($InstanceID = 0, $intPINCode)
    {
        //Define variables
        $service = 'SkyPlay';
        $action = "X_NDS_SetUserPIN";
        $args = "<InstanceID>" . trim($InstanceID) . "</InstanceID>";
        $args .= "<UserPIN>" . trim($intPINCode) . "</UserPIN>";

        return $this->processUPnP($action, $args, $service);
    }

    //End SkyPlay Functions

    //Start SkyRC Functions
    /**
     *
     * To Do
     * @param String $InstanceID Required. Refers to the media. Value of zero (0) equates to currently playing media.
     * @service SkyRC
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/ListPresets/ </code>
     * @sample PHP: <code> $oSTB->ListPresets(); </code>
     * @return String SOAP response
     */
    function ListPresets($InstanceID = 0)
    {
        //Define variables
        $service = 'SkyRC';
        $action = "ListPresets";
        $args = "<InstanceID>" . trim($InstanceID) . "</InstanceID>";

        return $this->processUPnP($action, $args, $service);
    }

    /**
     *
     * To Do
     * @param String $InstanceID Required. Refers to the media. Value of zero (0) equates to currently playing media.
     * @param String $PresetName Required.
     * @service SkyRC
     * @api Available via API
     * @sample API: <code> http://upnp_api.remote/SelectPreset/123456/0/ </code>
     * @sample PHP: <code> $oSTB->SelectPreset(123456,0); </code>
     * @return String SOAP response
     */
    function SelectPreset($InstanceID, $PresetName)
    {
        //Define variables
        $service = 'SkyRC';
        $action = "SelectPreset";
        $args = "<InstanceID>" . trim($InstanceID) . "</InstanceID>";
        $args .= "<PresetName>" . trim($PresetName) . "</PresetName>";

        return $this->processUPnP($action, $args, $service);
    }

    //End SkyRC Functions

    //Start Misc STB Functions
    /**
     *
     * Initiate subscription to service in STB
     * @param String $Service Required. The name of the service being subscribed to. SkyPlay or SkyBook.
     * @param Integer $ListeningPort Required. The port the client will be listening on.
     * @param Integer $Duration The length in seconds that the subscription will run for. Defaults to 300 seconds.
     * @param Integer $Debug Debug level. Defaults to 0 - No debug output.
     * @return String The subscription ID.
     * @access Public
     * @author NJW
     */
    function Subscribe($Service, $ListeningPort, $Duration = 300, $Debug = 0)
    {

        //Define empty object class to hold subscription info
        $oSub = new stdClass();

        $oSub->hostport = $this->get_port(); // This must match the STB port found in the discovery process
        $oSub->listport = $ListeningPort; // This is an arbitrary choice of port number
        $oSub->timeout = $Duration;
        $oSub->controlURL = '/' . $Service . '2';
        $oSub->serviceType = "urn:schemas-nds-com:service:" . $Service . ":2";

        $oSub->host = $this->get_ipaddress();
        $oSub->listhost = GetMyIp();

        $arruuid = explode(":", $this->get_udn()); //Removes uuid: from start of string
        $oSub->uuid = $arruuid[1];

        $oSub->strSID = _subSetUp($oSub->host, $oSub->hostport, $oSub->serviceType, $oSub->controlURL, $oSub->listhost, $oSub->listport, $oSub->uuid, $oSub->timeout, $Debug);

        return $oSub->strSID;

    }

    /**
     *
     * Wrapper function to unsubscribe from STB subscriptions
     * @param String $ControlURL Required. The name of the service being subscribed to. SkyPlay or SkyBook.
     * @param String $SID Required. The unique ID of the subscription
     * @param Integer $Debug Debug level. Defaults to 0 - No debug output.
     * @return String SOAP response
     * @access Public
     * @author NJW
     */
    function Unsubscribe($ControlURL, $SID, $Debug = 0)
    {
        return _subKill($this->get_ipaddress(), $this->get_port(), $ControlURL, $SID, $Debug);
    }
    //End Misc STB Functions

}