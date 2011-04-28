<?php

require_once(dirname(__FILE__).'/../include/SalomeTMFTracker.class.php');

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * Test the class SalomeTMFTracker
 */
class SalomeTMFTrackerTest extends UnitTestCase {
	
	var $row;
	
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function SalomeTMFTrackerTest($name = 'SalomeTMFTracker test') {
        $this->UnitTestCase($name);
    }
    
    function setUp() {
    	$this->row = array();
    	$this->row['group_artifact_id'] = 102;
        $this->row['report_id'] = 100;
        $this->row['environment_field'] = 'slm_environment';
        $this->row['campaign_field'] = 'slm_campaign';
        $this->row['family_field'] = 'slm_family';
        $this->row['suite_field'] = 'slm_suite';
        $this->row['test_field'] = 'slm_test';
        $this->row['action_field'] = 'slm_action';
        $this->row['execution_field'] = 'slm_execution';
        $this->row['dataset_field'] = 'slm_dataset'; 
    }
    function tearDown() {
        unset($this->row);
    }
    
    function testIsWellConfigured() {
        $t = new SalomeTMFTracker($this->row);
        $this->assertTrue($t->isWellConfigured());
    }
    
	function testIsNotWellConfiguredMissingEverything() {
        $this->row = array();
        
    	$t = new SalomeTMFTracker($this->row);
        $this->assertError();  // 
        $this->assertError();  // 
        $this->assertError();  // 
        $this->assertError();  // 
        $this->assertError();  // 1 error for each missing field
        $this->assertError();  // 
        $this->assertError();  // 
        $this->assertError();  // 
        $this->assertError();  // 
        $this->assertError();  // 
        $this->assertFalse($t->isWellConfigured());
    }
    
    function testIsNotWellConfiguredMissingTrackerId() {
        unset($this->row['group_artifact_id']);
        
        $t = new SalomeTMFTracker($this->row);
        $this->assertError();
        $this->assertFalse($t->isWellConfigured());
    }
    
	function testIsNotWellConfiguredMissingReportId() {
		unset($this->row['report_id']);
        
    	$t = new SalomeTMFTracker($this->row);
        $this->assertError();
        $this->assertFalse($t->isWellConfigured());
    }
    
	function testIsNotWellConfiguredTrackerIdNull() {
        $this->row['group_artifact_id'] = null;
        
    	$t = new SalomeTMFTracker($this->row);
        $this->assertFalse($t->isWellConfigured());
    }
    
	function testIsNotWellConfiguredEnvironmentEquals0() {
        $this->row['environment_field'] = 0;
        
    	$t = new SalomeTMFTracker($this->row);
        $this->assertFalse($t->isWellConfigured());
    }
    
	function testIsNotWellConfiguredFamilyEquals0() {
        $this->row['family_field'] = 0;
        
    	$t = new SalomeTMFTracker($this->row);
        $this->assertFalse($t->isWellConfigured());
    }
    
	function testIsNotWellConfiguredDatasetEquals0() {
        $this->row['dataset_field'] = 0;
        
    	$t = new SalomeTMFTracker($this->row);
        $this->assertFalse($t->isWellConfigured());
    }
    
}

?>
