<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\REST\v1\Workflow\PostAction;

use EventManager;
use Jenkins_Client;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Transition;
use Transition_PostAction;
use Transition_PostAction_CIBuild;
use Transition_PostAction_Field_Date;
use Transition_PostAction_Field_Float;
use Transition_PostAction_Field_Int;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Workflow\PostAction\Visitor;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PostActionsRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private EventManager&MockObject $event_manager;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->event_manager = $this->createMock(EventManager::class);
    }

    public function testBuildReturnsRunJobRepresentationBasedOnGivenCiBuildPostAction(): void
    {
        $run_job = $this->buildAPostActionCIBuild(1, 'http://job.example.com');
        $builder = new PostActionsRepresentationBuilder($this->event_manager, [$run_job]);

        $representation = $builder->build();

        self::assertSame(1, $representation[0]->id);
        self::assertSame('run_job', $representation[0]->type);
        self::assertSame('http://job.example.com', $representation[0]->job_url);
    }

    private function buildAPostActionCIBuild($id, $job_url): Transition_PostAction_CIBuild
    {
        $transition = $this->createMock(Transition::class);
        $client     = $this->createMock(Jenkins_Client::class);
        return new Transition_PostAction_CIBuild($transition, $id, $job_url, $client);
    }

    public function testBuildReturnsRunJobRepresentationBasedOnGivenFieldDateAction(): void
    {
        $transition     = $this->createMock(Transition::class);
        $field          = StringFieldBuilder::aStringField(8)->build();
        $set_date_field = new Transition_PostAction_Field_Date($transition, 1, $field, Transition_PostAction_Field_Date::CLEAR_DATE);
        $builder        = new PostActionsRepresentationBuilder($this->event_manager, [$set_date_field]);

        $representation = $builder->build();

        self::assertSame(1, $representation[0]->id);
        self::assertSame('set_field_value', $representation[0]->type);
        self::assertSame(8, $representation[0]->field_id);
        self::assertSame('date', $representation[0]->field_type);
        self::assertSame('', $representation[0]->value);
    }

    public function testBuildReturnsRunJobRepresentationBasedOnGivenFieldIntAction(): void
    {
        $transition    = $this->createMock(Transition::class);
        $field         = StringFieldBuilder::aStringField(8)->build();
        $set_int_field = new Transition_PostAction_Field_Int($transition, 1, $field, 23);
        $builder       = new PostActionsRepresentationBuilder($this->event_manager, [$set_int_field]);

        $representation = $builder->build();

        self::assertSame(1, $representation[0]->id);
        self::assertSame('set_field_value', $representation[0]->type);
        self::assertSame(8, $representation[0]->field_id);
        self::assertSame('int', $representation[0]->field_type);
        self::assertSame(23, $representation[0]->value);
    }

    public function testBuildReturnsRunJobRepresentationBasedOnGivenFieldFloatAction(): void
    {
        $transition      = $this->createMock(Transition::class);
        $field           = StringFieldBuilder::aStringField(8)->build();
        $set_float_field = new Transition_PostAction_Field_Float($transition, 1, $field, 3.4);
        $builder         = new PostActionsRepresentationBuilder($this->event_manager, [$set_float_field]);

        $representation = $builder->build();

        self::assertSame(1, $representation[0]->id);
        self::assertSame('set_field_value', $representation[0]->type);
        self::assertSame(8, $representation[0]->field_id);
        self::assertSame('float', $representation[0]->field_type);
        self::assertSame(3.4, $representation[0]->value);
    }

    public function testBuildReturnsAsManyRepresentationsAsGivenActions(): void
    {
        $post_actions = [
            $this->buildAPostAction(),
            $this->buildAPostAction(),
            $this->buildAPostAction(),
        ];

        $builder = new PostActionsRepresentationBuilder($this->event_manager, $post_actions);

        $this->assertCount(3, $builder->build());
    }

    public function testBuilderAsksToExternalPlugin(): void
    {
        $post_actions = [
            $this->buildAnExternalPostAction(),
        ];

        $this->event_manager->expects($this->once())->method('processEvent');

        $builder = new PostActionsRepresentationBuilder($this->event_manager, $post_actions);

        $builder->build();
    }

    private function buildAPostAction(): Transition_PostAction_Field_Float
    {
        $transition = $this->createMock(Transition::class);
        $field      = StringFieldBuilder::aStringField(8)->build();
        return new Transition_PostAction_Field_Float($transition, 1, $field, 3.4);
    }

    private function buildAnExternalPostAction(): Transition_PostAction
    {
        $transition = $this->createMock(Transition::class);
        return new class ($transition, 1) extends Transition_PostAction {
            #[\Override]
            public function getShortName()
            {
                return '';
            }

            #[\Override]
            public static function getLabel()
            {
                return '';
            }

            #[\Override]
            public function isDefined()
            {
                return true;
            }

            #[\Override]
            public function exportToXml(SimpleXMLElement $root, $xmlMapping)
            {
                return;
            }

            #[\Override]
            public function bypassPermissions(TrackerField $field)
            {
                return false;
            }

            #[\Override]
            public function accept(Visitor $visitor)
            {
                $visitor->visitExternalActions($this);
            }
        };
    }
}
