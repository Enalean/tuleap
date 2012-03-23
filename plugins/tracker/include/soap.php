<?php

//define fault code constants
define ('get_group_fault', '3000');
define ('get_artifact_type_factory_fault', '3002');
define ('get_artifact_factory_fault', '3003');
define ('get_artifact_field_factory_fault', '3004');    
define ('get_artifact_type_fault', '3005');
define ('get_artifact_fault', '3006');
define ('create_artifact_fault', '3007');
define ('invalid_field_dependency_fault', '3009');
define ('update_artifact_fault', '3010');
define ('get_artifact_file_fault', '3011');
define ('add_dependency_fault', '3012');
define ('delete_dependency_fault', '3013');
define ('create_followup_fault', '3014');
define ('get_artifact_field_fault', '3015');
define ('add_cc_fault', '3016');
define ('invalid_field_fault', '3017');
define ('delete_cc_fault', '3018');
define ('get_service_fault', '3020');
define ('get_artifact_report_fault', '3021');
define('update_artifact_followup_fault','3022');
define('delete_artifact_followup_fault','3023');

define('get_tracker_factory_fault','3024');
define('get_tracker_fault','3025');


require_once ('pre.php');
require_once ('session.php');
require_once ('utils_soap.php');

require_once ('Tracker/Tracker.class.php');
require_once ('Tracker/TrackerFactory.class.php');
require_once ('Tracker/Artifact/Tracker_Artifact.class.php');
require_once ('Tracker/Artifact/Tracker_ArtifactFactory.class.php');
require_once ('Tracker/FormElement/Tracker_FormElementFactory.class.php');

if (defined('NUSOAP')) {

//
// Type definition
//
$GLOBALS['server']->wsdl->addComplexType(
    'Tracker',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'tracker_id' => array('name'=>'tracker_id', 'type' => 'xsd:int'),
        'group_id' => array('name'=>'group_id', 'type' => 'xsd:int'),
        'name' => array('name'=>'name', 'type' => 'xsd:string'),
        'description' => array('name'=>'description', 'type' => 'xsd:string'),
        'item_name' => array('name'=>'item_name', 'type' => 'xsd:string'),        
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'ArrayOfTracker',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:Tracker[]')),
    'tns:Tracker'
);

$GLOBALS['server']->wsdl->addComplexType(
    'TrackerField',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'tracker_id' => array('name'=>'tracker_id', 'type' => 'xsd:int'),
        'field_id' => array('name'=>'field_id', 'type' => 'xsd:int'),
        'short_name' => array('name'=>'short_name', 'type' => 'xsd:string'),
        'label' => array('name'=>'label', 'type' => 'xsd:string'),
        'type' => array('name'=>'type', 'type' => 'xsd:string'),
        'values' => array('name'=>'type', 'type' => 'tns:ArrayOfTrackerFieldBindValue')
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'ArrayOfTrackerField',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:TrackerField[]')),
    'tns:TrackerField'
);

$GLOBALS['server']->wsdl->addComplexType(
    'TrackerFieldBindValue',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'field_id' => array('name'=>'field_id', 'type' => 'xsd:int'),
        'bind_value_id' => array('name'=>'baind_value_id', 'type' => 'xsd:int'),
        'bind_value_label' => array('name'=>'bind_value_label', 'type' => 'xsd:string')
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'ArrayOfTrackerFieldBindValue',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:TrackerFieldBindValue[]')),
    'tns:TrackerFieldBindValue'
);

$GLOBALS['server']->wsdl->addComplexType(
    'ArtifactFieldValue',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'field_name' => array('name' => 'field_name', 'type' => 'xsd:string'),
        'field_label' => array('name' => 'field_label', 'type' => 'xsd:string'),
        'field_value' => array('name' => 'field_value', 'type' => 'xsd:string')
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'ArrayOfArtifactFieldValue',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:ArtifactFieldValue[]')
    ),
    'tns:ArtifactFieldValue'
);
$GLOBALS['server']->wsdl->addComplexType(
    'Artifact',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'artifact_id' => array('name'=>'artifact_id', 'type' => 'xsd:int'),
        'tracker_id' => array('name'=>'tracker_id', 'type' => 'xsd:int'),
        'value' => array('name'=>'value', 'type' => 'tns:ArrayOfArtifactFieldValue')
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'ArrayOfArtifact',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:Artifact[]')),
    'tns:Artifact'
);

$GLOBALS['server']->wsdl->addComplexType(
    'Criteria',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'field_name' => array('name'=>'field_name', 'type' => 'xsd:string'),
        'field_value' => array('name'=>'field_value', 'type' => 'xsd:string'),
        'operator' => array('name'=>'operator', 'type' => 'xsd:string')
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'ArrayOfCriteria',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:Criteria[]')),
    'tns:Criteria'
);

$GLOBALS['server']->wsdl->addComplexType(
    'SortCriteria',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'field_name' => array('name'=>'field_name', 'type' => 'xsd:string'),
        'sort_direction' => array('name'=>'sort_direction', 'type' => 'xsd:string')
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'ArrayOfSortCriteria',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:SortCriteria[]')),
    'tns:SortCriteria'
);


$GLOBALS['server']->wsdl->addComplexType(
    'ArtifactQueryResult',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'total_artifacts_number' => array('name'=>'total_artifacts_number', 'type' => 'xsd:int'),
        'artifacts' => array('name'=>'artifacts', 'type' => 'tns:ArrayOfArtifact')
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'ArtifactFile',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'id' => array('name'=>'id', 'type' => 'xsd:int'),
        'artifact_id' => array('name'=>'artifact_id', 'type' => 'xsd:int'),
        'filename' => array('name'=>'filename', 'type' => 'xsd:string'),
        'description' => array('name'=>'description', 'type' => 'xsd:string'),
        'bin_data' => array('name'=>'bin_data', 'type' => 'xsd:base64Binary'),
        'filesize' => array('name'=>'filesize', 'type' => 'xsd:int'),
        'filetype' => array('name'=>'filetype', 'type' => 'xsd:string'),
        'adddate' => array('name'=>'adddate', 'type' => 'xsd:int'),
        'submitted_by' => array('name'=>'submitted_by', 'type' => 'xsd:string')
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'ArrayOfArtifactFile',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:ArtifactFile[]')),
    'tns:ArtifactFile'
);

