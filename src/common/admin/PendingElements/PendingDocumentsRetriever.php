<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\admin\PendingElements;

use PFUser;
use Project;
use Tuleap\Event\Dispatchable;

class PendingDocumentsRetriever implements Dispatchable
{
    public const NAME = "pendingDocumentsRetriever";
    /**
     * @var Project
     * @psalm-readonly
     */
    private $project;
    /**
     * @var PFUser
     * @psalm-readonly
     */
    private $user;
    /**
     * @var \CSRFSynchronizerToken
     * @psalm-readonly
     */
    private $token;

    /**
     * @var array
     */
    private $html = [];

    public function __construct(Project $project, PFUser $user, \CSRFSynchronizerToken $token)
    {
        $this->project = $project;
        $this->user = $user;
        $this->token = $token;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getUser(): PFUser
    {
        return $this->user;
    }

    public function getToken(): \CSRFSynchronizerToken
    {
        return $this->token;
    }

    public function addPurifiedHTML(string $html): void
    {
        $this->html[] = $html;
    }

    /**
     * @return array
     */
    public function getHtml(): array
    {
        return $this->html;
    }
}
