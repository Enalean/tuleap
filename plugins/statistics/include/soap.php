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
     *              - user is not site admin
     */
    function getDiskUsageByProject($session_key, $group_id) {
        try {
            $soap_request_validator = new SOAP_RequestValidator(ProjectManager::instance(), UserManager::instance());
            $user    = $soap_request_validator->continueSession($session_key);
            $project = $soap_request_validator->getProjectById($group_id);
            $dum     = new Statistics_DiskUsageManager();
            return $dum->returnTotalProjectSize($group_id);
            
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }
    
    $GLOBALS['server']->addFunction(
        array(
            'getDiskUsageByProject',
        )
    );
}
?>
