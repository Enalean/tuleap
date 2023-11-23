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

use org\bovigo\vfs\vfsStream;
use Tuleap\ForgeConfigSandbox;

class CustomizedLogoDetectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private $data_dir_path;

    protected function setUp(): void
    {
        $this->data_dir_path = vfsStream::setup('/')->url();
        \ForgeConfig::set('sys_data_dir', $this->data_dir_path);
        mkdir($this->data_dir_path . '/images', 0750, true);
    }

    public function testItConsidersLogoNotCustomizedIfItIsNotDeployed(): void
    {
        $detector = new CustomizedLogoDetector(new \LogoRetriever(), new FileContentComparator());

        self::assertFalse($detector->isLegacyOrganizationLogoCustomized());
    }

    public function testItConsidersLogoNotCustomizedIfItIsTheSameAsOurs(): void
    {
        copy(
            __DIR__ . '/../../../../../src/www/themes/BurningParrot/images/organization_logo.png',
            $this->data_dir_path . '/images/organization_logo.png',
        );

        $detector = new CustomizedLogoDetector(new \LogoRetriever(), new FileContentComparator());

        self::assertFalse($detector->isLegacyOrganizationLogoCustomized());
    }

    public function testItConsidersLogoCustomizedIfContentIsNotTheSameAsOurs(): void
    {
        copy(__FILE__, $this->data_dir_path . '/images/organization_logo.png');

        $detector = new CustomizedLogoDetector(new \LogoRetriever(), new FileContentComparator());

        self::assertTrue($detector->isLegacyOrganizationLogoCustomized());
    }

    public function testItDoesNotConsiderSvgLogoCustomizedIfItIsNotDeployed(): void
    {
        $detector = new CustomizedLogoDetector(new \LogoRetriever(), new FileContentComparator());

        self::assertFalse($detector->isSvgOrganizationLogoCustomized());
    }

    public function testItDoesNotConsiderSvgLogoCustomizedIfTheSmallVariantIsNotDeployed(): void
    {
        touch($this->data_dir_path . '/images/organization_logo.svg');

        $detector = new CustomizedLogoDetector(new \LogoRetriever(), new FileContentComparator());

        self::assertFalse($detector->isSvgOrganizationLogoCustomized());
    }

    public function testItConsidersSvgLogoCustomizedIfItIsDeployedAsWellAsItsSmallVariant(): void
    {
        touch($this->data_dir_path . '/images/organization_logo.svg');
        touch($this->data_dir_path . '/images/organization_logo_small.svg');

        $detector = new CustomizedLogoDetector(new \LogoRetriever(), new FileContentComparator());

        self::assertTrue($detector->isSvgOrganizationLogoCustomized());
    }
}
