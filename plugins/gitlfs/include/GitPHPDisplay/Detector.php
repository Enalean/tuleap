<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\GitLFS\GitPHPDisplay;

class Detector
{
    public const LFS_CONTENT_REGEXP         = "/^version\s.*\noid\ssha256:(?P<oidsha256>[A-Fa-f0-9]{64})\nsize\s[0-9]+\n$/";
    public const LFS_CONTENT_REGEXP_OID_KEY = "oidsha256";

    public function isFileALFSFile($file_content)
    {
        return (bool) preg_match(self::LFS_CONTENT_REGEXP, $file_content);
    }
}
