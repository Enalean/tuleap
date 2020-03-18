<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

use Tuleap\FRS\UploadedLinksDao;
use Tuleap\FRS\UploadedLinksRetriever;
use Tuleap\URI\URIModifier;

require_once __DIR__ . '/../../include/pre.php';
require_once __DIR__ . '/../../include/session.php';

// define fault code constants
define('INVALID_PACKAGE_FAULT', '3017');
define('INVALID_RELEASE_FAULT', '3018');
define('INVALID_FILE_FAULT', '3019');

if (defined('NUSOAP')) {
// Type definition
    $server->wsdl->addComplexType(
        'FRSPackage',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'package_id' => array('name' => 'package_id', 'type' => 'xsd:int'),
        'group_id' => array('name' => 'group_id', 'type' => 'xsd:int'),
        'name' => array('name' => 'name', 'type' => 'xsd:string'),
        'status_id' => array('name' => 'status_id', 'type' => 'xsd:int'),
        'rank' => array('name' => 'rank', 'type' => 'xsd:int'),
        'approve_license' => array('name' => 'approve_license', 'type' => 'xsd:boolean'),
        )
    );

    $server->wsdl->addComplexType(
        'ArrayOfFRSPackage',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:FRSPackage[]')),
        'tns:FRSPackage'
    );

    $server->wsdl->addComplexType(
        'FRSRelease',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'release_id' => array('name' => 'release_id', 'type' => 'xsd:int'),
        'package_id' => array('name' => 'package_id', 'type' => 'xsd:int'),
        'name' => array('name' => 'name', 'type' => 'xsd:string'),
        'notes' => array('name' => 'notes', 'type' => 'xsd:string'),
        'changes' => array('name' => 'changes', 'type' => 'xsd:string'),
        'status_id' => array('name' => 'description', 'type' => 'xsd:string'),
        'release_date' => array('name' => 'release_date', 'type' => 'xsd:int'),
        'released_by' => array('name' => 'released_by', 'type' => 'xsd:string'),
        )
    );

    $server->wsdl->addComplexType(
        'ArrayOfFRSRelease',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:FRSRelease[]')),
        'tns:FRSRelease'
    );

    $server->wsdl->addComplexType(
        'FRSFile',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'file_id'       => array('name' => 'file_id',       'type' => 'xsd:int'),
        'release_id'    => array('name' => 'release_id',    'type' => 'xsd:int'),
        'file_name'     => array('name' => 'file_name',     'type' => 'xsd:string'),
        'file_size'     => array('name' => 'file_size',     'type' => 'xsd:int'),
        'type_id'       => array('name' => 'type_id',       'type' => 'xsd:int'),
        'processor_id'  => array('name' => 'processor_id',  'type' => 'xsd:int'),
        'release_time'  => array('name' => 'release_time',  'type' => 'xsd:int'),
        'post_date'     => array('name' => 'post_date',     'type' => 'xsd:int'),
        'computed_md5'  => array('name' => 'computed_md5',  'type' => 'xsd:string'),
        'reference_md5' => array('name' => 'reference_md5', 'type' => 'xsd:string'),
        'user_id'       => array('name' => 'user_id',       'type' => 'xsd:int'),
        'comment'       => array('name' => 'comment',       'type' => 'xsd:string'),
        )
    );

    $server->wsdl->addComplexType(
        'ArrayOfFRSFile',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:FRSFile[]')),
        'tns:FRSFile'
    );

    if (! isset($uri)) {
        $uri = '';
    }

