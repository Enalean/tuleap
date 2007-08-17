<?php

// define fault code constants
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
define ('invalid_field_fault', '3016');

require_once ('nusoap.php');
require_once ('pre.php');
require_once ('session.php');
require_once ('common/include/Error.class.php');
require_once ('common/tracker/ArtifactType.class.php');
require_once ('common/tracker/ArtifactTypeFactory.class.php');
require_once ('common/tracker/Artifact.class.php');
require_once ('common/tracker/ArtifactFactory.class.php');
require_once ('common/tracker/ArtifactField.class.php');
require_once ('common/tracker/ArtifactFieldFactory.class.php');
require_once ('common/tracker/ArtifactFieldSet.class.php');
require_once ('common/tracker/ArtifactFieldSetFactory.class.php');
require_once ('common/tracker/ArtifactReportFactory.class.php');
require_once ('www/tracker/include/ArtifactFieldHtml.class.php');


//
// Type definition
//
$server->wsdl->addComplexType(
    'ArtifactType',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'group_artifact_id' => array('name'=>'group_artifact_id', 'type' => 'xsd:int'),
        'group_id' => array('name'=>'group_id', 'type' => 'xsd:int'),
        'name' => array('name'=>'name', 'type' => 'xsd:string'),
        'description' => array('name'=>'description', 'type' => 'xsd:string'),
        'item_name' => array('name'=>'item_name', 'type' => 'xsd:string'),
        'open_count' => array('name'=>'open_count', 'type' => 'xsd:int'),
        'total_count' => array('name'=>'total_count', 'type' => 'xsd:int'),
        'total_file_size' => array('name'=>'total_file_size', 'type' => 'xsd:float'),
        'field_sets' => array('name' => 'field_sets', 'type' => 'tns:ArrayOfArtifactFieldSet')
    )
);

$server->wsdl->addComplexType(
    'ArrayOfArtifactType',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:ArtifactType[]')),
    'tns:ArtifactType'
);

$server->wsdl->addComplexType(
    'ArtifactFieldSet',
    'complexType',
    'struct',
    'sequence',
    '',
    array(                  
        'field_set_id' => array('name'=>'field_set_id', 'type' => 'xsd:int'),
        'group_artifact_id'  => array('name'=>'group_artifact_id', 'type' => 'xsd:int'),
        'name' => array('name'=>'name', 'type' => 'xsd:string'),
        'description' => array('name'=>'description', 'type' => 'xsd:string'),
        'rank' => array('name'=>'rank', 'type' => 'xsd:int'),
        'fields'=> array('name'=>'fields', 'type' => 'tns:ArrayOfArtifactField'),
    )
);

$server->wsdl->addComplexType(
    'ArrayOfArtifactFieldSet',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:ArtifactFieldSet[]')),
    'tns:ArtifactFieldSet'
);

$server->wsdl->addComplexType(
    'ArtifactField',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'field_id' => array('name' => 'field_id', 'type' => 'xsd:int'),
        'group_artifact_id' => array('name'=>'group_artifact_id', 'type' => 'xsd:int'),
        'field_set_id' => array('name'=>'field_set_id', 'type' => 'xsd:int'),
        'field_name' => array('name' => 'field_name', 'type' => 'xsd:string'),
        'data_type' => array('name' => 'data_type', 'type' => 'xsd:int'),
        'display_type' => array('name' => 'display_type', 'type' => 'xsd:string'),
        'display_size' => array('name' => 'display_size', 'type' => 'xsd:string'),
        'label' => array('name' => 'label', 'type' => 'xsd:string'),
        'description' => array('name' => 'description', 'type' => 'xsd:string'),
        'scope' => array('name' => 'scope', 'type' => 'xsd:string'),
        'required' => array('name' => 'required', 'type' => 'xsd:int'),
        'empty_ok' => array('name' => 'empty_ok', 'type' => 'xsd:int'),
        'keep_history' => array('name' => 'keep_history', 'type' => 'xsd:int'),
        'special' => array('name' => 'special', 'type' => 'xsd:int'),
        'value_function' => array('name' => 'value_function', 'type' => 'xsd:string'),
        'available_values' => array('name' => 'available_values', 'type' => 'tns:ArrayOfArtifactFieldValueList'),
        'default_value' => array('name' => 'default_value', 'type' => 'xsd:string'),
        'user_can_submit' => array('name' => 'user_can_submit', 'type' => 'xsd:boolean'),
        'user_can_update' => array('name' => 'user_can_update', 'type' => 'xsd:boolean'),
        'user_can_read'   => array('name' => 'user_can_read', 'type' => 'xsd:boolean'),
        'is_standard_field'   => array('name' => 'is_standard_field', 'type' => 'xsd:boolean')
    )
);

$server->wsdl->addComplexType(
    'ArrayOfArtifactField',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:ArtifactField[]')
    ),
    'tns:ArtifactField'
);

$server->wsdl->addComplexType(
    'ArtifactFieldValue',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'field_id' => array('name' => 'field_id', 'type' => 'xsd:int'),
        'artifact_id' => array('name'=>'artifact_id', 'type' => 'xsd:int'),
        'field_value' => array('name' => 'field_value', 'type' => 'xsd:string')
    )
);

$server->wsdl->addComplexType(
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

$server->wsdl->addComplexType(
    'ArtifactFieldNameValue',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'field_name' => array('name' => 'field_name', 'type' => 'xsd:string'),
        'artifact_id' => array('name'=>'artifact_id', 'type' => 'xsd:int'),
        'field_value' => array('name' => 'field_value', 'type' => 'xsd:string')
    )
);

$server->wsdl->addComplexType(
    'ArrayOfArtifactFieldNameValue',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:ArtifactFieldNameValue[]')
    ),
    'tns:ArtifactFieldNameValue'
);

$server->wsdl->addComplexType(
    'ArtifactFieldValueList',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'field_id' => array('name' => 'field_id', 'type' => 'xsd:int'),
        'group_artifact_id' => array('name'=>'group_artifact_id', 'type' => 'xsd:int'),
        'value_id' => array('name' => 'value_id', 'type' => 'xsd:int'),
        'value' => array('name' => 'value', 'type' => 'xsd:string'),
        'description' => array('name' => 'description', 'type' => 'xsd:string'),
        'order_id' => array('name' => 'order_id', 'type' => 'xsd:int'),
        'status' => array('name' => 'status', 'type' => 'xsd:string')
    )
);

$server->wsdl->addComplexType(
    'ArrayOfArtifactFieldValueList',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:ArtifactFieldValueList[]')
    ),
    'tns:ArtifactFieldValueList'
);

$server->wsdl->addComplexType(
    'Artifact',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'artifact_id' => array('name'=>'artifact_id', 'type' => 'xsd:int'),
        'group_artifact_id' => array('name'=>'group_artifact_id', 'type' => 'xsd:int'),
        'status_id' => array('name'=>'status_id', 'type' => 'xsd:int'),
        'submitted_by' => array('name'=>'submitted_by', 'type' => 'xsd:int'),
        'open_date' => array('name'=>'open_date', 'type' => 'xsd:int'),
        'close_date' => array('name'=>'close_date', 'type' => 'xsd:int'),
        'summary' => array('name'=>'summary', 'type' => 'xsd:string'),
        'details' => array('name'=>'details', 'type' => 'xsd:string'),
        'severity'=>array('name'=>'severity', 'type' => 'xsd:int'),
        'extra_fields'=>array('name'=>'extra_fields', 'type' => 'tns:ArrayOfArtifactFieldValue')
    )
);

$server->wsdl->addComplexType(
    'ArrayOfArtifact',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:Artifact[]')),
    'tns:Artifact'
);

$server->wsdl->addComplexType(
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

$server->wsdl->addComplexType(
    'ArrayOfCriteria',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:Criteria[]')),
    'tns:Criteria'
);

$server->wsdl->addComplexType(
    'ArtifactCanned',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'artifact_canned_id' => array('name'=>'artifact_canned_id', 'type' => 'xsd:int'),
        'group_artifact_id'  => array('name'=>'group_artifact_id', 'type' => 'xsd:int'),
        'title'          => array('name'=>'title', 'type' => 'xsd:string'),
        'body'              => array('name'=>'body', 'type' => 'xsd:string')
    )
);

$server->wsdl->addComplexType(
    'ArrayOfArtifactCanned',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:ArtifactCanned[]')),
    'tns:ArtifactCanned'
);

$server->wsdl->addComplexType(
    'ArtifactFollowup',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'artifact_id'          => array('name'=>'artifact_id', 'type' => 'xsd:int'),        
        'comment'           => array('name'=>'comment', 'type' => 'xsd:string'),
        'date'                     => array('name'=>'date', 'type' => 'xsd:int'),
        'by'               => array('name'=>'by', 'type' => 'xsd:string'),
        'comment_type_id'     => array('name'=>'comment_type_id', 'type' => 'xsd:int'),    
    )
);

$server->wsdl->addComplexType(
    'ArrayOfArtifactFollowup',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:ArtifactFollowup[]')),
    'tns:ArtifactFollowup'
);

$server->wsdl->addComplexType(
    'ArtifactReport',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'report_id'           => array('name'=>'report_id', 'type' => 'xsd:int'),
        'group_artifact_id'   => array('name'=>'group_artifact_id', 'type' => 'xsd:int'),
        'user_id'           => array('name'=>'user_id', 'type' => 'xsd:int'),
        'name'                     => array('name'=>'name', 'type' => 'xsd:string'),
        'description'           => array('name'=>'description', 'type' => 'xsd:string'),
        'scope'           => array('name'=>'scope', 'type' => 'xsd:string'),
        'fields'          => array('name'=>'fields', 'type' => 'tns:ArrayOfArtifactReportField')    
    )
);

$server->wsdl->addComplexType(
    'ArrayOfArtifactReport',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:ArtifactReport[]')),
    'tns:ArtifactReport'
);

$server->wsdl->addComplexType(
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

$server->wsdl->addComplexType(
    'ArrayOfArtifactFile',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:ArtifactFile[]')),
    'tns:ArtifactFile'
);

$server->wsdl->addComplexType(
    'ArtifactReportField',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'report_id'           => array('name'=>'report_id', 'type' => 'xsd:int'),
        'field_name'          => array('name'=>'field_name', 'type' => 'xsd:string'),
        'show_on_query'       => array('name'=>'show_on_query', 'type' => 'xsd:int'),
        'show_on_result'      => array('name'=>'show_on_result', 'type' => 'xsd:int'),
        'place_query'           => array('name'=>'place_query', 'type' => 'xsd:int'),
        'place_result'           => array('name'=>'place_result', 'type' => 'xsd:int'),
        'col_width'          => array('name'=>'col_width', 'type' => 'xsd:int')    
    )
);

$server->wsdl->addComplexType(
    'ArrayOfArtifactReportField',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:ArtifactReportField[]')),
    'tns:ArtifactReportField'
);

$server->wsdl->addComplexType(
    'ArtifactDependence',
    'complexType',
    'struct',
    'sequence',
    '',
    array(                  
    'artifact_depend_id'          => array('name'=>'artifact_depend_id', 'type' => 'xsd:int'),
    'artifact_id'                 => array('name'=>'artifact_id', 'type' => 'xsd:int'),
    'is_dependent_on_artifact_id' => array('name'=>'is_dependent_on_artifact_id', 'type' => 'xsd:int')    
    )
);

$server->wsdl->addComplexType(
    'ArrayOfArtifactDependence',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:ArtifactDependence[]')),
    'tns:ArtifactDependence'
);

