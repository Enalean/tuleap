<?php
require_once ('nusoap.php');
require_once ('pre.php');
require_once ('session.php');
require_once('common/include/Error.class.php');
require_once('common/frs/FRSPackage.class.php');
require_once('common/frs/FRSPackageFactory.class.php');
require_once('common/frs/FRSRelease.class.php');
require_once('common/frs/FRSReleaseFactory.class.php');
require_once('common/frs/FRSFile.class.php');
require_once('common/frs/FRSFileFactory.class.php');

// define fault code constants
define('invalid_package_fault', '3017');
define('invalid_release_fault', '3018');
define('invalid_file_fault', '3019');

//
// Type definition
//
$server->wsdl->addComplexType(
    'FRSPackage',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'package_id' => array('name'=>'package_id', 'type' => 'xsd:int'),
        'group_id' => array('name'=>'group_id', 'type' => 'xsd:int'),
        'name' => array('name'=>'name', 'type' => 'xsd:string'),
        'status_id' => array('name'=>'status_id', 'type' => 'xsd:int'),
        'rank' => array('name'=>'rank', 'type' => 'xsd:int'),
        'approve_license' => array('name'=>'approve_license', 'type' => 'xsd:boolean'),
    )
);

$server->wsdl->addComplexType(
    'ArrayOfFRSPackage',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:FRSPackage[]')),
    'tns:FRSPackage'
);

$server->wsdl->addComplexType(
    'FRSRelease',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'release_id' => array('name'=>'release_id', 'type' => 'xsd:int'),
        'package_id' => array('name'=>'package_id', 'type' => 'xsd:int'),
        'name' => array('name'=>'name', 'type' => 'xsd:string'),
        'notes' => array('name'=>'notes', 'type' => 'xsd:string'),
        'changes' => array('name'=>'changes', 'type' => 'xsd:string'),
        'status_id' => array('name'=>'description', 'type' => 'xsd:string'),
        'preformatted' => array('name'=>'preformatted', 'type' => 'xsd:boolean'),
        'release_date' => array('name'=>'release_date', 'type' => 'xsd:int'),
        'released_by' => array('name'=>'released_by', 'type' => 'xsd:string'),
    )
);

$server->wsdl->addComplexType(
    'ArrayOfFRSRelease',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:FRSRelease[]')),
    'tns:FRSRelease'
);

$server->wsdl->addComplexType(
    'FRSFile',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'file_id' => array('name'=>'file_id', 'type' => 'xsd:int'),
        'release_id' => array('name'=>'release_id', 'type' => 'xsd:int'),
        'file_name' => array('name'=>'file_name', 'type' => 'xsd:string'),
        'file_size' => array('name'=>'file_size', 'type' => 'xsd:int'),
        'type_id' => array('name'=>'type_id', 'type' => 'xsd:int'),
        'processor_id' => array('name'=>'processor_id', 'type' => 'xsd:int'),
        'release_time' => array('name'=>'release_time', 'type' => 'xsd:int'),
        'post_date' => array('name'=>'post_date', 'type' => 'xsd:int'),
    )
);

$server->wsdl->addComplexType(
    'ArrayOfFRSFile',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:FRSFile[]')),
    'tns:FRSFile'
);

//
// Function definition
//
$server->register(
    'getPackages',
    array(
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int'),
    array('getPackagesResponse'=>'tns:ArrayOfFRSPackage'),
    $uri,
    $uri.'#getPackages',
    'rpc',
    'encoded',
    'Returns the array of FRSPackages that belongs to the group identified by group ID.
     Returns a soap fault if the group ID does not match with a valid project.'
);

$server->register(
    'addPackage',
    array(
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'package_name'=>'xsd:string',
        'status_id'=>'xsd:int',
        'rank'=>'xsd:int',
        'approve_license'=>'xsd:boolean'),
    array('addPackageResponse'=>'xsd:int'),
    $uri,
    $uri.'#addPackage',
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
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'package_id'=>'xsd:int'),
    array('getPackagesResponse'=>'tns:ArrayOfFRSRelease'),
    $uri,
    $uri.'#getReleases',
    'rpc',
    'encoded',
    'Returns the array of FRSReleases that belongs to the group identified by group ID and 
     to the package identified by package_id.
     Returns a soap fault if the group ID does not match with a valid project, or if the package ID
     does not match with the right group ID.'
);

