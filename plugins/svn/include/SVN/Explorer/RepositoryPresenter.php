<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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

namespace Tuleap\SVN\Explorer;

use DateHelper;
use Tuleap\SVN\Repository\Repository;

class RepositoryPresenter
{
    /**
     * @var Repository
     * @psalm-readonly
     */
    public $repository;
    /**
     * @var string
     * @psalm-readonly
     */
    public $purified_commit_date;
    /**
     * @var int
     * @psalm-readonly
     */
    private $commit_date;
    /**
     * @var \PFUser
     * @psalm-readonly
     */
    private $user;

    public function __construct(Repository $repository, int $commit_date, \PFUser $user)
    {
        $this->repository           = $repository;
        $this->commit_date          = $commit_date;
        $this->user                 = $user;
        $this->purified_commit_date = (! $this->commit_date) ? '-' : DateHelper::relativeDateInlineContext(
            $this->commit_date,
            $this->user
        );
    }
}
