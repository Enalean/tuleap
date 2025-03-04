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

use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyIsPlannable;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsPlannableStub;
use Tuleap\REST\I18NRestException;
use Workflow;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AddToTopBacklogPostActionJSONParserTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private AddToTopBacklogPostActionJSONParser $parser;
    private VerifyIsPlannable $verify_is_plannable;

    protected function setUp(): void
    {
        $this->verify_is_plannable = VerifyIsPlannableStub::buildPlannableElement();
        $this->parser              = new AddToTopBacklogPostActionJSONParser($this->verify_is_plannable);
    }

    public function testAcceptsWhenItLooksLikeTheAppropriateAction(): void
    {
        self::assertTrue($this->parser->accept(['id' => 12, 'type' => 'program_management_add_to_top_backlog']));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderInvalidJSONPayload')]
    public function testDoesNotAcceptUnknownAction(array $json): void
    {
        self::assertFalse($this->parser->accept($json));
    }

    public static function dataProviderInvalidJSONPayload(): array
    {
        return [
            'Incorrectly formatted' => [[]],
            'Other action' => [['id' => 14, 'type' => 'something_else']],
        ];
    }

    public function testGetPostActionValue(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('getTrackerId')->willReturn(140);

        $post_action = $this->parser->parse($workflow, []);
        self::assertInstanceOf(AddToTopBacklogPostActionValue::class, $post_action);
    }

    public function testThrowsAnExceptionWhenTheWorkflowIsNotPartOfAPlannableTracker(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('getTrackerId')->willReturn(403);
        $this->verify_is_plannable = VerifyIsPlannableStub::buildNotPlannableElement();
        $parser                    = new AddToTopBacklogPostActionJSONParser($this->verify_is_plannable);

        $this->expectException(I18NRestException::class);

        $parser->parse($workflow, []);
    }
}
