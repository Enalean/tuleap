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
    private $logo_path;
    private $logo_mimetype;

    public function __construct()
    {
        $this->logo_path     = ForgeConfig::get('sys_data_dir') . '/images/organization_logo.png';
        $this->logo_mimetype = 'image/png';
    }

    public function getPath()
    {
        if ($this->hasLogo()) {
            return $this->logo_path;
        }
        return null;
    }

    public function getUrl()
    {
        if ($this->hasLogo()) {
            return HTTPRequest::instance()->getServerUrl() . '/images/organization_logo.png';
        }
        return null;
    }

    public function getMimetype()
    {
        return $this->logo_mimetype;
    }

    private function hasLogo()
    {
        return file_exists($this->logo_path);
    }
}
