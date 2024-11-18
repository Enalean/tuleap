<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;

final class RequestDataAugmentorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var int
     */
    private $art_link_id;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker
     */
    private $tracker;
    /**
     * @var Tracker_FormElement_Field_ArtifactLink
     */
    private $field;

    /**
     * @var RequestDataAugmentor
     */
    private $augmentor;

    protected function setUp(): void
    {
        $this->art_link_id = 555;
        $this->tracker     = \Mockery::spy(\Tracker::class);

        $this->field = ArtifactLinkFieldBuilder::anArtifactLinkField($this->art_link_id)->build();
        $this->field->setTracker($this->tracker);

        $this->augmentor = new RequestDataAugmentor();
    }

    public function testDoesNothingWhenThereAreNoParentsInRequest(): void
    {
        $new_values  = '32';
        $fields_data = [
            $this->art_link_id => [
                'new_values' => $new_values,
            ],
        ];

        $this->tracker->shouldReceive('isProjectAllowedToUseType')->andReturn(true);

        $this->augmentor->augmentDataFromRequest($this->field, $fields_data);

        $this->assertEquals($new_values, $fields_data[$this->art_link_id]['new_values']);
    }

    public function testDoesntAppendPleaseChooseOption(): void
    {
        $new_values  = '356';
        $parent_id   = '';
        $fields_data = [
            $this->art_link_id => [
                'new_values' => $new_values,
                'parent'     => $parent_id,
            ],
        ];

        $this->tracker->shouldReceive('isProjectAllowedToUseType')->andReturn(true);

        $this->field->augmentDataFromRequest($fields_data);

        $this->assertEquals($new_values, $fields_data[$this->art_link_id]['new_values']);
    }

    public function testDoesntAppendCreateNewOption(): void
    {
        $new_values  = '356';
        $parent_id   = '-1';
        $fields_data = [
            $this->art_link_id => [
                'new_values' => $new_values,
                'parent'     => $parent_id,
            ],
        ];

        $this->tracker->shouldReceive('isProjectAllowedToUseType')->andReturn(true);

        $this->augmentor->augmentDataFromRequest($this->field, $fields_data);

        $this->assertEquals($new_values, $fields_data[$this->art_link_id]['new_values']);
    }

    public function testAddsLinkWithType(): void
    {
        $new_values  = '356';
        $type        = '_is_child';
        $fields_data = [
            $this->art_link_id => [
                'new_values' => $new_values,
                'type'       => $type,
            ],
        ];

        $this->tracker->shouldReceive('isProjectAllowedToUseType')->andReturn(true);

        $this->augmentor->augmentDataFromRequest($this->field, $fields_data);

        $this->assertEquals(['356' => '_is_child'], $fields_data[$this->art_link_id]['types']);
    }

    public function testDoesNotAddPropertiesIfNoParentAndNoNewValues(): void
    {
        $fields_data = [];

        $this->tracker->shouldReceive('isProjectAllowedToUseType')->andReturn(true);

        $this->augmentor->augmentDataFromRequest($this->field, $fields_data);

        $this->assertEmpty($fields_data);
    }

    public function testWhenUserWantsSomeArtifactsToBeParentsThenTheyArePutInTheSpecialKeyParentSoThatTheLinkIsDoneTheRightWay(): void
    {
        $fields_data = [
            $this->art_link_id => [
                'new_values' => '356,357',
                'type'       => '_is_parent',
            ],
        ];

        $this->tracker->shouldReceive('isProjectAllowedToUseType')->andReturn(true);

        $this->augmentor->augmentDataFromRequest($this->field, $fields_data);

        $this->assertEquals(
            [
                'new_values' => '',
                'type'       => '',
                'parent'     => [356, 357],
            ],
            $fields_data[$this->art_link_id]
        );
    }

    public function testWhenUserWantsExistingLinksToBeParentsThenTheyArePutInTheSpecialKeyParentSoThatTheLinkIsDoneTheRightWay(): void
    {
        $fields_data = [
            $this->art_link_id => [
                'new_values' => '',
                'type' => '',
                'types' => [
                    '123' => 'depends_on',
                    '234' => '_is_child',
                    '345' => '_is_parent',
                    '456' => '_is_parent',
                ],
            ],
        ];

        $this->tracker->shouldReceive('isProjectAllowedToUseType')->andReturn(true);

        $this->augmentor->augmentDataFromRequest($this->field, $fields_data);

        $this->assertEquals(
            [
                'new_values' => '',
                'type'     => '',
                'types'    => [
                    '123' => 'depends_on',
                    '234' => '_is_child',
                ],
                'parent'     => [345, 456],
                'removed_values' => [
                    '345' => [345],
                    '456' => [456],
                ],
            ],
            $fields_data[$this->art_link_id]
        );
    }

    public function testUserCanBothSetNewLinksAndExistingLinksAsParent(): void
    {
        $fields_data = [
            $this->art_link_id => [
                'new_values' => '356,357',
                'type' => '_is_parent',
                'types' => [
                    '123' => 'depends_on',
                    '234' => '_is_child',
                    '345' => '_is_parent',
                    '456' => '_is_parent',
                ],
            ],
        ];

        $this->tracker->shouldReceive('isProjectAllowedToUseType')->andReturn(true);

        $this->augmentor->augmentDataFromRequest($this->field, $fields_data);

        $this->assertEquals(
            [
                'new_values' => '',
                'type'       => '',
                'types'      => [
                    '123' => 'depends_on',
                    '234' => '_is_child',
                ],
                'parent'     => [356, 357, 345, 456],
                'removed_values' => [
                    '345' => [345],
                    '456' => [456],
                ],
            ],
            $fields_data[$this->art_link_id]
        );
    }

    public function testRemovedExistingLinksCannotBeBothRemovedAndChangedToParent(): void
    {
        $fields_data = [
            $this->art_link_id => [
                'new_values' => '',
                'type' => '',
                'types' => [
                    '456' => '_is_parent',
                ],
                'removed_values' => [
                    '456' => ['456'],
                ],
            ],
        ];

        $this->tracker->shouldReceive('isProjectAllowedToUseType')->andReturn(true);

        $this->augmentor->augmentDataFromRequest($this->field, $fields_data);

        $this->assertEquals(
            [
                'new_values' => '',
                'type' => '',
                'types' => [],
                'removed_values' => [
                    '456' => ['456'],
                ],
            ],
            $fields_data[$this->art_link_id]
        );
    }
}
