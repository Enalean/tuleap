<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\SVN\Admin;

use Tuleap\SVNCore\Repository;

class MailHeaderManager
{
    private $dao;

    public function __construct(MailHeaderDao $dao)
    {
        $this->dao = $dao;
    }

    public function create(MailHeader $mail_header)
    {
        if (! $this->dao->create($mail_header)) {
            throw new CannotCreateMailHeaderException(dgettext('tuleap-svn', 'Unable to update Repository data'));
        }
    }

    public function getByRepository(Repository $repository)
    {
        $row = $this->dao->searchByRepositoryId($repository->getId());
        if (! $row) {
            return new MailHeader(
                $repository,
                ""
            );
        }
        return $this->instantiateFromRow($row, $repository);
    }

    private function instantiateFromRow(array $row, Repository $repository)
    {
        return new MailHeader(
            $repository,
            $row['header']
        );
    }
}
