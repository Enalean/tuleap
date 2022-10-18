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

namespace Tuleap\OnlyOffice\Open;

use Tuleap\Docman\FilenamePattern\RetrieveFilenamePattern;
use Tuleap\Docman\Tests\Stub\FilenamePatternRetrieverStub;
use Tuleap\ForgeConfigSandbox;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class DocmanFileLastVersionProviderTest extends TestCase
{
    use ForgeConfigSandbox;

    private const PROJECT_ID = 102;

    /**
     * @var \Docman_ItemFactory&\PHPUnit\Framework\MockObject\Stub
     */
    private $item_factory;
    /**
     * @var \Docman_VersionFactory&\PHPUnit\Framework\MockObject\Stub
     */
    private $version_factory;
    /**
     * @var \Docman_PermissionsManager&\PHPUnit\Framework\MockObject\Stub
     */
    private $permissions_manager;

    protected function setUp(): void
    {
        $this->item_factory    = $this->createStub(\Docman_ItemFactory::class);
        $this->version_factory = $this->createStub(\Docman_VersionFactory::class);

        $this->permissions_manager = $this->createStub(\Docman_PermissionsManager::class);
        \Docman_PermissionsManager::setInstance(self::PROJECT_ID, $this->permissions_manager);
    }

    protected function tearDown(): void
    {
        \Docman_PermissionsManager::clearInstances();
    }

    /**
     * @dataProvider dataProviderLastVersionFileEdit
     */
    public function testCanRetrieveTheLastVersionOfADocmanFile(
        bool $user_can_write,
        RetrieveFilenamePattern $filename_pattern_retriever,
        bool $feature_flag_edition_is_enabled,
        bool $expected_can_be_edited,
    ): void {
        $item = new \Docman_File(['group_id' => self::PROJECT_ID]);
        $this->item_factory->method('getItemFromDb')->willReturn($item);
        $this->permissions_manager->method('userCanAccess')->willReturn(true);
        $this->permissions_manager->method('userCanWrite')->willReturn($user_can_write);
        $expected_version = new \Docman_Version();
        $this->version_factory->method('getCurrentVersionForItem')->willReturn($expected_version);

        if ($feature_flag_edition_is_enabled) {
            \ForgeConfig::setFeatureFlag('onlyoffice_edit_document', '1');
        }

        $provider = $this->buildProvider($filename_pattern_retriever);

        $result = $provider->getLastVersionOfAFileUserCanAccess(UserTestBuilder::buildWithDefaults(), 741);

        self::assertTrue(Result::isOk($result));
        self::assertSame($item, $result->unwrapOr(null)->item);
        self::assertSame($expected_version, $result->unwrapOr(null)->version);
        self::assertSame($expected_can_be_edited, $result->unwrapOr(null)->can_be_edited);
    }

    public function dataProviderLastVersionFileEdit(): array
    {
        return [
            'Document can be edited in ONLYOFFICE' => [true, FilenamePatternRetrieverStub::buildWithNoPattern(), true, true],
            'Feature flag to allow edition is disabled' => [true, FilenamePatternRetrieverStub::buildWithNoPattern(), false, false],
            'User cannot edit the document' => [false, FilenamePatternRetrieverStub::buildWithNoPattern(), true, false],
            'Filename pattern prevent edition in ONLYOFFICE' => [true, FilenamePatternRetrieverStub::buildWithPattern('something'), true, false],
        ];
    }

    public function testCannotRetrieveANonExistingFile(): void
    {
        $this->item_factory->method('getItemFromDb')->willReturn(null);

        $provider = $this->buildProvider(FilenamePatternRetrieverStub::buildWithNoPattern());

        $result = $provider->getLastVersionOfAFileUserCanAccess(UserTestBuilder::buildWithDefaults(), 404);

        self::assertTrue(Result::isErr($result));
    }

    public function testCannotRetrieveTheVersionOfAnItemThatIsNotAFile(): void
    {
        $this->item_factory->method('getItemFromDb')->willReturn(new \Docman_Folder());

        $provider = $this->buildProvider(FilenamePatternRetrieverStub::buildWithNoPattern());

        $result = $provider->getLastVersionOfAFileUserCanAccess(UserTestBuilder::buildWithDefaults(), 999);

        self::assertTrue(Result::isErr($result));
    }

    public function testCannotRetrieveTheVersionOfAnItemTheUserCannotAccess(): void
    {
        $this->item_factory->method('getItemFromDb')->willReturn(new \Docman_File(['group_id' => self::PROJECT_ID]));
        $this->permissions_manager->method('userCanAccess')->willReturn(false);

        $provider = $this->buildProvider(FilenamePatternRetrieverStub::buildWithNoPattern());

        $result = $provider->getLastVersionOfAFileUserCanAccess(UserTestBuilder::buildWithDefaults(), 403);


        self::assertTrue(Result::isErr($result));
    }

    public function testCannotRetrieveANonExistantVersion(): void
    {
        $this->item_factory->method('getItemFromDb')->willReturn(new \Docman_File(['group_id' => self::PROJECT_ID]));
        $this->permissions_manager->method('userCanAccess')->willReturn(true);
        $this->version_factory->method('getCurrentVersionForItem')->willReturn(null);

        $provider = $this->buildProvider(FilenamePatternRetrieverStub::buildWithNoPattern());

        $result = $provider->getLastVersionOfAFileUserCanAccess(UserTestBuilder::buildWithDefaults(), 852);

        self::assertTrue(Result::isErr($result));
    }

    private function buildProvider(
        RetrieveFilenamePattern $filename_pattern_retriever,
    ): DocmanFileLastVersionProvider {
        return new DocmanFileLastVersionProvider(
            $this->item_factory,
            $this->version_factory,
            $filename_pattern_retriever,
        );
    }
}
