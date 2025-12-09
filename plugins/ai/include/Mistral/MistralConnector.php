<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\AI\Mistral;

use Tuleap\AI\Requestor\AIRequestorEntity;
use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeySecret;
use Tuleap\Config\ConfigKeyString;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;

#[ConfigKeyCategory('AI')]
interface MistralConnector
{
    #[ConfigKey('Mistral API Key')]
    #[ConfigKeyString('')]
    #[ConfigKeySecret]
    final public const string CONFIG_API_KEY = 'mistral_api_key';

    /**
     * @return Ok<null>|Err<Fault>
     */
    public function testConnection(): Ok|Err;

    /**
     * @return Ok<CompletionResponse>|Err<Fault>
     */
    public function sendCompletion(AIRequestorEntity $requestor, Completion $completion, string $service): Ok|Err;
}
