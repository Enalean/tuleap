<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\SVN;

use Tuleap\Event\Dispatchable;
use Tuleap\Layout\BaseLayout;

final class SvnCoreAccess implements Dispatchable
{
    public const NAME = 'svnCoreAccess';

    /**
     * @var \Project
     * @psalm-readonly
     */
    public $project;
    /**
     * @var string
     * @psalm-readonly
     */
    public $requested_uri;
    /**
     * @var ?BaseLayout
     */
    private $response;
    /**
     * @var string
     */
    private $redirect_uri;

    public function __construct(\Project $project, string $requested_uri, ?BaseLayout $response)
    {
        $this->project       = $project;
        $this->requested_uri = $requested_uri;
        $this->response      = $response;
    }

    public function setRedirectUri(string $uri): void
    {
        $this->redirect_uri = $uri;
    }

    public function redirect(): void
    {
        if ($this->response && $this->redirect_uri) {
            $this->response->redirect($this->redirect_uri);
        }
    }

    public function hasRedirectUri(): bool
    {
        return $this->redirect_uri !== null;
    }
}