// Function definition
    $server->register(
        'getPackages',
        array(
        'sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int'),
        array('getPackagesResponse' => 'tns:ArrayOfFRSPackage'),
        $uri,
        $uri . '#getPackages',
        'rpc',
        'encoded',
        'Returns the array of FRSPackages that belongs to the group identified by group ID.
     Returns a soap fault if the group ID does not match with a valid project.'
    );

    $server->register(
        'addPackage',
        array(
        'sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'package_name' => 'xsd:string',
        'status_id' => 'xsd:int',
        'rank' => 'xsd:int',
        'approve_license' => 'xsd:boolean'),
        array('addPackageResponse' => 'xsd:int'),
        $uri,
        $uri . '#addPackage',
        'rpc',
        'encoded',
        'Add a Package to the File Release Manager of the project group_id with the values given by 
     package_name, status_id, rank and approve_license. 
     Returns the ID of the created package if the creation succeed.
     Returns a soap fault if the group_id is not a valid one, or if the add failed.'
    );

    $server->register(
        'getReleases',
        array(
        'sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'package_id' => 'xsd:int'),
        array('getReleasesResponse' => 'tns:ArrayOfFRSRelease'),
        $uri,
        $uri . '#getReleases',
        'rpc',
        'encoded',
        'Returns the array of FRSReleases that belongs to the group identified by group ID and 
     to the package identified by package_id.
     Returns a soap fault if the group ID does not match with a valid project, or if the package ID
     does not match with the right group ID.'
    );

    $server->register(
        'updateRelease',
        array(
        'sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'package_id' => 'xsd:int',
        'release_id' => 'xsd:int',
        'notes' => 'xsd:string',
        'changes' => 'xsd:string',
        'status_id' => 'xsd:int',),
        array('updateRelease' => 'xsd:boolean'),
        $uri,
        $uri . '#updateRelease',
        'rpc',
        'encoded',
        'Updates a Release in the File Release Manager of the project group_id with
     the values given by package_id, release_id, name, notes, changes and status_id.
     Returns true if the update succeed.
     Returns a soap fault if the group_id is not a valid one, if the package does
     not match with the group ID, if the release does not exist or access to it
     is not permiited or if the update failed.'
    );

    $server->register(
        'addRelease',
        array(
        'sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'package_id' => 'xsd:int',
        'name' => 'xsd:string',
        'notes' => 'xsd:string',
        'changes' => 'xsd:string',
        'status_id' => 'xsd:int',
        'release_date' => 'xsd:int'),
        array('addRelease' => 'xsd:int'),
        $uri,
        $uri . '#addRelease',
        'rpc',
        'encoded',
        'Add a Release to the File Release Manager of the project group_id with the values given by 
     package_id, name, notes, changes, status_id and release_date. 
     Returns the ID of the created release if the creation succeed.
     Returns a soap fault if the group_id is not a valid one, 
     if the package does not match with the group ID, or if the add failed.'
    );

    $server->register(
        'getFiles',
        array(
        'sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'package_id' => 'xsd:int',
        'release_id' => 'xsd:int'),
        array('getFilesResponse' => 'tns:ArrayOfFRSFile'),
        $uri,
        $uri . '#getFiles',
        'rpc',
        'encoded',
        'Returns the array of FRSFiles that belongs to the group identified by group ID, 
     to the package identified by package_id and to the release identfied by release_id.
     Returns a soap fault if the group ID does not match with a valid project, or if the package ID
     does not match with the right group ID, or if the release ID does not match with the right package ID.'
    );

    $server->register(
        'getFileInfo',
        array(
        'sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'package_id' => 'xsd:int',
        'release_id' => 'xsd:int',
        'file_id' => 'xsd:int'),
        array('getFileInfoResponse' => 'tns:FRSFile'),
        $uri,
        $uri . '#getFileInfo',
        'rpc',
        'encoded',
        'Returns the metadata of the file contained in 
     the release release_id in the package package_id, in the project group_id.
     Returns a soap fault if the group ID does not match with a valid project, or if the package ID
     does not match with the right group ID, or if the release ID does not match with the right package ID,
     or if the file ID does not match with the right release ID.'
    );

    $server->register(
        'getFile',
        array(
        'sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'package_id' => 'xsd:int',
        'release_id' => 'xsd:int',
        'file_id' => 'xsd:int'),
        array('getFileResponse' => 'xsd:string'),
        $uri,
        $uri . '#getFile',
        'rpc',
        'encoded',
        'Returns the <strong>content</strong> (encoded in base64) of the file contained in 
     the release release_id in the package package_id, in the project group_id.
     Returns a soap fault if the group ID does not match with a valid project, or if the package ID
     does not match with the right group ID, or if the release ID does not match with the right package ID,
     or if the file ID does not match with the right release ID.'
    );

    $server->register(
        'getFileChunk',
        array(
        'sessionKey' => 'xsd:string',
        'group_id'   => 'xsd:int',
        'package_id' => 'xsd:int',
        'release_id' => 'xsd:int',
        'file_id'    => 'xsd:int',
        'offset'     => 'xsd:int',
        'size'       => 'xsd:int'),
        array('getFileChunkResponse' => 'xsd:string'),
        $uri,
        $uri . '#getFileChunk',
        'rpc',
        'encoded',
        'Returns a part (chunk) of the <strong>content</strong>, encoded in base64, of the file contained in 
     the release release_id in the package package_id, in the project group_id.
     You specify the offset where the download should start and the size to transfer.
     Returns a soap fault if the group ID does not match with a valid project, or if the package ID
     does not match with the right group ID, or if the release ID does not match with the right package ID,
     or if the file ID does not match with the right release ID.'
    );

    $server->register(
        'addFile',
        array(
        'sessionKey'        => 'xsd:string',
        'group_id'          => 'xsd:int',
        'package_id'        => 'xsd:int',
        'release_id'        => 'xsd:int',
        'filename'          => 'xsd:string',
        'base64_contents'   => 'xsd:string',
        'type_id'           => 'xsd:int',
        'processor_id'      => 'xsd:int',
        'reference_md5'     => 'xsd:string',
        'comment'           => 'xsd:string'
        ),
        array('addFile' => 'xsd:int'),
        $uri,
        $uri . '#addFile',
        'rpc',
        'encoded',
        'Add a File to the File Release Manager of the project group_id with the values given by 
     package_id, release_id, filename, base64_contents, type_id, processor_id, reference_md5 and comment.
     The content of the file must be encoded in base64.
     Returns the ID of the created file if the creation succeed.
     Returns a soap fault if the group_id is not a valid one, 
     if the package does not match with the group ID, 
     if the release does not match with the package ID,
     or if the add failed.'
    );

    $server->register(
        'addFileChunk',
        array(
        'sessionKey'     => 'xsd:string',
        'filename'       => 'xsd:string',
        'contents'       => 'xsd:string',
        'first_chunk'    => 'xsd:boolean',
        ),
        array('addFileChunk' => 'xsd:integer'),
        $uri,
        $uri . '#addFileChunk',
        'rpc',
        'encoded',
        'Add a chunk to a file in the incoming directory to be released later in FRS. 
     The content of the chunk must be encoded in base64.
     Returns the size of the written chunk if the chunk addition succeed.
     Returns a soap fault if the session is not valid
     or if the addition failed.'
    );

    $server->register(
        'addUploadedFile',
        array(
        'sessionKey'    => 'xsd:string',
        'group_id'      => 'xsd:int',
        'package_id'    => 'xsd:int',
        'release_id'    => 'xsd:int',
        'filename'      => 'xsd:string',
        'type_id'       => 'xsd:int',
        'processor_id'  => 'xsd:int',
        'reference_md5' => 'xsd:string',
        'comment'       => 'xsd:string'
        ),
        array('addUploadedFile' => 'xsd:int'),
        $uri,
        $uri . '#addUploadedFile',
        'rpc',
        'encoded',
        'Add a File to the File Release Manager of the project group_id with the values given by 
     package_id, release_id, filename, type_id, processor_id, reference_md5 and comment.
     The file must already be present in the incoming directory.
     Returns the ID of the created file if the creation succeed.
     Returns a soap fault if the group_id is not a valid one, 
     if the package does not match with the group ID, 
     if the release does not match with the package ID,
     or if the add failed.'
    );

    $server->register(
        'getUploadedFiles',
        array(
        'sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int'
        ),
        array('getUploadedFilesResponse' => 'tns:ArrayOfstring'),
        $uri,
        $uri . '#getUploadedFiles',
        'rpc',
        'encoded',
        'Get the file names of the file present in the incoming directory on the server.'
    );

    $server->register(
        'deleteFile',
        array(
        'sessionKey' => 'xsd:string',
        'group_id' => 'xsd:int',
        'package_id' => 'xsd:int',
        'release_id' => 'xsd:int',
        'file_id' => 'xsd:int'
        ),
        array('deleteFileResponse' => 'xsd:boolean'),
        $uri,
        $uri . '#deleteFile',
        'rpc',
        'encoded',
        'Delete the file file_id in release release_id in package package_id.
    Returns true if succeed, or a soap fault if an error occured.'
    );

    $server->register(
        'deleteEmptyPackage',
        array(
        'sessionKey'  => 'xsd:string',
        'group_id'    => 'xsd:int',
        'package_id'  => 'xsd:int',
        'cleanup_all' => 'xsd:boolean'
        ),
        array('deleteEmptyPackageResponse' => 'tns:ArrayOfFRSPackage'),
        $uri,
        $uri . '#deleteEmptyPackage',
        'rpc',
        'encoded',
        'Delete a package or all empty packages in project group_id.
    Returns the list of deleted packages if succeed, or a soap fault if an error occured.'
    );

    $server->register(
        'deleteEmptyRelease',
        array(
        'sessionKey'  => 'xsd:string',
        'group_id'    => 'xsd:int',
        'package_id'  => 'xsd:int',
        'release_id'  => 'xsd:int',
        'cleanup_all' => 'xsd:boolean'
        ),
        array('deleteEmptyReleaseResponse' => 'tns:ArrayOfFRSRelease'),
        $uri,
        $uri . '#deleteEmptyRelease',
        'rpc',
        'encoded',
        'Delete a release or all empty releases in package package_id.
    Returns the list of deleted releases if succeed, or a soap fault if an error occured.'
    );

    $server->register(
        'updateFileComment',
        array(
        'sessionKey'        => 'xsd:string',
        'group_id'          => 'xsd:int',
        'package_id'        => 'xsd:int',
        'release_id'        => 'xsd:int',
        'file_id'           => 'xsd:int',
        'comment'           => 'xsd:string',
        ),
        array('updateFileCommentResponse' => 'xsd:boolean'),
        $uri,
        $uri . '#updateFileComment',
        'rpc',
        'encoded',
        'Update the comment of a File in a release with the values given by
     group_id, package_id, release_id, file_id and comment.
     Returns boolean if the update succeed.
     Returns a soap fault if the group_id is not a valid one,
     if the package does not match with the group ID,
     if the release does not match with the package ID,
     if the file does not match with the file ID,
     or if the update failed.'
    );
} else {

/**
 * getPackages - returns an array of FRSPackages that belongs to the project identified by group_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the array of packages
 * @return array the array of SOAPFRSPackage that belongs to the project identified by $group_id, or a soap fault if group_id does not match with a valid project.
 */
    function getPackages($sessionKey, $group_id)
    {
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $pm->getGroupByIdForSoap($group_id, 'getPackages');
            } catch (SoapFault $e) {
                return $e;
            }
            $pkg_fact = new FRSPackageFactory();
            // we get only the active packages, even if we are project admin or file admin
            $packages = $pkg_fact->getActiveFRSPackages($group_id);
            return packages_to_soap($packages);
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'getPackages');
        }
    }

