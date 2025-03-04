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

namespace Tuleap\Gitlab\Repository\Webhook;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WebhookTuleapReferenceCollectionTest extends TestCase
{
    public function testCreatesCollectionFromSetOfReferences(): void
    {
        $ref_1 = new WebhookTuleapReference(1, null);
        $ref_2 = new WebhookTuleapReference(2, null);

        $collection = WebhookTuleapReferenceCollection::fromReferences($ref_1, $ref_2);

        self::assertEqualsCanonicalizing([$ref_1, $ref_2], $collection->getTuleapReferences());
    }

    public function testAggregatesCollection(): void
    {
        $ref_1 = new WebhookTuleapReference(1, null);
        $ref_2 = new WebhookTuleapReference(2, null);

        $collection = WebhookTuleapReferenceCollection::aggregateCollections(
            WebhookTuleapReferenceCollection::fromReferences($ref_1),
            WebhookTuleapReferenceCollection::fromReferences($ref_2)
        );

        self::assertEqualsCanonicalizing([$ref_1, $ref_2], $collection->getTuleapReferences());
    }

    public function testDefaultToAnEmptyCollection(): void
    {
        $collection = WebhookTuleapReferenceCollection::fromReferences();

        self::assertEmpty($collection->getTuleapReferences());
    }

    public function testEmptyCollectionHasNoReferences(): void
    {
        self::assertEmpty(WebhookTuleapReferenceCollection::empty()->getTuleapReferences());
    }

    public function testReferencesAreDeduplicated(): void
    {
        $reference = new WebhookTuleapReference(1, null);

        $collection = WebhookTuleapReferenceCollection::fromReferences($reference, $reference);

        self::assertEquals([$reference], $collection->getTuleapReferences());
    }
}
