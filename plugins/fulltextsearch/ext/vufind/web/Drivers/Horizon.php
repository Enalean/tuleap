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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 */

require_once 'Interface.php';

class Horizon implements DriverInterface
{
    private $db;

    function __construct()
    {
        // Load Configuration for this Module
        $configArray = parse_ini_file('conf/Horizon.ini', true);

        // Connect to database
        $this->db = mssql_pconnect($configArray['Catalog']['host'] . ':' . $configArray['Catalog']['port'],
				    $configArray['Catalog']['username'],
				    $configArray['Catalog']['password']);


        // Select the databse
        mssql_select_db($configArray['Catalog']['database']);
    }

    public function getHolding($id)
    {
        // Query holding information based on id field defined in import/marc.properties
        $sql = "select item.item# as ITEM_NUM, item.item_status as STATUS_CODE, item_status.descr as STATUS, " .
             "location.name as LOCATION, item.call_reconstructed as CALLNUMBER, " .
             "item.ibarcode as ITEM_BARCODE, convert(varchar(12), " .
             "dateadd(dd,item.due_date,'jan 1 1970')) as DUEDATE, item.copy_reconstructed as ITEM_SEQUENCE_NUMBER, ".
             "substring(collection.pac_descr,5,40) as COLLECTION from item " .
             "inner join item_status on item.item_status = item_status.item_status " .
             "inner join location on item.location = location.location " .
             "inner join collection on item.collection = collection.collection where item.bib# = $id";

        try {
            $holding = array();
            $sqlStmt = mssql_query($sql);
            while ($row = mssql_fetch_assoc($sqlStmt)) {
              $duedate = $row['DUEDATE'];
              switch ($row['STATUS_CODE']) {
                                case 'i': // checked in
                                        $available = 1;
                                        $reserve = 'N';
                                        break;
                                case 'h': // being held
                                        $available = 0;
                                        $reserve = 'Y';
                                        break;
                                case 'l': // lost
                                        $available = 0;
                                        $reserve = 'N';
                                        $duedate=''; // No due date for lost items
                                        break;
                                default:
                                        $available = 0;
                                        $reserve = 'N';
                                        break;
                                        }
                $holding[] = array('id' => $id,
                                   'availability' => $available,
                                   'item_num' => $row['ITEM_NUM'],
                                   'status' => $row['STATUS'],
                                   'location' => $row['LOCATION'],
                                   'reserve' => $reserve,
                                   'callnumber' => $row['CALLNUMBER'],
                                   'collection' => $row['COLLECTION'],
                                   'duedate' => $duedate,
                                   'barcode' => $row['ITEM_BARCODE'],
                                   'number' => $row['ITEM_SEQUENCE_NUMBER']);
            }
            return $holding;
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }
    }
    
    public function getHoldings($idList)
    {
        foreach ($idList as $id) {
            $holdings[] = $this->getHolding($id);
        }
        return $holdings;
    }

    public function getStatus($id)
    {
        return $this->getHolding($id);
    }

    public function getStatuses($idList)
    {
        $status = array();
        foreach ($idList as $id) {
            $status[] = $this->getStatus($id);
        }
        return $status;
    }

    public function getPurchaseHistory($id)
    {
        return array();
    }

    public function patronLogin($username, $password)
    {
        $sql = "select name_reconstructed as FULLNAME, email_address as EMAIL from borrower " .
               "left outer join borrower_address on borrower_address.borrower#=borrower.borrower# " .
               "inner join borrower_barcode on borrower.borrower#=borrower_barcode.borrower# " .
               "where borrower_barcode.bbarcode=\"$username\" and pin# = \"$password\"";

        try {
            $user = array();
            $sqlStmt = mssql_query($sql);
            $row = mssql_fetch_assoc($sqlStmt);
            if ($row) {
                list($lastname,$firstname)=explode(', ',$row['FULLNAME']);
                $user = array('id' => $username,
                              'firstname' => $firstname,
                              'lastname' => $lastname,
                              'cat_username' => $username,
                              'cat_password' => $password,
                              'email' => $row['EMAIL'],
                              'major' => null,
                              'college' => null);

                return $user;
            } else {
                return null;
            }
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }
    }

    public function getMyHolds($patron)
    {
        $sql = "select bib# as BIB_NUM, request_location as LOCATION, " .
               "convert(varchar(12),dateadd(dd, hold_exp_date, '1 jan 1970')) as EXPIRE, " .
               "convert(varchar(12),dateadd(dd, request_date, '1 jan 1970')) as CREATED from request " .
               "join borrower_barcode on borrower_barcode.borrower#=request.borrower# " .
               "where borrower_barcode.bbarcode=\"" . $patron['id'] . "\"";

        try {
            $sqlStmt = mssql_query($sql);

            while ($row = mssql_fetch_assoc($sqlStmt)) {
		$expire = ($row['EXPIRE']) ? $row['EXPIRE'] : "[Not yet available for pickup]";
                $holdList[] = array('id' => $row['BIB_NUM'],
                                    'location' => $row['LOCATION'],
                                    'expire' => $expire,
                                    'create' => $row['CREATED'],
                                    'reqnum' => null );
            }
            return $holdList;
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }
    }

