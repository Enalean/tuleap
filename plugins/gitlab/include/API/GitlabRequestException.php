<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\API;

use Psr\Http\Client\ClientExceptionInterface;

class GitlabRequestException extends \Exception
{
    /**
     * @var int
     */
    private $error_code;
    /**
     * @var string
     */
    private $gitlab_server_message;

    public function __construct(int $error_code, string $message, ?ClientExceptionInterface $previous = null)
    {
        parent::__construct("Error returned by the GitLab server: $message", $error_code, $previous);

        $this->gitlab_server_message = $message;

        $this->error_code = $error_code;
    }

    public function getErrorCode(): int
    {
        return $this->error_code;
    }

    public function getGitlabServerMessage(): string
    {
        return $this->gitlab_server_message;
    }
}
