<?php
/**
 *
 * Copyright (C) UB/FU Berlin
 *
 * last update: 7.11.2007
 * tested with X-Server Aleph 18.1.
 *
 * TODO: login, course information, getNewItems, duedate in holdings, https connection to x-server, ...
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
require_once 'Interface.php';

class Aleph implements DriverInterface
{
    private $db;
    private $dbName;
    
    function __construct()
    {
        // Load Configuration for this Module
        $configArray = parse_ini_file('conf/Aleph.ini', true);
        
        $this->host = $configArray['Catalog']['host'];
        $this->bib = $configArray['Catalog']['bib'];
        $this->useradm = $configArray['Catalog']['useradm'];
        $this->admlib = $configArray['Catalog']['admlib'];
        $this->loanlib = $configArray['Catalog']['loanlib'];
        $this->wwwuser = $configArray['Catalog']['wwwuser'];
        $this->wwwpasswd = $configArray['Catalog']['wwwpasswd'];
        $this->sublibadm = $configArray['sublibadm'];
        $this->logo = $configArray['logo'];

    }
    
    public function getStatus($id)
    {
        $holding = array();

        $tagmatch = "cbscindeloreis";
        $request = "http://$this->host/X?op=circ-status&library=$this->bib&sys_no=$id&user_name=$this->wwwuser&user_password=$this->wwwpasswd";
        $answer = file($request);
        $xmlfile = "";
        foreach($answer as $line) {
           // transform the misspelled xml-tags:
           if (preg_match("|^<[$tagmatch]|i", $line) || preg_match("|^</|i", $line)){
                   $line = preg_replace("/-/i", "_", $line);
           }
           $xmlfile = $xmlfile . $line;
        }
        $xml = simplexml_load_string($xmlfile);
        $max = substr_count($xmlfile, "<item_data>");
        for($i=0;$i < $max ; $i++){
            $status = $xml->item_data[$i]->loan_status;
            $location = $xml->item_data[$i]->sub_library;
            $collection = $xml->item_data[$i]->collection;
            $reserve = '-';
            $description = $xml->item_data[$i]->{'z30-description'};
            if ($description == '') {
                $number = $xml->item_data[$i]->barcode;
                $pos = strpos($number, '_');
                if (!($pos === false)) {
                    $number = substr($number, $pos + 1);
                }
            } else {
                $number = $description;
            }
            $callnumber = $xml->item_data[$i]->location;
            $duedate = $xml->item_data[$i]->due_date;
            $availability = false;
            if ($duedate == "On Shelf") {
                $availability = true;
                $duedate = NULL;
            }
            if (strlen($collection)) {
                $location .= ' ' . $collection;
            }
            $barcode = $xml->item_data[$i]->barcode;
            $holding[] = array('id' => $id,
                               'availability' => $availability,
                               'status' => (string) $status,
                               'location' => (string) $location,
                               'reserve' => $reserve,
                               'callnumber' => (string) $callnumber,
                               'duedate' => (string) $duedate,
                               'number' => (string) $number,
                               'barcode' => (string) $barcode);
        }
        return $holding;
    }
    
    public function getStatuses($idList)
    {
        foreach ($idList as $id) {
            $holdings[] = $this->getHolding($id);
        }
        return $holdings;
    }

    public function getHolding($id)
    {
        return $this->getStatus($id);
    }

    public function getHoldings($id)
    {
        return $this->getStatus($id);
    }

    public function getPurchaseHistory($id)
    {
        return array();
    }
    
    public function patronLogin($barcode, $lname)
    {
        $xmlfile = "";
        $tagmatch = "z";
        $request = "http://$this->host/X?op=bor-auth&library=$this->useradm&bor_id=$barcode&verification=$lname&user_name=$this->wwwuser&user_password=$this->wwwpasswd";
        $answer = file($request);
        $patron = NULL;
        foreach($answer as $line) {
            // transform the misspelled xml-tags:
            if (preg_match("|^<[$tagmatch]|i", $line) || preg_match("|^</[$tagmatch]|i", $line)) {
                $line = preg_replace("/-/i", "_", $line);
            }
            $xmlfile = $xmlfile . $line;
        }
        $xml = simplexml_load_string($xmlfile);
        if ($xml->error != '') {
            if((string)$xml->error != "Error in Verification") {
                $patron = new PEAR_Error($xml->error);
            }
        } else {
            $patron=array();
            $firstName = "";
            $lastName = "";
            // Assumes names stored in the format 'Surname, First names'  If this
            // isn't the case alter the regular expression and the two assignments.
            if (preg_match("/^(\w+)\s*,\s*(\w+)/", $xml->z303->z303_name, $matches))
            {
                $firstName = $matches[2];
                $lastName = $matches[1];
            }
            // This value was originally used in place of $barcode in generating the
            // $patron array below, but that approach failed with some Aleph
            // configurations; using $barcode instead of $username seems more
            // reliable:
            //$username = $xml->z303->z303_id;
            $email_addr = $xml->z304->z304_email_address;
            $home_lib = $xml->z303->z303_home_library;
            // Default the college to the useradm library and overwrite it if the
            // home_lib exists
            $patron['college'] = $this->useradm;
            if (($home_lib != '') && (array_key_exists("$home_lib",$this->sublibadm))) {
                if ($this->sublibadm["$home_lib"] != '') {
                    $patron['college'] = $this->sublibadm["$home_lib"];
                }
            }
            $patron['id'] = $barcode;
            $patron['firstname'] = $firstName;
            $patron['lastname'] = $lastName;
            $patron['cat_username'] = $barcode;
            $patron['cat_password'] = "$lname";
            $patron['email'] = "$email_addr";
            $patron['major'] = NULL;
        }
        return $patron;
    }
    
    public function getMyTransactions($user)
    {
        $tagmatch = "cbscindeloreisz";
        $transList = array();
        $request = "http://$this->host/X?op=bor-info&library=" . $user['college'] . 
            "&bor_id=" . $user['cat_username'] . 
            "&user_name=$this->wwwuser&user_password=$this->wwwpasswd";
        $answer = file($request);
        $xmlfile = NULL;
        foreach($answer as $line){
            // transform the misspelled xml-tags:
            if (preg_match("|^<[$tagmatch]|i", $line) || preg_match("|^</[$tagmatch]|i", $line)) {
                $line = preg_replace("/-/i", "_", $line);
            }
            $xmlfile = $xmlfile . $line;
        }
        $xml = simplexml_load_string($xmlfile);
        $max = substr_count($xmlfile, "<item_l>");
        for($i=0;$i < $max ; $i++){
            $id = str_pad($xml->item_l[$i]->z13->z13_doc_number, 9, '0', STR_PAD_LEFT);
            $duedate = $xml->item_l[$i]->z36->z36_due_date;
            $transList[] = array('duedate' => (string) $duedate,
                                 'id' => $id);
        }
        return $transList;
    }
    
    public function getMyHolds($user)
    {
        $tagmatch = "cbscindeloreisz";
        $holdList = array();
        $request = "http://$this->host/X?op=bor-info&loans=N&holds=Y&cash=N&library=" .
            $user['college'] . "&bor_id=" . $user['id'] .
            "&user_name=$this->wwwuser&user_password=$this->wwwpasswd";
        $answer = file($request);
        $xmlfile = "";
        foreach($answer as $line){
            // transform the misspelled xml-tags:
            if (preg_match("|^<[$tagmatch]|i", $line) || preg_match("|^</[$tagmatch]|i", $line)) {
                $line = preg_replace("/-/i", "_", $line);
            }
            $xmlfile = $xmlfile . $line;
        }
        $xml = simplexml_load_string($xmlfile);
        $max = substr_count($xmlfile, "<item_h>");
        for($i=0;$i < $max ; $i++){
            if ((string)$xml->item_h[$i]->z37->z37_request_type == "H") {
                $type = "hold";
                $id = (string)$xml->item_h[$i]->z37->z37_doc_number;
                $location = (string)$xml->item_h[$i]->z37->z37_pickup_location;
                $reqnum = (string)$xml->item_h[$i]->z37->z37_doc_number .
                    (string)$xml->item_h[$i]->z37->z37_item_sequence.(string)$xml->item_h[$i]->z37->z37_sequence;
                $expire = (string)$xml->item_h[$i]->z37->z37_end_request_date;
                $create = (string)$xml->item_h[$i]->z37->z37_open_date;
                $holdList[] = array('type' => $type,
                                    'id' => $id,
                                    'location' => $location,
                                    'reqnum' => $reqnum,
                                    'expire' => $expire,
                                    'create' => $create);
            }
        }
        return $holdList;
    }
    
    public function getMyFines($user)
    {
        $tagmatch = "cbscindeloreisz";
        $finesList = array();
        $request = "http://$this->host/X?op=bor-info&loans=N&hold=N&cash=Y&library=" .
            $user['college'] . "&bor_id=" . $user['id'] .
            "&verification=&user_name=$this->wwwuser&user_password=$this->wwwpasswd";
        $answer = file($request);
        $xmlfile = "";
        foreach($answer as $line){
            // transform the misspelled xml-tags:
            if (preg_match("|^<[$tagmatch]|i", $line) || preg_match("|^</[$tagmatch]|i", $line)) {
                $line = preg_replace("/-/i", "_", $line);
            }
            $xmlfile = $xmlfile . $line;
        }
        $xml = simplexml_load_string($xmlfile);
        $max = substr_count($xmlfile, "<fine>");

        for($i=0;$i < $max ; $i++){
            if (preg_match("/not paid/i",(string)$xml->fine[$i]->z31->z31_status)) {
                $description = preg_replace("/paid.*/i" ,"", (string) $xml->fine[$i]->z31->z31_description);
                $balance = (int)((float) preg_replace("/[\(\)]/", "", (string) $xml->fine[$i]->z31->z31_sum) * 100);
                if (preg_match_all("/(\d+\.\d{2})/", (string) $xml->fine[$i]->z31->z31_description, $matches)) {
                    $fine = (int)((float)$matches[0][1]*100);
                } else {
                    $fine = $balance;
                }
                $id = (string) $xml->fine[$i]->z30->z30_doc_number;
                // Note Aleph's X-Server doesn't tell us when the book was checked out or due back, just when the fine was issued.
                $finesList[] = array(
                    "amount"   => $fine,
                    "checkout" => "",
                    "fine"     => $description,
                    "balance"  => $balance,
                    "duedate"  => "",
                    "id"       => sprintf("%09d",$id) );
            }
        }
        return $finesList;
    }
    
    public function placeHold($recordId, $patronId, $comment, $type)
    {
        $hold=false;
        return $hold;
    }

    public function getNewItems($page, $limit, $startdate, $enddate, $fundId = null)
    {
        $items = array();
        return $items;
    }
    
    function getDepartments()
    {
        $deptList = array();
        return $deptList;
    }
    
    function getInstructors()
    {
        $deptList = array();
        return $deptList;
    }
    
    function getCourses()
    {
        $deptList = array();
        return $deptList;
    }

    function findReserves($course, $inst, $dept)
    {
        $recordList = array();
        return $recordList;
    }


    function getMyProfile($user)
    {
        $recordList=array();
        $tagmatch = "cbscindeloreisz";
        $transList = array();
        $request = "http://$this->host/X?op=bor-info&loans=N&cash=N&hold=N&library=" .
            $user['college'] . "&bor_id=" . $user['cat_username'] .
            "&user_name=$this->wwwuser&user_password=$this->wwwpasswd";
        $answer = file($request);
        $xmlfile = NULL;
        foreach($answer as $line){
            // transform the misspelled xml-tags:
            if (preg_match("|^<[$tagmatch]|i", $line) || preg_match("|^</[$tagmatch]|i", $line)) {
                $line = preg_replace("/-/i", "_", $line);
            }
            $xmlfile = $xmlfile . $line;
        }
        $xml = simplexml_load_string($xmlfile);
        $address1 = (string)$xml->z304->z304_address_1;
        $address2 = (string)$xml->z304->z304_address_2;
        $zip = (string)$xml->z304->z304_zip;
        $phone = (string)$xml->z304->z304_telephone;
        $group = (string)$xml->z305->z305_bor_status;

        // firstname
        $recordList['firstname'] = $user['firstname'];
        // lastname
        $recordList['lastname'] = $user['lastname'];
        // address1
        $recordList['address1'] = $address1;
        // address2
        $recordList['address2'] = $address2;
        // zip (Post Code)
        $recordList['zip'] = $zip;
        // phone
        $recordList['phone'] = $phone;
        // group
        $recordList['group'] = $group;
        return $recordList;
    }
}

?>
