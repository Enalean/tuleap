<?php
/**
 * ILS Driver for VuFind to get information from PICA
 * 
 * @author Oliver Marahrens <o.marahrens@tu-harburg.de>
 * 
 * Authentication in this driver is handled via LDAP, not via normal PICA!
 * First check local vufind database, and if no user is found, check LDAP.
 * LDAP configuration settings are taken from vufinds config.ini
 * 
 */

require_once 'Interface.php';
require_once 'DAIA.php';
require_once 'services/MyResearch/lib/User.php';
require_once 'sys/authn/LDAPConfigurationParameter.php';

/**
 * Holding information is got by DAIA, so its not necessary to implement those functions here
 * Just needs to extend DAIA driver
 */
class PICA extends DAIA
{

    private $username;
    private $password;
    private $ldapConfigurationParameter;

    /**
     * Constructor
     */
    public function __construct() {
    	parent::__construct();

        $configArray = parse_ini_file('conf/PICA.ini', true);

        $this->catalogHost = $configArray['Catalog']['Host'];
        $this->renewalsScript = $configArray['Catalog']['renewalsScript'];
    }

    // public functions implemented to satisfy Driver Interface
    
    /**
     * ********
     * Login a patron and return their basic details.
     * 
     */
    public function patronLogin($barcode, $password)
    {
        $this->ldapConfigurationParameter = new LDAPConfigurationParameter();
        if (isset($_SESSION['picauser']) === true) {
        	$barcode = $_SESSION['picauser']->username;
        	$password = $_SESSION['picauser']->cat_password;
        }
        if ($barcode == '' || $password == ''){
            return new PEAR_Error('Invalid Login, Please try again.');
        }
        $this->username = $barcode;
        $this->password = $password;
        
        // first look into local database
        $loginUser = new User();
        $loginUser->username = $barcode;
        $loginUser->password = $password;
        if ($loginUser->find(true)) {
            $loginUser->cat_username = $barcode;
            $loginUser->cat_password = $password;
        	$userArray = array('id' => $loginUser->id, 'firstname' =>  $loginUser->firstname, 'lastname' => $loginUser->lastname, 'email' => $loginUser->email, 'username' => $barcode, 'password' => $password, 'cat_username' => $barcode, 'cat_password' => $password);
            $_SESSION['picauser'] = $loginUser;
            return $userArray;
        } else {
            // if not found locally, look into LDAP for user data
            $result = $this->bindUser();
        }
               
        if (get_class($result) === 'PEAR_Error') {
            return false;
        }
        
        return $result;
    }

    /**
     * ********
     * Get a patron's detailed information.
     * 
     */
    public function getMyProfile($user)
    {
        $userinfo = $this->getUserdataFromLdap();
        // firstname
        $recordList['firstname'] = $userinfo->firstname;
        // lastname
        $recordList['lastname'] = $userinfo->lastname;
        //Street and Number $ City $ Zip
        $address = explode("\$", $userinfo->address);
        // address1
        $recordList['address1'] = $address[1];
        // address2
        $recordList['address2'] = $address[2];
        // zip (Post Code)
        $recordList['zip'] = $address[3];
        // phone
        $recordList['phone'] = $userinfo->phone;
        // group
        $recordList['group'] = $userinfo->group;
        if ($recordList['firstname'] === null) {
        	$recordList = $user;
            // add a group
            $recordList['group'] = 'No library account';
        }
        return $recordList;
    }

