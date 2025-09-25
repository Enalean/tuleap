<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CodeBlockFeaturesOnArtifactTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CodeBlockFeaturesOnArtifact $code_block_features;

    #[\Override]
    protected function setUp(): void
    {
        $this->code_block_features = CodeBlockFeaturesOnArtifact::getInstance();
    }

    #[\Override]
    protected function tearDown(): void
    {
        CodeBlockFeaturesOnArtifact::clearInstance();
    }

    public function testItDoesNotNeedMermaidByDefault(): void
    {
        self::assertFalse($this->code_block_features->isMermaidNeeded());
    }

    public function testItNeedsMermaid(): void
    {
        self::assertFalse($this->code_block_features->isMermaidNeeded());
        $this->code_block_features->needsMermaid();
        self::assertTrue($this->code_block_features->isMermaidNeeded());
    }

    public function testItBehavesAsASingleton(): void
    {
        self::assertFalse($this->code_block_features->isMermaidNeeded());
        CodeBlockFeaturesOnArtifact::getInstance()->needsMermaid();
        self::assertTrue($this->code_block_features->isMermaidNeeded());
    }
}
