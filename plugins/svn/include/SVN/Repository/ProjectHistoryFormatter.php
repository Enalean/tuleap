<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\SVN\Repository;

use Tuleap\SVN\Admin\ImmutableTag;
use Tuleap\SVN\Admin\MailNotification;
use Tuleap\SVNCore\Repository;

class ProjectHistoryFormatter
{
    private $messages = [];

    public function getFullHistory(Repository $repository)
    {
        return "Repository: " . $repository->getName() .
            PHP_EOL .
            implode(PHP_EOL, $this->messages);
    }

    private function extractHookReadableValue($value, $index)
    {
        if (isset($value[$index])) {
            /**
             * See https://github.com/vimeo/psalm/issues/4669
             * @psalm-taint-escape html
             */
            $value_to_read = $value[$index];
            return var_export($value_to_read, true);
        }

        return '-';
    }

    public function getHookConfigHistory(array $hook_config)
    {
        return HookConfig::MANDATORY_REFERENCE . ": " .
            $this->extractHookReadableValue($hook_config, HookConfig::MANDATORY_REFERENCE) .
            PHP_EOL .
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE . ": " .
            $this->extractHookReadableValue($hook_config, HookConfig::COMMIT_MESSAGE_CAN_CHANGE);
    }

    public function addCommitRuleHistory(array $hook_config)
    {
        $this->messages[] = $this->getHookConfigHistory($hook_config);
    }

    public function addImmutableTagHistory(ImmutableTag $immutable_tag)
    {
        $this->messages[] = $this->getImmutableTagsHistory($immutable_tag);
    }

    public function getImmutableTagsHistory(ImmutableTag $immutable_tag)
    {
         return "Path:" . PHP_EOL .
            $immutable_tag->getPathsAsString() . PHP_EOL .
            "Whitelist:" . PHP_EOL .
            $immutable_tag->getWhitelistAsString();
    }

    public function addAccessFileContentHistory($access_file)
    {
        $this->messages[] = $this->getAccessFileHistory($access_file);
    }

    public function getAccessFileHistory($access_file)
    {
        return "Access file:" . PHP_EOL . $access_file;
    }

    /**
     * @param MailNotification[] $mail_notifications
     *
     * @return string
     */
    public function addNotificationHistory(array $mail_notifications)
    {
        $message = '';
        foreach ($mail_notifications as $mail_notification) {
            $message .= "Path: " . $mail_notification->getPath() . PHP_EOL;
            $message .= "Emails: " . $mail_notification->getNotifiedMailsAsString() . PHP_EOL;
            $message .= "Users: " . $mail_notification->getNotifiedUsersAsString() . PHP_EOL;
            $message .= "User Groups: " . $mail_notification->getNotifiedUserGroupsAsString() . PHP_EOL;
        }

        $this->messages[] = "Notifications:" . PHP_EOL . $message;
    }
}