$server->wsdl->addComplexType(
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
$server->register(
    'getArtifactTypes', // method name
    array('sessionKey'=>'xsd:string', // input parameters
          'group_id'=>'xsd:int'
    ),
    array('return'=>'tns:ArrayOfArtifactType'), // output parameters
    $uri, // namespace
    $uri.'#getArtifactTypes', // soapaction
    'rpc', // style
    'encoded', // use
    'Returns the array of ArtifactType (trackers) that belongs to the group identified by group ID.
     Returns a soap fault if the group ID does not match with a valid project.' // documentation
);

$server->register(
    'getArtifacts',
    array('sessionKey'=>'xsd:string',
          'group_id'=>'xsd:int',
          'group_artifact_id'=>'xsd:int',
          'criteria' => 'tns:ArrayOfCriteria',
          'offset' => 'xsd:int',
          'max_rows' => 'xsd:int'
    ),
    array('return'=>'tns:ArrayOfArtifact'),
    $uri,
    $uri.'#getArtifacts',
    'rpc',
    'encoded',
    'Returns the array of Artifacts of the tracker group_artifact_id in the project group_id 
     that are matching the given criteria. If offset AND max_rows are filled, it returns only 
     max_rows artifacts, skipping the first offset ones. 
     Returns a soap fault if the group_id is not a valid one or if the group_artifact_id is not a valid one.'
);

$server->register(
    'addArtifact',
    array('sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'group_artifact_id'=>'xsd:int',
        'status_id' =>'xsd:int',
        'close_date'=>'xsd:int',
        'summary' =>'xsd:string',
        'details'=>'xsd:string',
        'severity'=>'xsd:int',
        'extra_fields'=>'tns:ArrayOfArtifactFieldValue'
    ),
    array('return'=>'xsd:int'),
    $uri,
    $uri.'#addArtifact',
    'rpc',
    'encoded',
    'Add an Artifact in the tracker group_artifact_id of the project group_id with the values given by 
     status_id, close_date, summary, details, severity and extra_fields for the non-standard fields. 
     Returns the Id of the created artifact if the creation succeed.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one, or if the add failed.
     NOTE : the mail notification system is not implemented with the SOAP API.'
);

$server->register(
    'addArtifactWithFieldNames',
    array('sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'group_artifact_id'=>'xsd:int',
        'status_id' =>'xsd:int',
        'close_date'=>'xsd:int',
        'summary' =>'xsd:string',
        'details'=>'xsd:string',
        'severity'=>'xsd:int',
        'extra_fields'=>'tns:ArrayOfArtifactFieldNameValue'
    ),
    array('return'=>'xsd:int'),
    $uri,
    $uri.'#addArtifact',
    'rpc',
    'encoded',
    'Add an Artifact in the tracker tracker_name of the project group_id with the values given by 
     status_id, close_date, summary, details, severity and extra_fields for the non-standard fields. 
     Returns the Id of the created artifact if the creation succeed.
     Returns a soap fault if the group_id is not a valid one, if the tracker_name is not a valid one, or if the add failed.
     NOTE : the mail notification system is not implemented with the SOAP API.'
);

$server->register(
    'updateArtifact',
    array('sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'group_artifact_id'=>'xsd:int',
        'artifact_id'=>'xsd:int',
        'status_id'=>'xsd:int',
        'close_date'=>'xsd:int', 
        'summary'=>'xsd:string', 
        'details'=>'xsd:string', 
        'severity'=>'xsd:int', 
        'extra_fields'=>'tns:ArrayOfArtifactFieldValue',
        'artifact_id_dependent'=>'xsd:string',
        'canned_response'=>'xsd:int'
    ),
    array('return'=>'xsd:int'),
    $uri,
    $uri.'#updateArtifact',
    'rpc',
    'encoded',
    'Update the artifact $artifact_id of the tracker $group_artifact_id in the project group_id with the values given by 
     status_id, close_date, summary, details, severity and extra_fields for the non-standard fields.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one, 
     if the artifart_id is not a valid one, or if the update failed.
     NOTE : the mail notification system is not implemented with the SOAP API.'
);

$server->register(
    'updateArtifactWithFieldNames',
    array('sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'group_artifact_id'=>'xsd:int',
        'artifact_id'=>'xsd:int',
        'status_id'=>'xsd:int',
        'close_date'=>'xsd:int', 
        'summary'=>'xsd:string', 
        'details'=>'xsd:string', 
        'severity'=>'xsd:int', 
        'extra_fields'=>'tns:ArrayOfArtifactFieldNameValue',
        'artifact_id_dependent'=>'xsd:string',
        'canned_response'=>'xsd:int'
    ),
    array('return'=>'xsd:int'),
    $uri,
    $uri.'#updateArtifact',
    'rpc',
    'encoded',
    'Update the artifact $artifact_id of the tracker $tracker_name in the project group_id with the values given by 
     status_id, close_date, summary, details, severity and extra_fields for the non-standard fields.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one, 
     if the artifart_id is not a valid one, or if the update failed.
     NOTE : the mail notification system is not implemented with the SOAP API.'
);

$server->register(
    'getArtifactFollowups',
    array('sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'group_artifact_id'=>'xsd:int',
        'artifact_id'=>'xsd:int'
    ),
    array('return'=>'tns:ArrayOfArtifactFollowup'),
    $uri,
    $uri.'#getArtifactFollowups',
    'rpc',
    'encoded',
    'Returns the list of follow-ups (ArtifactFollowup) of the artifact artifact_id of the tracker group_artifact_id in the project group_id.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one, 
     or if the artifart_id is not a valid one.'
);

$server->register(
    'getArtifactCannedResponses',
    array('sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'group_artifact_id'=>'xsd:int'
    ),
    array('return'=>'tns:ArrayOfArtifactCanned'),
    $uri,
    $uri.'#getArtifactCannedResponses',
    'rpc',
    'encoded',
    'Returns the list of canned responses (ArtifactCanned) for the tracker group_artifact_id of the project group_id. 
     Returns a soap fault if the group_id is not a valid one or if group_artifact_id is not a valid one.'
);

$server->register(
    'getArtifactReports',
    array('sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'group_artifact_id'=>'xsd:int',
        'user_id'=>'xsd:int'
    ),
    array('return'=>'tns:ArrayOfArtifactReport'),
    $uri,
    $uri.'#getArtifactReports',
    'rpc',
    'encoded',
    'Returns the list of reports (ArtifactReport) for the tracker group_artifact_id of the project group_id of the user user_id. 
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one, 
     or if the user_id is not a valid one.'
);

$server->register(
    'getArtifactAttachedFiles',
    array('sessionKey'=>'xsd:string',
          'group_id'=>'xsd:int',
          'group_artifact_id'=>'xsd:int',
          'artifact_id'=>'xsd:int'
    ),
    array('return'=>'tns:ArrayOfArtifactFile'),
    $uri,
    $uri.'#getArtifactAttachedFiles',
    'rpc',
    'encoded',
    'Returns the array of attached files (ArtifactFile) attached to the artifact artifact_id in the tracker group_artifact_id of the project group_id. 
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one, 
     or if the artifact_id is not a valid one.'
);

$server->register(
    'getAttachedFiles',
    array('sessionKey'=>'xsd:string',
          'group_id'=>'xsd:int',
          'group_artifact_id'=>'xsd:int',
          'artifact_id'=>'xsd:int'
    ),
    array('return'=>'tns:ArrayOfArtifactFile'),
    $uri,
    $uri.'#getAttachedFiles',
    'rpc',
    'encoded',
    'Deprecated. Please use getArtifactAttachedFiles'
);

$server->register(
    'getArtifactById',
    array('sessionKey'=>'xsd:string',
          'group_id'=>'xsd:int',
          'group_artifact_id'=>'xsd:int',
          'artifact_id'=>'xsd:int'
    ),
    array('return'=>'tns:Artifact'),
    $uri,
    $uri.'#getArtifactById',
    'rpc',
    'encoded',
    'Returns the artifact (Artifact) identified by the id artifact_id in the tracker group_artifact_id of the project group_id. 
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one, 
     or if the artifact_id is not a valid one.'
);

$server->register(
    'getArtifactDependencies',
    array('sessionKey'=>'xsd:string',
          'group_id'=>'xsd:int',
          'group_artifact_id'=>'xsd:int',
          'artifact_id'=>'xsd:int'
    ),
    array('return'=>'tns:ArrayOfArtifactDependence'),
    $uri,
    $uri.'#getArtifactDependencies',
    'rpc',
    'encoded',
    'Returns the list of the dependencies (ArtifactDependence) for the artifact artifact_id of the tracker group_artifact_id of the project group_id. 
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one, 
     or if the artifact_id is not a valid one.'
);

$server->register(
    'getDependencies',
    array('sessionKey'=>'xsd:string',
          'group_id'=>'xsd:int',
          'group_artifact_id'=>'xsd:int',
          'artifact_id'=>'xsd:int'
    ),
    array('return'=>'tns:ArrayOfArtifactDependence'),
    $uri,
    $uri.'#getDependencies',
    'rpc',
    'encoded',
    'Deprecated. Please use getArtifactDependencies'
);

$server->register(
    'addArtifactFile',
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
    $uri,
    $uri.'#addArtifactFile',
    'rpc',
    'encoded',
    'Add an attached file to the artifact artifact_id of the tracker group_artifact_id of the project group_id. 
     The attached file is described by the raw encoded_data (encoded in base64), the description of the file, 
     the name of the file and it type (the mimi-type -- plain/text, image/jpeg, etc ...). 
     Returns the ID of the attached file if the attachment succeed.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one, 
     or if the artifact_id is not a valid one, or if the attachment failed.
     NOTE : the mail notification system is not implemented with the SOAP API.'
);

$server->register(
    'deleteArtifactFile',
    array('sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'group_artifact_id'=>'xsd:int',
        'artifact_id'=>'xsd:int',
        'file_id'=>'xsd:int'
    ),
    array('return'=>'xsd:int'),
    $uri,
    $uri.'#deleteArtifactFile',
    'rpc',
    'encoded',
    'Delete the attached file file_id from the artifact artifact_id of the tracker group_artifact_id of the project group_id. 
     Returns the ID of the deleted file if the deletion succeed. 
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one, 
     if the artifact_id is not a valid one, if the file_id is not a valid one or if the deletion failed.
     NOTE : the mail notification system is not implemented with the SOAP API.'
);

$server->register(
    'addArtifactDependencies',
    array('sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'group_artifact_id'=>'xsd:int',
        'artifact_id'=>'xsd:int',
        'is_dependent_on_artifact_id'=>'tns:ArrayOfInt'
    ),
    array(),
    $uri,
    $uri.'#addArtifactDependencies',
    'rpc',
    'encoded',
    'Add the list of dependencies is_dependent_on_artifact_id to the list of dependencies of the artifact artifact_id 
     of the tracker group_artifact_id of the project group_id.
     Returns nothing if the add succeed. 
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one, 
     if the artifact_id is not a valid one, or if the add failed.
     NOTE : the mail notification system is not implemented with the SOAP API.'
);

$server->register(
    'addDependencies',
    array('sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'group_artifact_id'=>'xsd:int',
        'artifact_id'=>'xsd:int',
        'is_dependent_on_artifact_id'=>'tns:ArrayOfInt'
    ),
    array(),
    $uri,
    $uri.'#addDependencies',
    'rpc',
    'encoded',
    'Deprecated. Please use addArtifactDependencies'
);

$server->register(
    'deleteArtifactDependency',
    array('sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'group_artifact_id'=>'xsd:int',
        'artifact_id'=>'xsd:int',
        'dependent_on_artifact_id'=>'xsd:int'
    ),
    array('return'=>'xsd:int'),
    $uri,
    $uri.'#deleteArtifactDependence',
    'rpc',
    'encoded',
    'Delete the dependence between the artifact dependent_on_artifact_id and the artifact artifact_id of the tracker group_artifact_id of the project group_id.
     Returns the ID of the deleted dependency if the deletion succeed. 
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one, 
     if the artifact_id is not a valid one, if the dependent_on_artifact_id is not a valid artifact id, or if the deletion failed.
     NOTE : the mail notification system is not implemented with the SOAP API.'
);

$server->register(
    'deleteDependency',
    array('sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'group_artifact_id'=>'xsd:int',
        'artifact_id'=>'xsd:int',
        'dependent_on_artifact_id'=>'xsd:int'
    ),
    array('return'=>'xsd:int'),
    $uri,
    $uri.'#deleteDependence',
    'rpc',
    'encoded',
    'Deprecated. Please use deleteArtifactDependency'
);

$server->register(
    'addArtifactFollowup',
    array('sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'group_artifact_id' => 'xsd:int',
        'artifact_id' => 'xsd:int',
        'body' => 'xsd:string'
    ),
    array(),
    $uri,
    $uri.'#addArtifactFollowup',
    'rpc',
    'encoded',
    'Add a follow-up body to the artifact artifact_id of the tracker group_artifact_id of the project group_id.
     Returns nothing if the add succeed. 
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one, 
     if the artifact_id is not a valid one, or if the add failed.
     NOTE : the mail notification system is not implemented with the SOAP API.'
);

$server->register(
    'addFollowup',
    array('sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'group_artifact_id' => 'xsd:int',
        'artifact_id' => 'xsd:int',
        'body' => 'xsd:string'
    ),
    array(),
    $uri,
    $uri.'#addFollowup',
    'rpc',
    'encoded',
    'Deprecated. Please use addArtifactFollowup'
);

$server->register(
    'existArtifactSummary',
    array('sessionKey' => 'xsd:string',
        'group_artifact_id' => 'xsd:int',
        'summary' => 'xsd:string'
    ),
    array('return'=>'xsd:int'),
    $uri,
    $uri.'#existArtifactSummary',
    'rpc',
    'encoded',
    'Check if there is an artifact in the tracker group_artifact_id that already have the summary summary (the summary is unique inside a given tracker).
     Returns the ID of the artifact containing the same summary in the tracker, or -1 if the summary does not exist in this tracker.'
);

$server->register(
    'existSummary',
    array('sessionKey' => 'xsd:string',
        'group_artifact_id' => 'xsd:int',
        'summary' => 'xsd:string'
    ),
    array('return'=>'xsd:int'),
    $uri,
    $uri.'#existSummary',
    'rpc',
    'encoded',
    'Deprecated. Please use existArtifactSummary'
);

//
// Function implementation
//

/**
 * getArtifactTypes - returns an array of ArtifactTypes (trackers) that belongs to the project identified by group_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the array of trackers
 * @return array the array of SOAPArtifactType that belongs to the project identified by $group_id, or a soap fault if group_id does not match with a valid project.
 */
function &getArtifactTypes($sessionKey, $group_id) {
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new soap_fault(get_group_fault,'getArtifactTypes','Could Not Get Group','Could Not Get Group');
        } elseif ($group->isError()) {
            return new soap_fault(get_group_fault, 'getArtifactTypes', $group->getErrorMessage(),$group->getErrorMessage());
        }
        if (!checkRestrictedAccess($group)) {
            return new soap_fault(get_group_fault, 'getArtifactTypes', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
        }
        
        $atf = new ArtifactTypeFactory($group);
        if (!$atf || !is_object($atf)) {
            return new soap_fault(get_artifact_type_factory_fault, 'getArtifactTypes', 'Could Not Get ArtifactTypeFactory','Could Not Get ArtifactTypeFactory');
        } elseif ($atf->isError()) {
            return new soap_fault(get_artifact_type_factory_fault, 'getArtifactTypes', $atf->getErrorMessage(), $atf->getErrorMessage());
        }
        // The function getArtifactTypes returns only the trackers the user is allowed to view
        return artifacttypes_to_soap($atf->getArtifactTypes());
    } else {
        return new soap_fault(invalid_session_fault,'getArtifactTypes','Invalid Session','');
    }
}

