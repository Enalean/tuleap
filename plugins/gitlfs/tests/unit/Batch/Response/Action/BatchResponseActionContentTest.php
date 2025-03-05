<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\GitLFS\Batch\Response\Action;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Cryptography\ConcealedString;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BatchResponseActionContentTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testActionContentHasAnAuthenticationHeader(): void
    {
        $action_href            = new class implements BatchResponseActionHref {
            public function getHref(): string
            {
                return 'https://example.com/action';
            }
        };
        $authorization_token    = new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString());
        $token_header_formatter = new class implements SplitTokenFormatter {
            public function getIdentifier(SplitToken $token): ConcealedString
            {
                return new ConcealedString('identifier');
            }
        };
        $action_content         = new BatchResponseActionContent(
            $action_href,
            $authorization_token,
            $token_header_formatter,
            10000
        );

        $action_content_serialized = json_decode(json_encode($action_content));
        $this->assertTrue(isset($action_content_serialized->header->Authorization));
    }
}
