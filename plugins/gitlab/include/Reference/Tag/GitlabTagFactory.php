<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Reference\Tag;

use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\TagPush\TagInfoDao;

class GitlabTagFactory
{
    /**
     * @var TagInfoDao
     */
    private $tag_info_dao;

    public function __construct(TagInfoDao $tag_info_dao)
    {
        $this->tag_info_dao = $tag_info_dao;
    }

    public function getGitlabTagInRepositoryWithTagName(
        GitlabRepositoryIntegration $repository_integration,
        string $tag_name,
    ): ?GitlabTag {
        $row = $this->tag_info_dao->searchTagInRepositoryWithTagName(
            $repository_integration->getId(),
            $tag_name
        );

        if ($row === null) {
            return null;
        }

        return new GitlabTag(
            $row['commit_sha1'],
            $row['tag_name'],
            $row['tag_message']
        );
    }
}
