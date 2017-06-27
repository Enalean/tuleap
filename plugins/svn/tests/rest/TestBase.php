<?php
/**
 * Copyright (c) Enalean, 2017. All rights reserved
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

namespace Tuleap\SVN\REST;

use RestBase;

/**
 * @group SVNTests
 */
class TestBase extends RestBase
{
    const PROJECT_NAME = 'svn-plugin-test';

    /**
     * @var int
     */
    protected $svn_project_id;

    public function setUp()
    {
        parent::setUp();

        if (! $this->svn_project_id) {
            $this->svn_project_id = $this->getProjectId(self::PROJECT_NAME);
        }
    }
}
