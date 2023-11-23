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

namespace Tuleap\Layout\Logo;

use Psr\Log\NullLogger;
use Tuleap\ForgeConfigSandbox;

class CachedCustomizedLogoDetectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;
    use \Tuleap\TemporaryTestDirectory;

    private string $cache_file;

    protected function setUp(): void
    {
        // use TemporaryTestDirectory trait instead of vfsStream because the latter is not compatible with FileWriter
        $cache_dir_path = $this->getTmpDir();
        \ForgeConfig::set('codendi_cache_dir', $cache_dir_path);
        $this->cache_file = $cache_dir_path . '/customized_logo.json';
    }

    public function testItDoesRealComputationIfInformationIsNotInCache(): void
    {
        $logger   = new NullLogger();
        $detector = $this->createMock(CustomizedLogoDetector::class);

        $cache = new CachedCustomizedLogoDetector($detector, $logger);

        $detector->expects(self::once())
            ->method('isLegacyOrganizationLogoCustomized');

        $cache->isLegacyOrganizationLogoCustomized();
    }

    public function testItUsesOnlyInformationAlreadyInCache(): void
    {
        $logger   = new NullLogger();
        $detector = $this->createMock(CustomizedLogoDetector::class);

        $content = json_encode(["is_legacy_organization_logo_customized" => true], JSON_THROW_ON_ERROR);
        file_put_contents($this->cache_file, $content);

        $cache = new CachedCustomizedLogoDetector($detector, $logger);

        $detector->expects(self::never())
            ->method('isLegacyOrganizationLogoCustomized');

        self::assertTrue($cache->isLegacyOrganizationLogoCustomized());
    }

    public function testItReturnsFalseIfItIsFalseInCache(): void
    {
        $logger   = new NullLogger();
        $detector = $this->createMock(CustomizedLogoDetector::class);

        $content = json_encode(["is_legacy_organization_logo_customized" => false], JSON_THROW_ON_ERROR);
        file_put_contents($this->cache_file, $content);

        $cache = new CachedCustomizedLogoDetector($detector, $logger);

        self::assertFalse($cache->isLegacyOrganizationLogoCustomized());
    }

    public function testItStoresTheInformationInCache(): void
    {
        $logger   = new NullLogger();
        $detector = $this->createMock(CustomizedLogoDetector::class);
        $detector->expects(self::once())
            ->method('isLegacyOrganizationLogoCustomized')
            ->willReturn(false);

        $cache = new CachedCustomizedLogoDetector($detector, $logger);
        self::assertFalse($cache->isLegacyOrganizationLogoCustomized());

        self::assertStringEqualsFile($this->cache_file, '{"is_legacy_organization_logo_customized":false}');
    }

    public function testItStoresTrueInTheCacheIfLogoIsCustomized(): void
    {
        $logger   = new NullLogger();
        $detector = $this->createMock(CustomizedLogoDetector::class);
        $detector->expects(self::once())
            ->method('isLegacyOrganizationLogoCustomized')
            ->willReturn(true);

        $cache = new CachedCustomizedLogoDetector($detector, $logger);
        self::assertTrue($cache->isLegacyOrganizationLogoCustomized());

        self::assertStringEqualsFile($this->cache_file, '{"is_legacy_organization_logo_customized":true}');
    }

    public function testItRegeneratesTheCacheIfItDoesNotContainsAnArray(): void
    {
        $logger   = new NullLogger();
        $detector = $this->createMock(CustomizedLogoDetector::class);
        $detector->expects(self::once())
            ->method('isLegacyOrganizationLogoCustomized')
            ->willReturn(true);

        $content = 'whatever';
        file_put_contents($this->cache_file, $content);

        $cache = new CachedCustomizedLogoDetector($detector, $logger);
        self::assertTrue($cache->isLegacyOrganizationLogoCustomized());

        self::assertStringEqualsFile($this->cache_file, '{"is_legacy_organization_logo_customized":true}');
    }

    public function testItInvalidatesCache(): void
    {
        $content = json_encode(["is_legacy_organization_logo_customized" => true], JSON_THROW_ON_ERROR);
        file_put_contents($this->cache_file, $content);

        CachedCustomizedLogoDetector::invalidateCache();

        self::assertFileDoesNotExist($this->cache_file);
    }

    public function testItStoresTrueInTheCacheIfSvgLogoIsCustomized(): void
    {
        $logger   = new NullLogger();
        $detector = $this->createMock(CustomizedLogoDetector::class);
        $detector->expects(self::once())
            ->method('isSvgOrganizationLogoCustomized')
            ->willReturn(true);

        $cache = new CachedCustomizedLogoDetector($detector, $logger);
        self::assertTrue($cache->isSvgOrganizationLogoCustomized());

        self::assertStringEqualsFile($this->cache_file, '{"is_svg_organization_logo_customized":true}');
    }

    public function testItUsesInformationInCacheToKnowIfSvgLogoIsCustomized(): void
    {
        $content = json_encode(["is_svg_organization_logo_customized" => true], JSON_THROW_ON_ERROR);
        file_put_contents($this->cache_file, $content);

        $logger   = new NullLogger();
        $detector = $this->createMock(CustomizedLogoDetector::class);
        $detector->expects(self::never())
            ->method('isSvgOrganizationLogoCustomized');

        $cache = new CachedCustomizedLogoDetector($detector, $logger);
        self::assertTrue($cache->isSvgOrganizationLogoCustomized());
    }
}
