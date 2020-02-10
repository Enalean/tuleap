<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 *
 */

namespace Tuleap\Docman\Test\rest;

use RestBase;
use TestDataBuilder;

class DocmanBase extends RestBase
{
    public const PROJECT_NAME     = 'docmanproject';
    public const DOCMAN_USER_NAME = 'docman_regular_user';

    protected $project_id;
    protected $docman_user_id;
    protected $test_user_1_id;

    public function setUp() : void
    {
        parent::setUp();
        $this->project_id = $this->getProjectId(self::PROJECT_NAME);

        $this->initUserId(self::DOCMAN_USER_NAME);

        $this->docman_user_id = $this->user_ids[self::DOCMAN_USER_NAME];
        $this->test_user_1_id = $this->user_ids[TestDataBuilder::TEST_USER_1_NAME];
    }
}
