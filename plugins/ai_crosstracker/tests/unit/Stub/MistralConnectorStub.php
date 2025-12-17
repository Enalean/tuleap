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

declare(strict_types=1);

namespace Tuleap\AICrossTracker\Stub;

use Override;
use Tuleap\AI\Mistral\Completion;
use Tuleap\AI\Mistral\CompletionResponse;
use Tuleap\AI\Mistral\MistralConnector;
use Tuleap\AI\Requestor\AIRequestorEntity;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class MistralConnectorStub implements MistralConnector
{
    private CompletionResponse $response;
    private(set) Completion $query;

    public function withResponse(CompletionResponse $response): self
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    #[Override]
    public function testConnection(): Ok|Err
    {
        return Result::ok(null);
    }

    /**
     * @return Ok<CompletionResponse>|Err<Fault>
     */
    #[Override]
    public function sendCompletion(AIRequestorEntity $requestor, Completion $completion, string $service): Ok|Err
    {
        $this->query = $completion;
        return Result::ok($this->response);
    }
}
