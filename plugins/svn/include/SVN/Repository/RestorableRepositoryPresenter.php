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

namespace Tuleap\SVN\Repository;

use Tuleap\Date\DateHelper;

/**
 * @psalm-immutable
 */
class RestorableRepositoryPresenter
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $purified_deletion_date;
    /**
     * @var int
     */
    public $project_id;
    /**
     * @var int
     */
    public $repository_id;

    public function __construct(\PFUser $user, string $name, int $deletion_date, int $project_id, int $repository_id)
    {
        $this->name                   = $name;
        $this->purified_deletion_date = DateHelper::relativeDateInlineContext($deletion_date, $user);
        $this->project_id             = $project_id;
        $this->repository_id          = $repository_id;
    }
}