/**
 * package_to_soap : return the soap FRSPackage structure giving a PHP FRSPackage Object.
 * @access private
 *
 * WARNING : We check the permissions here : only the readable packages are returned.
 *
 * @param Object{FRSPackage} $package the package to convert.
 * @return array the SOAPFRSPackage corresponding to the FRSPackage Object
 */
    function package_to_soap($package)
    {
        // check if current user is allowed to see this package
        if ($package->userCanRead()) {
            return array(
            'package_id' => $package->getPackageID(),
            'group_id' => $package->getGroupID(),
            'name' => util_unconvert_htmlspecialchars($package->getName()),
            'status_id' => $package->getStatusID(),
            'rank' => $package->getRank(),
            'approve_license' => $package->getApproveLicense()
            );
        }
        return null;
    }

    function packages_to_soap(&$pkg_arr)
    {
        $return = array();
        foreach ($pkg_arr as $package) {
            $return[] = package_to_soap($package);
        }
        return $return;
    }

/**
 * addPackage - add a package in the file release manager of the project $group_id with given values
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to add the package
 * @param string $package_name the name of the package
 * @param int $status_id the ID of the status of the package
 * @param int $rank the rank of the package in the package list page (optionnal, by default set to 0)
 * @param int $approve_license true if we need to approve the license before downloading every file in this package, false otherwise.
 * @return int the ID of the new created package,
 *              or a soap fault if :
 *              - group_id does not match with a valid project,
 *              - the user does not have the permissions to create a package
 *              - the package creation failed.
 */
    function addPackage($sessionKey, $group_id, $package_name, $status_id, $rank = 0, $approve_license = true)
    {
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $group = $pm->getGroupByIdForSoap($group_id, 'addPackage');
            } catch (SoapFault $e) {
                return $e;
            }
            $pkg_fact = new FRSPackageFactory();
            if ($pkg_fact->userCanCreate($group_id)) {
                // we check that the package name don't already exist
                if ($pkg_fact->isPackageNameExist($package_name, $group_id)) {
                    return new SoapFault(INVALID_PACKAGE_FAULT, 'Package name already exists in this project', 'addPackage');
                } else {
                    $pkg_array = array('group_id'        => $group->getID(),
                                   'name'            => $package_name,
                                   'status_id'       => $status_id,
                                   'rank'            => $rank,
                                   'approve_license' => $approve_license);
                    $dar = $pkg_fact->create($pkg_array);
                    if (!$dar) {
                        return new SoapFault(INVALID_PACKAGE_FAULT, $dar->isError(), 'addPackage');
                    } else {
                        // if there is no error, $dar contains the package_id
                        return $dar;
                    }
                }
            } else {
                return new SoapFault(INVALID_PACKAGE_FAULT, 'User is not allowed to create a package', 'addPackage');
            }
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'addPackage');
        }
    }

/**
 * getReleases - returns an array of FRSReleases that belongs to the project identified by group_id and package package_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the array of releases
 * @param int $package_id the ID of the package we want to retrieve the array of releases
 * @return array the array of SOAPFRSRelease that belongs to the project identified by $group_id, in the package $package_id, or a soap fault if group_id does not match with a valid project or if package_id does not match with group_id.
 */
    function getReleases($sessionKey, $group_id, $package_id)
    {
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $pm->getGroupByIdForSoap($group_id, 'getReleases');
            } catch (SoapFault $e) {
                return $e;
            }
            // retieve the package
            $pkg_fact = new FRSPackageFactory();
            $package  = $pkg_fact->getFRSPackageFromDb($package_id);
            if (!$package || $package->isDeleted() || $package->getGroupID() != $group_id) {
                return new SoapFault(INVALID_PACKAGE_FAULT, 'Invalid Package', 'getReleases');
            }
            // check access rights to this package
            if (! $package->userCanRead() || ! $package->isActive()) {
                return new SoapFault(INVALID_PACKAGE_FAULT, 'Permission to this Package denied', 'getReleases');
            }

            $release_fact = new FRSReleaseFactory();
            // we get only the active releases, even if we are project admin or file admin
            $releases = $release_fact->getActiveFRSReleases($package_id, $group_id);
            return releases_to_soap($releases);
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'getReleases');
        }
    }

/**
 * release_to_soap : return the soap FRSRelease structure giving a PHP FRSRelease Object.
 * @access private
 *
 * WARNING : We check the permissions here : only the readable releases are returned.
 *
 * @param Object{FRSRelease} $release the release to convert.
 * @return array the SOAPFRSRelease corresponding to the FRSRelease Object
 */
    function release_to_soap($release)
    {
        // check if the user can view
        if ($release->userCanRead()) {
            return array(
            'release_id' => $release->getReleaseID(),
            'package_id' => $release->getPackageID(),
            'name' => $release->getName(),
            'notes' => $release->getNotes(),
            'changes' => $release->getChanges(),
            'status_id' => $release->getStatusID(),
            'release_date' => $release->getReleaseDate(),
            'released_by' => $release->getReleasedBy()
            );
        }
        return null;
    }

    function releases_to_soap($release_arr)
    {
        $return = array();
        foreach ($release_arr as $release) {
            $soap_release = release_to_soap($release);
            if ($soap_release != null) {
                $return[] = $soap_release;
            }
        }
        return $return;
    }