    /**
     * ********
     * Show items currently on loan to the patron.
     * 
     */
    public function getMyTransactions($patron)
    {
        $URL = "/loan/DB=1/USERINFO";
        $POST = array("ACT" => "UI_LOL", "BOR_U" => $_SESSION['picauser']->username, "BOR_PW" => $_SESSION['picauser']->cat_password);
        $postit = $this->postit($URL, $POST);

        // How many items are there?
        $holds = substr_count($postit, 'input type="checkbox" name="VB"');
        $holdsByIframe = substr_count($postit, '<iframe');
        $ppns = array();
        $expiration = array();
        $transList = array();
        $barcode = array();
        $reservations = array();
        if ($holdsByIframe >= $holds) {
            $position = strpos($postit, '<iframe');
            for ($i = $i; $i < $holdsByIframe; $i++) {
                $pos = strpos($postit, 'VBAR=', $position);
                $value = substr($postit, $pos+9, 8);
                $completeValue = substr($postit, $pos+5, 12);
                $barcode[] = $completeValue;
                $ppns[] = $this->getPpnByBarcode($value);
                $position = $pos + 1;
                $position_expire = $position;
                for ($n = 0; $n<3; $n++) {
                    $position_expire = $this->strpos_backwards($postit, '<td class="value-small">', $position_expire-1);
                    if ($n === 1) {
                        $position_reservations = $position_expire;
                    }
                }
                $reservations[] = substr($postit, $position_reservations+24, 1);
                $expiration[] = substr($postit, $position_expire+24, 10);
                $renewals[] = $this->getRenewals($completeValue);
            }
            $holds = $holdsByIframe;
        }
        else {
            // no iframes in PICA catalog, use checkboxes instead
            // Warning: reserved items have no checkbox in OPC! They wont appear in this list
            $position = strpos($postit, 'input type="checkbox" name="VB"');
            for ($i = 0; $i < $holds; $i++) {
                $pos = strpos($postit, 'value=', $position);
                $value = substr($postit, $pos+11, 8);
                $completeValue = substr($postit, $pos+7, 12);
                $barcode[] = $completeValue;
                $ppns[] = $this->getPpnByBarcode($value);
                $position = $pos + 1;
                $position_expire = $position;
                for ($n = 0; $n<4; $n++) {
                    $position_expire = strpos($postit, '<td class="value-small">', $position_expire+1);
                }
                $expiration[] = substr($postit, $position_expire+24, 10);
                $renewals[] = $this->getRenewals($completeValue);
            }
        }
        for ($i = 0; $i < $holds; $i++) {
        	if ($ppns[$i] !== false) {
                $transList[] = array(
                    'id'      => $ppns[$i],
                    'duedate' => $expiration[$i],
                    'renewals' => $renewals[$i],
                    'reservations' => $reservations[$i],
                    'vb'      => $barcode[$i]
                );
        	}
        	else {
        		// There is a problem: no PPN found for this item...
        		// lets take id 0 to avoid serious error (that will just return an empty title)
        		$transList[] = array(
                    'id'      => 0,
                    'duedate' => $expiration[$i],
                    'renewals' => $renewals[$i],
                    'reservations' => $reservations[$i],
                    'vb'      => $barcode[$i]
                );
        	}
        }
        return $transList;
    }

    private function strpos_backwards($haystack, $needle, $offset = 0) {
        if ($offset === 0) {
            $haystack_reverse = strrev($haystack);
        }
        else {
            $haystack_reverse = strrev(substr($haystack, 0, $offset));
        }
        $needle_reverse = strrev($needle);
        $position_brutto = strpos($haystack_reverse, $needle_reverse);
        if ($offset === 0) {
            $position_netto = strlen($haystack)-$position_brutto-strlen($needle);
        }
        else {
            $position_netto = $offset-$position_brutto-strlen($needle);
        }
        return $position_netto;
    }

    /**
     * get the number of renewals
     * 
     * @param string $barcode Barcode of the medium
     * @return int number of renewals, if renewals script has not been set, return false
     */
    private function getRenewals($barcode) {
        $renewals = false;
        if (isset($this->renewalsScript) === true) {
            $POST = array("DB" => '1', "VBAR" => $barcode, "U" => $_SESSION['picauser']->username);
            $URL = $this->renewalsScript;
            $postit = $this->postit($URL, $POST);
    	
    	    $renewalsString = $postit;
    	    $pos = strpos($postit, '<span');
    	    $renewals = strip_tags(substr($renewalsString, $pos));
        }
    	return $renewals;
    }
    /**
     * ********
     * Renew item(s)
     * 
     */
    public function renew($recordId)
    {
        $URL = "/loan/DB=1/LNG=DU/USERINFO";
        $POST = array("ACT" => "UI_RENEWLOAN", "BOR_U" => $_SESSION['picauser']->username, "BOR_PW" => $_SESSION['picauser']->cat_password);
        if (is_array($recordId) === true) {
            foreach ($recordId as $rid) {
                array_push($POST['VB'], $recordId);
            }
        }
        else {
            $POST['VB'] = $recordId;
        }
        $postit = $this->postit($URL, $POST);

        return true;
    }

