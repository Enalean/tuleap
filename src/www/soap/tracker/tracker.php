<?php
// define fault code constants
define('GET_GROUP_FAULT', '3000');
define('GET_ARTIFACT_TYPE_FACTORY_FAULT', '3002');
define('GET_ARTIFACT_FACTORY_FAULT', '3003');
define('GET_ARTIFACT_FIELD_FACTORY_FAULT', '3004');
define('GET_ARTIFACT_TYPE_FAULT', '3005');
define('GET_ARTIFACT_FAULT', '3006');
define('CREATE_ARTIFACT_FAULT', '3007');
define('INVALID_FIELD_DEPENDENCY_FAULT', '3009');
define('UPDATE_ARTIFACT_FAULT', '3010');
define('GET_ARTIFACT_FILE_FAULT', '3011');
define('ADD_DEPENDENCY_FAULT', '3012');
define('DELETE_DEPENDENCY_FAULT', '3013');
define('CREATE_FOLLOWUP_FAULT', '3014');
define('GET_ARTIFACT_FIELD_FAULT', '3015');
define('ADD_CC_FAULT', '3016');
define('INVALID_FIELD_FAULT', '3017');
define('DELETE_CC_FAULT', '3018');
define('GET_SERVICE_FAULT', '3020');
define('GET_ARTIFACT_REPORT_FAULT', '3021');
define('UPDATE_ARTIFACT_FOLLOWUP_FAULT', '3022');
define('DELETE_ARTIFACT_FOLLOWUP_FAULT', '3023');

require_once __DIR__ . '/../../include/pre.php';
require_once __DIR__ . '/../common/session.php';

if (defined('NUSOAP')) {
// Type definition
    $server->wsdl->addComplexType(
        'TrackerDesc',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'group_artifact_id' => array('name' => 'group_artifact_id', 'type' => 'xsd:int'),
        'group_id' => array('name' => 'group_id', 'type' => 'xsd:int'),
        'name' => array('name' => 'name', 'type' => 'xsd:string'),
        'description' => array('name' => 'description', 'type' => 'xsd:string'),
        'item_name' => array('name' => 'item_name', 'type' => 'xsd:string'),
        'open_count' => array('name' => 'open_count', 'type' => 'xsd:int'),
        'total_count' => array('name' => 'total_count', 'type' => 'xsd:int'),
        'reports_desc' => array('name' => 'reports', 'type' => 'tns:ArrayOfArtifactReportDesc'),
        )
    );

    $server->wsdl->addComplexType(
        'ArrayOfTrackerDesc',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:TrackerDesc[]')),
        'tns:TrackerDesc'
    );

    $server->wsdl->addComplexType(
        'ArtifactType',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'group_artifact_id' => array('name' => 'group_artifact_id', 'type' => 'xsd:int'),
        'group_id' => array('name' => 'group_id', 'type' => 'xsd:int'),
        'name' => array('name' => 'name', 'type' => 'xsd:string'),
        'description' => array('name' => 'description', 'type' => 'xsd:string'),
        'item_name' => array('name' => 'item_name', 'type' => 'xsd:string'),
        'open_count' => array('name' => 'open_count', 'type' => 'xsd:int'),
        'total_count' => array('name' => 'total_count', 'type' => 'xsd:int'),
        'total_file_size' => array('name' => 'total_file_size', 'type' => 'xsd:float'),
        'field_sets' => array('name' => 'field_sets', 'type' => 'tns:ArrayOfArtifactFieldSet'),
        'field_dependencies' => array('name' => 'field_dependencies', 'type' => 'tns:ArrayOfArtifactRule')
        )
    );

    $server->wsdl->addComplexType(
        'ArrayOfArtifactType',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:ArtifactType[]')),
        'tns:ArtifactType'
    );

    $server->wsdl->addComplexType(
        'ArtifactFieldSet',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'field_set_id' => array('name' => 'field_set_id', 'type' => 'xsd:int'),
        'group_artifact_id'  => array('name' => 'group_artifact_id', 'type' => 'xsd:int'),
        'name' => array('name' => 'name', 'type' => 'xsd:string'),
        'label' => array('name' => 'label', 'type' => 'xsd:string'),
        'description' => array('name' => 'description', 'type' => 'xsd:string'),
        'description_text' => array('name' => 'description_text', 'type' => 'xsd:string'),
        'rank' => array('name' => 'rank', 'type' => 'xsd:int'),
        'fields' => array('name' => 'fields', 'type' => 'tns:ArrayOfArtifactField'),
        )
    );

    $server->wsdl->addComplexType(
        'ArrayOfArtifactFieldSet',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:ArtifactFieldSet[]')),
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
        'group_artifact_id' => array('name' => 'group_artifact_id', 'type' => 'xsd:int'),
        'field_set_id' => array('name' => 'field_set_id', 'type' => 'xsd:int'),
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
        array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:ArtifactField[]')
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
        'artifact_id' => array('name' => 'artifact_id', 'type' => 'xsd:int'),
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
        array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:ArtifactFieldValue[]')
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
        array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:ArtifactFieldNameValue[]')
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
        'group_artifact_id' => array('name' => 'group_artifact_id', 'type' => 'xsd:int'),
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
        array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:ArtifactFieldValueList[]')
        ),
        'tns:ArtifactFieldValueList'
    );

    $server->wsdl->addComplexType(
        'ArtifactRule',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'rule_id' => array('name' => 'rule_id', 'type' => 'xsd:int'),
        'group_artifact_id'  => array('name' => 'group_artifact_id', 'type' => 'xsd:int'),
        'source_field_id' => array('name' => 'source_field_id', 'type' => 'xsd:int'),
        'source_value_id' => array('name' => 'source_value_id', 'type' => 'xsd:int'),
        'target_field_id' => array('name' => 'target_field_id', 'type' => 'xsd:int'),
        'target_value_id' => array('name' => 'target_value_id', 'type' => 'xsd:int')
        )
    );

    $server->wsdl->addComplexType(
        'ArrayOfArtifactRule',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:ArtifactRule[]')),
        'tns:ArtifactRule'
    );

    $server->wsdl->addComplexType(
        'Artifact',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'artifact_id' => array('name' => 'artifact_id', 'type' => 'xsd:int'),
        'group_artifact_id' => array('name' => 'group_artifact_id', 'type' => 'xsd:int'),
        'status_id' => array('name' => 'status_id', 'type' => 'xsd:int'),
        'submitted_by' => array('name' => 'submitted_by', 'type' => 'xsd:int'),
        'open_date' => array('name' => 'open_date', 'type' => 'xsd:int'),
        'close_date' => array('name' => 'close_date', 'type' => 'xsd:int'),
        'last_update_date' => array('name' => 'last_update_date', 'type' => 'xsd:int'),
        'summary' => array('name' => 'summary', 'type' => 'xsd:string'),
        'details' => array('name' => 'details', 'type' => 'xsd:string'),
        'severity' => array('name' => 'severity', 'type' => 'xsd:int'),
        'extra_fields' => array('name' => 'extra_fields', 'type' => 'tns:ArrayOfArtifactFieldValue')
        )
    );

    $server->wsdl->addComplexType(
        'ArrayOfArtifact',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:Artifact[]')),
        'tns:Artifact'
    );

    $server->wsdl->addComplexType(
        'Criteria',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'field_name' => array('name' => 'field_name', 'type' => 'xsd:string'),
        'field_value' => array('name' => 'field_value', 'type' => 'xsd:string'),
        'operator' => array('name' => 'operator', 'type' => 'xsd:string')
        )
    );

    $server->wsdl->addComplexType(
        'ArrayOfCriteria',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:Criteria[]')),
        'tns:Criteria'
    );

    $server->wsdl->addComplexType(
        'SortCriteria',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'field_name' => array('name' => 'field_name', 'type' => 'xsd:string'),
        'sort_direction' => array('name' => 'sort_direction', 'type' => 'xsd:string')
        )
    );

    $server->wsdl->addComplexType(
        'ArrayOfSortCriteria',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:SortCriteria[]')),
        'tns:SortCriteria'
    );


    $server->wsdl->addComplexType(
        'ArtifactQueryResult',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'total_artifacts_number' => array('name' => 'total_artifacts_number', 'type' => 'xsd:int'),
        'artifacts' => array('name' => 'artifacts', 'type' => 'tns:ArrayOfArtifact')
        )
    );

    $server->wsdl->addComplexType(
        'ArtifactCanned',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'artifact_canned_id' => array('name' => 'artifact_canned_id', 'type' => 'xsd:int'),
        'group_artifact_id'  => array('name' => 'group_artifact_id', 'type' => 'xsd:int'),
        'title'          => array('name' => 'title', 'type' => 'xsd:string'),
        'body'              => array('name' => 'body', 'type' => 'xsd:string')
        )
    );

    $server->wsdl->addComplexType(
        'ArrayOfArtifactCanned',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:ArtifactCanned[]')),
        'tns:ArtifactCanned'
    );

    $server->wsdl->addComplexType(
        'ArtifactFollowup',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'artifact_id'          => array('name' => 'artifact_id', 'type' => 'xsd:int'),
        'follow_up_id'          => array('name' => 'follow_up_id', 'type' => 'xsd:int'),
        'comment'           => array('name' => 'comment', 'type' => 'xsd:string'),
        'date'                     => array('name' => 'date', 'type' => 'xsd:int'),
        'original_date'                     => array('name' => 'original_date', 'type' => 'xsd:int'),
        'by'               => array('name' => 'by', 'type' => 'xsd:string'),
        'original_by'               => array('name' => 'original_by', 'type' => 'xsd:string'),
        'comment_type_id'     => array('name' => 'comment_type_id', 'type' => 'xsd:int'),
        'comment_type'     => array('name' => 'comment_type', 'type' => 'xsd:string'),
        'field_name'     => array('name' => 'field_name', 'type' => 'xsd:string'),
        'user_can_edit'  => array('name' => 'user_can_edit', 'type' => 'xsd:int')
        )
    );

    $server->wsdl->addComplexType(
        'ArrayOfArtifactFollowup',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:ArtifactFollowup[]')),
        'tns:ArtifactFollowup'
    );

    $server->wsdl->addComplexType(
        'ArtifactReport',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'report_id'         => array('name' => 'report_id', 'type' => 'xsd:int'),
        'group_artifact_id' => array('name' => 'group_artifact_id', 'type' => 'xsd:int'),
        'name'              => array('name' => 'name', 'type' => 'xsd:string'),
        'description'       => array('name' => 'description', 'type' => 'xsd:string'),
        'scope'             => array('name' => 'scope', 'type' => 'xsd:string'),
        'fields'            => array('name' => 'fields', 'type' => 'tns:ArrayOfArtifactReportField')
        )
    );

    $server->wsdl->addComplexType(
        'ArrayOfArtifactReport',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:ArtifactReport[]')),
        'tns:ArtifactReport'
    );

    $server->wsdl->addComplexType(
        'ArtifactReportDesc',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'report_id'         => array('name' => 'report_id', 'type' => 'xsd:int'),
        'group_artifact_id' => array('name' => 'group_artifact_id', 'type' => 'xsd:int'),
        'name'              => array('name' => 'name', 'type' => 'xsd:string'),
        'description'       => array('name' => 'description', 'type' => 'xsd:string'),
        'scope'             => array('name' => 'scope', 'type' => 'xsd:string')
        )
    );

    $server->wsdl->addComplexType(
        'ArrayOfArtifactReportDesc',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:ArtifactReportDesc[]')),
        'tns:ArtifactReportDesc'
    );

    $server->wsdl->addComplexType(
        'ArtifactFile',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'id' => array('name' => 'id', 'type' => 'xsd:int'),
        'artifact_id' => array('name' => 'artifact_id', 'type' => 'xsd:int'),
        'filename' => array('name' => 'filename', 'type' => 'xsd:string'),
        'description' => array('name' => 'description', 'type' => 'xsd:string'),
        'bin_data' => array('name' => 'bin_data', 'type' => 'xsd:base64Binary'),
        'filesize' => array('name' => 'filesize', 'type' => 'xsd:int'),
        'filetype' => array('name' => 'filetype', 'type' => 'xsd:string'),
        'adddate' => array('name' => 'adddate', 'type' => 'xsd:int'),
        'submitted_by' => array('name' => 'submitted_by', 'type' => 'xsd:string')
        )
    );

    $server->wsdl->addComplexType(
        'ArrayOfArtifactFile',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:ArtifactFile[]')),
        'tns:ArtifactFile'
    );

    $server->wsdl->addComplexType(
        'ArtifactReportField',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'report_id'           => array('name' => 'report_id', 'type' => 'xsd:int'),
        'field_name'          => array('name' => 'field_name', 'type' => 'xsd:string'),
        'show_on_query'       => array('name' => 'show_on_query', 'type' => 'xsd:int'),
        'show_on_result'      => array('name' => 'show_on_result', 'type' => 'xsd:int'),
        'place_query'           => array('name' => 'place_query', 'type' => 'xsd:int'),
        'place_result'           => array('name' => 'place_result', 'type' => 'xsd:int'),
        'col_width'          => array('name' => 'col_width', 'type' => 'xsd:int')
        )
    );

    $server->wsdl->addComplexType(
        'ArrayOfArtifactReportField',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:ArtifactReportField[]')),
        'tns:ArtifactReportField'
    );

    $server->wsdl->addComplexType(
        'ArtifactCC',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'artifact_cc_id' => array('name' => 'artifact_cc_id', 'type' => 'xsd:int'),
        'artifact_id' => array('name' => 'artifact_id', 'type' => 'xsd:int'),
        'email' => array('name' => 'email', 'type' => 'xsd:string'),
        'added_by' => array('name' => 'added_by', 'type' => 'xsd:int'),
        'added_by_name' => array('name' => 'added_by_name', 'type' => 'xsd:string'),
        'comment' => array('name' => 'comment', 'type' => 'xsd:string'),
        'date' => array('name' => 'date', 'type' => 'xsd:int')
        )
    );

    $server->wsdl->addComplexType(
        'ArrayOfArtifactCC',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:ArtifactCC[]')),
        'tns:ArtifactCC'
    );

    $server->wsdl->addComplexType(
        'ArtifactDependency',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'artifact_depend_id'          => array('name' => 'artifact_depend_id', 'type' => 'xsd:int'),
        'artifact_id'                 => array('name' => 'artifact_id', 'type' => 'xsd:int'),
        'is_dependent_on_artifact_id' => array('name' => 'is_dependent_on_artifact_id', 'type' => 'xsd:int'),
        'summary' => array('name' => 'summary', 'type' => 'xsd:string'),
        'tracker_id' => array('name' => 'tracker_id', 'type' => 'xsd:int'),
        'tracker_name' => array('name' => 'tracker_name', 'type' => 'xsd:string'),
        'group_id' => array('name' => 'group_id', 'type' => 'xsd:int'),
        'group_name' => array('name' => 'group_name', 'type' => 'xsd:string')
        )
    );

    $server->wsdl->addComplexType(
        'ArrayOfArtifactDependency',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:ArtifactDependency[]')),
        'tns:ArtifactDependency'
    );

    $server->wsdl->addComplexType(
        'ArtifactHistory',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        //'artifact_history_id' => array('name'=>'artifact_history_id', 'type' => 'xsd:int'),
        //'artifact_id' => array('name'=>'artifact_id', 'type' => 'xsd:int'),
        'field_name' => array('name' => 'field_name', 'type' => 'xsd:string'),
        'old_value' => array('name' => 'old_value', 'type' => 'xsd:string'),
        'new_value' => array('name' => 'new_value', 'type' => 'xsd:string'),
        'modification_by' => array('name' => 'modification_by', 'type' => 'xsd:string'),
        'date' => array('name' => 'date', 'type' => 'xsd:int')
        )
    );

    $server->wsdl->addComplexType(
        'ArrayOfArtifactHistory',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:ArtifactHistory[]')),
        'tns:ArtifactHistory'
    );

    $server->wsdl->addComplexType(
        'ArrayOfInt',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'xsd:int[]')),
        'xsd:int'
    );

    $server->wsdl->addComplexType(
        'ArtifactFromReport',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'artifact_id' => array('name' => 'artifact_id', 'type' => 'xsd:int'),
        'severity' => array('name' => 'severity', 'type' => 'xsd:int'),
        'fields' => array('name' => 'fields', 'type' => 'tns:ArrayOfArtifactFieldFromReport')
        )
    );

    $server->wsdl->addComplexType(
        'ArrayOfArtifactFromReport',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:ArtifactFromReport[]')),
        'tns:ArtifactFromReport'
    );

    $server->wsdl->addComplexType(
        'ArtifactFieldFromReport',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'field_name' => array('name' => 'field_name', 'type' => 'xsd:string'),
        'field_value' => array('name' => 'field_value', 'type' => 'xsd:string')
        )
    );

    $server->wsdl->addComplexType(
        'ArrayOfArtifactFieldFromReport',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:ArtifactFieldFromReport[]')),
        'tns:ArtifactFieldFromReport'
    );

    $server->wsdl->addComplexType(
        'ArtifactFromReportResult',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'total_artifacts_number' => array('name' => 'total_artifacts_number', 'type' => 'xsd:int'),
        'artifacts' => array('name' => 'artifacts', 'type' => 'tns:ArrayOfArtifactFromReport')
        )
    );

    if (! isset($uri)) {
        $uri = '';
    }
