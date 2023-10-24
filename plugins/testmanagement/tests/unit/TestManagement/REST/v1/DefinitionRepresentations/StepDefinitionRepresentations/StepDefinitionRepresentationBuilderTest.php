<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 * along with Tuleap. If not, <see http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Tuleap\TestManagement\REST\v1\DefinitionRepresentations\StepDefinitionRepresentations;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\TestManagement\Step\Step;
use Tuleap\Tracker\Artifact\Artifact;

class StepDefinitionRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItReturnsTheTextRepresentationOfTheStep(): void
    {
        $description      = 'Turn the wheel';
        $expected_results = 'The car should also turn to avoid the cliff';
        $step             = new Step(1, $description, 'text', $expected_results, 'text', 1);

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturn(101);
        $artifact = Mockery::spy(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);

        $expected_representation = new StepDefinitionRepresentation(
            1,
            $description,
            'text',
            null,
            $expected_results,
            'text',
            null,
            1
        );

        $purifier = \Mockery::mock(\Codendi_HTMLPurifier::class);
        $purifier->shouldReceive('purifyHTMLWithReferences')->with($description, 101)->andReturn($description)->once();
        $purifier->shouldReceive('purifyHTMLWithReferences')
            ->with($expected_results, 101)
            ->andReturn($expected_results)
            ->once();
        $representation = StepDefinitionRepresentationBuilder::build(
            $step,
            $artifact,
            $purifier,
            Mockery::spy(ContentInterpretor::class)
        );
        $this->assertEquals($expected_representation, $representation);
    }

    public function testItReturnsTheHTMLRepresentationOfTheStep(): void
    {
        $description      = '<p>Turn the <strong>wheel</strong></p>';
        $expected_results = '<p>The car should <strong>also</strong> turn to <strong>avoid</strong> the <i>cliff</i></p>';
        $step             = new Step(1, $description, 'html', $expected_results, 'html', 1);

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturn(101);
        $artifact = Mockery::spy(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);

        $expected_representation = new StepDefinitionRepresentation(
            1,
            $description,
            'html',
            null,
            $expected_results,
            'html',
            null,
            1
        );

        $purifier = \Mockery::mock(\Codendi_HTMLPurifier::class);
        $purifier->shouldReceive('purifyHTMLWithReferences')->with($description, 101)->andReturn($description)->once();
        $purifier->shouldReceive('purifyHTMLWithReferences')
            ->with($expected_results, 101)
            ->andReturn($expected_results)
            ->once();
        $representation = StepDefinitionRepresentationBuilder::build(
            $step,
            $artifact,
            $purifier,
            Mockery::spy(ContentInterpretor::class)
        );
        $this->assertEquals($expected_representation, $representation);
    }

    public function testItReturnsTheCommonmarkRepresentationOfTheStep(): void
    {
        $commonmark_interpreter = Mockery::spy(ContentInterpretor::class);

        $description          = 'Turn the **wheel**';
        $expected_description = '<p>The car should <strong>also</strong> turn to <strong>avoid</strong> the <i>cliff</i></p>';
        $commonmark_interpreter->shouldReceive('getInterpretedContentWithReferences')->with(
            $description,
            101
        )->andReturn($expected_description)->once();

        $expected_results               = 'The car should **also** turn to **avoid** the _cliff_';
        $expected_html_expected_results = '<p>The car should <strong>also</strong> turn to <strong>avoid</strong> the <i>cliff</i></p>';
        $commonmark_interpreter->shouldReceive('getInterpretedContentWithReferences')->with(
            $expected_results,
            101
        )->andReturn($expected_html_expected_results)->once();

        $step = new Step(1, $description, 'commonmark', $expected_results, 'commonmark', 1);

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturn(101);
        $artifact = Mockery::spy(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);

        $expected_representation = new StepDefinitionRepresentation(
            1,
            $expected_description,
            'html',
            'Turn the **wheel**',
            $expected_html_expected_results,
            'html',
            'The car should **also** turn to **avoid** the _cliff_',
            1
        );
        $purifier                = \Mockery::mock(\Codendi_HTMLPurifier::class);
        $purifier->shouldReceive('purify')->with($description)->andReturn($description)->once();
        $purifier->shouldReceive('purify')->with($expected_results)->andReturn($expected_results)->once();
        $representation = StepDefinitionRepresentationBuilder::build(
            $step,
            $artifact,
            $purifier,
            $commonmark_interpreter
        );
        $this->assertEquals($expected_representation, $representation);
    }

    public function testItThrowsExceptionWhenTheStepDefinitionFormatIsNotFound(): void
    {
        $description      = 'Turn the **wheel**';
        $expected_results = 'The car should **also** turn to **avoid** the _cliff_';
        $step             = new Step(1, $description, 'vroom', $expected_results, 'vroom', 1);

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturn(101);
        $artifact = Mockery::spy(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);

        $this->expectException(StepDefinitionFormatNotFoundException::class);
        $purifier = \Mockery::mock(\Codendi_HTMLPurifier::class);
        StepDefinitionRepresentationBuilder::build(
            $step,
            $artifact,
            $purifier,
            Mockery::spy(ContentInterpretor::class)
        );
    }
}
