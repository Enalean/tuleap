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

namespace Tuleap\BotMattermost\Administration\Request;

use Tuleap\BotMattermost\Exception\ProvidedBotParameterIsNotValidException;

class ParameterValidator
{
    /**
     * @throws ProvidedBotParameterIsNotValidException
     */
    public function validateBotParameterFromRequest(string $bot_name, string $webhook_url, string $avatar_url): void
    {
        $valid_https_uri = new \Valid_HTTPSURI();

        if ($bot_name === '' || $webhook_url === '') {
            throw new ProvidedBotParameterIsNotValidException(
                dgettext('tuleap-botmattermost', 'The name and the webhook URL input must be filled')
            );
        }

        if (! $valid_https_uri->validate($webhook_url)) {
            throw new ProvidedBotParameterIsNotValidException(
                dgettext('tuleap-botmattermost', 'Invalid Webhook URL')
            );
        }

        if ($avatar_url !== '' && ! $valid_https_uri->validate($avatar_url)) {
            throw new ProvidedBotParameterIsNotValidException(
                dgettext('tuleap-botmattermost', 'Invalid Avatar URL')
            );
        }
    }
}