// Function definition
    $server->register(
        'getTrackerList', // method name
        array('sessionKey' => 'xsd:string', // input parameters
          'group_id' => 'xsd:int'
        ),
        array('return' => 'tns:ArrayOfTrackerDesc'), // output parameters
        $uri, // namespace
        $uri . '#getTrackerList', // soapaction
        'rpc', // style
        'encoded', // use
        'Returns the array of TrackerDesc (light description of trackers) that belongs to the group identified by group ID.
     Returns a soap fault if the group ID does not match with a valid project.' // documentation
    );

    $server->register(
        'getArtifactType', // method name
        array('sessionKey' => 'xsd:string', // input parameters
          'group_id' => 'xsd:int',
          'group_artifact_id' => 'xsd:int'
        ),
        array('return' => 'tns:ArtifactType'), // output parameters
        $uri, // namespace
        $uri . '#getArtifactType', // soapaction
        'rpc', // style
        'encoded', // use
        'Returns the ArtifactType (tracker) with the ID group_artifact_id that belongs to the group identified by group ID.
     Returns a soap fault if the group ID does not match with a valid project, or if the group_artifact_id is invalid or is not a tracker of the project.' // documentation
    );

    $server->register(
        'getArtifactTypes', // method name
        array('sessionKey' => 'xsd:string', // input parameters
          'group_id' => 'xsd:int'
        ),
        array('return' => 'tns:ArrayOfArtifactType'), // output parameters
        $uri, // namespace
        $uri . '#getArtifactTypes', // soapaction
        'rpc', // style
        'encoded', // use
        'Returns the array of ArtifactType (trackers) that belongs to the group identified by group ID.
     Returns a soap fault if the group ID does not match with a valid project.' // documentation
    );

    $server->register(
        'getArtifacts',
        array('sessionKey' => 'xsd:string',
          'group_id' => 'xsd:int',
          'group_artifact_id' => 'xsd:int',
          'criteria' => 'tns:ArrayOfCriteria',
          'offset' => 'xsd:int',
          'max_rows' => 'xsd:int'
        ),
        array('return' => 'tns:ArtifactQueryResult'),
        $uri,
        $uri . '#getArtifacts',
        'rpc',
        'encoded',
        'Returns the ArtifactQueryResult of the tracker group_artifact_id in the project group_id
     that are matching the given criteria. If offset AND max_rows are filled, it returns only
     max_rows artifacts, skipping the first offset ones.
     It is not possible to sort artifact with this function (use getArtifactsFromReport if you want to sort).
     Returns a soap fault if the group_id is not a valid one or if the group_artifact_id is not a valid one.'
    );

    $server->register(
        'getArtifactsFromReport',
        array('sessionKey' => 'xsd:string',
          'group_id' => 'xsd:int',
          'group_artifact_id' => 'xsd:int',
          'report_id' => 'xsd:int',
          'criteria' => 'tns:ArrayOfCriteria',
          'offset' => 'xsd:int',
          'max_rows' => 'xsd:int',
          'sort_criteria' => 'tns:ArrayOfSortCriteria'
        ),
        array('return' => 'tns:ArtifactFromReportResult'),
        $uri,
        $uri . '#getArtifactsFromReport',
        'rpc',
        'encoded',
        'Returns the ArtifactReportResult of the tracker group_artifact_id in the project group_id
     with the report report_id that are matching the given criteria.
     If offset AND max_rows are filled, it returns only max_rows artifacts, skipping the first offset ones.
     The result will be sorted, as defined in the param sort_criteria.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one or if the report_id is not a valid one.'
    );

    $server->register(
        'addArtifact',
        array('sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'group_artifact_id' => 'xsd:int',
        'status_id' => 'xsd:int',
        'close_date' => 'xsd:int',
        'summary' => 'xsd:string',
        'details' => 'xsd:string',
        'severity' => 'xsd:int',
        'extra_fields' => 'tns:ArrayOfArtifactFieldValue'
        ),
        array('return' => 'xsd:int'),
        $uri,
        $uri . '#addArtifact',
        'rpc',
        'encoded',
        'Add an Artifact in the tracker group_artifact_id of the project group_id with the values given by
     status_id, close_date, summary, details, severity and extra_fields for the non-standard fields.
     Returns the Id of the created artifact if the creation succeed.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one, or if the add failed.'
    );

    $server->register(
        'addArtifactWithFieldNames',
        array('sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'group_artifact_id' => 'xsd:int',
        'status_id' => 'xsd:int',
        'close_date' => 'xsd:int',
        'summary' => 'xsd:string',
        'details' => 'xsd:string',
        'severity' => 'xsd:int',
        'extra_fields' => 'tns:ArrayOfArtifactFieldNameValue'
        ),
        array('return' => 'xsd:int'),
        $uri,
        $uri . '#addArtifact',
        'rpc',
        'encoded',
        'Add an Artifact in the tracker tracker_name of the project group_id with the values given by
     status_id, close_date, summary, details, severity and extra_fields for the non-standard fields.
     Returns the Id of the created artifact if the creation succeed.
     Returns a soap fault if the group_id is not a valid one, if the tracker_name is not a valid one, or if the add failed.'
    );

    $server->register(
        'updateArtifact',
        array('sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'group_artifact_id' => 'xsd:int',
        'artifact_id' => 'xsd:int',
        'status_id' => 'xsd:int',
        'close_date' => 'xsd:int',
        'summary' => 'xsd:string',
        'details' => 'xsd:string',
        'severity' => 'xsd:int',
        'extra_fields' => 'tns:ArrayOfArtifactFieldValue'
        ),
        array('return' => 'xsd:int'),
        $uri,
        $uri . '#updateArtifact',
        'rpc',
        'encoded',
        'Update the artifact $artifact_id of the tracker $group_artifact_id in the project group_id with the values given by
     status_id, close_date, summary, details, severity and extra_fields for the non-standard fields.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one,
     if the artifart_id is not a valid one, or if the update failed.'
    );

    $server->register(
        'updateArtifactWithFieldNames',
        array('sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'group_artifact_id' => 'xsd:int',
        'artifact_id' => 'xsd:int',
        'status_id' => 'xsd:int',
        'close_date' => 'xsd:int',
        'summary' => 'xsd:string',
        'details' => 'xsd:string',
        'severity' => 'xsd:int',
        'extra_fields' => 'tns:ArrayOfArtifactFieldNameValue'
        ),
        array('return' => 'xsd:int'),
        $uri,
        $uri . '#updateArtifact',
        'rpc',
        'encoded',
        'Update the artifact $artifact_id of the tracker $tracker_name in the project group_id with the values given by
     status_id, close_date, summary, details, severity and extra_fields for the non-standard fields.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one,
     if the artifart_id is not a valid one, or if the update failed.'
    );

    $server->register(
        'getArtifactFollowups',
        array('sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'group_artifact_id' => 'xsd:int',
        'artifact_id' => 'xsd:int'
        ),
        array('return' => 'tns:ArrayOfArtifactFollowup'),
        $uri,
        $uri . '#getArtifactFollowups',
        'rpc',
        'encoded',
        'Returns the list of follow-ups (ArtifactFollowup) of the artifact artifact_id of the tracker group_artifact_id in the project group_id.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one,
     or if the artifart_id is not a valid one.'
    );

    $server->register(
        'getArtifactCannedResponses',
        array('sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'group_artifact_id' => 'xsd:int'
        ),
        array('return' => 'tns:ArrayOfArtifactCanned'),
        $uri,
        $uri . '#getArtifactCannedResponses',
        'rpc',
        'encoded',
        'Returns the list of canned responses (ArtifactCanned) for the tracker group_artifact_id of the project group_id.
     Returns a soap fault if the group_id is not a valid one or if group_artifact_id is not a valid one.'
    );

    $server->register(
        'getArtifactReports',
        array('sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'group_artifact_id' => 'xsd:int'
        ),
        array('return' => 'tns:ArrayOfArtifactReport'),
        $uri,
        $uri . '#getArtifactReports',
        'rpc',
        'encoded',
        'Returns the list of reports (ArtifactReport) for the tracker group_artifact_id of the project group_id of the current user.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one.'
    );

    $server->register(
        'getArtifactAttachedFiles',
        array('sessionKey' => 'xsd:string',
          'group_id' => 'xsd:int',
          'group_artifact_id' => 'xsd:int',
          'artifact_id' => 'xsd:int'
        ),
        array('return' => 'tns:ArrayOfArtifactFile'),
        $uri,
        $uri . '#getArtifactAttachedFiles',
        'rpc',
        'encoded',
        'Returns the array of attached files (ArtifactFile) attached to the artifact artifact_id in the tracker group_artifact_id of the project group_id.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one,
     or if the artifact_id is not a valid one. NOTE : for performance reasons, the result does not contain the content of the file. Please use getArtifactAttachedFile to get the content of a single file'
    );

    $server->register(
        'getArtifactAttachedFile',
        array('sessionKey' => 'xsd:string',
          'group_id' => 'xsd:int',
          'group_artifact_id' => 'xsd:int',
          'artifact_id' => 'xsd:int',
          'file_id' => 'xsd:int'
        ),
        array('return' => 'tns:ArtifactFile'),
        $uri,
        $uri . '#getArtifactAttachedFile',
        'rpc',
        'encoded',
        'Returns the attached file (ArtifactFile) with the id file_id attached to the artifact artifact_id in the tracker group_artifact_id of the project group_id.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one,
     if the artifact_id is not a valid one, or if the file_id doesnt match with the given artifact_id.'
    );


    $server->register(
        'getArtifactById',
        array('sessionKey' => 'xsd:string',
          'group_id' => 'xsd:int',
          'group_artifact_id' => 'xsd:int',
          'artifact_id' => 'xsd:int'
        ),
        array('return' => 'tns:Artifact'),
        $uri,
        $uri . '#getArtifactById',
        'rpc',
        'encoded',
        'Returns the artifact (Artifact) identified by the id artifact_id in the tracker group_artifact_id of the project group_id.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one,
     or if the artifact_id is not a valid one.'
    );

    $server->register(
        'getArtifactDependencies',
        array('sessionKey' => 'xsd:string',
          'group_id' => 'xsd:int',
          'group_artifact_id' => 'xsd:int',
          'artifact_id' => 'xsd:int'
        ),
        array('return' => 'tns:ArrayOfArtifactDependency'),
        $uri,
        $uri . '#getArtifactDependencies',
        'rpc',
        'encoded',
        'Returns the list of the dependencies (ArtifactDependency) for the artifact artifact_id of the tracker group_artifact_id of the project group_id.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one,
     or if the artifact_id is not a valid one.'
    );

    $server->register(
        'getArtifactInverseDependencies',
        array('sessionKey' => 'xsd:string',
          'group_id' => 'xsd:int',
          'group_artifact_id' => 'xsd:int',
          'artifact_id' => 'xsd:int'
        ),
        array('return' => 'tns:ArrayOfArtifactDependency'),
        $uri,
        $uri . '#getArtifactInverseDependencies',
        'rpc',
        'encoded',
        'Returns the list of the dependencies (ArtifactDependency) that other artifact can have with the artifact artifact_id of the tracker group_artifact_id of the project group_id.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one,
     or if the artifact_id is not a valid one.'
    );

    $server->register(
        'addArtifactAttachedFile',
        array('sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'group_artifact_id' => 'xsd:int',
        'artifact_id' => 'xsd:int',
        'encoded_data' => 'xsd:string',
        'description' => 'xsd:string',
        'filename' => 'xsd:string',
        'filetype' => 'xsd:string'
        ),
        array('return' => 'xsd:int'),
        $uri,
        $uri . '#addArtifactAttachedFile',
        'rpc',
        'encoded',
        'Add an attached file to the artifact artifact_id of the tracker group_artifact_id of the project group_id.
     The attached file is described by the raw encoded_data (encoded in base64), the description of the file,
     the name of the file and it type (the mimi-type -- plain/text, image/jpeg, etc ...).
     Returns the ID of the attached file if the attachment succeed.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one,
     or if the artifact_id is not a valid one, or if the attachment failed.'
    );

    $server->register(
        'deleteArtifactAttachedFile',
        array('sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'group_artifact_id' => 'xsd:int',
        'artifact_id' => 'xsd:int',
        'file_id' => 'xsd:int'
        ),
        array('return' => 'xsd:int'),
        $uri,
        $uri . '#deleteArtifactAttachedFile',
        'rpc',
        'encoded',
        'Delete the attached file file_id from the artifact artifact_id of the tracker group_artifact_id of the project group_id.
     Returns the ID of the deleted file if the deletion succeed.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one,
     if the artifact_id is not a valid one, if the file_id is not a valid one or if the deletion failed.'
    );

    $server->register(
        'addArtifactDependencies',
        array('sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'group_artifact_id' => 'xsd:int',
        'artifact_id' => 'xsd:int',
        'is_dependent_on_artifact_ids' => 'xsd:string'
        ),
        array('return' => 'xsd:boolean'),
        $uri,
        $uri . '#addArtifactDependencies',
        'rpc',
        'encoded',
        'Add the list of dependencies is_dependent_on_artifact_id to the list of dependencies of the artifact artifact_id
     of the tracker group_artifact_id of the project group_id.
     Returns true if the add succeed.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one,
     if the artifact_id is not a valid one, or if the add failed.'
    );

    $server->register(
        'deleteArtifactDependency',
        array('sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'group_artifact_id' => 'xsd:int',
        'artifact_id' => 'xsd:int',
        'dependent_on_artifact_id' => 'xsd:int'
        ),
        array('return' => 'xsd:int'),
        $uri,
        $uri . '#deleteArtifactDependency',
        'rpc',
        'encoded',
        'Delete the dependency between the artifact dependent_on_artifact_id and the artifact artifact_id of the tracker group_artifact_id of the project group_id.
     Returns the ID of the deleted dependency if the deletion succeed.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one,
     if the artifact_id is not a valid one, if the dependent_on_artifact_id is not a valid artifact id, or if the deletion failed.'
    );

    $server->register(
        'addArtifactFollowup',
        array('sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'group_artifact_id' => 'xsd:int',
        'artifact_id' => 'xsd:int',
        'body' => 'xsd:string',
        'comment_type_id' => 'xsd:int',
        'format' => 'xsd:int'
        ),
        array('return' => 'xsd:boolean'),
        $uri,
        $uri . '#addArtifactFollowup',
        'rpc',
        'encoded',
        'Add a follow-up body to the artifact artifact_id of the tracker group_artifact_id of the project group_id,
     with optionals comment type and canned response. If canned response is set, it will replace the body.
     Returns nothing if the add succeed.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one,
     if the artifact_id is not a valid one, or if the add failed.'
    );

    $server->register(
        'updateArtifactFollowUp',
        array('sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'group_artifact_id' => 'xsd:int',
        'artifact_id' => 'xsd:int',
        'artifact_history_id' => 'xsd:int',
        'comment' => 'xsd:string',
        ),
        array('return' => 'xsd:boolean'),
        $uri,
        $uri . '#updateArtifact',
        'rpc',
        'encoded',
        'Update the follow_up artifact_history_id of the tracker $group_artifact_id in the project group_id for the artifact $artifact_id with the new comment $comment.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one,
     if the artifart_id is not a valid one, if the artifact_history_id is not a valid one, or if the update failed.'
    );

    $server->register(
        'deleteArtifactFollowUp',
        array('sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'group_artifact_id' => 'xsd:int',
        'artifact_id' => 'xsd:int',
        'artifact_history_id' => 'xsd:int'
        ),
        array('return' => 'xsd:boolean'),
        $uri,
        $uri . '#deleteArtifact',
        'rpc',
        'encoded',
        'Delete the follow_up artifact_history_id of the tracker $group_artifact_id in the project group_id for the artifact $artifact_id.
     Returns a soap fault if the group_id is not a valid one, if the group_artifact_id is not a valid one,
     if the artifart_id is not a valid one, if the artifact_history_id is not a valid one, or if the deletion failed.'
    );

    $server->register(
        'existArtifactSummary',
        array('sessionKey' => 'xsd:string',
        'group_artifact_id' => 'xsd:int',
        'summary' => 'xsd:string'
        ),
        array('return' => 'xsd:int'),
        $uri,
        $uri . '#existArtifactSummary',
        'rpc',
        'encoded',
        'Check if there is an artifact in the tracker group_artifact_id that already have the summary summary (the summary is unique inside a given tracker).
     Returns the ID of the artifact containing the same summary in the tracker, or -1 if the summary does not exist in this tracker.'
    );

    $server->register(
        'getArtifactCCList',
        array('sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'group_artifact_id' => 'xsd:int',
        'artifact_id' => 'xsd:int'
        ),
        array('return' => 'tns:ArrayOfArtifactCC'),
        $uri,
        $uri . '#getArtifactCCList',
        'rpc',
        'encoded',
        'Get the list of emails or logins in the CC list of a specific artifact'
    );

    $server->register(
        'addArtifactCC',
        array('sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'group_artifact_id' => 'xsd:int',
        'artifact_id' => 'xsd:int',
        'cc_list' => 'xsd:string',
        'cc_comment' => 'xsd:string'
        ),
        array('return' => 'xsd:boolean'),
        $uri,
        $uri . '#addArtifactCC',
        'rpc',
        'encoded',
        'Add a list of emails or logins in the CC list of a specific artifact, with an optional comment'
    );

    $server->register(
        'deleteArtifactCC',
        array('sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'group_artifact_id' => 'xsd:int',
        'artifact_id' => 'xsd:int',
        'artifact_cc_id' => 'xsd:int'
        ),
        array('return' => 'xsd:boolean'),
        $uri,
        $uri . '#deleteArtifactCC',
        'rpc',
        'encoded',
        'Delete a CC to the CC list of the artifact'
    );

    $server->register(
        'getArtifactHistory',
        array('sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'group_artifact_id' => 'xsd:int',
        'artifact_id' => 'xsd:int'
        ),
        array('return' => 'tns:ArrayOfArtifactHistory'),
        $uri,
        $uri . '#getArtifactHistory',
        'rpc',
        'encoded',
        'Get the history of the artifact (the history of the fields values)'
    );
} else {


/**
 * getTrackerList - returns an array of TrackerDesc (short description of trackers) that belongs to the project identified by group_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the list of trackers
 * @return array the array of SOAPTrackerDesc that belongs to the project identified by $group_id, or a soap fault if group_id does not match with a valid project.
 */
    function getTrackerList($sessionKey, $group_id)
    {
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $group = $pm->getGroupByIdForSoap($group_id, 'getTrackerList');
            } catch (SoapFault $e) {
                return $e;
            }

            $project = new Project($group_id);
            if (!$project->usesService('tracker')) {
                return new SoapFault(GET_SERVICE_FAULT, 'Tracker service is not used for this project.', 'getTrackerList');
            }

            $atf = new ArtifactTypeFactory($group);
            if (!$atf || !is_object($atf)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FACTORY_FAULT, 'Could Not Get ArtifactTypeFactory', 'getTrackerList');
            } elseif ($atf->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FACTORY_FAULT, $atf->getErrorMessage(), 'getTrackerList');
            }
            // The function getArtifactTypes returns only the trackers the user is allowed to view
            return trackerlist_to_soap($atf->getArtifactTypes());
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'getTrackerList');
        }
    }

