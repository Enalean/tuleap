<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'SOAP_NbRequestsExceedLimit_Exception.class.php';
require_once 'dao/SOAP_RequestLimitatorDao.class.php';

class SOAP_RequestLimitator {
    private $nbMaxCall;
    private $timeframe;
    
    private $currentTime;
    private $nbCallToMethod;
    private $firstCallToMethod;

    public function __construct($nbCall, $timeframe, $dao) {
        $this->nbMaxCall      = $nbCall;
        $this->timeframe      = $timeframe;
        
        $this->dao = $dao;
        $this->nbCallToMethod    = array();
        $this->firstCallToMethod = array();
    }
    
    public function logCallTo($methodName) {
        $this->currentTime = time();
        $this->loadDataFor($methodName);
        if ($this->callToMethodExceedsLimit($methodName)) {
            throw new SOAP_NbRequestsExceedLimit_Exception();
        }
        $this->dao->saveCallToMethod($methodName, $this->currentTime);
    }
    
    private function loadDataFor($name) {
        $dar = $this->dao->searchFirstCallToMethod($name, ($this->currentTime - $this->timeframe));
        if ($dar && $dar->rowCount() == 1) {
            $this->nbCallToMethod[$name] = $this->dao->foundRows();
        } else {
            $this->nbCallToMethod[$name] = 0;
        }
    }
    
    private function callToMethodExceedsLimit($name) {
        if ($this->nbCallToMethod[$name] >= $this->nbMaxCall) {
            return true;
        }
        return false;
    }
}

?>
