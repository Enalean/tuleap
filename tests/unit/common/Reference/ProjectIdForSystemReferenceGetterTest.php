<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Reference;

use FRSFile;
use FRSFileFactory;
use FRSRelease;
use FRSReleaseFactory;
use ReferenceManager;
use Tuleap\Event\Dispatchable;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\Reference\GetSystemReferenceNatureByKeywordStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectIdForSystemReferenceGetterTest extends TestCase
{
    private const int PROJECT_ID = 101;

    public function testReturnsNullIfNoNatureForKeyword(): void
    {
        $getter = new ProjectIdForSystemReferenceGetter(
            GetSystemReferenceNatureByKeywordStub::withoutMatchingNature(),
            $this->createMock(FRSReleaseFactory::class),
            $this->createMock(FRSFileFactory::class),
            EventDispatcherStub::withIdentityCallback(),
        );

        self::assertNull($getter->getProjectIdForSystemReference('foo', '123'));
    }

    public function testReturnsNullIfReferencedReleaseDoesNotExist(): void
    {
        $release_factory = $this->createMock(FRSReleaseFactory::class);
        $release_factory->method('getFRSReleaseFromDb')->willReturn(null);

        $getter = new ProjectIdForSystemReferenceGetter(
            GetSystemReferenceNatureByKeywordStub::withMatchingNature(ReferenceManager::REFERENCE_NATURE_RELEASE),
            $release_factory,
            $this->createMock(FRSFileFactory::class),
            EventDispatcherStub::withIdentityCallback(),
        );

        self::assertNull($getter->getProjectIdForSystemReference('release', '123'));
    }

    public function testReturnsProjectIdOfReferencedRelease(): void
    {
        $release = $this->createMock(FRSRelease::class);
        $release->method('getProject')->willReturn(
            ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build(),
        );

        $release_factory = $this->createMock(FRSReleaseFactory::class);
        $release_factory->method('getFRSReleaseFromDb')->willReturn($release);

        $getter = new ProjectIdForSystemReferenceGetter(
            GetSystemReferenceNatureByKeywordStub::withMatchingNature(ReferenceManager::REFERENCE_NATURE_RELEASE),
            $release_factory,
            $this->createMock(FRSFileFactory::class),
            EventDispatcherStub::withIdentityCallback(),
        );

        self::assertSame(self::PROJECT_ID, $getter->getProjectIdForSystemReference('release', '123'));
    }

    public function testReturnsNullIfReferencedFileDoesNotExist(): void
    {
        $file_factory = $this->createMock(FRSFileFactory::class);
        $file_factory->method('getFRSFileFromDb')->willReturn(null);

        $getter = new ProjectIdForSystemReferenceGetter(
            GetSystemReferenceNatureByKeywordStub::withMatchingNature(ReferenceManager::REFERENCE_NATURE_FILE),
            $this->createMock(FRSReleaseFactory::class),
            $file_factory,
            EventDispatcherStub::withIdentityCallback(),
        );

        self::assertNull($getter->getProjectIdForSystemReference('file', '123'));
    }

    public function testReturnsProjectIdOfReferencedFile(): void
    {
        $file = $this->createMock(FRSFile::class);
        $file->method('getGroup')->willReturn(
            ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build(),
        );

        $file_factory = $this->createMock(FRSFileFactory::class);
        $file_factory->method('getFRSFileFromDb')->willReturn($file);

        $getter = new ProjectIdForSystemReferenceGetter(
            GetSystemReferenceNatureByKeywordStub::withMatchingNature(ReferenceManager::REFERENCE_NATURE_FILE),
            $this->createMock(FRSReleaseFactory::class),
            $file_factory,
            EventDispatcherStub::withIdentityCallback(),
        );

        self::assertSame(self::PROJECT_ID, $getter->getProjectIdForSystemReference('file', '123'));
    }

    public function testReturnsNullIfNoNatureIsNotFulfilled(): void
    {
        $getter = new ProjectIdForSystemReferenceGetter(
            GetSystemReferenceNatureByKeywordStub::withMatchingNature('foobar'),
            $this->createMock(FRSReleaseFactory::class),
            $this->createMock(FRSFileFactory::class),
            EventDispatcherStub::withIdentityCallback(),
        );

        self::assertNull($getter->getProjectIdForSystemReference('foo', '123'));
    }

    public function testReturnsProjectIdFromPlugin(): void
    {
        $getter = new ProjectIdForSystemReferenceGetter(
            GetSystemReferenceNatureByKeywordStub::withMatchingNature('foobar'),
            $this->createMock(FRSReleaseFactory::class),
            $this->createMock(FRSFileFactory::class),
            EventDispatcherStub::withCallback(
                static function (Dispatchable $event): Dispatchable {
                    if ($event instanceof GetProjectIdForSystemReferenceEvent) {
                        $event->setProjectId(101);
                    }
                    return $event;
                }
            ),
        );

        self::assertSame(self::PROJECT_ID, $getter->getProjectIdForSystemReference('file', '123'));
    }
}