    /**
     * ********
     * Get any fines outstanding on a patron account.
     * 
     */
    public function getMyFines($patron)
    {
        // The patron comes as an array...
        $p = $patron[0];
        $URL = "/loan/DB=1/LNG=DU/USERINFO";
        $POST = array("ACT" => "UI_LOC", "BOR_U" => $_SESSION['picauser']->username, "BOR_PW" => $_SESSION['picauser']->cat_password);
        $postit = $this->postit($URL, $POST);

        // How many items are there?
        $holds = substr_count($postit, '<td class="plain"')/3;
        $ppns = array();
        $fineDate = array();
        $description = array();
        $fine = array();
        $position = strpos($postit, '<td class="infotab2" align="left">Betrag<td>');
        for ($i = 0; $i < $holds; $i++) {
        	$pos = strpos($postit, '<td class="plain"', $position);
        	// first class=plain => description
        	// length = position of next </td> - startposition
        	$nextClosingTd = strpos($postit, '</td>', $pos);
        	$description[$i] = substr($postit, $pos+18, ($nextClosingTd-$pos-18));
        	$position = $pos + 1;
        	// next class=plain => date of fee creation
        	$pos = strpos($postit, '<td class="plain"', $position);
        	$nextClosingTd = strpos($postit, '</td>', $pos);
        	$fineDate[$i] = substr($postit, $pos+18, ($nextClosingTd-$pos-18));
        	$position = $pos + 1;
        	// next class=plain => amount of fee
        	$pos = strpos($postit, '<td class="plain"', $position);
        	$nextClosingTd = strpos($postit, '</td>', $pos);
        	$fineString = substr($postit, $pos+32, ($nextClosingTd-$pos-32));
        	$feeString = explode(',', $fineString);
        	$feeString[1] = substr($feeString[1], 0, 2);
        	$fine[$i] = (double) implode('', $feeString);
        	$position = $pos + 1;
        }

        $fineList = array();
        for ($i = 0; $i < $holds; $i++) {
            $fineList[] = array(
                "amount"   => $fine[$i],
                "checkout" => "",
                "fine"     => $fineDate[$i] . ': ' . utf8_encode(html_entity_decode($description[$i])),
                "duedate"  => ""
            );
            // id should be the ppn of the book resulting the fine
            // but theres currently no way to find out the PPN (we have neither barcode nor signature...)
        }
        return $fineList;
    }

    /**
     * ********
     * Show any unsatisfied requests for the parton.
     * 
     */
    public function getMyHolds($patron)
    {
        $URL = "/loan/DB=1/LNG=DU/USERINFO";
        $POST = array("ACT" => "UI_LOR", "BOR_U" => $_SESSION['picauser']->username, "BOR_PW" => $_SESSION['picauser']->cat_password);
        $postit = $this->postit($URL, $POST);

        // How many items are there?
        $holds = substr_count($postit, 'input type="checkbox" name="VB"');
        $ppns = array();
        $creation = array();
        $position = strpos($postit, 'input type="checkbox" name="VB"');
        for ($i = 0; $i < $holds; $i++) {
        	$pos = strpos($postit, 'value=', $position);
        	$value = substr($postit, $pos+11, 8);
        	$ppns[] = $this->getPpnByBarcode($value);
        	$position = $pos + 1;
        	$position_create = $position;
        	for ($n = 0; $n<3; $n++) {
        	    $position_create = strpos($postit, '<td class="value-small">', $position_create+1);
        	}
        	$creation[] = substr($postit, $position_create+24, 10);
        }
        /* items, which are ordered and have no signature yet, are not included in the for-loop 
         * getthem by checkbox PPN
         */
        $moreholds = substr_count($postit, 'input type="checkbox" name="PPN"');
        $position = strpos($postit, 'input type="checkbox" name="PPN"');
        for ($i = 0; $i < $moreholds; $i++) {
        	$pos = strpos($postit, 'value=', $position);
        	// get the length of PPN
       		$x = strpos($postit, '"', $pos+7);
        	$value = substr($postit, $pos+7, $x-$pos-7);
        	// problem: the value presnted here does not contain the checksum!
        	// so its not a valid identifier
        	// we need to calculate the checksum
        	$checksum = 0;
            for ($i=0; $i<strlen($value);$i++) {
                $checksum += $value[$i]*(9-$i);
            }
            if ($checksum%11 === 1) $checksum = 'X';
            else if ($checksum%11 === 0) $checksum = 0;
            else $checksum = 11 - $checksum%11;
        	$ppns[] = $value.$checksum;
        	$position = $pos + 1;
        	$position_create = $position;
        	for ($n = 0; $n<3; $n++) {
        	    $position_create = strpos($postit, '<td class="value-small">', $position_create+1);
        	}
        	$creation[] = substr($postit, $position_create+24, 10);
        }

        /* media ordered from closed stack is not visible on the UI_LOR page requested above...
         * we need to do another request and filter the UI_LOL-page for requests
         */
        $POST_LOL = array("ACT" => "UI_LOL", "BOR_U" => $_SESSION['picauser']->username, "BOR_PW" => $_SESSION['picauser']->cat_password);
        $postit_lol = $this->postit($URL, $POST_LOL);

        $requests = substr_count($postit_lol, '<td class="value-small">bestellt</td>');
        $position = 0;
        for ($i = 0; $i < $requests; $i++) {
        	$position = strpos($postit_lol, '<td class="value-small">bestellt</td>', $position+1);
        	$pos = strpos($postit_lol, '<td class="value-small">', ($position-100));
        	$nextClosingTd = strpos($postit_lol, '</td>', $pos);
        	$value = substr($postit_lol, $pos+27, ($nextClosingTd-$pos-27));
        	$ppns[] = $this->getPpnByBarcode($value);
        	$creation[] = 'today';
        }
        
        for ($i = 0; $i < ($holds+$moreholds+$requests); $i++) {
            $holdList[] = array(
                "id"       => $ppns[$i],
                "create"   => $creation[$i]
            );
        }
        return $holdList;
    }