$server->register(
    'addRelease',
    array(
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'package_id'=>'xsd:int',
        'name'=>'xsd:string',
        'notes'=>'xsd:string',
        'changes'=>'xsd:string',
        'status_id'=>'xsd:int',
        'release_date'=>'xsd:int'),
    array('addRelease'=>'xsd:int'),
    $uri,
    $uri.'#addRelease',
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
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'package_id'=>'xsd:int',
        'release_id'=>'xsd:int'),
    array('getFilesResponse'=>'tns:ArrayOfFRSFile'),
    $uri,
    $uri.'#getFiles',
    'rpc',
    'encoded',
    'Returns the array of FRSFiles that belongs to the group identified by group ID, 
     to the package identified by package_id and to the release identfied by release_id.
     Returns a soap fault if the group ID does not match with a valid project, or if the package ID
     does not match with the right group ID, or if the release ID does not match with the right package ID.'
);

$server->register(
    'getFile',
    array(
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'package_id'=>'xsd:int',
        'release_id'=>'xsd:int',
        'file_id'=>'xsd:int'),
    array('getFileResponse'=>'xsd:string'),
    $uri,
    $uri.'#getFile',
    'rpc',
    'encoded',
    'Returns the <strong>content</strong> (encoded in base64) of the file contained in 
     the release release_id in the package package_id, in the project group_id.
     Returns a soap fault if the group ID does not match with a valid project, or if the package ID
     does not match with the right group ID, or if the release ID does not match with the right package ID,
     or if the file ID does not match with the right release ID.'
);

$server->register(
    'addFile',
    array(
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'package_id'=>'xsd:int',
        'release_id'=>'xsd:int',
        'filename'=>'xsd:string',
        'base64_contents'=>'xsd:string',
        'type_id'=>'xsd:int',
        'processor_id'=>'xsd:int'
        ),
    array('addFile'=>'xsd:string'),
    $uri,
    $uri.'#addFile',
    'rpc',
    'encoded',
    'Add a File to the File Release Manager of the project group_id with the values given by 
     package_id, release_id, filename, base64_contents, type_id and processor_id. 
     The content of the file must be encoded in base64.
     Returns the ID of the created file if the creation succeed.
     Returns a soap fault if the group_id is not a valid one, 
     if the package does not match with the group ID, 
     if the release does not match with the package ID,
     or if the add failed.'
);

$server->register(
    'addUploadedFile',
    array(
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'package_id'=>'xsd:int',
        'release_id'=>'xsd:int',
        'filename'=>'xsd:string',
        'type_id'=>'xsd:int',
        'processor_id'=>'xsd:int'
        ),
    array('addUploadedFile'=>'xsd:string'),
    $uri,
    $uri.'#addUploadedFile',
    'rpc',
    'encoded',
    'Add a File to the File Release Manager of the project group_id with the values given by 
     package_id, release_id, filename, type_id and processor_id. 
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
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int'
        ),
    array('getUploadedFilesResponse'=>'tns:ArrayOfstring'),
    $uri,
    $uri.'#getUploadedFiles',
    'rpc',
    'encoded',
    'Get the file names of the file present in the incoming directory on the server.'
);

//
// Function implementation
//

/**
 * getPackages - returns an array of FRSPackages that belongs to the project identified by group_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the array of packages
 * @return array the array of SOAPFRSPackage that belongs to the project identified by $group_id, or a soap fault if group_id does not match with a valid project.
 */
