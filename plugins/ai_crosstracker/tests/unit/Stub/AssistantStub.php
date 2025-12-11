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
use Tuleap\AI\Mistral\ChunkContent;
use Tuleap\AI\Mistral\Completion;
use Tuleap\AI\Mistral\Message;
use Tuleap\AI\Mistral\Model;
use Tuleap\AI\Mistral\Role;
use Tuleap\AI\Mistral\TextChunk;
use Tuleap\AICrossTracker\Assistant\Assistant;
use Tuleap\AICrossTracker\Assistant\AssistantResponseFormatBuilder;

final class AssistantStub implements Assistant
{
    #[Override]
    public function getCompletion(\PFUser $user, array $messages): Completion
    {
        return new Completion(
            Model::DEVSTRALL_2512,
            AssistantResponseFormatBuilder::buildFormat(),
            new Message(
                Role::SYSTEM,
                new ChunkContent(
                    new TextChunk('AssistantStub'),
                ),
            ),
            ... $messages
        );
    }
}