/**
 * trackerlist_to_soap : return the soap ArrayOfTrackerDesc structure giving an array of PHP ArtifactType Object.
 * @access private
 *
 * WARNING : We check the permissions here : only the readable trackers are returned.
 *
 * @param array of Object{ArtifactType} $at_arr the array of artifactTypes to convert.
 * @return array the SOAPArrayOfTrackerDesc corresponding to the array of ArtifactTypes Object (light)
 */
    function trackerlist_to_soap($at_arr)
    {
        global $ath;
        $user_id = UserManager::instance()->getCurrentUser()->getId();
        $return = array();
        for ($i = 0; $i < count($at_arr); $i++) {
            if ($at_arr[$i]->isError()) {
                //skip if error
            } else {
                $ath = new ArtifactType($at_arr[$i]->getGroup(), $at_arr[$i]->getID());
                if (!$ath || !is_object($ath)) {
                    return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'ArtifactType could not be created', 'getArtifactTypes');
                }
                if ($ath->isError()) {
                    return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $ath->getErrorMessage(), 'getArtifactTypes');
                }
                // Check if this tracker is valid (not deleted)
                if (!$ath->isValid()) {
                    return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'This tracker is no longer valid.', 'getArtifactTypes');
                }

                // Check if the user can view this tracker
                if ($ath->userCanView($user_id)) {
                    // get the reports description (light desc of reports)
                    $report_fact = new ArtifactReportFactory();
                    if (!$report_fact || !is_object($report_fact)) {
                        return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Could Not Get ArtifactReportFactory', 'getArtifactTypes');
                    }
                    $reports_desc = artifactreportsdesc_to_soap($report_fact->getReports($at_arr[$i]->data_array['group_artifact_id'], $user_id));

                    $sql = "SELECT COALESCE(sum(af.filesize) / 1024,NULL,0) as total_file_size"
                        . " FROM artifact_file af, artifact a, artifact_group_list agl"
                        . " WHERE (af.artifact_id = a.artifact_id)"
                        . " AND (a.group_artifact_id = agl.group_artifact_id)"
                        . " AND (agl.group_artifact_id =" . db_ei($at_arr[$i]->getID()) . ")";
                    $result = db_query($sql);
                    $return[] = array(
                    'group_artifact_id' => $at_arr[$i]->data_array['group_artifact_id'],
                    'group_id' => $at_arr[$i]->data_array['group_id'],
                    'name' => SimpleSanitizer::unsanitize($at_arr[$i]->data_array['name']),
                    'description' => SimpleSanitizer::unsanitize($at_arr[$i]->data_array['description']),
                    'item_name' => $at_arr[$i]->data_array['item_name'],
                    'open_count' => ($at_arr[$i]->userHasFullAccess() ? $at_arr[$i]->getOpenCount() : -1),
                    'total_count' => ($at_arr[$i]->userHasFullAccess() ? $at_arr[$i]->getTotalCount() : -1),
                    'total_file_size' => db_result($result, 0, 0),
                    'reports_desc' => $reports_desc
                    );
                }
            }
        }
        return $return;
    }

    function artifactreportsdesc_to_soap($artifactreportsdesc)
    {
        $return = array();
        if (is_array($artifactreportsdesc) && count($artifactreportsdesc)) {
            foreach ($artifactreportsdesc as $arid => $artifactreportdesc) {
                if ($artifactreportdesc->isError()) {
                    //skip if error
                } else {
                    $return[] = array(
                    'report_id'          => $artifactreportdesc->getID(),
                    'group_artifact_id'  => $artifactreportdesc->getArtifactTypeID(),
                    'name'               => $artifactreportdesc->getName(),
                    'description'        => $artifactreportdesc->getDescription(),
                    'scope'              => $artifactreportdesc->getScope()
                    );
                }
            }
        }
        return $return;
    }

/**
 * getArtifactType - returns the ArtifactType (tracker) with the ID $group_artifact_id that belongs to the project identified by group_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the tracker structure
 * @param int $group_artifact_id the ID of the tracker we want to retrieve the structure
 * @return the SOAPArtifactType of the tracker $group_artifact_id that belongs to the project identified by $group_id, or a soap fault if group_id does not match with a valid project or if $group_artifact_id doesn't exist is not a tracker of the project.
 */
    function getArtifactType($sessionKey, $group_id, $group_artifact_id)
    {
        if (session_continue($sessionKey)) {
            $user_id = UserManager::instance()->getCurrentUser()->getId();
            try {
                $pm = ProjectManager::instance();
                $group = $pm->getGroupByIdForSoap($group_id, 'getArtifactType');
            } catch (SoapFault $e) {
                return $e;
            }

            $at = new ArtifactType($group, $group_artifact_id);
            if (!$at || !is_object($at)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FACTORY_FAULT, 'Could Not Get ArtifactType', 'getArtifactType');
            } elseif ($at->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FACTORY_FAULT, $at->getErrorMessage(), 'getArtifactType');
            }

            if ($at->userCanView($user_id)) {
               // The function getArtifactTypes returns only the trackers the user is allowed to view
                $soap_art = artifacttype_to_soap($at);
                if (empty($soap_art)) {
                    return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Permission denied.', 'getArtifactType');
                }
                return $soap_art;
            } else {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Permission denied.', 'getArtifactType');
            }
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'getArtifactType');
        }
    }

/**
 * getArtifactTypes - returns an array of ArtifactTypes (trackers) that belongs to the project identified by group_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the array of trackers
 * @return array the array of SOAPArtifactType that belongs to the project identified by $group_id, or a soap fault if group_id does not match with a valid project.
 */
    function getArtifactTypes($sessionKey, $group_id)
    {
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $group = $pm->getGroupByIdForSoap($group_id, 'getArtifactTypes');
            } catch (SoapFault $e) {
                return $e;
            }

            $atf = new ArtifactTypeFactory($group);
            if (!$atf || !is_object($atf)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FACTORY_FAULT, 'Could Not Get ArtifactTypeFactory', 'getArtifactTypes');
            } elseif ($atf->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FACTORY_FAULT, $atf->getErrorMessage(), 'getArtifactTypes');
            }
            // The function getArtifactTypes returns only the trackers the user is allowed to view
            return artifacttypes_to_soap($atf->getArtifactTypes());
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'getArtifactTypes');
        }
    }

