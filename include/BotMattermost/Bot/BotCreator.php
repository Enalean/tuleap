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

use Tuleap\BotMattermost\Administration\Request\ParameterValidator;
use Tuleap\BotMattermost\Exception\BotAlreadyExistException;
use Tuleap\BotMattermost\Exception\CannotCreateBotException;
use Tuleap\BotMattermost\Exception\ProvidedBotParameterIsNotValidException;

class BotCreator
{
    public function __construct(private BotFactory $bot_factory, private ParameterValidator $parameter_validator)
    {
    }

    /**
     * @throws ProvidedBotParameterIsNotValidException
     * @throws CannotCreateBotException
     * @throws BotAlreadyExistException
     */
    public function createProjectBot(string $bot_name, string $webhook_url, string $avatar_url, int $project_id): void
    {
        $this->createBot(
            $bot_name,
            $webhook_url,
            $avatar_url,
            $project_id
        );
    }

    /**
     * @throws ProvidedBotParameterIsNotValidException
     * @throws CannotCreateBotException
     * @throws BotAlreadyExistException
     */
    public function createSystemBot(string $bot_name, string $webhook_url, string $avatar_url): void
    {
        $this->createBot(
            $bot_name,
            $webhook_url,
            $avatar_url,
            null
        );
    }

    /**
     * @throws ProvidedBotParameterIsNotValidException
     * @throws CannotCreateBotException
     * @throws BotAlreadyExistException
     */
    private function createBot(string $bot_name, string $webhook_url, string $avatar_url, ?int $project_id): void
    {
        $this->parameter_validator->validateBotParameterFromRequest(
            $bot_name,
            $webhook_url,
            $avatar_url
        );

        $this->bot_factory->save(
            $bot_name,
            $webhook_url,
            $avatar_url,
            $project_id
        );
    }
}
