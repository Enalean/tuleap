<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\OAuth2ServerCore\AuthorizationServer;


#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PromptParameterValuesExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var PromptParameterValuesExtractor
     */
    private $extractor;

    protected function setUp(): void
    {
        $this->extractor = new PromptParameterValuesExtractor();
    }

    public function testExtractsPromptValues(): void
    {
        $this->assertEquals(
            ['login', 'consent'],
            $this->extractor->extractPromptValues('login consent')
        );
    }

    public function testExtractDuplicatesOnlyOnce(): void
    {
        $this->assertEquals(
            ['none'],
            $this->extractor->extractPromptValues('none none')
        );
    }

    public function testDoesNotExtractUnknownValues(): void
    {
        $this->assertEquals(
            [],
            $this->extractor->extractPromptValues('unknown_A unknown_b')
        );
    }

    public function testNoneCannotBeUsedWithOtherValues(): void
    {
        $this->expectException(PromptNoneParameterCannotBeMixedWithOtherPromptParametersException::class);
        $this->extractor->extractPromptValues('none login');
    }
}
