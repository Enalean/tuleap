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

namespace Tuleap\REST;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;

final class AccessKeyHeaderExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&SplitTokenIdentifierTranslator
     */
    private $access_token_unserializer;

    protected function setUp(): void
    {
        $this->access_token_unserializer = $this->createMock(SplitTokenIdentifierTranslator::class);
    }

    public function testAccessKeyIsExtractedFromTheHeader(): void
    {
        $extractor = new AccessKeyHeaderExtractor($this->access_token_unserializer, ['HTTP_X_AUTH_ACCESSKEY' => 'valid']);

        $this->access_token_unserializer->method('getSplitToken')->willReturn($this->createMock(SplitToken::class));

        self::assertTrue($extractor->isAccessKeyHeaderPresent());
        self::assertNotNull($extractor->extractAccessKey());
    }

    public function testAccessKeyCannotBeFoundWhenTheHeaderIsNotPresent(): void
    {
        $extractor = new AccessKeyHeaderExtractor($this->access_token_unserializer, []);

        self::assertFalse($extractor->isAccessKeyHeaderPresent());
        self::assertNull($extractor->extractAccessKey());
    }

    public function testAccessKeyCannotBeExtractedWhenTheHeaderIsNotPresentButTheIdentifierIsNotValid(): void
    {
        $extractor = new AccessKeyHeaderExtractor($this->access_token_unserializer, ['HTTP_X_AUTH_ACCESSKEY' => 'not_valid']);

        $this->access_token_unserializer->method('getSplitToken')->willThrowException(
            $this->createMock(SplitTokenException::class),
        );

        self::assertTrue($extractor->isAccessKeyHeaderPresent());
        $this->expectException(SplitTokenException::class);
        $extractor->extractAccessKey();
    }
}
