<?php
/**
 *
 * Copyright (C) Verus Solutions Pvt.Ltd 2010.
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
 *
 */
require_once 'Interface.php';

class NewGenLib implements DriverInterface
{
    private $db;
    private $dbName;
    private $config;

    function __construct()
    {
        // Load Configuration for this Module
        $this->config = parse_ini_file('conf/NewGenLib.ini', true);

        // Define Database Name
        $this->dbName = $this->config['Catalog']['database'];

        try {
            $this->db = new PDO('pgsql:host='
                                .$this->config['Catalog']['hostname']
                                .' user='
                                .$this->config['Catalog']['user']
                                .' dbname='
                                .$this->config['Catalog']['database'] 
                                .' password='
                                .$this->config['Catalog']['password']
                                .' port='
                                .$this->config['Catalog']['port']);
        } catch (PDOException $e) {
            throw $e;
        }
    }

public function getHolding($RecordID)
    {
		$holding = array();
		$pieces = explode("_",$RecordID);
		$CatId = $pieces[0];
		$LibId = $pieces[1];
	//SQL Statement
		$mainsql = "select d.status as status, d.location_id as location_id, d.call_number as call_number, d.accession_number as 		accession_number, d.barcode as barcode, d.library_id as library_id ".
		    "from document d,cat_volume v where d.volume_id=v.volume_id and v.cataloguerecordid=".$CatId." and v.owner_library_id=".$LibId;
	try{
	    $sqlStmt = $this->db->prepare($mainsql);
            $sqlStmt->execute();
	} catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }
		$reserve = 'N';
	while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
		switch ($row['status']) {
		        case 'B':
			    $status="Available";
		            $available = true;
		            $reserve = 'N';
		            break;
		        case 'A':
		            // Instead of relying on status = 'On holds shelf',
		            // I might want to see if:
		            // action.hold_request.current_copy = asset.copy.id
		            // and action.hold_request.capture_time is not null
		            // and I think action.hold_request.fulfillment_time is null    
			    $status="Checked out";
		            $available = false;
		            $reserve = 'Y';
		            break;
		        default:
			    $status="LOST";
		            $available = false;
		            $reserve = 'N';
		            break;
            }
	    $locationsql = "select location from location where location_id='".$row['location_id']."' and library_id=".$row['library_id'];
	   try{
		    $sqlStmt1 = $this->db->prepare($locationsql);
		    $sqlStmt1->execute();
	      } catch (PDOException $e1) {
		    return new PEAR_Error($e1->getMessage());
	      }	
	  $location="";
	   while ($rowLoc = $sqlStmt1->fetch(PDO::FETCH_ASSOC)) {
		$location=$rowLoc['location'];
	   }

