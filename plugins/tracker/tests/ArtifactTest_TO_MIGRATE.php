<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * Tests the class Artifact
 */
class ArtifactTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function ArtifactTest($name = 'Artifact test') {
        $this->UnitTestCase($name);
    }
/* OBSOLETE
    function testAddDependenciesSimple() {
        $a =& new ArtifactTestVersion($this);
        $a->setReturnValue('insertDependency', true);
        $a->setReturnValue('validArtifact', true);
        $a->setReturnValue('existDependency', false);
        $changes = null;
        $this->assertTrue($a->addDependencies("171",&$changes,false), "It should be possible to add a dependency like 171");
    }

    function testAddWrongDependency() {
        $GLOBALS['Response'] = new MockResponse();
        $a =& new ArtifactTestVersion($this);
        $a->setReturnValue('insertDependency', true);
        $a->setReturnValue('validArtifact', false);
        //$a->setReturnValue('existDependency', false);
        $changes = null;
        $this->assertFalse($a->addDependencies("99999",&$changes,false), "It should be possible to add a dependency like 99999 because it is not a valid artifact");
        $GLOBALS['Response']->expectCallCount('addFeedback', 2);

    }

    function testAddDependenciesDouble() {
        $a =& new ArtifactTestVersion($this);
        $a->setReturnValue('insertDependency', true);
        $a->setReturnValue('validArtifact', true);
        $a->setReturnValue('existDependency', false);
        $a->setReturnValueAt(0, 'existDependency', false);
        $a->setReturnValueAt(1, 'existDependency', true);
        $changes = null;
        $this->assertTrue($a->addDependencies("171, 171",&$changes,false), "It should be possible to add two identical dependencies in the same time, without getting an exception");
    }
    */
}
?>
