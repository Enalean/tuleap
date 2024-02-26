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

use JsonException;
use Psr\Log\LoggerInterface;
use Tuleap\File\FileWriter;

class CachedCustomizedLogoDetector implements IDetectIfLogoIsCustomized
{
    /**
     * @var CustomizedLogoDetector
     */
    private $detector;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(CustomizedLogoDetector $detector, LoggerInterface $logger)
    {
        $this->detector = $detector;
        $this->logger   = $logger;
    }

    public static function invalidateCache(): void
    {
        if (file_exists(self::getCacheFile())) {
            unlink(self::getCacheFile());
        }
    }

    /**
     * @psalm-return non-empty-string
     */
    private static function getCacheFile(): string
    {
        $cache_file = \ForgeConfig::getCacheDir() . '/customized_logo.json';
        assert($cache_file !== '');
        return $cache_file;
    }

    public function isLegacyOrganizationLogoCustomized(): bool
    {
        $information = $this->getInformationFromCacheFile();
        if (isset($information['is_legacy_organization_logo_customized'])) {
            return $information['is_legacy_organization_logo_customized'];
        }

        $information['is_legacy_organization_logo_customized'] = $this->detector->isLegacyOrganizationLogoCustomized();
        $this->storeInformationInCache($information);

        return $information['is_legacy_organization_logo_customized'];
    }

    public function isSvgOrganizationLogoCustomized(): bool
    {
        $information = $this->getInformationFromCacheFile();
        if (isset($information['is_svg_organization_logo_customized'])) {
            return $information['is_svg_organization_logo_customized'];
        }

        $information['is_svg_organization_logo_customized'] = $this->detector->isSvgOrganizationLogoCustomized();
        $this->storeInformationInCache($information);

        return $information['is_svg_organization_logo_customized'];
    }

    private function getInformationFromCacheFile(): array
    {
        $path = self::getCacheFile();
        if (! is_file($path)) {
            return [];
        }

        try {
            $information = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->logger->error(
                "Cache file $path was containing unreadable information. It has been reset.",
                ["exception" => $e]
            );

            return [];
        }

        if (! is_array($information)) {
            return [];
        }

        return $information;
    }

    private function storeInformationInCache(array $information): void
    {
        try {
            FileWriter::writeFile(self::getCacheFile(), json_encode($information, JSON_THROW_ON_ERROR));
        } catch (JsonException | \RuntimeException $e) {
            $this->logger->error("Unable to store customized logo information in cache.", ["exception" => $e]);
        }
    }
}
