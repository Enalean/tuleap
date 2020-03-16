<?php
// Type definition
$server->wsdl->addComplexType(
    'ArrayOfstring',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'xsd:string[]')),
    'xsd:string'
);

$server->wsdl->addComplexType(
    'ArrayOfInteger',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'xsd:integer[]')),
    'xsd:integer'
);

$server->wsdl->addComplexType(
    'ArrayOflong',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'xsd:long[]')),
    'xsd:long'
);

$server->wsdl->addComplexType(
    'ArrayOfint',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'xsd:int[]')),
    'xsd:int'
);

$server->wsdl->addComplexType(
    'Revision',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'revision' => array('name' => 'revision', 'type' => 'xsd:string'),
        'author'   => array('name' => 'author',   'type' => 'xsd:string'),
        'date'     => array('name' => 'date',     'type' => 'xsd:string'),
        'message'  => array('name' => 'message',  'type' => 'xsd:string'),
    )
);

$server->wsdl->addComplexType(
    'ArrayOfRevision',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:Revision[]')),
    'tns:Revision'
);

$server->wsdl->addComplexType(
    'Commiter',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'user_id'      => array('name' => 'user_id',      'type' => 'xsd:int'),
        'commit_count' => array('name' => 'commit_count', 'type' => 'xsd:int'),
    )
);

$server->wsdl->addComplexType(
    'ArrayOfCommiter',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:Commiter[]')),
    'tns:Commiter'
);

$server->wsdl->addComplexType(
    'SvnPathInfo',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'path'         => array('name' => 'path',         'type' => 'xsd:string'),
        'commit_count' => array('name' => 'commit_count', 'type' => 'xsd:int'),
    )
);

$server->wsdl->addComplexType(
    'ArrayOfSvnPathInfo',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:SvnPathInfo[]')),
    'tns:SvnPathInfo'
);

$server->wsdl->addComplexType(
    'SvnPathDetails',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'path'      => array('name' => 'path',       'type' => 'xsd:string'),
        'author'    => array('name' => 'author',     'type' => 'xsd:int'),
        'message'   => array('name' => 'message',    'type' => 'xsd:string'),
        'timestamp' => array('name' => 'timestamp',  'type' => 'xsd:int'),
    )
);

$server->wsdl->addComplexType(
    'ArrayOfSvnPathDetails',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:SvnPathDetails[]')),
    'tns:SvnPathDetails'
);

$server->wsdl->addComplexType(
    'UserInfo',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'identifier' => array('name' => 'identifier', 'type' => 'xsd:string'),
        'username' => array('name' => 'username', 'type' => 'xsd:string'),
        'id' => array('name' => 'id', 'type' => 'xsd:string'),
        'real_name' => array('name' => 'real_name', 'type' => 'xsd:string'),
        'email' => array('name' => 'email', 'type' => 'xsd:string'),
        'ldap_id' => array('name' => 'ldap_id', 'type' => 'xsd:string'),
    )
);

$server->wsdl->addComplexType(
    'ArrayOfUserInfo',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:UserInfo[]')),
    'tns:UserInfo'
);

$server->wsdl->addComplexType(
    'DescField',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'id'           => array('name' => 'id',           'type' => 'xsd:int'),
        'name'         => array('name' => 'name',         'type' => 'xsd:string'),
        'is_mandatory' => array('name' => 'is_mandatory', 'type' => 'xsd:int'),
    )
);

$server->wsdl->addComplexType(
    'ArrayOfDescFields',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:DescField[]')),
    'tns:DescField'
);

$server->wsdl->addComplexType(
    'DescFieldValue',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'id'    => array('name' => 'id',    'type' => 'xsd:int'),
        'value' => array('name' => 'value', 'type' => 'xsd:string'),
    )
);

$server->wsdl->addComplexType(
    'ArrayOfDescFieldsValues',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:DescFieldValue[]')),
    'tns:DescFieldValue'
);

$server->wsdl->addComplexType(
    'ServiceValue',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'id'         => array('name' => 'id',         'type' => 'xsd:int'),
        'short_name' => array('name' => 'short_name', 'type' => 'xsd:string'),
        'is_used'    => array('name' => 'is_used',    'type' => 'xsd:int'),
    )
);

$server->wsdl->addComplexType(
    'ArrayOfServicesValues',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:ServiceValue[]')),
    'tns:ServiceValue'
);
