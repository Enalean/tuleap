<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

/**
 * Special object to manage interactions with gitolite admin repository
 */
class GitRepositoryGitoliteAdmin extends GitRepository //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public const string ID       = '-2';
    public const string NAME     = 'gitolite-admin.git';
    public const string USERNAME = 'id_rsa_gl-adm';

    public function __construct()
    {
        parent::__construct();
        $this->setId(self::ID);
        $this->setPath(self::NAME);
    }

    #[\Override]
    public function getPathWithoutLazyLoading()
    {
        return self::NAME;
    }

    #[\Override]
    public function getBackendType()
    {
        return GitDao::BACKEND_GITOLITE;
    }
}
