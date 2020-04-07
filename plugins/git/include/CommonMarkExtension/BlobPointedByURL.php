<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Git\CommonMarkExtension;

/**
 * @psalm-immutable
 */
final class BlobPointedByURL
{
    /**
     * @var string
     */
    private $blob_ref;
    /**
     * @var string
     */
    private $commit_ref;
    /**
     * @var string
     */
    private $path;

    public function __construct(string $blob_ref, string $commit_ref, string $path_in_repo)
    {
        $this->blob_ref   = $blob_ref;
        $this->commit_ref = $commit_ref;
        $this->path       = $path_in_repo;
    }

    public function getBlobRef(): string
    {
        return $this->blob_ref;
    }

    public function getCommitRef(): string
    {
        return $this->commit_ref;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
