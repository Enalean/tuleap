<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\BotMattermostAgileDashboard\BotMattermostStandUpSummary;

use Tuleap\BotMattermost\Bot\Bot;
use Tuleap\BotMattermost\Bot\BotValidityChecker;
use Tuleap\BotMattermost\Exception\BotCannotBeUsedInProjectException;
use Tuleap\BotMattermostAgileDashboard\Exception\CannotCreateBotNotificationException;

class NotificationCreator
{
    public function __construct(private Dao $dao, private BotValidityChecker $bot_validity_checker)
    {
    }

    /**
     * @param string[] $channels
     * @throws CannotCreateBotNotificationException
     * @throws BotCannotBeUsedInProjectException
     */
    public function createNotification(Bot $bot, int $project_id, array $channels, string $send_time): void
    {
        $this->bot_validity_checker->checkBotCanBeUsedInProject(
            $bot,
            $project_id
        );

        if (! $this->dao->createNotification($channels, $bot->getId(), $project_id, $send_time)) {
            throw new CannotCreateBotNotificationException();
        }
    }
}
