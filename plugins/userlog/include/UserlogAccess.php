<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

declare(strict_types=1);

namespace Tuleap\Userlog;

use DateTimeImmutable;
use HTTPRequest;
use PFUser;
use Project;

final class UserlogAccess
{
    /**
     * @var DateTimeImmutable
     */
    private $date;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var PFUser
     */
    private $user;

    /**
     * @var string
     */
    private $user_agent;

    /**
     * @var string
     */
    private $request_method;

    /**
     * @var string
     */
    private $request_uri;

    /**
     * @var string
     */
    private $ip_address;

    /**
     * @var string
     */
    private $http_referer;

    private function __construct(
        DateTimeImmutable $date,
        Project $project,
        PFUser $user,
        string $user_agent,
        string $request_method,
        string $request_uri,
        string $ip_address,
        string $http_referer
    ) {
        $this->date           = $date;
        $this->project        = $project;
        $this->user           = $user;
        $this->user_agent     = $user_agent;
        $this->request_method = $request_method;
        $this->request_uri    = $request_uri;
        $this->ip_address     = $ip_address;
        $this->http_referer   = $http_referer;
    }

    public static function buildFromRequest(HTTPRequest $request): self
    {
        return new self(
            new DateTimeImmutable(),
            $request->getProject(),
            $request->getCurrentUser(),
            $request->getFromServer('HTTP_USER_AGENT') ?: '',
            $request->getFromServer('REQUEST_METHOD') ?: '',
            $request->getFromServer('REQUEST_URI') ?: '',
            $request->getIPAddress(),
            $request->getFromServer('HTTP_REFERER') ?: ''
        );
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getUser(): PFUser
    {
        return $this->user;
    }

    public function getUserAgent(): string
    {
        return $this->user_agent;
    }

    public function getRequestMethod(): string
    {
        return $this->request_method;
    }

    public function getRequestUri(): string
    {
        return $this->request_uri;
    }

    public function getIpAddress(): string
    {
        return $this->ip_address;
    }

    public function getHttpReferer(): string
    {
        return $this->http_referer;
    }

    public function hasProjectIdDefined(): bool
    {
        return $this->project->getID() !== 0 && $this->project->getID() !== null;
    }

    public function setProject(Project $project): void
    {
        $this->project = $project;
    }
}
