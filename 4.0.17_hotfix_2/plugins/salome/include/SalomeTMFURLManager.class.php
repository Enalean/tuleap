<?php
/**
 * @copyright Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 *
 * SalomeTMFURLManager
 */

class SalomeTMFURLManager {
	
	/**
     * @var string $_url the url
     */
    var $_url;
    
    /**
     * @var array $_array_url the array of components of the url
     */
    var $_array_url;
    
    /**
     * Constructor
     *
     */
    function SalomeTMFURLManager($url) {
    	$this->_url = $url;
        $this->_array_url = parse_url($url);
    }
    
    /**
     * Get the host of the url
     * 
     * @return string the host of the url, or null if no host
     */
    function getHost() {
        if (array_key_exists('host', $this->_array_url)) {
            return $this->_array_url['host'];
        } elseif (array_key_exists('path', $this->_array_url)) {
            return $this->_array_url['path'];
        } else {
            return null;
        }
    }
    
    /**
     * Get the port of the url
     *
     * @return string the port of the url, or null if no port
     */
	function getPort() {
        if (array_key_exists('port', $this->_array_url)) {
            return $this->_array_url['port'];
        } else {
            return null;
        }
    }
    
    /**
     * Get the scheme of the url
     *
     * @return string the scheme of the url, or null if no scheme
     */
    function getScheme() {
        if (array_key_exists('scheme', $this->_array_url)) {
            return $this->_array_url['scheme'];
        } else {
            return null;
        }
    }
    
    /**
     * Get the salome JDBC url
     *
     * @return string the salome JDBC url
     */
    function getJDBCUrl() {
        return 'jdbc:mysql://' . $this->getHost() . '/salome';
    }

}

?>