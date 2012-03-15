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

class Demo implements DriverInterface
{
    // Used when getting random bib ids from solr
    private $db;
    private $total_records;
    
    private function getFakeLoc()
    {
        $loc = rand()%3;
        switch($loc) {
            case 0: return "Campus A";
            case 1: return "Campus B";
            case 2: return "Campus C";
        }
    }

    private function getFakeStatus()
    {
        $loc = rand()%10;
        switch($loc) {
            case 10: return "Missing";
            case  9: return "On Order";
            case  8: return "Invoiced";
            default: return "Available";
        }
    }

    private function getFakeCallNum()
    {
        $codes = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $a = $codes[rand()%strlen($codes)];
        $b = rand()%899 + 100;
        $c = rand()%9999;
        return $a.$b.".".$c;
    }

    private function prepSolr()
    {
        global $configArray;

        // Create or solr connection
        $class = $configArray['Index']['engine'];
        $this->db = new $class($configArray['Index']['url']);
        if ($configArray['System']['debug']) {
            $this->db->debug = true;
        }

        // Get the total # of records in the system
        $result = $this->db->search('*:*');
        $this->total_records = $result['response']['numFound'];
    }

    private function getRandomBibId() {
        // Let's keep away from both ends of the index
        $result = $this->db->search('*:*', null, null, rand()%($this->total_records-1), 1);
        return $result['response']['docs'][0]['id'];
    }

    public function getStatus($id)
    {
        // How many items are there?
        $records = rand()%15;
        $holdings = array();

        // NOTE: Ran into an interesting bug when using:
        
        // 'availability' => rand()%2 ? true : false
        
        // It seems rand() returns alternating even/odd
        // patterns on some versions running under windows.
        // So this method gives better 50/50 results:
        
        // 'availability' => (rand()%100 > 49) ? true : false
        
        // Create a fake entry for each one
        for ($i = 0; $i < $records; $i++) {
            $holding[] = array(
                'id'           => $id,
                'number'       => $i+1,
                'barcode'      => sprintf("%08d",rand()%50000),
                'availability' => (rand()%100 > 50) ? true : false,
                'status'       => $this->getFakeStatus(),
                'location'     => $this->getFakeLoc(),
                'reserve'      => (rand()%100 > 49) ? 'Y' : 'N',
                'callnumber'   => $this->getFakeCallNum(),
                'duedate'      => ''
            );
        }
        return $holding;
    }

    public function getStatuses($ids)
    {
        // Random Seed
        srand(time());
        
        $status = array();
        foreach ($ids as $id) {
            $status[] = $this->getStatus($id);
        }
        return $status;
    }

    public function getHolding($id)
    {
        return $this->getStatus($id);
    }

    public function getPurchaseHistory($id)
    {
        return array();
    }

    /**
     * ********
     * 
     * Login a patron and return their basic details.
     * 
     */
    public function patronLogin($barcode, $password)
    {
        $user = array();

        $user['id']           = trim($barcode);
        $user['firstname']    = trim("Lib");
        $user['lastname']     = trim("Rarian");
        $user['cat_username'] = trim($barcode);
        $user['cat_password'] = trim($password);
        $user['email']        = trim("Lib.Rarian@library.not");
        $user['major']        = null;
        $user['college']      = null;

        return $user;
    }

    /**
     * ********
     * 
     * Get a patron's detailed information.
     * 
     */
    public function getMyProfile($patron)
    {
        $patron = array(
            'firstname' => trim("Lib"),
            'lastname'  => trim("Rarian"),
            'address1'  => trim("Somewhere ..."),
            'address2'  => trim("Other the Rainbow"),
            'zip'       => trim("12345"),
            'phone'     => trim("1900 CALL ME"),
            'group'     => trim("Library Staff")
        );
        return $patron;
    }

    /**
     * ********
     * 
     * Get any fines outstanding on a patron account.
     * 
     */
    public function getMyFines($patron)
    {
        // How many items are there?
        // %20 - 2 = 10% chance of none, 90% of 1-18 (give or take some odd maths)
        $fines = rand()%20 - 2;

        // Do some initial work in solr so we aren't repeating it inside this loop.
        $this->prepSolr();

        $fineList = array();
        for ($i = 0; $i < $fines; $i++) {
            // How many days overdue is the item?
            $day_overdue = rand()%30 + 5;
            // 50c a day fine?
            $fine = $day_overdue * 0.50;
            
            $fineList[] = array(
                "amount"   => $fine * 100,
                "checkout" => date("j-M-y", strtotime("now - ".($day_overdue+14)." days")),
                // After 20 days it becomes 'Long Overdue'
                "fine"     => $day_overdue > 20 ? "Long Overdue" : "Overdue",
                // 50% chance they've paid half of it
                "balance"  => (rand()%100 > 49 ? $fine/2 : $fine) * 100,
                "duedate"  => date("j-M-y", strtotime("now - $day_overdue days")),
                "id"       => $this->getRandomBibId()
            );
        }
        return $fineList;
    }

