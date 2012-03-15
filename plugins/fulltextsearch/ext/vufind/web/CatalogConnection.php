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

/**
 * Catalog Connection Class
 *
 * This abstract class defines the signature for the available methods for
 * interacting with the local catalog.
 *
 * The parameters names are of no major concern as you can redefine the purpose
 * of the parameters for each method for whatever purpose your driver needs.
 * The most important element here is what the method will return.  In all cases
 * the method can return a PEAR_Error object if an error occurs.
 */
class CatalogConnection 
{
    /**
     * A boolean value that defines whether a connection has been successfully
     * made.
     * @access  public
     * @var     bool
     */
    public $status = false;

    /**
     * The object of the appropriate driver.
     * @access  private
     * @var     object
     */
    public $driver;
    
    /**
     * Constructor
     *
     * This is responsible for instantiating the driver that has been specified.
     *
     * @param   string  $driver     The name of the driver to load.
     * @return  null
     * @access  public
     */
    function __construct($driver)
    {
        global $configArray;
        $path = "{$configArray['Site']['local']}/Drivers/{$driver}.php";
        if (is_readable($path)) {
            require_once $path;
            
            try {
                $this->driver = new $driver;
            } catch (PDOException $e) {
                throw $e;
            }

            $this->status = true;
        }
    }
    
    /**
     * Get Status
     *
     * This is responsible for retrieving the status information of a certain
     * record.
     *
     * @param   string  $recordId   The record id to retrieve the holdings for
     * @return  mixed               An associative array with the following keys:
     *                              availability (boolean), status, location,
     *                              reserve, callnumber
     *                              If an error occures, return a PEAR_Error
     * @access  public
     */
    function getStatus($recordId)
    {
        return $this->driver->getStatus($recordId);
    }

    /**
     * Get Statuses
     *
     * This is responsible for retrieving the status information for a
     * collection of records.
     *
     * @param   array  $recordIds   The array of record ids to retrieve the
     *                              status for
     * @return  mixed               An associative array with the following keys:
     *                              availability (boolean), status, location,
     *                              reserve, callnumber
     *                              If an error occures, return a PEAR_Error
     * @access  public
     * @author  Chris Delis <cedelis@uillinois.edu>
     */
    function getStatuses($recordIds)
    {
        return $this->driver->getStatuses($recordIds);
    }

    /**
     * Get Holding
     *
     * This is responsible for retrieving the holding information of a certain
     * record.
     *
     * @param   string  $recordId   The record id to retrieve the holdings for
     * @return  mixed               An associative array with the following keys:
     *                              availability (boolean), status, location,
     *                              reserve, callnumber, duedate, number,
     *                              holding summary, holding notes
     *                              If an error occurs, return a PEAR_Error
     * @access  public
     */
    function getHolding($recordId)
    {
        $holding = $this->driver->getHolding($recordId);
        
        // Validate return from driver's getHolding method -- should be an array or
        // an error.  Anything else is unexpected and should become an error.
        if (!is_array($holding) && !PEAR::isError($holding)) {
            return new PEAR_Error('Unexpected return from getHolding: ' . $holding);
        }
        
        return $holding;
    }

    /**
     * Get Purchase History
     *
     * This is responsible for retrieving the acquisitions history data for the
     * specific record.
     *
     * @param   string  $recordId   The record id to retrieve the info for
     * @return  mixed               An array with the acquisitions data
     *                              If an error occures, return a PEAR_Error
     * @access  public
     */
    function getPurchaseHistory($recordId)
    {
        return $this->driver->getPurchaseHistory($recordId);
    }

    /**
     * Patron Login
     *
     * This is responsible for authenticating a patron against the catalog.
     *
     * @param   string  $username   The patron username
     * @param   string  $password   The patron password
     * @return  mixed               A string of the user's ID number
     *                              If an error occures, return a PEAR_Error
     * @access  public
     */
    function patronLogin($username, $password)
    {
        return $this->driver->patronLogin($username, $password);
    }

