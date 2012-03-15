<?php
/**
 *
 * Copyright (C) Villanova University 2009.
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

/**
 * AWS_Request Class
 *
 * This class is used for generating requests to Amazon's AWS API.
 *
 * @author      Demian Katz <demian.katz@villanova.edu>
 * @access      public
 */
class AWS_Request
{
    private $endpoint = 'webservices.amazon.com';
    private $method = HTTP_REQUEST_METHOD_GET;
    private $requestURI = '/onca/xml';
    private $url;
    
    /**
     * Constructor
     *
     * Sets up the parameters to send to AWS.
     *
     * @access  public
     * @param   string  $accessKeyId    Access Key ID distributed by Amazon.
     * @param   string  $operation      The API operation to perform.
     * @param   array   $extraParams    Associative array of extra API parameters.
     * @param   string  $service        The service (default = AWSECommerceService).
     */
    public function __construct($accessKeyId, $operation, $extraParams = array(), 
        $service = 'AWSECommerceService')
    {
        global $configArray;

        // Collect all the user-specified parameters:
        $params = $extraParams;
        $params['AWSAccessKeyId'] = $accessKeyId;
        $params['Service'] = $service;
        $params['Operation'] = $operation;
        
        // Add a timestamp:
        $params['Timestamp'] = gmdate('Y-m-d\TH:i:s\Z');
        
        // Alphabetize the parameters:
        ksort($params);
        
        // URL encode and assemble the parameters:
        $encodedParams = array();
        foreach ($params as $key => $value) {
            $encodedParams[] = $this->encode($key) . '=' . $this->encode($value);
        }
        $encodedParams = implode('&', $encodedParams);
        
        // Build the HMAC signature:
        $sigData = ($this->method == HTTP_REQUEST_METHOD_GET ? 'GET' : 'POST') . 
            "\n" . $this->endpoint . "\n" . $this->requestURI . "\n" . 
            $encodedParams;
        $key = $configArray['Content']['amazonsecret'];
        $hmacHash = hash_hmac('sha256', $sigData, $key, 1);
        
        // Save the final request URL:
        $this->url = 'http://' . $this->endpoint . $this->requestURI . '?' . 
            $encodedParams . 
            '&Signature=' . $this->encode(base64_encode($hmacHash));
    }

    /**
     * urlencode a string according to RFC 3986.  Needed for compatibility with PHP
     * versions prior to 5.3.
     *
     * @access  private
     * @param   string  $str        The string to urlencode.
     * @return  string              The urlencoded string.
     */
    private function encode($str)
    {
        $str = rawurlencode($str); 
        
        // Needed for compatibility with PHP versions prior to 5.3. 
        $str = str_replace('%7E', '~', $str);
        
        return $str;
    }
    
    /**
     * Perform the request represented by the object and return the API response.
     *
     * @access  public
     * @return  mixed    PEAR error on error, response body otherwise
     */
    public function sendRequest()
    {
        global $configArray;

        // Make the request:
        $client = new Proxy_Request();
        $client->setMethod($this->method);
        $client->setURL($this->url);

        $result = $client->sendRequest();
        
        // Send back the error or the response body, as appropriate:
        return PEAR::isError($result) ? $result : $client->getResponseBody();
    }
}
?>