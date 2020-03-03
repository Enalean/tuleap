<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\HudsonGit\Hook\JenkinsTuleapBranchSourcePluginHook;

/**
 * @psalm-immutable
 */
class JenkinsTuleapPluginHookResponse
{
    /**
     * @var int
     */
    private $status_code;

    /**
     * @var string
     */
    private $body;

    public function __construct(int $status_code, string $body)
    {
        $this->status_code = $status_code;
        $this->body        = $body;
    }

    public function getStatusCode(): int
    {
        return $this->status_code;
    }

    public function getBody(): string
    {
        return $this->body;
    }
}
