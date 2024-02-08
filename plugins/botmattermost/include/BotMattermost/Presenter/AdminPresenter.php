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

namespace Tuleap\BotMattermost\Presenter;

use CSRFSynchronizerToken;
use Tuleap\BotMattermost\Bot\Bot;

class AdminPresenter
{
    public CSRFSynchronizerToken $csrf_token;
    /**
     * @var BotPresenter[]
     */
    public array $bots;
    public bool $has_bots;
    public string $create_bot_url;

    /**
     * @param Bot[] $bots
     */
    public function __construct(CSRFSynchronizerToken $csrf_token, array $bots)
    {
        $this->csrf_token = $csrf_token;
        $this->bots       = $this->buildBotPresenterCollection($bots);

        $this->has_bots = count($this->bots) > 0;

        $this->create_bot_url = '?action=add_bot';
    }

    /**
     * @param Bot[] $bots
     *
     * @retrun BotPresenter[]
     */
    private function buildBotPresenterCollection(array $bots): array
    {
        $presenters = [];
        foreach ($bots as $bot) {
            $presenters[] = BotPresenter::buildFromBot($bot);
        }

        return $presenters;
    }
}
