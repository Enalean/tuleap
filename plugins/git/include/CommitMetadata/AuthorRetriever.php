<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Git\CommitMetadata;

use Tuleap\User\UserName;

final class AuthorRetriever implements RetrieveAuthor
{
    public function __construct(private \Git_Exec $git_exec, private \UserManager $user_manager)
    {
    }

    /**
     * @throws \Git_Command_Exception
     */
    #[\Override]
    public function getAuthor(string $sha1): UserName
    {
        $author_information = $this->git_exec->getAuthorInformation($sha1);
        $tuleap_user        = $this->user_manager->getUserByEmail($author_information['email']);

        if (! $tuleap_user) {
            return UserName::fromUsername($author_information['name']);
        }
        return UserName::fromUser($tuleap_user);
    }
}
