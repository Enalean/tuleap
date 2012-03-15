<?php
/**
 * ILS Driver for VuFind to query availability information via DAIA.
 * 
 * @author Oliver Marahrens <o.marahrens@tu-harburg.de>
 * 
 * based on the proof-of-concept-driver by Till Kinstler, GBV
 */

require_once 'Interface.php';

class DAIA implements DriverInterface
{
    private $baseURL;

    public function __construct()
    {
        $configArray = parse_ini_file('conf/DAIA.ini', true);

        $this->baseURL = $configArray['Global']['baseUrl'];
    }

    public function getStatus($id)
    {
        $holding = $this->daiaToHolding($id);
        return $holding;
    }

    public function getStatuses($ids)
    {
        $items = array();
        foreach ($ids as $id) {
            $items[] = $this->getShortStatus($id);
        }
        return $items;
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
     * Query a DAIA server and return the result as DomDocument object.
     * The returned object is an XML document containing
     * content as described in the DAIA format specification.
     */
    private function queryDAIA($id)
    {
        $daia = new DomDocument();
        $daia->load($this->baseURL . '?output=xml&ppn='.$id);

        return $daia;
    }

    /**
     * Flatten a DAIA response to an array of holding information.
     */
    public function daiaToHolding($id)
    {
        $daia = $this->queryDAIA($id);
        // get Availability information from DAIA
        $documentlist = $daia->getElementsByTagName('document');
		$status = array();
		for ($b = 0; $documentlist->item($b) !== null; $b++) {
		$itemlist = $documentlist->item($b)->getElementsByTagName('item');
		for ($c = 0; $itemlist->item($c) !== null; $c++) 
		{
                    $result = array(
                        'callnumber' => '',
                        'availability' => '0',
                        'number' => ($c+1),
                        'reserve' => 'No',
                        'duedate' => '',
                        'queue'   => '',
                        'delay'   => '',
                        'barcode' => 1,
                        'status' => '',
                        'id' => $id,
                        'itemid' => '',
                        'recallhref' => '',
                        'location' => '',
                        'location.id' => '',
                        'location.href' => '',
                        'label' => ''
                    );
                    $result['itemid'] = $itemlist->item($c)->attributes->getNamedItem('id')->nodeValue;
                    if ($itemlist->item($c)->attributes->getNamedItem('href') !== null) {
                        $result['recallhref'] = $itemlist->item($c)->attributes->getNamedItem('href')->nodeValue;
                    }
                    $departmentElements = $itemlist->item($c)->getElementsByTagName('department');
                    if($departmentElements->length > 0) {
                        if ($departmentElements->item(0)->nodeValue) {
                            $result['location'] = $departmentElements->item(0)->nodeValue;
                            $result['location.id'] = $departmentElements->item(0)->attributes->getNamedItem('id')->nodeValue;
                            $result['location.href'] = $departmentElements->item(0)->attributes->getNamedItem('href')->nodeValue;
                        }
                    }
                    $storageElements = $itemlist->item($c)->getElementsByTagName('storage');
                    if ($storageElements->length > 0) {
                        if ($storageElements->item(0)->nodeValue) {
                            $result['location'] = $storageElements->item(0)->nodeValue;
                            $result['location.id'] = $storageElements->item(0)->attributes->getNamedItem('id')->nodeValue;
                            $result['location.href'] = $storageElements->item(0)->attributes->getNamedItem('href')->nodeValue;
                            $result['barcode'] = $result['location.id'];
                        }
                    }
        			$labelElements = $itemlist->item($c)->getElementsByTagName('label');
        			if ($labelElements->length > 0) {
                        if ($labelElements->item(0)->nodeValue) {
                            $result['label'] = $labelElements->item(0)->nodeValue;
                            $result['callnumber'] = urldecode($labelElements->item(0)->nodeValue);
                        }
                    }
        
                    #$loanAvail = 0;
                    #$loanExp = 0;
                    #$presAvail = 0;
                    #$presExp = 0;
                    
                    $unavailableElements = $itemlist->item($c)->getElementsByTagName('unavailable');
                    if ($unavailableElements->item(0) !== null) {
                        for ($n = 0; $unavailableElements->item($n) !== null; $n++) {
                        	$service = $unavailableElements->item($n)->attributes->getNamedItem('service')->nodeValue;
                            if ($service === 'presentation') {
                                $result['presentation.availability'] = '0';
                                if ($unavailableElements->item($n)->attributes->getNamedItem('expected') !== null) {
                                	$result['presentation.duedate'] = $unavailableElements->item($n)->attributes->getNamedItem('expected')->nodeValue;
                                }
                                if ($unavailableElements->item($n)->attributes->getNamedItem('queue') !== null) {
                                    $result['presentation.queue'] = $unavailableElements->item($n)->attributes->getNamedItem('queue')->nodeValue;
                                }
                                $result['availability'] = '0';
                            } elseif ($service === 'loan') {
                                $result['loan.availability'] = '0';
                                if ($unavailableElements->item($n)->attributes->getNamedItem('expected') !== null) {
                                    $result['loan.duedate'] = $unavailableElements->item($n)->attributes->getNamedItem('expected')->nodeValue;
                                }
                                if ($unavailableElements->item($n)->attributes->getNamedItem('queue') !== null) {
                                    $result['loan.queue'] = $unavailableElements->item($n)->attributes->getNamedItem('queue')->nodeValue;
                                }
                                $result['availability'] = '0';
                            } elseif ($service === 'interloan') {
                                $result['interloan.availability'] = '0';
                                if ($unavailableElements->item($n)->attributes->getNamedItem('expected') !== null) {
                                    $result['interloan.duedate'] = $unavailableElements->item($n)->attributes->getNamedItem('expected')->nodeValue;
                                }
                                if ($unavailableElements->item($n)->attributes->getNamedItem('queue') !== null) {
                                    $result['interloan.queue'] = $unavailableElements->item($n)->attributes->getNamedItem('queue')->nodeValue;
                                }
                                $result['availability'] = '0';
                            } elseif ($service === 'openaccess') {
                                $result['openaccess.availability'] = '0';
                                if ($unavailableElements->item($n)->attributes->getNamedItem('expected') !== null) {
                                    $result['openaccess.duedate'] = $unavailableElements->item($n)->attributes->getNamedItem('expected')->nodeValue;
                                }
                                if ($unavailableElements->item($n)->attributes->getNamedItem('queue') !== null) {
                                    $result['openaccess.queue'] = $unavailableElements->item($n)->attributes->getNamedItem('queue')->nodeValue;
                                }
                                $result['availability'] = '0';
                            }
                            // TODO: message/limitation
                           	if ($unavailableElements->item($n)->attributes->getNamedItem('expected') !== null) {
                            	$result['duedate'] = $unavailableElements->item($n)->attributes->getNamedItem('expected')->nodeValue;
                            }
                            if ($unavailableElements->item($n)->attributes->getNamedItem('queue') !== null) {
                                $result['queue'] = $unavailableElements->item($n)->attributes->getNamedItem('queue')->nodeValue;
                            }                        
                        }
                    }
                    
                    $availableElements = $itemlist->item($c)->getElementsByTagName('available');
                    if ($availableElements->item(0) !== null) {
                        for ($n = 0; $availableElements->item($n) !== null; $n++) {
                        	$service = $availableElements->item($n)->attributes->getNamedItem('service')->nodeValue;
                            if ($service === 'presentation') {
                                $result['presentation.availability'] = '1';
                                if ($availableElements->item($n)->attributes->getNamedItem('delay') !== null) {
                                    $result['presentation.delay'] = $availableElements->item($n)->attributes->getNamedItem('delay')->nodeValue;
                                }
                                $result['availability'] = '1';
                            } elseif ($service === 'loan') {
                                $result['loan.availability'] = '1';
                               	if ($availableElements->item($n)->attributes->getNamedItem('delay') !== null) {
                                    $result['loan.delay'] = $availableElements->item($n)->attributes->getNamedItem('delay')->nodeValue;
                                }
                                $result['availability'] = '1';
                            } elseif ($service === 'interloan') {
                                $result['interloan.availability'] = '1';
                                if ($availableElements->item($n)->attributes->getNamedItem('delay') !== null) {
                                    $result['interloan.delay'] = $availableElements->item($n)->attributes->getNamedItem('delay')->nodeValue;
                                }
                                $result['availability'] = '1';
                            } elseif ($service === 'openaccess') {
                                $result['openaccess.availability'] = '1';
                              	if ($availableElements->item($n)->attributes->getNamedItem('delay') !== null) {
                                    $result['openaccess.delay'] = $availableElements->item($n)->attributes->getNamedItem('delay')->nodeValue;
                                }
                                $result['availability'] = '1';
                            }
                            // TODO: message/limitation
                           	if ($availableElements->item($n)->attributes->getNamedItem('delay') !== null) {
                                $result['delay'] = $availableElements->item($n)->attributes->getNamedItem('delay')->nodeValue;
                            }
                        }
                    }
                    $status[] = $result;
                /* $status = "available";
                if (loanAvail) return 0;
                if (presAvail) {
                    if (loanExp) return 1;
                    return 2;
                }
                if (loanExp) return 3;
                if (presExp) return 4;
                return 5;
                */
		}
		}
        return $status;
    }
        
    public function getShortStatus($id) {
        $daia = $this->queryDAIA($id);
        // get Availability information from DAIA
        $itemlist = $daia->getElementsByTagName('item');
        $label = "Unknown";
        $storage = "Unknown";
        $holding = array();
                for ($c = 0; $itemlist->item($c) !== null; $c++)
                {
                        $storageElements = $itemlist->item($c)->getElementsByTagName('storage');
                        if ($storageElements->item(0)->nodeValue) $storage = $storageElements->item(0)->nodeValue;
                        $labelElements = $itemlist->item($c)->getElementsByTagName('label');
                        if ($labelElements->item(0)->nodeValue) $label = $labelElements->item(0)->nodeValue;
                        $availableElements = $itemlist->item($c)->getElementsByTagName('available');
                        if ($availableElements->item(0) !== null) {
                                $availability = 1;
                                $status = 'Available';
                            #for ($n = 0; $availableElements->item($n) !== null; $n++) {
                                #    $status .= ' ' . $availableElements->item($n)->getAttribute('service');
                            #}
                    }
                    else {
                        $status = 'Unavailable';
                        $availability = 0;
                    }
            $holding[] = array('availability' => $availability,
                           'id' => $id,
                           'status' => "$status",
                           'location' => "$storage",
                           'reserve' => 'N',
                           'callnumber' => "$label",
                           'duedate' => '',
                           'number' => ($c+1));
                }
        return $holding;
    }

}
?>