/**
 * artifacttypes_to_soap : return the soap ArrayOfArtifactType structure giving an array of PHP ArtifactType Object.
 * @access private
 * 
 * WARNING : We check the permissions here : only the readable trackers and the readable fields are returned.
 *
 * @param array of Object{ArtifactType} $at_arr the array of artifactTypes to convert.
 * @return array the SOAPArrayOfArtifactType corresponding to the array of ArtifactTypes Object
 */
function artifacttypes_to_soap($at_arr) {
    global $ath;
    $user_id = session_get_userid();
    $return = array();
    for ($i=0; $i<count($at_arr); $i++) {
        if ($at_arr[$i]->isError()) {
            //skip if error
        } else {
            $field_sets = array();
            $ath = new ArtifactType($at_arr[$i]->getGroup(), $at_arr[$i]->getID());
            if (!$ath || !is_object($ath)) {
                return new soap_fault(get_artifact_type_fault, 'getArtifactTypes', 'ArtifactType could not be created','ArtifactType could not be created');
            }
            if ($ath->isError()) {
                return new soap_fault(get_artifact_type_fault, 'getArtifactTypes', $ath->getErrorMessage(),$ath->getErrorMessage());
            }
            // Check if this tracker is valid (not deleted)
            if ( !$ath->isValid() ) {
                return new soap_fault(get_artifact_type_fault, 'getArtifactTypes', 'This tracker is no longer valid.','This tracker is no longer valid.');
            }
            // Check if the user can view this tracker
            if ($ath->userCanView($user_id)) {
            
                $art_fieldset_fact = new ArtifactFieldSetFactory($at_arr[$i]);
                if (!$art_fieldset_fact || !is_object($art_fieldset_fact)) {
                    return new soap_fault(get_artifact_field_factory_fault, 'getFieldSets', 'Could Not Get ArtifactFieldSetFactory','Could Not Get ArtifactFieldSetFactory');
                } elseif ($art_fieldset_fact->isError()) {
                    return new soap_fault(get_artifact_field_factory_fault, 'getFieldSets', $art_fieldset_fact->getErrorMessage(),$art_fieldset_fact->getErrorMessage());
                }
                $result_fieldsets = $art_fieldset_fact->getAllFieldSetsContainingUsedFields();
            
                foreach($result_fieldsets as $fieldset_id => $result_fieldset) {
                    $fields = array();
                    $fields_in_fieldset = $result_fieldset->getAllUsedFields();     
                    $group_id = $at_arr[$i]->Group->getID();
                    $group_artifact_id = $at_arr[$i]->getID();
                    while ( list($key, $field) = each($fields_in_fieldset) ) {
                            
                        if ($field->userCanRead($group_id,$group_artifact_id,$user_id)) {
                            $availablevalues = array();
                            $result = $field->getFieldPredefinedValues($at_arr[$i]->getID());
                            $rows=db_numrows($result);
                            $cols=db_numfields($result);
                            for ($j=0; $j<$rows; $j++) {     
                                $availablevalues[] = array (
                                    'field_id' => $field->getID(),
                                    'group_artifact_id' => $at_arr[$i]->getID(),
                                    'value_id' => db_result($result,$j,0),
                                    'value' => db_result($result,$j,1),
                                    'description' => ($cols > 2) ? db_result($result,$j,4) : '',
                                    'order_id' => ($cols > 2) ? db_result($result,$j,5) : 0,
                                    'status' => ($cols > 2) ? db_result($result,$j,6) : ''
                                );
                            }
                            // For bound-values select boxes, we add the none value.
                            if (($field->isMultiSelectBox() || $field->isSelectBox()) && ($field->isBound())) {
                                $availablevalues[] = array (
                                    'field_id' => $field->getID(),
                                    'group_artifact_id' => $at_arr[$i]->getID(),
                                    'value_id' => 100,
                                    'value' => 'None',
                                    'description' => '',
                                    'order_id' => 10,
                                    'status' => 'P'
                                );    
                            }
                            if ($field->isMultiSelectBox()) {
                                $defaultvalue = implode(",", $field->getDefaultValue());
                            } else {
                                $defaultvalue = $field->getDefaultValue();
                            }
                            $fields[] = array(
                                'field_id' => $field->getID(),
                                'group_artifact_id' => $at_arr[$i]->getID(),
                                'field_set_id' => $field->getFieldSetID(), 
                                'field_name' => $field->getName(),
                                'data_type' => $field->getDataType(),
                                'display_type' => $field->getDisplayType(),
                                'display_size' => $field->getDisplaySize(),
                                'label'    => $field->getLabel(),
                                'description' => $field->getDescription(),
                                'scope' => $field->getScope(),
                                'required' => $field->getRequired(),
                                'empty_ok' => $field->getEmptyOk(),
                                'keep_history' => $field->getKeepHistory(),
                                'special' => $field->getSpecial(),
                                'value_function' => implode(",", $field->getValueFunction()),
                                'available_values' => $availablevalues,
                                'default_value' => $defaultvalue,
                                'user_can_submit' => $field->userCanSubmit($group_id,$group_artifact_id,$user_id),
                                'user_can_read' => $field->userCanRead($group_id,$group_artifact_id,$user_id),
                                'user_can_update' => $field->userCanUpdate($group_id,$group_artifact_id,$user_id),
                                'is_standard_field' => $field->isStandardField()
                            );
                        }
                    }
                    $field_sets[] = array(
                        'field_set_id'=>$result_fieldset->getID(),
                        'group_artifact_id'=>$result_fieldset->getArtifactTypeID(),
                        'name'=>$result_fieldset->getName(),
                        'description'=>$result_fieldset->getDescription(),
                        'rank'=>$result_fieldset->getRank(),
                        'fields'=>$fields
                    );
                }
                $sql = "SELECT COALESCE(sum(af.filesize) / 1024,NULL,0) as total_file_size"
                        ." FROM artifact_file af, artifact a, artifact_group_list agl"
                        ." WHERE (af.artifact_id = a.artifact_id)" 
                        ." AND (a.group_artifact_id = agl.group_artifact_id)" 
                        ." AND (agl.group_artifact_id =".$at_arr[$i]->getID().")";
                $result=db_query($sql);
                $return[]=array(
                    'group_artifact_id'=>$at_arr[$i]->data_array['group_artifact_id'],
                    'group_id'=>$at_arr[$i]->data_array['group_id'],
                    'name'=>$at_arr[$i]->data_array['name'],
                    'description'=>$at_arr[$i]->data_array['description'],
                    'item_name'=>$at_arr[$i]->data_array['item_name'],
                    'open_count' => ($at_arr[$i]->userHasFullAccess()?$at_arr[$i]->getOpenCount():NULL),
                    'total_count' => ($at_arr[$i]->userHasFullAccess()?$at_arr[$i]->getTotalCount():NULL),
                    'total_file_size' => db_result($result, 0, 0),
                    'field_sets' => $field_sets
                );
            }
        }
    }
    return $return;
}

/**
 * getArtifacts - returns an array of Artifacts that belongs to the project $group_id, to the tracker $group_artifact_id,
 *                and that match the criteria $criteria. If $offset and $max_rows are filled, the number of returned artifacts
 *                will not exceed $max_rows, beginning at $offset.
 *
 * !!!!!!!!!!!!!!!
 * !!! Warning : If $max_rows is not filled, $offset is not taken into account. !!!
 * !!!!!!!!!!!!!!!
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the array of artifacts
 * @param int $group_artifact_id the ID of the tracker we want to retrieve the array of artifacts
 * @param array{SOAPCriteria} $criteria the criteria that the set of artifact must match
 * @param int $offset number of artifact skipped. Used in association with $max_rows to limit the number of returned artifact.
 * @param int $max_rows the maximum number of artifacts returned
 * @return array the array of SOAPArtifact that match the criteria $criteria and belong to the project $group_id and the tracker $group_artifact_id,
 *          or a soap fault if group_id does not match with a valid project, or if group_artifact_id does not match with a valid tracker.
 */
