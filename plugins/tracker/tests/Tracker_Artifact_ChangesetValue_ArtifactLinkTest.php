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

require_once('bootstrap.php');

Mock::generate('Tracker_Artifact');

Mock::generate('Tracker_ArtifactLinkInfo');

Mock::generatePartial(
    'Tracker_Artifact_ChangesetValue_ArtifactLink',
    'Tracker_Artifact_ChangesetValue_ArtifactLinkTestVersion',
    array(
        'getArtifactIds'
    )
);

Mock::generate('Tracker_FormElement_Field_ArtifactLink');

class Tracker_Artifact_ChangesetValue_ArtifactLinkTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->field_class          = 'MockTracker_FormElement_Field_ArtifactLink';
        $this->changesetvalue_class = 'Tracker_Artifact_ChangesetValue_ArtifactLink';

        $this->user   = mock('PFUser');
        $user_manager = stub('UserManager')->getCurrentUser()->returns($this->user);
        UserManager::setInstance($user_manager);

        $this->artlink_info_123 = new MockTracker_ArtifactLinkInfo();
        $this->artlink_info_123->setReturnValue('getArtifactId', '123');
        $this->artlink_info_123->setReturnValue('getKeyword', 'bug');
        $this->artlink_info_123->setReturnValue('getUrl', '<a>bug #123</a>'); // for test
        $this->artlink_info_123->setReturnValue('__toString', 'bug #123'); // for test
        $this->artlink_info_123->setReturnValue('getLabel', 'bug #123');
        $this->artlink_info_123->setReturnValue('userCanView', true, array($this->user));

        $this->artlink_info_copy_of_123 = new MockTracker_ArtifactLinkInfo();
        $this->artlink_info_copy_of_123->setReturnValue('getArtifactId', '123');
        $this->artlink_info_copy_of_123->setReturnValue('getKeyword', 'bug');
        $this->artlink_info_copy_of_123->setReturnValue('getUrl', '<a>bug #123</a>'); // for test
        $this->artlink_info_copy_of_123->setReturnValue('__toString', 'bug #123'); // for test
        $this->artlink_info_copy_of_123->setReturnValue('getLabel', 'bug #123');
        $this->artlink_info_copy_of_123->setReturnValue('userCanView', true, array($this->user));

        $this->artlink_info_321 = new MockTracker_ArtifactLinkInfo();
        $this->artlink_info_321->setReturnValue('getArtifactId', '321');
        $this->artlink_info_321->setReturnValue('getKeyword', 'task');
        $this->artlink_info_321->setReturnValue('getUrl', '<a>task #321</a>'); // for test
        $this->artlink_info_321->setReturnValue('__toString', 'task #321'); // for test
        $this->artlink_info_321->setReturnValue('getLabel', 'task #321');
        $this->artlink_info_321->setReturnValue('userCanView', true, array($this->user));

        $this->artlink_info_copy_of_321 = new MockTracker_ArtifactLinkInfo();
        $this->artlink_info_copy_of_321->setReturnValue('getArtifactId', '321');
        $this->artlink_info_copy_of_321->setReturnValue('getKeyword', 'task');
        $this->artlink_info_copy_of_321->setReturnValue('getUrl', '<a>task #321</a>'); // for test
        $this->artlink_info_copy_of_321->setReturnValue('__toString', 'task #321'); // for test
        $this->artlink_info_copy_of_321->setReturnValue('getLabel', 'task #321');
        $this->artlink_info_copy_of_321->setReturnValue('userCanView', true, array($this->user));

        $this->artlink_info_666 = new MockTracker_ArtifactLinkInfo();
        $this->artlink_info_666->setReturnValue('getArtifactId', '666');
        $this->artlink_info_666->setReturnValue('getKeyword', 'sr');
        $this->artlink_info_666->setReturnValue('getUrl', '<a>sr #666</a>'); // for test
        $this->artlink_info_666->setReturnValue('__toString', 'sr #666'); // for test
        $this->artlink_info_666->setReturnValue('getLabel', 'sr #666');
        $this->artlink_info_666->setReturnValue('userCanView', true, array($this->user));

        $this->artlink_info_copy_of_666 = new MockTracker_ArtifactLinkInfo();
        $this->artlink_info_copy_of_666->setReturnValue('getArtifactId', '666');
        $this->artlink_info_copy_of_666->setReturnValue('getKeyword', 'sr');
        $this->artlink_info_copy_of_666->setReturnValue('getUrl', '<a>sr #666</a>'); // for test
        $this->artlink_info_copy_of_666->setReturnValue('__toString', 'sr #666'); // for test
        $this->artlink_info_copy_of_666->setReturnValue('getLabel', 'sr #666');
        $this->artlink_info_copy_of_666->setReturnValue('userCanView', true, array($this->user));

        $this->artlink_info_999 = new MockTracker_ArtifactLinkInfo();
        $this->artlink_info_999->setReturnValue('getArtifactId', '999');
        $this->artlink_info_999->setReturnValue('getKeyword', 'story');
        $this->artlink_info_999->setReturnValue('getUrl', '<a>story #999</a>'); // for test
        $this->artlink_info_999->setReturnValue('__toString', 'story #999'); // for test
        $this->artlink_info_999->setReturnValue('getLabel', 'story #999');
        $this->artlink_info_999->setReturnValue('userCanView', true, array($this->user));

        $this->artlink_info_copy_of_999 = new MockTracker_ArtifactLinkInfo();
        $this->artlink_info_copy_of_999->setReturnValue('getArtifactId', '999');
        $this->artlink_info_copy_of_999->setReturnValue('getKeyword', 'story');
        $this->artlink_info_copy_of_999->setReturnValue('getUrl', '<a>story #999</a>'); // for test
        $this->artlink_info_copy_of_999->setReturnValue('__toString', 'story #999'); // for test
        $this->artlink_info_copy_of_999->setReturnValue('getLabel', 'story #999');
        $this->artlink_info_copy_of_999->setReturnValue('userCanView', true, array($this->user));
    }

    public function tearDown() {
        parent::tearDown();
        unset($this->artlink_info_123);
        unset($this->artlink_info_321);
        unset($this->artlink_info_666);
        unset($this->artlink_info_999);
        UserManager::clearInstance();
    }

    public function testNoDiff() {
        $field  = new $this->field_class();
        $art_links_1 = array('123' => $this->artlink_info_123, '321' => $this->artlink_info_321, '999' => $this->artlink_info_999);
        $art_links_2 = array('999' => $this->artlink_info_999, '123' => $this->artlink_info_123, '321' => $this->artlink_info_321);
        $list_1 = new $this->changesetvalue_class(111, $field, false, $art_links_1, array());
        $list_2 = new $this->changesetvalue_class(111, $field, false, $art_links_2, array());
        $this->assertFalse($list_1->diff($list_2));
        $this->assertFalse($list_2->diff($list_1));
    }

    public function testDiff_cleared() {
        $field  = new $this->field_class();
        $list_1 = new $this->changesetvalue_class(111, $field, false, array(), array());
        $art_links_2 = array('999' => $this->artlink_info_999, '123' => $this->artlink_info_123, '321' => $this->artlink_info_321);
        $list_2 = new $this->changesetvalue_class(111, $field, false, $art_links_2, array());
        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getText', 'cleared', array('plugin_tracker_artifact','cleared'));
        $this->assertEqual($list_1->diff($list_2), ' cleared');
    }

    public function testDiff_setto() {
        $field  = new $this->field_class();
        $art_links_1 = array('123' => $this->artlink_info_123, '999' => $this->artlink_info_999);
        $list_1 = new $this->changesetvalue_class(111, $field, false, $art_links_1, array());
        $list_2 = new $this->changesetvalue_class(111, $field, false, array(), array());
        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getText', 'set to', array('plugin_tracker_artifact','set_to'));
        $this->assertEqual($list_1->diff($list_2), ' set to <a>bug #123</a>, <a>story #999</a>');
    }

    public function testDiff_changedfrom() {
        $field  = new $this->field_class();
        $art_links_1 = array('123' => $this->artlink_info_123);
        $art_links_2 = array('321' => $this->artlink_info_321);
        $list_1 = new $this->changesetvalue_class(111, $field, false, $art_links_1, array());
        $list_2 = new $this->changesetvalue_class(111, $field, false, $art_links_2, array());
        $GLOBALS['Language']->setReturnValue('getText', 'changed from', array('plugin_tracker_artifact','changed_from'));
        $GLOBALS['Language']->setReturnValue('getText', 'to', array('plugin_tracker_artifact','to'));
        $this->assertEqual($list_1->diff($list_2), ' changed from <a>task #321</a> to <a>bug #123</a>');
        $this->assertEqual($list_2->diff($list_1), ' changed from <a>bug #123</a> to <a>task #321</a>');
    }

    public function testDiff_changedfromInPlainText() {
        $field  = new $this->field_class();
        $art_links_1 = array('123' => $this->artlink_info_123);
        $art_links_2 = array('321' => $this->artlink_info_321);
        $list_1 = new $this->changesetvalue_class(111, $field, false, $art_links_1, array());
        $list_2 = new $this->changesetvalue_class(111, $field, false, $art_links_2, array());
        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getText', 'changed from', array('plugin_tracker_artifact','changed_from'));
        $GLOBALS['Language']->setReturnValue('getText', 'to', array('plugin_tracker_artifact','to'));
        $this->assertEqual($list_1->diff($list_2, 'text'), ' changed from task #321 to bug #123');
        $this->assertEqual($list_2->diff($list_1, 'text'), ' changed from bug #123 to task #321');
    }

    public function testDiff_added() {
        $field  = new $this->field_class();
        $art_links_1 = array('123' => $this->artlink_info_123, '321' => $this->artlink_info_321, '999' => $this->artlink_info_999);
        $art_links_2 = array('123' => $this->artlink_info_123, '321' => $this->artlink_info_321);
        $list_1 = new $this->changesetvalue_class(111, $field, false, $art_links_1, array());
        $list_2 = new $this->changesetvalue_class(111, $field, false, $art_links_2, array());
        $GLOBALS['Language']->setReturnValue('getText', 'added', array('plugin_tracker_artifact','added'));
        $this->assertEqual($list_1->diff($list_2), '<a>story #999</a> added');
    }

    public function testDiff_removed() {
        $field  = new $this->field_class();
        $art_links_1 = array('123' => $this->artlink_info_123, '321' => $this->artlink_info_321);
        $art_links_2 = array('123' => $this->artlink_info_123, '321' => $this->artlink_info_321, '999' => $this->artlink_info_999);
        $list_1 = new $this->changesetvalue_class(111, $field, false, $art_links_1, array());
        $list_2 = new $this->changesetvalue_class(111, $field, false, $art_links_2, array());
        $GLOBALS['Language']->setReturnValue('getText', 'removed', array('plugin_tracker_artifact','removed'));
        $this->assertEqual($list_1->diff($list_2), '<a>story #999</a> removed');
    }

    public function testDiff_added_and_removed() {
        $field  = new $this->field_class();
        $art_links_1 = array('123' => $this->artlink_info_123, '321' => $this->artlink_info_321, '999' => $this->artlink_info_999);
        $art_links_2 = array('999' => $this->artlink_info_999, '123' => $this->artlink_info_123, '666' => $this->artlink_info_666);
        $list_1 = new $this->changesetvalue_class(111, $field, false, $art_links_1, array());
        $list_2 = new $this->changesetvalue_class(111, $field, false, $art_links_2, array());
        $GLOBALS['Language']->setReturnValue('getText', 'removed', array('plugin_tracker_artifact','removed'));
        $GLOBALS['Language']->setReturnValue('getText', 'added', array('plugin_tracker_artifact','added'));
        $this->assertPattern('%<a>sr #666</a> removed%', $list_1->diff($list_2));
        $this->assertPattern('%<a>task #321</a> added%', $list_1->diff($list_2));
    }

    public function testDiff_added_and_removed_no_duplicates() {
        $field  = new $this->field_class();
        $art_links_1 = array('123' => $this->artlink_info_123, '321' => $this->artlink_info_321, '999' => $this->artlink_info_999);
        $art_links_2 = array('999' => $this->artlink_info_copy_of_999, '123' => $this->artlink_info_copy_of_123, '666' => $this->artlink_info_copy_of_666);
        $list_1 = new $this->changesetvalue_class(111, $field, false, $art_links_1, array());
        $list_2 = new $this->changesetvalue_class(111, $field, false, $art_links_2, array());
        $GLOBALS['Language']->setReturnValue('getText', 'removed', array('plugin_tracker_artifact','removed'));
        $GLOBALS['Language']->setReturnValue('getText', 'added', array('plugin_tracker_artifact','added'));
        $this->assertNoPattern('%<a>bug #123</a>%', $list_1->diff($list_2));
        $this->assertNoPattern('%<a>story #999</a> removed%', $list_1->diff($list_2));
        $this->assertPattern('%<a>sr #666</a> removed%', $list_1->diff($list_2));
        $this->assertPattern('%<a>task #321</a> added%', $list_1->diff($list_2));
    }

    public function testSoapValue() {
        $field      = new $this->field_class();
        $value_list = new $this->changesetvalue_class(111, $field, false, array('123' => $this->artlink_info_123, '321' => $this->artlink_info_321, '999' => $this->artlink_info_999), array());
        $this->assertEqual($value_list->getSoapValue($this->user), array('value' => "123, 321, 999"));
    }
}

