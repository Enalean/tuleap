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
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionVisitor;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;
use Tuleap\Tracker\Workflow\Update\PostAction;

final class AddToTopBacklogPostActionValueUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|AddToTopBacklogPostActionDAO
     */
    private $dao;
    /**
     * @var AddToTopBacklogPostActionValueUpdater
     */
    private $updater;

    protected function setUp(): void
    {
        $this->dao     = \Mockery::mock(AddToTopBacklogPostActionDAO::class);
        $this->updater = new AddToTopBacklogPostActionValueUpdater(
            $this->dao,
            new DBTransactionExecutorPassthrough()
        );
    }

    public function testUpdatesPostAction(): void
    {
        $actions = new PostActionCollection(new AddToTopBacklogPostActionValue(), self::buildOtherPostAction());

        $this->dao->shouldReceive('deleteTransitionPostActions')->with(14)->once();
        $this->dao->shouldReceive('createPostActionForTransitionID')->with(14)->once();

        $this->updater->updateByTransition($actions, new \Transition(14, 321, null, null));
    }

    public function testOnlyCreatesThePostActionForTheTransitionIfTheAppropriatePostActionValueIsPresent(): void
    {
        $actions = new PostActionCollection(self::buildOtherPostAction());

        $this->dao->shouldReceive('deleteTransitionPostActions')->with(15)->once();
        $this->dao->shouldNotReceive('createPostActionForTransitionID');

        $this->updater->updateByTransition($actions, new \Transition(15, 321, null, null));
    }

    private static function buildOtherPostAction(): PostAction
    {
        return new class implements PostAction {

            public function accept(PostActionVisitor $visitor): void
            {
            }
        };
    }
}
