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

use GitRepository;
use PFUser;

class DownloadURLBuilder
{

    public function buildDownloadURL(GitRepository $repository, PFUser $user, $file_content)
    {
        if (! $repository->userCanRead($user)) {
            return '';
        }

        $repository_id = $repository->getId();
        $oid_sha_256   = $this->getOidSha256($file_content);

        if ($oid_sha_256 === null) {
            return '';
        }

        return "plugins/git-lfs/$repository_id/objects/$oid_sha_256";
    }

    public function getOidSha256($file)
    {
        $matches = [];
        preg_match(Detector::LFS_CONTENT_REGEXP, $file, $matches);

        if (isset($matches[Detector::LFS_CONTENT_REGEXP_OID_KEY])) {
            return $matches[Detector::LFS_CONTENT_REGEXP_OID_KEY];
        }

        return null;
    }
}
