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

namespace Tuleap\AICrossTracker\Assistant;

use Luracast\Restler\RestException;
use Override;
use Tuleap\AI\Mistral\ChunkContent;
use Tuleap\AI\Mistral\Completion;
use Tuleap\AI\Mistral\Message;
use Tuleap\AI\Mistral\Model;
use Tuleap\AI\Mistral\Role;
use Tuleap\AI\Mistral\TextChunk;

final class UserAssistant implements Assistant
{
    /**
     * @param Message[] $messages
     * @throws RestException
     */
    #[Override]
    public function getCompletion(\PFUser $user, array $messages): Completion
    {
        $tql_doc = file_get_contents(__DIR__ . '/tql.html');
        if ($tql_doc === false) {
            throw new RestException(500, 'TQL doc error');
        }

        return new Completion(
            Model::DEVSTRALL_2512,
            AssistantResponseFormatBuilder::buildFormat(),
            new Message(
                Role::SYSTEM,
                new ChunkContent(
                    new TextChunk(
                        <<<EOT
                            You are an assistant that helps to generate TQL queries for users. TQL is a pseudo programming
                            language, described in section "TQL documentation" below.

                            You do not provide assistance for anything that does not aim to produce a TQL query. Users
                            request information using only plaintext.
                            EOT
                    ),
                    new TextChunk('### TQL documentation' . PHP_EOL . $tql_doc),
                    new TextChunk(
                        <<<EOT
                            The requester is in the context of a personal project, queries will usually have `FROM @project = MY_PROJECTS()`
                            to refer to the projects the user is member of.
                            EOT
                    ),
                ),
            ),
            ... $messages
        );
    }
}
