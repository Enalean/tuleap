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

require_once 'Solr.php';

/**
 * Solr Statistics Class
 *
 * Offers functionality for recording usage statistics data into Solr
 *
 * @version     $Revision: 1.13 $
 * @author      Andrew S. Nagy <andrew.nagy@villanova.edu>
 * @access      public
 */
class SolrStats extends Solr
{
    private $institution = '';

    public function __construct($host)
    {
        parent::__construct($host, 'stats');
    }
    
    public function setInstitution($institution)
    {
        $this->institution = $institution;
    }

    public function saveSearch($phrase, $type)
    {
        $doc = array();
        $doc['phrase'] = $phrase;
        $doc['type'] = $type;
        
        return $this->save($doc);
    }

    public function saveNoHits($phrase, $type)
    {
        $doc = array();
        $doc['phrase'] = $phrase;
        $doc['type'] = $type;
        $doc['noresults'] = 'T';

        return $this->save($doc);
    }

    public function saveRecordView($id)
    {
        $doc = array();
        $doc['recordId'] = $id;
        
        return $this->save($doc);
    }

    private function save($data = array())
    {
        $userAgent = $this->determineBrowser();

        $data['id'] = uniqid('', true);
        $data['datestamp'] = substr(date('c', strtotime('now')), 0, -6) . 'Z';
        $data['institution'] = $this->institution;
        $data['browser'] = $userAgent['browser'];
        $data['browserVersion'] = $userAgent['browserVersion'];
        $data['ipaddress'] = $_SERVER['REMOTE_ADDR'];
        $data['referrer'] = $_SERVER['HTTP_REFERER'];
        $data['url'] = $_SERVER['REQUEST_URI'];

        $xml = $this->getSaveXML($data);
        if ($this->saveRecord($xml)) {
            $this->commit();
            return true;
        } else {
            return new PEAR_Error('Could not record statistics');
        }
    }
    
    private function determineBrowser()
    {
        // Parse User Agent String
        $code = $_SERVER['HTTP_USER_AGENT'];
        preg_match_all('/\([^"]*\)|[^ ]+/', $code, $info);
        $info = $info[0];
        
        // Determine Browser
        if (isset($info[5])) {
            // Safari
            $browser = explode('/', $info[5]);
            $version = explode('/', $info[4]);
            $product = array($browser[0], $version[1]);
        } elseif (isset($info[3])) {
            // Firefox
            $product = $info[3];
            $product = explode('/', $product);
        } else {
            $product = explode('; ', $info[1]);
            if ($product[2] == 'MSIE') {
                // IE
                $product = explode(' ', $product[2]);
            } else {
                $product = array('Other');
            }
        }
        
        // Parse System Info
        $system = $info[1];
        
        // Build new return array
        $info = array('browser' => $product[0],
                      'browserVersion' => $product[1],
                      'system' => $system);
        return $info;
    }
    
}
?>