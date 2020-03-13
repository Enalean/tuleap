<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\SOAP\SOAPRequestValidatorImplementation;

require_once __DIR__ . '/../../include/user.php';
require_once __DIR__ . '/../../include/utils_soap.php';

if (defined('NUSOAP')) {
// Type definition
    $server->wsdl->addComplexType(
        'Group',
        'complexType',
        'struct',
        'all',
        '',
        array(
        'group_id' => array('name' => 'group_id', 'type' => 'xsd:int'),
        'group_name' => array('name' => 'group_name', 'type' => 'xsd:string'),
        'unix_group_name' => array('name' => 'unix_group_name', 'type' => 'xsd:string'),
        'description' => array('name' => 'description', 'type' => 'xsd:string')
        )
    );

    $server->wsdl->addComplexType(
        'ArrayOfGroup',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(
        array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:Group[]')
        ),
        'tns:Group'
    );

    $server->wsdl->addComplexType(
        'UGroupMember',
        'complexType',
        'struct',
        'all',
        '',
        array(
        'user_id'   => array('name' => 'user_id',   'type' => 'xsd:int'),
        'user_name' => array('name' => 'user_name', 'type' => 'xsd:string')
        )
    );

    $server->wsdl->addComplexType(
        'ArrayOfUGroupMember',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(
        array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:UGroupMember[]')
        ),
        'tns:UGroupMember'
    );

    $GLOBALS['server']->wsdl->addComplexType(
        'Ugroup',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'ugroup_id' => array('name' => 'ugroup_id', 'type' => 'xsd:int'),
        'name' => array('name' => 'name', 'type' => 'xsd:string'),
        'members' => array('name' => 'members', 'type' => 'tns:ArrayOfUGroupMember'),
        )
    );

    $GLOBALS['server']->wsdl->addComplexType(
        'ArrayOfUgroup',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:Ugroup[]')),
        'tns:Ugroup'
    );

// Function definition
    $server->register(
        'getMyProjects',               // method name
        array('sessionKey' => 'xsd:string'               // input parameters
        ),
        array('return'   => 'tns:ArrayOfGroup'),           // output parameters
        $uri,                   // namespace
        $uri . '#getMyProjects',        // soapaction
        'rpc',                           // style
        'encoded',                           // use
        'Returns the list of Groups that the current user belong to'             // documentation
    );

    $server->register(
        'getGroupByName',
        array('sessionKey' => 'xsd:string',
        'unix_group_name' => 'xsd:string'),
        array('return' => 'tns:Group'),
        $uri,
        $uri . '#getGroupByName',
        'rpc',
        'encoded',
        'Returns a Group object matching with the given unix_group_name, or a soap fault if the name does not match with a valid project.'
    );

    $server->register(
        'getGroupById',
        array('sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int'
        ),
        array('return' => 'tns:Group'),
        $uri,
        $uri . '#getGroupById',
        'rpc',
        'encoded',
        'Returns the Group object associated with the given ID, or a soap fault if the ID does not match with a valid project.'
    );


    $server->register(
        'getGroupUgroups',
        array('sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int'
        ),
        array('return' => 'tns:ArrayOfUgroup'),
        $uri,
        $uri . '#getGroupUgroups',
        'rpc',
        'encoded',
        'Returns the Ugroups associated to the given project:
     <pre>
       [
         ["ugroup_id" => 120,
          "name"      => "my custom group",
          "members"   => [ ["user_id"   => 115,
                            "user_name" => "john_doe"],
                         ]
         ]
       ]
     </pre>
    '
    );

    $server->register(
        'getProjectGroupsAndUsers',
        array('sessionKey' => 'xsd:string',
          'group_id'   => 'xsd:int'
        ),
        array('return' => 'tns:ArrayOfUgroup'),
        $uri,
        $uri . '#getProjectGroupsAndUsers',
        'rpc',
        'encoded',
        'Returns all groups defined in project both dynamic and static (aka user group).
     <pre>
      [
        ["ugroup_id" => 3,
         "name"      => "project_members",
         "members"   => [ ["user_id"   => 115,
                           "user_name" => "john_doe"],
                          ["user_id"   => 120,
                           "user_name" => "foo_bar"]
                        ]
        ],
        ["ugroup_id" => 120,
         "name"      => "my custom group",
         "members"   => [ ["user_id"   => 115,
                           "user_name" => "john_doe"],
                        ]
        ]
      ]
     </pre>
    '
    );
} else {


/**
 * Returns a soap Group object corresponding to the Codendi Group object
 *
 * @param Object{Group} $group the group we want to convert in soap
 * @return array the soap group object
 */
    function group_to_soap($group)
    {
        $soap_group = array(
        'group_id' => $group->getGroupId(),
        'group_name' => $group->getPublicName(),
        'unix_group_name' => $group->getUnixName(),
        'description' => $group->getDescription()
        );
        return $soap_group;
    }


/**
 * getMyProjects : returns the array of SOAPGroup the current user is member of
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @return array the array of SOAPGroup th ecurrent user ismember of
 */
    function getMyProjects($sessionKey)
    {
        if (session_continue($sessionKey)) {
            $gf = new GroupFactory();
            $my_groups = $gf->getMyGroups();
            return groups_to_soap($my_groups);
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session ', 'getMyProjects');
        }
    }

/**
 * getGroupByName : returns the SOAPGroup associated with the given unix group name
 *
 * @global $Language
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param string $unix_group_name the unix name of the group we want to get
 * @return array the SOAPGroup associated with the given unix name
 */
    function getGroupByName($sessionKey, $unix_group_name)
    {
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $project = $pm->getGroupByIdForSoap($unix_group_name, 'getGroupByName', true);
                $soap_group = group_to_soap($project);
                return new SoapVar($soap_group, SOAP_ENC_OBJECT);
            } catch (SoapFault $e) {
                return $e;
            }
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'getGroupByName');
        }
    }

