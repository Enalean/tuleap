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
        $this->assertEqual('gpig', $this->extractor->getProjectName('/var/lib/tuleap/gitolite/repositories/gpig/dalvik.git'));
        $this->assertEqual('gpig', $this->extractor->getProjectName('/var/lib/tuleap/gitroot/gpig/dalvik.git'));
    }
    
    function testExtractsTheNameAfterTheFirstOccurrenceOfRootPath() {
        $this->assertEqual('gitroot', $this->extractor->getProjectName('/gitroot/gitroot/stuff.git'));
    }

    function testExtractsGroupNameFromPersonalRepos() {
        $this->assertEqual('gpig', $this->extractor->getProjectName('/var/lib/tuleap/gitolite/repositories/gpig/u/manuel/dalvik.git'));
        $this->assertEqual('gpig', $this->extractor->getProjectName('/var/lib/tuleap/gitroot/gpig/u/manuel/dalvik.git'));
    }
    
    function testExtractsGroupNameFromSymlinkedRepo() {
        $this->assertEqual('chene', $this->extractor->getProjectName('/data/codendi/gitroot/chene/gitshell.git'));
    }
    
    function testExtractsGroupNameThrowsAnExceptionWhenNoProjectNameFound() {
        $this->expectException('GitNoProjectFoundException');
        $this->extractor->getProjectName('/non_existing_path/dalvik.git');
    }
}
?>
