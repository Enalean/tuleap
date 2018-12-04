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

    private $field_class;

    public function setUp() {
        parent::setUp();
        $this->field_class          = 'MockTracker_FormElement_Field_ArtifactLink';

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

    private function getChangesetValueArtifactLink(array $artifact_links, array $reverse_artifact_links) {
        $field  = new $this->field_class();
        stub($field)->getTracker()->returns(mock('Tracker'));

        $value = partial_mock(
            'Tracker_Artifact_ChangesetValue_ArtifactLink',
            array('getNaturePresenterFactory'),
            array(111, mock('Tracker_Artifact_Changeset'), $field, false, $artifact_links, $reverse_artifact_links)
        );
        stub($value)->getNaturePresenterFactory()->returns(
            mock('Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory')
        );

        return $value;
    }

    public function testNoDiff() {
        $art_links_1 = array('123' => $this->artlink_info_123, '321' => $this->artlink_info_321, '999' => $this->artlink_info_999);
        $art_links_2 = array('999' => $this->artlink_info_999, '123' => $this->artlink_info_123, '321' => $this->artlink_info_321);
        $list_1 = $this->getChangesetValueArtifactLink($art_links_1, array());
        $list_2 = $this->getChangesetValueArtifactLink($art_links_2, array());
        $this->assertFalse($list_1->diff($list_2));
        $this->assertFalse($list_2->diff($list_1));
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