function getArtifacts($sessionKey,$group_id,$group_artifact_id, $criteria, $offset, $max_rows) {
    global $art_field_fact;
    if (session_continue($sessionKey)) {
        $grp =& group_get_object($group_id);
        if (!$grp || !is_object($grp)) {
            return new soap_fault(get_group_fault,'getArtifacts','Could Not Get Group','Could Not Get Group');
        } elseif ($grp->isError()) {
            return new soap_fault(get_group_fault,'getArtifacts',$grp->getErrorMessage(),$grp->getErrorMessage());
        }
        if (!checkRestrictedAccess($grp)) {
            return new soap_fault(get_group_fault, 'getArtifactTypes', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
        }
        $at = new ArtifactType($grp,$group_artifact_id);
        if (!$at || !is_object($at)) {
            return new soap_fault(get_artifact_type_fault,'getArtifacts','Could Not Get ArtifactType','Could Not Get ArtifactType');
        } elseif (! $at->userCanView()) {
            return new soap_fault(get_artifact_type_fault,'getArtifacts','Permission Denied','You are not granted sufficient permission to perform this operation.');
        } elseif ($at->isError()) {
            return new soap_fault(get_artifact_type_fault,'getArtifacts',$at->getErrorMessage(),$at->getErrorMessage());
        }
        $art_field_fact = new ArtifactFieldFactory($at);
        if (!$art_field_fact || !is_object($art_field_fact)) {
            return new soap_fault(get_artifact_field_factory_fault, 'getArtifactTypes', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
        } elseif ($art_field_fact->isError()) {
            return new soap_fault(get_artifact_field_factory_fault, 'getArtifactTypes', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
        }
        $af = new ArtifactFactory($at);
        if (!$af || !is_object($af)) {
            return new soap_fault(get_artifact_factory_fault,'getArtifacts','Could Not Get ArtifactFactory','Could Not Get ArtifactFactory');
        } elseif ($af->isError()) {
            return new soap_fault(get_artifact_factory_fault,'getArtifacts',$atf->getErrorMessage(),$atf->getErrorMessage());
        }
        // the function getArtifacts returns only the artifacts the user is allowed to view
        $artifacts = $af->getArtifacts($criteria, $offset, $max_rows);
        return artifacts_to_soap($artifacts); 
    } else {
        return new soap_fault(invalid_session_fault,'getArtifactTypes','Invalid Session ','');
    }
}

/**
 * getArtifactById - returns the Artifacts that belongs to the project $group_id, to the tracker $group_artifact_id,
 *                  and that is identified by the ID $artifact_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the artifact
 * @param int $group_artifact_id the ID of the tracker we want to retrieve the artifact
 * @param int $artifact_id the ID of the artifact we are looking for
 * @return array the SOAPArtifact identified by ID $artifact_id,
 *          or a soap fault if group_id does not match with a valid project, or if group_artifact_id does not match with a valid tracker,
 *          or if artifact_id is not a valid artifact of this tracker.
 */
function getArtifactById($sessionKey,$group_id,$group_artifact_id, $artifact_id) {    
    global $art_field_fact, $ath; 
    if (session_continue($sessionKey)){
        $grp =& group_get_object($group_id);
        if (!$grp || !is_object($grp)) {
            return new soap_fault(get_group_fault,'getArtifactById','Could Not Get Group','Could Not Get Group');
        } elseif ($grp->isError()) {
            return new soap_fault(get_group_fault,'getArtifactById',$grp->getErrorMessage(),$grp->getErrorMessage());
        }
        if (!checkRestrictedAccess($grp)) {
            return new soap_fault(get_group_fault, 'getArtifactTypes', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
        }
        
        $ath = new ArtifactType($grp, $group_artifact_id);
        if (!$ath || !is_object($ath)) {
            return new soap_fault(get_artifact_type_fault, 'getArtifactById', 'ArtifactType could not be created','ArtifactType could not be created');
        }
        if ($ath->isError()) {
            return new soap_fault(get_artifact_type_fault, 'getArtifactById', $ath->getErrorMessage(),$ath->getErrorMessage());
        }
        // Check if this tracker is valid (not deleted)
        if ( !$ath->isValid() ) {
            return new soap_fault(get_artifact_type_fault, 'getArtifactById', 'This tracker is no longer valid.','This tracker is no longer valid.');
        }
        
        $art_field_fact = new ArtifactFieldFactory($ath);
        if (!$art_field_fact || !is_object($art_field_fact)) {
            return new soap_fault(get_artifact_field_factory_fault, 'getArtifactById', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
        } elseif ($art_field_fact->isError()) {
            return new soap_fault(get_artifact_field_factory_fault, 'getArtifactById', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
        }
        $a = new Artifact($ath, $artifact_id);
        if (!$a || !is_object($a)) {
            return new soap_fault(get_artifact_fault, 'getArtifactById', 'Could Not Get Artifact', 'Could Not Get Artifact');
        } elseif ($a->isError()) {
            return new soap_fault(get_artifact_fault, 'getArtifactById', $a->getErrorMessage(), $a->getErrorMessage());
        }
        return artifact_to_soap($a);
    } else {
       return new soap_fault(invalid_session_fault,'getArtifactById','Invalid Session','');
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
    global $art_field_fact;

    $return = array();
    // We check if the user can view this artifact
    if ($artifact->userCanView(user_getid())) {
        $extrafieldvalues = array();
        $extrafielddata   = $artifact->getExtraFieldData();
        if (is_array($extrafielddata) && count($extrafielddata) > 0 ) {
            while(list($field_id, $value) = each($extrafielddata)) {
                $field = $art_field_fact->getFieldFromId($field_id);
                if ($field->userCanRead($artifact->ArtifactType->Group->getID(),$artifact->ArtifactType->getID(), user_getid())) {
                    $extrafieldvalues[] = array (    
                        'field_id'    => $field_id,
                        'artifact_id' => $artifact->getID(),
                        'field_value' => $value
                    );
                }
            }
        }
        
        // Check Permissions on standard fields (status_id, submitted_by, open_date, close_date, summary, details, severity)
        // artifact_id
        $field_artifact_id = $art_field_fact->getFieldFromName('artifact_id');
        if ($field_artifact_id && $field_artifact_id->userCanRead($artifact->ArtifactType->Group->getID(),$artifact->ArtifactType->getID(), user_getid())) {
                $return['artifact_id'] = $artifact->getID();
        }
        // group_artifact_id
        $return['group_artifact_id'] = $artifact->ArtifactType->getID();
        // status_id
        $field_status_id = $art_field_fact->getFieldFromName('status_id');
        if ($field_status_id && $field_status_id->userCanRead($artifact->ArtifactType->Group->getID(),$artifact->ArtifactType->getID(), user_getid())) {
                $return['status_id'] = $artifact->getStatusID();
        }
        // submitted_by
        $field_submitted_by = $art_field_fact->getFieldFromName('submitted_by');
        if ($field_submitted_by && $field_submitted_by->userCanRead($artifact->ArtifactType->Group->getID(),$artifact->ArtifactType->getID(), user_getid())) {
                $return['submitted_by'] = $artifact->getSubmittedBy();
        }
        // open_date
        $field_open_date = $art_field_fact->getFieldFromName('open_date');
        if ($field_open_date && $field_open_date->userCanRead($artifact->ArtifactType->Group->getID(),$artifact->ArtifactType->getID(), user_getid())) {
                $return['open_date'] = $artifact->getOpenDate();
        }
        // close_date
        $field_close_date = $art_field_fact->getFieldFromName('close_date');
        if ($field_close_date && $field_close_date->userCanRead($artifact->ArtifactType->Group->getID(),$artifact->ArtifactType->getID(), user_getid())) {
                $return['close_date'] = $artifact->getCloseDate();
        }
        // summary
        $field_summary = $art_field_fact->getFieldFromName('summary');
        if ($field_summary && $field_summary->userCanRead($artifact->ArtifactType->Group->getID(),$artifact->ArtifactType->getID(), user_getid())) {
                $return['summary'] = $artifact->getSummary();
        }
        // details
        $field_details = $art_field_fact->getFieldFromName('details');
        if ($field_details && $field_details->userCanRead($artifact->ArtifactType->Group->getID(),$artifact->ArtifactType->getID(), user_getid())) {
                $return['details'] = $artifact->getDetails();
        }
        // severity
        $field_severity = $art_field_fact->getFieldFromName('severity');
        if ($field_severity && $field_severity->userCanRead($artifact->ArtifactType->Group->getID(),$artifact->ArtifactType->getID(), user_getid())) {
                $return['severity'] = $artifact->getSeverity();
        }
        $return['extra_fields'] = $extrafieldvalues;
    }
    return $return;
}

function artifacts_to_soap($at_arr) {
    $return = array();
    foreach ($at_arr as $atid => $artifact) {
        $return[] = artifact_to_soap($artifact);
    }
    return $return;
}

function setArtifactData($status_id, $close_date, $summary, $details, $severity, $extra_fields) {
    global $art_field_fact; 
    
    $data = array();
    // set standard fields data
    if (isset($status_id))       $data ['status_id']    =  $status_id;
    if (isset($close_date))   $data ['close_date']   =  date("Y-m-d", $close_date); // Dates are internally stored in timestamp, but for update and create functions, date must be Y-m-d
    if (isset($summary))      $data ['summary']      =  $summary;
    if (isset($details))      $data ['details']      =  $details;
    if (isset($severity))     $data ['severity']     =  $severity;
    
    // set extra fields data
    if (is_array($extra_fields) && count($extra_fields) > 0) {
        foreach ($extra_fields as $e => $extra_field) {
        
            $field = $art_field_fact->getFieldFromId($extra_field['field_id']);
            if ($field->isStandardField()) {
                continue;
            }
            else {
                if ($field->isMultiSelectBox()) {
                    $value = explode(",", $extra_field['field_value']);
                    $data [$field->getName()] = $value;
                } elseif ($field->isDateField()) {
                    // Dates are internally stored in timestamp, but for update and create functions, date must be Y-m-d
                    $value = date("Y-m-d", $extra_field['field_value']);
                    $data [$field->getName()] = $value;
                } else {
                    $data [$field->getName()] = $extra_field['field_value'];
                }
            }
        }
    }
    return $data;
}

/**
 * addArtifact - add an artifact in tracker $group_artifact_id of the project $group_id with given valuess
 *
 * NOTE : the mail notification system is not implemented with the SOAP API.
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to add the artifact
 * @param int $group_artifact_id the ID of the tracker we want to add the artifact
 * @param int $status_id the ID of the status of the artifact
 * @param string $close_date the close date of the artifact. The format must be YYYY-MM-DD
 * @param string $summary the summary of the artifact
 * @param string $details the details (original submission) of the artifact
 * @param int $severity the severity of the artifact
 * @param array{SOAPArtifactFieldValue} $extra_fields the extra_fields of the artifact (non standard fields)
 * @return int the ID of the new created artifact, 
 *              or a soap fault if :
 *              - group_id does not match with a valid project, 
 *              - group_artifact_id does not match with a valid tracker,
 *              - the user does not have the permissions to submit an artifact
 *              - the given values are breaking a field dependency rule
 *              - the artifact creation failed.
 */
function addArtifact($sessionKey, $group_id, $group_artifact_id, $status_id, $close_date, $summary, $details, $severity, $extra_fields) {
    global $art_field_fact, $ath; 
    if (session_continue($sessionKey)) {
        $grp =& group_get_object($group_id);
        if (!$grp || !is_object($grp)) {
            return new soap_fault(get_group_fault,'addArtifact','Could Not Get Group','Could Not Get Group');
        } elseif ($grp->isError()) {
            return new soap_fault(get_group_fault,'addArtifact',$grp->getErrorMessage(),$grp->getErrorMessage());
        }
        if (!checkRestrictedAccess($grp)) {
            return new soap_fault(get_group_fault, 'getArtifactTypes', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
        }
        
        $ath = new ArtifactType($grp, $group_artifact_id);
        if (!$ath || !is_object($ath)) {
            return new soap_fault(get_artifact_type_fault, 'addArtifact', 'ArtifactType could not be created','ArtifactType could not be created');
        }
        if ($ath->isError()) {
            return new soap_fault(get_artifact_type_fault, 'addArtifact', $ath->getErrorMessage(),$ath->getErrorMessage());
        }
        // Check if this tracker is valid (not deleted)
        if ( !$ath->isValid() ) {
            return new soap_fault(get_artifact_type_fault, 'addArtifact', 'This tracker is no longer valid.','This tracker is no longer valid.');
        }

        // check the user if he can submit artifacts for this tracker
        if (!$ath->userCanSubmit(session_get_userid())) {
            return new soap_fault(permission_denied_fault, 'addArtifact', 'Permission Denied', 'You are not granted sufficient permission to perform this operation.');
        }
        
        $art_field_fact = new ArtifactFieldFactory($ath);
        if (!$art_field_fact || !is_object($art_field_fact)) {
            return new soap_fault(get_artifact_field_factory_fault, 'addArtifact', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
        } elseif ($art_field_fact->isError()) {
            return new soap_fault(get_artifact_field_factory_fault, 'addArtifact', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
        }
        
        // 1) The permissions check will be done in the Artifact create function
        // 2) Check the allow empty value and the default value for each field.
        $all_used_fields = $art_field_fact->getAllUsedFields();
        foreach($all_used_fields as $used_field) {
            // We only check the field the user is allowed to submit
            // because the Artifact create function expect only these fields in the array $vfl
            if ($used_field->userCanSubmit($group_id, $group_artifact_id, session_get_userid())) {
                // We skip these 3 fields, because their value is automatically filled
                if ($used_field->getName() == 'open_date' || $used_field->getName() == 'submitted_by' || $used_field->getName() == 'artifact_id') {
                    continue;
                }
                
                // check the allow empty value. If the empty value is not allowed and the field not filled, we put the default value.
                if (!$used_field->isEmptyOk()) {
                    // the field must be filled, so we will check if it is
                    if ($used_field->isStandardField()) {
                        $used_field_name = $used_field->getName();
                        if (! isset($$used_field_name)) {
                            // $$ : dynamic variable. The variable will be $status_id, $close_date, $summary depend on the corresponding field
                            if (is_array($used_field->getDefaultValue())) {
                                // if the default values are multiple, we set them in a string separated with comma
                                $$used_field_name = implode(",", $used_field->getDefaultValue());
                            } else {
                                $$used_field_name = $used_field->getDefaultValue();
                            }
                            
                        }
                    } else {
                        $used_field_present = false;
                        // We will search if the field is filled
                        foreach($extra_fields as $extra_field) {
                            if ($extra_field['field_id'] == $used_field->getID()) {
                                $used_field_present = true;
                            }
                        }
                        if (! $used_field_present) {
                            // the field is required, but there is no value, so we put the default value
                            $extra_field_to_add = array();
                            $extra_field_to_add['field_id'] = $used_field->getID();
                            if (is_array($used_field->getDefaultValue())) {
                                // if the default values are multiple, we set them in a string separated with comma
                                $extra_field_to_add['field_value'] = implode(",", $used_field->getDefaultValue());
                            } else {
                                $extra_field_to_add['field_value'] = $used_field->getDefaultValue();
                            }
                            $extra_fields[] = $extra_field_to_add;
                        }
                    }
                }
            }
        }
        
        $a = new Artifact($ath);
        if (!$a || !is_object($a)) {
            return new soap_fault(get_artifact_fault, 'addArtifact', 'Could Not Get Artifact', 'Could Not Get Artifact');
        } elseif ($a->isError()) {
            return new soap_fault(get_artifact_fault, 'addArtifact', $a->getErrorMessage(), $a->getErrorMessage());
        }
        
        $data = setArtifactData($status_id, $close_date, $summary, $details, $severity, $extra_fields);
        
        //Check Field Dependencies
        require_once('common/tracker/ArtifactRulesManager.class.php');
        $arm =& new ArtifactRulesManager();
        if (!$arm->validate($ath->getID(), $data, $art_field_fact)) {
            return new soap_fault(invalid_field_dependency_fault, 'addArtifact', 'Invalid Field Dependency', 'Invalid Field Dependency');
        }
        if (!$a->create($data)) {
            return new soap_fault(create_artifact_fault,'addArtifact',$a->getErrorMessage(),$a->getErrorMessage());
        } else {
            return $a->getID();
        }
    } else {
           return new soap_fault(invalid_session_fault,'addArtifact','Invalid Session ','');
    }
}

/**
 * addArtifactWithFieldNames - add an artifact in tracker $tracjer_name of the project $group_id with given valuess
 *
 * NOTE : the mail notification system is not implemented with the SOAP API.
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to add the artifact
 * @param int $group_artifact_id the ID of the tracker we want to add the artifact
 * @param int $status_id the ID of the status of the artifact
 * @param string $close_date the close date of the artifact. The format must be YYYY-MM-DD
 * @param string $summary the summary of the artifact
 * @param string $details the details (original submission) of the artifact
 * @param int $severity the severity of the artifact
 * @param array{SOAPArtifactFieldNameValue} $extra_fields the extra_fields of the artifact (non standard fields)
 * @return int the ID of the new created artifact, 
 *              or a soap fault if :
 *              - group_id does not match with a valid project, 
 *              - tracker_name does not match with a valid tracker,
 *              - the user does not have the permissions to submit an artifact
 *              - the given values are breaking a field dependency rule
 *              - the artifact creation failed.
 */
function addArtifactWithFieldNames($sessionKey, $group_id, $group_artifact_id, $status_id, $close_date, $summary, $details, $severity, $extra_fields) {
    global $art_field_fact, $ath; 
    if (session_continue($sessionKey)) {
        $grp =& group_get_object($group_id);
        if (!$grp || !is_object($grp)) {
            return new soap_fault(get_group_fault,'addArtifact','Could Not Get Group','Could Not Get Group');
        } elseif ($grp->isError()) {
            return new soap_fault(get_group_fault,'addArtifact',$grp->getErrorMessage(),$grp->getErrorMessage());
        }
        if (!checkRestrictedAccess($grp)) {
            return new soap_fault(get_group_fault, 'getArtifactTypes', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
        }
        $at = new ArtifactType($grp, $group_artifact_id);
        if (!$at || !is_object($at)) {
            return new soap_fault(get_artifact_type_fault, 'addArtifact', 'ArtifactType could not be created','ArtifactType could not be created');
        }
        if ($at->isError()) {
            return new soap_fault(get_artifact_type_fault, 'addArtifact', $at->getErrorMessage(),$at->getErrorMessage());
        }
        // Check if this tracker is valid (not deleted)
        if ( !$at->isValid() ) {
            return new soap_fault(get_artifact_type_fault, 'addArtifact', 'This tracker is no longer valid.','This tracker is no longer valid.');
        }
        
        $group_artifact_id = $at->getID();
        
        $art_field_fact = new ArtifactFieldFactory($at);
        if (!$art_field_fact || !is_object($art_field_fact)) {
            return new soap_fault(get_artifact_field_factory_fault, 'addArtifact', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
        } elseif ($art_field_fact->isError()) {
            return new soap_fault(get_artifact_field_factory_fault, 'addArtifact', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
        }
        
        // translate the field_name in field_id, in order to call the real addArtifact function
        $extrafields_with_id = array();
        foreach($extra_fields as $extra_field_name) {
            $field = $art_field_fact->getFieldFromName($extra_field_name['field_name']);
            if ($field) {
                $extra_field_id = $field->getID();
                $extrafields_with_id[] = array('field_id' => $extra_field_id, 'field_value' => $extra_field_name['field_value']);
            } else {
                return new soap_fault(invalid_field_fault,'addArtifact','Invalid Field:'.$extra_field_name['field_name'],'addArtifact','Invalid Field:'.$extra_field_name['field_name']);
            }
        }
        
        return addArtifact($sessionKey, $group_id, $group_artifact_id, $status_id, $close_date, $summary, $details, $severity, $extrafields_with_id);
        
    } else {
           return new soap_fault(invalid_session_fault,'addArtifact','Invalid Session ','');
    }
}

/**
 * updateArtifact - update the artifact $artifact_id in tracker $group_artifact_id of the project $group_id with given values
 *
 * NOTE : the mail notification system is not implemented with the SOAP API.
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to update the artifact
 * @param int $group_artifact_id the ID of the tracker we want to update the artifact
 * @param int $artifact_id the ID of the artifact we want to update
 * @param int $status_id the ID of the status of the artifact
 * @param int $close_date the close date of the artifact. The date format is timestamp
 * @param string $summary the summary of the artifact
 * @param string $details the details (original submission) of the artifact
 * @param int $severity the severity of the artifact
 * @param array{SOAPArtifactFieldValue} $extra_fields the extra_fields of the artifact (non standard fields)
 * @param @deprecated string $artifact_id_dependent a list of artifact IDs (separated with a comma) this artifact is dependent. PLEASE DO NOT USE THIS PARAM
 * @param @deprecated int $canned_response the id of the canned response associated with a follow-up. PLEASE DO NOT USE THIS PARAM
 * @return int the ID of the artifact, 
 *              or a soap fault if :
 *              - group_id does not match with a valid project, 
 *              - group_artifact_id does not match with a valid tracker,
 *              - artifact_id does not match with a valid artifact,
 *              - the given values are breaking a field dependency rule
 *              - the artifact modification failed.
 */
function updateArtifact($sessionKey, $group_id, $group_artifact_id, $artifact_id, $status_id, $close_date, $summary, $details, $severity, $extra_fields, $artifact_id_dependent, $canned_response) {
    global $art_field_fact, $ath; 
    if (session_continue($sessionKey)) {
        $grp =& group_get_object($group_id);
        if (!$grp || !is_object($grp)) {
            return new soap_fault(get_group_fault,'updateArtifact','Could Not Get Group','Could Not Get Group');
        } elseif ($grp->isError()) {
            return new soap_fault(get_group_fault,'updateArtifact',$grp->getErrorMessage(),$grp->getErrorMessage());
        }
        if (!checkRestrictedAccess($grp)) {
            return new soap_fault(get_group_fault, 'getArtifactTypes', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
        }
        
        $ath = new ArtifactType($grp, $group_artifact_id);
        if (!$ath || !is_object($ath)) {
            return new soap_fault(get_artifact_type_fault, 'updateArtifact', 'ArtifactType could not be created','ArtifactType could not be created');
        }
        if ($ath->isError()) {
            return new soap_fault(get_artifact_type_fault, 'updateArtifact', $ath->getErrorMessage(),$ath->getErrorMessage());
        }
        // Check if this tracker is valid (not deleted)
        if ( !$ath->isValid() ) {
            return new soap_fault(get_artifact_type_fault, 'updateArtifact', 'This tracker is no longer valid.','This tracker is no longer valid.');
        }
        
        $art_field_fact = new ArtifactFieldFactory($ath);
        if (!$art_field_fact || !is_object($art_field_fact)) {
            return new soap_fault(get_artifact_field_factory_fault, 'updateArtifact', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
        } elseif ($art_field_fact->isError()) {
            return new soap_fault(get_artifact_field_factory_fault, 'updateArtifact', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
        }
        ;
        $a = new Artifact($ath, $artifact_id);
        if (!$a || !is_object($a)) {
            return new soap_fault(get_artifact_fault, 'updateArtifact', 'Could Not Get Artifact', 'Could Not Get Artifact');
        } elseif ($a->isError()) {
            return new soap_fault(get_artifact_fault, 'updateArtifact', $a->getErrorMessage(), $a->getErrorMessage());
        }
        
        $data = setArtifactData($status_id, $close_date, $summary, $details, $severity, $extra_fields);
        
        //Check Field Dependencies
        require_once('common/tracker/ArtifactRulesManager.class.php');
        $arm =& new ArtifactRulesManager();
        if (!$arm->validate($ath->getID(), $data, $art_field_fact)) {
            return new soap_fault(invalid_field_dependency_fault, 'updateArtifact', 'Invalid Field Dependency', 'Invalid Field Dependency');
        }
        
        if (!$a->handleUpdate($artifact_id_dependent, $canned_response, $changes, false, $data, true)) {
            return new soap_fault(update_artifact_fault, 'updateArtifact', $a->getErrorMessage(), $a->getErrorMessage());
        } else {
            
            if ($a->isError()) {
                return new soap_fault(get_artifact_type_fault, 'updateArtifact', $a->getErrorMessage(),$a->getErrorMessage());
            }
            // Update last_update_date field
            $a->update_last_update_date();
            return $a->getID();
        }
        
    } else {
        return new soap_fault(invalid_session_fault,'updateArtifact','Invalid Session ','');
    }
}

/**
 * updateArtifactWithFieldNames - update the artifact $artifact_id in tracker $tracker_name of the project $group_id with given values
 *
 * NOTE : the mail notification system is not implemented with the SOAP API.
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to update the artifact
 * @param int $group_artifact_id the ID of the tracker we want to update the artifact
 * @param int $artifact_id the ID of the artifact we want to update
 * @param int $status_id the ID of the status of the artifact
 * @param int $close_date the close date of the artifact. The date format is timestamp
 * @param string $summary the summary of the artifact
 * @param string $details the details (original submission) of the artifact
 * @param int $severity the severity of the artifact
 * @param array{SOAPArtifactFieldNameValue} $extra_fields the extra_fields of the artifact (non standard fields)
 * @param @deprecated string $artifact_id_dependent a list of artifact IDs (separated with a comma) this artifact is dependent. PLEASE DO NOT USE THIS PARAM
 * @param @deprecated int $canned_response the id of the canned response associated with a follow-up. PLEASE DO NOT USE THIS PARAM
 * @return int the ID of the artifact, 
 *              or a soap fault if :
 *              - group_id does not match with a valid project, 
 *              - trackr_name does not match with a valid tracker,
 *              - artifact_id does not match with a valid artifact,
 *              - the given values are breaking a field dependency rule
 *              - the artifact modification failed.
 */
function updateArtifactWithFieldNames($sessionKey, $group_id, $group_artifact_id, $artifact_id, $status_id, $close_date, $summary, $details, $severity, $extra_fields, $artifact_id_dependent, $canned_response) {
    global $art_field_fact, $ath;
    if (session_continue($sessionKey)) {
        $grp =& group_get_object($group_id);
        if (!$grp || !is_object($grp)) {
            return new soap_fault(get_group_fault,'addArtifact','Could Not Get Group','Could Not Get Group');
        } elseif ($grp->isError()) {
            return new soap_fault(get_group_fault,'addArtifact',$grp->getErrorMessage(),$grp->getErrorMessage());
        }
        if (!checkRestrictedAccess($grp)) {
            return new soap_fault(get_group_fault, 'getArtifactTypes', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
        }
        $at = new ArtifactType($grp, $group_artifact_id);
        if (!$at || !is_object($at)) {
            return new soap_fault(get_artifact_type_fault, 'updateArtifact', 'ArtifactType could not be created','ArtifactType could not be created');
        }
        if ($at->isError()) {
            return new soap_fault(get_artifact_type_fault, 'updateArtifact', $at->getErrorMessage(),$at->getErrorMessage());
        }
        // Check if this tracker is valid (not deleted)
        if ( !$at->isValid() ) {
            return new soap_fault(get_artifact_type_fault, 'updateArtifact', 'This tracker is no longer valid.','This tracker is no longer valid.');
        }
        
        $group_artifact_id = $at->getID();
        
        $art_field_fact = new ArtifactFieldFactory($at);
        if (!$art_field_fact || !is_object($art_field_fact)) {
            return new soap_fault(get_artifact_field_factory_fault, 'updateArtifact', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
        } elseif ($art_field_fact->isError()) {
            return new soap_fault(get_artifact_field_factory_fault, 'updateArtifact', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
        }
        
        // translate the field_name in field_id, in order to call the real addArtifact function
        $extrafields_with_id = array();
        foreach($extra_fields as $extra_field_name) {
            $field = $art_field_fact->getFieldFromName($extra_field_name['field_name']);
            if ($field) {
                $extra_field_id = $field->getID();
                $extrafields_with_id[] = array('field_id' => $extra_field_id, 'field_value' => $extra_field_name['field_value']);
            } else {
                return new soap_fault(invalid_field_fault,'updateArtifact','Invalid Field:'.$extra_field_name['field_name'],'updateArtifact','Invalid Field:'.$extra_field_name['field_name']);
            }
        }
        
        return updateArtifact($sessionKey, $group_id, $group_artifact_id, $artifact_id, $status_id, $close_date, $summary, $details, $severity, $extrafields_with_id, $artifact_id_dependent, $canned_response);
        
    } else {
        return new soap_fault(invalid_session_fault,'updateArtifact','Invalid Session ','');
    }
}


/**
 * getArtifactFollowups - returns the array of follow-ups of the artifact $artifact_d in tracker $group_artifact_id of the project $group_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the artifact follow-ups
 * @param int $group_artifact_id the ID of the tracker we want to retrieve the artifact follow-ups
 * @param int $artifact_id the ID of the artifact we want to retrieve the follow-ups
 * @return array{SOAPArtifactFollowup} the array of the follow-ups for this artifact, 
 *              or a soap fault if :
 *              - group_id does not match with a valid project, 
 *              - group_artifact_id does not match with a valid tracker,
 *              - the artifact_id does not match with a valid artifact
 */
function &getArtifactFollowups($sessionKey, $group_id, $group_artifact_id, $artifact_id) {
    global $art_field_fact; 
    if (session_continue($sessionKey)){
        $grp =& group_get_object($group_id);
        if (!$grp || !is_object($grp)) {
            return new soap_fault(get_group_fault,'getArtifactFollowups','Could Not Get Group','Could Not Get Group');
        } elseif ($grp->isError()) {
            return new soap_fault(get_group_fault,'getArtifactFollowups',$grp->getErrorMessage(),$grp->getErrorMessage());
        }
        if (!checkRestrictedAccess($grp)) {
            return new soap_fault(get_group_fault, 'getArtifactTypes', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
        }

        $at = new ArtifactType($grp,$group_artifact_id);
        if (!$at || !is_object($at)) {
            return new soap_fault(get_artifact_type_fault,'getArtifactFollowups','Could Not Get ArtifactType','Could Not Get ArtifactType');
        } elseif ($at->isError()) {
            return new soap_fault(get_artifact_type_fault,'getArtifactFollowups',$at->getErrorMessage(),$at->getErrorMessage());
        }

        $art_field_fact = new ArtifactFieldFactory($at);
        if (!$art_field_fact || !is_object($art_field_fact)) {
            return new soap_fault(get_artifact_field_factory_fault, 'getArtifactFollowups', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
        } elseif ($art_field_fact->isError()) {
            return new soap_fault(get_artifact_field_factory_fault, 'getArtifactFollowups', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
        }

        $a = new Artifact($at, $artifact_id);
        if (!$a || !is_object($a)) {
            return new soap_fault(get_artifact_fault, 'getArtifactFollowups', 'Could Not Get Artifact', 'Could Not Get Artifact');
        } elseif ($a->isError()) {
            return new soap_fault(get_artifact_fault, 'getArtifactFollowups', $a->getErrorMessage(), $a->getErrorMessage());
        }
        $return  = artifactfollowups_to_soap($a->getFollowups());
        return new soapval('return', 'tns:ArrayOfArtifactFollowup', $return);        
    } else {
        return new soap_fault(invalid_session_fault,'getArtifactFollowups','Invalid Session ','');
    }
}

function artifactfollowups_to_soap($followups_res) {
    $return = array();
    $rows = db_numrows($followups_res);
    for ($i=0; $i < $rows; $i++) {
        $return[] = array (
            'artifact_id'          => db_result($followups_res, $i, 'artifact_id'),    
            'comment'               => db_result($followups_res, $i, 'old_value'),
            'date'                     => db_result($followups_res, $i, 'date'),
            'by'                    => (db_result($followups_res, $i, 'mod_by')==100?db_result($followups_res, $i, 'email'):db_result($followups_res, $i, 'user_name')),
            'comment_type_id'     => db_result($followups_res, $i, 'comment_type_id')            
        );
    }
    return $return;
}

/**
 * getArtifactCannedResponses - returns the array of canned responses of the tracker $group_artifact_id of the project $group_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the artifact follow-ups
 * @param int $group_artifact_id the ID of the tracker we want to retrieve the artifact follow-ups
 * @return array{SOAPArtifactCannedResponses} the array of the canned responses for this tracker,
 *              or a soap fault if :
 *              - group_id does not match with a valid project, 
 *              - group_artifact_id does not match with a valid tracker
 */
function &getArtifactCannedResponses($sessionKey, $group_id, $group_artifact_id) {
    if (session_continue($sessionKey)) {
        $grp =& group_get_object($group_id);
        if (!$grp || !is_object($grp)) {
            return new soap_fault(get_group_fault,'getArtifactCannedResponses','Could Not Get Group','Could Not Get Group');
        } elseif ($grp->isError()) {
            return new soap_fault(get_group_fault,'getArtifactCannedResponses',$grp->getErrorMessage(),$grp->getErrorMessage());
        }
        if (!checkRestrictedAccess($grp)) {
            return new soap_fault(get_group_fault, 'getArtifactTypes', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
        }
        
        $at = new ArtifactType($grp,$group_artifact_id);
        if (!$at || !is_object($at)) {
            return new soap_fault(get_artifact_type_fault,'getArtifactCannedResponses','Could Not Get ArtifactType','Could Not Get ArtifactType');
        } elseif ($at->isError()) {
            return new soap_fault(get_artifact_type_fault,'getArtifactCannedResponses',$at->getErrorMessage(),$at->getErrorMessage());
        }
        return artifactcannedresponses_to_soap($at->getCannedResponses());
    } else {
        return new soap_fault(invalid_session_fault,'getArtifactCannedResponses','Invalid Session ','');
    }
}

function artifactcannedresponses_to_soap($cannedresponses_res) {
    $return = array();
    $rows = db_numrows($cannedresponses_res);
    for ($i=0; $i < $rows; $i++) {
        $return[] = array (
            'artifact_canned_id' => db_result($cannedresponses_res, $i, 'artifact_canned_id'),
            'title' => db_result($cannedresponses_res, $i, 'title'),
            'body' => db_result($cannedresponses_res, $i, 'body')
        );
    }
    return $return;
}

/**
 * getArtifactReports - returns the array of reports of the tracker $group_artifact_id of the project $group_id for the current user
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the reports
 * @param int $group_artifact_id the ID of the tracker we want to retrieve the reports
 * @param @deprecated int user_id the ID of the user we want to get the report. PLEASE DO NOT USE THIS PARAM.
 * @return array{SOAPArtifactReport} the array of the reports of the current user for this tracker,
 *              or a soap fault if :
 *              - group_id does not match with a valid project, 
 *              - group_artifact_id does not match with a valid tracker
 */
function &getArtifactReports($sessionKey, $group_id, $group_artifact_id, $user_id) {
    // Deprecated param. DO NOT USE ANYMORE
    $user_id = user_getid();
    if (session_continue($sessionKey)) {
        $grp =& group_get_object($group_id);
        if (!$grp || !is_object($grp)) {
            return new soap_fault(get_group_fault,'getArtifactReports','Could Not Get Group','Could Not Get Group');
        } elseif ($grp->isError()) {
            return new soap_fault(get_group_fault,'getArtifactReports',$grp->getErrorMessage(),$grp->getErrorMessage());
        }
        if (!checkRestrictedAccess($grp)) {
            return new soap_fault(get_group_fault, 'getArtifactTypes', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
        }
        
        $at = new ArtifactType($grp,$group_artifact_id);
        if (!$at || !is_object($at)) {
            return new soap_fault(get_artifact_type_fault,'getArtifactReports','Could Not Get ArtifactType','Could Not Get ArtifactType');
        } elseif ($at->isError()) {
            return new soap_fault(get_artifact_type_fault,'getArtifactReports',$at->getErrorMessage(),$at->getErrorMessage());
        }
        
        $report_fact = new ArtifactReportFactory();
        if (!$report_fact || !is_object($report_fact)) {
            return new soap_fault(get_report_factory_fault,'getArtifactReports', 'Could Not Get ArtifactReportFactory', 'Could Not Get ArtifactReportFactory');
        }
        
        return artifactreports_to_soap($report_fact->getReports($group_artifact_id, user_getid()));
    
    } else {
        return new soap_fault(invalid_session_fault, 'getArtifactReports', 'Invalid Session ', '');
    }
}

function &artifactreports_to_soap($artifactreports) {
    $return = array();
    if (is_array($artifactreports) && count($artifactreports)) {
        foreach ($artifactreports as $arid => $artifactreport){
            $fields = array();
            if ($artifactreport->isError()) {
                //skip if error
            } else {
                $report_fields = $artifactreport->getSortedFields();    
                if(is_array($report_fields) && count($report_fields) > 0 ) {
                    while(list($key, $field) = each($report_fields)) {
                        $fields[] = array (
                            'report_id'      => $artifactreport->getID(),
                            'field_name'     => $field->getName(),
                            'show_on_query'  => $field->getShowOnQuery(),
                            'show_on_result' => $field->getShowOnResult(),
                            'place_query'      => $field->getPlaceQuery(),
                            'place_result'      => $field->getPlaceResult(),
                            'col_width'     => $field->getColWidth()    
                        );
                    }
                }
                $return[]=array(
                    'report_id'           => $artifactreport->getID(),
                    'group_artifact_id'   => $artifactreport->getArtifactTypeID(),
                    'name'                     => $artifactreport->getName(),
                    'description'           => $artifactreport->getDescription(),
                    'scope'           => $artifactreport->getScope(),
                    'fields'          => $fields
                );
            }
        }
    }
    return $return;
}

/**
 * getArtifactAttachedFiles - returns the array of ArtifactFile of the artifact $artifact_id in the tracker $group_artifact_id of the project $group_id
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
function &getArtifactAttachedFiles($sessionKey,$group_id,$group_artifact_id,$artifact_id) {
    global $art_field_fact; 
    if (session_continue($sessionKey)) {
        $grp =& group_get_object($group_id);
        if (!$grp || !is_object($grp)) {
            return new soap_fault(get_group_fault,'getArtifactAttachedFiles','Could Not Get Group','Could Not Get Group');
        } elseif ($grp->isError()) {
            return new soap_fault(get_group_fault,'getArtifactAttachedFiles',$grp->getErrorMessage(),$grp->getErrorMessage());
        }
        if (!checkRestrictedAccess($grp)) {
            return new soap_fault(get_group_fault, 'getArtifactTypes', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
        }

        $at = new ArtifactType($grp,$group_artifact_id);
        if (!$at || !is_object($at)) {
            return new soap_fault(get_artifact_type_fault,'getArtifactAttachedFiles','Could Not Get ArtifactType','Could Not Get ArtifactType');
        } elseif ($at->isError()) {
            return new soap_fault(get_artifact_type_fault,'getArtifactAttachedFiles',$at->getErrorMessage(),$at->getErrorMessage());
        }
        
        $art_field_fact = new ArtifactFieldFactory($at);
        if (!$art_field_fact || !is_object($art_field_fact)) {
            return new soap_fault(get_artifact_field_factory_fault, 'getArtifactAttachedFiles', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
        } elseif ($art_field_fact->isError()) {
            return new soap_fault(get_artifact_field_factory_fault, 'getArtifactAttachedFiles', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
        }
        
        $a = new Artifact($at,$artifact_id);
        if (!$a || !is_object($a)) {
            return new soap_fault(get_artifact_fault,'getArtifactAttachedFiles','Could Not Get Artifact','Could Not Get Artifact');
        } elseif ($a->isError()) {
            return new soap_fault(get_artifact_fault,'getArtifactAttachedFiles',$a->getErrorMessage(),$a->getErrorMessage());
        } elseif (! $a->userCanView()) {
            return new soap_fault(get_artifact_fault,'getArtifactAttachedFiles','Permissions denied','Permissions denied');
        }
        
        return artifactfiles_to_soap($a->getAttachedFiles());
    } else {
        return new soap_fault(invalid_session_fault, 'getArtifactAttachedFiles', 'Invalid Session', '');
    }
}

/**
 * @deprecated please use getArtifactAttachedFiles.
 */
function &getAttachedFiles($sessionKey,$group_id,$group_artifact_id,$artifact_id) {
    return getArtifactAttachedFiles($sessionKey,$group_id,$group_artifact_id,$artifact_id);
}

function artifactfiles_to_soap($attachedfiles_arr) {
    $return = array();
    $rows=db_numrows($attachedfiles_arr);
    for ($i=0; $i<$rows; $i++) {
        $bin_data = db_result($attachedfiles_arr, $i, 'bin_data');
        $encoded_data = base64_encode($bin_data);
        $return[]=array(
            'id' => db_result($attachedfiles_arr, $i, 'id'),
            'artifact_id' => db_result($attachedfiles_arr, $i, 'artifact_id'),
            'filename' => db_result($attachedfiles_arr, $i, 'filename'),
            'description' => db_result($attachedfiles_arr, $i, 'description'),
            'bin_data' => $encoded_data,
            'filesize' => db_result($attachedfiles_arr, $i, 'filesize'),
            'filetype' => db_result($attachedfiles_arr, $i, 'filetype'),
            'adddate'  => db_result($attachedfiles_arr, $i, 'adddate'),
            'submitted_by' => db_result($attachedfiles_arr, $i, 'user_name')
        );
    }
    return $return;
}

/**
 * getArtifactDependencies - returns the array of ArtifactDependence of the artifact $artifact_id in the tracker $group_artifact_id of the project $group_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the dependencies
 * @param int $group_artifact_id the ID of the tracker we want to retrieve the dependencies
 * @param int $artifact_id the ID of the artifact we want to retrieve the dependencies
 * @return array{SOAPArtifactDependence} the array of the dependencies of the artifact,
 *              or a soap fault if :
 *              - group_id does not match with a valid project, 
 *              - group_artifact_id does not match with a valid tracker
 *              - artifact_id does not match with a valid artifact
 */
function getArtifactDependencies($sessionKey,$group_id,$group_artifact_id,$artifact_id) {
    global $art_field_fact; 
    if (session_continue($sessionKey)) {
        $grp =& group_get_object($group_id);
        if (!$grp || !is_object($grp)) {
            return new soap_fault(get_group_fault,'getArtifactDependencies','Could Not Get Group','Could Not Get Group');
        } elseif ($grp->isError()) {
            return new soap_fault(get_group_fault,'getArtifactDependencies',$grp->getErrorMessage(),$grp->getErrorMessage());
        }
        if (!checkRestrictedAccess($grp)) {
            return new soap_fault(get_group_fault, 'getArtifactTypes', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
        }

        $at = new ArtifactType($grp,$group_artifact_id);
        if (!$at || !is_object($at)) {
            return new soap_fault(get_artifact_type_fault,'getArtifactDependencies','Could Not Get ArtifactType','Could Not Get ArtifactType');
        } elseif ($at->isError()) {
            return new soap_fault(get_artifact_type_fault,'getArtifactDependencies',$at->getErrorMessage(),$at->getErrorMessage());
        }
        
        $art_field_fact = new ArtifactFieldFactory($at);
        if (!$art_field_fact || !is_object($art_field_fact)) {
            return new soap_fault(get_artifact_field_factory_fault, 'getArtifactDependencies', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
        } elseif ($art_field_fact->isError()) {
            return new soap_fault(get_artifact_field_factory_fault, 'getArtifactDependencies', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
        }

        $a = new Artifact($at,$artifact_id);
        if (!$a || !is_object($a)) {
            return new soap_fault(get_artifact_fault,'getArtifactDependencies','Could Not Get Artifact','Could Not Get Artifact');
        } elseif ($a->isError()) {
            return new soap_fault(get_artifact_fault,'getArtifactDependencies',$a->getErrorMessage(),$a->getErrorMessage());
        } elseif (! $a->userCanView()) {
            return new soap_fault(get_artifact_fault,'getArtifactAttachedFiles','Permissions denied','Permissions denied');
        }
    
        return dependencies_to_soap($a->getDependencies());
    } else {
        return new soap_fault(invalid_session_fault, 'getArtifactDependencies', 'Invalid Session', '');
    }
}

/**
 * @deprecated please use getArtifactDependencies.
 */
function getDependencies($sessionKey,$group_id,$group_artifact_id,$artifact_id) {
    return getArtifactDependencies($sessionKey,$group_id,$group_artifact_id,$artifact_id);
}
 
function dependencies_to_soap($dependancies) {
    $return = array();
    $rows=db_numrows($dependancies);
    for ($i=0; $i<$rows; $i++) {
        $return[]=array(
            'artifact_depend_id' => db_result($dependancies, $i, 'artifact_depend_id'),
            'artifact_id' => db_result($dependancies, $i, 'artifact_id'),
            'is_dependent_on_artifact_id' => db_result($dependancies, $i, 'is_dependent_on_artifact_id')
        );
    }
    return $return;
} 

/**
 * addArtifactFile - add an attached file to the artifact $artifact_id
 *
 * NOTE : the mail notification system is not implemented with the SOAP API.
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
function addArtifactFile($sessionKey,$group_id,$group_artifact_id,$artifact_id,$encoded_data,$description,$filename,$filetype) {
    global $art_field_fact; 
    if (session_continue($sessionKey)) {
        $grp =& group_get_object($group_id);
        if (!$grp || !is_object($grp)) {
            return new soap_fault(get_group_fault,'addArtifactFile','Could Not Get Group','Could Not Get Group');
        } elseif ($grp->isError()) {
            return new soap_fault(get_group_fault,'addArtifactFile',$grp->getErrorMessage(),$grp->getErrorMessage());
        }
        if (!checkRestrictedAccess($grp)) {
            return new soap_fault(get_group_fault, 'getArtifactTypes', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
        }

        $at = new ArtifactType($grp,$group_artifact_id);
        if (!$at || !is_object($at)) {
            return new soap_fault(get_artifact_type_fault,'addArtifactFile','Could Not Get ArtifactType','Could Not Get ArtifactType');
        } elseif ($at->isError()) {
            return new soap_fault(get_artifact_type_fault,'addArtifactFile',$at->getErrorMessage(),$at->getErrorMessage());
        }
        
        $art_field_fact = new ArtifactFieldFactory($at);
        if (!$art_field_fact || !is_object($art_field_fact)) {
            return new soap_fault(get_artifact_field_factory_fault, 'addArtifactFile', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
        } elseif ($art_field_fact->isError()) {
            return new soap_fault(get_artifact_field_factory_fault, 'addArtifactFile', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
        }

        $a = new Artifact($at,$artifact_id);
        if (!$a || !is_object($a)) {
            return new soap_fault(get_artifact_fault,'addArtifactFile','Could Not Get Artifact','Could Not Get Artifact');
        } elseif ($a->isError()) {
            return new soap_fault(get_artifact_fault,'addArtifactFile',$a->getErrorMessage(),$a->getErrorMessage());
        }

        $af = new ArtifactFile($a);
        if (!$af || !is_object($af)) {
            return new soap_fault(get_artifact_file_fault,'addArtifactFile','Could Not Create File Object','Could Not Create File Object');
        } else if ($af->isError()) {
            return new soap_fault(get_artifact_file_fault,'addArtifactFile',$af->getErrorMessage(),$af->getErrorMessage());
        }

        $bin_data = addslashes(base64_decode($encoded_data));

        $filesize = strlen($bin_data);

        $id = $af->create($filename,$filetype,$filesize,$bin_data,$description, $changes);

        if (!$id) {
            return new soap_fault(get_artifact_file_fault,'addArtifactFile',$af->getErrorMessage(),$af->getErrorMessage());
        }

        return $id;
    } else {
        return new soap_fault(invalid_session_fault, 'addArtifactFile', 'Invalid Session', 'Invalid Session');
    }
}

/**
 * deleteArtifactFile - delete an attached file to the artifact $artifact_id
 *
 * NOTE : the mail notification system is not implemented with the SOAP API.
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
function deleteArtifactFile($sessionKey,$group_id,$group_artifact_id,$artifact_id,$file_id) {
    global $art_field_fact; 
    if (session_continue($sessionKey)) {
        $grp =& group_get_object($group_id);
        if (!$grp || !is_object($grp)) {
            return new soap_fault(get_group_fault,'deleteArtifactFile','Could Not Get Group','Could Not Get Group');
        } elseif ($grp->isError()) {
            return new soap_fault(get_group_fault,'deleteArtifactFile',$grp->getErrorMessage(),$grp->getErrorMessage());
        }
        if (!checkRestrictedAccess($grp)) {
            return new soap_fault(get_group_fault, 'getArtifactTypes', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
        }

        $at = new ArtifactType($grp,$group_artifact_id);
        if (!$at || !is_object($at)) {
            return new soap_fault(get_artifact_type_fault,'deleteArtifactFile','Could Not Get ArtifactType','Could Not Get ArtifactType');
        } elseif ($at->isError()) {
            return new soap_fault(get_artifact_type_fault,'deleteArtifactFile',$at->getErrorMessage(),$at->getErrorMessage());
        }

        $art_field_fact = new ArtifactFieldFactory($at);
        if (!$art_field_fact || !is_object($art_field_fact)) {
            return new soap_fault(get_artifact_field_factory_fault, 'deleteArtifactFile', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
        } elseif ($art_field_fact->isError()) {
            return new soap_fault(get_artifact_field_factory_fault, 'deleteArtifactFile', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
        }

        $a = new Artifact($at,$artifact_id);
        if (!$a || !is_object($a)) {
            return new soap_fault(get_artifact_fault,'deleteArtifactFile','Could Not Get Artifact','Could Not Get Artifact');
        } elseif ($a->isError()) {
            return new soap_fault(get_artifact_fault,'deleteArtifactFile',$a->getErrorMessage(),$a->getErrorMessage());
        }

        $af = new ArtifactFile($a, $file_id);
        if (!$af || !is_object($af)) {
            return new soap_fault(get_artifact_file_fault,'deleteArtifactFile','Could Not Create File Object','Could Not Create File Object');
        } else if ($af->isError()) {
            return new soap_fault(get_artifact_file_fault,'deleteArtifactFile',$af->getErrorMessage(),$af->getErrorMessage());
        }

        if (!$af->delete()) {
            return new soap_fault(get_artifact_file_fault,'deleteArtifactFile',$af->getErrorMessage(),$af->getErrorMessage());
        }

        return $file_id;
    } else {
        return new soap_fault(invalid_session_fault, 'deleteArtifactFile', 'Invalid Session', 'Invalid Session');
    }
}

/**
 * addArtifactDependencies - add dependencies to the artifact $artifact_id
 *
 * NOTE : the mail notification system is not implemented with the SOAP API.
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to add the dependencies
 * @param int $group_artifact_id the ID of the tracker we want to add the dependencies
 * @param int $artifact_id the ID of the artifact we want to add the dependencies
 * @param string $is_dependent_on_artifact_id the list of dependencies, in the form of a list of artifact_id, separated with a comma.
 * @return void if the add is ok or a soap fault if :
 *              - group_id does not match with a valid project, 
 *              - group_artifact_id does not match with a valid tracker
 *              - artifact_id does not match with a valid artifact
 *              - the add failed
 */
function addArtifactDependencies($sessionKey, $group_id, $group_artifact_id, $artifact_id, $is_dependent_on_artifact_id){
    global $art_field_fact; 
    if (session_continue($sessionKey)) {
        $grp =& group_get_object($group_id);
        if (!$grp || !is_object($grp)) {
            return new soap_fault(get_group_fault,'addArtifactDependencies','Could Not Get Group','Could Not Get Group');
        } elseif ($grp->isError()) {
            return new soap_fault(get_group_fault,'addArtifactDependencies',$grp->getErrorMessage(),$grp->getErrorMessage());
        }
        if (!checkRestrictedAccess($grp)) {
            return new soap_fault(get_group_fault, 'getArtifactTypes', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
        }

        $at = new ArtifactType($grp,$group_artifact_id);
        if (!$at || !is_object($at)) {
            return new soap_fault(get_artifact_type_fault,'addArtifactDependencies','Could Not Get ArtifactType','Could Not Get ArtifactType');
        } elseif ($at->isError()) {
            return new soap_fault(get_artifact_type_fault,'addArtifactDependencies',$at->getErrorMessage(),$at->getErrorMessage());
        }

        $art_field_fact = new ArtifactFieldFactory($at);
        if (!$art_field_fact || !is_object($art_field_fact)) {
            return new soap_fault(get_artifact_field_factory_fault, 'addArtifactDependencies', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
        } elseif ($art_field_fact->isError()) {
            return new soap_fault(get_artifact_field_factory_fault, 'addArtifactDependencies', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
        }

        $a = new Artifact($at,$artifact_id);
        if (!$a || !is_object($a)) {
            return new soap_fault(get_artifact_fault,'addArtifactDependencies','Could Not Get Artifact','Could Not Get Artifact');
        } elseif ($a->isError()) {
            return new soap_fault(get_artifact_fault,'addArtifactDependencies',$a->getErrorMessage(),$a->getErrorMessage());
        }

        $comma_separated = implode(",", $is_dependent_on_artifact_id);

        $a->addDependencies($comma_separated,&$changes,true);
        if (!isset($changes) || !is_array($changes) || count($changes) == 0) {
            return new soap_fault(add_dependency_fault, 'addArtifactDependencies', 'Dependencies addition for artifact #'.$a->getID().' failed', 'Dependencies addition for artifact #'.$a->getID().' failed');
        }
    } else {
        return new soap_fault(invalid_session_fault, 'addArtifactDependencies', 'Invalid Session', 'Invalid Session');
    }
}

/**
 * @deprecated Please use addArtifactDependencies
 */
function addDependencies($sessionKey, $group_id, $group_artifact_id, $artifact_id, $is_dependent_on_artifact_id) {
    return addArtifactDependencies($sessionKey, $group_id, $group_artifact_id, $artifact_id, $is_dependent_on_artifact_id);
}

/**
 * deleteArtifactDependency - delete the dependency between $artifact_id and $dependent_on_artifact_id
 *
 * NOTE : the mail notification system is not implemented with the SOAP API.
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to delete the dependency
 * @param int $group_artifact_id the ID of the tracker we want to delete the dependency
 * @param int $artifact_id the ID of the artifact we want to delete the dependency
 * @param int $dependent_on_artifact_id the ID of the artifact which make the dependence we want to delete
 * @return int the ID of the deleted dependency or a soap fault if :
 *              - group_id does not match with a valid project, 
 *              - group_artifact_id does not match with a valid tracker
 *              - artifact_id does not match with a valid artifact
 *              - dependent_on_artifact_id does not match with a valid artifact or is not a valid dependency
 *              - the delete failed
 */
function deleteArtifactDependency($sessionKey, $group_id, $group_artifact_id, $artifact_id, $dependent_on_artifact_id) {
    global $art_field_fact; 
    if (session_continue($sessionKey)) {
        $grp =& group_get_object($group_id);
        if (!$grp || !is_object($grp)) {
            return new soap_fault(get_group_fault,'deleteArtifactDependency','Could Not Get Group','Could Not Get Group');
        } elseif ($grp->isError()) {
            return new soap_fault(get_group_fault,'deleteArtifactDependency',$grp->getErrorMessage(),$grp->getErrorMessage());
        }
        if (!checkRestrictedAccess($grp)) {
            return new soap_fault(get_group_fault, 'getArtifactTypes', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
        }

        $at = new ArtifactType($grp,$group_artifact_id);
        if (!$at || !is_object($at)) {
            return new soap_fault(get_artifact_type_fault,'deleteArtifactDependency','Could Not Get ArtifactType','Could Not Get ArtifactType');
        } elseif ($at->isError()) {
            return new soap_fault(get_artifact_type_fault,'deleteArtifactDependency',$at->getErrorMessage(),$at->getErrorMessage());
        }

        $art_field_fact = new ArtifactFieldFactory($at);
        if (!$art_field_fact || !is_object($art_field_fact)) {
            return new soap_fault(get_artifact_field_factory_fault, 'deleteArtifactDependency', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
        } elseif ($art_field_fact->isError()) {
            return new soap_fault(get_artifact_field_factory_fault, 'deleteArtifactDependency', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
        }

        $a = new Artifact($at,$artifact_id);
        if (!$a || !is_object($a)) {
            return new soap_fault(get_artifact_fault,'deleteArtifactDependency','Could Not Get Artifact','Could Not Get Artifact');
        } elseif ($a->isError()) {
            return new soap_fault(get_artifact_fault,'deleteArtifactDependency',$a->getErrorMessage(),$a->getErrorMessage());
        }

        if (!$a->existDependency($dependent_on_artifact_id) || !$a->deleteDependency($dependent_on_artifact_id,$changes)) {
            return new soap_fault(delete_dependency_fault, 'deleteArtifactDependency', 'Error deleting dependency'. $dependent_on_artifact_id, 'Error deleting dependency'. $dependent_on_artifact_id);
        } else { 
            return $dependent_on_artifact_id;
        }
        
    } else {
        return new soap_fault(invalid_session_fault, 'deleteArtifactDependency', 'Invalid Session', 'Invalid Session');
    }
}

/**
 * @deprecated Please use deleteArtifactDependency
 */
function deleteDependency($sessionKey, $group_id, $group_artifact_id, $artifact_id, $dependent_on_artifact_id) {
    return deleteArtifactDependency($sessionKey, $group_id, $group_artifact_id, $artifact_id, $dependent_on_artifact_id);
}

/**
 * addArtifactFollowup - add a followup to the artifact $artifact_id
 *
 * NOTE : the mail notification system is not implemented with the SOAP API.
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to add the follow-up
 * @param int $group_artifact_id the ID of the tracker we want to add the follow-up
 * @param int $artifact_id the ID of the artifact we want to add the follow-up
 * @param string $body the body of the follow-up
 * @return void if the add is ok or a soap fault if :
 *              - group_id does not match with a valid project, 
 *              - group_artifact_id does not match with a valid tracker
 *              - artifact_id does not match with a valid artifact
 *              - the add failed
 */
function addArtifactFollowup($sessionKey,$group_id,$group_artifact_id,$artifact_id,$body) {
    global $art_field_fact; 
    if (session_continue($sessionKey)) {
        $grp =& group_get_object($group_id);
        if (!$grp || !is_object($grp)) {
            return new soap_fault(get_group_fault,'addArtifactFollowup','Could Not Get Group','Could Not Get Group');
        } elseif ($grp->isError()) {
            return new soap_fault(get_group_fault,'addArtifactFollowup',$grp->getErrorMessage(),$grp->getErrorMessage());
        }
        if (!checkRestrictedAccess($grp)) {
            return new soap_fault(get_group_fault, 'getArtifactTypes', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
        }

        $at = new ArtifactType($grp,$group_artifact_id);
        if (!$at || !is_object($at)) {
            return new soap_fault(get_artifact_type_fault,'addArtifactFollowup','Could Not Get ArtifactType','Could Not Get ArtifactType');
        } elseif ($at->isError()) {
            return new soap_fault(get_artifact_type_fault,'addArtifactFollowup',$at->getErrorMessage(),$at->getErrorMessage());
        }

        $art_field_fact = new ArtifactFieldFactory($at);
        if (!$art_field_fact || !is_object($art_field_fact)) {
            return new soap_fault(get_artifact_field_factory_fault, 'addArtifactFollowup', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
        } elseif ($art_field_fact->isError()) {
            return new soap_fault(get_artifact_field_factory_fault, 'addArtifactFollowup', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
        }

        $a = new Artifact($at,$artifact_id);
        if (!$a || !is_object($a)) {
            return new soap_fault(get_artifact_fault,'addArtifactFollowup','Could Not Get Artifact','Could Not Get Artifact');
        } elseif ($a->isError()) {
            return new soap_fault(get_artifact_fault,'addArtifactFollowup',$a->getErrorMessage(),$a->getErrorMessage());
        }
        if (!$a->addComment($body,false,&$changes)) {
            return new soap_fault(create_followup_fault, 'addArtifactFollowup', 'Comment could not be saved', 'Comment could not be saved');
        }
        // Update last_update_date field
        $a->update_last_update_date();

    } else {
        return new soap_fault(invalid_session_fault, 'addArtifactFollowup', 'Invalid Session', 'Invalid Session');
    }
}

/**
 * @deprecated Please use addArtifactFollowup
 */
function addFollowup($sessionKey,$group_id,$group_artifact_id,$artifact_id,$body) {
    return addArtifactFollowup($sessionKey,$group_id,$group_artifact_id,$artifact_id,$body);
}

/**
 * existArtifactSummary - check if the tracker $group_artifact_id already contains an artifact with the summary $summary
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_artifact_id the ID of the tracker we want to check
 * @param string $summary the summary we want to check
 * @return int the ID of the artifact containing the same summary in the tracker, or
 *              -1 if the summary does not exist in this tracker.
 */
function existArtifactSummary($sessionKey, $group_artifact_id, $summary) {
    if (session_continue($sessionKey)) {
        $user = session_get_userid();
        $res=db_query("SELECT artifact_id FROM artifact WHERE group_artifact_id = ".$group_artifact_id.
                  " AND submitted_by=".$user. 
                  " AND summary=\"".$summary."\"");
        if ($res && db_numrows($res) > 0) {
            return new soapval('return', 'xsd:int', db_result($res, 0, 0));
        } else {
            return new soapval('return', 'xsd:int', -1);
        }
    } else {
        return new soap_fault(invalid_session_fault, 'existArtifactSummary', 'Invalid Session', 'Invalid Session');
    }
}

/**
 * @deprecated Please use existArtifactSummary
 */
function existSummary($sessionKey, $group_artifact_id, $summary) {
    return existArtifactSummary($sessionKey, $group_artifact_id, $summary);
}

?>
