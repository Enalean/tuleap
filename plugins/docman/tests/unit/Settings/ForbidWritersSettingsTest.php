<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Settings;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ForbidWritersSettingsTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\TestWith([null, true])]
    #[\PHPUnit\Framework\Attributes\TestWith([['forbid_writers_to_update' => false], true])]
    #[\PHPUnit\Framework\Attributes\TestWith([['forbid_writers_to_update' => true], false])]
    public function testAreWritersAllowedToUpdateProperties(?array $settings, bool $expected): void
    {
        $dao = new class ($settings) implements ForbidWritersDAOSettings {
            public function __construct(private ?array $settings)
            {
            }

            #[\Override]
            public function searchByProjectId(int $project_id): ?array
            {
                return $this->settings;
            }
        };

        self::assertEquals(
            $expected,
            (new ForbidWritersSettings($dao))->areWritersAllowedToUpdateProperties(102)
        );
    }

    #[\PHPUnit\Framework\Attributes\TestWith([null, true])]
    #[\PHPUnit\Framework\Attributes\TestWith([['forbid_writers_to_delete' => false], true])]
    #[\PHPUnit\Framework\Attributes\TestWith([['forbid_writers_to_delete' => true], false])]
    public function testAreWritersAllowedToDelete(?array $settings, bool $expected): void
    {
        $dao = new class ($settings) implements ForbidWritersDAOSettings {
            public function __construct(private ?array $settings)
            {
            }

            #[\Override]
            public function searchByProjectId(int $project_id): ?array
            {
                return $this->settings;
            }
        };

        self::assertEquals(
            $expected,
            (new ForbidWritersSettings($dao))->areWritersAllowedToDelete(102)
        );
    }
}
