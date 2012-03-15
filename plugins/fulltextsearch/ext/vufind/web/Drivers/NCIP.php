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

require_once 'XML/Unserializer.php';
require_once 'XML/Serializer.php';
require_once 'sys/Proxy_Request.php';

/**
 * NCIP Interface
 *
 * The goal of this class is to allow the VuFind system to interact directly
 * with a Library System via the NISO NCIP Interface
 *
 * @version     $Revision$
 * @author      Andrew S. Nagy <andrew.nagy@villanova.edu>
 * @access      public
 */
class NCIP
{
    private $doc;
    private $ncipMethod;
    private $client;
    private $agencyCode;

    function __construct()
    {
        // Define HTTP Client
        $this->client = new Proxy_Request(null, array('useBrackets' => false));
        $this->client->setMethod(HTTP_REQUEST_METHOD_POST);
        $this->client->addHeader('Content-Type', 'text/xml');
    	$this->client->setURL($configArray['NCIP']['url']);

        // Setup XML Messages
        $dom = new DOMImplementation();
        $doctype = $dom->createDocumentType('NCIPMessage',
                   '-//NISO//NCIP DTD Version 1//EN',
                   'http://www.niso.org/ncip/v1_0/imp1/dtd/ncip_v1_0.dtd');
        $this->doc = $dom->createDocument('', '', $doctype);
        $this->doc->encoding = 'UTF-8';
        $this->doc->formatOutput = true;
        
        $this->agencyCode = $configArray['NCIP']['agencyId'];
    }
    
    public function getHolding($recordId)
    {
        // Build XML Header
        $message = $this->doc->createElement('NCIPMessage');
        $message->setAttribute('version', 'http://www.niso.org/ncip/v1_0/imp1/dtd/ncip_v1_0.dtd');
        $method = $this->doc->createElement('LookupItem');
        $method->appendChild($this->createHeader());
        $message->appendChild($method);
        $this->doc->appendChild($message);
        
        // Build XML Message
        $uniqueItem = $this->doc->createElement('UniqueItemId');

        //$uniqueAgency = $this->doc->createElement('UniqueAgencyId');
        //$agencyScheme = $this->doc->createElement('Scheme');
        //$uniqueAgency->appendChild($agencyScheme);
        //$agencyValue = $this->doc->createElement('Value', $this->agencyCode);
        //$uniqueAgency->appendChild($agencyValue);
        //$uniqueItem->appendChild($uniqueAgency);

        $itemId = $this->doc->createElement('ItemIdentifierValue', $recordId);
        $uniqueItem->appendChild($itemId);
        $method->appendChild($uniqueItem);

        $elementType = $this->doc->createElement('ItemElementType');
        $elementScheme = $this->doc->createElement('Scheme', 'http://www.niso.org/ncip/v1_0/schemes/itemelementtype/itemelementtype.scm');
        $elementType->appendChild($elementScheme);
        $elementValue = $this->doc->createElement('Value', 'Circulation Status');
        $elementType->appendChild($elementValue);
        $method->appendChild($elementType);

        $response = $this->send();
        
        return $response;
    }
    
    public function getHoldings($idList)
    {
        foreach ($idList as $id) {
            $holdings[] = $this->getHolding($id);
        }
        return $holdings;
    }
    
    public function patronLogin($username, $password)
    {
    }

    public function patronLookup()
    {
        // Build XML Header
        $message = $this->doc->createElement('NCIPMessage');
        $message->setAttribute('version', 'http://www.niso.org/ncip/v1_0/imp1/dtd/ncip_v1_0.dtd');
        $method = $this->doc->createElement('LookupUser');
        $method->appendChild($this->createHeader());
        $message->appendChild($method);
        $this->doc->appendChild($message);


        $response = $this->send();
        return $response;
    }

    public function getMyTransactions($patronId)
    {
    }

    public function placeHold($recordId, $patronId)
    {
        // Build XML Header
        $message = $this->doc->createElement('NCIPMessage');
        $message->setAttribute('version', 'http://www.niso.org/ncip/v1_0/imp1/dtd/ncip_v1_0.dtd');
        $method = $this->doc->createElement('RequestItem');
        $method->appendChild($this->createHeader());
        $message->appendChild($method);
        $this->doc->appendChild($message);

        // Define Patron
        $uniqueUser = $this->doc->createElement('UniqueUserId');
        $userId = $this->doc->createElement('UserIdentifierValue', $patronId);
        $uniqueUser->appendChild($userId);
        $method->appendChild($uniqueUser);

        // Define Record
        $uniqueRecord = $this->doc->createElement('UniqueBibliographicId');
        $bibRecord = $this->doc->createElement('BibliographicRecordId');
        $recordId = $this->doc->createElement('BibliographicRecordIdentifier', $recordId);
        $bibRecord->appendChild($recordId);
        $uniqueRecord->appendChild($bibRecord);
        $method->appendChild($uniqueRecord);

        // Define Request
        $uniqueRequest = $this->doc->createElement('UniqueRequestId');
        $requestId = $this->doc->createElement('RequestIdentifierValue', uniqid());
        $uniqueRequest->appendChild($requestId);
        $method->appendChild($uniqueRequest);

        // Define Request Type
        $requestType = $this->doc->createElement('RequestType');
        $requestScheme = $this->doc->createElement('Scheme', 'http://www.niso.org/ncip/v1_0/imp1/schemes/requesttype/requesttype.scm');
        $requestType->appendChild($requestScheme);
        $requestValue = $this->doc->createElement('Value', 'Hold');
        $requestType->appendChild($requestValue);
        $method->appendChild($requestype);

        $response = $this->send();
        return $response;
    }

