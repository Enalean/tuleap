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

namespace Tuleap\AgileDashboard\Workflow\PostAction\Update\Internal;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Transition;
use Tuleap\AgileDashboard\Workflow\PostAction\Update\AddToTopBacklogValue;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AddToTopBacklogValueUpdaterTest extends TestCase
{
    private AddToTopBacklogValueUpdater $value_updater;
    private AddToTopBacklogValueRepository&MockObject $value_repository;
    private PostActionCollection&MockObject $collection;
    private Transition $transition;

    protected function setUp(): void
    {
        $this->value_repository = $this->createMock(AddToTopBacklogValueRepository::class);
        $this->value_updater    = new AddToTopBacklogValueUpdater($this->value_repository);
        $this->collection       = $this->createMock(PostActionCollection::class);
        $this->transition       = new Transition(
            1,
            1,
            null,
            new Tracker_FormElement_Field_List_Bind_StaticValue(1, 'label', '', 1, false),
        );
    }

    public function testItAddsOnlyOneAddToTopBacklogPostAction(): void
    {
        $this->collection->expects(self::once())->method('getExternalPostActionsValue')
            ->willReturn([
                new AddToTopBacklogValue(),
                new AddToTopBacklogValue(),
            ]);

        $this->value_repository->expects(self::once())->method('deleteAllByTransition');
        $this->value_repository->expects(self::once())->method('create');

        $this->value_updater->updateByTransition(
            $this->collection,
            $this->transition
        );
    }

    public function testItOnlyDeletesAddToTopBacklogPostActionIfNoActionProvided(): void
    {
        $this->collection->expects(self::once())->method('getExternalPostActionsValue')->willReturn([]);

        $this->value_repository->expects(self::once())->method('deleteAllByTransition');
        $this->value_repository->expects(self::never())->method('create');

        $this->value_updater->updateByTransition(
            $this->collection,
            $this->transition
        );
    }
}
