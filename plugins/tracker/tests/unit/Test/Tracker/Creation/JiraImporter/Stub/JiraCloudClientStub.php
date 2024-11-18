<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Test\Tracker\Creation\JiraImporter\Stub;

use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\Attachment;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;

abstract class JiraCloudClientStub implements JiraClient
{
    public array $urls = [];

    public function isJiraCloud(): bool
    {
        return true;
    }

    public function isJiraServer9(): bool
    {
        return true;
    }

    public function getUrl(string $url): ?array
    {
        return $this->urls[$url] ?? null;
    }

    public function getAttachmentContents(Attachment $attachment): string
    {
        return '';
    }
}
