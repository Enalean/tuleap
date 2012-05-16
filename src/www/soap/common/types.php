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

}

?>
