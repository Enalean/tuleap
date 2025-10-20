<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog;

use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserRetriever;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;

class ListFieldChangeInitialValueRetriever
{
    /**
     * @var CreationStateListValueFormatter
     */
    private $creation_state_list_value_formatter;

    /**
     * @var JiraUserRetriever
     */
    private $jira_user_retriever;

    public function __construct(
        CreationStateListValueFormatter $creation_state_list_value_formatter,
        JiraUserRetriever $jira_user_retriever,
    ) {
        $this->creation_state_list_value_formatter = $creation_state_list_value_formatter;
        $this->jira_user_retriever                 = $jira_user_retriever;
    }

    /**
     * @return mixed
     * @throws JiraConnectionException
     */
    public function retrieveBoundValue(
        string $changed_field_from,
        FieldMapping $field_mapping,
    ) {
        if ($field_mapping->getBindType() === \Tuleap\Tracker\FormElement\Field\List\Bind\Static\ListFieldStaticBind::TYPE) {
            return $this->creation_state_list_value_formatter->formatListValue(
                $changed_field_from,
            );
        }

        if ($field_mapping->getType() === \Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE) {
            $user = $this->jira_user_retriever->getAssignedTuleapUser($changed_field_from);
            return $this->creation_state_list_value_formatter->formatListValue(
                (string) $user->getId(),
            );
        }

        if (strpos($changed_field_from, '[') === 0) {
            $account_ids = explode(',', substr($changed_field_from, 1, -1));
        } else {
            $account_ids = explode(',', $changed_field_from);
        }
        $selected_users_ids = [];

        foreach ($account_ids as $account_id) {
            $user = $this->jira_user_retriever->getAssignedTuleapUser(
                trim($account_id)
            );

            if ((int) $user->getId() === (int) TrackerImporterUser::ID) {
                continue;
            }

            $selected_users_ids[] = $user->getId();
        }

        return $this->creation_state_list_value_formatter->formatMultiUserListValues($selected_users_ids);
    }
}
