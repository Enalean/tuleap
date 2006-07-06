<?php
  // define fault code constants
  define ('get_group_fault', '3000');
  define ('invalid_session_fault', '3001');
  define ('get_artifact_type_factory_fault', '3002');
  define ('get_artifact_factory_fault', '3003');
  define ('get_artifact_field_factory_fault', '3004');	
  define ('get_artifact_type_fault', '3005');
  define ('get_artifacts_fault', '3006');
  define ('create_artifact_fault', '3007');
  define ('permission_denied_fault', '3008');
  define ('invalid_field_dependency_fault', '3009');
  define ('update_artifact_fault', '3010');
  define ('get_artifact_file_fault', '3011');
  define ('add_dependency_fault', '3012');
  define ('delete_dependency_fault', '3013');
  define ('create_followup_fault', '3014');
  
  require_once ('nusoap/lib/nusoap.php');
  require_once ('pre.php');
  require_once ('session.php');
  require_once ('common/include/Error.class');
  require_once ('common/tracker/ArtifactType.class');
  require_once ('common/tracker/ArtifactTypeFactory.class');
  require_once ('common/tracker/Artifact.class');
  require_once ('common/tracker/ArtifactFactory.class');
  require_once ('common/tracker/ArtifactField.class');
  require_once ('common/tracker/ArtifactFieldFactory.class');
  require_once ('common/tracker/ArtifactFieldSet.class');
  require_once ('common/tracker/ArtifactFieldSetFactory.class');
  require_once ('common/tracker/ArtifactReportFactory.class');
  require_once ('www/tracker/include/ArtifactFieldHtml.class');
  
  
  // Create the server instance
  $server = new soap_server();
  
  // Initialize WSDL support
  $server->configureWSDL('CodeXTrackerwsdl', 'urn:CodeXTrackerwsdl');
  
  // Register the data structures used by the services
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
		'scope' => array('name' => 'description', 'type' => 'xsd:string'),
		'required' => array('name' => 'required', 'type' => 'xsd:int'),
		'empty_ok' => array('name' => 'empty_ok', 'type' => 'xsd:int'),
		'keep_history' => array('name' => 'empty_ok', 'type' => 'xsd:int'),
		'special' => array('name' => 'empty_ok', 'type' => 'xsd:int'),
		'value_function' => array('name' => 'value_function', 'type' => 'xsd:string'),
		'available_values' => array('name' => 'available_values', 'type' => 'tns:ArrayOfArtifactFieldValueList'),
		'default_value' => array('name' => 'default_selected', 'type' => 'xsd:string'),
		'user_can_submit' => array('name' => 'user_can_submit', 'type' => 'xsd:boolean'),
		'user_can_update' => array('name' => 'user_can_update', 'type' => 'xsd:boolean'),
		'user_can_read'   => array('name' => 'user_can_read', 'type' => 'xsd:boolean')
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
		'artifact_id' => array('name'=>'group_artifact_id', 'type' => 'xsd:int'),
		'field_value' => array('name' => 'field_name', 'type' => 'xsd:string')
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
	'title' 	     => array('name'=>'title', 'type' => 'xsd:string'),
	'body' 		     => array('name'=>'body', 'type' => 'xsd:string')
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
	'artifact_id'	      => array('name'=>'artifact_id', 'type' => 'xsd:int'),		
	'comment' 	      => array('name'=>'comment', 'type' => 'xsd:string'),
	'date' 	      	      => array('name'=>'date', 'type' => 'xsd:int'),
	'by' 		      => array('name'=>'by', 'type' => 'xsd:string'),
	'comment_type_id'     => array('name'=>'type', 'type' => 'xsd:int'),	
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
	'report_id' 	      => array('name'=>'report_id', 'type' => 'xsd:int'),
	'group_artifact_id'   => array('name'=>'group_artifact_id', 'type' => 'xsd:int'),
	'user_id' 	      => array('name'=>'user_id', 'type' => 'xsd:int'),
	'name' 	      	      => array('name'=>'name', 'type' => 'xsd:string'),
	'description' 	      => array('name'=>'description', 'type' => 'xsd:string'),
	'scope' 	      => array('name'=>'scope', 'type' => 'xsd:string'),
	'fields'	      => array('name'=>'fields', 'type' => 'tns:ArrayOfArtifactReportField')	
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
	'report_id' 	      => array('name'=>'report_id', 'type' => 'xsd:int'),
	'field_name'          => array('name'=>'field_name', 'type' => 'xsd:string'),
	'show_on_query'       => array('name'=>'user_id', 'type' => 'xsd:int'),
	'show_on_result'      => array('name'=>'name', 'type' => 'xsd:int'),
	'place_query' 	      => array('name'=>'description', 'type' => 'xsd:int'),
	'place_result' 	      => array('name'=>'scope', 'type' => 'xsd:int'),
	'col_width'	      => array('name'=>'query_fields', 'type' => 'xsd:int')	
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
  
  // Register the methods to expose
  $server->register(
	'getArtifactTypes',			      	              // method name
	array('sessionKey'=>'xsd:string',			      // input parameters 		
	      'group_id'=>'xsd:int',
	      'user_id'=>'xsd:int'
	),
	array('return'=>'tns:ArrayOfArtifactType'),                   // output parameters
	'urn:CodeXTrackerwsdl',					      // namespace
	'urn:CodeXTrackerwsdl#getArtifactTypes',		      // soapaction	
	'rpc',							      // style	
	'encoded',						      // use
	'Get Artifact Types By Group Id'		              // documentation	
  );
  
  $server->register(
	'getArtifacts',                                               // method name
	array('sessionKey'=>'xsd:string',                             // input parameters
	      'group_id'=>'xsd:int',
	      'group_artifact_id'=>'xsd:int',
	      'user_id' => 'xsd:int',
	      'criteria' => 'tns:ArrayOfCriteria'
	),
	array('return'=>'tns:ArrayOfArtifact'),                       // output parameters
	'urn:CodeXTrackerwsdl',                                       // namespace
	'urn:CodeXTrackerwsdl#getArtifacts',                          // soapaction
	'rpc',							      // style	
	'encoded',						      // use	
	'Get Artifacts'                         		      // documentation	
  );
 
  $server->register(
	'addArtifact',						      // method name	
	array(	'sessionKey'=>'xsd:string',			      // input parameters	
		'group_id'=>'xsd:int',
		'group_artifact_id'=>'xsd:int',
		'user_id'=>'xsd:int',
		'status_id' =>'xsd:int',
		'submitted_by'=>'xsd:int',
		'open_date'=>'xsd:int',
		'close_date'=>'xsd:int',
		'summary' =>'xsd:string',
		'details'=>'xsd:string',
		'severity'=>'xsd:int',
		'extra_fields'=>'tns:ArrayOfArtifactFieldValue'
	),
	array('return'=>'xsd:int'),				      // output parameters	
	'urn:CodeXTrackerwsdl',					      // namespace	
	'urn:CodeXTrackerwsdl#addArtifact',			      // soapaction	
	'rpc',							      // style		
	'encoded',						      // use	
	'add Artifact'						      // documentation	
  );
  
  $server->register(
	'updateArtifact',					      // method name		
	array(	'sessionKey'=>'xsd:string',			      // input parameters	 	
		'group_id'=>'xsd:int',
		'group_artifact_id'=>'xsd:int',
		'user_id'=>'xsd:int',
		'artifact_id'=>'xsd:int',
		'artifact_id_dependent'=>'xsd:string',
		'canned_response'=>'xsd:int',
		'status_id'=>'xsd:int',
		'submitted_by'=>'xsd:int', 
		'open_date'=>'xsd:int', 
		'close_date'=>'xsd:int', 
		'summary'=>'xsd:string', 
		'details'=>'xsd:string', 
		'severity'=>'xsd:int', 
		'extra_fields'=>'tns:ArrayOfArtifactFieldValue'
	),
	array('return'=>'xsd:int'),				      // output parameters	
	'urn:CodeXTrackerwsdl',					      // namespace	 
	'urn:CodeXTrackerwsdl#updateArtifact',			      // soapaction		
	'rpc',							      // style			
	'encoded',						      // use		
	'update an artifact'					      // documentation		
  );
  
  $server->register(
	'getArtifactFollowups',					      // method name		
	array(	'sessionKey'=>'xsd:string',			      // input parameters	 	
		'group_id'=>'xsd:int',
		'group_artifact_id'=>'xsd:int',
		'artifact_id'=>'xsd:int'
	),
	array('return'=>'tns:ArrayOfArtifactFollowup'),		      // output parameters	
	'urn:CodeXTrackerwsdl',					      // namespace	 
	'urn:CodeXTrackerwsdl#getArtifactFollowups',		      // soapaction		
	'rpc',							      // style			
	'encoded',						      // use		
	'get followups of an artifact'				      // documentation		
  );
  
  $server->register(
	'getArtifactCannedResponses',				      // method name		
	array(	'sessionKey'=>'xsd:string',			      // input parameters	 	
		'group_id'=>'xsd:int',
		'group_artifact_id'=>'xsd:int'
	),
	array('return'=>'tns:ArrayOfArtifactCanned'),		      // output parameters	
	'urn:CodeXTrackerwsdl',					      // namespace	 
	'urn:CodeXTrackerwsdl#getArtifactCannedResponses',	      // soapaction		
	'rpc',							      // style			
	'encoded',						      // use		
	'get Responses Types defined For this Tracker'		      // documentation		
  );
  
  $server->register(
	'getArtifactReports',					      // method name		
	array(	'sessionKey'=>'xsd:string',			      // input parameters	 	
		'group_id'=>'xsd:int',
		'group_artifact_id'=>'xsd:int',
		'user_id' => 'xsd:int'
	),
	array('return'=>'tns:ArrayOfArtifactReport'),		      // output parameters	
	'urn:CodeXTrackerwsdl',					      // namespace	 
	'urn:CodeXTrackerwsdl#getArtifactReports',			      // soapaction		
	'rpc',							      // style			
	'encoded',						      // use		
	'get Reports For this Tracker'		      		      // documentation		
  );
  
  $server->register(
	'getAttachedFiles',					       // method name		
	array('sessionKey'=>'xsd:string',                              // input parameters				
	      'group_id'=>'xsd:int',  
	      'group_artifact_id'=>'xsd:int',
	      'artifact_id'=>'xsd:int'
	),
	array('return'=>'tns:ArrayOfArtifactFile'),		       // output parameters	
	'urn:CodeXTrackerwsdl',					       // namespace	
	'urn:CodeXTrackerwsdl#getAttachedFiles',		       // soapaction
	'rpc',							       // style	
	'encoded',						       // use
	'get The Attached Files'				       // documentation	
  );
  
  $server->register(
	'getArtifactById',					       // method name		
	array('sessionKey'=>'xsd:string',                              // input parameters				
	      'group_id'=>'xsd:int',  
	      'group_artifact_id'=>'xsd:int',
	      'artifact_id'=>'xsd:int'
	),
	array('return'=>'tns:Artifact'),		               // output parameters	
	'urn:CodeXTrackerwsdl',					       // namespace	
	'urn:CodeXTrackerwsdl#getArtifactById',		               // soapaction
	'rpc',							       // style	
	'encoded',						       // use
	'get Artifact By Id'     				       // documentation	
  );
  
  $server->register(
	'getDependancies',					       // method name		
	array('sessionKey'=>'xsd:string',                              // input parameters				
	      'group_id'=>'xsd:int',  
	      'group_artifact_id'=>'xsd:int',
	      'artifact_id'=>'xsd:int'
	),
	array('return'=>'tns:ArrayOfArtifactDependence'),	       // output parameters	
	'urn:CodeXTrackerwsdl',					       // namespace	
	'urn:CodeXTrackerwsdl#getDependancies',		               // soapaction
	'rpc',							       // style	
	'encoded',						       // use
	'get the artifact dependencies'				       // documentation	
  );
  
  $server->register(
	'addArtifactFile',						// method name		
	array(	'sessionKey'=>'xsd:string',				// input parameters	
		'group_id'=>'xsd:int',
		'group_artifact_id'=>'xsd:int',
		'artifact_id'=>'xsd:int',
		'bin_data'=>'xsd:string',
		'description'=>'xsd:string',
		'filename'=>'xsd:string',
		'filetype'=>'xsd:string'
	),
	array('return'=>'xsd:int'),					// output parameters	
	'urn:CodeXTrackerwsdl',						// namespace
	'urn:CodeXTrackerwsdl#addArtifactFile',				// soapaction
	'rpc',								// style	
	'encoded',							// use
	'attach a file to an artifact'					// documentation	
	
  );
  
  $server->register(
	'deleteArtifactFile',						// method name		
	array(	'sessionKey'=>'xsd:string',				// input parameters	
		'group_id'=>'xsd:int',
		'group_artifact_id'=>'xsd:int',
		'artifact_id'=>'xsd:int',
		'file_id'=>'xsd:int'
	),
	array('return'=>'xsd:int'),					// output parameters	
	'urn:CodeXTrackerwsdl',						// namespace
	'urn:CodeXTrackerwsdl#deleteArtifactFile',			// soapaction
	'rpc',								// style	
	'encoded',							// use
	'detach a file from an artifact'				// documentation	
	
  );
  
  $server->register(
	'addDependencies',						// method name		
	array(	'sessionKey'=>'xsd:string',				// input parameters	
		'group_id'=>'xsd:int',
		'group_artifact_id'=>'xsd:int',
		'artifact_id'=>'xsd:int',
		'is_dependent_on_artifact_id'=>'tns:ArrayOfInt'
	),
	array(),							// output parameters	
	'urn:CodeXTrackerwsdl',						// namespace
	'urn:CodeXTrackerwsdl#addDependencies',				// soapaction
	'rpc',								// style	
	'encoded',							// use
	'add a list of dependencies'   					// documentation	
	
  );
  
  $server->register(
	'deleteDependency',						// method name		
	array(	'sessionKey'=>'xsd:string',				// input parameters	
		'group_id'=>'xsd:int',
		'group_artifact_id'=>'xsd:int',
		'artifact_id'=>'xsd:int',
		'dependent_on_artifact_id'=>'xsd:int'
	),
	array('return'=>'xsd:int'),					// output parameters	
	'urn:CodeXTrackerwsdl',						// namespace
	'urn:CodeXTrackerwsdl#deleteDependence',			// soapaction
	'rpc',								// style	
	'encoded',							// use
	'delete a dependence'						// documentation	
	
  );
  
  $server->register(
	'addFollowup',							// method name		
	array(	'sessionKey' => 'xsd:string',				// input parameters	
		'group_id' => 'xsd:int',
		'group_artifact_id' => 'xsd:int',
		'artifact_id' => 'xsd:int',
		'body' => 'xsd:string'
	),
	array(),							// output parameters	
	'urn:CodeXTrackerwsdl',						// namespace
	'urn:CodeXTrackerwsdl#addFollowup',				// soapaction
	'rpc',								// style	
	'encoded',							// use
	'add a followup'						// documentation	
	
  );
  
  $server->register(
	'existSummary',							// method name		
	array(	'sessionKey' => 'xsd:string',				// input parameters	
		'group_artifact_id' => 'xsd:int',
		'summary' => 'xsd:string'
	),
	array('return'=>'xsd:int'),					// output parameters	
	'urn:CodeXTrackerwsdl',						// namespace
	'urn:CodeXTrackerwsdl#existSummary',				// soapaction
	'rpc',								// style	
	'encoded',							// use
	'check if an artifact was double-submitted'			// documentation	
	
  );
  
  // Define the methods as a PHP function
  function &getArtifactTypes($sessionKey, $group_id) {
	if (session_continue($sessionKey)){
	
	   $group =& group_get_object($group_id);
	   if (!$group || !is_object($group)) {
		return new soap_fault (get_group_fault,'getArtifactTypes','Could Not Get Group','Could Not Get Group');
	   } elseif ($group->isError()) {
		return new soap_fault (get_group_fault, 'getArtifactTypes', '$group->getErrorMessage()',$group->getErrorMessage());
	   }

	   $atf = new ArtifactTypeFactory($group);
	   if (!$atf || !is_object($atf)) {
		return new soap_fault (get_artifact_type_factory_fault, 'getArtifactTypes', 'Could Not Get ArtifactTypeFactory','Could Not Get ArtifactTypeFactory');
	   } elseif ($atf->isError()) {
		return new soap_fault (get_artifact_type_factory_fault, 'getArtifactTypes', $atf->getErrorMessage(),          $atf->getErrorMessage());
	   }
	   
	   return artifacttypes_to_soap($atf->getArtifactTypes());
	   
	} else
    	   return new soap_fault (invalid_session_fault,'getArtifactTypes','Invalide Session ','');
	
  }
  
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
                	   return new soap_fault (get_artifact_type_fault, 'getArtifactTypes', 'ArtifactType could not be created','ArtifactType could not be created');
                      }
        	      if ($ath->isError()) {
                	   return new soap_fault (get_artifact_type_fault, 'getArtifactTypes', $ath->getErrorMessage(),$_ath->getErrorMessage());
        	      }
        	      // Check if this tracker is valid (not deleted)
                      if ( !$ath->isValid() ) {
                           return new soap_fault (get_artifact_type_fault, 'getArtifactTypes', 'This tracker is no longer valid.','This tracker is no longer valid.');
                      }
		      // Check if the user can view this tracker
		      if ($ath->userCanView($user_id)) {
		      
	   	      	  $art_fieldset_fact = new ArtifactFieldSetFactory($at_arr[$i]);
	   		  if (!$art_fieldset_fact || !is_object($art_fieldset_fact)) {
	        	      return new soap_fault (get_artifact_field_factory_fault, 'getFieldSets', 'Could Not Get ArtifactFieldSetFactory','Could Not Get ArtifactFieldSetFactory');
	   		  } elseif ($art_fieldset_fact->isError()) {
	        	      return new soap_fault (get_artifact_field_factory_fault, 'getFieldSets', $art_fieldset_fact->getErrorMessage(),$art_fieldset_fact->getErrorMessage());
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
		      	      			$cols=@mysql_num_fields($result);
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
			      			if (($field->isMultiSelectBox() || $field->isSelectBox()) 
			      				&& ($field->getValueFunction())) {
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
		      	      			$fields[] = array(
				      			'field_id' => $field->getID(),
				      			'group_artifact_id' => $at_arr[$i]->getID(),
				      			'field_set_id' => $field->getFieldSetID(), 
				      			'field_name' => $field->getName(),
				      			'data_type' => $field->getDataType(),
				      			'display_type' => $field->getDisplayType(),
				      			'display_size' => $field->getDisplaySize(),
				      			'label'	=> $field->getLabel(),
				      			'description' => $field->getDescription(),
				      			'scope' => $field->getScope(),
				      			'required' => $field->getRequired(),
				      			'empty_ok' => $field->getEmptyOk(),
				      			'keep_history' => $field->getKeepHistory(),
				      			'special' => $field->getSpecial(),
				     			'value_function' => $field->getValueFunction(),
				      			'available_values' => $availablevalues,
				      			'default_value' => $field->getDefaultValue(),
				      			'user_can_submit' => $field->userCanSubmit($group_id,$group_artifact_id,$user_id),
				      			'user_can_read' => $field->userCanRead($group_id,$group_artifact_id,$user_id),
				      			'user_can_update' => $field->userCanUpdate($group_id,$group_artifact_id,$user_id)	
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
				'open_count' => $at_arr[$i]->getOpenCount(),
				'total_count' => $at_arr[$i]->getTotalCount(),
				'total_file_size' => db_result($result, 0, 0),
				'field_sets' => $field_sets
		          );
                      }
            }
	}
	return $return;
  }
  
  function getArtifacts($sessionKey,$group_id,$group_artifact_id, $user_id, $criteria) {	
	global $art_field_fact; 
	if (session_continue($sessionKey)){

	   $grp =& group_get_object($group_id);
	   if (!$grp || !is_object($grp)) {
		return new soap_fault (get_group_fault,'getArtifacts','Could Not Get Group','Could Not Get Group');
	   } elseif ($grp->isError()) {
		return new soap_fault (get_group_fault,'getArtifacts',$grp->getErrorMessage(),$grp->getErrorMessage());
	   }

 	   $at = new ArtifactType($grp,$group_artifact_id);
	   if (!$at || !is_object($at)) {
		return new soap_fault (get_artifact_type_fault,'getArtifacts','Could Not Get ArtifactType','Could Not Get ArtifactType');
	   } elseif ($at->isError()) {
		return new soap_fault (get_artifact_type_fault,'getArtifacts',$at->getErrorMessage(),$at->getErrorMessage());
	   }

	   $art_field_fact = new ArtifactFieldFactory($at);
	   if (!$art_field_fact || !is_object($art_field_fact)) {
	        return new soap_fault (get_artifact_field_factory_fault, 'getArtifactTypes', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
	   } elseif ($art_field_fact->isError()) {
	        return new soap_fault (get_artifact_field_factory_fault, 'getArtifactTypes', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
	   }
	   
	   $af = new ArtifactFactory($at);
	   if (!$af || !is_object($af)) {
		return new soap_fault (get_artifact_factory_fault,'getArtifacts','Could Not Get ArtifactFactory','Could Not Get ArtifactFactory');
	   } elseif ($af->isError()) {
		return new soap_fault (get_artifact_factory_fault,'getArtifacts',$atf->getErrorMessage(),$atf->getErrorMessage());
	   }
	   
	   $artifacts = $af->getArtifacts($user_id, $criteria);
	   return artifacts_to_soap($artifacts); 
	} else
    	   return new soap_fault (invalid_session_fault,'getArtifactTypes','Invalide Session ','');	
  }
  
  function artifacts_to_soap($at_arr) {
  	$return = array();
	//if (is_array($at_arr) && count($at_arr))
	    foreach ($at_arr as $atid => $artifact){
		if ($artifact->isError()) {
			//skip if error
		} else {
			$extrafieldvalues = array();
			$extrafielddata   = $artifact->getExtraFieldData();
			if(is_array($extrafielddata) && count($extrafielddata) > 0 ) {
			    while(list($field_id, $value) = each($extrafielddata)) {
				$extrafieldvalues[] = array (	
							'field_id'    => $field_id,
							'artifact_id' => $atid,
							'field_value' => $value
				);
			    }
			}
			$return[]=array(
				'artifact_id'=> $artifact->data_array['artifact_id'],
				'group_artifact_id'=>$artifact->data_array['group_artifact_id'],
				'status_id'=>$artifact->data_array['status_id'],
				'submitted_by'=>$artifact->data_array['submitted_by'],
				'open_date'=>$artifact->data_array['open_date'],
				'close_date'=>$artifact->data_array['close_date'],
				'summary'=>$artifact->data_array['summary'],
				'details'=>$artifact->data_array['details'],
				'severity'=>$artifact->data_array['severity'],
				'extra_fields'=>$extrafieldvalues
			);
		}
	    }
	return $return;
  }
  
  function getArtifactById($sessionKey,$group_id,$group_artifact_id, $artifact_id) {	
	global $art_field_fact, $ath; 
	if (session_continue($sessionKey)){
	
		$grp =& group_get_object($group_id);
		if (!$grp || !is_object($grp)) {
			return new soap_fault (get_group_fault,'getArtifactById','Could Not Get Group','Could Not Get Group');
		} elseif ($grp->isError()) {
			return new soap_fault (get_group_fault,'getArtifactById',$grp->getErrorMessage(),$grp->getErrorMessage());
		}

		$ath = new ArtifactType($grp, $group_artifact_id);
        	if (!$ath || !is_object($ath)) {
                	return new soap_fault (get_artifact_type_fault, 'getArtifactById', 'ArtifactType could not be created','ArtifactType could not be created');
                }
        	if ($ath->isError()) {
                	return new soap_fault (get_artifact_type_fault, 'getArtifactById', $ath->getErrorMessage(),$_ath->getErrorMessage());
        	}
        	// Check if this tracker is valid (not deleted)
                if ( !$ath->isValid() ) {
                        return new soap_fault (get_artifact_type_fault, 'getArtifactById', 'This tracker is no longer valid.','This tracker is no longer valid.');
                }
		
		$art_field_fact = new ArtifactFieldFactory($ath);
	   	if (!$art_field_fact || !is_object($art_field_fact)) {
	        	return new soap_fault (get_artifact_field_factory_fault, 'getArtifactById', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
	   	} elseif ($art_field_fact->isError()) {
	        	return new soap_fault (get_artifact_field_factory_fault, 'getArtifactById', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
	   	}
	   	
	   	$a = new Artifact($ath, $artifact_id);
		if (!$a || !is_object($a)) {
			return new soap_fault (get_artifact_fault, 'getArtifactById', 'Could Not Get Artifact', 'Could Not Get Artifact');
		} elseif ($a->isError()) {
			return new soap_fault (get_artifact_fault, 'getArtifactById', $a->getErrorMessage(), $a->getErrorMessage());
		}
		return artifact_to_soap($a);
		
	} else
    	   return new soap_fault (invalid_session_fault,'getArtifactById','Invalide Session ','');	
  }
  
  function artifact_to_soap($artifact) {
  	$extrafieldvalues = array();
	$extrafielddata   = $artifact->getExtraFieldData();
	if(is_array($extrafielddata) && count($extrafielddata) > 0 ) {
		while(list($field_id, $value) = each($extrafielddata)) {
			$extrafieldvalues[] = array (	
				'field_id'    => $field_id,
				'artifact_id' => $atid,
				'field_value' => $value
			);
		}
	}
  	$return=array(
		'artifact_id'=> $artifact->data_array['artifact_id'],
		'group_artifact_id'=>$artifact->data_array['group_artifact_id'],
		'status_id'=>$artifact->data_array['status_id'],
		'submitted_by'=>$artifact->data_array['submitted_by'],
		'open_date'=>$artifact->data_array['open_date'],
		'close_date'=>$artifact->data_array['close_date'],
		'summary'=>$artifact->data_array['summary'],
		'details'=>$artifact->data_array['details'],
		'severity'=>$artifact->data_array['severity'],
		'extra_fields'=>$extrafieldvalues
	);
	return $return;
  }
  
  function setArtifactData($status_id, $submitted_by, $open_date, $close_date, $summary, $details, $severity, $extra_fields) {
  	global $art_field_fact; 

  	$data = array();
  	// set standard fields data
  	if (isset($status_id)) 	  $data ['status_id']    =  $status_id;
  	if (isset($submitted_by)) $data ['submitted_by'] =  $submitted_by;
  	if (isset($open_date))    $data ['open_date']    =  $open_date;
  	if (isset($close_date))   $data ['close_date']   =  $close_date;
  	if (isset($summary))      $data ['summary']      =  $summary;
  	if (isset($details))      $data ['details']      =  $details;
  	if (isset($severity))     $data ['severity']     =  $severity;
  	
  	// set extra fields data
  	if (is_array($extra_fields) && count($extra_fields) > 0)
  	    foreach ($extra_fields as $e => $extra_field) {
  	    
  		$field = $art_field_fact->getFieldFromId($extra_field['field_id']);
  		if ($field->isStandardField())
  			continue;
  		else {
			
			if ($field->isMultiSelectBox()) {
				$value = explode(",", $extra_field['field_value']);
				$data [$field->getName()] = $value;
			} else
				$data [$field->getName()] = $extra_field['field_value'];
		     }
  	    }
  	
  	return $data;
  } 
  
  function addArtifact($sessionKey ,$group_id, $group_artifact_id, $user_id, $status_id, $submitted_by, $open_date, $close_date, $summary, $details, $severity, $extra_fields) {
	global $art_field_fact, $ath; 
	if (session_continue($sessionKey)){

		$grp =& group_get_object($group_id);
		if (!$grp || !is_object($grp)) {
			return new soap_fault (get_group_fault,'addArtifact','Could Not Get Group','Could Not Get Group');
		} elseif ($grp->isError()) {
			return new soap_fault (get_group_fault,'addArtifact',$grp->getErrorMessage(),$grp->getErrorMessage());
		}
		
		$ath = new ArtifactType($grp, $group_artifact_id);
        	if (!$ath || !is_object($ath)) {
                	return new soap_fault (get_artifact_type_fault, 'addArtifact', 'ArtifactType could not be created','ArtifactType could not be created');
                }
        	if ($ath->isError()) {
                	return new soap_fault (get_artifact_type_fault, 'addArtifact', $ath->getErrorMessage(),$_ath->getErrorMessage());
        	}
        	// Check if this tracker is valid (not deleted)
                if ( !$ath->isValid() ) {
                        return new soap_fault (get_artifact_type_fault, 'addArtifact', 'This tracker is no longer valid.','This tracker is no longer valid.');
                }

		// check the user if he can submit artifacts for this tracker
		if (!$ath->userCanSubmit($user_id))
		{
			return new soap_fault(permission_denied_fault, 'addArtifact', 'Permission Denied', 'You are not granted sufficient permission to perform this operation.');
		}
		
		$art_field_fact = new ArtifactFieldFactory($ath);
	   	if (!$art_field_fact || !is_object($art_field_fact)) {
	        	return new soap_fault (get_artifact_field_factory_fault, 'addArtifact', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
	   	} elseif ($art_field_fact->isError()) {
	        	return new soap_fault (get_artifact_field_factory_fault, 'addArtifact', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
	   	}
	   	$a = new Artifact($ath);
		if (!$a || !is_object($a)) {
			return new soap_fault (get_artifact_fault, 'addArtifact', 'Could Not Get Artifact', 'Could Not Get Artifact');
		} elseif ($a->isError()) {
			return new soap_fault (get_artifact_fault, 'addArtifact', $a->getErrorMessage(), $a->getErrorMessage());
		}
                
		$data = setArtifactData($status_id, $submitted_by, $open_date, $close_date, $summary, $details, $severity, $extra_fields);
		
		//Check Field Dependencies
                require_once('common/tracker/ArtifactRulesManager.class');
                $arm =& new ArtifactRulesManager();
                if (!$arm->validate($ath->getID(), $data, $art_field_fact)) {
                      return new soap_fault (invalid_field_dependency_fault, 'addArtifact', 'Invalid Field Dependency', 'Invalid Field Dependency');
                }
                
		if (!$a->create($data)) {
			return new soap_fault (create_artifact_fault,'addArtifact',$a->getErrorMessage(),$a->getErrorMessage());
		} else {
			return $a->getID();
		}

	} else
    	   return new soap_fault (invalid_session_fault,'addArtifact','Invalide Session ','');	
  }
  
  function updateArtifact($sessionKey ,$group_id, $group_artifact_id, $user_id, $artifact_id, $artifact_id_dependent, $canned_response, $status_id, $submitted_by, $open_date, $close_date, $summary, $details, $severity, $extra_fields) {
  	global $art_field_fact, $ath; 
	if (session_continue($sessionKey)){
	
		$grp =& group_get_object($group_id);
		if (!$grp || !is_object($grp)) {
			return new soap_fault (get_group_fault,'updateArtifact','Could Not Get Group','Could Not Get Group');
		} elseif ($grp->isError()) {
			return new soap_fault (get_group_fault,'updateArtifact',$grp->getErrorMessage(),$grp->getErrorMessage());
		}

		$ath = new ArtifactType($grp, $group_artifact_id);
        	if (!$ath || !is_object($ath)) {
                	return new soap_fault (get_artifact_type_fault, 'updateArtifact', 'ArtifactType could not be created','ArtifactType could not be created');
                }
        	if ($ath->isError()) {
                	return new soap_fault (get_artifact_type_fault, 'updateArtifact', $ath->getErrorMessage(),$_ath->getErrorMessage());
        	}
        	// Check if this tracker is valid (not deleted)
                if ( !$ath->isValid() ) {
                        return new soap_fault (get_artifact_type_fault, 'updateArtifact', 'This tracker is no longer valid.','This tracker is no longer valid.');
                }
		
		$art_field_fact = new ArtifactFieldFactory($ath);
	   	if (!$art_field_fact || !is_object($art_field_fact)) {
	        	return new soap_fault (get_artifact_field_factory_fault, 'updateArtifact', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
	   	} elseif ($art_field_fact->isError()) {
	        	return new soap_fault (get_artifact_field_factory_fault, 'updateArtifact', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
	   	}
	   	
	   	$a = new Artifact($ath, $artifact_id);
		if (!$a || !is_object($a)) {
			return new soap_fault (get_artifact_fault, 'updateArtifact', 'Could Not Get Artifact', 'Could Not Get Artifact');
		} elseif ($a->isError()) {
			return new soap_fault (get_artifact_fault, 'updateArtifact', $a->getErrorMessage(), $a->getErrorMessage());
		}
                
		$data = setArtifactData($status_id, $submitted_by, $open_date, $close_date, $summary, $details, $severity, $extra_fields);
		
		//Check Field Dependencies
                require_once('common/tracker/ArtifactRulesManager.class');
                $arm =& new ArtifactRulesManager();
                if (!$arm->validate($ath->getID(), $data, $art_field_fact)) {
                      return new soap_fault (invalid_field_dependency_fault, 'updateArtifact', 'Invalid Field Dependency', 'Invalid Field Dependency');
                }
                
		if (!$a->handleUpdate($artifact_id_dependent, $canned_response, $changes, false, $data, true)) {
			return new soap_fault (update_artifact_fault, 'updateArtifact', $a->getErrorMessage(), $a->getErrorMessage());
		} else {
			return $a->getID();
		}
		
	} else
    	   return new soap_fault (invalid_session_fault,'updateArtifact','Invalide Session ','');
	
  }
  
  function &getArtifactFollowups ($sessionKey, $group_id, $group_artifact_id, $artifact_id) {
  	global $art_field_fact; 
  	if (session_continue($sessionKey)){
  		$grp =& group_get_object($group_id);
		if (!$grp || !is_object($grp)) {
			return new soap_fault (get_group_fault,'getArtifactFollowups','Could Not Get Group','Could Not Get Group');
		} elseif ($grp->isError()) {
			return new soap_fault (get_group_fault,'getArtifactFollowups',$grp->getErrorMessage(),$grp->getErrorMessage());
		}
		$at = new ArtifactType($grp,$group_artifact_id);
	   	if (!$at || !is_object($at)) {
			return new soap_fault (get_artifact_type_fault,'getArtifactFollowups','Could Not Get ArtifactType','Could Not Get ArtifactType');
	   	} elseif ($at->isError()) {
			return new soap_fault (get_artifact_type_fault,'getArtifactFollowups',$at->getErrorMessage(),$at->getErrorMessage());
	   	}

	   	$art_field_fact = new ArtifactFieldFactory($at);
	   	if (!$art_field_fact || !is_object($art_field_fact)) {
	        	return new soap_fault (get_artifact_field_factory_fault, 'getArtifactFollowups', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
	   	} elseif ($art_field_fact->isError()) {
	        	return new soap_fault (get_artifact_field_factory_fault, 'getArtifactFollowups', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
	   	}
	   	
	   	$a = new Artifact($at, $artifact_id);
		if (!$a || !is_object($a)) {
			return new soap_fault (get_artifact_fault, 'getArtifactFollowups', 'Could Not Get Artifact', 'Could Not Get Artifact');
		} elseif ($a->isError()) {
			return new soap_fault (get_artifact_fault, 'getArtifactFollowups', $a->getErrorMessage(), $a->getErrorMessage());
		}
		$return  = artifactfollowups_to_soap($a->getFollowups());
		return new soapval('return', 'tns:ArrayOfArtifactFollowup', $return);
		
  	
  	} else
    	   return new soap_fault (invalid_session_fault,'getArtifactFollowups','Invalide Session ','');
  }
  
  function artifactfollowups_to_soap($followups_res) {
	$return = array();
	$rows = db_numrows($followups_res);
	for ($i=0; $i < $rows; $i++)
		$return[] = array (
			'artifact_id'	      => db_result($followups_res, $i, 'artifact_id'),	
			'comment' 	      => db_result($followups_res, $i, 'old_value'),
			'date' 	      	      => db_result($followups_res, $i, 'date'),
			'by' 	     	      => (db_result($followups_res, $i, 'mod_by')==100?db_result($followups_res, $i, 'email'):db_result($followups_res, $i, 'user_name')),
			'comment_type_id'     => db_result($followups_res, $i, 'comment_type_id')			
		);
	return $return;
  }
	
  function &getArtifactCannedResponses ($sessionKey, $group_id, $group_artifact_id) {
  	if (session_continue($sessionKey)){
  	
  		$grp =& group_get_object($group_id);
		if (!$grp || !is_object($grp)) {
			return new soap_fault (get_group_fault,'getArtifactCannedResponses','Could Not Get Group','Could Not Get Group');
		} elseif ($grp->isError()) {
			return new soap_fault (get_group_fault,'getArtifactCannedResponses',$grp->getErrorMessage(),$grp->getErrorMessage());
		}
		
		$at = new ArtifactType($grp,$group_artifact_id);
	   	if (!$at || !is_object($at)) {
			return new soap_fault (get_artifact_type_fault,'getArtifactCannedResponses','Could Not Get ArtifactType','Could Not Get ArtifactType');
	   	} elseif ($at->isError()) {
			return new soap_fault (get_artifact_type_fault,'getArtifactCannedResponses',$at->getErrorMessage(),$at->getErrorMessage());
	   	}

	   	return artifactcannedresponses_to_soap($at->getCannedResponses());
  	
  	} else
    	   return new soap_fault (invalid_session_fault,'getArtifactCannedResponses','Invalide Session ','');
  }
  
  function artifactcannedresponses_to_soap($cannedresponses_res) {
	$return = array();
	$rows = db_numrows($cannedresponses_res);
	for ($i=0; $i < $rows; $i++)
		$return[] = array (
			'artifact_canned_id' => db_result($cannedresponses_res, $i, 'artifact_canned_id'),
			'group_artifact_id' => db_result($cannedresponses_res, $i, 'group_artifact_id'),
			'title' => db_result($cannedresponses_res, $i, 'title'),
			'body' => db_result($cannedresponses_res, $i, 'body')
		);
	return $return;
  }
  
  function &getArtifactReports ($sessionKey, $group_id, $group_artifact_id, $user_id) {
  	if (session_continue($sessionKey)){
  	
  		$grp =& group_get_object($group_id);
		if (!$grp || !is_object($grp)) {
			return new soap_fault (get_group_fault,'getArtifactReports','Could Not Get Group','Could Not Get Group');
		} elseif ($grp->isError()) {
			return new soap_fault (get_group_fault,'getArtifactReports',$grp->getErrorMessage(),$grp->getErrorMessage());
		}
		
		$at = new ArtifactType($grp,$group_artifact_id);
	   	if (!$at || !is_object($at)) {
			return new soap_fault (get_artifact_type_fault,'getArtifactCannedResponses','Could Not Get ArtifactType','Could Not Get ArtifactType');
	   	} elseif ($at->isError()) {
			return new soap_fault (get_artifact_type_fault,'getArtifactCannedResponses',$at->getErrorMessage(),$at->getErrorMessage());
	   	}
		
		$report_fact = new ArtifactReportFactory();
	   	if (!$report_fact || !is_object($report_fact)) {
			return new soap_fault (get_report_factory_fault,'getArtifactReports', 'Could Not Get ArtifactReportFactory', 'Could Not Get ArtifactReportFactory');
		}
		
	   	return artifactreports_to_soap($report_fact->getReports($group_artifact_id, $user_id));
  	
  	} else
    	   return new soap_fault (invalid_session_fault, 'getArtifactReports', 'Invalide Session ', '');
  }
  
  function &artifactreports_to_soap($artifactreports) {
	$return = array();
	if (is_array($artifactreports) && count($artifactreports))
	    foreach ($artifactreports as $arid => $artifactreport){
		$fields = array();
		if ($artifactreport->isError()) {
			//skip if error
		} else {
		     $report_fields = $artifactreport->getSortedFields();	
		     if(is_array($report_fields) && count($report_fields) > 0 ) {
			    while(list($key, $field) = each($report_fields)) {
				$fields[] = array (
						'report_id' 	 => $artifactreport->getID(),
						'field_name'     => $field->getName(),
						'show_on_query'  => $field->getShowOnQuery(),
	                                        'show_on_result' => $field->getShowOnResult(),
	                                        'place_query' 	 => $field->getPlaceQuery(),
	                                        'place_result' 	 => $field->getPlaceResult(),
	                                        'col_width'	 => $field->getColWidth()	
				);
			    }
		     }
		     $return[]=array(
				'report_id' 	      => $artifactreport->getID(),
				'group_artifact_id'   => $artifactreport->getArtifactTypeID(),
				'name' 	      	      => $artifactreport->getName(),
				'description' 	      => $artifactreport->getDescription(),
				'scope' 	      => $artifactreport->getScope(),
				'fields'	      => $fields
		     );
		}
	    }
	return $return;
  }
  
  function &getAttachedFiles($sessionKey,$group_id,$group_artifact_id,$artifact_id) {
	global $art_field_fact; 
	if (session_continue($sessionKey)){
	
		$grp =& group_get_object($group_id);
		if (!$grp || !is_object($grp)) {
			return new soap_fault (get_group_fault,'getArtifactFiles','Could Not Get Group','Could Not Get Group');
		} elseif ($grp->isError()) {
			return new soap_fault (get_group_fault,'getArtifactFiles',$grp->getErrorMessage(),$grp->getErrorMessage());
		}

		$at = new ArtifactType($grp,$group_artifact_id);
		if (!$at || !is_object($at)) {
			return new soap_fault (get_artifact_type_fault,'getArtifactFiles','Could Not Get ArtifactType','Could Not Get ArtifactType');
		} elseif ($at->isError()) {
			return new soap_fault (get_artifact_type_fault,'getArtifactFiles',$at->getErrorMessage(),$at->getErrorMessage());
		}
		
		$art_field_fact = new ArtifactFieldFactory($at);
	   	if (!$art_field_fact || !is_object($art_field_fact)) {
	        	return new soap_fault (get_artifact_field_factory_fault, 'getArtifactFiles', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
	   	} elseif ($art_field_fact->isError()) {
	        	return new soap_fault (get_artifact_field_factory_fault, 'getArtifactFiles', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
	   	}
	   	
		$a = new Artifact($at,$artifact_id);
		if (!$a || !is_object($a)) {
			return new soap_fault (get_artifact_fault,'getArtifactFiles','Could Not Get Artifact','Could Not Get Artifact');
		} elseif ($a->isError()) {
			return new soap_fault (get_artifact_fault,'getArtifactFiles',$a->getErrorMessage(),$a->getErrorMessage());
		}

		return artifactfiles_to_soap($a->getAttachedFiles());
	} else
    	   	return new soap_fault (invalid_session_fault, 'getArtifactFiles', 'Invalide Session ', '');
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
  
  function getDependancies($sessionKey,$group_id,$group_artifact_id,$artifact_id) {
  	global $art_field_fact; 
	if (session_continue($sessionKey)){
	
		$grp =& group_get_object($group_id);
		if (!$grp || !is_object($grp)) {
			return new soap_fault (get_group_fault,'getDependancies','Could Not Get Group','Could Not Get Group');
		} elseif ($grp->isError()) {
			return new soap_fault (get_group_fault,'getDependancies',$grp->getErrorMessage(),$grp->getErrorMessage());
		}

		$at = new ArtifactType($grp,$group_artifact_id);
		if (!$at || !is_object($at)) {
			return new soap_fault (get_artifact_type_fault,'getDependancies','Could Not Get ArtifactType','Could Not Get ArtifactType');
		} elseif ($at->isError()) {
			return new soap_fault (get_artifact_type_fault,'getDependancies',$at->getErrorMessage(),$at->getErrorMessage());
		}
		
		$art_field_fact = new ArtifactFieldFactory($at);
	   	if (!$art_field_fact || !is_object($art_field_fact)) {
	        	return new soap_fault (get_artifact_field_factory_fault, 'getDependancies', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
	   	} elseif ($art_field_fact->isError()) {
	        	return new soap_fault (get_artifact_field_factory_fault, 'getDependancies', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
	   	}
	   	
		$a = new Artifact($at,$artifact_id);
		if (!$a || !is_object($a)) {
			return new soap_fault (get_artifact_fault,'getDependancies','Could Not Get Artifact','Could Not Get Artifact');
		} elseif ($a->isError()) {
			return new soap_fault (get_artifact_fault,'getDependancies',$a->getErrorMessage(),$a->getErrorMessage());
		}

		return dependancies_to_soap($a->getDependencies());
	} else
    	   	return new soap_fault (invalid_session_fault, 'getDependancies', 'Invalide Session ', '');
  	
  }
  
  function dependancies_to_soap($dependancies) {
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
  
  function addArtifactFile($sessionKey,$group_id,$group_artifact_id,$artifact_id,$bin_data,$description,$filename,$filetype) {
  	global $art_field_fact; 
	if (session_continue($sessionKey)){
	
		$grp =& group_get_object($group_id);
		if (!$grp || !is_object($grp)) {
			return new soap_fault (get_group_fault,'addArtifactFile','Could Not Get Group','Could Not Get Group');
		} elseif ($grp->isError()) {
			return new soap_fault (get_group_fault,'addArtifactFile',$grp->getErrorMessage(),$grp->getErrorMessage());
		}

		$at = new ArtifactType($grp,$group_artifact_id);
		if (!$at || !is_object($at)) {
			return new soap_fault (get_artifact_type_fault,'addArtifactFile','Could Not Get ArtifactType','Could Not Get ArtifactType');
		} elseif ($at->isError()) {
			return new soap_fault (get_artifact_type_fault,'addArtifactFile',$at->getErrorMessage(),$at->getErrorMessage());
		}
		
		$art_field_fact = new ArtifactFieldFactory($at);
	   	if (!$art_field_fact || !is_object($art_field_fact)) {
	        	return new soap_fault (get_artifact_field_factory_fault, 'addArtifactFile', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
	   	} elseif ($art_field_fact->isError()) {
	        	return new soap_fault (get_artifact_field_factory_fault, 'addArtifactFile', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
	   	}

		$a = new Artifact($at,$artifact_id);
		if (!$a || !is_object($a)) {
			return new soap_fault (get_artifact_fault,'addArtifactFile','Could Not Get Artifact','Could Not Get Artifact');
		} elseif ($a->isError()) {
			return new soap_fault (get_artifact_fault,'addArtifactFile',$a->getErrorMessage(),$a->getErrorMessage());
		}
	
		$af = new ArtifactFile($a);
		if (!$af || !is_object($af)) {
			return new soap_fault (get_artifact_file_fault,'addArtifactFile','Could Not Create File Object','Could Not Create File Object');
		} else if ($af->isError()) {
			return new soap_fault (get_artifact_file_fault,'addArtifactFile',$af->getErrorMessage(),$af->getErrorMessage());
		}

		$filesize = strlen($bin_data);
	
		$id = $af->create($filename,$filetype,$filesize,$bin_data,$description, $changes);
	
		if (!$id) {
			return new soap_fault (get_artifact_file_fault,'addArtifactFile',$af->getErrorMessage(),$af->getErrorMessage());
		}
	
		return $id;
	} else
    	   	return new soap_fault (invalid_session_fault, 'addArtifactFile', 'Invalide Session', 'Invalide Session');
  }
  
  function deleteArtifactFile($sessionKey,$group_id,$group_artifact_id,$artifact_id,$file_id) {
  	global $art_field_fact; 
	if (session_continue($sessionKey)){
	
		$grp =& group_get_object($group_id);
		if (!$grp || !is_object($grp)) {
			return new soap_fault (get_group_fault,'deleteArtifactFile','Could Not Get Group','Could Not Get Group');
		} elseif ($grp->isError()) {
			return new soap_fault (get_group_fault,'deleteArtifactFile',$grp->getErrorMessage(),$grp->getErrorMessage());
		}

		$at = new ArtifactType($grp,$group_artifact_id);
		if (!$at || !is_object($at)) {
			return new soap_fault (get_artifact_type_fault,'deleteArtifactFile','Could Not Get ArtifactType','Could Not Get ArtifactType');
		} elseif ($at->isError()) {
			return new soap_fault (get_artifact_type_fault,'deleteArtifactFile',$at->getErrorMessage(),$at->getErrorMessage());
		}
		
		$art_field_fact = new ArtifactFieldFactory($at);
	   	if (!$art_field_fact || !is_object($art_field_fact)) {
	        	return new soap_fault (get_artifact_field_factory_fault, 'deleteArtifactFile', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
	   	} elseif ($art_field_fact->isError()) {
	        	return new soap_fault (get_artifact_field_factory_fault, 'deleteArtifactFile', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
	   	}

		$a = new Artifact($at,$artifact_id);
		if (!$a || !is_object($a)) {
			return new soap_fault (get_artifact_fault,'deleteArtifactFile','Could Not Get Artifact','Could Not Get Artifact');
		} elseif ($a->isError()) {
			return new soap_fault (get_artifact_fault,'deleteArtifactFile',$a->getErrorMessage(),$a->getErrorMessage());
		}
	
		$af = new ArtifactFile($a, $file_id);
		if (!$af || !is_object($af)) {
			return new soap_fault (get_artifact_file_fault,'deleteArtifactFile','Could Not Create File Object','Could Not Create File Object');
		} else if ($af->isError()) {
			return new soap_fault (get_artifact_file_fault,'deleteArtifactFile',$af->getErrorMessage(),$af->getErrorMessage());
		}
	
		if (!$af->delete()) {
			return new soap_fault (get_artifact_file_fault,'deleteArtifactFile',$af->getErrorMessage(),$af->getErrorMessage());
		}
	
		return $file_id;
	} else
    	   	return new soap_fault (invalid_session_fault, 'deleteArtifactFile', 'Invalide Session', 'Invalide Session');
  }
  
  function addDependencies($sessionKey, $group_id, $group_artifact_id, $artifact_id,				$is_dependent_on_artifact_id){
  	global $art_field_fact; 
  	if (session_continue($sessionKey)){

  		$grp =& group_get_object($group_id);
		if (!$grp || !is_object($grp)) {
			return new soap_fault (get_group_fault,'addDependencies','Could Not Get Group','Could Not Get Group');
		} elseif ($grp->isError()) {
			return new soap_fault (get_group_fault,'addDependencies',$grp->getErrorMessage(),$grp->getErrorMessage());
		}

		$at = new ArtifactType($grp,$group_artifact_id);
		if (!$at || !is_object($at)) {
			return new soap_fault (get_artifact_type_fault,'addDependencies','Could Not Get ArtifactType','Could Not Get ArtifactType');
		} elseif ($at->isError()) {
			return new soap_fault (get_artifact_type_fault,'addDependencies',$at->getErrorMessage(),$at->getErrorMessage());
		}
		
		$art_field_fact = new ArtifactFieldFactory($at);
	   	if (!$art_field_fact || !is_object($art_field_fact)) {
	        	return new soap_fault (get_artifact_field_factory_fault, 'addDependencies', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
	   	} elseif ($art_field_fact->isError()) {
	        	return new soap_fault (get_artifact_field_factory_fault, 'addDependencies', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
	   	}

		$a = new Artifact($at,$artifact_id);
		if (!$a || !is_object($a)) {
			return new soap_fault (get_artifact_fault,'addDependencies','Could Not Get Artifact','Could Not Get Artifact');
		} elseif ($a->isError()) {
			return new soap_fault (get_artifact_fault,'addDependencies',$a->getErrorMessage(),$a->getErrorMessage());
		}
		
  		$comma_separated = implode(",", $is_dependent_on_artifact_id);
  		
  		$a->addDependencies($comma_separated,&$changes,true);
  		if (!isset($changes) || !is_array($changes) || count($changes) == 0)
  			return new soap_fault (add_dependency_fault, 'addDependencies', 'Dependencies addition for artifact #'.$a->getID().' failed', 'Dependencies addition for artifact #'.$a->getID().' failed');     	
  	} else
    		return new soap_fault (invalid_session_fault, 'addDependencies', 'Invalide Session', 'Invalide Session');
  }
  
  function deleteDependency($sessionKey, $group_id, $group_artifact_id, $artifact_id, $dependent_on_artifact_id){
  	global $art_field_fact; 
  	if (session_continue($sessionKey)){

  		$grp =& group_get_object($group_id);
		if (!$grp || !is_object($grp)) {
			return new soap_fault (get_group_fault,'deleteDependency','Could Not Get Group','Could Not Get Group');
		} elseif ($grp->isError()) {
			return new soap_fault (get_group_fault,'deleteDependency',$grp->getErrorMessage(),$grp->getErrorMessage());
		}

		$at = new ArtifactType($grp,$group_artifact_id);
		if (!$at || !is_object($at)) {
			return new soap_fault (get_artifact_type_fault,'deleteDependency','Could Not Get ArtifactType','Could Not Get ArtifactType');
		} elseif ($at->isError()) {
			return new soap_fault (get_artifact_type_fault,'deleteDependency',$at->getErrorMessage(),$at->getErrorMessage());
		}
		
		$art_field_fact = new ArtifactFieldFactory($at);
	   	if (!$art_field_fact || !is_object($art_field_fact)) {
	        	return new soap_fault (get_artifact_field_factory_fault, 'deleteDependency', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
	   	} elseif ($art_field_fact->isError()) {
	        	return new soap_fault (get_artifact_field_factory_fault, 'deleteDependency', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
	   	}

		$a = new Artifact($at,$artifact_id);
		if (!$a || !is_object($a)) {
			return new soap_fault (get_artifact_fault,'deleteDependency','Could Not Get Artifact','Could Not Get Artifact');
		} elseif ($a->isError()) {
			return new soap_fault (get_artifact_fault,'deleteDependency',$a->getErrorMessage(),$a->getErrorMessage());
		}
		
  		if (!$a->existDependency($dependent_on_artifact_id) 
  		 || !$a->deleteDependency($dependent_on_artifact_id,$changes)) {
  			return new soap_fault (delete_dependency_fault, 'deleteDependency', 'Error deleting dependency'. $dependent_on_artifact_id, 'Error deleting dependency'. $dependent_on_artifact_id);
  		} else { 
  			return $dependent_on_artifact_id;
  		}
  		
  	} else
    		return new soap_fault (invalid_session_fault, 'deleteDependency', 'Invalide Session', 'Invalide Session');
  }
  
  function addFollowup($sessionKey,$group_id,$group_artifact_id,$artifact_id,$body){
  	global $art_field_fact; 
  	if (session_continue($sessionKey)){
  		$grp =& group_get_object($group_id);
		if (!$grp || !is_object($grp)) {
			return new soap_fault (get_group_fault,'addFollowup','Could Not Get Group','Could Not Get Group');
		} elseif ($grp->isError()) {
			return new soap_fault (get_group_fault,'addFollowup',$grp->getErrorMessage(),$grp->getErrorMessage());
		}

		$at = new ArtifactType($grp,$group_artifact_id);
		if (!$at || !is_object($at)) {
			return new soap_fault (get_artifact_type_fault,'addFollowup','Could Not Get ArtifactType','Could Not Get ArtifactType');
		} elseif ($at->isError()) {
			return new soap_fault (get_artifact_type_fault,'addFollowup',$at->getErrorMessage(),$at->getErrorMessage());
		}
		
		$art_field_fact = new ArtifactFieldFactory($at);
	   	if (!$art_field_fact || !is_object($art_field_fact)) {
	        	return new soap_fault (get_artifact_field_factory_fault, 'addFollowup', 'Could Not Get ArtifactFieldFactory','Could Not Get ArtifactFieldFactory');
	   	} elseif ($art_field_fact->isError()) {
	        	return new soap_fault (get_artifact_field_factory_fault, 'addFollowup', $art_field_fact->getErrorMessage(),$art_field_fact->getErrorMessage());
	   	}

		$a = new Artifact($at,$artifact_id);
		if (!$a || !is_object($a)) {
			return new soap_fault (get_artifact_fault,'addFollowup','Could Not Get Artifact','Could Not Get Artifact');
		} elseif ($a->isError()) {
			return new soap_fault (get_artifact_fault,'addFollowup',$a->getErrorMessage(),$a->getErrorMessage());
		}
		if (!$a->addComment($body,false,&$changes)) 
			return new soap_fault (create_followup_fault, 'addFollowup', 'Comment could not be saved', 'Comment could not be saved');
	} else
    		return new soap_fault (invalid_session_fault, 'addFollowup', 'Invalide Session', 'Invalide Session');
  } 
  
  function existSummary($sessionKey, $group_artifact_id, $summary){
  	if (session_continue($sessionKey)){
  		$user = session_get_userid();
  		$res=db_query("SELECT artifact_id FROM artifact WHERE group_artifact_id = ".$group_artifact_id.
  			      " AND submitted_by=".$user. 
  			      " AND summary=\"".$summary."\""
  		     );
	  	if ($res && db_numrows($res) > 0) {
	  		return new soapval('return', 'xsd:int', db_result($res, 0, 0));
	  	} else
	  		return new soapval('return', 'xsd:int', -1);
  	} else
    		return new soap_fault (invalid_session_fault, 'existSummary', 'Invalide Session', 'Invalide Session');
  }
  
  
  $HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
  $server->service($HTTP_RAW_POST_DATA);

?>  
