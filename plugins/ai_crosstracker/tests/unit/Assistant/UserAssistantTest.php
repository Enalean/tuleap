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

use Tuleap\AI\Mistral\Message;
use Tuleap\AI\Mistral\Role;
use Tuleap\AI\Mistral\StringContent;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserAssistantTest extends TestCase
{
    public function testOneMessageFromUserIsPrePromptedBySystem(): void
    {
        $assistant    = new UserAssistant();
        $completion   = $assistant->getCompletion(
            UserTestBuilder::anActiveUser()->build(),
            [new Message(Role::USER, new StringContent('foo'))]
        );
        $json_encoded = json_encode($completion->jsonSerialize());
        self::assertNotFalse($json_encoded);
        $json = json_decode($json_encoded, true);
        self::assertCount(2, $json['messages'][0]);
        self::assertEquals(Role::SYSTEM->value, $json['messages'][0]['role']);
        self::assertEquals(Role::USER->value, $json['messages'][1]['role']);
        self::assertEquals('foo', $json['messages'][1]['content']);
    }
}