    /**
     * ********
     *
     * Show any unsatisfied requests for the parton.
     * 
     */
    public function getMyHolds($patron)
    {
        // How many items are there?
        // %10 - 1 = 10% chance of none, 90% of 1-9 (give or take some odd maths)
        $holds = rand()%10 - 1;

        // Do some initial work in solr so we aren't repeating it inside this loop.
        $this->prepSolr();

        $holdList = array();
        for ($i = 0; $i < $holds; $i++) {
            $holdList[] = array(
                "id"       => $this->getRandomBibId(),
                "location" => $this->getFakeLoc(),
                "expire"   => date("j-M-y", strtotime("now + 30 days")),
                "create"   => date("j-M-y", strtotime("now - ".(rand()%10)." days")),
                "reqnum"   => sprintf("%06d", rand()%9999)
            );
        }
        return $holdList;
    }

    /**
     * ********
     * 
     * Show items currently on loan to the patron.
     * 
     */
    public function getMyTransactions($patron)
    {
        // How many items are there?
        // %10 - 1 = 10% chance of none, 90% of 1-9 (give or take some odd maths)
        $trans = rand()%10 - 1;

        // Do some initial work in solr so we aren't repeating it inside this loop.
        $this->prepSolr();

        $transList = array();
        for ($i = 0; $i < $trans; $i++) {
            // When is it due? +/- up to 15 days
            $due_relative = rand()%30 - 15;
            // Due date
            if ($due_relative >= 0) {
                $due_date = date("j-M-y", strtotime("now +$due_relative days"));
            } else {
                $due_date = date("j-M-y", strtotime("now $due_relative days"));
            }
    
            // Times renewed    : 0,0,0,0,0,1,2,3,4,5
            $renew = rand()%10 - 5; if ($renew < 0) $renew = 0;
            // Pending requests : 0,0,0,0,0,1,2,3,4,5
            $req = rand()%10 - 5;   if ($req < 0) $req = 0;
            
            $transList[] = array(
                'duedate' => $due_date,
                'barcode' => sprintf("%08d",rand()%50000),
                'renew'   => $renew,
                'request' => $req,
                "id"       => $this->getRandomBibId(),
            );
        }
        return $transList;
    }

    /**
     * ********
     * 
     * Generate some fake fund codes
     * 
     */
    public function getFunds()
    {
        return array("Fund A", "Fund B", "Fund C");
    }

    /**
     * ********
     *
     * Generate fake course reserves information
     *
     */
    public function getDepartments()
    {
        return array("Dept. A", "Dept. B", "Dept. C");
    }
    public function getInstructors()
    {
        return array("Instructor A", "Instructor B", "Instructor C");
    }
    public function getCourses()
    {
        return array("Course A", "Course B", "Course C");
    }

    /**
     * ********
     * 
     * Generate some fake new item results
     * 
     */
    public function getNewItems($page, $limit, $daysOld, $fundId = null)
    {
        // Do some initial work in solr so we aren't repeating it inside this loop.
        $this->prepSolr();

        // Pick a random number of results to return -- don't exceed limit or 30,
        // whichever is smaller (this can be pretty slow due to the random ID code).
        $count = rand(0, $limit > 30 ? 30 : $limit);
        $results = array();
        for ($x = 0; $x < $count; $x++) {
            $randomId = $this->getRandomBibId();
            
            // avoid duplicate entries in array:
            if (!in_array($randomId, $results)) {
                $results[] = $randomId;
            }
        }
        $retVal = array('count' => count($results), 'results' => array());
        foreach($results as $result) {
            $retVal['results'][] = array('id' => $result);
        }
        return $retVal;
    }

    /**
     * ********
     * 
     * Generate some fake course reserves results
     * 
     */
    function findReserves($course, $inst, $dept)
    {
        // Do some initial work in solr so we aren't repeating it inside this loop.
        $this->prepSolr();

        // Pick a random number of results to return -- don't exceed 30.
        $count = rand(0, 30);
        $results = array();
        for ($x = 0; $x < $count; $x++) {
            $randomId = $this->getRandomBibId();
            
            // avoid duplicate entries in array:
            if (!in_array($randomId, $results)) {
                $results[] = $randomId;
            }
        }

        $retVal = array();
        foreach($results as $current) {
            $retVal[] = array('BIB_ID' => $current);
        }
        return $retVal;
    }
}
?>