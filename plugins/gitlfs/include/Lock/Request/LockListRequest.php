<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\GitLFS\Lock\Request;

use Tuleap\GitLFS\HTTP\GitLfsHTTPOperation;
use Tuleap\GitLFS\HTTP\RequestReference;

class LockListRequest implements GitLfsHTTPOperation
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var string|null
     */
    private $path;

    /**
     * @var RequestReference|null
     */
    private $reference;

    public function __construct(
        ?int $id,
        ?string $path,
        ?RequestReference $reference
    ) {
        $this->id        = $id;
        $this->path      = $path;
        $this->reference = $reference;
    }

    public static function buildFromHTTPRequest(\HTTPRequest $request): LockListRequest
    {
        $reference = null;
        $refspec   = $request->get("refspec") ?: null;

        if ($refspec !== null) {
            $reference = new RequestReference($refspec);
        }
        return new self(
            $request->get("id") ?: null,
            $request->get("path") ?: null,
            $reference
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function isWrite(): bool
    {
        return true;
    }

    public function isRead(): bool
    {
        return true;
    }

    public function getReference(): ?RequestReference
    {
        return $this->reference;
    }
}