    /**
     * Get Patron Transactions
     *
     * This is responsible for retrieving all transactions by a specific patron.
     *
     * @param   array   $patron     The patron array
     * @return  array               Array of the patron's transactions
     *                              If an error occures, return a PEAR_Error
     * @access  public
     */
    function getMyTransactions($patron)
    {
        return $this->driver->getMyTransactions($patron);
    }

    /**
     * Get Patron Fines
     *
     * This is responsible for retrieving all fines by a specific patron.
     *
     * @param   array   $patron     The patron array
     * @return  array               Array of the patron's fines
     *                              If an error occures, return a PEAR_Error
     * @access  public
     */
    function getMyFines($patron)
    {
        return $this->driver->getMyFines($patron);
    }

    /**
     * Get Patron Holds
     *
     * This is responsible for retrieving all holds by a specific patron.
     *
     * @param   array   $patron     The patron array
     * @return  array               Array of the patron's holds
     *                              If an error occures, return a PEAR_Error
     * @access  public
     */
    function getMyHolds($patron)
    {
        return $this->driver->getMyHolds($patron);
    }

    /**
     * Get Patron Profile
     *
     * This is responsible for retrieving the profile for a specific patron.
     *
     * @param   array   $patron     The patron array
     * @return  array               Array of the patron's profile data
     *                              If an error occures, return a PEAR_Error
     * @access  public
     */
    function getMyProfile($patron)
    {
        return $this->driver->getMyProfile($patron);
    }

    /**
     * Place Hold
     *
     * This is responsible for both placing holds as well as placing recalls.
     *
     * @param   string  $recordId   The id of the bib record
     * @param   string  $patronId   The id of the patron
     * @param   string  $comment    Any comment regarding the hold or recall
     * @param   string  $type       Whether to place a hold or recall
     * @return  mixed               True if successful, false if unsuccessful
     *                              If an error occures, return a PEAR_Error
     * @access  public
     */
    function placeHold($recordId, $patronId, $comment, $type)
    {
        return $this->driver->placeHold($recordId, $patronId, $comment, $type);
    }

    /**
     * Get Hold Link
     *
     * The goal for this method is to return a URL to a "place hold" web page on
     * the ILS OPAC. This is used for ILSs that do not support an API or method
     * to place Holds.
     *
     * @param   string  $recordId   The id of the bib record
     * @return  mixed               True if successful, otherwise return a PEAR_Error
     * @access  public
     */
    function getHoldLink($recordId)
    {
        return $this->driver->getHoldLink($recordId);
    }

    function getNewItems($page = 1, $limit = 20, $daysOld, $fundId = null)
    {
        return $this->driver->getNewItems($page, $limit, $daysOld, $fundId);
    }

    function getFunds()
    {
        // Graceful degradation -- return empty fund list if no method supported.
        return method_exists($this->driver, 'getFunds') ?
            $this->driver->getFunds() : array();
    }

    function getDepartments()
    {
        // Graceful degradation -- return empty list if no method supported.
        return method_exists($this->driver, 'getDepartments') ?
            $this->driver->getDepartments() : array();
    }

    function getInstructors()
    {
        // Graceful degradation -- return empty list if no method supported.
        return method_exists($this->driver, 'getInstructors') ?
            $this->driver->getInstructors() : array();
    }

    function getCourses()
    {
        // Graceful degradation -- return empty list if no method supported.
        return method_exists($this->driver, 'getCourses') ?
            $this->driver->getCourses() : array();
    }

    function findReserves($course, $inst, $dept)
    {
        return $this->driver->findReserves($course, $inst, $dept);
    }
    
    function getSuppressedRecords()
    {
        return $this->driver->getSuppressedRecords();
    }

    /* Default method -- pass along calls to the driver if available; return
     * false otherwise.  This allows custom functions to be implemented in
     * the driver without constant modification to the connection class.
     *
     * @param   string  $methodName     The name of the called method.
     * @param   array   $params         Array of passed parameters.
     * @return  mixed                   Varies by method (false if undefined method)
     * @access  public
     */
    public function __call($methodName, $params)
    {
        $method = array($this->driver, $methodName);
        if (is_callable($method)) {
            return call_user_func_array($method, $params);
        }
        return false;
    }
}

?>