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

class SOAP_RequestLimitator {
    private $nbMaxCall;
    private $timeframe;
    
    private $nbCallToMethod;
    private $firstCallToMethod;

    public function __construct($nbCall, $timeframe) {
        $this->nbMaxCall      = $nbCall;
        $this->timeframe      = $timeframe;
        
        $this->nbCallToMethod    = array();
        $this->firstCallToMethod = array();
    }
    
    public function logCallTo($methodName) {
        $this->logCallToMethod($methodName);
        if ($this->callToMethodExceedsLimit($methodName)) {
            throw new SOAP_NbRequestsExceedLimit_Exception('stuff');
        }
    }
    
    private function logCallToMethod($name) {
        if (isset($this->nbCallToMethod[$name]) && !$this->delayExpired($name)) {
            $this->nbCallToMethod[$name]++;
        } else {
            $this->initCounters($name);
        }
    }
    
    private function delayExpired($name) {
        $timeSinceFirstCall = time() - $this->firstCallToMethod[$name];
        if ($timeSinceFirstCall >= $this->timeframe) {
            return true;
        }
        return false;
    }
    
    private function initCounters($name) {
        $this->nbCallToMethod[$name]    = 0;
        $this->firstCallToMethod[$name] = time();
    }
    
    private function callToMethodExceedsLimit($name) {
        if ($this->nbCallToMethod[$name] >= $this->nbMaxCall) {
            return true;
        }
        return false;
    }
}

?>
