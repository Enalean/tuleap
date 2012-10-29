<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

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
        'artifact_id'      => array('name' => 'artifact_id', 'type' => 'xsd:int'),
        'tracker_id'       => array('name' => 'tracker_id', 'type' => 'xsd:int'),
        'submitted_by'     => array('name' => 'submitted_by', 'type' => 'xsd:int'),
        'submitted_on'     => array('name' => 'submitted_on', 'type' => 'xsd:int'),
        'last_update_date' => array('name' => 'last_update_date', 'type' => 'xsd:int'),
        'value'            => array('name' => 'value', 'type' => 'tns:ArrayOfArtifactFieldValue')
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
    'CriteriaValueDate',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'op'      => array('type' => 'xsd:string'),
        'to_date' => array('type' => 'xsd:int'),
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'CriteriaValueDateAdvanced',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'from_date' => array('type' => 'xsd:int'),
        'to_date'   => array('type' => 'xsd:int'),
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'CriteriaValue',
    'complexType',
    'struct',
    'choice',
    '',
    array(
        'value'        => array('type' => 'xsd:string'),
        'date'         => array('type' => 'tns:CriteriaValueDate'),
        'dateAdvanced' => array('type' => 'tns:CriteriaValueDateAdvanced'),
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'Criteria',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'field_name' => array('type' => 'xsd:string'),
        'value'      => array('type' => 'tns:CriteriaValue'),
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

//$GLOBALS['server']->wsdl->addComplexType(
//    'ArtifactHistory',
//    'complexType',
//    'struct',
//    'sequence',
//    '',
//    array(
//        'artifact_id' => array('name'=>'artifact_id', 'type' => 'xsd:int'),
//        'changeset_id' => array('name'=>'changeset_id', 'type' => 'xsd:int'),
//        'changes' => array('name'=>'changes', 'type' => 'tns:ArrayOfString'),
//        'modification_by' => array('name'=>'modification_by', 'type' => 'xsd:string'),
//        'date' => array('name'=>'date', 'type' => 'xsd:int'),
//        'comment' => array('name'=>'comment', 'type' => 'tns:ArtifactFollowup')
//    )
//);
//
//$GLOBALS['server']->wsdl->addComplexType(
//    'ArrayOfArtifactHistory',
//    'complexType',
//    'array',
//    '',
//    'SOAP-ENC:Array',
//    array(),
//    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:ArtifactHistory[]')),
//    'tns:ArtifactHistory'
//);

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

$GLOBALS['server']->wsdl->addComplexType(
    'TrackerSemanticTitle',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'field_name' => array('name'=>'field_name', 'type' => 'xsd:string')
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'TrackerSemanticStatus',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'field_name' => array('name'=>'field_name', 'type' => 'xsd:string'),
        'values' => array('name'=>'values', 'type' => 'xsd:int[]')
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'TrackerSemanticContributor',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
       'field_name' => array('name'=>'field_name', 'type' => 'xsd:string')
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'TrackerSemantic',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'title'        => array('name'=>'title', 'type' => 'tns:TrackerSemanticTitle'),
        'status'       => array('name'=>'status', 'type' => 'tns:TrackerSemanticStatus'),
        'contributor'  => array('name'=>'contributor', 'type' => 'tns:TrackerSemanticContributor'),
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'TrackerStructure',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
       'semantic' => array('name'=>'semantic', 'type' => 'tns:TrackerSemantic')
    )
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
    array('return'=>'tns:ArrayOfTrackerField'), // output parameters
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
        'comment' => 'xsd:string',
        'comment_format' => 'xsd:string'
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
    'Returns the artifact (Artifact) identified by the id artifact_id
     Returns a soap fault if the group_id is not a valid one, if the tracker_id is not a valid one,
     or if the artifact_id is not a valid one.'
);

$GLOBALS['server']->register(
    'getTrackerStructure',
    array('sessionKey'=>'xsd:string',
          'group_id'=>'xsd:int',
          'tracker_id'=>'xsd:int',
    ),
    array('return'=>'tns:TrackerStructure'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#getTrackerStructure',
    'rpc',
    'encoded',
    'Returns the tracker structure.'
);

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

?>
