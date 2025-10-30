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

namespace Tuleap\AICrossTracker\REST\v1;

use Tuleap\AI\Mistral\Message;
use Tuleap\AI\Mistral\Role;
use Tuleap\AI\Mistral\StringContent;

/**
 * @psalm-immutable
 */
final class MessageRepresentation
{
    /**
     * @var string Role of the message {@required true}
     */
    public string $role;
    /**
     * @var string Content of the message {@required true}
     */
    public string $content;

    public function toMistralMessage(): Message
    {
        return new Message(Role::from($this->role), new StringContent($this->content));
    }
}
