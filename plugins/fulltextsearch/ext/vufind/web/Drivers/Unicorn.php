<?php
/**
 * IMPORTANT:  This is not the latest Unicorn driver.  For better functionality,
 * please visit the vufind-unicorn project: http://code.google.com/p/vufind-unicorn/
 */

/**
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

class Unicorn implements DriverInterface
{
	private $host;
	private $port;
	private $search_prog;

	function __construct()
	{
        // Load Configuration for this Module

        $configArray = parse_ini_file('conf/Unicorn.ini', true);

        $this->host = $configArray['Catalog']['host'];
        $this->port = $configArray['Catalog']['port'];
        $this->search_prog = $configArray['Catalog']['search_prog'];
	}
	

	public function getStatus($id)
	{
	
		$params = array('search' => 'holding', 'id' => $id);
		$xml = $this->search_sirsi($params);
	
		foreach ($xml->record as $record) {
			$callnum_rec = $record->catalog->callnum_records;
			$item_rec = $record->catalog->item_record;
			
			// Unicorn doesn't give status or availability; so make them up
			$status = "Available";
			$availability = 1;
			
			if ( $item_rec->date_time_due ) {
				$status = "Checked Out"; 
				$availability = 0;
			}

			$holding[] = array (
				'status' => $status,
				'availability' => $availability,
			 	'id' => $id,
				'number' => $item_rec->copy_number,
				'duedate' => $item_rec->date_time_due,
				'callnumber' => $callnum_rec->item_number,
				'reserve' => $callnum_rec->copies_on_reserve,
				'location' => $item_rec->location,
				// can also get these values from Unicorn
				//'ncopies' => $callnum_rec->number_of_copies,
				//'item_type' => $item_rec->item_type,
				//'barcode' => $item_rec->item_id
			);
		}
		
		return $holding;

	} // end getHolding
 	
	public function getStatuses($idList)
	{
	/* want the params array to look like so:
		 $params = array (
			'search' => 'holdings',
			'id0' => "$idList[0]",
			'id1' => "$idList[1]",
			'id2' => "$idList[2]",
		);
 	*/
		
	$params['search'] = 'holdings';
            
	for ($i=0; $i<count($idList); $i++) 
	{
		$params["id$i"] = "$idList[$i]";
	}

	$i = 0; // to get the id from $params in foreach loops below
	
	$xml = $this->search_sirsi($params);
	
	foreach ($xml->titles as $titles) 
	{
		$holdings = array(); 
		
		foreach ($titles->record as $record) 
		{
			$callnum_rec = $record->catalog->callnum_records;
			$item_rec = $record->catalog->item_record;
			
			// Unicorn doesn't give status or availability; make them up
			$status = "Available";
			$availability = 1;
			
			if ( $item_rec->date_time_due ) 
			{
				$status = "Checked Out"; 
				$availability = 0;
			}

			$holdings[] = array (
				'status' => $status,
				'availability' => $availability,
				'id' => $params["id$i"],
				'number' => $item_rec->copy_number,
				'duedate' => $item_rec->date_time_due,
				'callnumber' => $callnum_rec->item_number,
				'reserve' => $callnum_rec->copies_on_reserve,
				 'location' => $item_rec->location,
				 // can also get following values from Unicorn
				 //'ncopies' => $callnum_rec->number_of_copies,
				 //'item_type' => $item_rec->item_type,
				 //'barcode' => $item_rec->item_id
			 );
		} // end foreach ($titles->record as $record) {

		$items[] = $holdings;
		$i++; // increment to get item id
		}
	
	return $items;
	
	} // end getHoldings

	/* this is useful for testing :-)
	public function getHoldings($idList)
	{
		foreach ($idList as $id) {
			$holdings[] = $this->getHolding($id);
        }
        
		return $holdings;
	}
       */


	public function search_sirsi($params)
	{
		$url = $this->build_query($params);
		$response = file_get_contents($url);
		
		$xml = simplexml_load_string($response);
		
		if ($xml === false) 
		{
			echo "<br/>simplexml_load_string failed in Unicorn.php, search_sirsi() <br/>";
			exit(1);
		}
		return $xml;
	}
	
	public function build_query($params)
	{
		$query_string = '?';
		$url = $this->host;
		
		if ($this->port) 
		{
			$url=  $url . ":" . $this->port . "/" . $this->search_prog;
		}
	    else 
	    {
	    	$url =  $url . "/" . $this->search_prog;
	    }

	    $url = $url . '?' . http_build_query($params);

	    return $url;
	}
	
    public function getHolding($id)
    {
        return $this->getStatus($id);
    }

    public function getPurchaseHistory($id)
    {
        return array();
    }

}

?>
