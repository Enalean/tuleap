<?php
/**
 *
 * Copyright (C) Villanova University 2007.
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
require_once 'sys/Proxy_Request.php';

class Voyager implements DriverInterface
{
    private $db;
    private $dbName;
    private $config;
    private $statusRankings = false;        // used by pickStatus() method
    
    function __construct()
    {
        // Load Configuration for this Module
        $this->config = parse_ini_file('conf/Voyager.ini', true);
        
        // Define Database Name
        $this->dbName = $this->config['Catalog']['database'];
        
        // Based on the configuration file, use either "SID" or "SERVICE_NAME"
        // to connect (correct value varies depending on Voyager's Oracle setup):
        $connectType = isset($this->config['Catalog']['connect_with_sid']) &&
            $this->config['Catalog']['connect_with_sid'] ?
            'SID' : 'SERVICE_NAME';
        
        $tns = '(DESCRIPTION=' .
                 '(ADDRESS_LIST=' .
                   '(ADDRESS=' .
                     '(PROTOCOL=TCP)' . 
                     '(HOST=' . $this->config['Catalog']['host'] . ')' .
                     '(PORT=' . $this->config['Catalog']['port'] . ')' .
                   ')' .
                 ')' . 
                 '(CONNECT_DATA=' .
                   '(' . $connectType . '=' . $this->config['Catalog']['service'] . ')' .
                 ')' . 
               ')';
        try {
            $this->db = new PDO("oci:dbname=$tns",
                                $this->config['Catalog']['user'],
                                $this->config['Catalog']['password']);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Private support method to pick which status message to display when multiple
     * options are present.
     *
     * @param   array   $statusArray    Array of status messages to choose from.
     * @access  private
     * @return  string                  The best status message to display.
     */
    private function pickStatus($statusArray)
    {
        // This array controls the rankings of possible status messages.  The lower
        // the ID in the ITEM_STATUS_TYPE table, the higher the priority of the
        // message.  We only need to load it once -- after that, it's cached in the
        // driver.
        if ($this->statusRankings == false) {
            // Execute SQL
            $sql = "SELECT * FROM $this->dbName.ITEM_STATUS_TYPE";
            try {
                $sqlStmt = $this->db->prepare($sql);
                $sqlStmt->execute();
            } catch (PDOException $e) {
                return new PEAR_Error($e->getMessage());
            }
            
            // Read results
            while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
                $this->statusRankings[$row['ITEM_STATUS_DESC']] = $row['ITEM_STATUS_TYPE'];
            }            
        }
        
        // Pick the first entry by default, then see if we can find a better match:
        $status = $statusArray[0];
        $rank = $this->statusRankings[$status];
        for ($x = 1; $x < count($statusArray); $x++) {
            if ($this->statusRankings[$statusArray[$x]] < $rank) {
                $status = $statusArray[$x];
            }
        }
        
        return $status;
    }
    
    /**
     * Private support method to take an array of status strings and determine
     * whether or not this indicates an available item.  Returns an array with
     * two keys: 'available', the boolean availability status, and 'otherStatuses',
     * every status code found other than "Not Charged" - for use with pickStatus().
     *
     * @param   array   $statusArray    The status codes to analyze.
     * @access  private
     * @return  array                   Availability and other status information.
     */    
    private function determineAvailability($statusArray)
    {
        // It's possible for a record to have multiple status codes.  We
        // need to loop through in search of the "Not Charged" (i.e. on 
        // shelf) status, collecting any other statuses we find along the
        // way...
        $notCharged = false;
        $otherStatuses = array();
        foreach ($statusArray as $status) {
            switch ($status) {
                case 'Not Charged':
                    $notCharged = true;
                    break;
                default:
                    $otherStatuses[] = $status;
                    break;
            }
        }
        
        // If we found other statuses or if we failed to find "Not Charged,"
        // the item is not available!
        $available = (count($otherStatuses) == 0 && $notCharged);

        return array('available' => $available, 'otherStatuses' => $otherStatuses);        
    }

    public function getStatus($id)
    {
        // There are two possible queries we can use to obtain status information.
        // The first (and most common) obtains information from a combination of
        // items and holdings records.  The second (a rare case) obtains 
        // information from the holdings record when no items are available.
        $items = "select ITEM.ITEM_ID, ITEM.ON_RESERVE, ITEM_STATUS_DESC as status, LOCATION.LOCATION_DISPLAY_NAME as location, MFHD_MASTER.DISPLAY_CALL_NO as callnumber " .
                 "from $this->dbName.BIB_ITEM, $this->dbName.ITEM, $this->dbName.ITEM_STATUS_TYPE, $this->dbName.ITEM_STATUS, $this->dbName.LOCATION, $this->dbName.MFHD_ITEM, $this->dbName.MFHD_MASTER " .
                 "where BIB_ITEM.BIB_ID = :id " .
                 "and BIB_ITEM.ITEM_ID = ITEM.ITEM_ID " .
                 "and ITEM.ITEM_ID = ITEM_STATUS.ITEM_ID " .
                 "and ITEM_STATUS.ITEM_STATUS = ITEM_STATUS_TYPE.ITEM_STATUS_TYPE " .
                 "and LOCATION.LOCATION_ID = ITEM.PERM_LOCATION " .
                 "and MFHD_ITEM.ITEM_ID = ITEM.ITEM_ID " .
                 "and MFHD_MASTER.SUPPRESS_IN_OPAC='N' " .
                 "and MFHD_MASTER.MFHD_ID = MFHD_ITEM.MFHD_ID";
        $noItems = "select 1 as ITEM_ID, 'N' as ON_RESERVE, 'No information available' as status, LOCATION.LOCATION_DISPLAY_NAME as location, MFHD_MASTER.DISPLAY_CALL_NO as callnumber " .
                   "from $this->dbName.BIB_MFHD, $this->dbName.LOCATION, $this->dbName.MFHD_MASTER " .
                   "where BIB_MFHD.BIB_ID = :id " .
                   "and LOCATION.LOCATION_ID = MFHD_MASTER.LOCATION_ID " .
                   "and MFHD_MASTER.SUPPRESS_IN_OPAC='N' " .
                   "and MFHD_MASTER.MFHD_ID = BIB_MFHD.MFHD_ID";
        $possibleQueries = array($items, $noItems);
        
        // Loop through the possible queries and try each in turn -- the first one
        // that yields results will cause the function to return.
        foreach ($possibleQueries as $sql) {
            // Execute SQL
            try {
                $sqlStmt = $this->db->prepare($sql);
                $sqlStmt->execute(array(':id' => $id));
            } catch (PDOException $e) {
                return new PEAR_Error($e->getMessage());
            }
    
            // Build Array of Item Information
            $data = array();
            while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
                if (!isset($data[$row['ITEM_ID']])) {
                    $data[$row['ITEM_ID']] = array(
                        'id' => $id,
                        'status' => $row['STATUS'],
                        'status_array' => array($row['STATUS']),
                        'location' => htmlentities($row['LOCATION']),
                        'reserve' => $row['ON_RESERVE'],
                        'callnumber' => $row['CALLNUMBER']
                    );
                } else {
                    if (!in_array($row['STATUS'], $data[$row['ITEM_ID']]['status_array'])) {
                        $data[$row['ITEM_ID']]['status_array'][] = $row['STATUS'];
                    }
                }
            }
    
            // If we found any information, break out of the foreach loop;
            // we don't need to try any more queries.
            if (count($data) > 0) {
                break;
            }
        }

        // Process the raw data into final status information:
        $status = array();
        foreach ($data as $current) {
            // Get availability/status info based on the array of status codes:
            $availability = $this->determineAvailability($current['status_array']);
            
            // If we found other statuses, we should override the display value
            // appropriately:
            if (count($availability['otherStatuses']) > 0) {
                $current['status'] = $this->pickStatus($availability['otherStatuses']);
            }
            $current['availability'] = $availability['available'];
            $status[] = $current;
        }

        return $status;
    }

    public function getStatuses($idList)
    {
        $status = array();
        foreach ($idList as $id) {
            $status[] = $this->getStatus($id);
        }
        return $status;
    }

    public function getHolding($id)
    {
        require_once 'File/MARC.php';

        // There are two possible queries we can use to obtain status information.
        // The first (and most common) obtains information from a combination of
        // items and holdings records.  The second (a rare case) obtains 
        // information from the holdings record when no items are available.
        $items = "select ITEM_BARCODE.ITEM_BARCODE, ITEM.ITEM_ID, MFHD_DATA.RECORD_SEGMENT, MFHD_ITEM.ITEM_ENUM, ITEM.ON_RESERVE, ITEM.ITEM_SEQUENCE_NUMBER, ITEM_STATUS_DESC as status, LOCATION.LOCATION_DISPLAY_NAME as location, MFHD_MASTER.DISPLAY_CALL_NO as callnumber, CIRC_TRANSACTIONS.CURRENT_DUE_DATE as duedate " .
                 "from $this->dbName.BIB_ITEM, $this->dbName.ITEM, $this->dbName.ITEM_STATUS_TYPE, $this->dbName.ITEM_STATUS, $this->dbName.LOCATION, $this->dbName.MFHD_ITEM, $this->dbName.MFHD_MASTER, $this->dbName.MFHD_DATA, $this->dbName.CIRC_TRANSACTIONS, $this->dbName.ITEM_BARCODE " .
                 "where BIB_ITEM.BIB_ID = :id " .
                 "and BIB_ITEM.ITEM_ID = ITEM.ITEM_ID " .
                 "and ITEM.ITEM_ID = ITEM_STATUS.ITEM_ID " .
                 "and ITEM_STATUS.ITEM_STATUS = ITEM_STATUS_TYPE.ITEM_STATUS_TYPE " .
                 "and ITEM_BARCODE.ITEM_ID (+)= ITEM.ITEM_ID " .
                 "and LOCATION.LOCATION_ID = ITEM.PERM_LOCATION " . 
                 "and CIRC_TRANSACTIONS.ITEM_ID (+)= ITEM.ITEM_ID " .
                 "and MFHD_ITEM.ITEM_ID = ITEM.ITEM_ID " . 
                 "and MFHD_MASTER.MFHD_ID = MFHD_ITEM.MFHD_ID " .
                 "and MFHD_DATA.MFHD_ID = MFHD_ITEM.MFHD_ID " .
                 "and MFHD_MASTER.SUPPRESS_IN_OPAC='N' " .
                 "order by ITEM.ITEM_SEQUENCE_NUMBER, MFHD_DATA.SEQNUM";
        $noItems = "select null as ITEM_BARCODE, null as ITEM_ID, MFHD_DATA.RECORD_SEGMENT, null as ITEM_ENUM, 'N' as ON_RESERVE, 1 as ITEM_SEQUENCE_NUMBER, 'No information available' as status, LOCATION.LOCATION_DISPLAY_NAME as location, MFHD_MASTER.DISPLAY_CALL_NO as callnumber, null as duedate " .
                   "from $this->dbName.BIB_MFHD, $this->dbName.LOCATION, $this->dbName.MFHD_MASTER, $this->dbName.MFHD_DATA " .
                   "where BIB_MFHD.BIB_ID = :id " .
                   "and LOCATION.LOCATION_ID = MFHD_MASTER.LOCATION_ID " .
                   "and MFHD_MASTER.MFHD_ID = BIB_MFHD.MFHD_ID " .
                   "and MFHD_DATA.MFHD_ID = BIB_MFHD.MFHD_ID " .
                   "and MFHD_MASTER.SUPPRESS_IN_OPAC='N' " .
                   "order by MFHD_DATA.SEQNUM";
        $possibleQueries = array($items, $noItems);

        // Loop through the possible queries and try each in turn -- the first one
        // that yields results will cause us to break out of the loop.
        foreach ($possibleQueries as $sql) {
            // Execute SQL
            try {
                $holding = array();
                $sqlStmt = $this->db->prepare($sql);
                $sqlStmt->execute(array(':id' => $id));
            } catch (PDOException $e) {
                return new PEAR_Error($e->getMessage());
            }
            
            // Build Holdings Array
            $i = 0;
            $data = array();
            while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
                // Determine Copy Number
                $number = ($row['ITEM_ENUM']) ? $row['ITEM_ENUM'] : $row['ITEM_SEQUENCE_NUMBER'];
    
                // Concat wrapped rows (MARC data more than 300 bytes gets split into multiple rows)
                if (isset($data[$row['ITEM_ID']]["$number"])) {
                    // We don't want to concatenate the same MARC information to itself over and
                    // over due to a record with multiple status codes -- we should only concat
                    // wrapped rows for the FIRST status code we encounter!
                    if ($data[$row['ITEM_ID']]["$number"]['STATUS_ARRAY'][0] == $row['STATUS']) {
                        $data[$row['ITEM_ID']]["$number"]['RECORD_SEGMENT'] .= $row['RECORD_SEGMENT'];
                    }
                    
                    // If we've encountered a new status code, we should track it:
                    if (!in_array($row['STATUS'], $data[$row['ITEM_ID']]["$number"]['STATUS_ARRAY'])) {
                        $data[$row['ITEM_ID']]["$number"]['STATUS_ARRAY'][] = $row['STATUS'];
                    }
                } else {
                    // This is the first time we've encountered this row number -- initialize
                    // the row and start an array of statuses.
                    $data[$row['ITEM_ID']]["$number"] = $row;
                    $data[$row['ITEM_ID']]["$number"]['STATUS_ARRAY'] = array($row['STATUS']);
                }
            }
    
            // If we found data, we can leave the foreach loop -- we don't need to try
            // any more queries.
            if (count($data) > 0) {
                break;
            }
        }

        foreach($data as $item) {
            foreach($item as $number => $row) {
                // Get availability/status info based on the array of status codes:
                $availability = $this->determineAvailability($row['STATUS_ARRAY']);
                
                // If we found other statuses, we should override the display value
                // appropriately:
                if (count($availability['otherStatuses']) > 0) {
                    $row['STATUS'] = $this->pickStatus($availability['otherStatuses']);
                }

                $holding[$i] = array('id' => $id,
                                     'availability' => $availability['available'],
                                     'status' => $row['STATUS'],
                                     'location' => htmlentities($row['LOCATION']),
                                     'reserve' => $row['ON_RESERVE'],
                                     'callnumber' => $row['CALLNUMBER'],
                                     'duedate' => $row['DUEDATE'],
                                     'number' => $number,
                                     'barcode' => $row['ITEM_BARCODE']);
    
                // Parse Holding Record
                if ($row['RECORD_SEGMENT']) {
                    try {
                        $marc = new File_MARC(str_replace(array("\n", "\r"), '', $row['RECORD_SEGMENT']), File_MARC::SOURCE_STRING);
                        if ($record = $marc->next()) {
                            // Get Notes
                            if ($fields = $record->getFields('852')) {
                                foreach ($fields as $field) {
                                    if ($subfields = $field->getSubfields('z')) {
                                        foreach($subfields as $subfield) {
                                            // If this is the first time through, assume a single-line summary
                                            if (!isset($holding[$i]['notes'])) {
                                                $holding[$i]['notes'] = $subfield->getData();
                                                // If we already have a summary line, convert it to an array and append more data
                                            } else {
                                                if (!is_array($holding[$i]['notes'])) {
                                                    $holding[$i]['notes'] = array($holding[$i]['notes']);
                                                }
                                                $holding[$i]['notes'][] = $subfield->getData();
                                            }
                                        }
                                    }
                                }
                            }
    
                            // Get Summary (may be multiple lines)
                            if ($fields = $record->getFields('866')) {
                                foreach ($fields as $field) {
                                    if ($subfield = $field->getSubfield('a')) {
                                        // If this is the first time through, assume a single-line summary
                                        if (!isset($holding[$i]['summary'])) {
                                            $holding[$i]['summary'] = $subfield->getData();
                                            // If we already have a summary line, convert it to an array and append more data
                                        } else {
                                            if (!is_array($holding[$i]['summary'])) {
                                                $holding[$i]['summary'] = array($holding[$i]['summary']);
                                            }
                                            $holding[$i]['summary'][] = $subfield->getData();
                                        }
                                    }
                                }
                            }
                        }
                    } catch (Exception $e) {
                        trigger_error('Poorly Formatted MFHD Record', E_USER_NOTICE);
                    }
                }
                
                $i++;
            }
        }

        return $holding;
    }
    
    /*
    public function getHoldings($idList)
    {
        $sql = "select ITEM.ON_RESERVE, ITEM.ITEM_SEQUENCE_NUMBER, ITEM_STATUS_DESC as status, LOCATION.LOCATION_DISPLAY_NAME as location, MFHD_MASTER.DISPLAY_CALL_NO as callnumber, CIRC_TRANSACTIONS.CURRENT_DUE_DATE as duedate " .
               "from $this->dbName.BIB_ITEM, $this->dbName.ITEM, $this->dbName.ITEM_STATUS_TYPE, $this->dbName.ITEM_STATUS, $this->dbName.LOCATION, $this->dbName.MFHD_ITEM, $this->dbName.MFHD_MASTER, $this->dbName.CIRC_TRANSACTIONS " .
               "where BIB_ITEM.ITEM_ID = ITEM.ITEM_ID " .
               "and ITEM.ITEM_ID = ITEM_STATUS.ITEM_ID " .
               "and ITEM_STATUS.ITEM_STATUS = ITEM_STATUS_TYPE.ITEM_STATUS_TYPE " .
               "and LOCATION.LOCATION_ID = ITEM.PERM_LOCATION " .
               "and CIRC_TRANSACTIONS.ITEM_ID (+)= ITEM.ITEM_ID " .
               "and MFHD_ITEM.ITEM_ID = ITEM.ITEM_ID " .
               "and MFHD_MASTER.MFHD_ID = MFHD_ITEM.MFHD_ID " .
               "and (";
        for ($i=0; $i<count($idList); $i++) {
            if ($i > 0) {
                $sql .= ' OR ';
            }
            $sql .= "BIB_ITEM.BIB_ID = '$idList[$i]'";
        }
        $sql .= ')';
        echo $sql;

        try {
            $holding = array();
            $sqlStmt = $this->db->prepare($sql);
            $sqlStmt->execute();
            while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
                switch ($row['STATUS']) {
                    case 'Not Charged':
                    case 'Cataloging Review':
                    case 'Circulation Review':
                        $available = true;
                        break;
                    default:
                        $available = false;
                        break;
                }

                $holding[] = array('availability' => $available,
                                   'status' => $row['STATUS'],
                                   'location' => $row['LOCATION'],
                                   'reserve' => $row['ON_RESERVE'],
                                   'callnumber' => $row['CALLNUMBER'],
                                   'duedate' => $row['DUEDATE'],
                                   'number' => $row['ITEM_SEQUENCE_NUMBER']);
            }
            return $holding;
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }
    }
    */

    public function getPurchaseHistory($id)
    {
        $sql = "select SERIAL_ISSUES.ENUMCHRON " .
               "from $this->dbName.SERIAL_ISSUES, $this->dbName.COMPONENT, $this->dbName.ISSUES_RECEIVED, $this->dbName.SUBSCRIPTION, $this->dbName.LINE_ITEM " .
               "where SERIAL_ISSUES.COMPONENT_ID = COMPONENT.COMPONENT_ID " .
               "and ISSUES_RECEIVED.ISSUE_ID = SERIAL_ISSUES.ISSUE_ID " .
               "and ISSUES_RECEIVED.COMPONENT_ID = COMPONENT.COMPONENT_ID " .
               "and COMPONENT.SUBSCRIPTION_ID = SUBSCRIPTION.SUBSCRIPTION_ID " .
               "and SUBSCRIPTION.LINE_ITEM_ID = LINE_ITEM.LINE_ITEM_ID " .
               "and SERIAL_ISSUES.RECEIVED = 1 " .
               "and ISSUES_RECEIVED.OPAC_SUPPRESSED = 1 " .
               "and LINE_ITEM.BIB_ID = :id " .
               "order by SERIAL_ISSUES.ISSUE_ID DESC";
        try {
            $data = array();
            $sqlStmt = $this->db->prepare($sql);
            $sqlStmt->execute(array(':id' => $id));
            while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
                $data[] = array('issue' => $row['ENUMCHRON']);
            }
            return $data;
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }
    }

    public function patronLogin($barcode, $lname)
    {
        $sql = "SELECT PATRON.PATRON_ID, PATRON.FIRST_NAME " .
               "FROM $this->dbName.PATRON, $this->dbName.PATRON_BARCODE " .
               "WHERE PATRON.PATRON_ID = PATRON_BARCODE.PATRON_ID AND " .
               "lower(PATRON.LAST_NAME) = :lname AND " .
               "lower(PATRON_BARCODE.PATRON_BARCODE) = :barcode";
        try {
            $sqlStmt = $this->db->prepare($sql);
            $sqlStmt->bindParam(':lname', strtolower($lname), PDO::PARAM_STR);
            $sqlStmt->bindParam(':barcode', strtolower($barcode), PDO::PARAM_STR);
            $sqlStmt->execute();
            $row = $sqlStmt->fetch(PDO::FETCH_ASSOC);
            if (isset($row['PATRON_ID']) && ($row['PATRON_ID'] != '')) {
                return array(
                    'id' => $row['PATRON_ID'],
                    'firstname' => $row['FIRST_NAME'],
                    'lastname' => $lname,
                    'cat_username' => $barcode,
                    'cat_password' => $lname,
                    // There's supposed to be a getPatronEmailAddress stored 
                    // procedure in Oracle, but I couldn't get it to work here;
                    // might be worth investigating further if needed later.
                    'email' => null,
                    'major' => null,
                    'college' => null);
            } else {
                return null;
            }
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }
    }
    
    public function getMyTransactions($patron)
    {
        $transList = array();

        $sql = "SELECT CHARGE_DUE_DATE as DUEDATE, BIB_ITEM.BIB_ID " .
               "FROM $this->dbName.CIRC_TRANSACTIONS, $this->dbName.BIB_ITEM " .
               "WHERE BIB_ITEM.ITEM_ID = CIRC_TRANSACTIONS.ITEM_ID " .
               "AND CIRC_TRANSACTIONS.PATRON_ID = :id";
        try {
            $sqlStmt = $this->db->prepare($sql);
            $sqlStmt->execute(array(':id' => $patron['id']));
            while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
                $transList[] = array('duedate' => $row['DUEDATE'],
                                     'id' => $row['BIB_ID']);
            }
            return $transList;
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }
    }

    public function getMyFines($patron)
    {
        $fineList = array();

        $sql = "SELECT unique FINE_FEE_TYPE.FINE_FEE_DESC, FINE_FEE.FINE_FEE_AMOUNT, FINE_FEE.FINE_FEE_BALANCE, FINE_FEE.ORIG_CHARGE_DATE, FINE_FEE.DUE_DATE, BIB_ITEM.BIB_ID " .
               "FROM (($this->dbName.FINE_FEE INNER JOIN $this->dbName.FINE_FEE_TYPE ON FINE_FEE.FINE_FEE_TYPE = FINE_FEE_TYPE.FINE_FEE_TYPE) INNER JOIN $this->dbName.PATRON ON FINE_FEE.PATRON_ID = PATRON.PATRON_ID) INNER JOIN $this->dbName.BIB_ITEM ON FINE_FEE.ITEM_ID = BIB_ITEM.ITEM_ID ".
               "WHERE PATRON.PATRON_ID=:id";

        try {
            $sqlStmt = $this->db->prepare($sql);
            $sqlStmt->execute(array(':id' => $patron['id']));
            while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
                $fineList[] = array('amount' => $row['FINE_FEE_AMOUNT'],
                                    'fine' => $row['FINE_FEE_DESC'],
                                    'balance' => $row['FINE_FEE_BALANCE'],
                                    'checkout' => $row['ORIG_CHARGE_DATE'],
                                    'duedate' => $row['DUE_DATE'],
                                    'id' => $row['BIB_ID']);
            }
            return $fineList;
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }
    }

    public function getMyHolds($patron)
    {
        $holdList = array();

        $sql = "SELECT HOLD_RECALL.BIB_ID, HOLD_RECALL.PICKUP_LOCATION, HOLD_RECALL.HOLD_RECALL_TYPE, HOLD_RECALL.EXPIRE_DATE, HOLD_RECALL.CREATE_DATE " .
               "FROM $this->dbName.HOLD_RECALL " .
               "WHERE HOLD_RECALL.PATRON_ID = :id";
        try {
            $sqlStmt = $this->db->prepare($sql);
            $sqlStmt->execute(array(':id' => $patron['id']));
            while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
                $holdList[] = array('type' => $row['HOLD_RECALL_TYPE'],
                                    'id' => $row['BIB_ID'],
                                    'location' => $row['PICKUP_LOCATION'],
                                    'expire' => $row['EXPIRE_DATE'],
                                    'create' => $row['CREATE_DATE']);
            }
            return $holdList;
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }
    }

    public function getMyProfile($patron)
    {
        $sql = "SELECT PATRON.LAST_NAME, PATRON.FIRST_NAME, PATRON.HISTORICAL_CHARGES, PATRON_ADDRESS.ADDRESS_LINE1, PATRON_ADDRESS.ADDRESS_LINE2, PATRON_ADDRESS.ZIP_POSTAL, PATRON_PHONE.PHONE_NUMBER, PATRON_GROUP.PATRON_GROUP_NAME " .
               "FROM $this->dbName.PATRON, $this->dbName.PATRON_ADDRESS, $this->dbName.PATRON_PHONE, $this->dbName.PATRON_BARCODE, $this->dbName.PATRON_GROUP " .
               "WHERE PATRON.PATRON_ID = PATRON_ADDRESS.PATRON_ID " .
               "AND PATRON_ADDRESS.ADDRESS_ID = PATRON_PHONE.ADDRESS_ID " .
               "AND PATRON.PATRON_ID = PATRON_BARCODE.PATRON_ID " .
               "AND PATRON_BARCODE.PATRON_GROUP_ID = PATRON_GROUP.PATRON_GROUP_ID " .
               "AND PATRON.PATRON_ID = :id";
        try {
            $sqlStmt = $this->db->prepare($sql);
            $sqlStmt->execute(array(':id' => $patron['id']));
            $row = $sqlStmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $patron = array('firstname' => $row['FIRST_NAME'],
                                'lastname' => $row['LAST_NAME'],
                                'address1' => $row['ADDRESS_LINE1'],
                                'address2' => $row['ADDRESS_LINE2'],
                                'zip' => $row['ZIP_POSTAL'],
                                'phone' => $row['PHONE_NUMBER'],
                                'group' => $row['PATRON_GROUP_NAME']);
                return $patron;
            } else {
                return null;
            }
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }
    }

    public function getHoldLink($recordId)
    {
        // Open Record Page
        $client = new Proxy_Request(null, array('useBrackets' => false));
        $client->setURL($this->config['Catalog']['pwebrecon'] . '?BBID=' . $recordId);
        $result = $client->sendRequest();
        if (!PEAR::isError($result)) {
            // Get HTML Page
            $body = str_replace("\n", '', $client->getResponseBody());
            $body = str_replace('<A', "\n<A", $body);  // Not sure why this is needed, but it solved the problem

            // Parse out SEQ and PID
            //preg_match('/<a href="(.*)">Place Hold/i', $body, $temp);
            //$link = $this->config['Catalog']['pwebrecon'] . strrchr(trim($temp[1]), '?');
            preg_match('/\?PAGE=REQUESTBIB&SEQ=(.*)&PID=(.*)"/', $body, $temp);
            
            // Establish Place Hold Link
            if ($temp[1] && $temp[2]) {
                $link = $this->config['Catalog']['pwebrecon'] . '?PAGE=REQUESTBIB' .
                        '&SEQ=' . $temp[1] . '&PID=' . $temp[2];
                return $link;
            } else {
                return new PEAR_Error('Cannot process "Place Hold"');
            }
        } else {
            return $result;
        }
    }

    public function getNewItems($page, $limit, $daysOld, $fundId = null)
    {
        $items = array();

        // Prevent unnecessary load on Voyager -- no point in exceeding the maximum
        // configured date range.
        $maxAge = 30;
        $searchSettings = getExtraConfigArray('searches');
        if (isset($searchSettings['NewItem']['ranges'])) {
            $tmp = explode(',', $searchSettings['NewItem']['ranges']);
            foreach($tmp as $current) {
                if (intval($current) > $maxAge) {
                    $maxAge = intval($current);
                }
            }
        }
        if ($daysOld > $maxAge) {
            $daysOld = $maxAge;
        }

        $bindParams = array(
            ':enddate' => date('d-m-Y', strtotime('now')),
            ':startdate' => date('d-m-Y', strtotime("-$daysOld day"))
        );

        $sql = "select count(distinct LINE_ITEM.BIB_ID) as count " .
               "from $this->dbName.LINE_ITEM, $this->dbName.LINE_ITEM_COPY_STATUS, $this->dbName.LINE_ITEM_FUNDS, $this->dbName.FUND " .
               "where LINE_ITEM.LINE_ITEM_ID = LINE_ITEM_COPY_STATUS.LINE_ITEM_ID " .
               "and LINE_ITEM_COPY_STATUS.COPY_ID = LINE_ITEM_FUNDS.COPY_ID " .
               "and LINE_ITEM_FUNDS.FUND_ID = FUND.FUND_ID ";
        if ($fundId) {
            // Although we're getting an ID value from getFunds() passed in here,
            // it's not actually an ID -- we use names as IDs (see note in getFunds
            // itself for more details).
            $sql .= "and lower(FUND.FUND_NAME) = :fund ";
            $bindParams[':fund'] = strtolower($fundId);
        }
        $sql .= "and LINE_ITEM.CREATE_DATE >= to_date(:startdate, 'dd-mm-yyyy') " .
               "and LINE_ITEM.CREATE_DATE < to_date(:enddate, 'dd-mm-yyyy')";
        try {
            $sqlStmt = $this->db->prepare($sql);
            $sqlStmt->execute($bindParams);
            $row = $sqlStmt->fetch(PDO::FETCH_ASSOC);
            $items['count'] = $row['COUNT'];
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }

        $page = ($page) ? $page : 1;
        $limit = ($limit) ? $limit : 20;
        $bindParams[':startRow'] = (($page-1)*$limit)+1;
        $bindParams[':endRow'] = ($page*$limit);
        /*
        $sql = "select * from " .
               "(select a.*, rownum rnum from " .
               "(select LINE_ITEM.BIB_ID, BIB_TEXT.TITLE, FUND.FUND_NAME, LINE_ITEM.CREATE_DATE, LINE_ITEM_STATUS.LINE_ITEM_STATUS_DESC " .
               "from $this->dbName.BIB_TEXT, $this->dbName.LINE_ITEM, $this->dbName.LINE_ITEM_COPY_STATUS, $this->dbName.LINE_ITEM_STATUS, $this->dbName.LINE_ITEM_FUNDS, $this->dbName.FUND " .
               "where BIB_TEXT.BIB_ID = LINE_ITEM.BIB_ID " .
               "and LINE_ITEM.LINE_ITEM_ID = LINE_ITEM_COPY_STATUS.LINE_ITEM_ID " .
               "and LINE_ITEM_COPY_STATUS.COPY_ID = LINE_ITEM_FUNDS.COPY_ID " .
               "and LINE_ITEM_STATUS.LINE_ITEM_STATUS = LINE_ITEM_COPY_STATUS.LINE_ITEM_STATUS " .
               "and LINE_ITEM_FUNDS.FUND_ID = FUND.FUND_ID ";
        */
        $sql = "select * from " .
               "(select a.*, rownum rnum from " .
               "(select LINE_ITEM.BIB_ID, LINE_ITEM.CREATE_DATE " .
               "from $this->dbName.LINE_ITEM, $this->dbName.LINE_ITEM_COPY_STATUS, $this->dbName.LINE_ITEM_STATUS, $this->dbName.LINE_ITEM_FUNDS, $this->dbName.FUND " .
               "where LINE_ITEM.LINE_ITEM_ID = LINE_ITEM_COPY_STATUS.LINE_ITEM_ID " .
               "and LINE_ITEM_COPY_STATUS.COPY_ID = LINE_ITEM_FUNDS.COPY_ID " .
               "and LINE_ITEM_STATUS.LINE_ITEM_STATUS = LINE_ITEM_COPY_STATUS.LINE_ITEM_STATUS " .
               "and LINE_ITEM_FUNDS.FUND_ID = FUND.FUND_ID ";
        if ($fundId) {
            $sql .= "and lower(FUND.FUND_NAME) = :fund ";
        }
        $sql .= "and LINE_ITEM.CREATE_DATE >= to_date(:startdate, 'dd-mm-yyyy') " .
               "and LINE_ITEM.CREATE_DATE < to_date(:enddate, 'dd-mm-yyyy') " .
               "group by LINE_ITEM.BIB_ID, LINE_ITEM.CREATE_DATE " .
               "order by LINE_ITEM.CREATE_DATE desc) a " .
               "where rownum <= :endRow) " .
               "where rnum >= :startRow";
        try {
            $sqlStmt = $this->db->prepare($sql);
            $sqlStmt->execute($bindParams);
            while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
                $items['results'][]['id'] = $row['BIB_ID'];
            }
            return $items;
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }
    }
    
    function getFunds()
    {
        $list = array();

        // Are funds disabled?  If so, do no work!
        if (isset($this->config['Funds']['disabled']) && 
            $this->config['Funds']['disabled']) {
            return $list;
        }
        
        // Load and normalize whitelist and blacklist if necessary:
        if (isset($this->config['Funds']['whitelist']) && 
            is_array($this->config['Funds']['whitelist'])) {
            $whitelist = array();
            foreach($this->config['Funds']['whitelist'] as $current) {
                $whitelist[] = strtolower($current);
            }
        } else {
            $whitelist = false;
        }
        if (isset($this->config['Funds']['blacklist']) && 
            is_array($this->config['Funds']['blacklist'])) {
            $blacklist = array();
            foreach($this->config['Funds']['blacklist'] as $current) {
                $blacklist[] = strtolower($current);
            }
        } else {
            $blacklist = false;
        }
        
        // Retrieve the data from Voyager; if we're limiting to a parent fund, we 
        // need to apply a special WHERE clause and bind parameter.
        if (isset($this->config['Funds']['parent_fund'])) {
            $bindParams = array(':parent' => $this->config['Funds']['parent_fund']);
            $whereClause = 'WHERE FUND.PARENT_FUND = :parent';
        } else {
            $bindParams = array();
            $whereClause = '';
        }
        $sql = "select distinct lower(FUND.FUND_NAME) as name " .
            "from $this->dbName.FUND {$whereClause} order by name";
        try {
            $sqlStmt = $this->db->prepare($sql);
            $sqlStmt->execute($bindParams);
            while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
                // Process blacklist and whitelist to skip illegal values:
                if ((is_array($blacklist) && in_array($row['NAME'], $blacklist)) ||
                    (is_array($whitelist) && !in_array($row['NAME'], $whitelist))) {
                    continue;
                }
                
                // Normalize the capitalization of the name:
                $name = ucwords($row['NAME']);

                // Set the array key to the lookup ID used by getNewItems and the
                // array value to the on-screen display name.
                //
                // We actually want to use the NAME of the fund to do lookups, not
                // its ID.  This is because multiple funds may share the same name,
                // and it is useful to collate all these results together.  To 
                // achieve the effect, we just fill the same value in as the name
                // and the ID in the return array.
                //
                // If you want to change this code to use numeric IDs instead,
                // you can adjust the SQL above, change the array key used in the
                // line below, and adjust the lookups done in getNewItems().
                $list[$name] = $name;
            }
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }

        return $list;
    }

    function getDepartments()
    {
        $deptList = array();
        
        $sql = "select DEPARTMENT.DEPARTMENT_ID, DEPARTMENT.DEPARTMENT_NAME " .
               "from $this->dbName.RESERVE_LIST, $this->dbName.RESERVE_LIST_COURSES, $this->dbName.DEPARTMENT " .
               "where " .
               "RESERVE_LIST.RESERVE_LIST_ID = RESERVE_LIST_COURSES.RESERVE_LIST_ID and " .
               "RESERVE_LIST_COURSES.DEPARTMENT_ID = DEPARTMENT.DEPARTMENT_ID " .
               "group by DEPARTMENT.DEPARTMENT_ID, DEPARTMENT_NAME " .
               "order by DEPARTMENT_NAME";
        try {
            $sqlStmt = $this->db->prepare($sql);
            $sqlStmt->execute();
            while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
                $deptList[$row['DEPARTMENT_ID']] = $row['DEPARTMENT_NAME'];
            }
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }

        return $deptList;
    }
    
    function getInstructors()
    {
        $instList = array();

        $bindParams = array();

        $sql = "select INSTRUCTOR.INSTRUCTOR_ID, INSTRUCTOR.LAST_NAME || ', ' || INSTRUCTOR.FIRST_NAME as NAME " .
               "from $this->dbName.RESERVE_LIST, $this->dbName.RESERVE_LIST_COURSES, $this->dbName.INSTRUCTOR " .
               "where RESERVE_LIST.RESERVE_LIST_ID = RESERVE_LIST_COURSES.RESERVE_LIST_ID and " .
               "RESERVE_LIST_COURSES.INSTRUCTOR_ID = INSTRUCTOR.INSTRUCTOR_ID " .
               "group by INSTRUCTOR.INSTRUCTOR_ID, LAST_NAME, FIRST_NAME " .
               "order by LAST_NAME";
        try {
            $sqlStmt = $this->db->prepare($sql);
            $sqlStmt->execute($bindParams);
            while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
                $instList[$row['INSTRUCTOR_ID']] = $row['NAME'];
            }
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }

        return $instList;
    }
    
    function getCourses()
    {
        $courseList = array();

        $bindParams = array();

        $sql = "select COURSE.COURSE_NUMBER || ': ' || COURSE.COURSE_NAME as NAME, COURSE.COURSE_ID " .
               "from $this->dbName.RESERVE_LIST, $this->dbName.RESERVE_LIST_COURSES, $this->dbName.COURSE " .
               "where RESERVE_LIST.RESERVE_LIST_ID = RESERVE_LIST_COURSES.RESERVE_LIST_ID and " .
               "RESERVE_LIST_COURSES.COURSE_ID = COURSE.COURSE_ID " .
               "group by COURSE.COURSE_ID, COURSE_NUMBER, COURSE_NAME " .
               "order by COURSE_NUMBER";
        try {
            $sqlStmt = $this->db->prepare($sql);
            $sqlStmt->execute($bindParams);
            while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
                $courseList[$row['COURSE_ID']] = $row['NAME'];
            }
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }

        return $courseList;
    }