/**
 * artifacttype_to_soap : return the soap ArtifactType structure giving an PHP ArtifactType Object.
 * @access private
 *
 * WARNING : We check the permissions here : only the readable trackers and the readable fields are returned.
 *
 * @param Object{ArtifactType} $at the artifactType to convert.
 * @return the SOAPArtifactType corresponding to the ArtifactType Object
 */
    function artifacttype_to_soap($at)
    {
        global $ath;
        $user_id = UserManager::instance()->getCurrentUser()->getId();
        $return = array();

        // number of opend artifact are not part of ArtifactType, so we have to get it with ArtifactTypeFactory (could need some refactoring maybe)
        $atf = new ArtifactTypeFactory($at->getGroup());
        $arr_count = $atf->getStatusIdCount($at->getID());
        if ($arr_count) {
            $open_count = array_key_exists('open_count', $arr_count) ? $arr_count['open_count'] : -1;
            $count = array_key_exists('count', $arr_count) ? $arr_count['count'] : -1;
        } else {
            $open_count = -1;
            $count = -1;
        }

        $field_sets = array();
        $ath = new ArtifactType($at->getGroup(), $at->getID());
        if (!$ath || !is_object($ath)) {
            return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'ArtifactType could not be created', 'getArtifactTypes');
        }
        if ($ath->isError()) {
            return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $ath->getErrorMessage(), 'getArtifactTypes');
        }
        // Check if this tracker is valid (not deleted)
        if (!$ath->isValid()) {
            return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'This tracker is no longer valid.', 'getArtifactTypes');
        }
        // Check if the user can view this tracker
        if ($ath->userCanView($user_id)) {
            $art_fieldset_fact = new ArtifactFieldSetFactory($at);
            if (!$art_fieldset_fact || !is_object($art_fieldset_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldSetFactory', 'getFieldSets');
            } elseif ($art_fieldset_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_fieldset_fact->getErrorMessage(), 'getFieldSets');
            }
            $result_fieldsets = $art_fieldset_fact->getAllFieldSetsContainingUsedFields();

            foreach ($result_fieldsets as $fieldset_id => $result_fieldset) {
                $fields = array();
                $fields_in_fieldset = $result_fieldset->getAllUsedFields();
                $group_id = $at->Group->getID();
                $group_artifact_id = $at->getID();
                foreach ($fields_in_fieldset as $key => $field) {
                    if ($field->userCanRead($group_id, $group_artifact_id, $user_id)) {
                        $availablevalues = array();
                        $result = $field->getFieldPredefinedValues($at->getID(), false, false, false, false);
                        $rows = db_numrows($result);
                        $cols = db_numfields($result);
                        for ($j = 0; $j < $rows; $j++) {
                            $field_status = ($cols > 2) ? db_result($result, $j, 6) : '';
                            // we don't send hidden values (status == 'H')
                            if ($field_status != 'H') {
                                $availablevalues[] = array (
                                'field_id' => $field->getID(),
                                'group_artifact_id' => $at->getID(),
                                'value_id' => db_result($result, $j, 0),
                                'value' => SimpleSanitizer::unsanitize(db_result($result, $j, 1)),
                                'description' => SimpleSanitizer::unsanitize(($cols > 2) ? db_result($result, $j, 4) : ''),
                                'order_id' => ($cols > 2) ? db_result($result, $j, 5) : 0,
                                'status' => $field_status
                                );
                            }
                        }
                        // For bound-values select boxes, we add the none value.
                        if (($field->isMultiSelectBox() || $field->isSelectBox()) && ($field->isBound())) {
                            $availablevalues[] = array (
                            'field_id' => $field->getID(),
                            'group_artifact_id' => $at->getID(),
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
                        'group_artifact_id' => $at->getID(),
                        'field_set_id' => $field->getFieldSetID(),
                        'field_name' => SimpleSanitizer::unsanitize($field->getName()),
                        'data_type' => $field->getDataType(),
                        'display_type' => $field->getDisplayType(),
                        'display_size' => $field->getDisplaySize(),
                        'label'    => SimpleSanitizer::unsanitize($field->getLabel()),
                        'description' => SimpleSanitizer::unsanitize($field->getDescription()),
                        'scope' => $field->getScope(),
                        'required' => $field->getRequired(),
                        'empty_ok' => $field->getEmptyOk(),
                        'keep_history' => $field->getKeepHistory(),
                        'special' => $field->getSpecial(),
                        'value_function' => implode(",", $field->getValueFunction()),
                        'available_values' => $availablevalues,
                        'default_value' => $defaultvalue,
                        'user_can_submit' => $field->userCanSubmit($group_id, $group_artifact_id, $user_id),
                        'user_can_read' => $field->userCanRead($group_id, $group_artifact_id, $user_id),
                        'user_can_update' => $field->userCanUpdate($group_id, $group_artifact_id, $user_id),
                        'is_standard_field' => $field->isStandardField()
                        );
                    }
                }
                $field_sets[] = array(
                'field_set_id' => $result_fieldset->getID(),
                'group_artifact_id' => $result_fieldset->getArtifactTypeID(),
                'name' => SimpleSanitizer::unsanitize($result_fieldset->getName()),
                'label' => SimpleSanitizer::unsanitize($result_fieldset->getLabel()),
                'description' => SimpleSanitizer::unsanitize($result_fieldset->getDescription()),
                'description_text' => SimpleSanitizer::unsanitize($result_fieldset->getDescriptionText()),
                'rank' => $result_fieldset->getRank(),
                'fields' => $fields
                );
            }

            // We add the field dependencies
            $field_dependencies = artifactrules_to_soap($at);

            $sql = "SELECT COALESCE(sum(af.filesize) / 1024,NULL,0) as total_file_size"
                . " FROM artifact_file af, artifact a, artifact_group_list agl"
                . " WHERE (af.artifact_id = a.artifact_id)"
                . " AND (a.group_artifact_id = agl.group_artifact_id)"
                . " AND (agl.group_artifact_id =" . db_ei($at->getID()) . ")";
            $result = db_query($sql);
            $return = array(
            'group_artifact_id' => $at->data_array['group_artifact_id'],
            'group_id' => $at->data_array['group_id'],
            'name' => SimpleSanitizer::unsanitize($at->data_array['name']),
            'description' => SimpleSanitizer::unsanitize($at->data_array['description']),
            'item_name' => $at->data_array['item_name'],
            'open_count' => ($at->userHasFullAccess() ? $open_count : -1),
            'total_count' => ($at->userHasFullAccess() ? $count : -1),
            'total_file_size' => db_result($result, 0, 0),
            'field_sets' => $field_sets,
            'field_dependencies' => $field_dependencies
            );
        }
        return $return;
    }

    function artifacttypes_to_soap($at_arr)
    {
        $return = array();
        for ($i = 0; $i < count($at_arr); $i++) {
            if ($at_arr[$i]->isError()) {
                //skip if error
            } else {
                $return[] = artifacttype_to_soap($at_arr[$i]);
            }
        }
        return $return;
    }

    function artifactrule_to_soap($rule)
    {
        $return = array();
        $return['rule_id'] = $rule->id;
        $return['group_artifact_id'] = $rule->group_artifact_id;
        $return['source_field_id'] = $rule->source_field;
        $return['source_value_id'] = $rule->source_value;
        $return['target_field_id'] = $rule->target_field;
        $return['target_value_id'] = $rule->target_value;
        return $return;
    }

    function artifactrules_to_soap($artifact_type)
    {
        $return = array();
        $arm = new ArtifactRulesManager();
        $rules = $arm->getAllRulesByArtifactTypeWithOrder($artifact_type->getID());
        if ($rules && count($rules) > 0) {
            foreach ($rules as $key => $rule) {
                $return[] = artifactrule_to_soap($rule);
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
 * @param int $group_artifact_id the ID of the tracker we want to retrieve the array of artifacts
 * @param array $criteria the criteria that the set of artifact must match
 * @param int $offset number of artifact skipped. Used in association with $max_rows to limit the number of returned artifact.
 * @param int $max_rows the maximum number of artifacts returned
 * @return the SOAPArtifactQueryResult that match the criteria $criteria and belong to the project $group_id and the tracker $group_artifact_id,
 *          or a soap fault if group_id does not match with a valid project, or if group_artifact_id does not match with a valid tracker.
 */
    function getArtifacts($sessionKey, $group_id, $group_artifact_id, $criteria, $offset, $max_rows)
    {
        global $art_field_fact;
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'getArtifacts');
            } catch (SoapFault $e) {
                return $e;
            }
            $at = new ArtifactType($grp, $group_artifact_id);
            if (!$at || !is_object($at)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Could Not Get ArtifactType', 'getArtifacts');
            } elseif (! $at->userCanView()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Permission Denied: You are not granted sufficient permission to perform this operation.', 'getArtifacts');
            } elseif ($at->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $at->getErrorMessage(), 'getArtifacts');
            }
            $art_field_fact = new ArtifactFieldFactory($at);
            if (!$art_field_fact || !is_object($art_field_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldFactory', 'getArtifactTypes');
            } elseif ($art_field_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_field_fact->getErrorMessage(), 'getArtifactTypes');
            }
            $af = new ArtifactFactory($at);
            if (!$af || !is_object($af)) {
                return new SoapFault(GET_ARTIFACT_FACTORY_FAULT, 'Could Not Get ArtifactFactory', 'getArtifacts');
            } elseif ($af->isError()) {
                return new SoapFault(GET_ARTIFACT_FACTORY_FAULT, $af->getErrorMessage(), 'getArtifacts');
            }
            $total_artifacts = 0;
            // the function getArtifacts returns only the artifacts the user is allowed to view
            $artifacts = $af->getArtifacts($criteria, $offset, $max_rows, $total_artifacts);
            return artifact_query_result_to_soap($artifacts, $total_artifacts);
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session ', 'getArtifactTypes');
        }
    }

/**
 * getArtifactsFromReport - returns an ArtifactReportResult that belongs to the project $group_id, to the tracker $group_artifact_id,
 *                using the report $report_id and that match the criteria $criteria. If $offset and $max_rows are filled,
 *                the number of returned artifacts will not exceed $max_rows, beginning at $offset.
 *
 * !!!!!!!!!!!!!!!
 * !!! Warning : If $max_rows is not filled, $offset is not taken into account. !!!
 * !!!!!!!!!!!!!!!
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the array of artifacts
 * @param int $group_artifact_id the ID of the tracker we want to retrieve the array of artifacts
 * @param int $report_id the ID of the report that will be use to build the result
 * @param array $criteria the criteria that the set of artifact must match
 * @param int $offset number of artifact skipped. Used in association with $max_rows to limit the number of returned artifact.
 * @param int $max_rows the maximum number of artifacts returned
 * @param array $sort_criteria the sort criteria to sort the result
 * @return the SOAPArtifactFromReportResult that match the criteria $criteria and belong to the project $group_id and the tracker $group_artifact_id,
 *          or a soap fault if group_id does not match with a valid project, or if group_artifact_id does not match with a valid tracker.
 */
    function getArtifactsFromReport($sessionKey, $group_id, $group_artifact_id, $report_id, $criteria, $offset, $max_rows, $sort_criteria)
    {
        global $art_field_fact;
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'getArtifactsFromReport');
            } catch (SoapFault $e) {
                return $e;
            }
            $at = new ArtifactType($grp, $group_artifact_id);
            if (!$at || !is_object($at)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Could Not Get ArtifactType', 'getArtifactsFromReport');
            } elseif (! $at->userCanView()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Permission Denied: You are not granted sufficient permission to perform this operation.', 'getArtifactsFromReport');
            } elseif ($at->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $at->getErrorMessage(), 'getArtifactsFromReport');
            }
            $art_field_fact = new ArtifactFieldFactory($at);
            if (!$art_field_fact || !is_object($art_field_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldFactory', 'getArtifactsFromReport');
            } elseif ($art_field_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_field_fact->getErrorMessage(), 'getArtifactsFromReport');
            }
            $af = new ArtifactFactory($at);
            if (!$af || !is_object($af)) {
                return new SoapFault(GET_ARTIFACT_FACTORY_FAULT, 'Could Not Get ArtifactFactory', 'getArtifactsFromReport');
            } elseif ($af->isError()) {
                return new SoapFault(GET_ARTIFACT_FACTORY_FAULT, $af->getErrorMessage(), 'getArtifactsFromReport');
            }

            $ar = new ArtifactReport($report_id, $group_artifact_id);
            if (!$ar || !is_object($ar)) {
                return new SoapFault(GET_ARTIFACT_REPORT_FAULT, 'Could Not Get ArtifactFactory', 'getArtifactsFromReport');
            } elseif ($ar->isError()) {
                return new SoapFault(GET_ARTIFACT_REPORT_FAULT, $ar->getErrorMessage(), 'getArtifactsFromReport');
            }

            $total_artifacts = 0;
            // the function getArtifacts returns only the artifacts the user is allowed to view
            $artifacts = $af->getArtifactsFromReport($group_id, $group_artifact_id, $report_id, $criteria, $offset, $max_rows, $sort_criteria, $total_artifacts);
            if ($af->isError()) {
                return new SoapFault(GET_ARTIFACT_REPORT_FAULT, $af->getErrorMessage(), 'getArtifactsFromReport');
            }
            return artifact_report_result_to_soap($artifacts, $total_artifacts);
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session ', 'getArtifactsFromReport');
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
    function getArtifactById($sessionKey, $group_id, $group_artifact_id, $artifact_id)
    {
        global $art_field_fact, $ath;
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'getArtifactById');
            } catch (SoapFault $e) {
                return $e;
            }

            $ath = new ArtifactType($grp, $group_artifact_id);
            if (!$ath || !is_object($ath)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'ArtifactType could not be created', 'getArtifactById');
            }
            if ($ath->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $ath->getErrorMessage(), 'getArtifactById');
            }
            // Check if this tracker is valid (not deleted)
            if (!$ath->isValid()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'This tracker is no longer valid.', 'getArtifactById');
            }

            $art_field_fact = new ArtifactFieldFactory($ath);
            if (!$art_field_fact || !is_object($art_field_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldFactory', 'getArtifactById');
            } elseif ($art_field_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_field_fact->getErrorMessage(), 'getArtifactById');
            }
            $a = new Artifact($ath, $artifact_id);
            if (!$a || !is_object($a)) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Could Not Get Artifact', 'getArtifactById');
            } elseif ($a->isError()) {
                return new SoapFault(GET_ARTIFACT_FAULT, $a->getErrorMessage(), 'getArtifactById');
            }
            return artifact_to_soap($a);
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'getArtifactById');
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
    function artifact_to_soap($artifact)
    {
        global $art_field_fact;

        $return = array();
        $user_id = UserManager::instance()->getCurrentUser()->getId();
        // We check if the user can view this artifact
        if ($artifact->userCanView($user_id)) {
            $extrafieldvalues = array();
            $extrafielddata   = $artifact->getExtraFieldData();
            if (is_array($extrafielddata) && count($extrafielddata) > 0) {
                foreach ($extrafielddata as $field_id => $value) {
                    $field = $art_field_fact->getFieldFromId($field_id);
                    if ($field->userCanRead($artifact->ArtifactType->Group->getID(), $artifact->ArtifactType->getID(), $user_id)) {
                        $extrafieldvalues[] = array (
                        'field_id'    => $field_id,
                        'artifact_id' => $artifact->getID(),
                        'field_value' => html_entity_decode($value)  //util_unconvert_htmlspecialchars ?
                        );
                    }
                }
            }

            // Check Permissions on standard fields (status_id, submitted_by, open_date, close_date, last_update_date, summary, details, severity)
            // artifact_id
            $field_artifact_id = $art_field_fact->getFieldFromName('artifact_id');
            if ($field_artifact_id && $field_artifact_id->userCanRead($artifact->ArtifactType->Group->getID(), $artifact->ArtifactType->getID(), $user_id)) {
                $return['artifact_id'] = $artifact->getID();
            }
            // group_artifact_id
            $return['group_artifact_id'] = $artifact->ArtifactType->getID();
            // status_id
            $field_status_id = $art_field_fact->getFieldFromName('status_id');
            if ($field_status_id && $field_status_id->userCanRead($artifact->ArtifactType->Group->getID(), $artifact->ArtifactType->getID(), $user_id)) {
                $return['status_id'] = $artifact->getStatusID();
            }
            // submitted_by
            $field_submitted_by = $art_field_fact->getFieldFromName('submitted_by');
            if ($field_submitted_by && $field_submitted_by->userCanRead($artifact->ArtifactType->Group->getID(), $artifact->ArtifactType->getID(), $user_id)) {
                $return['submitted_by'] = $artifact->getSubmittedBy();
            }
            // open_date
            $field_open_date = $art_field_fact->getFieldFromName('open_date');
            if ($field_open_date && $field_open_date->userCanRead($artifact->ArtifactType->Group->getID(), $artifact->ArtifactType->getID(), $user_id)) {
                $return['open_date'] = $artifact->getOpenDate();
            }
            // close_date
            $field_close_date = $art_field_fact->getFieldFromName('close_date');
            if ($field_close_date && $field_close_date->userCanRead($artifact->ArtifactType->Group->getID(), $artifact->ArtifactType->getID(), $user_id)) {
                $return['close_date'] = $artifact->getCloseDate();
            }
            // last_update_date
            $field_last_update_date = $art_field_fact->getFieldFromName('last_update_date');
            if ($field_last_update_date && $field_last_update_date->userCanRead($artifact->ArtifactType->Group->getID(), $artifact->ArtifactType->getID(), $user_id)) {
                $return['last_update_date'] = $artifact->getLastUpdateDate();
            }
            // summary
            $field_summary = $art_field_fact->getFieldFromName('summary');
            if ($field_summary && $field_summary->userCanRead($artifact->ArtifactType->Group->getID(), $artifact->ArtifactType->getID(), $user_id)) {
                $return['summary'] = util_unconvert_htmlspecialchars($artifact->getSummary());
            }
            // details
            $field_details = $art_field_fact->getFieldFromName('details');
            if ($field_details && $field_details->userCanRead($artifact->ArtifactType->Group->getID(), $artifact->ArtifactType->getID(), $user_id)) {
                $return['details'] = util_unconvert_htmlspecialchars($artifact->getDetails());
            }
            // severity
            $field_severity = $art_field_fact->getFieldFromName('severity');
            if ($field_severity && $field_severity->userCanRead($artifact->ArtifactType->Group->getID(), $artifact->ArtifactType->getID(), $user_id)) {
                $return['severity'] = $artifact->getSeverity();
            }
            $return['extra_fields'] = $extrafieldvalues;
        }
        return $return;
    }

    function artifacts_to_soap($at_arr)
    {
        $return = array();
        foreach ($at_arr as $atid => $artifact) {
            $return[] = artifact_to_soap($artifact);
        }
        return $return;
    }

    function artifact_query_result_to_soap($artifacts, $total_artifacts_number)
    {
        $return = array();
        $return['total_artifacts_number'] = $total_artifacts_number;
        if ($total_artifacts_number == 0 && $artifacts == false) {
            $return['artifacts'] = null;
        } else {
            $return['artifacts'] = artifacts_to_soap($artifacts);
        }
        return $return;
    }