function getPackages($sessionKey,$group_id) {
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new soap_fault(get_group_fault,'getPackages','Could Not Get Group','');
        } elseif ($group->isError()) {
            return new soap_fault(get_group_fault, 'getPackages', $group->getErrorMessage(),'');
        }
        if (!checkRestrictedAccess($group)) {
            return new soap_fault(get_group_fault, 'getPackages', 'Restricted user: permission denied.', '');
        }
        
        $pkg_fact = new FRSPackageFactory();
        // we get only the active packages, even if we are project admin or file admin
        $packages =& $pkg_fact->getFRSPackagesFromDb($group_id, 1); // 1 for active packages
        return packages_to_soap($packages);
    } else {
        return new soap_fault(invalid_session_fault,'getPackages','Invalid Session','');
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
function package_to_soap($package) {
    $return = null;
    if ($package->isError()) {
        //skip if error
    } else {
        // check if current user is allowed to see this package
        if ($package->userCanRead()) {
            $return=array(
                'package_id' => $package->getPackageID(),
                'group_id' => $package->getGroupID(),
                'name' => $package->getName(),
                'status_id' => $package->getStatusID(),
                'rank' => $package->getRank(),
                'approve_license' => $package->getApproveLicense()
                );
        } 
    }
    return $return;
}

function packages_to_soap(&$pkg_arr) {
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
function addPackage($sessionKey,$group_id,$package_name,$status_id,$rank=0,$approve_license=true) {
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new soap_fault(get_group_fault,'addPackage','Could Not Get Group','');
        } elseif ($group->isError()) {
            return new soap_fault(get_group_fault, 'addPackage', $group->getErrorMessage(),'');
        }
        if (!checkRestrictedAccess($group)) {
            return new soap_fault(get_group_fault, 'addPackage', 'Restricted user: permission denied.', '');
        }
        
        $pkg_fact = new FRSPackageFactory();
        if ($pkg_fact->userCanCreate($group_id)) {
            // we check that the package name don't already exist
            if ($pkg_fact->isPackageNameExist($package_name, $group_id)) {
                return new soap_fault('', 'addPackage', 'Package name already exists in this project', '');
            } else {
                $dao =& $pkg_fact->_getFRSPackageDao();
                $dar = $dao->create($group->getID(), $package_name, $status_id, $rank, $approve_license);
                if (!$dar) {
                    return new soap_fault('', 'addPackage', $dar->isError(), '');
                } else {
                    // if there is no error, $dar contains the package_id
                    return $dar;
                }
            }
        } else {
            return new soap_fault('','addPackage','User is not allowed to create a package','');
        }
    } else {
        return new soap_fault(invalid_session_fault,'addPackage','Invalid Session','');
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
function getReleases($sessionKey,$group_id,$package_id) {
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new soap_fault(get_group_fault,'getReleases','Could Not Get Group','');
        } elseif ($group->isError()) {
            return new soap_fault(get_group_fault, 'getReleases', $group->getErrorMessage(),'');
        }
        if (!checkRestrictedAccess($group)) {
            return new soap_fault(get_group_fault, 'getReleases', 'Restricted user: permission denied.', '');
        }
        
        // retieve the package
        $pkg_fact = new FRSPackageFactory();
        $package =& $pkg_fact->getFRSPackageFromDb($package_id);
        if (!$package || $package->getGroupID() != $group_id) {
            return new soap_fault(invalid_package_fault,'getReleases','Invalid Package','');
        }
        // check access rights to this package
        if (! $package->userCanRead() || ! $package->isActive()) {
            return new soap_fault(invalid_package_fault,'getReleases','Permission to this Package denied','');
        }
        
        $release_fact = new FRSReleaseFactory();
        // we get only the active releases, even if we are project admin or file admin
        $releases =& $release_fact->getFRSReleasesFromDb($package_id, 1, $group_id); // 1 for active releases
        return releases_to_soap($releases);
    } else {
        return new soap_fault(invalid_session_fault,'getReleases','Invalid Session','');
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
function release_to_soap($release) {
    $return = null;
    if ($release->isError()) {
        //skip if error
    } else {
        // check if the user can view 
        if ($release->userCanRead()) {
            $return = array(
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
    }
    return $return;
}

function releases_to_soap($release_arr) {
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
function addRelease($sessionKey,$group_id,$package_id,$name,$notes,$changes,$status_id,$release_date) {
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new soap_fault(get_group_fault,'addRelease','Could Not Get Group','');
        } elseif ($group->isError()) {
            return new soap_fault(get_group_fault, 'addRelease', $group->getErrorMessage(),'');
        }
        if (!checkRestrictedAccess($group)) {
            return new soap_fault(get_group_fault, 'addRelease', 'Restricted user: permission denied.', '');
        }
        
        // retieve the package
        $pkg_fact = new FRSPackageFactory();
        $package =& $pkg_fact->getFRSPackageFromDb($package_id);
        if (!$package || $package->getGroupID() != $group_id) {
            return new soap_fault(invalid_package_fault,'addRelease','Invalid Package','');
        }
        
        $release_fact = new FRSReleaseFactory();
        if ($release_fact->userCanCreate($group_id)) {
            if ($release_fact->isReleaseNameExist($name, $package_id)) {
                return new soap_fault('', 'addRelease', 'Release name already exists in this package', '');
            } else {
                $dao =& $release_fact->_getFRSReleaseDao();
                $dar = $dao->create($package_id, $name, $notes, $changes, $status_id, 0, $release_date);
                if (!$dar) {
                    return new soap_fault('', 'addRelease', $dar->isError(), '');
                } else {
                    // if there is no error, $dar contains the release_id
                    return $dar;
                }
            }
        } else {
            return new soap_fault('', 'addRelease', 'User is not allowed to create a release', '');
        }
    } else {
        return new soap_fault(invalid_session_fault,'addRelease','Invalid Session','');
    }
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
function getFiles($sessionKey,$group_id,$package_id,$release_id) {
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new soap_fault(get_group_fault,'getFiles','Could Not Get Group','');
        } elseif ($group->isError()) {
            return new soap_fault(get_group_fault, 'getFiles', $group->getErrorMessage(),'');
        }
        if (!checkRestrictedAccess($group)) {
            return new soap_fault(get_group_fault, 'getFiles', 'Restricted user: permission denied.', '');
        }
        
        // retieve the package
        $pkg_fact = new FRSPackageFactory();
        $package =& $pkg_fact->getFRSPackageFromDb($package_id);
        if (!$package || $package->getGroupID() != $group_id) {
            return new soap_fault(invalid_package_fault,'getFiles','Invalid Package','');
        }
        // check access rights to this package
        if (! $package->userCanRead() || ! $package->isActive()) {
            return new soap_fault(invalid_package_fault,'getFiles','Permission to this Package denied','');
        }
        
        // retrieve the release
        $release_fact = new FRSReleaseFactory();
        $release =& $release_fact->getFRSReleaseFromDb($release_id);
        if (!$release || $release->getPackageID() != $package_id) {
            return new soap_fault(invalid_release_fault,'getFiles','Invalid Release','');
        }
        // check access rights to this release
        if (! $release->userCanRead() || ! $release->isActive()) {
            return new soap_fault(invalid_release_fault,'getFiles','Permission to this Release denied','');
        }
        
        $files_arr =& $release->getFiles();
        return files_to_soap($files_arr);
    } else {
        return new soap_fault(invalid_session_fault,'getFiles','Invalid Session','');
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
function file_to_soap($file) {
    $return = null;
    if ($file->isError()) {
        //skip if error
    } else {
        // for the moment, no permissions on files
        $return = array(
            'file_id' => $file->getFileID(),
            'release_id' => $file->getReleaseID(),
            'file_name' => $file->getFileName(),
            'file_size' => $file->getFileSize(),
            'type_id' => $file->getTypeID(),
            'processor_id' => $file->getProcessorID(),
            'release_time' => $file->getReleaseTime(),
            'post_date' => $file->getPostDate(),
        );
    }
    return $return;
}

function files_to_soap($files_arr) {
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
function getFile($sessionKey,$group_id,$package_id,$release_id,$file_id) {
    if (session_continue($sessionKey)) {
    
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new soap_fault(get_group_fault,'getFile','Could Not Get Group','');
        } elseif ($group->isError()) {
            return new soap_fault(get_group_fault, 'getFile', $group->getErrorMessage(),'');
        }
        if (!checkRestrictedAccess($group)) {
            return new soap_fault(get_group_fault, 'getFile', 'Restricted user: permission denied.', '');
        }

        // retieve the package
        $pkg_fact = new FRSPackageFactory();
        $package =& $pkg_fact->getFRSPackageFromDb($package_id);
        if (!$package || $package->getGroupID() != $group_id) {
            return new soap_fault(invalid_package_fault,'getFile','Invalid Package','');
        }
        // check access rights to this package
        if (! $package->userCanRead() || ! $package->isActive()) {
            return new soap_fault(invalid_package_fault,'getFiles','Permission to this Package denied','');
        }
        
        // retrieve the release
        $release_fact = new FRSReleaseFactory();
        $release =& $release_fact->getFRSReleaseFromDb($release_id);
        if (!$release || $release->getPackageID() != $package_id) {
            return new soap_fault(invalid_release_fault,'getFile','Invalid Release','');
        }
        // check access rights to this release
        if (! $release->userCanRead() || ! $release->isActive()) {
            return new soap_fault(invalid_release_fault,'getFiles','Permission to this Release denied','');
        }
        
        $file_fact = new FRSFileFactory();
        $file =& $file_fact->getFRSFileFromDb($file_id);
        if (!$file || $file->getReleaseID() != $release_id) {
            return new soap_fault(invalid_file_fault,'getFile','Invalid File','');
        }
        
        if (!$file->fileExists()) {
            return new soap_fault(invalid_file_fault,'getFile','File doesn\'t exist on the server','');
        }
        
        // Log the download action
        $file->logDownload();
        
        $contents = $file->getContent();
        return base64_encode($contents);
    } else {
        return new soap_fault(invalid_session_fault,'getFile','Invalid Session','');
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
function addFile($sessionKey,$group_id,$package_id,$release_id,$filename,$base64_contents,$type_id,$processor_id) {
    if (session_continue($sessionKey)) {

        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new soap_fault(get_group_fault,'addFile','Could Not Get Group','');
        } elseif ($group->isError()) {
            return new soap_fault(get_group_fault, 'addFile', $group->getErrorMessage(),'');
        }
        if (!checkRestrictedAccess($group)) {
            return new soap_fault(get_group_fault, 'addFile', 'Restricted user: permission denied.', '');
        }
        
        // retieve the package
        $pkg_fact = new FRSPackageFactory();
        $package =& $pkg_fact->getFRSPackageFromDb($package_id);
        if (!$package || $package->getGroupID() != $group_id) {
            return new soap_fault(invalid_package_fault,'addFile','Invalid Package','');
        }
        
        // retrieve the release
        $release_fact = new FRSReleaseFactory();
        $release =& $release_fact->getFRSReleaseFromDb($release_id);
        if (!$release || $release->getPackageID() != $package_id) {
            return new soap_fault(invalid_release_fault,'addFile','Invalid Release','');
        }
        
        $file_fact = new FRSFileFactory();
        if ($file_fact->userCanAdd($group_id)) {
            $tmpname = tempnam("/tmp", "codex_soap_frs");
            $fh = fopen($tmpname, "wb");
            if (!$fh) {
                return new soap_fault('','addFile','Could not create temporary file in directory /tmp', '');
            }
            fwrite($fh, base64_decode($base64_contents));
            fclose($fh);
            
            // move the file in the incoming dir
            if (! rename($tmpname, $GLOBALS['ftp_incoming_dir'].'/'.basename($filename))) {
                return new soap_fault('','addFile','Impossible to move the file in the incoming dir: '.$GLOBALS['ftp_incoming_dir'],'');
            }
            
            // call addUploadedFile function
            $uploaded_filename = basename($filename);
            return addUploadedFile($sessionKey,$group_id,$package_id,$release_id,$uploaded_filename,$type_id,$processor_id);
            
        } else {
            return new soap_fault('', 'addFile', 'User is not allowed to add a file', '');
        }
    } else {
        return new soap_fault(invalid_session_fault,'addFile','Invalid Session','');
    }
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
function addUploadedFile($sessionKey,$group_id,$package_id,$release_id,$filename,$type_id,$processor_id) {
    if (session_continue($sessionKey)) {

        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new soap_fault(get_group_fault,'addUploadedFile','Could Not Get Group','');
        } elseif ($group->isError()) {
            return new soap_fault(get_group_fault, 'addUploadedFile', $group->getErrorMessage(),'');
        }
        if (!checkRestrictedAccess($group)) {
            return new soap_fault(get_group_fault, 'addUploadedFile', 'Restricted user: permission denied.', '');
        }
        
        // retieve the package
        $pkg_fact = new FRSPackageFactory();
        $package =& $pkg_fact->getFRSPackageFromDb($package_id);
        if (!$package || $package->getGroupID() != $group_id) {
            return new soap_fault(invalid_package_fault,'addUploadedFile','Invalid Package','');
        }
        
        // retrieve the release
        $release_fact = new FRSReleaseFactory();
        $release =& $release_fact->getFRSReleaseFromDb($release_id);
        if (!$release || $release->getPackageID() != $package_id) {
            return new soap_fault(invalid_release_fault,'addUploadedFile','Invalid Release','');
        }
        
        $file_fact = new FRSFileFactory();
        if ($file_fact->userCanAdd($group_id)) {
            if (! $file_fact->isFileBaseNameExists($filename, $release->getReleaseID(), $group_id)) {
                $file_id = $file_fact->createFromIncomingFile(basename($filename),$release_id,$type_id,$processor_id);
                if (! $file_id) {
                    return new soap_fault('','addUploadedFile',$file_fact->getErrorMessage(),'');
                } else {
                    return $file_id;
                }
            } else {
                return new soap_fault('', 'addUploadedFile', 'Filename "'.$filename.'" already exists', '');
            }
        } else {
            return new soap_fault('', 'addUploadedFile', 'User is not allowed to add a file', '');
        }
    } else {
        return new soap_fault(invalid_session_fault,'addUploadedFile','Invalid Session','');
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
function getUploadedFiles($sessionKey, $group_id) {
    if (session_continue($sessionKey)) {
        $file_fact = new FRSFileFactory();
        if ($file_fact->userCanAdd($group_id)) {
            $soap_files = array();
            $file_names = $file_fact->getUploadedFileNames();
            return $file_names;
        } else {
            return new soap_fault('', 'getUploadedFiles', 'User not allowed to see the uploaded files', '');
        }
    } else {
        return new soap_fault(invalid_session_fault,'getUploadedFiles','Invalid Session','');
    }
}
