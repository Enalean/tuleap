<?php

require_once (dirname(__FILE__) . '/../hooks/ExtractCrossRef.class.php');

/**
 * Description of ExtractCrossRefTest
 */
class ExtractCrossRefTest extends UnitTestCase {

    public function setUp() {
        $this->extractor = new ExtractCrossRef();
    }

    function testExtractsGroupNameFromProjectRepos() {
        $this->assertEqual('myproject', $this->extractor->getProjectName('/gitroot/myproject/stuff.git'));
        $this->assertEqual('gpig', $this->extractor->getProjectName('/gitolite/repositories/gpig/dalvik.git'));
        $this->assertEqual('gpig', $this->extractor->getProjectName('/var/lib/codendi/gitolite/repositories/gpig/dalvik.git'));
        $this->assertEqual('gpig', $this->extractor->getProjectName('/var/lib/codendi/gitroot/gpig/dalvik.git'));
    }

    function testExtractsGroupNameFromPersonalRepos() {
        $this->assertEqual('gpig', $this->extractor->getProjectName('/var/lib/codendi/gitolite/repositories/gpig/u/manuel/dalvik.git'));
        $this->assertEqual('gpig', $this->extractor->getProjectName('/var/lib/codendi/gitroot/gpig/u/manuel/dalvik.git'));
    }
    
    function testExtractsGroupNameThrowsAnExceptionWhenNoProjectNameFound() {
        $this->expectException('GitNoProjectFound');
        $this->extractor->getProjectName('/non_existing_path/dalvik.git');
    }
}
?>