$GLOBALS['server']->wsdl->addComplexType(
    'ArtifactHistory',
    'complexType',
    'struct',
    'sequence',
    '',
    array(                  
        'artifact_id' => array('name'=>'artifact_id', 'type' => 'xsd:int'),    
        'changeset_id' => array('name'=>'changeset_id', 'type' => 'xsd:int'),
        'changes' => array('name'=>'changes', 'type' => 'tns:ArrayOfString'),
        'modification_by' => array('name'=>'modification_by', 'type' => 'xsd:string'),
        'date' => array('name'=>'date', 'type' => 'xsd:int'),
        'comment' => array('name'=>'comment', 'type' => 'tns:ArtifactFollowup')
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'ArrayOfArtifactHistory',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:ArtifactHistory[]')),
    'tns:ArtifactHistory'
);

$GLOBALS['server']->wsdl->addComplexType(
    'ArrayOfInt',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'xsd:int[]')),
    'xsd:int'
);

//
// Function definition
//
$GLOBALS['server']->register(
    'getTrackerList', // method name
    array('sessionKey'=>'xsd:string', // input parameters
          'group_id'=>'xsd:int'
    ),
    array('return'=>'tns:ArrayOfTracker'), // output parameters
    $GLOBALS['uri'], // namespace
    $GLOBALS['uri'].'#getTrackerList', // soapaction
    'rpc', // style
    'encoded', // use
    'Returns the array of Tracker that belongs to the group identified by group ID.
     Returns a soap fault if the group ID does not match with a valid project.' // documentation
);

$GLOBALS['server']->register(
    'getTrackerFields', // method name
    array('sessionKey'=>'xsd:string', // input parameters
          'group_id'=>'xsd:int',
          'tracker_id'=>'xsd:int',
    ),
    array('return'=>'tns:ArrayOfTrackerFields'), // output parameters
    $GLOBALS['uri'], // namespace
    $GLOBALS['uri'].'#getTrackerFields', // soapaction
    'rpc', // style
    'encoded', // use
    'Returns the array of Trackerfields that are used in the tracker tracker_id of the project group_id.
     Returns a soap fault if the tracker ID or the group ID does not match with a valid project.' // documentation
);

