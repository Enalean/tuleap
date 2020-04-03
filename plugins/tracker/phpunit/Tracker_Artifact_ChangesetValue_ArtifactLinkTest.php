<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker;

use Mockery;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_ArtifactLink;
use Tracker_ArtifactLinkInfo;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;

class Tracker_Artifact_ChangesetValue_ArtifactLinkTest extends \PHPUnit\Framework\TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalLanguageMock;

    /**
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_ChangesetValue_ArtifactLink
     */
    private function getChangesetValueArtifactLink(array $artifact_links, array $reverse_artifact_links)
    {
        $field = Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getTracker')->andReturn(Mockery::mock(Tracker::class));

        $value = Mockery::mock(
            Tracker_Artifact_ChangesetValue_ArtifactLink::class,
            [
                111,
                Mockery::mock(Tracker_Artifact_Changeset::class),
                $field,
                false,
                $artifact_links,
                $reverse_artifact_links
            ]
        )->makePartial()->shouldAllowMockingProtectedMethods();
        $value->shouldReceive('getNaturePresenterFactory')->andReturn(Mockery::mock(NaturePresenterFactory::class));

        return $value;
    }

    public function testNoDiffForChangesetValueArtifactLinkDiff(): void
    {
        $artlink_info_123 = Mockery::mock(
            Tracker_ArtifactLinkInfo::class,
            [
                'getArtifactId' => '123',
                'getKeyword'    => 'bug',
                'getUrl'        => '<a>bug #123</a>',
                '__toString'    => 'bug #123',
                'getLabel'      => 'bug #123',
                'userCanView'   => true,
            ]
        );

        $artlink_info_321 = Mockery::mock(
            Tracker_ArtifactLinkInfo::class,
            [
                'getArtifactId' => '321',
                'getKeyword'    => 'task',
                'getUrl'        => '<a>task #321</a>',
                '__toString'    => 'task #321',
                'getLabel'      => 'task #321',
                'userCanView'   => true,
            ]
        );

        $artlink_info_999 = Mockery::mock(
            Tracker_ArtifactLinkInfo::class,
            [
                'getArtifactId' => '999',
                'getKeyword'    => 'story',
                'getUrl'        => '<a>story #999</a>',
                '__toString'    => 'story #999',
                'getLabel'      => 'story #999',
                'userCanView'   => true,
            ]
        );

        $art_links_1 = ['123' => $artlink_info_123, '321' => $artlink_info_321, '999' => $artlink_info_999];
        $art_links_2 = ['999' => $artlink_info_999, '123' => $artlink_info_123, '321' => $artlink_info_321];
        $list_1      = $this->getChangesetValueArtifactLink($art_links_1, []);
        $list_2      = $this->getChangesetValueArtifactLink($art_links_2, []);
        $this->assertEquals(null, $list_1->diff($list_2));
        $this->assertEquals(null, $list_2->diff($list_1));
    }

    public function testItHasNoChangesWhenNoNewValues(): void
    {
        $old_values = [];
        /**
         * @var $changeset_value \Mockery\Mock|Tracker_Artifact_ChangesetValue_ArtifactLink
         */
        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $changeset_value->shouldReceive('getValue')->andReturn($old_values);
        $new_value = array('list_of_artifactlinkinfo' => '');
        $this->assertFalse($changeset_value->hasChanges($new_value));
    }

    public function testItHasNoChangesWhenSameValues(): void
    {
        $old_values = array(
            1 => Mockery::spy(Tracker_ArtifactLinkInfo::class),
            2 => Mockery::spy(Tracker_ArtifactLinkInfo::class)
        );
        /**
         * @var $changeset_value \Mockery\Mock|Tracker_Artifact_ChangesetValue_ArtifactLink
         */
        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $changeset_value->shouldReceive('getValue')->andReturn($old_values);

        $new_value = array(
            'list_of_artifactlinkinfo' => array(
                2 => Mockery::spy(Tracker_ArtifactLinkInfo::class),
                1 => Mockery::spy(Tracker_ArtifactLinkInfo::class)
            )
        );

        $this->assertFalse($changeset_value->hasChanges($new_value));
    }

    public function testItHasChangesWhenLinksAreAdded(): void
    {
        $old_values = array(
            1 => Mockery::mock(Tracker_ArtifactLinkInfo::class),
            2 => Mockery::mock(Tracker_ArtifactLinkInfo::class)
        );
        /**
         * @var $changeset_value \Mockery\Mock|Tracker_Artifact_ChangesetValue_ArtifactLink
         */
        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $changeset_value->shouldReceive('getValue')->andReturn($old_values);

        $new_value = array(
            'list_of_artifactlinkinfo' => array(
                1 => Mockery::mock(Tracker_ArtifactLinkInfo::class),
                2 => Mockery::mock(Tracker_ArtifactLinkInfo::class),
                3 => Mockery::mock(Tracker_ArtifactLinkInfo::class)
            )
        );

        $this->assertTrue($changeset_value->hasChanges($new_value));
    }

    public function testItHasChangesWhenLinksAreRemoved(): void
    {
        $old_values = array(
            1 => Mockery::mock(Tracker_ArtifactLinkInfo::class),
            2 => Mockery::mock(Tracker_ArtifactLinkInfo::class)
        );
        /**
         * @var $changeset_value \Mockery\Mock|Tracker_Artifact_ChangesetValue_ArtifactLink
         */
        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $changeset_value->shouldReceive('getValue')->andReturn($old_values);

        $new_value = array(
            'list_of_artifactlinkinfo' => array(
                1 => Mockery::mock(Tracker_ArtifactLinkInfo::class)
            )
        );

        $this->assertTrue($changeset_value->hasChanges($new_value));
    }

    public function testItHasChangesWhenNatureIsChanged(): void
    {
        $old_values = array(
            1 => Mockery::mock(Tracker_ArtifactLinkInfo::class, ['getNature' => '_is_child']),
            2 => Mockery::mock(Tracker_ArtifactLinkInfo::class, ['getNature' => ''])
        );
        /**
         * @var $changeset_value \Mockery\Mock|Tracker_Artifact_ChangesetValue_ArtifactLink
         */
        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $changeset_value->shouldReceive('getValue')->andReturn($old_values);

        $new_value = array(
            'list_of_artifactlinkinfo' => array(
                1 => Mockery::mock(Tracker_ArtifactLinkInfo::class, ['getNature' => 'fixed_in']),
                2 => Mockery::mock(Tracker_ArtifactLinkInfo::class, ['getNature' => ''])
            )
        );

        $this->assertTrue($changeset_value->hasChanges($new_value));
    }
}