/**
 * addRelease - add a release in the file release manager, in the package $package_id of the project $group_id with given values
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to add the release
 * @param int $package_id the ID of the package we want to add the release
 * @param string $name the name of the release we want to add
 * @param string $notes the notes of the release
 * @param string $changes the changes of the release
 * @param int $status_id the ID of the status of the release
 * @param int $release_date the release date, in timestamp format
 * @return int the ID of the new created release,
 *              or a soap fault if :
 *              - group_id does not match with a valid project,
 *              - package_id does not match with a valid package,
 *              - package_id does not belong to the project group_id,
 *              - the user does not have the permissions to create a release
 *              - the release creation failed.
 */
    function addRelease($sessionKey, $group_id, $package_id, $name, $notes, $changes, $status_id, $release_date)
    {
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $pm->getGroupByIdForSoap($group_id, 'addRelease');
            } catch (SoapFault $e) {
                return $e;
            }
            // retieve the package
            $pkg_fact = new FRSPackageFactory();
            $package  = $pkg_fact->getFRSPackageFromDb($package_id);
            if (!$package || $package->getGroupID() != $group_id) {
                return new SoapFault(INVALID_PACKAGE_FAULT, 'Invalid Package', 'addRelease');
            }

            $release_fact = new FRSReleaseFactory();
            if ($release_fact->userCanCreate($group_id)) {
                if ($release_fact->isReleaseNameExist($name, $package_id)) {
                    return new SoapFault(INVALID_RELEASE_FAULT, 'Release name already exists in this package', 'addRelease');
                } else {
                    $release_array = array ('package_id' => $package_id,
                                        'name' => $name,
                                        'notes' => $notes,
                                        'changes' => $changes,
                                        'status_id' => $status_id,
                                        'release_date' => $release_date);
                    $dar = $release_fact->create($release_array);
                    if (!$dar) {
                        return new SoapFault(INVALID_RELEASE_FAULT, $dar->isError(), 'addRelease');
                    } else {
                        // if there is no error, $dar contains the release_id
                        //add the default permission inherited from package
                        //we can modify it from web UI
                        $release_array['release_id'] = $dar;
                        $release = new FRSRelease($release_array);
                        if ($release_fact->setDefaultPermissions($release)) {
                            return $dar;
                        } else {
                            return new SoapFault(INVALID_RELEASE_FAULT, 'Could not retrieve parent package permission', 'addRelease');
                        }
                    }
                }
            } else {
                return new SoapFault(INVALID_RELEASE_FAULT, 'User is not allowed to create a release', 'addRelease');
            }
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'addRelease');
        }
    }

/**
 * updateRelease - update a release in the file release manager, in the package $package_id of the project $group_id with given values
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group the release is in
 * @param int $package_id the ID of the package the release is in
 * @param int $release_id the ID of the release we want to update
 * @param string $notes the notes of the release
 * @param string $changes the changes of the release
 * @param int $status_id the ID of the status of the release
 *
 * @return bool true if release updated
 *              or a soap fault if :
 *              - group_id does not match with a valid project,
 *              - package_id does not match with a valid package,
 *              - package_id does not belong to the project group_id,
 *              - the user does not have the permissions to update a release
 *              - the release creation failed.
 */
    function updateRelease($sessionKey, $group_id, $package_id, $release_id, $notes, $changes, $status_id)
    {
        if (! session_continue($sessionKey)) {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'updateRelease');
        }

        $project_manager = ProjectManager::instance();
        try {
            $project_manager->getGroupByIdForSoap($group_id, 'updateRelease');
        } catch (SoapFault $e) {
            return $e;
        }

        $pkg_fact = new FRSPackageFactory();
        $package = $pkg_fact->getFRSPackageFromDb($package_id);
        if (!$package || $package->getGroupID() != $group_id) {
            return new SoapFault(INVALID_PACKAGE_FAULT, 'Invalid Package', 'updateRelease');
        }

        $release_factory = new FRSReleaseFactory();
        if (! $release_factory->userCanUpdate($group_id, $release_id)) {
            return new SoapFault(INVALID_RELEASE_FAULT, 'User is not allowed to update a release', 'updateRelease');
        }

        if (! $release = $release_factory->getFRSReleaseFromDb($release_id)) {
            return new SoapFault(INVALID_RELEASE_FAULT, 'Release does not exist', 'updateRelease');
        }

        $release_array = array (
        'package_id'    => $package_id,
        'notes'         => $notes,
        'changes'       => $changes,
        'status_id'     => $status_id,
        'release_id'    => $release_id
        );

        if (! $release_factory->update($release_array)) {
            return new SoapFault(INVALID_RELEASE_FAULT, 'Update Failed', 'updateRelease');
        }

        return true;
    }

/**
 * getFiles - returns an array of FRSFiles that belongs to the release identified by release_id, in the package package_id, in project group_id,
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the array of files
 * @param int $package_id the ID of the package we want to retrieve the array of files
 * @param int $release_id the ID of the release we want to retrieve the array of files
 * @return array the array of SOAPFRSFile that belongs to the project identified by $group_id, in the package $package_id, in the release $release_id
 *         or a soap fault if group_id does not match with a valid project or if package_id does not match with group_id, or if release_id does not match with package_id.
 */
    function getFiles($sessionKey, $group_id, $package_id, $release_id)
    {
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $pm->getGroupByIdForSoap($group_id, 'getFiles');
            } catch (SoapFault $e) {
                return $e;
            }
            // retieve the package
            $pkg_fact = new FRSPackageFactory();
            $package  = $pkg_fact->getFRSPackageFromDb($package_id);
            if (!$package || $package->isDeleted() || $package->getGroupID() != $group_id) {
                return new SoapFault(INVALID_PACKAGE_FAULT, 'Invalid Package', 'getFiles');
            }
            // check access rights to this package
            if (! $package->userCanRead() || ! $package->isActive()) {
                return new SoapFault(INVALID_PACKAGE_FAULT, 'Permission to this Package denied', 'getFiles');
            }

            // retrieve the release
            $release_fact = new FRSReleaseFactory();
            $release      = $release_fact->getFRSReleaseFromDb($release_id);
            if (!$release || $release->isDeleted() || $release->getPackageID() != $package_id) {
                return new SoapFault(INVALID_RELEASE_FAULT, 'Invalid Release', 'getFiles');
            }
            // check access rights to this release
            if (! $release->userCanRead() || ! $release->isActive()) {
                return new SoapFault(INVALID_RELEASE_FAULT, 'Permission to this Release denied', 'getFiles');
            }

            $files_arr = $release->getFiles();
            return files_to_soap($files_arr);
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'getFiles');
        }
    }

