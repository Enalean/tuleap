<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Reference;

use Tuleap\Test\Builders\ReferenceBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ReferenceInstanceTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ARTIFACT_ID = 49;
    private const PROJECT_ID  = 164;

    public function testItBuildsFromComponents(): void
    {
        $reference    = ReferenceBuilder::anArtReference()->build();
        $match        = 'art #' . self::ARTIFACT_ID;
        $context_word = 'closes';
        $instance     = new ReferenceInstance(
            $match,
            $reference,
            (string) self::ARTIFACT_ID,
            'art',
            self::PROJECT_ID,
            $context_word
        );

        self::assertSame($match, $instance->getMatch());
        self::assertSame($reference, $instance->getReference());
        self::assertSame((string) self::ARTIFACT_ID, $instance->getValue());
        self::assertSame($context_word, $instance->getContextWord());
        self::assertStringContainsString('/goto', $instance->getFullGotoLink());
    }
}