/**
 * artifact_report_to_soap : return the soap artifactreport structure giving a PHP Artifact Object.
 * @access private
 *
 * WARNING : We check the permissions here : only the readable fields are returned.
 *
 * @param Object{Artifact} $artifact the artifact to convert.
 * @return array the SOAPArtifactReport corresponding to the Artifact Object
 */
    function artifact_report_to_soap($artifact)
    {
        global $art_field_fact;

        $return = array();
        $return_fields = array();

        $return['artifact_id'] = $artifact['id'];
        $return['severity'] = $artifact['severity_id'];

        // we assume that the first field is 'severity_id'
        $arr_keys = array_keys($artifact);
        if ($arr_keys[0] == 'severity_id') {
            // we remove the severity_id field (only used to color the line) -- if severity is used in the report, the field name is 'severity'
            $severity_field = array_shift($artifact);
        }
        // we assume that now the first field is 'id'
        $arr_keys = array_keys($artifact);
        if ($arr_keys[0] == 'id') {
            // we remove the id field (only used to identify the artifact) -- if artifact_id is used in the report, the field name is 'artifact_id'
            $severity_field = array_shift($artifact);
        }

        foreach ($artifact as $field_name => $field_value) {
            $return_fields[] = array('field_name' => $field_name, 'field_value' => $field_value);
        }
        $return['fields'] = $return_fields;

        return $return;
    }

    function artifacts_report_to_soap($at_arr)
    {
        $return = array();
        foreach ($at_arr as $atid => $artifact) {
            $return[] = artifact_report_to_soap($artifact);
        }
        return $return;
    }

    function artifact_report_result_to_soap($artifacts, $total_artifacts_number)
    {
        $return = array();
        $return['total_artifacts_number'] = $total_artifacts_number;
        if ($total_artifacts_number == 0 && $artifacts == false) {
            $return['artifacts'] = null;
        } else {
            $return['artifacts'] = artifacts_report_to_soap($artifacts);
        }
        return $return;
    }

    function setArtifactData($status_id, $close_date, $summary, $details, $severity, $extra_fields)
    {
        global $art_field_fact;

        $data = array();
        // set standard fields data
        if (isset($status_id)) {
            $data['status_id']    =  $status_id;
        }
        if (isset($close_date) && $close_date != 0) {
            $data['close_date']   =  date("Y-m-d", $close_date); // Dates are internally stored in timestamp, but for update and create functions, date must be Y-m-d
        }
        if (isset($summary)) {
            $data['summary']      =  $summary;
        }
        if (isset($details)) {
            $data['details']      =  $details;
        }
        if (isset($severity) && $severity != 0) {
            $data['severity']     =  $severity;
        }

        // set extra fields data
        if (is_array($extra_fields) && count($extra_fields) > 0) {
            foreach ($extra_fields as $e => $extra_field) {
                if (is_object($extra_field)) {
                    $extra_field = objectToArray($extra_field);
                }

                $field = $art_field_fact->getFieldFromId($extra_field['field_id']);
                if ($field->isStandardField()) {
                    continue;
                } else {
                    if ($field->isMultiSelectBox()) {
                        $value = explode(",", $extra_field['field_value']);
                        $data[$field->getName()] = $value;
                    } elseif ($field->isDateField()) {
                        // Dates are internally stored in timestamp, but for update and create functions, date must be Y-m-d
                        $value = date("Y-m-d", $extra_field['field_value']);
                        $data[$field->getName()] = $value;
                    } else {
                        $data[$field->getName()] = $extra_field['field_value'];
                    }
                }
            }
        }
        return $data;
    }

/**
 * addArtifact - add an artifact in tracker $group_artifact_id of the project $group_id with given valuess
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to add the artifact
 * @param int $group_artifact_id the ID of the tracker we want to add the artifact
 * @param int $status_id the ID of the status of the artifact
 * @param string $close_date the close date of the artifact. The format must be YYYY-MM-DD
 * @param string $summary the summary of the artifact
 * @param string $details the details (original submission) of the artifact
 * @param int $severity the severity of the artifact
 * @param array $extra_fields the extra_fields of the artifact (non standard fields)
 * @return int the ID of the new created artifact,
 *              or a soap fault if :
 *              - group_id does not match with a valid project,
 *              - group_artifact_id does not match with a valid tracker,
 *              - the user does not have the permissions to submit an artifact
 *              - the given values are breaking a field dependency rule
 *              - the artifact creation failed.
 */
    function addArtifact($sessionKey, $group_id, $group_artifact_id, $status_id, $close_date, $summary, $details, $severity, $extra_fields)
    {
        global $art_field_fact, $ath;

        if (session_continue($sessionKey)) {
            $user_id = UserManager::instance()->getCurrentUser()->getId();
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'addArtifact');
            } catch (SoapFault $e) {
                return $e;
            }

            $ath = new ArtifactType($grp, $group_artifact_id);
            if (!$ath || !is_object($ath)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'ArtifactType could not be created', 'addArtifact');
            }
            if ($ath->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $ath->getErrorMessage(), 'addArtifact');
            }
            // Check if this tracker is valid (not deleted)
            if (!$ath->isValid()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'This tracker is no longer valid.', 'addArtifact');
            }

            // check the user if he can submit artifacts for this tracker
            if (!$ath->userCanSubmit($user_id)) {
                return new SoapFault(PERMISSION_DENIED_FAULT, 'Permission Denied: You are not granted sufficient permission to perform this operation.', 'addArtifact');
            }

            $art_field_fact = new ArtifactFieldFactory($ath);
            if (!$art_field_fact || !is_object($art_field_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldFactory', 'addArtifact');
            } elseif ($art_field_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_field_fact->getErrorMessage(), 'addArtifact');
            }

            // 1) The permissions check will be done in the Artifact create function
            // 2) Check the allow empty value and the default value for each field.
            $all_used_fields = $art_field_fact->getAllUsedFields();

            foreach ($all_used_fields as $used_field) {
                 // We only check the field the user is allowed to submit
                // because the Artifact create function expect only these fields in the array $vfl
                if ($used_field->userCanSubmit($group_id, $group_artifact_id, $user_id)) {
                    // We skip these 4 fields, because their value is automatically filled
                    if ($used_field->getName() == 'open_date' || $used_field->getName() == 'last_update_date' || $used_field->getName() == 'submitted_by' || $used_field->getName() == 'artifact_id') {
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

                            foreach ($extra_fields as $extra_field) {
                                if (is_object($extra_field)) {
                                    $extra_field = objectToArray($extra_field);
                                }

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
                return new SoapFault(GET_ARTIFACT_FAULT, 'Could Not Get Artifact', 'addArtifact');
            } elseif ($a->isError()) {
                return new SoapFault(GET_ARTIFACT_FAULT, $a->getErrorMessage(), 'addArtifact');
            }

            $data = setArtifactData($status_id, $close_date, $summary, $details, $severity, $extra_fields);

            //Check Field Dependencies
            $arm = new ArtifactRulesManager();
            if (!$arm->validate($ath->getID(), $data, $art_field_fact)) {
                return new SoapFault(INVALID_FIELD_DEPENDENCY_FAULT, 'Invalid Field Dependency', 'addArtifact');
            }
            if (!$a->create($data)) {
                return new SoapFault(CREATE_ARTIFACT_FAULT, $a->getErrorMessage(), 'addArtifact');
            } else {
                // Send the notification
                $agnf = new ArtifactGlobalNotificationFactory();
                $addresses = $agnf->getAllAddresses($ath->getID());
                $a->mailFollowupWithPermissions($addresses);

                return $a->getID();
            }
        } else {
               return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session ', 'addArtifact');
        }
    }

    function objectToArray($object)
    {
        foreach ($object as $key => $value) {
            $array[$key] = $value;
        }
        return $array;
    }

/**
 * addArtifactWithFieldNames - add an artifact in tracker $tracjer_name of the project $group_id with given valuess
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to add the artifact
 * @param int $group_artifact_id the ID of the tracker we want to add the artifact
 * @param int $status_id the ID of the status of the artifact
 * @param string $close_date the close date of the artifact. The format must be YYYY-MM-DD
 * @param string $summary the summary of the artifact
 * @param string $details the details (original submission) of the artifact
 * @param int $severity the severity of the artifact
 * @param array $extra_fields the extra_fields of the artifact (non standard fields)
 * @return int the ID of the new created artifact,
 *              or a soap fault if :
 *              - group_id does not match with a valid project,
 *              - tracker_name does not match with a valid tracker,
 *              - the user does not have the permissions to submit an artifact
 *              - the given values are breaking a field dependency rule
 *              - the artifact creation failed.
 */
    function addArtifactWithFieldNames($sessionKey, $group_id, $group_artifact_id, $status_id, $close_date, $summary, $details, $severity, $extra_fields)
    {
        global $art_field_fact, $ath;

        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'addArtifactWithFieldNames');
            } catch (SoapFault $e) {
                return $e;
            }
            $at = new ArtifactType($grp, $group_artifact_id);
            if (!$at || !is_object($at)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'ArtifactType could not be created', 'addArtifact');
            }
            if ($at->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $at->getErrorMessage(), 'addArtifact');
            }
            // Check if this tracker is valid (not deleted)
            if (!$at->isValid()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'This tracker is no longer valid.', 'addArtifact');
            }

            $group_artifact_id = $at->getID();

            $art_field_fact = new ArtifactFieldFactory($at);
            if (!$art_field_fact || !is_object($art_field_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldFactory', 'addArtifact');
            } elseif ($art_field_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_field_fact->getErrorMessage(), 'addArtifact');
            }

            // translate the field_name in field_id, in order to call the real addArtifact function
            $extrafields_with_id = array();
            foreach ($extra_fields as $extra_field_name) {
                $field = $art_field_fact->getFieldFromName($extra_field_name->field_name);
                if ($field) {
                    $extra_field_id = $field->getID();
                    $extrafields_with_id[] = array('field_id' => $extra_field_id, 'artifact_id' => 0, 'field_value' => $extra_field_name->field_value);
                } else {
                    return new SoapFault(INVALID_FIELD_FAULT, 'Invalid Field:' . $extra_field_name->field_name, 'addArtifact');
                }
            }

            return addArtifact($sessionKey, $group_id, $group_artifact_id, $status_id, $close_date, $summary, $details, $severity, $extrafields_with_id);
        } else {
               return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session ', 'addArtifact');
        }
    }

/**
 * updateArtifact - update the artifact $artifact_id in tracker $group_artifact_id of the project $group_id with given values
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
 * @param array $extra_fields the extra_fields of the artifact (non standard fields)
 * @return SoapFault|int the ID of the artifact,
 *              or a soap fault if :
 *              - group_id does not match with a valid project,
 *              - group_artifact_id does not match with a valid tracker,
 *              - artifact_id does not match with a valid artifact,
 *              - the given values are breaking a field dependency rule
 *              - the artifact modification failed.
 */
    function updateArtifact($sessionKey, $group_id, $group_artifact_id, $artifact_id, $status_id, $close_date, $summary, $details, $severity, $extra_fields)
    {
        global $art_field_fact, $ath;
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'updateArtifact');
            } catch (SoapFault $e) {
                return $e;
            }

            $ath = new ArtifactType($grp, $group_artifact_id);
            if (!$ath || !is_object($ath)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'ArtifactType could not be created', 'updateArtifact');
            }
            if ($ath->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $ath->getErrorMessage(), 'updateArtifact');
            }
            // Check if this tracker is valid (not deleted)
            if (!$ath->isValid()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'This tracker is no longer valid.', 'updateArtifact');
            }

            $art_field_fact = new ArtifactFieldFactory($ath);
            if (!$art_field_fact || !is_object($art_field_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldFactory', 'updateArtifact');
            } elseif ($art_field_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_field_fact->getErrorMessage(), 'updateArtifact');
            }
            $a = new Artifact($ath, $artifact_id);
            if (!$a || !is_object($a)) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Could Not Get Artifact', 'updateArtifact');
            } elseif ($a->isError()) {
                return new SoapFault(GET_ARTIFACT_FAULT, $a->getErrorMessage(), 'updateArtifact');
            }

            $data = setArtifactData($status_id, $close_date, $summary, $details, $severity, $extra_fields);

            //Check Field Dependencies
            $arm = new ArtifactRulesManager();
            if (!$arm->validate($ath->getID(), $data, $art_field_fact)) {
                return new SoapFault(INVALID_FIELD_DEPENDENCY_FAULT, 'Invalid Field Dependency', 'updateArtifact');
            }

            $changes = [];
            if (! $a->handleUpdate($artifact_id_dependent, $canned_response, $changes, false, $data, true)) {
                return new SoapFault(UPDATE_ARTIFACT_FAULT, $a->getErrorMessage(), 'updateArtifact');
            } else {
                if ($a->isError()) {
                    return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $a->getErrorMessage(), 'updateArtifact');
                }
                // Update last_update_date field
                $a->update_last_update_date();
                assert(is_array($changes));
                // Send the notification
                if ($changes) {
                    $agnf = new ArtifactGlobalNotificationFactory();
                    $addresses = $agnf->getAllAddresses($ath->getID(), true);
                    $a->mailFollowupWithPermissions($addresses, $changes);
                }

                return $a->getID();
            }
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session ', 'updateArtifact');
        }
    }

/**
 * updateArtifactWithFieldNames - update the artifact $artifact_id in tracker $tracker_name of the project $group_id with given values
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
 * @param array $extra_fields the extra_fields of the artifact (non standard fields)
 * @return SoapFault|int the ID of the artifact,
 *              or a soap fault if :
 *              - group_id does not match with a valid project,
 *              - trackr_name does not match with a valid tracker,
 *              - artifact_id does not match with a valid artifact,
 *              - the given values are breaking a field dependency rule
 *              - the artifact modification failed.
 */
    function updateArtifactWithFieldNames($sessionKey, $group_id, $group_artifact_id, $artifact_id, $status_id, $close_date, $summary, $details, $severity, $extra_fields)
    {
        global $art_field_fact, $ath;
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'updateArtifactWithFieldNames');
            } catch (SoapFault $e) {
                return $e;
            }
            $at = new ArtifactType($grp, $group_artifact_id);
            if (!$at || !is_object($at)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'ArtifactType could not be created', 'updateArtifact');
            }
            if ($at->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $at->getErrorMessage(), 'updateArtifact');
            }
            // Check if this tracker is valid (not deleted)
            if (!$at->isValid()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'This tracker is no longer valid.', 'updateArtifact');
            }

            $group_artifact_id = $at->getID();

            $art_field_fact = new ArtifactFieldFactory($at);
            if (!$art_field_fact || !is_object($art_field_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldFactory', 'updateArtifact');
            } elseif ($art_field_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_field_fact->getErrorMessage(), 'updateArtifact');
            }

            // translate the field_name in field_id, in order to call the real addArtifact function
            $extrafields_with_id = array();
            foreach ($extra_fields as $extra_field_name) {
                $field = $art_field_fact->getFieldFromName($extra_field_name->field_name);
                if ($field) {
                    $extra_field_id = $field->getID();
                    $extrafields_with_id[] = array('field_id' => $extra_field_id, 'field_value' => $extra_field_name->field_value);
                } else {
                    return new SoapFault(INVALID_FIELD_FAULT, 'Invalid Field:' . $extra_field_name->field_name, 'updateArtifact');
                }
            }

            return updateArtifact($sessionKey, $group_id, $group_artifact_id, $artifact_id, $status_id, $close_date, $summary, $details, $severity, $extrafields_with_id, $artifact_id_dependent, $canned_response);
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session ', 'updateArtifact');
        }
    }


