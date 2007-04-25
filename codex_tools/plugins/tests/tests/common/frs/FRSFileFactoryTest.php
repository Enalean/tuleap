<?php

//{{{ Ugly fix to by pass error with ugroup_utils
//TODO: Fix it !
require_once('BaseLanguage.class.php');
$name = 'Fake_BaseLanguage_'. md5(uniqid(rand(), true));
eval("class $name extends BaseLanguage {}");
$GLOBALS['Language'] = new $name();
//}}}

require_once('common/frs/FRSFileFactory.class.php');
require_once('common/frs/FRSReleaseFactory.class.php');

Mock::generate('FRSReleaseFactory');
Mock::generate('FRSRelease');
Mock::generatePartial('FRSFileFactory', 'FRSFileFactoryTestVersion', array('_getFRSReleaseFactory'));

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * Tests the FRSFileFactory class
 */
class FRSFileFactoryTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function FRSFileFactoryTest($name = 'FRSfileFactory test') {
        $this->UnitTestCase($name);
    }

    function testgetUploadSubDirectory() {
        $package_id = rand(1, 1000);
        $release_id = rand(1, 1000);
        
        $release =& new MockFRSRelease($this);
        $release->setReturnValue('getPackageID', $package_id);
        $release->setReturnValue('getReleaseID', $release_id);
        
        $release_fact =& new MockFRSReleaseFactory($this);
        $release_fact->setReturnReference('getFRSReleaseFromDb', $release);
        
        $file_fact =& new FRSFileFactoryTestVersion();
        $file_fact->setReturnReference('_getFRSReleaseFactory', $release_fact);
        $file_fact->FRSFileFactory();
        
        $sub_dir = $file_fact->getUploadSubDirectory($release_id);
        $this->assertEqual($sub_dir, 'p'.$package_id.'_r'.$release_id);
    }

}
?>