/*
    // This is the original findReserves function -- it is retained here for
    // historical reasons, in case it needs to be restored in place of the
    // more advanced version below.
    function findReserves($course, $inst, $dept)
    {
        $recordList = array();
        $bindParams = array();
        
        $sql = "select MFHD_MASTER.DISPLAY_CALL_NO, BIB_TEXT.BIB_ID, BIB_TEXT.AUTHOR, BIB_TEXT.TITLE, BIB_TEXT.PUBLISHER, BIB_TEXT.PUBLISHER_DATE " .
               "from $this->dbName.MFHD_ITEM, $this->dbName.MFHD_MASTER, $this->dbName.BIB_TEXT, $this->dbName.BIB_ITEM, $this->dbName.RESERVE_LIST_COURSES, $this->dbName.RESERVE_LIST_ITEMS " .
               "where RESERVE_LIST_ITEMS.RESERVE_LIST_ID = RESERVE_LIST_COURSES.RESERVE_LIST_ID and " .
               "RESERVE_LIST_ITEMS.ITEM_ID = BIB_ITEM.ITEM_ID and " .
               "BIB_ITEM.BIB_ID = BIB_TEXT.BIB_ID and " .
               "MFHD_ITEM.ITEM_ID = BIB_ITEM.ITEM_ID and " .
               "MFHD_MASTER.MFHD_ID = MFHD_ITEM.MFHD_ID";
        if ($course != '') {
            $sql .= " and RESERVE_LIST_COURSES.COURSE_ID = :course";
            $bindParams[':course'] = $course;
        }
        if ($inst != '') {
            $sql .= " and RESERVE_LIST_COURSES.INSTRUCTOR_ID = :inst";
            $bindParams[':inst'] = $inst;
        }
        if ($dept != '') {
            $sql .= " and RESERVE_LIST_COURSES.DEPARTMENT_ID = :dept";
            $bindParams[':dept'] = $dept;
        }
        try {
            $sqlStmt = $this->db->prepare($sql);
            $sqlStmt->execute($bindParams);
            while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
                $recordList[] = $row;
            }
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }

        return $recordList;
    }
*/

    /* This version of findReserves was contributed by Matthew Hooper and includes
     * support for electronic reserves (though eReserve support is still a work in
     * progress).
     */
    function findReserves($course, $inst, $dept)
    {
        $recordList = array();

        $reserveWhere = array();
        $bindParams = array();

        if ($course != '') {
            $reserveWhere[] = "RESERVE_LIST_COURSES.COURSE_ID = :course" ;
            $bindParams[':course'] = $course;
        }
        if ($inst != '') {
            $reserveWhere[] = "RESERVE_LIST_COURSES.INSTRUCTOR_ID = :inst" ;
            $bindParams[':inst'] = $inst;
        }
        if ($dept != '') {
            $reserveWhere[] = "RESERVE_LIST_COURSES.DEPARTMENT_ID = :dept" ;
            $bindParams[':dept'] = $dept;
        }

        $reserveWhere = empty($reserveWhere) ? 
            "" : "where (" . implode(' AND ', $reserveWhere ) . ")";

        $sql = " select MFHD_MASTER.DISPLAY_CALL_NO, BIB_TEXT.BIB_ID, BIB_TEXT.AUTHOR, BIB_TEXT.TITLE, " .
               " BIB_TEXT.PUBLISHER, BIB_TEXT.PUBLISHER_DATE FROM $this->dbName.BIB_TEXT, $this->dbName.MFHD_MASTER where " .
               " bib_text.bib_id = (select bib_mfhd.bib_id from $this->dbName.bib_mfhd where bib_mfhd.mfhd_id = mfhd_master.mfhd_id) " .
               " and " .
               "  mfhd_master.mfhd_id in ( ".
               "  ((select distinct eitem.mfhd_id from $this->dbName.eitem where " .
               "    eitem.eitem_id in " .
               "    (select distinct reserve_list_eitems.eitem_id from " .
               "     $this->dbName.reserve_list_eitems where reserve_list_eitems.reserve_list_id in " .
               "     (select distinct reserve_list_courses.reserve_list_id from " .
               "      $this->dbName.reserve_list_courses " .
               "      $reserveWhere )) )) union " .
               "  ((select distinct mfhd_item.mfhd_id from $this->dbName.mfhd_item where " .
               "    mfhd_item.item_id in " .
               "    (select distinct reserve_list_items.item_id from " .
               "    $this->dbName.reserve_list_items where reserve_list_items.reserve_list_id in " .
               "    (select distinct reserve_list_courses.reserve_list_id from " .
               "      $this->dbName.reserve_list_courses $reserveWhere )) )) " .
               "  ) ";

        try {
            $sqlStmt = $this->db->prepare($sql);
            $sqlStmt->execute($bindParams);
            while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
                $recordList[] = $row;
            }
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }

        return $recordList;
    }

    function getSuppressedRecords()
    {
        $list = array();

        $sql = "select BIB_MASTER.BIB_ID " .
               "from $this->dbName.BIB_MASTER " .
               "where BIB_MASTER.SUPPRESS_IN_OPAC='Y'";
        try {
            $sqlStmt = $this->db->prepare($sql);
            $sqlStmt->execute();
            while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
                $list[] = $row['BIB_ID'];
            }
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }

        return $list;
    }
}

?>
