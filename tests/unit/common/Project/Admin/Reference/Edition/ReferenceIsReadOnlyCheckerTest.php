<?php
/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project\Admin\Reference\Edition;

use Event;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ReferenceIsReadOnlyCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ReferenceIsReadOnlyChecker $checker;
    private \EventManager&\PHPUnit\Framework\MockObject\MockObject $event_manager;

    protected function setUp(): void
    {
        $this->event_manager = $this->createMock(\EventManager::class);
        $this->checker       = new ReferenceIsReadOnlyChecker($this->event_manager);
    }

    /**
     * @testWith ["P", 105, "", false]
     *           ["S", 105, "", true]
     *           ["S", 100, "", false]
     *           ["P", 105, "tracker", true]
     */
    public function testCheckThatReferencesCanBeEdited($scope, $project_id, $service_short_name, $expected_result): void
    {
        $reference = new \Reference(
            1,
            'art',
            'desc',
            'https://example.com',
            $scope,
            $service_short_name,
            'tracker',
            1,
            $project_id
        );
        $this->event_manager->expects(self::once())->method('processEvent')->with(Event::GET_REFERENCE_ADMIN_CAPABILITIES, self::callback(function (array $args) {
            $args['can_be_edited'] = true;
            return true;
        }));

        self::assertSame($expected_result, $this->checker->isReferenceReadOnly($reference));
    }
}
