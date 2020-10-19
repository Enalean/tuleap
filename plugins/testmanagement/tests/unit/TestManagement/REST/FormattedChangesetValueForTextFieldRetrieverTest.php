<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_Text;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Artifact\Artifact;

class FormattedChangesetValueForTextFieldRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var FormattedChangesetValueForTextFieldRetriever
     */
    private $formatted_changeset_value_for_text_field_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Artifact
     */
    private $artifact;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $tracker_formelement_factory;

    protected function setUp(): void
    {
        $this->artifact = Mockery::mock(Artifact::class);
        $this->artifact->shouldReceive('getTrackerId')->andReturn(42);

        $this->user                                               = Mockery::mock(PFUser::class);
        $this->tracker_formelement_factory                        = Mockery::mock(Tracker_FormElementFactory::class);
        $this->formatted_changeset_value_for_text_field_retriever = new FormattedChangesetValueForTextFieldRetriever(
            $this->tracker_formelement_factory
        );
    }

    public function testGetFormattedChangesetValueForFieldText(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_Text::class);
        $field->shouldReceive('getId')->andReturn(112);

        $this->tracker_formelement_factory->shouldReceive('getUsedFieldByNameForUser')->andReturn($field);

        $result          = $this->formatted_changeset_value_for_text_field_retriever
            ->getFormattedChangesetValueForFieldText('result', 'Result', $this->artifact, $this->user);
        $expected_result = ['content' => 'Result', 'format' => 'html'];

        $this->assertEquals($expected_result, $result->value);
        $this->assertEquals(112, $result->field_id);
    }

    public function testGetFormattedChangesetValueForFieldTextReturnsNullIfFieldDoesntExist(): void
    {
        $this->tracker_formelement_factory->shouldReceive('getUsedFieldByNameForUser')->andReturn(null);

        $result = $this->formatted_changeset_value_for_text_field_retriever
            ->getFormattedChangesetValueForFieldText('result', 'Result', $this->artifact, $this->user);

        $this->assertNull($result);
    }
}
