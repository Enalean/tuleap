<?php

require_once ('pre.php');
require_once ('session.php');
require_once ('utils_soap.php');
require_once('common/include/SOAPRequest.class.php');
require_once('common/include/MIME.class.php');
require_once 'common/soap/SOAP_RequestValidator.class.php';

// define fault code constants
define('PLUGIN_STATISTICS_SOAP_FAULT_UNAVAILABLE_PLUGIN', '3020');
if (defined('NUSOAP')) {
    //
    // Function definition
    //
    $GLOBALS['server']->register(
        'getDiskUsageByProject', // method name
        array('sessionKey'=>'xsd:string', // input parameters
              'group_id'=>'xsd:int'
        ),
        array('return'=>'xsd:int'), // output parameters
        $GLOBALS['uri'], // namespace
        $GLOBALS['uri'].'#getDiskUsageByProject', // soapaction
        'rpc', // style
        'encoded', // use
        'Returns an int corresponding to the space occupied by group ID on the disk.
         Returns a soap fault if the group ID does not match with a valid project.' // documentation
    );

} else {
    //
    // SOAP function implementations
    //

    /**
     * getDiskUsageByProject - get disk used by a project
     *
     * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
     * @param int $group_id the ID of the group we want to attach the file
     * @return int the disk used
     *              or a soap fault if :
     *              - group_id does not match with a valid project
     */
    function getDiskUsageByProject($session_key, $group_id) {
        $user    = $this->soap_request_validator->continueSession($session_key);
        $project = $this->soap_request_validator->getProjectById($group_id);
    }
    
    $GLOBALS['server']->addFunction(
        array(
            'getDiskUsageByProject',
        )
    );
}
?>