/**
 * getFileInfo - returns an FRSFile metadata corresponding to the file identified by file_id that belongs to the release release_id, in the package package_id, in project group_id,
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group the file belongs to
 * @param int $package_id the ID of the package the file belongs to
 * @param int $release_id the ID of the release the file belongs to
 * @param int $file_id the ID of the file we want to retrieve the metadata
 * @return array FRSFile that belongs to the project identified by $group_id, in the package $package_id, in the release $release_id, with the ID $file_id
 *         or a soap fault if group_id does not match with a valid project or if package_id does not match with group_id, or if release_id does not match with package_id, or if file_id does not match with release_id.
 */
    function getFileInfo($sessionKey, $group_id, $package_id, $release_id, $file_id)
    {
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $pm->getGroupByIdForSoap($group_id, 'getFileInfo');
            } catch (SoapFault $e) {
                return $e;
            }

            // retieve the package
            $pkg_fact = new FRSPackageFactory();
            $package = $pkg_fact->getFRSPackageFromDb($package_id);
            if (!$package || $package->isDeleted() || $package->getGroupID() != $group_id) {
                return new SoapFault(INVALID_PACKAGE_FAULT, 'Invalid Package', 'getFileInfo');
            }
            // check access rights to this package
            if (! $package->userCanRead() || ! $package->isActive()) {
                return new SoapFault(INVALID_PACKAGE_FAULT, 'Permission to this Package denied', 'getFileInfo');
            }

            // retrieve the release
            $release_fact = new FRSReleaseFactory();
            $release = $release_fact->getFRSReleaseFromDb($release_id);
            if (!$release || $release->isDeleted() || $release->getPackageID() != $package_id) {
                return new SoapFault(INVALID_RELEASE_FAULT, 'Invalid Release', 'getFileInfo');
            }
            // check access rights to this release
            if (! $release->userCanRead() || ! $release->isActive()) {
                return new SoapFault(INVALID_RELEASE_FAULT, 'Permission to this Release denied', 'getFileInfo');
            }

            $file_fact = new FRSFileFactory();
            $file = $file_fact->getFRSFileFromDb($file_id);
            if (!$file || !$file->isActive() || $file->getReleaseID() != $release_id) {
                return new SoapFault(INVALID_FILE_FAULT, 'Invalid File', 'getFileInfo');
            }
            return file_to_soap($file);
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'getFileInfo', 'Invalid Session', 'getFileInfo');
        }
    }

/**
 * file_to_soap : return the soap FRSFile structure giving a PHP FRSFile Object.
 * @access private
 *
 * WARNING : We check the permissions here : only the readable files are returned.
 *
 * @param Object{FRSFile} $file the file to convert.
 * @return array the SOAPFRSFile corresponding to the FRSFile Object
 */
    function file_to_soap(FRSFile $file)
    {
        $return = null;
        // for the moment, no permissions on files
        $return = array(
        'file_id'       => $file->getFileID(),
        'release_id'    => $file->getReleaseID(),
        'file_name'     => $file->getFileName(),
        'file_size'     => $file->getFileSize(),
        'type_id'       => $file->getTypeID(),
        'processor_id'  => $file->getProcessorID(),
        'release_time'  => $file->getReleaseTime(),
        'post_date'     => $file->getPostDate(),
        'computed_md5'  => $file->getComputedMd5(),
        'reference_md5' => $file->getReferenceMd5(),
        'user_id'       => $file->getUserID(),
        'comment'       => $file->getComment(),
        );
        return $return;
    }

    function files_to_soap($files_arr)
    {
        $return = array();
        foreach ($files_arr as $file) {
            $return[] = file_to_soap($file);
        }
        return $return;
    }

/**
 * getFile - returns the content (encoded in base64) of FRSFiles that belongs to the release identified by file_id, part of release_id, package_id, in project group_id.
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the content of file
 * @param int $package_id the ID of the package we want to retrieve the content of file
 * @param int $release_id the ID of the release we want to retrieve the content of file
 * @param int $file_id the ID of the file we want to retrieve the content
 * @return the content of the file (encoded in base64) $file_id that belongs to the project identified by $group_id, in the package $package_id, in the release $release_id
 *         or a soap fault if
 *              - group_id does not match with a valid project or
 *              - package_id does not match with group_id, or
 *              - release_id does not match with package_id, or
 *              - file_id does not match with release_id, or
 *              - the file is not present on the server
 */
    function getFile($sessionKey, $group_id, $package_id, $release_id, $file_id)
    {
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $pm->getGroupByIdForSoap($group_id, 'getFile');
            } catch (SoapFault $e) {
                return $e;
            }

            // retieve the package
            $pkg_fact = new FRSPackageFactory();
            $package  = $pkg_fact->getFRSPackageFromDb($package_id);
            if (!$package || $package->isDeleted() || $package->getGroupID() != $group_id) {
                return new SoapFault(INVALID_PACKAGE_FAULT, 'Invalid Package', 'getFile');
            }
            // check access rights to this package
            if (! $package->userCanRead() || ! $package->isActive()) {
                return new SoapFault(INVALID_PACKAGE_FAULT, 'Permission to this Package denied', 'getFile');
            }

            // retrieve the release
            $release_fact = new FRSReleaseFactory();
            $release      = $release_fact->getFRSReleaseFromDb($release_id);
            if (!$release || $release->isDeleted() || $release->getPackageID() != $package_id) {
                return new SoapFault(INVALID_RELEASE_FAULT, 'Invalid Release', 'getFile');
            }
            // check access rights to this release
            if (! $release->userCanRead() || ! $release->isActive()) {
                return new SoapFault(INVALID_RELEASE_FAULT, 'Permission to this Release denied', 'getFile');
            }

            $file_fact = new FRSFileFactory();
            $file      = $file_fact->getFRSFileFromDb($file_id);
            if (!$file || !$file->isActive() || $file->getReleaseID() != $release_id) {
                return new SoapFault(INVALID_FILE_FAULT, 'Invalid File', 'getFile');
            }

            if (!$file->fileExists()) {
                return new SoapFault(INVALID_FILE_FAULT, 'File doesn\'t exist on the server', 'getFile');
            }

            // Log the download action
            $file->logDownload();

            $contents = $file->getContent();
            return base64_encode($contents);
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'getFile', 'Invalid Session', 'getFile');
        }
    }

