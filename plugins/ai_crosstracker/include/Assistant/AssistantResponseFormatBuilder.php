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
 */

declare(strict_types=1);

namespace Tuleap\AICrossTracker\Assistant;

use Tuleap\AI\Mistral\CompletionJSONSchema;
use Tuleap\AI\Mistral\CompletionJSONSchemaSchema;
use Tuleap\AI\Mistral\CompletionJSONSchemaSchemaProperty;
use Tuleap\AI\Mistral\CompletionResponseJSONFormat;

final readonly class AssistantResponseFormatBuilder
{
    private function __construct()
    {
    }

    public static function buildFormat(): CompletionResponseJSONFormat
    {
        return new CompletionResponseJSONFormat(
            new CompletionJSONSchema(
                'tql_query',
                new CompletionJSONSchemaSchema(
                    'TQL Query',
                    ['title', 'tql_query', 'explanations'],
                    [
                        'title' => new CompletionJSONSchemaSchemaProperty(
                            'string',
                            'Short description of the query in plaintext'
                        ),
                        'tql_query' => new CompletionJSONSchemaSchemaProperty(
                            'string',
                            'TQL query answering the user request. Split on multiple lines to make it human readable.'
                        ),
                        'explanations' => new CompletionJSONSchemaSchemaProperty(
                            'string',
                            'Additional explanations, use markdown to format the message'
                        ),
                    ]
                )
            )
        );
    }
}
