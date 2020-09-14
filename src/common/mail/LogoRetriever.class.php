<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

class LogoRetriever
{
    /**
     * @var string
     */
    private $legacy_logo_path;
    /**
     * @var string
     */
    private $legacy_logo_mimetype;
    /**
     * @var string
     */
    private $svg_logo_path;
    /**
     * @var string
     */
    private $small_svg_logo_path;

    public function __construct()
    {
        $this->svg_logo_path        = ForgeConfig::get('sys_data_dir') . '/images/organization_logo.svg';
        $this->small_svg_logo_path  = ForgeConfig::get('sys_data_dir') . '/images/organization_logo_small.svg';
        $this->legacy_logo_path     = ForgeConfig::get('sys_data_dir') . '/images/organization_logo.png';
        $this->legacy_logo_mimetype = 'image/png';
    }

    public function getLegacyPath(): ?string
    {
        if ($this->hasLegacyLogo()) {
            return $this->legacy_logo_path;
        }

        return null;
    }

    public function getSvgPath(): ?string
    {
        if ($this->hasSvgLogo()) {
            return $this->svg_logo_path;
        }

        return null;
    }

    public function getSmallSvgPath(): ?string
    {
        if ($this->hasSmallSvgLogo()) {
            return $this->small_svg_logo_path;
        }

        return null;
    }

    public function getLegacyUrl(): ?string
    {
        if ($this->hasLegacyLogo()) {
            return HTTPRequest::instance()->getServerUrl() . '/images/organization_logo.png';
        }
        return null;
    }

    public function getMimetype(): string
    {
        return $this->legacy_logo_mimetype;
    }

    private function hasLegacyLogo(): bool
    {
        return file_exists($this->legacy_logo_path);
    }

    private function hasSvgLogo(): bool
    {
        return file_exists($this->svg_logo_path);
    }

    private function hasSmallSvgLogo(): bool
    {
        return file_exists($this->small_svg_logo_path);
    }
}