/**
 * getFileChunk - returns the content (encoded in base64) of FRSFiles that belongs to the release identified by file_id, part of release_id, package_id, in project group_id.
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the content of file
 * @param int $package_id the ID of the package we want to retrieve the content of file
 * @param int $release_id the ID of the release we want to retrieve the content of file
 * @param int $file_id the ID of the file we want to retrieve the content
 * @return the content of the file (encoded in base64) $file_id that belongs to the project identified by $group_id, in the package $package_id, in the release $release_id
 *         or a soap fault if
 *              - group_id does not match with a valid project or
 *              - package_id does not match with group_id, or
 *              - release_id does not match with package_id, or
 *              - file_id does not match with release_id, or
 *              - the file is not present on the server
 */
    function getFileChunk($sessionKey, $group_id, $package_id, $release_id, $file_id, $offset, $size)
    {
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $pm->getGroupByIdForSoap($group_id, 'getFileChunk');
            } catch (SoapFault $e) {
                return $e;
            }

            // retieve the package
            $pkg_fact = new FRSPackageFactory();
            $package  = $pkg_fact->getFRSPackageFromDb($package_id);
            if (!$package || $package->isDeleted() || $package->getGroupID() != $group_id) {
                return new SoapFault(INVALID_PACKAGE_FAULT, 'Invalid Package', 'getFileChunk');
            }
            // check access rights to this package
            if (! $package->userCanRead() || ! $package->isActive()) {
                return new SoapFault(INVALID_PACKAGE_FAULT, 'Permission to this Package denied', 'getFileChunk');
            }

            // retrieve the release
            $release_fact = new FRSReleaseFactory();
            $release      = $release_fact->getFRSReleaseFromDb($release_id);
            if (!$release || $release->isDeleted() || $release->getPackageID() != $package_id) {
                return new SoapFault(INVALID_RELEASE_FAULT, 'Invalid Release', 'getFileChunk');
            }
            // check access rights to this release
            if (! $release->userCanRead() || ! $release->isActive()) {
                return new SoapFault(INVALID_RELEASE_FAULT, 'Permission to this Release denied', 'getFileChunk');
            }

            $file_fact = new FRSFileFactory();
            $file      = $file_fact->getFRSFileFromDb($file_id);
            if (!$file || !$file->isActive() || $file->getReleaseID() != $release_id) {
                return new SoapFault(INVALID_FILE_FAULT, 'Invalid File', 'getFileChunk');
            }

            if (!$file->fileExists()) {
                return new SoapFault(INVALID_FILE_FAULT, 'File doesn\'t exist on the server', 'getFileChunk');
            }

            // Log the download action
            $file->logDownload();

            $contents = $file->getContent($offset, $size);
            return base64_encode($contents);
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'getFile', 'Invalid Session', 'getFileChunk');
        }
    }

/**
 * addFile - add a file in the file release manager, in the release $release_id, in package $package_id of the project $group_id with given values
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to add the file
 * @param int $package_id the ID of the package we want to add the file
 * @param int $release_id the ID of the release we want to add the file
 * @param string $filename the name of the file we want to add (only file name, not directory)
 * @param string $base64_contents the content of the file, encoded in base64
 * @param int $type_id the ID of the type of the file
 * @param int $processor_id the ID of the processor of the file
 * @param string $reference_md5 the md5sum of the file calculated in client side
 * @param string $comment A comment/description of the uploaded file
 * @return int the ID of the new created file,
 *              or a soap fault if :
 *              - group_id does not match with a valid project,
 *              - package_id does not match with a valid package,
 *              - package_id does not belong to the project group_id,
 *              - release_id does not match with a valid release,
 *              - release_id does not belong to the project group_id,
 *              - the user does not have the permissions to create a file
 *              - the file creation failed.
 */
    function addFile($sessionKey, $group_id, $package_id, $release_id, $filename, $base64_contents, $type_id, $processor_id, $reference_md5, $comment)
    {
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $pm->getGroupByIdForSoap($group_id, 'addFile');
            } catch (SoapFault $e) {
                return $e;
            }

            // retieve the package
            $pkg_fact = new FRSPackageFactory();
            $package  = $pkg_fact->getFRSPackageFromDb($package_id);
            if (!$package || $package->getGroupID() != $group_id) {
                return new SoapFault(INVALID_PACKAGE_FAULT, 'Invalid Package', 'addFile');
            }

            // retrieve the release
            $release_fact = new FRSReleaseFactory();
            $release      = $release_fact->getFRSReleaseFromDb($release_id);
            if (!$release || $release->getPackageID() != $package_id) {
                return new SoapFault(INVALID_RELEASE_FAULT, 'Invalid Release', 'addFile');
            }

            $file_fact = new FRSFileFactory();
            if ($file_fact->userCanAdd($group_id)) {
                $tmpname = tempnam("/tmp", "codendi_soap_frs");
                $fh = fopen($tmpname, "wb");
                if (!$fh) {
                    return new SoapFault(INVALID_FILE_FAULT, 'Could not create temporary file in directory /tmp', 'addFile');
                }
                fwrite($fh, base64_decode($base64_contents));
                fclose($fh);

                // move the file in the incoming dir
                if (! rename($tmpname, $GLOBALS['ftp_incoming_dir'] . '/' . basename($filename))) {
                    return new SoapFault(INVALID_FILE_FAULT, 'Impossible to move the file in the incoming dir: ' . $GLOBALS['ftp_incoming_dir'], 'addFile');
                }

                // call addUploadedFile function
                $uploaded_filename = basename($filename);
                return addUploadedFile($sessionKey, $group_id, $package_id, $release_id, $uploaded_filename, $type_id, $processor_id, $reference_md5, $comment);
            } else {
                return new SoapFault(INVALID_FILE_FAULT, 'User is not allowed to add a file', 'addFile');
            }
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'addFile');
        }
    }

/**
 * updateFileComment - Update the comment of a File in a release with the values given by
 *  group_id, package_id, release_id, file_id and comment.
 *
 * @param string    $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int       $group_id the ID of the group we want to add the file
 * @param int       $package_id the ID of the package we want to add the file
 * @param int       $release_id the ID of the release we want to add the file
 * @param int       $file_id the ID of the file we want to retrieve the content
 * @param string    $comment A comment/description of the uploaded file
 * @return bool true if the file was updated, or a soap fault if:
 * - group_id does not match with a valid project,
 * - the package_id, release_id, file_id does not match
 * - the user does not have permissions to delete this file
 * - the system was not able to update the file.
 */
    function updateFileComment($sessionKey, $group_id, $package_id, $release_id, $file_id, $comment)
    {
        if (session_continue($sessionKey)) {
            try {
                $project_manager = ProjectManager::instance();
                $project_manager->getGroupByIdForSoap($group_id, 'updateFileComment');
            } catch (SoapFault $e) {
                return $e;
            }

            // retieve the package
            $pkg_fact = new FRSPackageFactory();
            $package  = $pkg_fact->getFRSPackageFromDb($package_id);
            if (!$package || $package->getGroupID() != $group_id) {
                return new SoapFault(INVALID_PACKAGE_FAULT, 'Invalid Package', 'updateFileComment');
            }

            // retrieve the release
            $release_fact = new FRSReleaseFactory();
            $release      = $release_fact->getFRSReleaseFromDb($release_id);
            if (!$release || $release->getPackageID() != $package_id) {
                return new SoapFault(INVALID_RELEASE_FAULT, 'Invalid Release', 'updateFileComment');
            }

            // retrieve the file
            $file_factory = new FRSFileFactory();

            if (! $file_factory->userCanAdd($group_id)) {
                return new SoapFault(INVALID_FILE_FAULT, 'User is not allowed to update file', 'updateFileComment');
            }

            $file = $file_factory->getFRSFileFromDb($file_id);
            if (! $file) {
                return new SoapFault(INVALID_FILE_FAULT, 'Invalid File', 'updateFileComment');
            }

            $data_array = array(
                'comment'   => $comment,
                'file_id'   => $file_id
            );
            try {
                $file_factory->update($data_array);
            } catch (Exception $e) {
                return new SoapFault(INVALID_FILE_FAULT, 'Unable to update: ' . $e->getMessage(), 'updateFileComment');
            }
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'updateFileComment');
        }

        return true;
    }
