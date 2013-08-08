<?php

if (defined('NUSOAP')) {
	
//
// Type definition
//
$server->wsdl->addComplexType(
    'ArrayOfstring',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'xsd:string[]')),
    'xsd:string'
);

$server->wsdl->addComplexType(
    'ArrayOfInteger',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'xsd:integer[]')),
    'xsd:integer'
);

$server->wsdl->addComplexType(
    'ArrayOflong',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'xsd:long[]')),
    'xsd:long'
);

$server->wsdl->addComplexType(
    'ArrayOfint',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'xsd:int[]')),
    'xsd:int'
);

$server->wsdl->addComplexType(
    'Revision',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'revision' => array('name'=>'revision', 'type' => 'xsd:string'),
        'author'   => array('name'=>'author',   'type' => 'xsd:string'),
        'date'     => array('name'=>'date',     'type' => 'xsd:string'),
        'message'  => array('name'=>'message',  'type' => 'xsd:string'),
    )
);

$server->wsdl->addComplexType(
    'ArrayOfRevision',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:Revision[]')),
    'tns:Revision'
);

$server->wsdl->addComplexType(
    'Commiter',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'user_id'      => array('name'=> 'user_id',      'type' => 'xsd:int'),
        'commit_count' => array('name'=> 'commit_count', 'type' => 'xsd:int'),
    )
);

$server->wsdl->addComplexType(
    'ArrayOfCommiter',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:Commiter[]')),
    'tns:Commiter'
);

$server->wsdl->addComplexType(
    'SvnPathInfo',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'path'         => array('name'=> 'path',         'type' => 'xsd:string'),
        'commit_count' => array('name'=> 'commit_count', 'type' => 'xsd:int'),
    )
);

$server->wsdl->addComplexType(
    'ArrayOfSvnPathInfo',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:SvnPathInfo[]')),
    'tns:SvnPathInfo'
);

$server->wsdl->addComplexType(
    'SvnPathDetails',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'path'      => array('name'=> 'path',       'type' => 'xsd:string'),
        'author'    => array('name'=> 'author',     'type' => 'xsd:int'),
        'message'   => array('name'=> 'message',    'type' => 'xsd:string'),
        'timestamp' => array('name'=> 'timestamp',  'type' => 'xsd:int'),
    )
);

$server->wsdl->addComplexType(
    'ArrayOfSvnPathDetails',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:SvnPathDetails[]')),
    'tns:SvnPathDetails'
);
}
?>