class Tracker_Artifact_ChangesetValue_ArtifactLink_HasChangesTest extends TuleapTestCase {

    public function itHasNoChangesWhenNoNewValues() {
        $old_values      = array();
        $changeset_value = aChangesetValueArtifactLink()->withArtifactLinks($old_values)->build();
        $new_value       = array('list_of_artifactlinkinfo' => '');
        $this->assertFalse($changeset_value->hasChanges($new_value));
    }

    public function itHasNoChangesWhenSameValues() {
        $old_values = array(
            1 => mock('Tracker_ArtifactLinkInfo'),
            2 => mock('Tracker_ArtifactLinkInfo')
        );
        $changeset_value = aChangesetValueArtifactLink()->withArtifactLinks($old_values)->build();

        $new_value = array(
            'list_of_artifactlinkinfo' => array(
                2 => mock('Tracker_ArtifactLinkInfo'),
                1 => mock('Tracker_ArtifactLinkInfo')
            )
        );

        $this->assertFalse($changeset_value->hasChanges($new_value));
    }

    public function itHasChangesWhenLinksAreAdded() {
        $old_values = array(
            1 => mock('Tracker_ArtifactLinkInfo'),
            2 => mock('Tracker_ArtifactLinkInfo')
        );
        $changeset_value = aChangesetValueArtifactLink()->withArtifactLinks($old_values)->build();

        $new_value = array(
            'list_of_artifactlinkinfo' => array(
                1 => mock('Tracker_ArtifactLinkInfo'),
                2 => mock('Tracker_ArtifactLinkInfo'),
                3 => mock('Tracker_ArtifactLinkInfo')
            )
        );

        $this->assertTrue($changeset_value->hasChanges($new_value));
    }

    public function itHasChangesWhenLinksAreRemoved() {
        $old_values = array(
            1 => mock('Tracker_ArtifactLinkInfo'),
            2 => mock('Tracker_ArtifactLinkInfo')
        );
        $changeset_value = aChangesetValueArtifactLink()->withArtifactLinks($old_values)->build();

        $new_value = array(
            'list_of_artifactlinkinfo' => array(
                1 => mock('Tracker_ArtifactLinkInfo')
            )
        );

        $this->assertTrue($changeset_value->hasChanges($new_value));
    }

    public function itHasChangesWhenNatureIsChanged() {
        $old_values = array(
            1 => stub('Tracker_ArtifactLinkInfo')->getNature()->returns('_is_child'),
            2 => stub('Tracker_ArtifactLinkInfo')->getNature()->returns('')
        );
        $changeset_value = aChangesetValueArtifactLink()->withArtifactLinks($old_values)->build();

        $new_value = array(
            'list_of_artifactlinkinfo' => array(
            1 => stub('Tracker_ArtifactLinkInfo')->getNature()->returns('fixed_in'),
            2 => stub('Tracker_ArtifactLinkInfo')->getNature()->returns('')
            )
        );

        $this->assertTrue($changeset_value->hasChanges($new_value));
    }
}