/**
 * addFileChunk - add a chunk of a file in the incoming directory.
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to add the file
 * @param string $filename the name of the file we want to add
 * @param string $contents the content of the chunk, encoded in base64
 * @param bool $first_chunk indicates if the chunk to add is the first
 * @return int|SoapFault size of the chunk if added, or a soap fault if:
 *              - the sessionKey is not valid,
 *              - the file creation failed.
 */
    function addFileChunk($sessionKey, $filename, $contents, $first_chunk)
    {
        if (! session_continue($sessionKey)) {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'addFileChunk');
        }
        // if it's the first chunk overwrite the existing (if exists) file with the same name
        if ($first_chunk) {
            $mode = 'w';
        } else {
            $mode = 'a';
        }

        $ftp_incoming_dir = ForgeConfig::get('ftp_incoming_dir');
        if ($ftp_incoming_dir === false) {
            return new SoapFault(INVALID_FILE_FAULT, 'Incoming directory is not set', 'addFileChunk');
        }
        $minimal_ftp_incoming_dir_path = URIModifier::removeDotSegments(URIModifier::removeEmptySegments($ftp_incoming_dir));
        if ($minimal_ftp_incoming_dir_path === '' || $minimal_ftp_incoming_dir_path === '/') {
            return new SoapFault(INVALID_FILE_FAULT, 'Incoming directory is misconfigured', 'addFileChunk');
        }
        if (strpos($filename, '/') !== false) {
            return new SoapFault(INVALID_FILE_FAULT, 'Filename can not contain /', 'addFileChunk');
        }
        $incoming_file_path = URIModifier::removeDotSegments(URIModifier::removeEmptySegments($ftp_incoming_dir . '/' . $filename));
        if (strpos($incoming_file_path, $ftp_incoming_dir) !== 0) {
            return new SoapFault(INVALID_FILE_FAULT, 'Filename is invalid', 'addFileChunk');
        }

        $fp = fopen($incoming_file_path, $mode);
        $chunk = base64_decode($contents);
        $cLength = strlen($chunk);
        $written = fwrite($fp, $chunk);
        fclose($fp);
        if ($written != $cLength) {
            return new SoapFault(INVALID_FILE_FAULT, 'Sent ' . $cLength . ' of data but only ' . $written . ' saved in the server', 'addFileChunk');
        }
        return $written;
    }

/**
 * addUploadedFile - add a file in the file release manager, in the release $release_id, in package $package_id of the project $group_id with given values
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to add the file
 * @param int $package_id the ID of the package we want to add the file
 * @param int $release_id the ID of the release we want to add the file
 * @param string $filename the name of the file we want to add (only file name, not directory)
 * @param int $type_id the ID of the type of the file
 * @param int $processor_id the ID of the processor of the file
 * @param string $reference_md5 the md5sum of the file calculated in client side
 * @param string $comment A comment/description of the uploaded file
 * @return int the ID of the new created file,
 *              or a soap fault if :
 *              - group_id does not match with a valid project,
 *              - package_id does not match with a valid package,
 *              - package_id does not belong to the project group_id,
 *              - release_id does not match with a valid release,
 *              - release_id does not belong to the project group_id,
 *              - the user does not have the permissions to create a file
 *              - the md5 comparison failed
 *              - the file creation failed.
 */
    function addUploadedFile($sessionKey, $group_id, $package_id, $release_id, $filename, $type_id, $processor_id, $reference_md5, $comment)
    {
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $pm->getGroupByIdForSoap($group_id, 'addUploadedFile');
            } catch (SoapFault $e) {
                return $e;
            }

            // retieve the package
            $pkg_fact = new FRSPackageFactory();
            $package  = $pkg_fact->getFRSPackageFromDb($package_id);
            if (!$package || $package->getGroupID() != $group_id) {
                return new SoapFault(INVALID_PACKAGE_FAULT, 'Invalid Package', 'addUploadedFile');
            }

            // retrieve the release
            $release_fact = new FRSReleaseFactory();
            $release      = $release_fact->getFRSReleaseFromDb($release_id);
            if (!$release || $release->getPackageID() != $package_id) {
                return new SoapFault(INVALID_RELEASE_FAULT, 'Invalid Release', 'addUploadedFile');
            }

            $file_fact = new FRSFileFactory();
            if ($file_fact->userCanAdd($group_id)) {
                $user = UserManager::instance()->getCurrentUser();

                $file = new FRSFile();
                $file->setRelease($release);
                $file->setFileName(basename($filename));
                $file->setTypeID($type_id);
                $file->setProcessorID($processor_id);
                $file->setReferenceMd5($reference_md5);
                $file->setUserID($user->getId());
                $file->setComment($comment);
                try {
                    $file_fact->createFile($file);
                    $release_fact->emailNotification($release);
                    return $file->getFileID();
                } catch (Exception $e) {
                    return new SoapFault(INVALID_FILE_FAULT, $e->getMessage(), 'addUploadedFile');
                }
            } else {
                return new SoapFault(INVALID_FILE_FAULT, 'User is not allowed to add a file', 'addUploadedFile');
            }
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'addUploadedFile');
        }
    }

/**
 * getUploadedFiles - get the names of the files present in the incoming directory
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to add the file
 * @return array of string the names of the files present in the incoming directory,
 *              or a soap fault if :
 *              - group_id does not match with a valid project,
 *              - the user does not have the permissions to see the incoming directory (must be project admin, file admin or super user)
 */
    function getUploadedFiles($sessionKey, $group_id)
    {
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $project = $pm->getGroupByIdForSoap($group_id, 'getUploadedFiles');
            } catch (SoapFault $e) {
                return $e;
            }

            $file_fact = new FRSFileFactory();
            if ($file_fact->userCanAdd($group_id)) {
                $soap_files = array();
                $file_names = $file_fact->getUploadedFileNames($project);
                return $file_names;
            } else {
                return new SoapFault(INVALID_FILE_FAULT, 'User not allowed to see the uploaded files', 'getUploadedFiles');
            }
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'getUploadedFiles');
        }
    }

