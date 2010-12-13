<?php

require_once ('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once('common/include/Error.class.php');
require_once('common/tracker/Artifact.class.php');

Mock::generatePartial('Artifact', 'ArtifactTestVersion', array('insertDependency', 'validArtifact', 'existDependency'));

require_once('common/include/Response.class.php');
Mock::generate('Response');

require_once('common/include/Codendi_HTMLPurifier.class.php');
Mock::generate('Codendi_HTMLPurifier');

require_once('www/include/utils.php');

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

    function setUp() {
        $GLOBALS['Language'] = new MockBaseLanguage($this);
    }

    function tearDown() {
        unset($GLOBALS['Language']);
    }

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
    
    function testFormatFollowUp() {
        $art = new ArtifactTestVersion($this);
        $hp = new MockCodendi_HTMLPurifier($this);

        $txtContent = 'testing the feature';
        $htmlContent = '&lt;pre&gt;   function processEvent($event, $params) {&lt;br /&gt;       foreach(parent::processEvent($event, $params) as $key =&amp;gt; $value) {&lt;br /&gt;           $params[$key] = $value;&lt;br /&gt;       }&lt;br /&gt;   }&lt;br /&gt;&lt;/pre&gt; ';
        //the output will be delivered in a mail
        $this->assertEqual('   function processEvent($event, $params) {       foreach(parent::processEvent($event, $params) as $key => $value) {           $params[$key] = $value;       }   } ' , $art->formatFollowUp(102, 1,$htmlContent, 2));
        $this->assertEqual($txtContent, $art->formatFollowUp(102, 0,$txtContent,2));
        
        //the output is destinated to be exported
        $this->assertEqual('<pre>   function processEvent($event, $params) {<br />       foreach(parent::processEvent($event, $params) as $key =&gt; $value) {<br />           $params[$key] = $value;<br />       }<br />   }<br /></pre> ', $art->formatFollowUp(102, 1,$htmlContent,1));
        $this->assertEqual($txtContent, $art->formatFollowUp(102, 0,$txtContent,1));
        
        //The output will be displayed on browser
        $this->assertEqual('<pre>   function processEvent($event, $params) {<br />       foreach(parent::processEvent($event, $params) as $key =&gt; $value) {<br />           $params[$key] = $value;<br />       }<br />   }<br /></pre> ', $art->formatFollowUp(102, 1,$htmlContent, 0));
        $this->assertEqual($txtContent, $art->formatFollowUp(102, 0,$txtContent, 0));
    }
}
?>
