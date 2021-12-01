<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

/**
 * @psalm-immutable
 */
class GitlabTag
{
    /**
     * @var string
     */
    private $commit_sha1;
    /**
     * @var string
     */
    private $tag_name;
    /**
     * @var string
     */
    private $tag_message;

    public function __construct(
        string $commit_sha1,
        string $tag_name,
        string $tag_message,
    ) {
        $this->commit_sha1 = $commit_sha1;
        $this->tag_name    = $tag_name;
        $this->tag_message = $tag_message;
    }

    public function getTagMessage(): string
    {
        return $this->tag_message;
    }

    public function getTagName(): string
    {
        return $this->tag_name;
    }

    public function getCommitSha1(): string
    {
        return $this->commit_sha1;
    }
}