/**
 * getArtifactFollowups - returns the array of follow-ups of the artifact $artifact_d in tracker $group_artifact_id of the project $group_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the artifact follow-ups
 * @param int $group_artifact_id the ID of the tracker we want to retrieve the artifact follow-ups
 * @param int $artifact_id the ID of the artifact we want to retrieve the follow-ups
 * @return array the array of the follow-ups for this artifact,
 *              or a soap fault if :
 *              - group_id does not match with a valid project,
 *              - group_artifact_id does not match with a valid tracker,
 *              - the artifact_id does not match with a valid artifact
 */
    function getArtifactFollowups($sessionKey, $group_id, $group_artifact_id, $artifact_id)
    {
        global $art_field_fact;
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'getArtifactFollowups');
            } catch (SoapFault $e) {
                return $e;
            }

            $at = new ArtifactType($grp, $group_artifact_id);
            if (!$at || !is_object($at)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Could Not Get ArtifactType', 'getArtifactFollowups');
            } elseif ($at->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $at->getErrorMessage(), 'getArtifactFollowups');
            }

            $art_field_fact = new ArtifactFieldFactory($at);
            if (!$art_field_fact || !is_object($art_field_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldFactory', 'getArtifactFollowups');
            } elseif ($art_field_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_field_fact->getErrorMessage(), 'getArtifactFollowups');
            }

            $a = new Artifact($at, $artifact_id);
            if (!$a || !is_object($a)) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Could Not Get Artifact', 'getArtifactFollowups');
            } elseif ($a->isError()) {
                return new SoapFault(GET_ARTIFACT_FAULT, $a->getErrorMessage(), 'getArtifactFollowups');
            }

            $return  = artifactfollowups_to_soap($a->getFollowups(), $group_id, $group_artifact_id, $a);
            return $return;
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session ', 'getArtifactFollowups');
        }
    }

    function artifactfollowups_to_soap($followups_res, $group_id, $group_artifact_id, $artifact)
    {
        $return = array();
        $rows = db_numrows($followups_res);
        for ($i = 0; $i < $rows; $i++) {
            $comment = Codendi_HTMLPurifier::instance()->purify(db_result($followups_res, $i, 'new_value'), CODENDI_PURIFIER_BASIC_NOBR, $group_id);
            $id = db_result($followups_res, $i, 'artifact_history_id');
            $return[] = array (
            'artifact_id'         => db_result($followups_res, $i, 'artifact_id'),
            'follow_up_id'        => $id,
            'comment'             => util_unconvert_htmlspecialchars($comment), //db_result($followups_res, $i, 'new_value'),
            'date'                => db_result($followups_res, $i, 'date'),
            'original_date'       => db_result($artifact->getOriginalCommentDate($id), 0, 'date'),
            'by'                  => (db_result($followups_res, $i, 'mod_by') == 100 ? db_result($followups_res, $i, 'email') : db_result($followups_res, $i, 'user_name')),
            'original_by'         => (db_result($artifact->getOriginalCommentSubmitter($id), 0, 'mod_by') == 100 ? db_result($artifact->getOriginalCommentSubmitter($id), 0, 'email') : user_getname(db_result($artifact->getOriginalCommentSubmitter($id), 0, 'mod_by'))),
            'comment_type_id'     => db_result($followups_res, $i, 'comment_type_id'),
            'comment_type'        => util_unconvert_htmlspecialchars(db_result($followups_res, $i, 'comment_type')),
            'field_name'          => db_result($followups_res, $i, 'field_name'),
            'user_can_edit'       => $artifact->userCanEditFollowupComment($id) ? 1 : 0
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
 * @return array the array of the canned responses for this tracker,
 *              or a soap fault if :
 *              - group_id does not match with a valid project,
 *              - group_artifact_id does not match with a valid tracker
 */
    function getArtifactCannedResponses($sessionKey, $group_id, $group_artifact_id)
    {
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'getArtifactCannedResponses');
            } catch (SoapFault $e) {
                return $e;
            }

            $at = new ArtifactType($grp, $group_artifact_id);
            if (!$at || !is_object($at)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Could Not Get ArtifactType', 'getArtifactCannedResponses');
            } elseif ($at->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $at->getErrorMessage(), 'getArtifactCannedResponses');
            }
            return artifactcannedresponses_to_soap($at->getCannedResponses(), $group_artifact_id);
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session ', 'getArtifactCannedResponses');
        }
    }

    function artifactcannedresponses_to_soap($cannedresponses_res, $group_artifact_id)
    {
        $return = array();
        $rows = db_numrows($cannedresponses_res);
        for ($i = 0; $i < $rows; $i++) {
            $return[] = array (
            'artifact_canned_id' => db_result($cannedresponses_res, $i, 'artifact_canned_id'),
            'group_artifact_id' => $group_artifact_id,
            'title' => util_unconvert_htmlspecialchars(db_result($cannedresponses_res, $i, 'title')),
            'body' => util_unconvert_htmlspecialchars(db_result($cannedresponses_res, $i, 'body'))
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
 * @return array the array of the reports of the current user for this tracker,
 *              or a soap fault if :
 *              - group_id does not match with a valid project,
 *              - group_artifact_id does not match with a valid tracker
 */
    function getArtifactReports($sessionKey, $group_id, $group_artifact_id)
    {
        $user_id = UserManager::instance()->getCurrentUser()->getId();
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'getArtifactReports');
            } catch (SoapFault $e) {
                return $e;
            }

            $at = new ArtifactType($grp, $group_artifact_id);
            if (!$at || !is_object($at)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Could Not Get ArtifactType', 'getArtifactReports');
            } elseif ($at->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $at->getErrorMessage(), 'getArtifactReports');
            }
            if (! $at->userCanView($user_id)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Permissions denied.', 'getArtifactReports');
            }

            $report_fact = new ArtifactReportFactory();
            if (!$report_fact || !is_object($report_fact)) {
                return new SoapFault(GET_REPORT_FACTORY_FAULT, 'Could Not Get ArtifactReportFactory', 'getArtifactReports');
            }

            return artifactreports_to_soap($report_fact->getReports($group_artifact_id, $user_id));
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session ', 'getArtifactReports');
        }
    }

    function artifactreports_to_soap($artifactreports)
    {
        $return = array();
        if (is_array($artifactreports) && count($artifactreports)) {
            foreach ($artifactreports as $arid => $artifactreport) {
                $fields = array();
                if ($artifactreport->isError()) {
                    //skip if error
                } else {
                    $report_fields = $artifactreport->getSortedFields();
                    if (is_array($report_fields) && count($report_fields) > 0) {
                        foreach ($report_fields as $field) {
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
                    $return[] = array(
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
 * NOTE : by default, this function does not return the content of the files (for performance reasons). To get the binary content of files, give $set_bin_data the true value.
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the attached files
 * @param int $group_artifact_id the ID of the tracker we want to retrieve the attached files
 * @param int $artifact_id the ID of the artifact we want to retrieve the attached files
 * @return array the array of the attached file of the artifact,
 *              or a soap fault if :
 *              - group_id does not match with a valid project,
 *              - group_artifact_id does not match with a valid tracker
 *              - artifact_id does not match with a valid artifact
 */
    function getArtifactAttachedFiles($sessionKey, $group_id, $group_artifact_id, $artifact_id, $set_bin_data = false)
    {
        global $art_field_fact;

        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'getArtifactAttachedFiles');
            } catch (SoapFault $e) {
                return $e;
            }

            $at = new ArtifactType($grp, $group_artifact_id);
            if (!$at || !is_object($at)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Could Not Get ArtifactType', 'getArtifactAttachedFiles');
            } elseif ($at->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $at->getErrorMessage(), 'getArtifactAttachedFiles');
            }

            $art_field_fact = new ArtifactFieldFactory($at);
            if (!$art_field_fact || !is_object($art_field_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldFactory', 'getArtifactAttachedFiles');
            } elseif ($art_field_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_field_fact->getErrorMessage(), 'getArtifactAttachedFiles');
            }

            $a = new Artifact($at, $artifact_id);
            if (!$a || !is_object($a)) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Could Not Get Artifact', 'getArtifactAttachedFiles');
            } elseif ($a->isError()) {
                return new SoapFault(GET_ARTIFACT_FAULT, $a->getErrorMessage(), 'getArtifactAttachedFiles');
            } elseif (! $a->userCanView()) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Permissions denied', 'getArtifactAttachedFiles');
            }

            return artifactfiles_to_soap($a->getAttachedFiles(), $set_bin_data);
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'getArtifactAttachedFiles');
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
 * @return mixed {SOAPArtifactFile} the attached file of the artifact,
 *              or a soap fault if :
 *              - group_id does not match with a valid project,
 *              - group_artifact_id does not match with a valid tracker
 *              - artifact_id does not match with a valid artifact
 *              - file_id does not match with the given artifact_id
 */
    function getArtifactAttachedFile($sessionKey, $group_id, $group_artifact_id, $artifact_id, $file_id)
    {
        global $art_field_fact;
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'getArtifactAttachedFile');
            } catch (SoapFault $e) {
                return $e;
            }

            $at = new ArtifactType($grp, $group_artifact_id);
            if (!$at || !is_object($at)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Could Not Get ArtifactType', 'getArtifactAttachedFile');
            } elseif ($at->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $at->getErrorMessage(), 'getArtifactAttachedFile');
            }

            $art_field_fact = new ArtifactFieldFactory($at);
            if (!$art_field_fact || !is_object($art_field_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldFactory', 'getArtifactAttachedFile');
            } elseif ($art_field_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_field_fact->getErrorMessage(), 'getArtifactAttachedFile');
            }

            $a = new Artifact($at, $artifact_id);
            if (!$a || !is_object($a)) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Could Not Get Artifact', 'getArtifactAttachedFile');
            } elseif ($a->isError()) {
                return new SoapFault(GET_ARTIFACT_FAULT, $a->getErrorMessage(), 'getArtifactAttachedFile');
            } elseif (! $a->userCanView()) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Permissions denied', 'getArtifactAttachedFile');
            }
            $file = artifactfile_to_soap($file_id, $a, true);
            if ($file != null) {
                return $file;
            } else {
                return new SoapFault(INVALID_SESSION_FAULT, 'Attached file ' . $file_id . ' not found', 'getArtifactAttachedFile');
            }
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'getArtifactAttachedFile');
        }
    }

    function artifactfiles_to_soap($attachedfiles_arr, $set_bin_data = false)
    {
        $return = array();
        $rows = db_numrows($attachedfiles_arr);
        for ($i = 0; $i < $rows; $i++) {
            $bin_data = db_result($attachedfiles_arr, $i, 'bin_data');
            $return[] = array(
            'id' => db_result($attachedfiles_arr, $i, 'id'),
            'artifact_id' => db_result($attachedfiles_arr, $i, 'artifact_id'),
            'filename' => db_result($attachedfiles_arr, $i, 'filename'),
            'description' => SimpleSanitizer::unsanitize(db_result($attachedfiles_arr, $i, 'description')),
            'bin_data' => ($set_bin_data ? $bin_data : null),
            'filesize' => db_result($attachedfiles_arr, $i, 'filesize'),
            'filetype' => db_result($attachedfiles_arr, $i, 'filetype'),
            'adddate' => db_result($attachedfiles_arr, $i, 'adddate'),
            'submitted_by' => db_result($attachedfiles_arr, $i, 'user_name')
            );
        }
        return $return;
    }

    function artifactfile_to_soap($file_id, Artifact $artifact, $set_bin_data)
    {
        $return = null;
        $attachedfiles_arr = $artifact->getAttachedFiles();
        $rows = db_numrows($attachedfiles_arr);
        for ($i = 0; $i < $rows; $i++) {
            $file = array();
            $attachment_id = db_result($attachedfiles_arr, $i, 'id');
            $file['id'] = $attachment_id;
            $file['artifact_id'] = db_result($attachedfiles_arr, $i, 'artifact_id');
            $file['filename'] = db_result($attachedfiles_arr, $i, 'filename');
            $file['description'] = SimpleSanitizer::unsanitize(db_result($attachedfiles_arr, $i, 'description'));
            if ($set_bin_data) {
                $attachment_path = ArtifactFile::getPathOnFilesystem($artifact, $attachment_id);
                if (is_file($attachment_path)) {
                    $file['bin_data'] = file_get_contents($attachment_path);
                } else {
                    $file['bin_data'] = db_result($attachedfiles_arr, $i, 'bin_data');
                }
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
 * getArtifactDependencies - returns the array of ArtifactDependency of the artifact $artifact_id in the tracker $group_artifact_id of the project $group_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the dependencies
 * @param int $group_artifact_id the ID of the tracker we want to retrieve the dependencies
 * @param int $artifact_id the ID of the artifact we want to retrieve the dependencies
 * @return array the array of the dependencies of the artifact,
 *              or a soap fault if :
 *              - group_id does not match with a valid project,
 *              - group_artifact_id does not match with a valid tracker
 *              - artifact_id does not match with a valid artifact
 */
    function getArtifactDependencies($sessionKey, $group_id, $group_artifact_id, $artifact_id)
    {
        global $art_field_fact;
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'getArtifactDependencies');
            } catch (SoapFault $e) {
                return $e;
            }

            $at = new ArtifactType($grp, $group_artifact_id);
            if (!$at || !is_object($at)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Could Not Get ArtifactType', 'getArtifactDependencies');
            } elseif ($at->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $at->getErrorMessage(), 'getArtifactDependencies');
            }

            $art_field_fact = new ArtifactFieldFactory($at);
            if (!$art_field_fact || !is_object($art_field_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldFactory', 'getArtifactDependencies');
            } elseif ($art_field_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_field_fact->getErrorMessage(), 'getArtifactDependencies');
            }

            $a = new Artifact($at, $artifact_id);
            if (!$a || !is_object($a)) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Could Not Get Artifact', 'getArtifactDependencies');
            } elseif ($a->isError()) {
                return new SoapFault(GET_ARTIFACT_FAULT, $a->getErrorMessage(), 'getArtifactDependencies');
            } elseif (! $a->userCanView()) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Permissions denied', 'getArtifactDependencies');
            }

            return dependencies_to_soap($at, $a->getDependencies());
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'getArtifactDependencies');
        }
    }

    function dependencies_to_soap($artifact_type, $dependencies)
    {
        $return = array();
        $rows = db_numrows($dependencies);
        for ($i = 0; $i < $rows; $i++) {
            // check the permission : is the user allowed to see the artifact ?
            $artifact = new Artifact($artifact_type, db_result($dependencies, $i, 'is_dependent_on_artifact_id'));
            if ($artifact && $artifact->userCanView()) {
                $return[] = array(
                'artifact_depend_id' => db_result($dependencies, $i, 'artifact_depend_id'),
                'artifact_id' => db_result($dependencies, $i, 'artifact_id'),
                'is_dependent_on_artifact_id' => db_result($dependencies, $i, 'is_dependent_on_artifact_id'),
                'summary' => util_unconvert_htmlspecialchars(db_result($dependencies, $i, 'summary')),
                'tracker_id' => db_result($dependencies, $i, 'group_artifact_id'),
                'tracker_name' => SimpleSanitizer::unsanitize(db_result($dependencies, $i, 'name')),
                'group_id' => db_result($dependencies, $i, 'group_id'),
                'group_name' => db_result($dependencies, $i, 'group_name')
                );
            }
        }
        return $return;
    }

