<?php

/**
 * Copyright (c) Enalean, 2012. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */
require_once 'HttpCurlClientException.class.php';
class HttpCurlClient
{
    /**
     * 
     */
    private $curl_handle;
        
    /**
     *
     * @var type 
     */
    private $curl_response;
    
    /**
     *
     * @var array 
     */
    private $curl_options = array();
    
    /**
     * Initiates a curl handle.
     */
    public function __construct() {
        $this->curl_handle = curl_init();
        $this->setOption(CURLINFO_HEADER_OUT, true);
    }
    
    /**
     * 
     * @return null | array
     */
    public function getLastRequest() {
        return curl_getinfo($this->curl_handle);
    }
    
    /**
     * If the option CURLOPT_RETURNTRANSFER is not set or set to FALSE, then
     * this will return a boolean.
     * If no request has been made then this will return NULL.
     * If CURLOPT_RETURNTRANSFER is set to TRUE, then all values will be returned as an array.
     * @return bool | null | array
     */
    public function getLastResponse() {
        return $this->curl_response;
    }
    
    /**
     * 
     * @return string | null
     */
    public function getLastError() {
        return curl_error($this->curl_handle);
    }
    
    /**
     * 
     * @return int
     */
    public function getErrorCode() {
        return curl_errno($this->curl_handle);
    }

    /**
     * 
     * @param string $name A valid curl option
     * @param multiple $value The curloption value
     */
    public function setOption($name, $value) {
        $this->curl_options[$name] = $value;
    }
    
    /**
     * 
     * @param string $name
     * @return multiple The curloption value
     */
    public function getOption($name) {
        return (isset($this->curl_options[$name])) ? $this->curl_options[$name] : null;
    }

    /**
     * 
     * @param array $options
     */
    public function setOptions(array $options) {
        $this->curl_options = $options;
    }
    
    /**
     * 
     * @return array
     */
    public function getOptions() {
        return $this->curl_options;
    }
    
    /**
     * 
     * @param array $options
     */
    public function addOptions(array $options) {
        foreach ($options as $name => $value) {
            $this->setOption($name, $value);
        }
    }


    /**
     * If the option CURLOPT_RETURNTRANSFER is not set or set to FALSE, then
     * this will return a boolean.
     * If no request has been made then this will return NULL.
     * If CURLOPT_RETURNTRANSFER is set to TRUE, then all values will be returned as an array.
     * @return bool | null | array
     */
    public function execute() {
        curl_setopt_array($this->curl_handle, $this->curl_options);
        return curl_exec($this->curl_handle);
    }

    /**
     * 
     * @param string $name
     * @return string
     */
    public function getInfo($name) {
        return curl_getinfo($this->curl_handle, $name);
    }

    /**
     * Closes curl session
     */
    public function close() {
        curl_close($this->curl_handle);
    }
    
    /**
     * Protected method to execute requests.
     * Should be called indirectly through specific methods.
     * 
     * @param array $options curl options
     * @throws Tracker_Exception
     */
    protected function doRequest() {
        $this->curl_response = $this->execute();
        
        if ($this->getErrorCode()) {
            throw new HttpCurlClientException($this->getLastError());
        }
    }
}
?>