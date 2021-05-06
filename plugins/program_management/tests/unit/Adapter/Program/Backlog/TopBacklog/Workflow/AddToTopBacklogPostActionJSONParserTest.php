<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\Workflow;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanStore;
use Tuleap\REST\I18NRestException;
use Workflow;

final class AddToTopBacklogPostActionJSONParserTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PlanStore
     */
    private $plan_store;
    /**
     * @var AddToTopBacklogPostActionJSONParser
     */
    private $parser;

    protected function setUp(): void
    {
        $this->plan_store = \Mockery::mock(PlanStore::class);
        $this->parser     = new AddToTopBacklogPostActionJSONParser($this->plan_store);
    }

    public function testAcceptsWhenItLooksLikeTheAppropriateAction(): void
    {
        self::assertTrue($this->parser->accept(['id' => 12, 'type' => 'program_management_add_to_top_backlog']));
    }

    /**
     * @dataProvider dataProviderInvalidJSONPayload
     */
    public function testDoesNotAcceptUnknownAction(array $json): void
    {
        self::assertFalse($this->parser->accept($json));
    }

    public function dataProviderInvalidJSONPayload(): array
    {
        return [
            'Incorrectly formatted' => [[]],
            'Other action' => [['id' => 14, 'type' => 'something_else']],
        ];
    }

    public function testGetPostActionValue(): void
    {
        $workflow = \Mockery::mock(Workflow::class);
        $workflow->shouldReceive('getTrackerId')->andReturn('140');
        $this->plan_store->shouldReceive('isPlannable')->andReturn(true);

        $post_action = $this->parser->parse($workflow, []);
        self::assertInstanceOf(AddToTopBacklogPostActionValue::class, $post_action);
    }


    public function testThrowsAnExceptionWhenTheWorkflowIsNotPartOfAPlannableTracker(): void
    {
        $workflow = \Mockery::mock(Workflow::class);
        $workflow->shouldReceive('getTrackerId')->andReturn('403');
        $this->plan_store->shouldReceive('isPlannable')->andReturn(false);

        $this->expectException(I18NRestException::class);

        $this->parser->parse($workflow, []);
    }
}