/**
 * getArtifactInverseDependencies - returns the array of the inverse ArtifactDependency of the artifact $artifact_id in the tracker $group_artifact_id of the project $group_id
 *
 * warning: the same structure ArtifactDependency is used for "reverse" dependencies, but artifact_depend_id won't be filled
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the inverse dependencies
 * @param int $group_artifact_id the ID of the tracker we want to retrieve the inverse dependencies
 * @param int $artifact_id the ID of the artifact we want to retrieve the inverse dependencies
 * @return array the array of the inverse dependencies of the artifact,
 *              or a soap fault if :
 *              - group_id does not match with a valid project,
 *              - group_artifact_id does not match with a valid tracker
 *              - artifact_id does not match with a valid artifact
 */
    function getArtifactInverseDependencies($sessionKey, $group_id, $group_artifact_id, $artifact_id)
    {
        global $art_field_fact;
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'getArtifactInverseDependencies');
            } catch (SoapFault $e) {
                return $e;
            }

            $at = new ArtifactType($grp, $group_artifact_id);
            if (!$at || !is_object($at)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Could Not Get ArtifactType', 'getArtifactInverseDependencies');
            } elseif ($at->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $at->getErrorMessage(), 'getArtifactInverseDependencies');
            }

            $art_field_fact = new ArtifactFieldFactory($at);
            if (!$art_field_fact || !is_object($art_field_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldFactory', 'getArtifactInverseDependencies');
            } elseif ($art_field_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_field_fact->getErrorMessage(), 'getArtifactInverseDependencies');
            }

            $a = new Artifact($at, $artifact_id);
            if (!$a || !is_object($a)) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Could Not Get Artifact', 'getArtifactInverseDependencies');
            } elseif ($a->isError()) {
                return new SoapFault(GET_ARTIFACT_FAULT, $a->getErrorMessage(), 'getArtifactInverseDependencies');
            } elseif (! $a->userCanView()) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Permissions denied', 'getArtifactInverseDependencies');
            }

            return inverse_dependencies_to_soap($at, $artifact_id, $a->getInverseDependencies());
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'getArtifactInverseDependencies');
        }
    }

/**
 * We keep the order of the relation in the database, even if we are getting the inverse.
 */
    function inverse_dependencies_to_soap($artifact_type, $artifact_id, $inverse_dependencies)
    {
        $return = array();
        $rows = db_numrows($inverse_dependencies);
        for ($i = 0; $i < $rows; $i++) {
            // check the permission : is the user allowed to see the artifact ?
            $artifact = new Artifact($artifact_type, db_result($inverse_dependencies, $i, 'artifact_id'));
            if ($artifact && $artifact->userCanView()) {
                $return[] = array(
                'artifact_depend_id' => db_result($inverse_dependencies, $i, 'artifact_depend_id'),
                'artifact_id' => db_result($inverse_dependencies, $i, 'artifact_id'),
                'is_dependent_on_artifact_id' => $artifact_id,
                'summary' => util_unconvert_htmlspecialchars(db_result($inverse_dependencies, $i, 'summary')),
                'tracker_id' => db_result($inverse_dependencies, $i, 'group_artifact_id'),
                'tracker_name' => SimpleSanitizer::unsanitize(db_result($inverse_dependencies, $i, 'name')),
                'group_id' => db_result($inverse_dependencies, $i, 'group_id'),
                'group_name' => db_result($inverse_dependencies, $i, 'group_name')
                );
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
    function addArtifactAttachedFile($sessionKey, $group_id, $group_artifact_id, $artifact_id, $encoded_data, $description, $filename, $filetype)
    {
        global $art_field_fact;
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'addArtifactAttachedFile');
            } catch (SoapFault $e) {
                return $e;
            }

            $at = new ArtifactType($grp, $group_artifact_id);
            if (!$at || !is_object($at)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Could Not Get ArtifactType', 'addArtifactFile');
            } elseif ($at->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $at->getErrorMessage(), 'addArtifactFile');
            }

            $art_field_fact = new ArtifactFieldFactory($at);
            if (!$art_field_fact || !is_object($art_field_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldFactory', 'addArtifactFile');
            } elseif ($art_field_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_field_fact->getErrorMessage(), 'addArtifactFile');
            }

            $a = new Artifact($at, $artifact_id);
            if (!$a || !is_object($a)) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Could Not Get Artifact', 'addArtifactFile');
            } elseif ($a->isError()) {
                return new SoapFault(GET_ARTIFACT_FAULT, $a->getErrorMessage(), 'addArtifactFile');
            }

            $af = new ArtifactFile($a);
            if (!$af || !is_object($af)) {
                return new SoapFault(GET_ARTIFACT_FILE_FAULT, 'Could Not Create File Object', 'addArtifactFile');
            } elseif ($af->isError()) {
                return new SoapFault(GET_ARTIFACT_FILE_FAULT, $af->getErrorMessage(), 'addArtifactFile');
            }

            $bin_data = base64_decode($encoded_data);

            $filesize = strlen($bin_data);

            $id = $af->create($filename, $filetype, $filesize, $bin_data, $description, $changes);

            if (!$id) {
                return new SoapFault(GET_ARTIFACT_FILE_FAULT, $af->getErrorMessage(), 'addArtifactFile');
            } else {
                // Send the notification
                if ($changes) {
                    $agnf = new ArtifactGlobalNotificationFactory();
                    $addresses = $agnf->getAllAddresses($at->getID(), true);
                    $a->mailFollowupWithPermissions($addresses, $changes);
                }
            }

            return $id;
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'addArtifactFile');
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
    function deleteArtifactAttachedFile($sessionKey, $group_id, $group_artifact_id, $artifact_id, $file_id)
    {
        global $art_field_fact;
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'deleteArtifactAttachedFile');
            } catch (SoapFault $e) {
                return $e;
            }

            $at = new ArtifactType($grp, $group_artifact_id);
            if (!$at || !is_object($at)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Could Not Get ArtifactType', 'deleteArtifactFile');
            } elseif ($at->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $at->getErrorMessage(), 'deleteArtifactFile');
            }

            $art_field_fact = new ArtifactFieldFactory($at);
            if (!$art_field_fact || !is_object($art_field_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldFactory', 'deleteArtifactFile');
            } elseif ($art_field_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_field_fact->getErrorMessage(), 'deleteArtifactFile');
            }

            $a = new Artifact($at, $artifact_id);
            if (!$a || !is_object($a)) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Could Not Get Artifact', 'deleteArtifactFile');
            } elseif ($a->isError()) {
                return new SoapFault(GET_ARTIFACT_FAULT, $a->getErrorMessage(), 'deleteArtifactFile');
            }

            $af = new ArtifactFile($a, $file_id);
            if (!$af || !is_object($af)) {
                return new SoapFault(GET_ARTIFACT_FILE_FAULT, 'Could Not Create File Object', 'deleteArtifactFile');
            } elseif ($af->isError()) {
                return new SoapFault(GET_ARTIFACT_FILE_FAULT, $af->getErrorMessage(), 'deleteArtifactFile');
            }

            if (!$af->delete()) {
                return new SoapFault(GET_ARTIFACT_FILE_FAULT, $af->getErrorMessage(), 'deleteArtifactFile');
            }

            return $file_id;
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'deleteArtifactFile');
        }
    }

/**
 * addArtifactDependencies - add dependencies to the artifact $artifact_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to add the dependencies
 * @param int $group_artifact_id the ID of the tracker we want to add the dependencies
 * @param int $artifact_id the ID of the artifact we want to add the dependencies
 * @param string $is_dependent_on_artifact_ids the list of dependencies, in the form of a list of artifact_id, separated with a comma.
 * @return void if the add is ok or a soap fault if :
 *              - group_id does not match with a valid project,
 *              - group_artifact_id does not match with a valid tracker
 *              - artifact_id does not match with a valid artifact
 *              - the add failed
 */
    function addArtifactDependencies($sessionKey, $group_id, $group_artifact_id, $artifact_id, $is_dependent_on_artifact_ids)
    {
        global $art_field_fact;
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'addArtifactDependencies');
            } catch (SoapFault $e) {
                return $e;
            }

            $at = new ArtifactType($grp, $group_artifact_id);
            if (!$at || !is_object($at)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Could Not Get ArtifactType', 'addArtifactDependencies');
            } elseif ($at->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $at->getErrorMessage(), 'addArtifactDependencies');
            }

            $art_field_fact = new ArtifactFieldFactory($at);
            if (!$art_field_fact || !is_object($art_field_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldFactory', 'addArtifactDependencies');
            } elseif ($art_field_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_field_fact->getErrorMessage(), 'addArtifactDependencies');
            }

            $a = new Artifact($at, $artifact_id);
            if (!$a || !is_object($a)) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Could Not Get Artifact', 'addArtifactDependencies');
            } elseif ($a->isError()) {
                return new SoapFault(GET_ARTIFACT_FAULT, $a->getErrorMessage(), 'addArtifactDependencies');
            }

            $changes = array();
            if (!$a->addDependencies($is_dependent_on_artifact_ids, $changes, false, false)) {
                if (!isset($changes) || !is_array($changes) || count($changes) == 0) {
                    return new SoapFault(ADD_DEPENDENCY_FAULT, 'Dependencies addition for artifact #' . $a->getID() . ' failed', 'addArtifactDependencies');
                }
            } else {
                return true;
            }
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'addArtifactDependencies');
        }
    }

/**
 * updateArtifactFollowUp - update the artifact follow up $artifact_history_id in tracker $group_artifact_id of the project $group_id for the artifact $artifact_id with given comment
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to update the comment
 * @param int $group_artifact_id the ID of the tracker we want to update the comment
 * @param int $artifact_id the ID of the artifact we want to update the comment
 * @param int $artifact_history_id the ID of the artifact comment we want to update
 * @param string $comment the new comment
 * @return int the 0 if the update failed and one otherwise,
 *              or a soap fault if :
 *              - group_id does not match with a valid project,
 *              - group_artifact_id does not match with a valid tracker,
 *              - artifact_id does not match with a valid artifact,
 *              - artifact_history_id does not match with a valid comment,
 *              - the comment modification failed.
 */
    function updateArtifactFollowUp($sessionKey, $group_id, $group_artifact_id, $artifact_id, $artifact_history_id, $comment)
    {
        global $art_field_fact, $changes;
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'updateArtifactFollowUp');
            } catch (SoapFault $e) {
                return $e;
            }

            $at = new ArtifactType($grp, $group_artifact_id);
            if (!$at || !is_object($at)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Could Not Get ArtifactType', 'updateArtifactFollowUp');
            } elseif ($at->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $at->getErrorMessage(), 'updateArtifactFollowUp');
            }

            $art_field_fact = new ArtifactFieldFactory($at);
            if (!$art_field_fact || !is_object($art_field_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldFactory', 'getArtifactById');
            } elseif ($art_field_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_field_fact->getErrorMessage(), 'getArtifactById');
            }

            $a = new Artifact($at, $artifact_id);

            if (!$a || !is_object($a)) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Could Not Get Artifact', 'updateArtifactFollowUp');
            } elseif ($a->isError()) {
                return new SoapFault(GET_ARTIFACT_FAULT, $a->getErrorMessage(), 'updateArtifactFollowUp');
            }

            $res = $a->getFollowUpDetails($artifact_history_id);

            if (!$a->updateFollowupComment($artifact_history_id, $comment, $changes, $res['format'])) {
                return new SoapFault(UPDATE_ARTIFACT_FOLLOWUP_FAULT, $a->getErrorMessage(), 'updateArtifactFollowUp');
            } else {
            // Send the notification
                if ($changes) {
                    $agnf = new ArtifactGlobalNotificationFactory();
                    $addresses = $agnf->getAllAddresses($at->getID(), true);
                    $a->mailFollowupWithPermissions($addresses, $changes);
                }

                return true;
            }
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session ', 'updateArtifactFollowUp');
        }
    }

/**
 * deleteArtifactFollowUp- delete the artifact follow up $artifact_history_id in tracker $group_artifact_id of the project $group_id for the artifact $artifact_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to delete the comment
 * @param int $group_artifact_id the ID of the tracker we want to delete the comment
 * @param int $artifact_id the ID of the artifact we want to delete the comment
 * @param int $artifact_history_id the ID of the artifact comment we want to delete
 * @return int the 0 if the deletion failed and 1 otherwise,
 *              or a soap fault if :
 *              - group_id does not match with a valid project,
 *              - group_artifact_id does not match with a valid tracker,
 *              - artifact_id does not match with a valid artifact,
 *              - artifact_history_id does not match with a valid comment,
 *              - the comment deletion failed.
 */

    function deleteArtifactFollowUp($sessionKey, $group_id, $group_artifact_id, $artifact_id, $artifact_history_id)
    {
        global $art_field_fact, $changes;
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'deleteArtifactFollowUp');
            } catch (SoapFault $e) {
                return $e;
            }

            $at = new ArtifactType($grp, $group_artifact_id);
            if (!$at || !is_object($at)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Could Not Get ArtifactType', 'deleteArtifactFollowUp');
            } elseif ($at->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $at->getErrorMessage(), 'deleteArtifactFollowUp');
            }

            $art_field_fact = new ArtifactFieldFactory($at);
            if (!$art_field_fact || !is_object($art_field_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldFactory', 'getArtifactById');
            } elseif ($art_field_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_field_fact->getErrorMessage(), 'getArtifactById');
            }

            $a = new Artifact($at, $artifact_id);

            if (!$a || !is_object($a)) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Could Not Get Artifact', 'deleteArtifactFollowUp');
            } elseif ($a->isError()) {
                return new SoapFault(GET_ARTIFACT_FAULT, $a->getErrorMessage(), 'deleteArtifactFollowUp');
            }

            if (!$a->deleteFollowupComment($artifact_id, $artifact_history_id)) {
                return new SoapFault(DELETE_ARTIFACT_FOLLOWUP_FAULT, $a->getErrorMessage(), 'deleteArtifactFollowUp');
            } else {
                return true;
            }
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session ', 'deleteArtifactFollowUp');
        }
    }

