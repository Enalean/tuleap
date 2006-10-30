<?php
require_once('common/tracker/ArtifactRuleValue.class');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: ArtifactRuleValueTest.php 1901 2005-08-18 14:54:55Z nterray $
 *
 * Tests the class ArtifactRuleValue
 */
class ArtifactRuleValueTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function ArtifactRuleValueTest($name = 'ArtifactRuleValue test') {
        $this->UnitTestCase($name);
    }
    
    function testApplyTo() {
        $arv =& new ArtifactRuleValue('id', 'group_artifact_id', 'source_field', 'source_value', 'target_field', 'target_value');
        
        $this->assertTrue( $arv->applyTo('group_artifact_id',       'source_field',        'source_value',       'target_field',       'target_value'      ));
        $this->assertFalse($arv->applyTo('group_artifact_id',       'source_field',        'source_value',       'target_field',       'false_target_value'));
        $this->assertFalse($arv->applyTo('false_group_artifact_id', 'source_field',        'source_value',       'target_field',       'farget_value'      ));
        $this->assertFalse($arv->applyTo('false_group_artifact_id', 'source_field',        'source_value',       'target_field',       'false_target_value'));
        $this->assertFalse($arv->applyTo('group_artifact_id',       'target_source_field', 'source_value',       'target_field',       'false_value'       ));
        $this->assertFalse($arv->applyTo('group_artifact_id',       'target_source_field', 'source_value',       'target_field',       'false_target_value'));
        $this->assertFalse($arv->applyTo('group_artifact_id',       'source_field',        'false_source_value', 'target_field',       'false_value'       ));
        $this->assertFalse($arv->applyTo('group_artifact_id',       'source_field',        'false_source_value', 'target_field',       'false_target_value'));
        $this->assertFalse($arv->applyTo('group_artifact_id',       'source_field',        'source_value',       'false_target_field', 'false_value'       ));
        $this->assertFalse($arv->applyTo('group_artifact_id',       'source_field',        'source_value',       'false_target_field', 'false_target_value'));
    }
    function testCanApplyTo() {
        $arv =& new ArtifactRuleValue('id', 'group_artifact_id', 'source_field', 'source_value', 'target_field', 'target_value');
        
        $this->assertTrue( $arv->canApplyTo('group_artifact_id',       'source_field',        'source_value',       'target_field',       'target_value'      ));
        $this->assertTrue( $arv->canApplyTo('group_artifact_id',       'source_field',        'source_value',       'target_field',       'false_target_value'));
        $this->assertFalse($arv->canApplyTo('false_group_artifact_id', 'source_field',        'source_value',       'target_field',       'farget_value'      ));
        $this->assertFalse($arv->canApplyTo('false_group_artifact_id', 'source_field',        'source_value',       'target_field',       'false_target_value'));
        $this->assertFalse($arv->canApplyTo('group_artifact_id',       'target_source_field', 'source_value',       'target_field',       'false_value'       ));
        $this->assertFalse($arv->canApplyTo('group_artifact_id',       'target_source_field', 'source_value',       'target_field',       'false_target_value'));
        $this->assertFalse($arv->canApplyTo('group_artifact_id',       'source_field',        'false_source_value', 'target_field',       'false_value'       ));
        $this->assertFalse($arv->canApplyTo('group_artifact_id',       'source_field',        'false_source_value', 'target_field',       'false_target_value'));
        $this->assertFalse($arv->canApplyTo('group_artifact_id',       'source_field',        'source_value',       'false_target_field', 'false_value'       ));
        $this->assertFalse($arv->canApplyTo('group_artifact_id',       'source_field',        'source_value',       'false_target_field', 'false_target_value'));
    }
}
?>
