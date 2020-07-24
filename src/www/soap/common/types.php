<?php
// Type definition
$server->wsdl->addComplexType(
    'ArrayOfstring',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'xsd:string[]']],
    'xsd:string'
);

$server->wsdl->addComplexType(
    'ArrayOfInteger',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'xsd:integer[]']],
    'xsd:integer'
);

$server->wsdl->addComplexType(
    'ArrayOflong',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'xsd:long[]']],
    'xsd:long'
);

$server->wsdl->addComplexType(
    'ArrayOfint',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'xsd:int[]']],
    'xsd:int'
);

$server->wsdl->addComplexType(
    'Revision',
    'complexType',
    'struct',
    'sequence',
    '',
    [
        'revision' => ['name' => 'revision', 'type' => 'xsd:string'],
        'author'   => ['name' => 'author',   'type' => 'xsd:string'],
        'date'     => ['name' => 'date',     'type' => 'xsd:string'],
        'message'  => ['name' => 'message',  'type' => 'xsd:string'],
    ]
);

$server->wsdl->addComplexType(
    'ArrayOfRevision',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:Revision[]']],
    'tns:Revision'
);

$server->wsdl->addComplexType(
    'Commiter',
    'complexType',
    'struct',
    'sequence',
    '',
    [
        'user_id'      => ['name' => 'user_id',      'type' => 'xsd:int'],
        'commit_count' => ['name' => 'commit_count', 'type' => 'xsd:int'],
    ]
);

$server->wsdl->addComplexType(
    'ArrayOfCommiter',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:Commiter[]']],
    'tns:Commiter'
);

$server->wsdl->addComplexType(
    'SvnPathInfo',
    'complexType',
    'struct',
    'sequence',
    '',
    [
        'path'         => ['name' => 'path',         'type' => 'xsd:string'],
        'commit_count' => ['name' => 'commit_count', 'type' => 'xsd:int'],
    ]
);

$server->wsdl->addComplexType(
    'ArrayOfSvnPathInfo',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:SvnPathInfo[]']],
    'tns:SvnPathInfo'
);

$server->wsdl->addComplexType(
    'SvnPathDetails',
    'complexType',
    'struct',
    'sequence',
    '',
    [
        'path'      => ['name' => 'path',       'type' => 'xsd:string'],
        'author'    => ['name' => 'author',     'type' => 'xsd:int'],
        'message'   => ['name' => 'message',    'type' => 'xsd:string'],
        'timestamp' => ['name' => 'timestamp',  'type' => 'xsd:int'],
    ]
);

$server->wsdl->addComplexType(
    'ArrayOfSvnPathDetails',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:SvnPathDetails[]']],
    'tns:SvnPathDetails'
);

$server->wsdl->addComplexType(
    'UserInfo',
    'complexType',
    'struct',
    'sequence',
    '',
    [
        'identifier' => ['name' => 'identifier', 'type' => 'xsd:string'],
        'username' => ['name' => 'username', 'type' => 'xsd:string'],
        'id' => ['name' => 'id', 'type' => 'xsd:string'],
        'real_name' => ['name' => 'real_name', 'type' => 'xsd:string'],
        'email' => ['name' => 'email', 'type' => 'xsd:string'],
        'ldap_id' => ['name' => 'ldap_id', 'type' => 'xsd:string'],
    ]
);

$server->wsdl->addComplexType(
    'ArrayOfUserInfo',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:UserInfo[]']],
    'tns:UserInfo'
);

$server->wsdl->addComplexType(
    'DescField',
    'complexType',
    'struct',
    'sequence',
    '',
    [
        'id'           => ['name' => 'id',           'type' => 'xsd:int'],
        'name'         => ['name' => 'name',         'type' => 'xsd:string'],
        'is_mandatory' => ['name' => 'is_mandatory', 'type' => 'xsd:int'],
    ]
);

$server->wsdl->addComplexType(
    'ArrayOfDescFields',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:DescField[]']],
    'tns:DescField'
);

$server->wsdl->addComplexType(
    'DescFieldValue',
    'complexType',
    'struct',
    'sequence',
    '',
    [
        'id'    => ['name' => 'id',    'type' => 'xsd:int'],
        'value' => ['name' => 'value', 'type' => 'xsd:string'],
    ]
);

$server->wsdl->addComplexType(
    'ArrayOfDescFieldsValues',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:DescFieldValue[]']],
    'tns:DescFieldValue'
);

$server->wsdl->addComplexType(
    'ServiceValue',
    'complexType',
    'struct',
    'sequence',
    '',
    [
        'id'         => ['name' => 'id',         'type' => 'xsd:int'],
        'short_name' => ['name' => 'short_name', 'type' => 'xsd:string'],
        'is_used'    => ['name' => 'is_used',    'type' => 'xsd:int'],
    ]
);

$server->wsdl->addComplexType(
    'ArrayOfServicesValues',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:ServiceValue[]']],
    'tns:ServiceValue'
);
