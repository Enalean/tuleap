<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook\PostPush\Branch;

use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class BranchNameTuleapReferenceParserTest extends TestCase
{
    private BranchNameTuleapReferenceParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new BranchNameTuleapReferenceParser();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('branchNamesProvider')]
    public function testItRetrievesTheReferenceInTheBranchName(string $branch_name, bool $found): void
    {
        $reference = $this->parser->extractTuleapReferenceFromBranchName($branch_name);
        if ($found) {
            self::assertInstanceOf(WebhookTuleapReference::class, $reference);
            self::assertSame(2447, $reference->getId());
        } else {
            self::assertNull($reference);
        }
    }

    public static function branchNamesProvider(): array
    {
        return [
            ['dev', false],
            ['dev_TULEAP', false],
            ['dev_TULEAP-2447', true],
            ['dev_TULEAP-2447_v2', true],
            ['dev_TULEAP-2447/other', true],
            ['dev_TULEAP-2447TULEAP-123', true],
            ['dev_TULEAP-2447_TULEAP-123', true],
            ['dev/TULEAP-2447/TULEAP-123', true],
            ['dev/TULEAP-2447', true],
            ['dev/tuleap-2447', true],
        ];
    }
}
