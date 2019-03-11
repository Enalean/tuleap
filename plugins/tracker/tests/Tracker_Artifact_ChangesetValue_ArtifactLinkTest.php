<?php
/**
 * Copyright Enalean (c) 2012 - Present. All rights reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This list is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('bootstrap.php');

Mock::generate('Tracker_Artifact');

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
    private $artlink_info_123;
    private $artlink_info_copy_of_123;
    private $artlink_info_321;
    private $artlink_info_copy_of_321;
    private $artlink_info_666;
    private $artlink_info_copy_of_666;
    private $artlink_info_999;
    private $artlink_info_copy_of_999;
    private $user;

    public function setUp() {
        parent::setUp();
        $this->field_class          = 'MockTracker_FormElement_Field_ArtifactLink';

        $this->user   = mock('PFUser');
        $user_manager = stub('UserManager')->getCurrentUser()->returns($this->user);
        UserManager::setInstance($user_manager);

        $this->artlink_info_123 = Mockery::mock(
            Tracker_ArtifactLinkInfo::class,
            [
                'getArtifactId' => '123',
                'getKeyword' => 'bug',
                'getUrl' => '<a>bug #123</a>',
                '__toString' => 'bug #123',
                'getLabel' => 'bug #123',
                'userCanView' => true,
            ]
        );

        $this->artlink_info_copy_of_123 = Mockery::mock(
            Tracker_ArtifactLinkInfo::class,
            [
                'getArtifactId' => '123',
                'getKeyword' => 'bug',
                'getUrl' => '<a>bug #123</a>',
                '__toString' => 'bug #123',
                'getLabel' => 'bug #123',
                'userCanView' => true,
            ]
        );

        $this->artlink_info_321 = Mockery::mock(
            Tracker_ArtifactLinkInfo::class,
            [
                'getArtifactId' => '321',
                'getKeyword' => 'task',
                'getUrl' => '<a>task #321</a>',
                '__toString' => 'task #321',
                'getLabel' => 'task #321',
                'userCanView' => true,
            ]
        );

        $this->artlink_info_copy_of_321 = Mockery::mock(
            Tracker_ArtifactLinkInfo::class,
            [
                'getArtifactId' => '321',
                'getKeyword' => 'task',
                'getUrl' => '<a>task #321</a>',
                '__toString' => 'task #321',
                'getLabel' => 'task #321',
                'userCanView' => true,
            ]
        );

        $this->artlink_info_666 = Mockery::mock(
            Tracker_ArtifactLinkInfo::class,
            [
                'getArtifactId' => '666',
                'getKeyword' => 'sr',
                'getUrl' => '<a>sr #666</a>',
                '__toString' => 'sr #666',
                'getLabel' => 'sr #666',
                'userCanView' => true,
            ]
        );

        $this->artlink_info_copy_of_666 = Mockery::mock(
            Tracker_ArtifactLinkInfo::class,
            [
                'getArtifactId' => '666',
                'getKeyword' => 'sr',
                'getUrl' => '<a>sr #666</a>',
                '__toString' => 'sr #666',
                'getLabel' => 'sr #666',
                'userCanView' => true,
            ]
        );

        $this->artlink_info_999 = Mockery::mock(
            Tracker_ArtifactLinkInfo::class,
            [
                'getArtifactId' => '999',
                'getKeyword' => 'story',
                'getUrl' => '<a>story #999</a>',
                '__toString' => 'story #999',
                'getLabel' => 'story #999',
                'userCanView' => true,
            ]
        );

        $this->artlink_info_copy_of_999 = Mockery::mock(
            Tracker_ArtifactLinkInfo::class,
            [
                'getArtifactId' => '999',
                'getKeyword' => 'story',
                'getUrl' => '<a>story #999</a>',
                '__toString' => 'story #999',
                'getLabel' => 'story #999',
                'userCanView' => true,
            ]
        );
    }

    public function tearDown() {
        parent::tearDown();
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
            1 => Mockery::spy(Tracker_ArtifactLinkInfo::class),
            2 => Mockery::spy(Tracker_ArtifactLinkInfo::class)
        );
        $changeset_value = aChangesetValueArtifactLink()->withArtifactLinks($old_values)->build();

        $new_value = array(
            'list_of_artifactlinkinfo' => array(
                2 => Mockery::spy(Tracker_ArtifactLinkInfo::class),
                1 => Mockery::spy(Tracker_ArtifactLinkInfo::class)
            )
        );

        $this->assertFalse($changeset_value->hasChanges($new_value));
    }

    public function itHasChangesWhenLinksAreAdded() {
        $old_values = array(
            1 => Mockery::mock(Tracker_ArtifactLinkInfo::class),
            2 => Mockery::mock(Tracker_ArtifactLinkInfo::class)
        );
        $changeset_value = aChangesetValueArtifactLink()->withArtifactLinks($old_values)->build();

        $new_value = array(
            'list_of_artifactlinkinfo' => array(
                1 => Mockery::mock(Tracker_ArtifactLinkInfo::class),
                2 => Mockery::mock(Tracker_ArtifactLinkInfo::class),
                3 => Mockery::mock(Tracker_ArtifactLinkInfo::class)
            )
        );

        $this->assertTrue($changeset_value->hasChanges($new_value));
    }

    public function itHasChangesWhenLinksAreRemoved() {
        $old_values = array(
            1 => Mockery::mock(Tracker_ArtifactLinkInfo::class),
            2 => Mockery::mock(Tracker_ArtifactLinkInfo::class)
        );
        $changeset_value = aChangesetValueArtifactLink()->withArtifactLinks($old_values)->build();

        $new_value = array(
            'list_of_artifactlinkinfo' => array(
                1 => Mockery::mock(Tracker_ArtifactLinkInfo::class)
            )
        );

        $this->assertTrue($changeset_value->hasChanges($new_value));
    }

    public function itHasChangesWhenNatureIsChanged() {
        $old_values = array(
            1 => Mockery::mock(Tracker_ArtifactLinkInfo::class, ['getNature' => '_is_child']),
            2 => Mockery::mock(Tracker_ArtifactLinkInfo::class, ['getNature' => ''])
        );
        $changeset_value = aChangesetValueArtifactLink()->withArtifactLinks($old_values)->build();

        $new_value = array(
            'list_of_artifactlinkinfo' => array(
            1 => Mockery::mock(Tracker_ArtifactLinkInfo::class, ['getNature' => 'fixed_in']),
            2 => Mockery::mock(Tracker_ArtifactLinkInfo::class, ['getNature' => ''])
            )
        );

        $this->assertTrue($changeset_value->hasChanges($new_value));
    }
}
