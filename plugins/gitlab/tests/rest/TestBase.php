<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All rights reserved
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

namespace Tuleap\Gitlab\REST;

use RestBase;

class TestBase extends RestBase
{
    protected $gitlab_project_id;

    /**
     * @var int
     */
    protected $gitlab_repository_id;

    public function setUp(): void
    {
        parent::setUp();

        $this->gitlab_project_id    = $this->getProjectId(GitLabDataBuilder::PROJECT_TEST_GITLAB_SHORTNAME);
        $this->gitlab_repository_id = 1;
    }
}
