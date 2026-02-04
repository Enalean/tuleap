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
use Tuleap\Reference\CrossReferencesDao;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\ReferenceBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ReferenceUpdateCommandTest extends TestCase
{
    private ReferenceManager&\PHPUnit\Framework\MockObject\MockObject $reference_manager;
    private CrossReferencesDao&\PHPUnit\Framework\MockObject\MockObject $cross_references_dao;
    private ReferenceUpdateCommand $reference_update_command;

    #[Override]
    protected function setUp(): void
    {
        $this->reference_manager    = $this->createMock(ReferenceManager::class);
        $this->cross_references_dao = $this->createMock(CrossReferencesDao::class);

        $this->reference_update_command = new ReferenceUpdateCommand(
            $this->reference_manager,
            $this->cross_references_dao
        );
    }

    public function testUpdateSystemReference(): void
    {
        $existing_reference = ReferenceBuilder::anArtifactReference()->inProject(123)->isASystemReference()->build();

        $http_request = HTTPRequestBuilder::get()->withParams([
            'is_used' => false,
        ])->build();

        $this->reference_manager
            ->expects($this->once())
            ->method('updateIsActive')
            ->with($existing_reference, false);

        $result = $this->reference_update_command->updateReference($existing_reference, $http_request, true, false);

        $this->assertTrue($result);
    }

    public function testUpdateProjectReferenceWithoutKeywordChange(): void
    {
        $existing_reference = ReferenceBuilder::anArtifactReference()->inProject(123)->isAProjectReference()->build();

        $http_request = HTTPRequestBuilder::get()->withParams([
            'keyword' => $existing_reference->getKeyword(),
            'service_short_name' => 'plugin_tracker',
            'description' => 'description',
            'link' => 'link',
            'nature' => 'nature',
            'is_used' => true,
        ])->build();

        $this->reference_manager
            ->expects($this->once())
            ->method('updateReference')
            ->willReturn(true);

        $this->cross_references_dao
            ->expects($this->never())
            ->method('updateTargetKeyword');

        $result = $this->reference_update_command->updateReference($existing_reference, $http_request, true, false);

        $this->assertTrue($result);
    }

    public function testUpdateProjectReferenceWithKeywordChangeAndUpdateAlreadyCreatedReferences(): void
    {
        $existing_reference = ReferenceBuilder::anArtifactReference()->inProject(123)->isAProjectReference()->build();

        $http_request = HTTPRequestBuilder::get()->withParams([
            'keyword' => 'new_keyword',
            'service_short_name' => 'plugin_tracker',
            'description' => 'description',
            'link' => 'link',
            'nature' => 'nature',
            'is_used' => 'true',
        ])->build();

        $this->reference_manager
            ->expects($this->once())
            ->method('updateReference')
            ->willReturn(true);

        $this->cross_references_dao
            ->expects($this->once())
            ->method('updateTargetKeyword')
            ->with($existing_reference->getKeyword(), 'new_keyword', $existing_reference->getGroupId());

        $this->cross_references_dao
            ->expects($this->once())
            ->method('updateSourceKeyword')
            ->with($existing_reference->getKeyword(), 'new_keyword', $existing_reference->getGroupId());

        $result = $this->reference_update_command->updateReference($existing_reference, $http_request, true, false);

        $this->assertTrue($result);
    }

    public function testItWillReturnFalseWhenDataFailedToBeStored(): void
    {
        $existing_reference = ReferenceBuilder::anArtifactReference()->inProject(123)->isAProjectReference()->build();

        $http_request = HTTPRequestBuilder::get()->withParams([
            'keyword' => $existing_reference->getKeyword(),
            'service_short_name' => 'plugin_tracker',
            'description' => 'description',
            'link' => 'link',
            'nature' => 'nature',
            'is_used' => 'true',
        ])->build();

        $this->reference_manager
            ->expects($this->once())
            ->method('updateReference')
            ->willReturn(false);

        $this->cross_references_dao
            ->expects($this->never())
            ->method('updateTargetKeyword');

        $result = $this->reference_update_command->updateReference($existing_reference, $http_request, true, false);

        $this->assertFalse($result);
    }
}