	   $duedateql = "select due_date from cir_transaction where accession_number='".$row['accession_number']."' and document_library_id='".$row['library_id']."' and status='A'";
	   try{
		    $sqlStmt2 = $this->db->prepare($duedateql);
		    $sqlStmt2->execute();
	      } catch (PDOException $e1) {
		    return new PEAR_Error($e1->getMessage());
	      }	
	   $duedate="";
	   while ($rowDD = $sqlStmt2->fetch(PDO::FETCH_ASSOC)) {
		$duedate=$rowDD['due_date'];
	   }	
	   $holding[] = array('id' => $RecordID,
                               'availability' => $available,
                               'status' => $status,
                               'location' => $location,
                               'reserve' => $reserve,
                               'callnumber' => $row['call_number'],
                               'duedate' => $duedate,
                               'number' => $row['accession_number'],
                               'barcode' => $row['barcode']);
		}
		
	return $holding;
    }	

    public function getMyFines($patron)
    {
        
	$MyFines = array();
	$pid=$patron['cat_username'];
	$fine='Overdue';
        $LibId = 1;
$mainsql = "select d.volume_id as volume_id, c.status as status, v.volume_id as volume_id, d.accession_number as accession_number, v.cataloguerecordid as cataloguerecordid, v.owner_library_id as owner_library_id, c.patron_id as patron_id, c.due_date as due_date, c.ta_date as ta_date, c.fine_amt as fine_amt, c.ta_id as ta_id,c.library_id as library_id from document d,cat_volume v,cir_transaction c where d.volume_id=v.volume_id and v.owner_library_id='".$LibId."' and c.accession_number=d.accession_number and c.document_library_id=d.library_id and c.patron_id='".$pid."' and c.status in('B','LOST') and c.fine_amt>0";
		
	try{
	    $sqlStmt = $this->db->prepare($mainsql);
            $sqlStmt->execute();
	} catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }
	$id = "";
	while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {

$id=$row['cataloguerecordid']."_".$row['owner_library_id'];
	  $amount=$row['fine_amt']*100;	
	  $checkout=$row['ta_date'];
	   $duedate=$row['due_date'];
	$paidamtsql="select sum(f.fine_amt_paid) as fine_amt_paid from cir_transaction_fine f where f.ta_id=".$row['ta_id']." and f.library_id=".$row['library_id'];
	try{
		    $sqlStmt1 = $this->db->prepare($paidamtsql);
		    $sqlStmt1->execute();
	      } catch (PDOException $e1) {
		    return new PEAR_Error($e1->getMessage());
	      }	
	   $paidamt="";
	   $balance="";
	   while ($rowpaid = $sqlStmt1->fetch(PDO::FETCH_ASSOC)) {
		$paidamt=$rowpaid['fine_amt_paid']*100;
		$balance=$amount-$paidamt;

	   }
		
		
	 	$MyFines[] = array('amount' => $amount,
                               'checkout' => $checkout,
                               'fine' => $fine,
                               'balance' => $balance,
                               'duedate' => $duedate,
                              'id'=>$id);
}		


 	return $MyFines;   

 }

  public function getMyHolds($patron)
    {
		$holds = array();
		$PatId = $patron['cat_username'];
		$LibId = 1;
	//SQL Statement
		$mainsql = "select d.volume_id as volume_id, c.status as status, v.volume_id as volume_id, d.accession_number as accession_number, v.cataloguerecordid as cataloguerecordid, v.owner_library_id as owner_library_id, c.patron_id as patron_id ". "from document d,cat_volume v,cir_transaction c where d.volume_id=v.volume_id and v.owner_library_id='".$LibId."' and c.accession_number=d.accession_number and c.document_library_id=d.library_id and c.patron_id='".$PatId."' and c.status='C'";
	try{
	    $sqlStmt = $this->db->prepare($mainsql);
            $sqlStmt->execute();
	} catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }
	while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
	   $type = "RECALLED ITEM - Return the item to the library";
	   $rIdql = "select due_date, ta_date from cir_transaction where patron_id='".$row['patron_id']."'";
	   try{
		    $sqlStmt2 = $this->db->prepare($rIdql);
		    $sqlStmt2->execute();
	      } catch (PDOException $e1) {
		    return new PEAR_Error($e1->getMessage());
	      }		
	   $RecordId=$row['cataloguerecordid']."_".$row['owner_library_id'];
	   $duedate="";
	   $tadate="";
	   while ($rowDD = $sqlStmt2->fetch(PDO::FETCH_ASSOC)) {
		$duedate=$rowDD['due_date'];
		$tadate=$rowDD['ta_date'];
	   }
	   $holds[] = array('type' => $type,
			    'id' => $RecordId,
			    'location' => NULL,
			    'reqnum' => NULL,
                            'expire' => $duedate." ".$type,
                            'create' => $tadate);	
	}
	//SQL Statement 2
	$mainsql2 = "select v.cataloguerecordid as cataloguerecordid, v.owner_library_id as owner_library_id, v.volume_id as volume_id, r.volume_id as volume_id, r.queue_no as queue_no, r.reservation_date as reservation_date, r.status as status ".
                     "from cir_reservation r, cat_volume v where r.patron_id='".$PatId."' and r.library_id='".$LibId."' and r.volume_id=v.volume_id and r.status in ('A', 'B')";
	try{
	    $sqlStmt2 = $this->db->prepare($mainsql2);
            $sqlStmt2->execute();
	} catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }
	while ($row2 = $sqlStmt2->fetch(PDO::FETCH_ASSOC)) {
			$location="";
			$type2="";
			switch ($row2['status']) {
		        case 'A':
			    $location="Checked out - No copy available in the library";
			    $type2 = $row2['queue_no'];
		            break;
		        case 'B':   
			    $location="Item available at the circulation desk";
			    $dtsql = "select ";
			    $type2 = "INTIMATED";
		            break;
            	}		
	$RecordId2=$row2['cataloguerecordid']."_".$row2['owner_library_id'];
	   $holds[] = array('type' => $type2,
			    'id' => $RecordId2,
			    'location' => $location,
			    'reqnum' => $row2['queue_no'],
                            'expire' => NULL." ".$type2,
                            'create' => $row2['reservation_date']);
	}
	return $holds;
    }


