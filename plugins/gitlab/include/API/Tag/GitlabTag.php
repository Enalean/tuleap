<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\API\Tag;

use Tuleap\Gitlab\API\GitlabResponseAPIException;

/**
 * @psalm-immutable
 */
class GitlabTag
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $message;
    /**
     * @var string
     */
    private $commit_sha1;

    public function __construct(string $name, string $message, string $commit_sha1)
    {
        $this->name        = $name;
        $this->message     = $message;
        $this->commit_sha1 = $commit_sha1;
    }

    public static function buildFromAPIResponse(array $tag_reponse): self
    {
        if (
            ! array_key_exists('name', $tag_reponse) ||
            ! array_key_exists('message', $tag_reponse) ||
            ! array_key_exists('commit', $tag_reponse)
        ) {
            throw new GitlabResponseAPIException("Some keys are missing in the tag Json. This is not expected. Aborting.");
        }

        $tag_commit_info = $tag_reponse['commit'];
        if (
            ! array_key_exists('id', $tag_commit_info)
        ) {
            throw new GitlabResponseAPIException("Some keys are missing in the commit Json. This is not expected. Aborting.");
        }

        if (
            ! is_string($tag_reponse['name']) ||
            ! is_string($tag_reponse['message']) ||
            ! is_string($tag_commit_info['id'])
        ) {
            throw new GitlabResponseAPIException("Some keys haven't the expected types. This is not expected. Aborting.");
        }

        return new self(
            $tag_reponse['name'],
            $tag_reponse['message'],
            $tag_commit_info['id'],
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getCommitSha1(): string
    {
        return $this->commit_sha1;
    }
}