/**
 * deletefile - delete the file $file_id of the release $release_id in the package $package_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to delete the file
 * @param int $package_id the ID of the package we want to delete the file
 * @param int $release_id the ID of the release we want to delete the file
 * @param int $file_id the ID of the file we want to delete
 * @return bool true if the file was deleted, or a soap fault if:
 * - group_id does not match with a valid project,
 * - the package_id, release_id, file_id does not match
 * - the user does not have permissions to delete this file
 * - the system was not able to delete the file.
 */
    function deleteFile($sessionKey, $group_id, $package_id, $release_id, $file_id)
    {
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $pm->getGroupByIdForSoap($group_id, 'deleteFile');
            } catch (SoapFault $e) {
                return $e;
            }

            // retieve the package
            $pkg_fact = new FRSPackageFactory();
            $package  = $pkg_fact->getFRSPackageFromDb($package_id);
            if (!$package || $package->getGroupID() != $group_id) {
                return new SoapFault(INVALID_PACKAGE_FAULT, 'Invalid Package', 'deleteFile');
            }

            // retrieve the release
            $release_fact = new FRSReleaseFactory();
            $release      = $release_fact->getFRSReleaseFromDb($release_id);
            if (!$release || $release->getPackageID() != $package_id) {
                return new SoapFault(INVALID_RELEASE_FAULT, 'Invalid Release', 'deleteFile');
            }

            if ($release_fact->userCanUpdate($group_id, $release_id)) {
                // retrieve the file
                $file_fact = new FRSFileFactory();
                $file_info = $file_fact->getFRSFileInfoListFromDb($group_id, $file_id);
                if (count($file_info) == 0) {
                    return new SoapFault(INVALID_FILE_FAULT, 'Invalid File', 'deleteFile');
                }

                // delete the file
                if (! $file_fact->delete_file($group_id, $file_id)) {
                    return new SoapFault(INVALID_FILE_FAULT, 'Impossible to delete file', 'deleteFile');
                } else {
                    return true;
                }
            } else {
                return new SoapFault(INVALID_RELEASE_FAULT, 'User does not have permission to delete a file in this release.', 'deleteFile');
            }
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'deleteFile');
        }
    }

/**
 * deleteEmptyPackage - Delete an empty package or all empty packages in project group_id.
 *
 * @param String  $sessionKey  The session hash associated with the session opened by the person who calls the service
 * @param int $group_id Id of the project in which we want to delete the package(s)
 * @param int $package_id Id of the package to delete
 * @param bool $cleanup_all Set to true to delete all empty packages
 *
 * @return Array list of deleted packages, or a soap fault if:
 *                 - group_id does not match with a valid project
 *                 - the user does not have permissions to delete packages
 *                 - the system was not able to delete the packages.
 */
    function deleteEmptyPackage($sessionKey, $group_id, $package_id, $cleanup_all)
    {
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $pm->getGroupByIdForSoap($group_id, 'deletePackage');
            } catch (SoapFault $e) {
                return $e;
            }
            $packageFactory = new FRSPackageFactory();
            $packages = array();
            if ($package_id && !$cleanup_all) {
                $package = $packageFactory->getFRSPackageFromDb($package_id);
                if (!$package || $package->getGroupID() != $group_id) {
                    return new SoapFault(INVALID_PACKAGE_FAULT, 'Invalid Package', 'deletePackage');
                }
                $packages[] = $package;
            } elseif ($cleanup_all) {
                $packages = $packageFactory->getFRSPackagesFromDb($group_id);
            }
            $deleted = array();
            foreach ($packages as $package) {
                $releaseFactory = new FRSReleaseFactory();
                $releases = $releaseFactory->getFRSReleasesFromDb($package->getPackageID());
                if (empty($releases)) {
                    if ($packageFactory->userCanUpdate($group_id, $package->getPackageID())) {
                        if ($packageFactory->delete_package($group_id, $package->getPackageID())) {
                            $deleted[] = package_to_soap($package);
                        } else {
                            return new SoapFault(INVALID_PACKAGE_FAULT, 'Package ' . $package->getPackageID() . ' could not be deleted', 'deletePackage');
                        }
                    } else {
                        return new SoapFault(INVALID_PACKAGE_FAULT, 'You don\'t have permission to delete package ' . $package->getPackageID(), 'deletePackage');
                    }
                }
            }
            return $deleted;
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'deletePackage');
        }
    }

/**
 * deleteEmptyRelease - Delete an empty release or all empty releases in package package_id in project group_id.
 *
 * @param String  $sessionKey  The session hash associated with the session opened by the person who calls the service
 * @param int $group_id Id of the project in which we want to delete empty releases
 * @param int $package_id Id of the package in which we want to delete empty releases
 * @param int $release_id Id of the release to delete
 * @param bool $cleanup_all Set to true to delete all empty releases
 *
 * @return Array list of deleted releases, or a soap fault if:
 *                 - group_id does not match with a valid project
 *                 - the package_id does not match
 *                 - the user does not have permissions to delete releases
 *                 - the system was not able to delete the releases.
 */
    function deleteEmptyRelease($sessionKey, $group_id, $package_id, $release_id, $cleanup_all)
    {
        if (session_continue($sessionKey)) {
            try {
                $pm = ProjectManager::instance();
                $pm->getGroupByIdForSoap($group_id, 'deleteRelease');
            } catch (SoapFault $e) {
                return $e;
            }
            $packageFactory = new FRSPackageFactory();
            $package = $packageFactory->getFRSPackageFromDb($package_id);
            if (!$package || $package->getGroupID() != $group_id) {
                return new SoapFault(INVALID_PACKAGE_FAULT, 'Invalid Package', 'deleteRelease');
            }
            $releaseFactory = new FRSReleaseFactory();
            $releases = array();
            if ($release_id && !$cleanup_all) {
                $release = $releaseFactory->getFRSReleaseFromDb($release_id);
                if (!$release || $release->getPackageID() != $package_id) {
                    return new SoapFault(INVALID_RELEASE_FAULT, 'Invalid Release', 'deleteRelease');
                }
                $releases[] = $release;
            } elseif ($cleanup_all) {
                // retrieve all the releases
                $releases = $releaseFactory->getFRSReleasesFromDb($package_id);
            }
            $deleted                  = array();
            $fileFactory              = new FRSFileFactory();
            $uploaded_links_retriever = new UploadedLinksRetriever(new UploadedLinksDao(), UserManager::instance());
            foreach ($releases as $release) {
                $files          = $fileFactory->getFRSFilesFromDb($release->getReleaseID());
                $uploaded_links = $uploaded_links_retriever->getLinksForRelease($release);
                if (empty($files) && empty($uploaded_links)) {
                    if ($releaseFactory->userCanUpdate($group_id, $release->getReleaseID())) {
                        if ($releaseFactory->delete_release($group_id, $release->getReleaseID())) {
                            $deleted[] = release_to_soap($release);
                        } else {
                            return new SoapFault(INVALID_PACKAGE_FAULT, 'Release ' . $release->getReleaseID() . ' could not be deleted', 'deleteRelease');
                        }
                    } else {
                        return new SoapFault(INVALID_PACKAGE_FAULT, 'You don\'t have permission to delete package ' . $release->getReleaseID(), 'deleteRelease');
                    }
                }
            }
            return $deleted;
        } else {
            return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', 'deleteRelease');
        }
    }

    $server->addFunction(
        array(
            'getPackages',
            'addPackage',
            'getReleases',
            'addRelease',
            'updateRelease',
            'getFiles',
            'getFileInfo',
            'getFile',
            'getFileChunk',
            'addFile',
            'addFileChunk',
            'addUploadedFile',
            'getUploadedFiles',
            'deleteFile',
            'deleteEmptyPackage',
            'deleteEmptyRelease',
            'updateFileComment',
        )
    );
}
