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

declare(strict_types=1);

namespace Tuleap\Docman\Test\rest;

use Tuleap\REST\BaseTestDataBuilder;
use Tuleap\REST\RestBase;

class DocmanBase extends RestBase
{
    public const string PROJECT_NAME                    = 'docmanproject';
    public const string DOCMAN_USER_NAME                = 'docman_regular_user';
    private const string REQUIREMENTS_TRACKER_SHORTNAME = 'requirements';

    protected int $project_id;
    protected int $docman_user_id;
    protected int $test_user_1_id;
    protected int $requirements_tracker_id;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->project_id = $this->getProjectId(self::PROJECT_NAME);

        $this->initUserId(self::DOCMAN_USER_NAME);

        $this->docman_user_id          = $this->user_ids[self::DOCMAN_USER_NAME];
        $this->test_user_1_id          = $this->user_ids[BaseTestDataBuilder::TEST_USER_1_NAME];
        $this->requirements_tracker_id = $this->tracker_ids[$this->project_id][self::REQUIREMENTS_TRACKER_SHORTNAME];
    }
}
