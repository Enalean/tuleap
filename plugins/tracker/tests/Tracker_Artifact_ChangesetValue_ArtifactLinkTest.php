<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This list is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once(dirname(__FILE__).'/../include/Tracker/Artifact/Tracker_Artifact.class.php');
Mock::generate('Tracker_Artifact');

require_once(dirname(__FILE__).'/../include/Tracker/Artifact/Tracker_ArtifactLinkInfo.class.php');
Mock::generate('Tracker_ArtifactLinkInfo');

require_once(dirname(__FILE__).'/../include/Tracker/Artifact/Tracker_Artifact_ChangesetValue_ArtifactLink.class.php');
Mock::generatePartial(
    'Tracker_Artifact_ChangesetValue_ArtifactLink',
    'Tracker_Artifact_ChangesetValue_ArtifactLinkTestVersion',
    array(
        'getArtifactIds'
    )
);

require_once(dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElement_Field_ArtifactLink.class.php');
Mock::generate('Tracker_FormElement_Field_ArtifactLink');

require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

class Tracker_Artifact_ChangesetValue_ArtifactLinkTest extends UnitTestCase {
    
    function __construct($name = 'Changeset Value ArtifactLink Test') {
        parent::__construct($name);
        $this->field_class          = 'MockTracker_FormElement_Field_ArtifactLink';
        $this->changesetvalue_class = 'Tracker_Artifact_ChangesetValue_ArtifactLink';
        $this->artlink_info_123 = new MockTracker_ArtifactLinkInfo();
        $this->artlink_info_123->setReturnValue('getArtifactId', '123');
        $this->artlink_info_123->setReturnValue('getKeyword', 'bug');
        $this->artlink_info_123->setReturnValue('getUrl', 'bug #123'); // for test
	$this->artlink_info_123->setReturnValue('__toString', 'bug #123'); // for test
        $this->artlink_info_321 = new MockTracker_ArtifactLinkInfo();
        $this->artlink_info_321->setReturnValue('getArtifactId', '321');
        $this->artlink_info_321->setReturnValue('getKeyword', 'task');
        $this->artlink_info_321->setReturnValue('getUrl', 'task #321'); // for test
        $this->artlink_info_321->setReturnValue('__toString', 'task #321'); // for test
        $this->artlink_info_666 = new MockTracker_ArtifactLinkInfo();
        $this->artlink_info_666->setReturnValue('getArtifactId', '666');
        $this->artlink_info_666->setReturnValue('getKeyword', 'sr');
        $this->artlink_info_666->setReturnValue('getUrl', 'sr #666'); // for test
        $this->artlink_info_666->setReturnValue('__toString', 'sr #666'); // for test
        $this->artlink_info_999 = new MockTracker_ArtifactLinkInfo();
        $this->artlink_info_999->setReturnValue('getArtifactId', '999');
        $this->artlink_info_999->setReturnValue('getKeyword', 'story');
        $this->artlink_info_999->setReturnValue('getUrl', 'story #999'); // for test
        $this->artlink_info_999->setReturnValue('__toString', 'story #999'); // for test
    }
    
    function testNoDiff() {
        $field  = new $this->field_class();
        $art_links_1 = array('123' => $this->artlink_info_123, '321' => $this->artlink_info_321, '999' => $this->artlink_info_999);
        $art_links_2 = array('999' => $this->artlink_info_999, '123' => $this->artlink_info_123, '321' => $this->artlink_info_321);
        $list_1 = new $this->changesetvalue_class(111, $field, false, $art_links_1);
        $list_2 = new $this->changesetvalue_class(111, $field, false, $art_links_2);
        $this->assertFalse($list_1->diff($list_2));
        $this->assertFalse($list_2->diff($list_1));
    }
    
    function testHasChangesNoChanges() {
        $field  = new Tracker_Artifact_ChangesetValue_ArtifactLinkTestVersion();
        $empty_array = array();
        $field->setReturnReference('getArtifactIds', $empty_array);
        $new_value = array('new_values' => '');
        $this->assertFalse($field->hasChanges($new_value));
    }
    
    function testHasChangesNoChanges2() {
        $field  = new Tracker_Artifact_ChangesetValue_ArtifactLinkTestVersion();
        $art_ids = array('1','2','3');
        $field->setReturnReference('getArtifactIds', $art_ids);
        $new_value = array('new_values' => '3,2,1');
        $this->assertFalse($field->hasChanges($new_value));
    }
    
    function testHasChangesWithChanges() {
        $field  = new Tracker_Artifact_ChangesetValue_ArtifactLinkTestVersion();
        $art_ids = array('1','2','3');
        $field->setReturnReference('getArtifactIds', $art_ids);
        $new_value = array('new_values' => '4,6');
        $this->assertTrue($field->hasChanges($new_value));
    }
    
    function testDiff_cleared() {
        $field  = new $this->field_class();
        $list_1 = new $this->changesetvalue_class(111, $field, false, array());
        $art_links_2 = array('999' => $this->artlink_info_999, '123' => $this->artlink_info_123, '321' => $this->artlink_info_321);
        $list_2 = new $this->changesetvalue_class(111, $field, false, $art_links_2);
        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getText', 'cleared', array('plugin_tracker_artifact','cleared'));
        $this->assertEqual($list_1->diff($list_2), ' cleared');
    }
    
    function testDiff_setto() {
        $field  = new $this->field_class();
        $art_links_1 = array('123' => $this->artlink_info_123, '999' => $this->artlink_info_999);
        $list_1 = new $this->changesetvalue_class(111, $field, false, $art_links_1);
        $list_2 = new $this->changesetvalue_class(111, $field, false, array());
        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getText', 'set to', array('plugin_tracker_artifact','set_to'));
        $this->assertEqual($list_1->diff($list_2), ' set to bug #123, story #999');
    }
    
    function testDiff_changedfrom() {
        $field  = new $this->field_class();
        $art_links_1 = array('123' => $this->artlink_info_123);
        $art_links_2 = array('321' => $this->artlink_info_321);
        $list_1 = new $this->changesetvalue_class(111, $field, false, $art_links_1);
        $list_2 = new $this->changesetvalue_class(111, $field, false, $art_links_2);
        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getText', 'changed from', array('plugin_tracker_artifact','changed_from'));
        $GLOBALS['Language']->setReturnValue('getText', 'to', array('plugin_tracker_artifact','to'));
        $this->assertEqual($list_1->diff($list_2), ' changed from task #321 to bug #123');
        $this->assertEqual($list_2->diff($list_1), ' changed from bug #123 to task #321');
    }
    
    function testDiff_added() {
        $field  = new $this->field_class();
        $art_links_1 = array('123' => $this->artlink_info_123, '321' => $this->artlink_info_321, '999' => $this->artlink_info_999);
        $art_links_2 = array('123' => $this->artlink_info_123, '321' => $this->artlink_info_321);
        $list_1 = new $this->changesetvalue_class(111, $field, false, $art_links_1);
        $list_2 = new $this->changesetvalue_class(111, $field, false, $art_links_2);
        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getText', 'added', array('plugin_tracker_artifact','added'));
        $this->assertEqual($list_1->diff($list_2), 'story #999 added');
    }
    
    function testDiff_removed() {
        $field  = new $this->field_class();
        $art_links_1 = array('123' => $this->artlink_info_123, '321' => $this->artlink_info_321);
        $art_links_2 = array('123' => $this->artlink_info_123, '321' => $this->artlink_info_321, '999' => $this->artlink_info_999);
        $list_1 = new $this->changesetvalue_class(111, $field, false, $art_links_1);
        $list_2 = new $this->changesetvalue_class(111, $field, false, $art_links_2);
        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getText', 'removed', array('plugin_tracker_artifact','removed'));
        $this->assertEqual($list_1->diff($list_2), 'story #999 removed');
    }
    
    function testDiff_added_and_removed() {
        $field  = new $this->field_class();
        $art_links_1 = array('123' => $this->artlink_info_123, '321' => $this->artlink_info_321, '999' => $this->artlink_info_999);
        $art_links_2 = array('999' => $this->artlink_info_999, '123' => $this->artlink_info_123, '666' => $this->artlink_info_666);
        $list_1 = new $this->changesetvalue_class(111, $field, false, $art_links_1);
        $list_2 = new $this->changesetvalue_class(111, $field, false, $art_links_2);
        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getText', 'removed', array('plugin_tracker_artifact','removed'));
        $GLOBALS['Language']->setReturnValue('getText', 'added', array('plugin_tracker_artifact','added'));
        $this->assertPattern('/sr #666 removed/', $list_1->diff($list_2));
        $this->assertPattern('/task #321 added/', $list_1->diff($list_2));
    }
    
    function testSoapValue() {
        $field      = new $this->field_class();
        $value_list = new $this->changesetvalue_class(111, $field, false, array('123' => $this->artlink_info_123, '321' => $this->artlink_info_321, '999' => $this->artlink_info_999));
        $this->assertEqual($value_list->getSoapValue(), "123, 321, 999");
    }
    
}

?>