/**
 * deleteArtifactDependency - delete the dependency between $artifact_id and $dependent_on_artifact_id
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
    function deleteArtifactDependency($sessionKey, $group_id, $group_artifact_id, $artifact_id, $dependent_on_artifact_id)
    {
        global $art_field_fact;
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'deleteArtifactDependency');
            } catch (SoapFault $e) {
                return $e;
            }

            $at = new ArtifactType($grp, $group_artifact_id);
            if (!$at || !is_object($at)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Could Not Get ArtifactType', 'deleteArtifactDependency');
            } elseif ($at->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $at->getErrorMessage(), 'deleteArtifactDependency');
            }

            $art_field_fact = new ArtifactFieldFactory($at);
            if (!$art_field_fact || !is_object($art_field_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldFactory', 'deleteArtifactDependency');
            } elseif ($art_field_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_field_fact->getErrorMessage(), 'deleteArtifactDependency');
            }

            $a = new Artifact($at, $artifact_id);
            if (!$a || !is_object($a)) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Could Not Get Artifact', 'deleteArtifactDependency');
            } elseif ($a->isError()) {
                return new SoapFault(GET_ARTIFACT_FAULT, $a->getErrorMessage(), 'deleteArtifactDependency');
            }

            if (!$a->existDependency($dependent_on_artifact_id) || !$a->deleteDependency($dependent_on_artifact_id, $changes)) {
                return new SoapFault(DELETE_DEPENDENCY_FAULT, 'Error deleting dependency' . $dependent_on_artifact_id, 'deleteArtifactDependency');
            } else {
                return $dependent_on_artifact_id;
            }
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'deleteArtifactDependency');
        }
    }


/**
 * addArtifactFollowup - add a followup to the artifact $artifact_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to add the follow-up
 * @param int $group_artifact_id the ID of the tracker we want to add the follow-up
 * @param int $artifact_id the ID of the artifact we want to add the follow-up
 * @param string $body the body of the follow-up
 * @param int $comment_type_id the comment type ID if so, or 100 if comment type is not used
 * @param int $format the format within the followup will be posted (text/HTML)
 * @return bool true if the add is ok or a soap fault if :
 * - group_id does not match with a valid project,
 * - group_artifact_id does not match with a valid tracker
 * - artifact_id does not match with a valid artifact
 * - the add failed
 */
    function addArtifactFollowup($sessionKey, $group_id, $group_artifact_id, $artifact_id, $body, $comment_type_id, $format)
    {
        global $art_field_fact, $ath;
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'addArtifactFollowup');
            } catch (SoapFault $e) {
                return $e;
            }

            $ath = new ArtifactType($grp, $group_artifact_id);
            if (!$ath || !is_object($ath)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Could Not Get ArtifactType', 'addArtifactFollowup');
            } elseif ($ath->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $ath->getErrorMessage(), 'addArtifactFollowup');
            }

            $art_field_fact = new ArtifactFieldFactory($ath);
            if (!$art_field_fact || !is_object($art_field_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldFactory', 'addArtifactFollowup');
            } elseif ($art_field_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_field_fact->getErrorMessage(), 'addArtifactFollowup');
            }

            $a = new Artifact($ath, $artifact_id);
            if (!$a || !is_object($a)) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Could Not Get Artifact', 'addArtifactFollowup');
            } elseif ($a->isError()) {
                return new SoapFault(GET_ARTIFACT_FAULT, $a->getErrorMessage(), 'addArtifactFollowup');
            }
            // add the follow up with 0 as canned_response_id. To set a canned response, just put the content in the body comment.
            if (!$a->addFollowUpComment($body, $comment_type_id, 0, $changes, $format)) {
                return new SoapFault(CREATE_FOLLOWUP_FAULT, 'Comment could not be saved', 'addArtifactFollowup');
            } else {
                // Send notification
                $agnf = new ArtifactGlobalNotificationFactory();
                $addresses = $agnf->getAllAddresses($ath->getID(), true);
                $a->mailFollowupWithPermissions($addresses, $changes);
                return true;
            }
            // Update last_update_date field
            $a->update_last_update_date();
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'addArtifactFollowup');
        }
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
    function existArtifactSummary($sessionKey, $group_artifact_id, $summary)
    {
        if (session_continue($sessionKey)) {
            $res = db_query("SELECT group_id FROM artifact_group_list WHERE group_artifact_id = " . db_ei($group_artifact_id));
            if ($res && db_numrows($res) > 0) {
                $group_id = db_result($res, 0, 'group_id');
            } else {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Tracker not found.', 'existArtifactSummary');
            }

            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'existArtifactSummary');
            } catch (SoapFault $e) {
                return $e;
            }

            $at = new ArtifactType($grp, $group_artifact_id);
            if ($at->userCanView()) {
                $res = db_query('SELECT artifact_id FROM artifact WHERE group_artifact_id = ' . db_ei($group_artifact_id) .
                      ' AND summary="' . db_es(htmlspecialchars($summary)) . '"');
                if ($res && db_numrows($res) > 0) {
                    return db_result($res, 0, 0);
                } else {
                    return -1;
                }
            } else {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Permission denied.', 'existArtifactSummary');
            }
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'existArtifactSummary');
        }
    }

/**
 * getArtifactCCList - returns the array of ArtifactCC of the artifact $artifact_id in the tracker $group_artifact_id of the project $group_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the CC list
 * @param int $group_artifact_id the ID of the tracker we want to retrieve the CC list
 * @param int $artifact_id the ID of the artifact we want to retrieve the CC list
 * @return array the array of the CC list of the artifact,
 *              or a soap fault if :
 *              - group_id does not match with a valid project,
 *              - group_artifact_id does not match with a valid tracker
 *              - artifact_id does not match with a valid artifact
 */
    function getArtifactCCList($sessionKey, $group_id, $group_artifact_id, $artifact_id)
    {
        global $art_field_fact;
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'getArtifactCCList');
            } catch (SoapFault $e) {
                return $e;
            }

            $at = new ArtifactType($grp, $group_artifact_id);
            if (!$at || !is_object($at)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Could Not Get ArtifactType', 'getArtifactCCList');
            } elseif ($at->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $at->getErrorMessage(), 'getArtifactCCList');
            }

            $art_field_fact = new ArtifactFieldFactory($at);
            if (!$art_field_fact || !is_object($art_field_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldFactory', 'getArtifactCCList');
            } elseif ($art_field_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_field_fact->getErrorMessage(), 'getArtifactCCList');
            }

            $a = new Artifact($at, $artifact_id);
            if (!$a || !is_object($a)) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Could Not Get Artifact', 'getArtifactCCList');
            } elseif ($a->isError()) {
                return new SoapFault(GET_ARTIFACT_FAULT, $a->getErrorMessage(), 'getArtifactCCList');
            } elseif (! $a->userCanView()) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Permissions denied', 'getArtifactCCList');
            }

            return artifactCC_to_soap($group_id, $group_artifact_id, $artifact_id, $a->getCCList());
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'getArtifactCCList');
        }
    }

    function artifactCC_to_soap($group_id, $group_artifact_id, $artifact_id, $artifact_cc_list)
    {
        $return = array();
        $rows = db_numrows($artifact_cc_list);
        for ($i = 0; $i < $rows; $i++) {
            // retrieve the field, for permission checks
            $return[] = array(
            'artifact_cc_id' => db_result($artifact_cc_list, $i, 'artifact_cc_id'),
            'artifact_id' => $artifact_id,
            'email' => db_result($artifact_cc_list, $i, 'email'),
            'added_by' => db_result($artifact_cc_list, $i, 'added_by'),
            'added_by_name' => db_result($artifact_cc_list, $i, 'user_name'),
            'comment' => SimpleSanitizer::unsanitize(db_result($artifact_cc_list, $i, 'comment')),
            'date' => db_result($artifact_cc_list, $i, 'date')
            );
        }
        return $return;
    }

/**
 * addArtifactCC - add a list of emails or logins, with an optional CC comment
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the project we work with
 * @param int $group_artifact_id the ID of the tracker we want to add CC
 * @param int $artifact_id the artifact we want to add CC
 * @param string $cc_list the list of emails or logins to add
 * @param string $cc_comment the optional comment
 */
    function addArtifactCC($sessionKey, $group_id, $group_artifact_id, $artifact_id, $cc_list, $cc_comment)
    {
        global $art_field_fact;
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'addArtifactCC');
            } catch (SoapFault $e) {
                return $e;
            }

            $at = new ArtifactType($grp, $group_artifact_id);
            if (!$at || !is_object($at)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Could Not Get ArtifactType', 'addArtifactCC');
            } elseif ($at->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $at->getErrorMessage(), 'addArtifactCC');
            }

            $art_field_fact = new ArtifactFieldFactory($at);
            if (!$art_field_fact || !is_object($art_field_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldFactory', 'addArtifactCC');
            } elseif ($art_field_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_field_fact->getErrorMessage(), 'addArtifactCC');
            }

            $a = new Artifact($at, $artifact_id);
            if (!$a || !is_object($a)) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Could Not Get Artifact', 'addArtifactCC');
            } elseif ($a->isError()) {
                return new SoapFault(GET_ARTIFACT_FAULT, $a->getErrorMessage(), 'addArtifactCC');
            }
            $changes = array();
            if (! $ok = $a->addCC($cc_list, $cc_comment, $changes, false)) {
                return new SoapFault(ADD_CC_FAULT, 'CC could not be added', 'addArtifactCC');
            } else {
                return $ok;
            }
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'addArtifactCC');
        }
    }

/**
 * deleteArtifactCC - delete a CC of an artifact
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the project we work with
 * @param int $group_artifact_id the ID of the tracker we want to delete the CC
 * @param int $artifact_id the artifact we want to delete the CC
 * @param int $artifact_cc_id the id of the artifact_cc to delete
 */
    function deleteArtifactCC($sessionKey, $group_id, $group_artifact_id, $artifact_id, $artifact_cc_id)
    {
        global $art_field_fact;

        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'deleteArtifactCC');
            } catch (SoapFault $e) {
                return $e;
            }

            $at = new ArtifactType($grp, $group_artifact_id);
            if (!$at || !is_object($at)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Could Not Get ArtifactType', 'deleteArtifactCC');
            } elseif ($at->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $at->getErrorMessage(), 'deleteArtifactCC');
            }

            $art_field_fact = new ArtifactFieldFactory($at);
            if (!$art_field_fact || !is_object($art_field_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldFactory', 'deleteArtifactCC');
            } elseif ($art_field_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_field_fact->getErrorMessage(), 'deleteArtifactCC');
            }

            $a = new Artifact($at, $artifact_id);
            if (!$a || !is_object($a)) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Could Not Get Artifact', 'deleteArtifactCC');
            } elseif ($a->isError()) {
                return new SoapFault(GET_ARTIFACT_FAULT, $a->getErrorMessage(), 'deleteArtifactCC');
            }
            $changes = array();
            if (! $ok = $a->deleteCC($artifact_cc_id, $changes, false)) {
                return new SoapFault(DELETE_CC_FAULT, 'CC could not be deleted', 'deleteArtifactCC');
            } else {
                return $ok;
            }
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'deleteArtifactCC');
        }
    }

/**
 * getArtifactHistory - returns the array of ArtifactHistory of the artifact $artifact_id in the tracker $group_artifact_id of the project $group_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the history
 * @param int $group_artifact_id the ID of the tracker we want to retrieve the history
 * @param int $artifact_id the ID of the artifact we want to retrieve the history
 * @return array the array of the history of the artifact,
 *              or a soap fault if :
 *              - group_id does not match with a valid project,
 *              - group_artifact_id does not match with a valid tracker
 *              - artifact_id does not match with a valid artifact
 */
    function getArtifactHistory($sessionKey, $group_id, $group_artifact_id, $artifact_id)
    {
        global $art_field_fact;
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $grp = $pm->getGroupByIdForSoap($group_id, 'getArtifactHistory');
            } catch (SoapFault $e) {
                return $e;
            }

            $at = new ArtifactType($grp, $group_artifact_id);
            if (!$at || !is_object($at)) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, 'Could Not Get ArtifactType', 'getArtifactHistory');
            } elseif ($at->isError()) {
                return new SoapFault(GET_ARTIFACT_TYPE_FAULT, $at->getErrorMessage(), 'getArtifactHistory');
            }

            $art_field_fact = new ArtifactFieldFactory($at);
            if (!$art_field_fact || !is_object($art_field_fact)) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, 'Could Not Get ArtifactFieldFactory', 'getArtifactHistory');
            } elseif ($art_field_fact->isError()) {
                return new SoapFault(GET_ARTIFACT_FIELD_FACTORY_FAULT, $art_field_fact->getErrorMessage(), 'getArtifactHistory');
            }

            $a = new Artifact($at, $artifact_id);
            if (!$a || !is_object($a)) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Could Not Get Artifact', 'getArtifactHistory');
            } elseif ($a->isError()) {
                return new SoapFault(GET_ARTIFACT_FAULT, $a->getErrorMessage(), 'getArtifactHistory');
            } elseif (! $a->userCanView()) {
                return new SoapFault(GET_ARTIFACT_FAULT, 'Permissions denied', 'getArtifactHistory');
            }

            return history_to_soap($group_id, $group_artifact_id, $a->getHistory());
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'getArtifactHistory');
        }
    }

    function history_to_soap($group_id, $group_artifact_id, $history)
    {
        global $art_field_fact;

        $return = array();
        $rows = db_numrows($history);
        for ($i = 0; $i < $rows; $i++) {
            // retrieve the field, for permission checks
            $field_name = db_result($history, $i, 'field_name');
            $field = $art_field_fact->getFieldFromName($field_name);
            if ($field) {
                if ($field->userCanRead($group_id, $group_artifact_id)) {
                    $return[] = array(
                    //'artifact_history_id' => db_result($history, $i, 'artifact_history_id'),
                    //'artifact_id' => db_result($history, $i, 'artifact_id'),
                    'field_name' => db_result($history, $i, 'field_name'),
                    'old_value' => util_unconvert_htmlspecialchars(db_result($history, $i, 'old_value')),
                    'new_value' => util_unconvert_htmlspecialchars(db_result($history, $i, 'new_value')),
                    'modification_by' => db_result($history, $i, 'user_name'),
                    'date' => db_result($history, $i, 'date')
                    );
                }
            } else {
                // used to put non-field changes (e.g: cc list, follow-up comments, etc)
                 $field_name = db_result($history, $i, 'field_name');
                if (preg_match("/^(lbl_)/", $field_name) && preg_match("/(_comment)$/", $field_name)) {
                    $field_name = "comment";
                }
                $return[] = array(
                //'artifact_history_id' => db_result($history, $i, 'artifact_history_id'),
                //'artifact_id' => db_result($history, $i, 'artifact_id'),
                'field_name' => $field_name,
                'old_value' => util_unconvert_htmlspecialchars(db_result($history, $i, 'old_value')),
                'new_value' => util_unconvert_htmlspecialchars(db_result($history, $i, 'new_value')),
                'modification_by' => db_result($history, $i, 'user_name'),
                'date' => db_result($history, $i, 'date')
                );
            }
        }
        return $return;
    }



    $server->addFunction(
        array(
            'getTrackerList',
            'getArtifactType',
            'getArtifactTypes',
            'getArtifacts',
            'getArtifactsFromReport',
            'addArtifact',
            'addArtifactWithFieldNames',
            'updateArtifact',
            'updateArtifactWithFieldNames',
            'getArtifactFollowups',
            'getArtifactCannedResponses',
            'getArtifactReports',
            'getArtifactAttachedFiles',
            'getArtifactAttachedFile',
            'getArtifactById',
            'getArtifactDependencies',
            'getArtifactInverseDependencies',
            'addArtifactAttachedFile',
            'deleteArtifactAttachedFile',
            'addArtifactDependencies',
            'deleteArtifactDependency',
            'addArtifactFollowup',
            'updateArtifactFollowUp',
            'deleteArtifactFollowUp',
            'existArtifactSummary',
            'getArtifactCCList',
            'addArtifactCC',
            'deleteArtifactCC',
            'getArtifactHistory',
        )
    );
}
