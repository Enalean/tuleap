<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\SVN\Explorer;

use Codendi_HTMLPurifier;
use DateHelper;
use Tuleap\SVN\Repository\Repository;

class RepositoryPresenter
{
    /**
     * @var Repository
     */
    public $repository;
    private $commit_date;

    public function __construct(Repository $repository, $commit_date)
    {
        $this->repository  = $repository;
        $this->commit_date = $commit_date;
    }

    public function getPurifiedHumanReadableCommitDate()
    {
        $purifier = Codendi_HTMLPurifier::instance();

        return $purifier->purify(
            DateHelper::timeAgoInWords(
                $this->commit_date,
                false,
                true
            ),
            CODENDI_PURIFIER_STRIP_HTML
        );
    }
}
