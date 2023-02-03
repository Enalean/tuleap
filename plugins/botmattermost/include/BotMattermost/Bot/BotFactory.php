<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\BotMattermost\Bot;

use Tuleap\BotMattermost\Exception\BotAlreadyExistException;
use Tuleap\BotMattermost\Exception\BotNotFoundException;
use Tuleap\BotMattermost\Exception\CannotCreateBotException;
use Tuleap\BotMattermost\Exception\CannotDeleteBotException;
use Tuleap\BotMattermost\Exception\CannotUpdateBotException;
use Tuleap\BotMattermost\Exception\EmptyUpdateException;

class BotFactory
{
    private $dao;

    public function __construct(BotDao $bot_dao)
    {
        $this->dao = $bot_dao;
    }

    /**
     * @throws CannotCreateBotException
     * @throws BotAlreadyExistException
     */
    public function save(
        string $bot_name,
        string $bot_webhook_url,
        string $bot_avatar_url,
        ?int $project_id,
    ): void {
        if (! $this->isABotWithNameWebhookUrlAndProjectIdAlreadyExisting($bot_name, $bot_webhook_url, $project_id)) {
            $id = $this->dao->addBot(
                trim($bot_name),
                trim($bot_webhook_url),
                trim($bot_avatar_url),
                $project_id
            );
            if (! $id) {
                throw new CannotCreateBotException();
            }
        } else {
            throw new BotAlreadyExistException();
        }
    }

    /**
     * @throws CannotUpdateBotException | EmptyUpdateException | BotNotFoundException|BotAlreadyExistException
     */
    public function update(
        string $bot_name,
        string $bot_webhook_url,
        string $bot_avatar_url,
        int $bot_id,
    ): void {
        $original_bot        = $this->getBotById($bot_id);
        $trimmed_name        = trim($bot_name);
        $trimmed_webhook_url = trim($bot_webhook_url);
        $trimmed_avatar_url  = trim($bot_avatar_url);

        if (
            ! $this->updateContainsChanges(
                $original_bot,
                $trimmed_name,
                $trimmed_webhook_url,
                $trimmed_avatar_url
            )
        ) {
            throw new EmptyUpdateException();
        }

        if (
            $this->isThereAnotherBotWithNameWebhookUrlAndProjectIdAlreadyExisting(
                $bot_id,
                $trimmed_name,
                $trimmed_webhook_url,
                $original_bot->getProjectId()
            )
        ) {
            throw new BotAlreadyExistException();
        }

        if (
            ! $this->dao->updateBot(
                $trimmed_name,
                $trimmed_webhook_url,
                $trimmed_avatar_url,
                $bot_id
            )
        ) {
            throw new CannotUpdateBotException();
        }
    }

    private function updateContainsChanges(
        Bot $original_bot,
        string $name,
        string $webhook_url,
        string $avatar_url,
    ): bool {
        return $original_bot->getName() !== $name ||
            $original_bot->getWebhookUrl() !== $webhook_url ||
            $original_bot->getAvatarUrl() !== $avatar_url;
    }

    public function deleteBotById(int $id): void
    {
        if (! $this->dao->deleteBot($id)) {
            throw new CannotDeleteBotException();
        }
    }

    /**
     * @return Bot[]
     */
    public function getSystemBots(): array
    {
        $dar = $this->dao->searchSystemBots();
        if ($dar === false) {
            throw new BotNotFoundException();
        }
        return $this->buildBotsFromDAR($dar);
    }

    /**
     * @return Bot[]
     */
    public function getProjectBots(int $project_id): array
    {
        $dar = $this->dao->searchProjectBots($project_id);
        if ($dar === false) {
            throw new BotNotFoundException();
        }
        return $this->buildBotsFromDAR($dar);
    }

    private function isABotWithNameWebhookUrlAndProjectIdAlreadyExisting(string $name, string $webhook_url, ?int $project_id): bool
    {
        if ($project_id === null) {
            return $this->dao->isASystemBotWithNameAndWebhookUrlAlreadyExisting($name, $webhook_url);
        }

        return $this->dao->isAProjectBotWithNameWebhookUrlAndProjectIdAlreadyExisting($name, $webhook_url, $project_id);
    }

    private function isThereAnotherBotWithNameWebhookUrlAndProjectIdAlreadyExisting(int $bot_id, string $name, string $webhook_url, ?int $project_id): bool
    {
        if ($project_id === null) {
            return $this->dao->isThereAnotherSystemBotWithNameAndWebhookUrl($bot_id, $name, $webhook_url);
        }

        return $this->dao->isThereAnotherProjectBotWithNameWebhookUrlAndProjectId($bot_id, $name, $webhook_url, $project_id);
    }

    public function getBotById($bot_id): Bot
    {
        $row = $this->dao->searchBotById($bot_id);
        if ($row === null || $row === false) {
            throw new BotNotFoundException();
        }

        return new Bot(
            $row['id'],
            $row['name'],
            $row['webhook_url'],
            $row['avatar_url'],
            $row['project_id']
        );
    }

    /**
     * @return Bot[]
     */
    private function buildBotsFromDAR(array $dar): array
    {
        $bots = [];
        foreach ($dar as $row) {
            $bots[] = new Bot(
                $row['id'],
                $row['name'],
                $row['webhook_url'],
                $row['avatar_url'],
                $row['project_id']
            );
        }

        return $bots;
    }
}