    /**
     * ********
     * TODO: implement it for PICA
     * Make a request on a specific record
     */
    public function placeHold($recordId, $patronId, $comment, $type)
    {
        $hold=false;
        return $hold;
    }
    
    /**
     * get new items from library system
     * TODO: implement it for PICA
     */
    public function getFunds() {
    	return null;
    }


    // private functions to connect to PICA

    /**
     * post something to a foreign host
     */
	private function postit($file, $data_to_send) 
	{
		// Parameter verarbeiten
		#print_r($data_to_send); # Zum Debuggen
		foreach ($data_to_send as $key => $dat)
		{
			$data_to_send[$key] = "$key=".rawurlencode(utf8_encode(stripslashes($dat)));
		}
		$postData = implode("&", $data_to_send);
		
		// HTTP-Header vorbereiten
		$out  = "POST $file HTTP/1.1\r\n";
		$out .= "Host: " . $this->catalogHost . "\r\n";
		$out .= "Content-type: application/x-www-form-urlencoded\r\n";
		$out .= "Content-length: ". strlen($postData) ."\r\n";
		$out .= "User-Agent: ".$_SERVER["HTTP_USER_AGENT"]."\r\n";
		$out .= "Connection: Close\r\n";
		$out .= "\r\n";
		$out .= $postData;
		if (!$conex = @fsockopen($this->catalogHost, "80", $errno, $errstr, 10)) return 0;
		fwrite($conex, $out);
		$data = '';
		while (!feof($conex)) {
			$data .= fgets($conex, 512);
		}
		fclose($conex);
		return $data;
	}


    /**
     * gets a PPN by its barcode
     */
    private function getPpnByBarcode($barcode) {
    	$searchUrl = "http://" . $this->catalogHost . "/DB=1/XML=1.0/CMD?ACT=SRCHA&IKT=1016&SRT=YOP&TRM=sgn+$barcode";
    	$doc = new DomDocument();
        $doc->load($searchUrl);
        // get Availability information from DAIA
        $itemlist = $doc->getElementsByTagName('SHORTTITLE');
        if (count($itemlist->item(0)->attributes) > 0) {
            $ppn = $itemlist->item(0)->attributes->getNamedItem('PPN')->nodeValue;
        }
        else {
        	return false;
        }
    	return $ppn;
    }

