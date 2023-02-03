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
 */

declare(strict_types=1);

namespace Tuleap\BotMattermost\Bot;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\BotMattermost\BotMattermostDeleted;
use Tuleap\DB\DBTransactionExecutor;

class BotDeletor
{
    public function __construct(
        private BotFactory $bot_factory,
        private EventDispatcherInterface $event_manager,
        private DBTransactionExecutor $db_transaction_executor,
    ) {
    }

    /**
     * @throws \Tuleap\BotMattermost\Exception\CannotDeleteBotException
     */
    public function deleteBot(Bot $bot): void
    {
        $this->db_transaction_executor->execute(function () use ($bot): void {
            $this->bot_factory->deleteBotById($bot->getId());

            $event = new BotMattermostDeleted($bot);
            $this->event_manager->dispatch($event);
        });
    }
}
