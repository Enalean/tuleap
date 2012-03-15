<?php

require_once 'HTTP/Request.php';

class Proxy_Request extends HTTP_Request
{
    private $useProxy = null;

    // Disable the proxy manually
    function disableProxy()
    {
        $this->useProxy = false;
    }

    // Enable the proxy manually
    function useProxy()
    {
        $this->useProxy = true;
    }

    // Time to send the request
    function SendRequest($saveBody = true)
    {
        // Unless the user has expressly set the proxy setting
        $defaultProxy = null;
        if ($this->useProxy == null) {
            // Is this localhost traffic?
            if (strstr($this->getUrl(), "localhost") !== false) {
                // Yes, don't proxy
                $defaultProxy = false;
            }
        }

        // Setup the proxy if needed
        //   useProxy + defaultProxy both need to be null or true
        if ($this->useProxy !== false && $defaultProxy !== false) {
            global $configArray;

            // Proxy server settings
            if (isset($configArray['Proxy']['host'])) {
                if (isset($configArray['Proxy']['port'])) {
                    $this->setProxy($configArray['Proxy']['host'], $configArray['Proxy']['port']);
                } else {
                    $this->setProxy($configArray['Proxy']['host']);
                }
            }
        }

        // Send the request via the parent class
        parent::sendRequest($saveBody);
    }
}

?>