    /**
     * gets holdings of magazin and journal exemplars
     */
    public function getJournalHoldings($ppn) {
    	$searchUrl = "http://" . $this->catalogHost . "/DB=1/XML=1.0/SET=1/TTL=1/FAM?PPN=".$ppn."&SHRTST=10000";
    	$doc = new DomDocument();
        $doc->load($searchUrl);
        $itemlist = $doc->getElementsByTagName('SHORTTITLE');
		$ppn = array();
        for ($n = 0; $itemlist->item($n); $n++) {
            if (count($itemlist->item($n)->attributes) > 0) {
                $ppn[] = $itemlist->item($n)->attributes->getNamedItem('PPN')->nodeValue;
            }
        }
    	return $ppn;
    }

    // private authentication functions
    // adopted from Authentication class
    // we are using LDAP for authentication - not OCLC PICA standard
    
    private function bindUser(){
        $ldapConnectionParameter = $this->ldapConfigurationParameter->getParameter();

        // Try to connect to LDAP and die if we can't; note that some LDAP setups
        // will successfully return a resource from ldap_connect even if the server
        // is unavailable -- we need to check for bad return values again at search 
        // time!
        $ldapConnection = @ldap_connect($ldapConnectionParameter['host'], 
            $ldapConnectionParameter['port']);
        if (!$ldapConnection) {
            return new PEAR_ERROR('Unable to connect to LDAP server.');
        }

        // Set LDAP options -- use protocol version 3 and then initiate TLS so we 
        // can have a secure connection over the standard LDAP port.
        @ldap_set_option($ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
        if (!@ldap_start_tls($ldapConnection)) {
            return new PEAR_ERROR('Problem starting LDAP TLS.');
        }

        // If bind_username and bind_password were supplied in the config file, use
        // them to access LDAP before proceeding.  In some LDAP setups, these 
        // settings can be excluded in order to skip this step.
        if (isset($ldapConnectionParameter['bind_username']) && 
            isset($ldapConnectionParameter['bind_password'])) {
            $ldapBind = @ldap_bind($ldapConnection, $ldapConnectionParameter['bind_username'], 
                $ldapConnectionParameter['bind_password']);
            if (!$ldapBind) {
                return new PEAR_ERROR('Unable to bind to LDAP server.');
            }
        }

        // Search for username
        $ldapFilter = $ldapConnectionParameter['username'] . '=' . $this->username;
        $ldapSearch = @ldap_search($ldapConnection, $ldapConnectionParameter['basedn'],
            $ldapFilter);
        if (!$ldapSearch) {
            return new PEAR_ERROR('Unable to connect to LDAP server.');
        }
        
        $info = ldap_get_entries($ldapConnection, $ldapSearch);
        if ($info['count']) {
            // Validate the user credentials by attempting to bind to LDAP:
            $ldapBind = @ldap_bind($ldapConnection, $info[0]['dn'], $this->password);
            if ($ldapBind){
                // If the bind was successful, we can look up the full user info:
                $ldapSearch = ldap_search($ldapConnection, $ldapConnectionParameter['basedn'],
                    $ldapFilter);
                $data = ldap_get_entries($ldapConnection, $ldapSearch);
                return $this->processLDAPUser($data, $ldapConnectionParameter);
            }
        }

        return new PEAR_ERROR('Username or password wrong! Access denied.');
    }

    private function processLDAPUser($data, $ldapConnectionParameter){
        $user = array();
        $user['username'] = $this->username;
		for ($i=0; $i<$data["count"];$i++) {
            for ($j=0;$j<$data[$i]["count"];$j++){
        
                if(($data[$i][$j] == $ldapConnectionParameter['firstname']) &&
                    ($ldapConnectionParameter['firstname'] != "")) {
                    $user['firstname'] = $data[$i][$data[$i][$j]][0];
                }

                if($data[$i][$j] == $ldapConnectionParameter['lastname'] &&
                    ($ldapConnectionParameter['lastname'] != "")) {
                    $user['lastname'] = $data[$i][$data[$i][$j]][0];
                }

                if($data[$i][$j] == $ldapConnectionParameter['email'] &&
                    ($ldapConnectionParameter['email'] != "")) {
                     $user['email'] = $data[$i][$data[$i][$j]][0];
                }

                if($data[$i][$j] == $ldapConnectionParameter['cat_username'] &&
                    ($ldapConnectionParameter['cat_username'] != "")) {
                     $user['cat_username'] = $data[$i][$data[$i][$j]][0];
                }
            }
        }
        // do not store cat_password into database, but assign it to Session user
        $sessionuser = new User();
        $sessionuser->username = $this->username;
        $sessionuser->cat_password = $this->password;
        $_SESSION['picauser'] = $sessionuser;
        return $user;
    }

    private function getUserdata($data, $ldapConnectionParameter){
        $user = new User();
        $user->username = $this->username;
		for ($i=0; $i<$data["count"];$i++) {
            for ($j=0;$j<$data[$i]["count"];$j++){
        
                if(($data[$i][$j] == $ldapConnectionParameter['firstname']) &&
                    ($ldapConnectionParameter['firstname'] != "")) {
                    $user->firstname = $data[$i][$data[$i][$j]][0];
                }

                if($data[$i][$j] == $ldapConnectionParameter['lastname'] &&
                    ($ldapConnectionParameter['lastname'] != "")) {
                    $user->lastname = $data[$i][$data[$i][$j]][0];
                }

                if($data[$i][$j] == $ldapConnectionParameter['email'] &&
                    ($ldapConnectionParameter['email'] != "")) {
                     $user->email = $data[$i][$data[$i][$j]][0];
                }

                if($data[$i][$j] == $ldapConnectionParameter['cat_username'] &&
                    ($ldapConnectionParameter['cat_username'] != "")) {
                     $user->cat_username = $data[$i][$data[$i][$j]][0];
                }

                if($data[$i][$j] == $ldapConnectionParameter['address'] &&
                    ($ldapConnectionParameter['address'] != "")) {
                     $user->address = $data[$i][$data[$i][$j]][0];
                }

                if($data[$i][$j] == $ldapConnectionParameter['phone'] &&
                    ($ldapConnectionParameter['phone'] != "")) {
                     $user->phone = $data[$i][$data[$i][$j]][0];
                }

                if($data[$i][$j] == $ldapConnectionParameter['group'] &&
                    ($ldapConnectionParameter['group'] != "")) {
                     $user->group = $data[$i][$data[$i][$j]][0];
                }
            }
        }
        return $user;
    }
    
    private function getUserdataFromLdap() {
        $ldapConnectionParameter = $this->ldapConfigurationParameter->getParameter();

        // Try to connect to LDAP and die if we can't; note that some LDAP setups
        // will successfully return a resource from ldap_connect even if the server
        // is unavailable -- we need to check for bad return values again at search 
        // time!
        $ldapConnection = @ldap_connect($ldapConnectionParameter['host'], 
            $ldapConnectionParameter['port']);
        if (!$ldapConnection) {
            return new PEAR_ERROR('Unable to connect to LDAP server.');
        }

        // Set LDAP options -- use protocol version 3 and then initiate TLS so we 
        // can have a secure connection over the standard LDAP port.
        @ldap_set_option($ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
        if (!@ldap_start_tls($ldapConnection)) {
            return new PEAR_ERROR('Problem starting LDAP TLS.');
        }

        // If bind_username and bind_password were supplied in the config file, use
        // them to access LDAP before proceeding.  In some LDAP setups, these 
        // settings can be excluded in order to skip this step.
        if (isset($ldapConnectionParameter['bind_username']) && 
            isset($ldapConnectionParameter['bind_password'])) {
            $ldapBind = @ldap_bind($ldapConnection, $ldapConnectionParameter['bind_username'], 
                $ldapConnectionParameter['bind_password']);
            if (!$ldapBind) {
                return new PEAR_ERROR('Unable to bind to LDAP server.');
            }
        }

        // Search for username
        $ldapFilter = $ldapConnectionParameter['username'] . '=' . $this->username;
        $ldapSearch = @ldap_search($ldapConnection, $ldapConnectionParameter['basedn'],
            $ldapFilter);
        if (!$ldapSearch) {
            return new PEAR_ERROR('Unable to connect to LDAP server.');
        }
        
        $info = ldap_get_entries($ldapConnection, $ldapSearch);
        if ($info['count']) {
            // Validate the user credentials by attempting to bind to LDAP:
            $ldapBind = @ldap_bind($ldapConnection, $info[0]['dn'], $this->password);
            if ($ldapBind){
                // If the bind was successful, we can look up the full user info:
                $ldapSearch = ldap_search($ldapConnection, $ldapConnectionParameter['basedn'],
                    $ldapFilter);
                $data = ldap_get_entries($ldapConnection, $ldapSearch);
                return $this->getUserdata($data, $ldapConnectionParameter);
            }
        }
    }
}
?>