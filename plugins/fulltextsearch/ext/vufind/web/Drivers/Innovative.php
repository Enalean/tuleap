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
require_once 'sys/Proxy_Request.php';
require_once 'Interface.php';

/**
 * VuFind Connector for Innovative
 *
 * This class uses screen scraping techniques to gather record holdings written
 * by Adam Bryn of the Tri-College consortium.
 *
 * @author Adam Brin <abrin@brynmawr.com>
 */
class Innovative implements DriverInterface
{
    public $config;
    
    public function __construct()
    {
        // Load Configuration for this Module
        $this->config = parse_ini_file('conf/Innovative.ini', true);
    }

    public function getStatus($id)
    {
        // Strip ID
        $id_ = substr(str_replace('.b', '', $id), 0, -1);

        // Load Record Page
        if (substr($this->config['Catalog']['url'], -1) == '/') {
            $host = substr($this->config['Catalog']['url'], 0, -1);
        } else {
            $host = $this->config['Catalog']['url'];
        }
        $req = new Proxy_Request($host . '/record=b' . $id_);
        if (PEAR::isError($req->sendRequest())) {
            return null;
        }
        $result = $req->getResponseBody();

        $r = substr($result, stripos($result, 'bibItems'));
        $r = substr($r,strpos($r,">")+1);
        $r = substr($r,0,stripos($r,"</table"));
        $rows = preg_split("/<tr([^>]*)>/",$r);
        $count = 0;
        $keys = array_pad(array(),10,"");

        $loc_col_name      = $this->config['OPAC']['location_column'];
        $call_col_name     = $this->config['OPAC']['call_no_column'];
        $status_col_name   = $this->config['OPAC']['status_column'];
        $reserves_col_name = $this->config['OPAC']['location_column'];
        $reserves_key_name = $this->config['OPAC']['reserves_key_name'];
        $stat_avail        = $this->config['OPAC']['status_avail'];
        $stat_due          = $this->config['OPAC']['status_due'];

        $ret = array();
        foreach ($rows as $row) {
            $cols = preg_split("/<t(h|d)([^>]*)>/",$row);
            for ($i=0; $i < sizeof($cols); $i++) {
                $cols[$i] = str_replace("&nbsp;"," ",$cols[$i]);
                $cols[$i] = ereg_replace("<!--([^(-->)]*)-->","",$cols[$i]);
                $cols[$i] = html_entity_decode(trim(substr($cols[$i],0,stripos($cols[$i],"</t"))));
                if ($count == 1) {
                    $keys[$i] = $cols[$i];
                } else if ($count > 1) {
                    if (stripos($keys[$i],$loc_col_name) > -1) {
                        $ret[$count-2]['location'] = strip_tags($cols[$i]);
                    }
                    if (stripos($keys[$i],$reserves_col_name) > -1) {
                        if (stripos($cols[$i],$reserves_key_name) > -1) {    // if the location name has "reserves"
                            $ret[$count-2]['reserve'] = 'Y';
                        } else {
                            $ret[$count-2]['reserve'] = 'N';
                        }
                    }
                    if (stripos($keys[$i],$call_col_name) > -1) {
                        $ret[$count-2]['callnumber'] = strip_tags($cols[$i]);
                    }
                    if (stripos($keys[$i],$status_col_name) > -1) {
                        if (stripos($cols[$i],$stat_avail) > -1) {
                            $ret[$count-2]['status'] = "Available";
                            $ret[$count-2]['availability'] = 1;
                        } else {
                            $ret[$count-2]['availability'] = 0;
                        }
                        if (stripos($cols[$i],$stat_due) > -1) {
                            $t = trim(substr($cols[$i],stripos($cols[$i],$stat_due)+strlen($stat_due)));
                            $t = substr($t,0,stripos($t," "));
                            $ret[$count-2]['duedate'] = $t;
                        }
                    }
                    //$ret[$count-2][$keys[$i]] = $cols[$i];
                    //$ret[$count-2]['id'] = $bibid;
                    $ret[$count-2]['id'] = $id;
                    $ret[$count-2]['number'] = ($count -1);
                }
            }
            $count++;
        }
        return $ret;
    }
    
    public function getStatuses($ids)
    {
        $items = array();
        $count = 0;
        foreach ($ids as $id) {
           $items[$count] = $this->getStatus($id);
           $count++;
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

}

?>
