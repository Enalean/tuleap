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

/**
 * Record and verify that a SOAP method is called in the quota range.
 * The quota is defined with a number of call per timeframe (for instance 10
 * call per hours).
 */
class SOAP_RequestLimitator
{
    private $nbMaxCall;
    private $timeframe;
    /**
     * @var SOAP_RequestLimitatorDao
     */
    private $dao;

    private $currentTime;
    private $nbCallToMethod;

    /**
     * Constructor
     *
     * @param int $nbCall Maximum number of call allowed
     * @param int $timeframe Time during which $nbCall applies
     * @param SOAP_RequestLimitatorDao $dao       Data access object
     */
    public function __construct($nbCall, $timeframe, SOAP_RequestLimitatorDao $dao)
    {
        $this->nbMaxCall = $nbCall;
        $this->timeframe = $timeframe;
        $this->dao       = $dao;

        $this->nbCallToMethod    = array();
    }

    /**
     * Save a call to a method name, throw an exception if quota is exceeded
     *
     * @param String $methodName
     * @throws SOAP_NbRequestsExceedLimit_Exception
     */
    public function logCallTo($methodName)
    {
        $this->currentTime = $_SERVER['REQUEST_TIME'];
        $this->loadDataFor($methodName);
        $this->dao->saveCallToMethod($methodName, $this->currentTime);
        $this->checkIfMethodExceedsLimits($methodName);
    }

    /**
     * Load data from the DB
     *
     * @param String $methodName
     */
    private function loadDataFor($methodName)
    {
        $dar = $this->dao->searchFirstCallToMethod($methodName, ($this->currentTime - $this->timeframe));
        if ($dar && $dar->rowCount() == 1) {
            $this->nbCallToMethod[$methodName] = $this->dao->foundRows();
        } else {
            $this->nbCallToMethod[$methodName] = 0;
        }
    }

    /**
     * Verify if amount of method call respect limits
     *
     * @param String $methodName
     * @throws SOAP_NbRequestsExceedLimit_Exception
     */
    private function checkIfMethodExceedsLimits($methodName)
    {
        if ($this->nbCallToMethod[$methodName] >= $this->nbMaxCall) {
            throw new SOAP_NbRequestsExceedLimit_Exception();
        }
    }
}