    public function cancelHold($requestId, $patronId)
    {
        // Build XML Header
        $message = $this->doc->createElement('NCIPMessage');
        $message->setAttribute('version', 'http://www.niso.org/ncip/v1_0/imp1/dtd/ncip_v1_0.dtd');
        $method = $this->doc->createElement('CancelRequestItem');
        $method->appendChild($this->createHeader());
        $message->appendChild($method);
        $this->doc->appendChild($message);

        // Define Patron
        $uniqueUser = $this->doc->createElement('UniqueUserId');
        $userId = $this->doc->createElement('UserIdentifierValue', $patronId);
        $uniqueUser->appendChild($userId);
        $method->appendChild($uniqueUser);

        // Define Request
        $uniqueRequest = $this->doc->createElement('UniqueRequestId');
        $requestId = $this->doc->createElement('RequestIdentifierValue', $requestId);
        $uniqueRequest->appendChild($requestId);
        $method->appendChild($uniqueRequest);

        // Define Request Type
        $requestType = $this->doc->createElement('RequestType');
        $requestScheme = $this->doc->createElement('Scheme', 'http://www.niso.org/ncip/v1_0/imp1/schemes/requesttype/requesttype.scm');
        $requestType->appendChild($requestScheme);
        $requestValue = $this->doc->createElement('Value', 'Hold');
        $requestType->appendChild($requestValue);
        $method->appendChild($requestype);

        // Define Request Scope
        $requestType = $this->doc->createElement('RequestScopeType');
        $requestScheme = $this->doc->createElement('Scheme', 'http://www.niso.org/ncip/v1_0/imp1/schemes/requestscopetype/requestscopetype.scm');
        $requestType->appendChild($requestScheme);
        $requestValue = $this->doc->createElement('Value', 'Item');
        $requestType->appendChild($requestValue);
        $method->appendChild($requestype);

        $response = $this->send();
        return $response;
    }

    public function placeRecall($recordId, $patronId, $comment)
    {
        // Confirm Patron Id

        // Recall Item
    }

    public function cancelRecall($recordId, $patronId)
    {
        // Cancel Recall Item
    }

    public function renewItem($recordId, $patronId, $returnDate)
    {
        // Build XML Header
        $message = $this->doc->createElement('NCIPMessage');
        $message->setAttribute('version', 'http://www.niso.org/ncip/v1_0/imp1/dtd/ncip_v1_0.dtd');
        $method = $this->doc->createElement('RenewItem');
        $method->appendChild($this->createHeader());
        $message->appendChild($method);
        $this->doc->appendChild($message);

        // Define Patron
        $uniqueUser = $this->doc->createElement('UniqueUserId');
        $userId = $this->doc->createElement('UserIdentifierValue', $patronId);
        $uniqueUser->appendChild($userId);
        $method->appendChild($uniqueUser);

        // Define Record
        $uniqueRecord = $this->doc->createElement('UniqueBibliographicId');
        $bibRecord = $this->doc->createElement('BibliographicRecordId');
        $recordId = $this->doc->createElement('BibliographicRecordIdentifier', $recordId);
        $bibRecord->appendChild($recordId);
        $uniqueRecord->appendChild($bibRecord);
        $method->appendChild($uniqueRecord);

        // Define Return Date
        $returnDate = $this->doc->createElement('DesiredDateForReturn', $returnDate);
        $method->appendChild($returnDate);

        $response = $this->send();
        return $response;
    }

    public function updateProfile($patronId)
    {
        // Update User
    }

    public function getUserProfile($patronId)
    {
    
    }
    
    private function createHeader()
    {
        $header = $this->doc->createElement('InitiationHeader');
        
        $agency = $this->doc->createElement('FromAgencyId');
        $uniqueAgency = $this->doc->createElement('UniqueAgencyId');
        $agencyScheme = $this->doc->createElement('Scheme');
        $uniqueAgency->appendChild($agencyScheme);
        $agencyValue = $this->doc->createElement('Value', $this->agencyCode);
        $uniqueAgency->appendChild($agencyValue);
        $agency->appendChild($uniqueAgency);
        $header->appendChild($agency);

        $agency = $this->doc->createElement('ToAgencyId');
        $uniqueAgency = $this->doc->createElement('UniqueAgencyId');
        $agencyScheme = $this->doc->createElement('Scheme');
        $uniqueAgency->appendChild($agencyScheme);
        $agencyValue = $this->doc->createElement('Value', $this->agencyCode);
        $uniqueAgency->appendChild($agencyValue);
        $agency->appendChild($uniqueAgency);
        $header->appendChild($agency);
        
        return $header;
    }
    
    /**
     * Submit REST Request
     *
     * @return  string      The response from the NCIP Server
     * @access  private
     */
    private function send()
	{
        $xml = $this->doc->saveXML();
        
        $this->client->addHeader('Content-Length', strlen($xml));
    	$this->client->setBody($xml);
        $result = $this->client->sendRequest();

        if (!PEAR::isError($result)) {
            return $this->process($this->client->getResponseBody());
        } else {
            return $result;
        }
	}

	private function process($result)
	{
    	$unxml = new XML_Unserializer();
    	$result = $unxml->unserialize($result);
    	if (!PEAR::isError($result)) {
            return $unxml->getUnserializedData();
        } else {
            PEAR::raiseError($result);
        }

        return null;
	}

}

?>