<?php
require_once('common/tracker/ArtifactRuleValue.class.php');

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * Tests the class ArtifactRuleValue
 */
class ArtifactRuleValueTest extends TuleapTestCase {

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
