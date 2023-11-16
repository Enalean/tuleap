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

use Tuleap\Date\DateHelper;
use Tuleap\SVN\Repository\CoreRepository;
use Tuleap\SVN\Repository\RepositoryWithLastCommitDate;

class RepositoryPresenter
{
    /**
     * @var RepositoryWithLastCommitDate
     * @psalm-readonly
     */
    public $repository;
    /**
     * @var string
     * @psalm-readonly
     */
    public $purified_commit_date = '-';
    /**
     * @var int
     * @psalm-readonly
     */
    public $commit_date = 0;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $is_core = false;

    public function __construct(RepositoryWithLastCommitDate $repository, \PFUser $user)
    {
        $this->repository = $repository;
        if ($repository->hasCommitActivity()) {
            $this->commit_date          = $repository->getLastCommitDate()->getTimestamp();
            $this->purified_commit_date = DateHelper::relativeDateInlineContext(
                $repository->getLastCommitDate()->getTimestamp(),
                $user
            );
        }
        $this->is_core = $repository->repository instanceof CoreRepository;
    }
}