/**
 * getGroupById : returns the SOAPGroup associated with the given ID
 *
 * @global $Language
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param string $group_id the ID of the group we want to get
 * @return array the SOAPGroup associated with the given ID
 */
    function getGroupById($sessionKey, $group_id)
    {
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $group = $pm->getGroupByIdForSoap($group_id, 'getGroupById');
                $soap_group = group_to_soap($group);
                return $soap_group;
            } catch (SoapFault $e) {
                return $e;
            }
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'getGroup');
        }
    }

/**
 * Returns the Ugroups associated to the given project
 * This function can only be called by members of the group
 */
    function getGroupUgroups($sessionKey, $group_id)
    {
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $group = $pm->getGroupByIdForSoap($group_id, 'getGroupUgroups');
                $ugroups = ugroup_get_ugroups_with_members($group_id);
                return ugroups_to_soap($ugroups);
            } catch (SoapFault $e) {
                return $e;
            }
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'getGroupUgroups');
        }
    }

    function getProjectGroupsAndUsers($session_key, $group_id)
    {
        try {
            $project_manager        = ProjectManager::instance();
            $user_manager           = UserManager::instance();
            $soap_request_validator = new SOAPRequestValidatorImplementation(
                $project_manager,
                $user_manager,
                new ProjectAccessChecker(
                    new PermissionsOverrider_PermissionsOverriderManager(),
                    new RestrictedUserCanAccessProjectVerifier(),
                    EventManager::instance()
                )
            );

            $user    = $soap_request_validator->continueSession($session_key);
            $project = $soap_request_validator->getProjectById($group_id, 'getProjectGroupsAndUsers');

            $soap_request_validator->assertUserCanAccessProject($user, $project);

            $ugroups     = ugroup_get_ugroups_with_members($group_id);
            $dyn_members = ugroup_get_all_dynamic_members($group_id);

            return ugroups_to_soap(array_merge($dyn_members, $ugroups));
        } catch (SoapFault $e) {
            return $e;
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }

    $server->addFunction(
        array(
            'getMyProjects',
            'getGroupByName',
            'getGroupById',
            'getGroupUgroups',
            'getProjectGroupsAndUsers',
        )
    );
}
