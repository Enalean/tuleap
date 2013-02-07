<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Support for Basic Auth
 */
class ElasticSearch_TransportHTTPBasicAuth extends ElasticSearchTransportHTTP {

    public function __construct($host, $port, $user, $password) {
        parent::__construct($host, $port);
        if ($user && $password) {
            curl_setopt($this->ch, CURLOPT_USERPWD, $user .':'. $password);
        }
        curl_setopt($this->ch, CURLOPT_FAILONERROR, true);
    }

    /**
     * @see ElasticSearchTransportHTTP::call()
     */
    protected function call($url, $method="GET", $payload=false) {
        $conn = $this->ch;
        $protocol = "http";
        $requestURL = $protocol . "://" . $this->host . $url;
        curl_setopt($conn, CURLOPT_URL, $requestURL);
        curl_setopt($conn, CURLOPT_TIMEOUT, self::TIMEOUT);
        curl_setopt($conn, CURLOPT_PORT, $this->port);
        curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1) ;
        curl_setopt($conn, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($conn, CURLOPT_FORBID_REUSE , 0) ;

        if (is_array($payload) && count($payload) > 0)
            curl_setopt($conn, CURLOPT_POSTFIELDS, json_encode($payload)) ;
        else
        	curl_setopt($conn, CURLOPT_POSTFIELDS, null);

        $data = curl_exec($conn);
        if ($data !== false)
            $data = json_decode($data, true);
        else
        {
            /**
             * cUrl error code reference can be found here:
             * http://curl.haxx.se/libcurl/c/libcurl-errors.html
             */
            $errno = curl_errno($conn);
            switch ($errno)
            {
                case CURLE_UNSUPPORTED_PROTOCOL:
                    $error = "Unsupported protocol [$protocol]";
                    break;
                case CURLE_FAILED_INIT:
                    $error = "Internal cUrl error?";
                    break;
                case CURLE_URL_MALFORMAT:
                    $error = "Malformed URL [$requestURL] -d " . json_encode($payload);
                    break;
                case CURLE_COULDNT_RESOLVE_PROXY:
                    $error = "Couldnt resolve proxy";
                    break;
                case CURLE_COULDNT_RESOLVE_HOST:
                    $error = "Couldnt resolve host";
                    break;
                case CURLE_COULDNT_CONNECT:
                    $error = "Could not reach the the fulltext search server at [{$this->host}]. Please contact the site admin.";
                    break;
                case CURLE_OPERATION_TIMEDOUT:
                    $error = "Operation timed out on [$requestURL]";
                    break;
                default:
                    $status = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
                    switch ($status) {
                    case 404:
                        $error = "404 Not Found. Document may have not been indexed. $requestURL.";
                        break;
                    case 401:
                        $error = "Server misconfigured. Tuleap cannot access to host [{$this->host}]";
                        break;
                    default:
                        $error = "Unknown error (status: $status) (errno: $errno) ($requestURL)";
                        if ($errno == 0)
                            $error .= ". Non-cUrl error";
                    }
                    break;
            }
            $exception = new ElasticSearchTransportHTTPException($error);
            $exception->payload = $payload;
            $exception->port = $this->port;
            $exception->protocol = $protocol;
            $exception->host = $this->host;
            $exception->method = $method;
            throw $exception;
        }

        if (array_key_exists('error', $data))
            $this->handleError($url, $method, $payload, $data);

        return $data;
    }
}
?>
