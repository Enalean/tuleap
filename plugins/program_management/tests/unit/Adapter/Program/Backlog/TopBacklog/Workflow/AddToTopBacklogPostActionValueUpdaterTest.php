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

use Tuleap\ProgramManagement\Tests\Stub\CreatePostActionStub;
use Tuleap\ProgramManagement\Tests\Stub\DeletePostActionStub;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionVisitor;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;
use Tuleap\Tracker\Workflow\Update\PostAction;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AddToTopBacklogPostActionValueUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var AddToTopBacklogPostActionValueUpdater
     */
    private $updater;

    private DeletePostActionStub $delete_post_action;
    private CreatePostActionStub $create_post_action;

    #[\Override]
    protected function setUp(): void
    {
        $this->delete_post_action = DeletePostActionStub::withCount();
        $this->create_post_action = CreatePostActionStub::withCount();
        $this->updater            = new AddToTopBacklogPostActionValueUpdater(
            $this->delete_post_action,
            new DBTransactionExecutorPassthrough(),
            $this->create_post_action
        );
    }

    public function testUpdatesPostAction(): void
    {
        $actions = new PostActionCollection(new AddToTopBacklogPostActionValue(), self::buildOtherPostAction());

        $this->updater->updateByTransition($actions, new \Transition(14, 321, null, ListStaticValueBuilder::aStaticValue('field')->build()));
        self::assertEquals(1, $this->delete_post_action->getCallCount());
        self::assertEquals(1, $this->create_post_action->getCallCount());
    }

    public function testOnlyCreatesThePostActionForTheTransitionIfTheAppropriatePostActionValueIsPresent(): void
    {
        $actions = new PostActionCollection(self::buildOtherPostAction());

        $this->updater->updateByTransition($actions, new \Transition(15, 321, null, ListStaticValueBuilder::aStaticValue('field')->build()));

        self::assertEquals(1, $this->delete_post_action->getCallCount());
        self::assertEquals(0, $this->create_post_action->getCallCount());
    }

    private static function buildOtherPostAction(): PostAction
    {
        return new class implements PostAction {
            #[\Override]
            public function accept(PostActionVisitor $visitor): void
            {
            }
        };
    }
}