public function getMyProfile($patron)
    {
	
	$catusr = $patron['cat_username'];
	$catpswd = $patron['cat_password'];
	$sql = "select p.patron_id as patron_id,p.user_password as user_password, p.fname as fname, p.lname as lname, p.address1 as address1, p.address2 as address2, p.pin as pin, p.phone1 as phone1 ".
"from patron p where p.patron_id='".$catusr."' and p.user_password='".$catpswd."'";
	try{
	    $sqlStmt = $this->db->prepare($sql);
            $sqlStmt->execute();
	} catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }
	while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
	    if ($catusr != $row['patron_id'] || $catpswd != $row['user_password']) {
		return null;
	    } else {
	    $profile = array('firstname' => $row['fname'],
	    		       'lastname' => $row['lname'],
	                       'address1' => $row['address1'],
	                       'address2' => $row['address2'],
	    	 	       'zip' => $row['pin'],
	    		       'phone' => $row['phone1'],
			       'group' => NULL);
		
	   }
	}
	return $profile;
    }

  public function getMyTransactions($patron)
    {
	$transactions = array();
	$PatId = $patron['cat_username'];
		$mainsql = "select c.due_date as due_date, c.status as status, c.ta_id as ta_id, c.library_id as library_id, c.accession_number as accession_number, v.cataloguerecordid as cataloguerecordid, v.owner_library_id as owner_library_id, c.patron_id as patron_id ". "from document d,cat_volume v,cir_transaction c where d.volume_id=v.volume_id and v.owner_library_id='1' and c.accession_number=d.accession_number and c.document_library_id=d.library_id and c.patron_id='".$PatId."' and c.status in('A','C')";
	try{
	    $sqlStmt = $this->db->prepare($mainsql);
            $sqlStmt->execute();
	} catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }
	while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
	   $countql = "select count(*) as total ". 
		 "from cir_transaction c, cir_transaction_renewal r where r.ta_id='".$row['ta_id']."' and r.library_id='".$row['library_id']."' and c.status='A'";
	   try{
	      $sql = $this->db->prepare($countql);
              $sql->execute();
	   } catch (PDOException $e) {
              return new PEAR_Error($e->getMessage());
           }
	$RecordId=$row['cataloguerecordid']."_".$row['owner_library_id'];
	   $count="";
	   while ($srow = $sql->fetch(PDO::FETCH_ASSOC)) {
		$count = "Renewed = ".$srow['total'];
	   }
	   $transactions[] = array('duedate' => $row['due_date']." ".$count,
			           'id' => $RecordId,
			           'barcode' => $row['accession_number'],
				   'renew' => $count,
			           'reqnum' => NULL);
	}
   return $transactions;
    }


 public function getStatus($RecordID)
    {
		$StatusResult = array();
		$pieces = explode("_",$RecordID);
		$CatId = $pieces[0];
		$LibId = $pieces[1];
	//SQL Statement
		$mainsql = "select d.status as status, d.location_id as location_id, d.call_number as call_number, d.library_id as library_id from document d,cat_volume v where d.volume_id=v.volume_id and v.cataloguerecordid='".$CatId."' and v.owner_library_id=".$LibId;
	try{
	    $sqlSmt = $this->db->prepare($mainsql);
            $sqlSmt->execute();
	} catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }
		$reserve = 'N';
	while ($row = $sqlSmt->fetch(PDO::FETCH_ASSOC)) {
		switch ($row['status']) {
		        case 'B':
			    $status="Available";
		            $available = true;
		            $reserve = 'N';
		            break;
		        case 'A':
		            // Instead of relying on status = 'On holds shelf',
		            // I might want to see if:
		            // action.hold_request.current_copy = asset.copy.id
		            // and action.hold_request.capture_time is not null
		            // and I think action.hold_request.fulfillment_time is null    
			    $status="Checked out";
		            $available = false;
		            $reserve = 'Y';
		            break;
		        default:
			    $status="Not Available";
		            $available = false;
		            $reserve = 'N';
		            break;
            }
	    $locationsql = "select location from location where location_id='".$row['location_id']."' and library_id=".$row['library_id'];
	   try{
		    $sqlSmt1 = $this->db->prepare($locationsql);
		    $sqlSmt1->execute();
	      } catch (PDOException $e1) {
		    return new PEAR_Error($e1->getMessage());
	      }	
	  $location="";
	   while ($rowLoc = $sqlSmt1->fetch(PDO::FETCH_ASSOC)) {
		$location=$rowLoc['location'];
	   }
			$StatusResult[] = array('id' => $RecordID,
                            		   'status' => $status,
                         		   'location' => $location,
		                           'reserve' => $reserve,
		                           'callnumber' => $row['call_number'],
                               		   'availability' => $available);
                               
		}
        return $StatusResult;
    }
    	
    public function getStatuses($StatusResult)
	    {
        	$status = array();
        	foreach ($StatusResult as $id) {
           	$status[] = $this->getStatus($id);
           }
        return $status;
    }



public function patronLogin($username, $password)
    {
	$patron = array();
	$PatId = $username;
	$LibId = 1;
	$psswrd = $password;
//SQL Statement
	$sql = "select p.patron_id as patron_id, p.library_id as library_id, p.fname as fname, p.lname as lname, p.user_password as user_password,  p.membership_start_date as membership_start_date, p.membership_expiry_date as membership_expiry_date, p.email as email ".
"from patron p where p.patron_id='".$PatId."' and p.user_password='".$psswrd."' and p.membership_start_date <= current_date and p.membership_expiry_date < current_date";
	
	try{
	    $sqlStmt = $this->db->prepare($sql);
            $sqlStmt->execute();
	} catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }
	while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
	    if ($PatId != $row['patron_id'] || $psswrd != $row['user_password']) {
		return NULL;
	    } else {
	    $patron = array("id" => $PatId,
                          "firstname" => $row['fname'],
                          'lastname' => $row['lname'],
                          'cat_username' => $PatId,
                          'cat_password' => $psswrd,
                          'email' => $row['email'],
                          'major' => NULL,
                          'college' => NULL);
	    }
	}
	return $patron;	
    }

    public function getPurchaseHistory($id)
    {
        return array();
    }

}
?>