    public function getMyFines($patron)
    {
        $sql = "select item.bib# as BIB_NUM, item.item# as ITEM_NUM, " .
               "burb.borrower# as BORROWER_NUM, burb.amount as AMOUNT, " .
               "convert(varchar(12),dateadd(dd, burb.date, '01 jan 1970')) as DUEDATE, " .
               "burb.block as FINE, burb.amount as BALANCE from burb " .
               "join item on item.item#=burb.item# " .
               "join borrower on borrower.borrower#=burb.borrower# " .
               "join borrower_barcode on borrower_barcode.borrower#=burb.borrower# " .
               "where borrower_barcode.bbarcode=\"" . $patron['id'] . "\" and amount != 0";

        try {
            $sqlStmt = mssql_query($sql);

            while ($row = mssql_fetch_assoc($sqlStmt)) {
                $checkout = '';
                $duedate = '';
                $bib_num = $row['BIB_NUM'];
                $item_num = $row['ITEM_NUM'];
                $borrower_num = $row['BORROWER_NUM'];
                $amount = $row['AMOUNT'];
                $balance += $amount;

                if (isset($bib_num) && isset($item_num))
                {
                    $cko = "select convert(varchar(12),dateadd(dd, date, '01 jan 1970')) as CHECKOUT " .
                           "from burb where borrower#=" . $borrower_num . " " .
                           "and item#=" . $item_num . " and block=\"infocko\"";
                    $sqlStmt_cko = mssql_query($cko);

                    if ($row_cko = mssql_fetch_assoc($sqlStmt_cko)) {
                        $checkout = $row_cko['CHECKOUT'];
                    }

                    $due = "select convert(varchar(12),dateadd(dd, date, '01 jan 1970')) as DUEDATE " .
                           "from burb where borrower#=" . $borrower_num . " " .
                           "and item#=" . $item_num . " and block=\"infodue\"";
                    $sqlStmt_due = mssql_query($due);

                    if ($row_due = mssql_fetch_assoc($sqlStmt_due)) {
                        $duedate = $row_due['DUEDATE'];
                    }
                }

                $fineList[] = array('id' => $bib_num,
                                    'amount' => $amount,
                                    'fine' => $row['FINE'],
                                    'balance' => $balance,
                                    'checkout' => $checkout,
                                    'duedate' => $duedate);
            }
            return $fineList;
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }

    }

    public function getMyProfile($patron)
    {
        $sql = "select name_reconstructed as FULLNAME, address1 as ADDRESS1, city_st.descr as ADDRESS2, " .
               "postal_code as ZIP, phone_no as PHONE from borrower " .
               "left outer join borrower_phone on borrower_phone.borrower#=borrower.borrower# " .
               "inner join borrower_address on borrower_address.borrower#=borrower.borrower# " .
               "inner join city_st on city_st.city_st=borrower_address.city_st " .
               "inner join borrower_barcode on borrower_barcode.borrower#=borrower.borrower# " .
               "where borrower_barcode.bbarcode=\"" . $patron['id'] . "\"";

        try {
            $sqlStmt = mssql_query($sql);

            $row = mssql_fetch_assoc($sqlStmt);
            if ($row) {
                list($lastname,$firstname)=explode(', ',$row['FULLNAME']);
                $profile= array('lastname' => $lastname,
                                'firstname' => $firstname,
                                'address1' => $row['ADDRESS1'],
                                'address2' => $row['ADDRESS2'],
                                'zip' => $row['ZIP'],
                                'phone' => $row['PHONE'],
                                'group' => null);
                return $profile;
            } else {
                return null;
            }
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }
    }

    public function getMyTransactions($patron)
    {
        $sql = "select item.bib# as BIB_NUM, item.ibarcode as ITEM_BARCODE, " .
               "convert(varchar(12), dateadd(dd, item.due_date, '01 jan 1970')) as DUEDATE, " .
               "item.n_renewals as RENEW, request.bib_queue_ord as REQUEST from circ " .
               "join item on item.item#=circ.item# " .
               "join borrower on borrower.borrower#=circ.borrower# " .
               "join borrower_barcode on borrower_barcode.borrower#=circ.borrower# " .
               "left outer join request on request.item#=circ.item# " .
               "where borrower_barcode.bbarcode=\"" . $patron['id'] . "\"";

        try {
            $sqlStmt = mssql_query($sql);

            while ($row = mssql_fetch_assoc($sqlStmt)) {
                $transList[] = array('id' => $row['BIB_NUM'],
                                     'duedate' => $row['DUEDATE'],
                                     'barcode' => $row['ITEM_BARCODE'],
                                     'renew' => $row['RENEW'],
                                     'request' => $row['REQUEST']);
            }
            return $transList;
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }

    }

    public function placeHold()
    {
        return true;
    }

}

?>
