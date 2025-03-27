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

namespace Tuleap\Gitlab\Repository\Webhook\PostMergeRequest;

use Tuleap\Gitlab\Repository\Webhook\PostPush\Branch\BranchNameTuleapReferenceParser;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReferenceCollection;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReferencesParser;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TuleapReferencesFromMergeRequestDataExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItExtractsReferencesFromTitleConcatenatedToDescription(): void
    {
        $reference_parser             = $this->createMock(WebhookTuleapReferencesParser::class);
        $branch_name_reference_parser = $this->createMock(BranchNameTuleapReferenceParser::class);

        $collection = WebhookTuleapReferenceCollection::empty();
        $reference_parser
            ->expects($this->once())
            ->method('extractCollectionOfTuleapReferences')
            ->with('title description')
            ->willReturn($collection);
        $branch_name_reference_parser->method('extractTuleapReferenceFromBranchName')->willReturn(null);

        $extractor = new TuleapReferencesFromMergeRequestDataExtractor($reference_parser, $branch_name_reference_parser);

        self::assertEquals($collection, $extractor->extract('title', 'description', 'branch_source'));
    }
}
