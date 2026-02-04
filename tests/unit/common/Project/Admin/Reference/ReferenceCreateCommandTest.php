<?php
/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Reference;

use Override;
use PHPUnit\Framework\TestCase;
use ReferenceManager;
use Tuleap\Test\Builders\HTTPRequestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ReferenceCreateCommandTest extends TestCase
{
    private const SYSTEM_SCOPE  = 'S';
    private const PROJECT_SCOPE = 'P';
    private ReferenceManager&\PHPUnit\Framework\MockObject\MockObject $reference_manager;
    private ReferenceCreateCommand $reference_create_command;

    #[Override]
    protected function setUp(): void
    {
        $this->reference_manager        = $this->createMock(ReferenceManager::class);
        $this->reference_create_command = new ReferenceCreateCommand($this->reference_manager);
    }

    public function testItCreatesAProjectReference(): void
    {
        $http_request = HTTPRequestBuilder::get()->withParams([
            'keyword' => 'keyword',
            'description' => 'description',
            'link' => 'link',
            'scope' => self::PROJECT_SCOPE,
            'service_short_name' => 'service',
            'nature' => 'nature',
            'is_used' => true,
            'group_id' => 123,
        ])->build();

        $this->reference_manager
            ->expects($this->once())
            ->method('createReference')
            ->willReturn(true);

        $result = $this->reference_create_command->createReference($http_request, false, false);

        $this->assertTrue($result);
    }

    public function testItCreatesASystemReference(): void
    {
        $http_request = HTTPRequestBuilder::get()->withParams([
            'keyword' => 'keyword',
            'description' => 'description',
            'link' => 'link',
            'scope' => self::SYSTEM_SCOPE ,
            'service_short_name' => 'service',
            'nature' => 'nature',
            'is_used' => true,
            'group_id' => \Project::DEFAULT_TEMPLATE_PROJECT_ID,
        ])->build();

        $this->reference_manager
            ->expects($this->once())
            ->method('createSystemReference')
            ->willReturn(true);

        $result = $this->reference_create_command->createReference($http_request, false, false);

        $this->assertTrue($result);
    }

    public function testItReturnsFalseWhenReferenceCreationFails(): void
    {
        $http_request = HTTPRequestBuilder::get()->withParams([
            'keyword' => 'keyword',
            'description' => 'description',
            'link' => 'link',
            'scope' => self::SYSTEM_SCOPE,
            'service_short_name' => 'service',
            'nature' => 'nature',
            'is_used' => true,
            'group_id' => 123,
        ])->build();

        $this->reference_manager
            ->expects($this->once())
            ->method('createReference')
            ->willReturn(false);

        $result = $this->reference_create_command->createReference($http_request, false, false);

        $this->assertFalse($result);
    }
}
