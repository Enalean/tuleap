<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_ArtifactLink;

final class AugmentDataFromRequestTest extends TestCase
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

    protected function setUp(): void
    {
        $this->art_link_id = 555;
        $this->tracker     = \Mockery::spy(\Tracker::class);

        $this->field = new Tracker_FormElement_Field_ArtifactLink(
            $this->art_link_id,
            101,
            null,
            'field_artlink',
            'Field ArtLink',
            '',
            1,
            'P',
            true,
            '',
            1
        );
        $this->field->setTracker($this->tracker);
    }

    public function testDoesNothingWhenThereAreNoParentsInRequest(): void
    {
        $new_values  = '32';
        $fields_data = [
            $this->art_link_id => [
                'new_values' => $new_values
            ]
        ];

        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(false);

        $this->field->augmentDataFromRequest($fields_data);

        $this->assertEquals($new_values, $fields_data[$this->art_link_id]['new_values']);
    }

    public function testSetsParentAsNewValues(): void
    {
        $new_values  = '';
        $parent_id   = '657';
        $fields_data = [
            $this->art_link_id => [
                'new_values' => $new_values,
                'parent'     => $parent_id
            ]
        ];

        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(false);

        $this->field->augmentDataFromRequest($fields_data);

        $this->assertEquals($parent_id, $fields_data[$this->art_link_id]['new_values']);
    }

    public function testAppendsParentToNewValues(): void
    {
        $new_values  = '356';
        $parent_id   = '657';
        $fields_data = [
            $this->art_link_id => [
                'new_values' => $new_values,
                'parent'     => $parent_id
            ]
        ];

        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(false);

        $this->field->augmentDataFromRequest($fields_data);

        $this->assertEquals("$new_values,$parent_id", $fields_data[$this->art_link_id]['new_values']);
    }

    public function testDoesntAppendPleaseChooseOption(): void
    {
        $new_values  = '356';
        $parent_id   = '';
        $fields_data = [
            $this->art_link_id => [
                'new_values' => $new_values,
                'parent'     => $parent_id
            ]
        ];

        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(false);

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
                'parent'     => $parent_id
            ]
        ];

        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(false);

        $this->field->augmentDataFromRequest($fields_data);

        $this->assertEquals($new_values, $fields_data[$this->art_link_id]['new_values']);
    }

    public function testAddsLinkWithNature(): void
    {
        $new_values  = '356';
        $nature      = '_is_child';
        $fields_data = [
            $this->art_link_id => [
                'new_values' => $new_values,
                'nature'     => $nature
            ]
        ];

        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(true);

        $this->field->augmentDataFromRequest($fields_data);

        $this->assertEquals(['356' => '_is_child'], $fields_data[$this->art_link_id]['natures']);
    }

    public function testDoesNotAddPropertiesIfNoParentAndNoNewValues(): void
    {
        $fields_data = [];

        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(true);

        $this->field->augmentDataFromRequest($fields_data);

        $this->assertEmpty($fields_data);
    }
}