$GLOBALS['server']->register(
    'getArtifacts',
    array('sessionKey'=>'xsd:string',
          'group_id'=>'xsd:int',
          'tracker_id'=>'xsd:int',
          'criteria' => 'tns:ArrayOfCriteria',
          'offset' => 'xsd:int',
          'max_rows' => 'xsd:int'
    ),
    array('return'=>'tns:ArtifactQueryResult'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#getArtifacts',
    'rpc',
    'encoded',
    'Returns the ArtifactQueryResult of the tracker tracker_id in the project group_id 
     that are matching the given criteria. If offset AND max_rows are filled, it returns only 
     max_rows artifacts, skipping the first offset ones.
     It is not possible to sort artifact with this function (use getArtifactsFromReport if you want to sort).
     Returns a soap fault if the group_id is not a valid one or if the tracker_id is not a valid one.'
);

$GLOBALS['server']->register(
    'addArtifact',
    array('sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'tracker_id'=>'xsd:int',
        'value'=>'tns:ArrayOfArtifactFieldValue'
    ),
    array('return'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#addArtifact',
    'rpc',
    'encoded',
    'Add an Artifact in the tracker tracker_id of the project group_id with the values given by 
     value (an ArtifactFieldValue).
     Returns the Id of the created artifact if the creation succeed.
     Returns a soap fault if the group_id is not a valid one, if the tracker_name is not a valid one, or if the add failed.'
);

$GLOBALS['server']->register(
    'updateArtifact',
    array('sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'tracker_id'=>'xsd:int',
        'artifact_id'=>'xsd:int',
        'value'=>'tns:ArrayOfArtifactFieldValue',
        'comment' => 'xsd:string'
    ),
    array('return'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#updateArtifact',
    'rpc',
    'encoded',
    'Update the artifact $artifact_id of the tracker $tracker_id in the project group_id with the values given by 
     value. Add a follow-up comment $comment.
     Returns a soap fault if the group_id is not a valid one, if the tracker_id is not a valid one, 
     if the artifart_id is not a valid one, or if the update failed.'
);

/*$GLOBALS['server']->register(
    'getArtifactAttachedFiles',
    array('sessionKey'=>'xsd:string',
          'group_id'=>'xsd:int',
          'group_artifact_id'=>'xsd:int',
          'artifact_id'=>'xsd:int'
    ),
    array('return'=>'tns:ArrayOfArtifactFile'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#getArtifactAttachedFiles',
    'rpc',
    'encoded',
    'Returns the array of attached files (ArtifactFile) attached to the artifact artifact_id in the tracker group_artifact_id of the project group_id. 
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one, 
     or if the artifact_id is not a valid one. NOTE : for performance reasons, the result does not contain the content of the file. Please use getArtifactAttachedFile to get the content of a single file'
);*/

/*$GLOBALS['server']->register(
    'getArtifactAttachedFile',
    array('sessionKey'=>'xsd:string',
          'group_id'=>'xsd:int',
          'group_artifact_id'=>'xsd:int',
          'artifact_id'=>'xsd:int',
          'file_id'=>'xsd:int'
    ),
    array('return'=>'tns:ArtifactFile'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#getArtifactAttachedFile',
    'rpc',
    'encoded',
    'Returns the attached file (ArtifactFile) with the id file_id attached to the artifact artifact_id in the tracker group_artifact_id of the project group_id. 
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one, 
     if the artifact_id is not a valid one, or if the file_id doesnt match with the given artifact_id.'
);*/


$GLOBALS['server']->register(
    'getArtifact',
    array('sessionKey'=>'xsd:string',
          'group_id'=>'xsd:int',
          'tracker_id'=>'xsd:int',
          'artifact_id'=>'xsd:int'
    ),
    array('return'=>'tns:Artifact'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#getArtifact',
    'rpc',
    'encoded',
    'Returns the artifact (Artifact) identified by the id artifact_id in the tracker tracker_id of the project group_id. 
     Returns a soap fault if the group_id is not a valid one, if the tracker_id is not a valid one, 
     or if the artifact_id is not a valid one.'
);

/*$GLOBALS['server']->register(
    'addArtifactAttachedFile',
    array('sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'group_artifact_id'=>'xsd:int',
        'artifact_id'=>'xsd:int',
        'encoded_data'=>'xsd:string',
        'description'=>'xsd:string',
        'filename'=>'xsd:string',
        'filetype'=>'xsd:string'
    ),
    array('return'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#addArtifactAttachedFile',
    'rpc',
    'encoded',
    'Add an attached file to the artifact artifact_id of the tracker group_artifact_id of the project group_id. 
     The attached file is described by the raw encoded_data (encoded in base64), the description of the file, 
     the name of the file and it type (the mimi-type -- plain/text, image/jpeg, etc ...). 
     Returns the ID of the attached file if the attachment succeed.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one, 
     or if the artifact_id is not a valid one, or if the attachment failed.'
);*/

/*$GLOBALS['server']->register(
    'deleteArtifactAttachedFile',
    array('sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'group_artifact_id'=>'xsd:int',
        'artifact_id'=>'xsd:int',
        'file_id'=>'xsd:int'
    ),
    array('return'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#deleteArtifactAttachedFile',
    'rpc',
    'encoded',
    'Delete the attached file file_id from the artifact artifact_id of the tracker group_artifact_id of the project group_id. 
     Returns the ID of the deleted file if the deletion succeed. 
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one, 
     if the artifact_id is not a valid one, if the file_id is not a valid one or if the deletion failed.'
);*/

/*$GLOBALS['server']->register(
    'getArtifactHistory',
    array('sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'tracker_id' => 'xsd:int',
        'artifact_id' => 'xsd:int'
    ),
    array('return'=>'tns:ArrayOfArtifactHistory'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#getArtifactHistory',
    'rpc',
    'encoded',
    'Get the history of the artifact (the history of the fields values)'
);*/
    
} else {
    

/**
 * getTrackerList - returns an array of Tracker that belongs to the project identified by group_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the list of trackers
 * @return array the array of SOAPTracker that belongs to the project identified by $group_id, or a soap fault if group_id does not match with a valid project.
 */
function getTrackerList($sessionKey, $group_id) {
    if (session_continue($sessionKey)) {
        $pm = ProjectManager::instance();
        try {
            $project = $pm->getGroupByIdForSoap($group_id, 'getTrackerList');
        } catch (SoapFault $e) {
            return $e;
        }

        if (!$project->usesService('tracker')) {
            return new SoapFault(get_service_fault, 'Tracker service is not used for this project.', 'getTrackerList');
        }
        
        $tf = TrackerFactory::instance();
        if (!$tf) {
            return new SoapFault(get_tracker_factory_fault, 'Could Not Get TrackerFactory','getTrackerList');
        } 
        
        // The function getTrackersByGroupId returns all trackers, 
        // even those the user is NOT allowed to view -> we will filter in trackerlist_to_soap
        $trackers = $tf->getTrackersByGroupId($group_id);
        return trackerlist_to_soap($trackers);
    } else {
        return new SoapFault(invalid_session_fault,'Invalid Session','getTrackerList');
    }
}

/**
 * trackerlist_to_soap : return the soap ArrayOfTracker structure giving an array of PHP Tracker Object.
 * @access private
 * 
 * WARNING : We check the permissions here : only the readable trackers are returned.
 *
 * @param array of Object{Tracker} $tf_arr the array of ArtifactTrackers to convert.
 * @return array the SOAPArrayOfTracker corresponding to the array of Trackers Object
 */
function trackerlist_to_soap($tf_arr) {
    $user_id = UserManager::instance()->getCurrentUser()->getId();
    $return = array();
    foreach ($tf_arr as $tracker_id => $tracker) {
            
            // Check if this tracker is active (not deleted)
            if ( !$tracker->isActive()) {
                return new SoapFault(get_tracker_fault, 'This tracker is no longer valid.','getTrackerList');
            }
           
            // Check if the user can view this tracker
            if ($tracker->userCanView($user_id)) {
                
                // get the reports description (light desc of reports)
                //$report_fact = new ArtifactReportFactory();
                /*if (!$report_fact || !is_object($report_fact)) {
                    return new SoapFault(get_artifact_type_fault, 'Could Not Get ArtifactReportFactory', 'getArtifactTrackers');
                }
                $reports_desc = artifactreportsdesc_to_soap($report_fact->getReports($at_arr[$i]->data_array['group_artifact_id'], $user_id));*/
                
                $return[]=array(
                    'tracker_id'  => $tracker->getId(),
                    'group_id'    => $tracker->getGroupID(),
                    'name'        => SimpleSanitizer::unsanitize($tracker->getName()),
                    'description' => SimpleSanitizer::unsanitize($tracker->getDescription()),
                    'item_name'   => $tracker->getItemName()
                    /*'reports_desc' => $reports_desc*/
                );
            }
    }
    return $return;
}



/**
 * getTrackerFields - returns an array of TrackerFields used in the tracker tracker_id of the project identified by group_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the project
 * @param int $tracker_id the ID of the Tracker
 * @return array the array of SOAPTrackerFields used in the tracker $tracker_id in the project identified by $group_id, 
 *          or a soap fault if tracker_id or group_id does not match with a valid project/tracker.
 */
function getTrackerFields($sessionKey, $group_id, $tracker_id) {
    if (session_continue($sessionKey)) {
        $pm = ProjectManager::instance();
        try {
            $project = $pm->getGroupByIdForSoap($group_id, 'getTrackerFields');
        } catch (SoapFault $e) {
            return $e;
        }

        if (!$project->usesService('tracker')) {
            return new SoapFault(get_service_fault, 'Tracker service is not used for this project.', 'getTrackerFields');
        }
        
        $tf = TrackerFactory::instance();
        if (!$tf) {
            return new SoapFault(get_tracker_factory_fault, 'Could Not Get Tracker Factory','getTrackerFields');
        }
        $tracker = $tf->getTrackerById($tracker_id);
        if ($tracker == null) {
            return new SoapFault(get_tracker_factory_fault, 'Could Not Get Tracker','getTrackerFields');
        } elseif ($tracker->getGroupId() != $group_id) {
            return new SoapFault(get_tracker_fault, 'Could not get Tracker.', 'getTrackerFields');
        }
        
        $fef = Tracker_FormElementFactory::instance();
        if (!$fef) {
            return new SoapFault(get_tracker_factory_fault, 'Could Not Get Field Factory','getTrackerFields');
        }        
        // The function getTrackerFields returns all tracker fields, 
        // even those the user is NOT allowed to view -> we will filter in trackerlist_to_soap
        $tracker_fields = $fef->getUsedFields($tracker);
        return trackerfields_to_soap($tracker, $tracker_fields);
    } else {
        return new SoapFault(invalid_session_fault,'Invalid Session','getTrackerFields');
    }
}

/**
 * trackerfields_to_soap : return the soap ArrayOfTrackerField structure giving an array of PHP Tracker_FormElement_Field Object.
 * @access private
 * 
 * WARNING : We check the permissions here : only the readable fields are returned.
 * 
 * @param Tracker $tracker the tracker
 * @param array of Object{Field} $tracker_fields the array of TrackerFields to convert.
 * @return array the SOAPArrayOfTrackerField corresponding to the array of Tracker Fields Object
 */
function trackerfields_to_soap($tracker, $tracker_fields) {
    $user = UserManager::instance()->getCurrentUser();
    $fef  = Tracker_FormElementFactory::instance();
    $return = array();
    foreach ($tracker_fields as $tracker_field) {
            // Check if the user can read this field
            if ($tracker_field->userCanRead( $user )) {
                $return[] = array(
                    'tracker_id' => $tracker->getId(),
                    'field_id'   => $tracker_field->getId(),
                    'short_name' => $tracker_field->getName(),
                    'label'      => $tracker_field->getLabel(),
                    'type'       => $fef->getType($tracker_field),
                    'values'     => $tracker_field->getSoapAvailableValues(),
                );
            }
    }
    return $return;
}

/**
 * getArtifacts - returns an ArtifactQueryResult that belongs to the project $group_id, to the tracker $group_artifact_id,
 *                and that match the criteria $criteria. If $offset and $max_rows are filled, the number of returned artifacts
 *                will not exceed $max_rows, beginning at $offset.
 *
 * !!!!!!!!!!!!!!!
 * !!! Warning : If $max_rows is not filled, $offset is not taken into account. !!!
 * !!!!!!!!!!!!!!!
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the array of artifacts
 * @param int $tracker_id the ID of the tracker we want to retrieve the array of artifacts
 * @param array{SOAPCriteria} $criteria the criteria that the set of artifact must match
 * @param int $offset number of artifact skipped. Used in association with $max_rows to limit the number of returned artifact.
 * @param int $max_rows the maximum number of artifacts returned
 * @return the SOAPArtifactQueryResult that match the criteria $criteria and belong to the project $group_id and the tracker $group_artifact_id,
 *          or a soap fault if group_id does not match with a valid project, or if group_artifact_id does not match with a valid tracker.
 */
function getArtifacts($sessionKey,$group_id,$tracker_id, $criteria, $offset, $max_rows) {
    if (session_continue($sessionKey)) {
        $pm = ProjectManager::instance();
        try {
            $project = $pm->getGroupByIdForSoap($group_id, 'getArtifacts');
        } catch (SoapFault $e) {
            return $e;
        }

        if (!$project->usesService('tracker')) {
            return new SoapFault(get_service_fault, 'Tracker service is not used for this project.', 'getArtifacts');
        }
        
        $tf = TrackerFactory::instance();
        if (!$tf) {
            return new SoapFault(get_tracker_factory_fault, 'Could Not Get TrackerFactory', 'getArtifacts');
        } 
        
        $tracker = $tf->getTrackerById($tracker_id);
        
        if ($tracker == null) {
            return new SoapFault(get_tracker_factory_fault, 'Could Not Get Tracker', 'getArtifacts');
        } else {
            if (! $tracker->userCanView()) {
                return new SoapFault(get_tracker_factory_fault,'Permission Denied: You are not granted sufficient permission to perform this operation.', 'getArtifacts');
            } else {
                $af = Tracker_ArtifactFactory::instance();
                $artifacts = $af->getArtifactsByTrackerId($tracker_id);
            }
        }
        
        // the function getArtifacts returns all artifacts without whecking if user is allowed to see them
        // => we need to fliter them
        return artifact_query_result_to_soap($artifacts); 
    } else {
        return new SoapFault(invalid_session_fault,'Invalid Session ','getArtifacts');
    }
}

/**
 * getArtifact - returns the Artifacts that belongs to the project $group_id, to the tracker $tracker_id,
 *                  and that is identified by the ID $artifact_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the artifact
 * @param int $tracker_id the ID of the tracker we want to retrieve the artifact
 * @param int $artifact_id the ID of the artifact we are looking for
 * @return array the SOAPArtifact identified by ID $artifact_id,
 *          or a soap fault if group_id does not match with a valid project, or if tracker_id does not match with a valid tracker,
 *          or if artifact_id is not a valid artifact of this tracker.
 */
function getArtifact($sessionKey,$group_id,$tracker_id, $artifact_id) {
    if (session_continue($sessionKey)){
        $pm = ProjectManager::instance();
        try {
            $project = $pm->getGroupByIdForSoap($group_id, 'getArtifact');
        } catch (SoapFault $e) {
            return $e;
        }

        if (!$project->usesService('tracker')) {
            return new SoapFault(get_service_fault, 'Tracker service is not used for this project.', 'getArtifact');
        }
        
        $tf = TrackerFactory::instance();
        if (!$tf) {
            return new SoapFault(get_tracker_factory_fault, 'Could Not Get TrackerFactory', 'getArtifact');
        } 
        
        $tracker = $tf->getTrackerById($tracker_id);
        
        if ($tracker == null) {
            return new SoapFault(get_tracker_factory_fault, 'Could Not Get Tracker', 'getArtifact');
        } else {
            if (! $tracker->userCanView()) {
                return new SoapFault(get_tracker_factory_fault,'Permission Denied: You are not granted sufficient permission to perform this operation.', 'getArtifact');
            } else {
                $af = Tracker_ArtifactFactory::instance();
                $artifact = $af->getArtifactById($artifact_id);
                if ($artifact) {
                    return artifact_to_soap($artifact);
                } else {
                    return new SoapFault(get_artifact_fault, 'Could Not Get Artifact', 'getArtifact');
                }
            }
        }
    } else {
       return new SoapFault(invalid_session_fault,'Invalid Session','getArtifact');
    }
}

/**
 * artifact_to_soap : return the soap artifact structure giving a PHP Artifact Object.
 * @access private
 * 
 * WARNING : We check the permissions here : only the readable fields are returned.
 *
 * @param Object{Artifact} $artifact the artifact to convert.
 * @return array the SOAPArtifact corresponding to the Artifact Object
 */
function artifact_to_soap($artifact) {
    $return = array();
    
    // We check if the user can view this artifact
    if ($artifact->userCanView()) {
        
        // artifact_id
        $return['artifact_id'] = $artifact->getId();
        // tracker_id
        $return['tracker_id'] = $artifact->getTrackerId();
        
        // value
        $artifact_value = array();
        $last_changeset = $artifact->getLastChangeset();
        $last_changeset_values = $last_changeset->getValues();
        $ff = Tracker_FormElementFactory::instance();
        foreach ($last_changeset_values as $field_id => $field_value) {
            
            
            if ($field_value) {
            
            
            
            if ($field = $ff->getFormElementById($field_id)) {
                if ($field->userCanRead()) {
                    $artifact_value[] = array(
                        'field_name' => $field->getName(),
                        'field_label' => $field->getLabel(),
                        'field_value' => $field_value->getSoapValue()
                    );
                }
            }
            
            
            }
            
            
            
        }
        $return['value'] = $artifact_value;
        
    }
    return $return;
}

function artifacts_to_soap($artifacts) {
    $return = array();
    foreach ($artifacts as $artifact_id => $artifact) {
        $return[] = artifact_to_soap($artifact);
    }
    return $return;
}

function artifact_query_result_to_soap($artifacts) {
    $return = array();
    if ($artifacts == false) {
        $return['artifacts'] = null;
        $return['total_artifacts_number'] = 0;
    } else {
        $return['artifacts'] = artifacts_to_soap($artifacts);
        $return['total_artifacts_number'] = count($return['artifacts']); 
    }
    return $return;
}

/**
 * addArtifact - add an artifact in tracker $tracker_id of the project $group_id with given values
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int    $group_id   the ID of the group we want to add the artifact
 * @param int    $tracker_id the ID of the tracker we want to add the artifact
 * @param array  $value      The fields values of the artifact (array of {SOAPArtifactFieldValue})
 *
 * @return int the ID of the new created artifact, 
 *              or a soap fault if :
 *              - group_id does not match with a valid project, 
 *              - tracker_name does not match with a valid tracker,
 *              - the user does not have the permissions to submit an artifact
 *              - the given values are breaking a field dependency rule
 *              - the artifact creation failed.
 */
function addArtifact($sessionKey, $group_id, $tracker_id, $value) {
    
    if (session_continue($sessionKey)) {
        $user = UserManager::instance()->getCurrentUser();
        $pm = ProjectManager::instance();
        try {
            $project = $pm->getGroupByIdForSoap($group_id, 'addArtifact');
        } catch (SoapFault $e) {
            return $e;
        }

        $tf = TrackerFactory::instance();
        $tracker = $tf->getTrackerById($tracker_id);
        if ($tracker == null) {
            return new SoapFault(get_tracker_fault, 'Could not get Tracker.', 'addArtifact');
        } elseif ($tracker->getGroupId() != $group_id) {
            return new SoapFault(get_tracker_fault, 'Could not get Tracker.', 'addArtifact');
        }
        
        $fef = Tracker_FormElementFactory::instance();
        
        $fields_data = array();
        foreach ($value as $field_value) {
            // field are identified by name, we need to retrieve the field id
            if ($field_value->field_name) {
                
                $field = $fef->getUsedFieldByName($tracker_id, $field_value->field_name);
                if ($field) {
                    
                    $field_data = $field->getFieldData($field_value->field_value);
                    if ($field_data != null) {
                        // $field_value is an object: SOAP must cast it in ArtifactFieldValue
                        if (isset($fields_data[$field->getId()])) {
                            if ( ! is_array($fields_data[$field->getId()]) ) {
                                $fields_data[$field->getId()] = array($fields_data[$field->getId()]);
                            }
                            $fields_data[$field->getId()][] = $field_data;
                        } else {
                            $fields_data[$field->getId()] = $field_data;
                        }
                    } else {
                        return new SoapFault(update_artifact_fault, 'Unknown value ' . $field_value->field_value . ' for field: '.$field_value->field_name ,'addArtifact');
                    }
                } else {
                    return new SoapFault(update_artifact_fault, 'Unknown field: '.$field_value->field_name ,'addArtifact');
                }
            }
        }
        
        $af = Tracker_ArtifactFactory::instance();
        if ($artifact = $af->createArtifact($tracker, $fields_data, $user, null)) {
            return $artifact->getId();
        } else {
            $response = new Response();
            if ($response->feedbackHasErrors()) {
                return new SoapFault(update_artifact_fault, $response->getRawFeedback(),'addArtifact');
            } else {
                return new SoapFault(update_artifact_fault, 'Unknown error','addArtifact');
            }
        }
        
    } else {
           return new SoapFault(invalid_session_fault,'Invalid Session ','addArtifact');
    }
}

/**
 * updateArtifact - update the artifact $artifact_id in tracker $tracker_id of the project $group_id with given values
 *
 * @param string $sessionKey  the session hash associated with the session opened by the person who calls the service
 * @param int    $group_id    the ID of the group we want to update the artifact
 * @param int    $tracker_id  the ID of the tracker we want to update the artifact
 * @param int    $artifact_id the ID of the artifact to update
 * @param array{SOAPArtifactFieldValue} $value the fields value to update
 * @param string $comment     the comment associated with the modification, or null if no follow-up comment.
 *
 * @return int The artifact id if update was fine,
 *              or a soap fault if :
 *              - group_id does not match with a valid project, 
 *              - tracker_id does not match with a valid tracker,
 *              - artifact_id does not match with a valid artifact,
 *              - the given values are breaking a field dependency rule
 *              - the artifact modification failed.
 */
function updateArtifact($sessionKey, $group_id, $tracker_id, $artifact_id, $value, $comment) {
    if (session_continue($sessionKey)) {
        $user = UserManager::instance()->getCurrentUser();
        $pm = ProjectManager::instance();
        try {
            $project = $pm->getGroupByIdForSoap($group_id, 'updateArtifact');
        } catch (SoapFault $e) {
            return $e;
        }

        $tf = TrackerFactory::instance();
        $tracker = $tf->getTrackerById($tracker_id);
        if ($tracker == null) {
            return new SoapFault(get_tracker_fault, 'Could not get Tracker.', 'updateArtifact');
        } elseif ($tracker->getGroupId() != $group_id) {
            return new SoapFault(get_tracker_fault, 'Could not get Tracker.', 'updateArtifact');
        }
        
        $af = Tracker_ArtifactFactory::instance();
        if ($artifact = $af->getArtifactById($artifact_id)) {
            if ($artifact->getTrackerId() != $tracker_id) {
                return new SoapFault(get_tracker_fault, 'Could not get Artifact.', 'updateArtifact');
            }
            
            //Check Field Dependencies
            // TODO : implement it
            /*require_once('common/tracker/ArtifactRulesManager.class.php');
            $arm =& new ArtifactRulesManager();
            if (!$arm->validate($ath->getID(), $data, $art_field_fact)) {
                return new SoapFault(invalid_field_dependency_fault, 'Invalid Field Dependency', 'updateArtifact');
            }*/
            
            $fef = Tracker_FormElementFactory::instance();
            
            $fields_data = array();
            foreach ($value as $field_value) {
                // field are identified by name, we need to retrieve the field id
                if ($field_value->field_name) {
                    
                    $field = $fef->getUsedFieldByName($tracker_id, $field_value->field_name);
                    if ($field) {
                        
                        $field_data = $field->getFieldData($field_value->field_value);
                        if ($field_data != null) {
                            // $field_value is an object: SOAP must cast it in ArtifactFieldValue
                            if (isset($fields_data[$field->getId()])) {
                                if ( ! is_array($fields_data[$field->getId()]) ) {
                                    $fields_data[$field->getId()] = array($fields_data[$field->getId()]);
                                }
                                $fields_data[$field->getId()][] = $field_data;
                            } else {
                                $fields_data[$field->getId()] = $field_data;
                            }
                        } else {
                            return new SoapFault(update_artifact_fault, 'Unknown value ' . $field_value->field_value . ' for field: '.$field_value->field_name ,'addArtifact');
                        }
                    } else {
                        return new SoapFault(update_artifact_fault, 'Unknown field: '.$field_value->field_name ,'addArtifact');
                    }
                }
            }
            
            if ($artifact->createNewChangeset($fields_data, $comment, $user, null)) {
                return $artifact_id;
            } else {
                $response = new Response();
                if ($response->feedbackHasErrors()) {
                    return new SoapFault(update_artifact_fault, $response->getRawFeedback(),'updateArtifact');
                } else {
                    return new SoapFault(update_artifact_fault, 'Unknown error','updateArtifact');
                }
            }
        } else {
            return new SoapFault(get_tracker_fault, 'Could not get Artifact.', 'updateArtifact');
        }
        
    } else {
        return new SoapFault(invalid_session_fault,'Invalid Session ','updateArtifact');
    }
}

/**
 * getArtifactAttachedFiles - returns the array of ArtifactFile of the artifact $artifact_id in the tracker $group_artifact_id of the project $group_id
 *
 * NOTE : by default, this function does not return the content of the files (for performance reasons). To get the binary content of files, give $set_bin_data the true value. 
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the attached files
 * @param int $group_artifact_id the ID of the tracker we want to retrieve the attached files
 * @param int $artifact_id the ID of the artifact we want to retrieve the attached files
 * @return array{SOAPArtifactFile} the array of the attached file of the artifact,
 *              or a soap fault if :
 *              - group_id does not match with a valid project, 
 *              - group_artifact_id does not match with a valid tracker
 *              - artifact_id does not match with a valid artifact
 */
function getArtifactAttachedFiles($sessionKey,$group_id,$group_artifact_id,$artifact_id,$set_bin_data = false) {
    global $art_field_fact;
    
    if (session_continue($sessionKey)) {
        $pm = ProjectManager::instance();
        try {
            $grp = $pm->getGroupByIdForSoap($group_id, 'getArtifactAttachedFiles');
        } catch (SoapFault $e) {
            return $e;
        }

        $at = new ArtifactTracker($grp,$group_artifact_id);
        if (!$at || !is_object($at)) {
            return new SoapFault(get_artifact_type_fault,'Could Not Get ArtifactTracker','getArtifactAttachedFiles');
        } elseif ($at->isError()) {
            return new SoapFault(get_artifact_type_fault,$at->getErrorMessage(),'getArtifactAttachedFiles');
        }
        
        $art_field_fact = new ArtifactFieldFactory($at);
        if (!$art_field_fact || !is_object($art_field_fact)) {
            return new SoapFault(get_artifact_field_factory_fault, 'Could Not Get ArtifactFieldFactory','getArtifactAttachedFiles');
        } elseif ($art_field_fact->isError()) {
            return new SoapFault(get_artifact_field_factory_fault, $art_field_fact->getErrorMessage(),'getArtifactAttachedFiles');
        }
        
        $a = new Artifact($at,$artifact_id);
        if (!$a || !is_object($a)) {
            return new SoapFault(get_artifact_fault,'Could Not Get Artifact','getArtifactAttachedFiles');
        } elseif ($a->isError()) {
            return new SoapFault(get_artifact_fault,$a->getErrorMessage(),'getArtifactAttachedFiles');
        } elseif (! $a->userCanView()) {
            return new SoapFault(get_artifact_fault,'Permissions denied','getArtifactAttachedFiles');
        }
        
        return artifactfiles_to_soap($a->getAttachedFiles(), $set_bin_data);
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session', 'getArtifactAttachedFiles');
    }
}

/**
 * getArtifactAttachedFile - returns the ArtifactFile with the id $file_id of the artifact $artifact_id in the tracker $group_artifact_id of the project $group_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the attached file
 * @param int $group_artifact_id the ID of the tracker we want to retrieve the attached file
 * @param int $artifact_id the ID of the artifact we want to retrieve the attached file
 * @param int $file_id the ID of the attached file
 * @return {SOAPArtifactFile} the attached file of the artifact,
 *              or a soap fault if :
 *              - group_id does not match with a valid project, 
 *              - group_artifact_id does not match with a valid tracker
 *              - artifact_id does not match with a valid artifact
 *              - file_id does not match with the given artifact_id
 */
function getArtifactAttachedFile($sessionKey,$group_id,$group_artifact_id,$artifact_id, $file_id) {
    global $art_field_fact; 
    if (session_continue($sessionKey)) {
        $pm = ProjectManager::instance();
        try {
            $grp = $pm->getGroupByIdForSoap($group_id, 'getArtifactAttachedFile');
        } catch (SoapFault $e) {
            return $e;
        }

        $at = new ArtifactTracker($grp,$group_artifact_id);
        if (!$at || !is_object($at)) {
            return new SoapFault(get_artifact_type_fault,'Could Not Get ArtifactTracker','getArtifactAttachedFile');
        } elseif ($at->isError()) {
            return new SoapFault(get_artifact_type_fault,$at->getErrorMessage(),'getArtifactAttachedFile');
        }
        
        $art_field_fact = new ArtifactFieldFactory($at);
        if (!$art_field_fact || !is_object($art_field_fact)) {
            return new SoapFault(get_artifact_field_factory_fault, 'Could Not Get ArtifactFieldFactory','getArtifactAttachedFile');
        } elseif ($art_field_fact->isError()) {
            return new SoapFault(get_artifact_field_factory_fault, $art_field_fact->getErrorMessage(),'getArtifactAttachedFile');
        }
        
        $a = new Artifact($at,$artifact_id);
        if (!$a || !is_object($a)) {
            return new SoapFault(get_artifact_fault,'Could Not Get Artifact','getArtifactAttachedFile');
        } elseif ($a->isError()) {
            return new SoapFault(get_artifact_fault,$a->getErrorMessage(),'getArtifactAttachedFile');
        } elseif (! $a->userCanView()) {
            return new SoapFault(get_artifact_fault,'Permissions denied','getArtifactAttachedFile');
        }
        $file = artifactfile_to_soap($file_id, $a->getAttachedFiles(), true);
        if ($file != null) {
               return $file;
        } else {
               return new SoapFault(invalid_session_fault, 'Attached file '.$file_id.' not found', 'getArtifactAttachedFile');
        }
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session', 'getArtifactAttachedFile');
    }
}

function artifactfiles_to_soap($attachedfiles_arr, $set_bin_data = false) {
    $return = array();
    $rows=db_numrows($attachedfiles_arr);
    for ($i=0; $i<$rows; $i++) {
        $bin_data = db_result($attachedfiles_arr, $i, 'bin_data');
        $return[] = array(
            'id' => db_result($attachedfiles_arr, $i, 'id'),
            'artifact_id' => db_result($attachedfiles_arr, $i, 'artifact_id'),
            'filename' => db_result($attachedfiles_arr, $i, 'filename'),
            'description' => SimpleSanitizer::unsanitize(db_result($attachedfiles_arr, $i, 'description')),
            'bin_data' => ($set_bin_data?$bin_data:null),
            'filesize' => db_result($attachedfiles_arr, $i, 'filesize'),
            'filetype' => db_result($attachedfiles_arr, $i, 'filetype'),
            'adddate' => db_result($attachedfiles_arr, $i, 'adddate'),
            'submitted_by' => db_result($attachedfiles_arr, $i, 'user_name')
        );
    }
    return $return;
}

function artifactfile_to_soap($file_id, $attachedfiles_arr, $set_bin_data) {
    $return = null;
    $rows = db_numrows($attachedfiles_arr);
    for ($i=0; $i<$rows; $i++) {
        $file = array();
        $file['id'] = db_result($attachedfiles_arr, $i, 'id');
        $file['artifact_id'] = db_result($attachedfiles_arr, $i, 'artifact_id');
        $file['filename'] = db_result($attachedfiles_arr, $i, 'filename');
        $file['description'] = SimpleSanitizer::unsanitize(db_result($attachedfiles_arr, $i, 'description'));
        if ($set_bin_data) {
            $bin_data = db_result($attachedfiles_arr, $i, 'bin_data');
            $file['bin_data'] = $bin_data;
        }
        $file['filesize'] = db_result($attachedfiles_arr, $i, 'filesize');
        $file['filetype'] = db_result($attachedfiles_arr, $i, 'filetype');
        $file['adddate']  = db_result($attachedfiles_arr, $i, 'adddate');
        $file['submitted_by'] = db_result($attachedfiles_arr, $i, 'user_name');
        if ($file['id'] == $file_id) {
            $return = $file;
        }
    }
    return $return;
}

/**
 * addArtifactAttachedFile - add an attached file to the artifact $artifact_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to attach the file
 * @param int $group_artifact_id the ID of the tracker we want to attach the file
 * @param int $artifact_id the ID of the artifact we want to attach the file
 * @param string $encoded_data the raw data of the file, encoded in base64
 * @param string $description description of the file
 * @param string $filename name of the file
 * @param string $filetype mime-type of the file (text/plain, image/jpeg, etc...)
 * @return int the ID of the new attached file created,
 *              or a soap fault if :
 *              - group_id does not match with a valid project, 
 *              - group_artifact_id does not match with a valid tracker
 *              - artifact_id does not match with a valid artifact
 *              - the file attachment to the artifact failed
 */
function addArtifactAttachedFile($sessionKey,$group_id,$group_artifact_id,$artifact_id,$encoded_data,$description,$filename,$filetype) {
    global $art_field_fact; 
    if (session_continue($sessionKey)) {
        $pm = ProjectManager::instance();
        try {
            $grp = $pm->getGroupByIdForSoap($group_id, 'addArtifactAttachedFile');
        } catch (SoapFault $e) {
            return $e;
        }

        $at = new ArtifactTracker($grp,$group_artifact_id);
        if (!$at || !is_object($at)) {
            return new SoapFault(get_artifact_type_fault,'Could Not Get ArtifactTracker','addArtifactFile');
        } elseif ($at->isError()) {
            return new SoapFault(get_artifact_type_fault,$at->getErrorMessage(),'addArtifactFile');
        }
        
        $art_field_fact = new ArtifactFieldFactory($at);
        if (!$art_field_fact || !is_object($art_field_fact)) {
            return new SoapFault(get_artifact_field_factory_fault, 'Could Not Get ArtifactFieldFactory','addArtifactFile');
        } elseif ($art_field_fact->isError()) {
            return new SoapFault(get_artifact_field_factory_fault, $art_field_fact->getErrorMessage(),'addArtifactFile');
        }

        $a = new Artifact($at,$artifact_id);
        if (!$a || !is_object($a)) {
            return new SoapFault(get_artifact_fault,'Could Not Get Artifact','addArtifactFile');
        } elseif ($a->isError()) {
            return new SoapFault(get_artifact_fault,$a->getErrorMessage(),'addArtifactFile');
        }

        $af = new ArtifactFile($a);
        if (!$af || !is_object($af)) {
            return new SoapFault(get_artifact_file_fault,'Could Not Create File Object','addArtifactFile');
        } else if ($af->isError()) {
            return new SoapFault(get_artifact_file_fault,$af->getErrorMessage(),'addArtifactFile');
        }

        $bin_data = base64_decode($encoded_data);

        $filesize = strlen($bin_data);

        $id = $af->create($filename,$filetype,$filesize,$bin_data,$description, $changes);

        if (!$id) {
            return new SoapFault(get_artifact_file_fault,$af->getErrorMessage(),'addArtifactFile');
        } else {
            // Send the notification
            if ($changes) {
                $agnf =& new ArtifactGlobalNotificationFactory();
                $addresses = $agnf->getAllAddresses($at->getID(), true);
                $a->mailFollowupWithPermissions($addresses, $changes);
            }
        }

        return $id;
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session', 'addArtifactFile');
    }
}

/**
 * deleteArtifactAttachedFile - delete an attached file to the artifact $artifact_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to delete the file
 * @param int $group_artifact_id the ID of the tracker we want to delete the file
 * @param int $artifact_id the ID of the artifact we want to delete the file
 * @param string $file_id the ID of the file we want to delete
 * @return int the ID of the deleted file,
 *              or a soap fault if :
 *              - group_id does not match with a valid project, 
 *              - group_artifact_id does not match with a valid tracker
 *              - artifact_id does not match with a valid artifact
 *              - file_id does not match with a valid attached file
 *              - the file deletion failed
 */
function deleteArtifactAttachedFile($sessionKey,$group_id,$group_artifact_id,$artifact_id,$file_id) {
    global $art_field_fact; 
    if (session_continue($sessionKey)) {
        $pm = ProjectManager::instance();
        try {
            $grp = $pm->getGroupByIdForSoap($group_id, 'deleteArtifactAttachedFile');
        } catch (SoapFault $e) {
            return $e;
        }

        $at = new ArtifactTracker($grp,$group_artifact_id);
        if (!$at || !is_object($at)) {
            return new SoapFault(get_artifact_type_fault,'Could Not Get ArtifactTracker','deleteArtifactFile');
        } elseif ($at->isError()) {
            return new SoapFault(get_artifact_type_fault,$at->getErrorMessage(),'deleteArtifactFile');
        }

        $art_field_fact = new ArtifactFieldFactory($at);
        if (!$art_field_fact || !is_object($art_field_fact)) {
            return new SoapFault(get_artifact_field_factory_fault, 'Could Not Get ArtifactFieldFactory','deleteArtifactFile');
        } elseif ($art_field_fact->isError()) {
            return new SoapFault(get_artifact_field_factory_fault, $art_field_fact->getErrorMessage(),'deleteArtifactFile');
        }

        $a = new Artifact($at,$artifact_id);
        if (!$a || !is_object($a)) {
            return new SoapFault(get_artifact_fault,'Could Not Get Artifact','deleteArtifactFile');
        } elseif ($a->isError()) {
            return new SoapFault(get_artifact_fault,$a->getErrorMessage(),'deleteArtifactFile');
        }

        $af = new ArtifactFile($a, $file_id);
        if (!$af || !is_object($af)) {
            return new SoapFault(get_artifact_file_fault,'Could Not Create File Object','deleteArtifactFile');
        } else if ($af->isError()) {
            return new SoapFault(get_artifact_file_fault,$af->getErrorMessage(),'deleteArtifactFile');
        }

        if (!$af->delete()) {
            return new SoapFault(get_artifact_file_fault,$af->getErrorMessage(),'deleteArtifactFile');
        }

        return $file_id;
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session', 'deleteArtifactFile');
    }
}

/**
 * getArtifactHistory - returns the array of ArtifactHistory of the artifact $artifact_id in the tracker $tracker_id of the project $group_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the history
 * @param int $tracker_id the ID of the tracker we want to retrieve the history
 * @param int $artifact_id the ID of the artifact we want to retrieve the history
 * @return array{SOAPArtifactHistory} the array of the history of the artifact,
 *              or a soap fault if :
 *              - group_id does not match with a valid project, 
 *              - tracker_id does not match with a valid tracker
 *              - artifact_id does not match with a valid artifact
 */
function getArtifactHistory($sessionKey, $group_id, $tracker_id, $artifact_id) {
    if (session_continue($sessionKey)) {
        $pm = ProjectManager::instance();
        try {
            $grp = $pm->getGroupByIdForSoap($group_id, 'getArtifactHistory');
        } catch (SoapFault $e) {
            return $e;
        }

        if (!$project->usesService('tracker')) {
            return new SoapFault(get_service_fault, 'Tracker service is not used for this project.', 'getArtifactFollowups');
        }
        
        $tf = TrackerFactory::instance();
        if (!$tf) {
            return new SoapFault(get_tracker_factory_fault, 'Could Not Get TrackerFactory', 'getArtifactFollowups');
        } 
        
        $tracker = $tf->getTrackerById($tracker_id);
        
        if ($tracker == null) {
            return new SoapFault(get_tracker_factory_fault, 'Could Not Get Tracker', 'getArtifactFollowups');
        } else {
            if (! $tracker->userCanView()) {
                return new SoapFault(get_tracker_factory_fault,'Permission Denied: You are not granted sufficient permission to perform this operation.', 'getArtifactFollowups');
            } else {
                $af = Tracker_ArtifactFactory::instance();
                $artifact = $af->getArtifactById($artifact_id);
                $changesets = $artifact->getChangesets();
                return history_to_soap($changesets, $group_id);

            }
        }
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session', 'getArtifactHistory');
    }
}

function history_to_soap($changesets, $group_id) {
    $return = array();
    foreach ($changesets as $changeset_id => $changeset) {
        
        if ($previous_changeset = $changeset->getArtifact()->getPreviousChangeset($changeset->getId())) {
        
            $changes = array();
            $factory = Tracker_FormElementFactory::instance();
            foreach($changeset->getValues() as $field_id => $current_changeset_value) {
                if ($field = $factory->getFieldById($field_id)) {
                    if ($current_changeset_value->hasChanged()) {
                        if ($previous_changeset_value = $previous_changeset->getValue($field)) {
                            if ($diff = $current_changeset_value->diff($previous_changeset_value)) {
                                $changes[] = $field->getLabel() .': ' .$diff;
                            }
                        }
                    }
                }
            }
            
            $return[] = array(
                            'artifact_id'     => $changeset->artifact->getId(),
                            'changeset_id'    => $changeset_id,
                            'changes'         => $changes,
                            'modification_by' => $changeset->submitted_by,
                            'date'            => $changeset->submitted_on,
                            'comment'         => $changeset->getComment()
                        );
        }
    }
    return $return;
}

$GLOBALS['server']->addFunction(
        array(
            'getTrackerList',
            'getTrackerFields',
            'getArtifact',
            'getArtifacts',
            'addArtifact',
            'updateArtifact',
            //'getArtifactAttachedFiles',
            //'getArtifactAttachedFile',
            //'addArtifactAttachedFile',
            //'deleteArtifactAttachedFile',
            //'getArtifactHistory',
        )
    );
}
?>
