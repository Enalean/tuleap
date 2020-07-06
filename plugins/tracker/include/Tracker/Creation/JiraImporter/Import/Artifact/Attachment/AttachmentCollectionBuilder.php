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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment;

use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\IssueAPIRepresentation;

class AttachmentCollectionBuilder
{
    public function buildCollectionOfAttachment(IssueAPIRepresentation $issue_api_representation): AttachmentCollection
    {
        $attachments = [];
        if ($issue_api_representation->getFieldByKey(AlwaysThereFieldsExporter::JIRA_ATTACHMENT_NAME) !== null) {
            foreach ($issue_api_representation->getFieldByKey(AlwaysThereFieldsExporter::JIRA_ATTACHMENT_NAME) as $attachment_api_value) {
                $attachments[] = Attachment::buildFromIssueAPIResponse($attachment_api_value);
            }
        }

        return new AttachmentCollection($attachments);
    }
}
