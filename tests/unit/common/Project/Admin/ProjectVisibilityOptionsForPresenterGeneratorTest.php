<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin;

use Project;
use Tuleap\Project\Admin\Visibility\UpdateVisibilityStatus;

final class ProjectVisibilityOptionsForPresenterGeneratorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testGeneratedOptionsWhenRestrictedUsersAreNotAllowed(): void
    {
        $generator = new ProjectVisibilityOptionsForPresenterGenerator();
        $options   = $generator->generateVisibilityOptions(false, UpdateVisibilityStatus::buildStatusSwitchIsAllowed(), Project::ACCESS_PUBLIC);

        self::assertEqualsCanonicalizing(
            [Project::ACCESS_PUBLIC, Project::ACCESS_PRIVATE],
            $this->getAvailableAccesses($options)
        );

        self::assertEquals(Project::ACCESS_PUBLIC, $this->getSelectedAccess($options));
    }

    public function testGeneratedOptionsWhenRestrictedUsersAreAllowed(): void
    {
        $generator = new ProjectVisibilityOptionsForPresenterGenerator();
        $options   = $generator->generateVisibilityOptions(true, UpdateVisibilityStatus::buildStatusSwitchIsAllowed(), Project::ACCESS_PRIVATE_WO_RESTRICTED);

        self::assertEqualsCanonicalizing(
            [Project::ACCESS_PUBLIC, Project::ACCESS_PRIVATE, Project::ACCESS_PRIVATE_WO_RESTRICTED, Project::ACCESS_PUBLIC_UNRESTRICTED],
            $this->getAvailableAccesses($options)
        );

        self::assertEquals(Project::ACCESS_PRIVATE_WO_RESTRICTED, $this->getSelectedAccess($options));
    }

    public function testGeneratedOptionsWhenRestrictedUsersAreAllowedButDisabled(): void
    {
        $generator = new ProjectVisibilityOptionsForPresenterGenerator();
        $options   = $generator->generateVisibilityOptions(true, UpdateVisibilityStatus::buildStatusSwitchIsNotAllowed(''), Project::ACCESS_PUBLIC);

        self::assertEqualsCanonicalizing(
            [Project::ACCESS_PUBLIC, Project::ACCESS_PRIVATE, Project::ACCESS_PRIVATE_WO_RESTRICTED, Project::ACCESS_PUBLIC_UNRESTRICTED],
            $this->getAvailableAccesses($options)
        );

        self::assertTrue($this->getPrivateWithoutRestrictedOptionDisabledValue($options));
    }

    private function getPrivateWithoutRestrictedOptionDisabledValue(array $options): bool
    {
        foreach ($options as $option) {
            if ($option['value'] == Project::ACCESS_PRIVATE_WO_RESTRICTED) {
                return $option['disabled'];
            }
        }

        return false;
    }

    private function getAvailableAccesses(array $options): array
    {
        $accesses = [];

        foreach ($options as $option) {
            $accesses[] = $option['value'];
        }

        return $accesses;
    }

    private function getSelectedAccess(array $options): string
    {
        foreach ($options as $option) {
            if ($option['selected'] !== '') {
                return $option['value'];
            }
        }

        return '';
    }
}
