<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Repository\Webhook\PostPush\Commits;

use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReferencesParser;

final class WebhookTuleapReferencesParserTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var WebhookTuleapReferencesParser
     */
    private $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new WebhookTuleapReferencesParser();
    }

    public function testItRetrievesTuleapReferencesInSingleLineMessage(): void
    {
        $references_collection = $this->parser->extractCollectionOfTuleapReferences(
            'artifact TULEAP-123 01 (TULEAP-234,tuleap-345)'
        );
        $references            = $references_collection->getTuleapReferences();

        $this->assertCount(3, $references);
        $this->assertSame(123, $references[0]->getId());
        $this->assertSame(234, $references[1]->getId());
        $this->assertSame(345, $references[2]->getId());
        $this->assertNull($references[0]->getCloseArtifactKeyword());
        $this->assertNull($references[1]->getCloseArtifactKeyword());
        $this->assertNull($references[2]->getCloseArtifactKeyword());
    }

    public function testItReturnsReferencesSortedById(): void
    {
        $references_collection = $this->parser->extractCollectionOfTuleapReferences('TULEAP-2, TULEAP-66, TULEAP-10');
        $references            = $references_collection->getTuleapReferences();

        $this->assertCount(3, $references);
        $this->assertSame(2, $references[0]->getId());
        $this->assertSame(10, $references[1]->getId());
        $this->assertSame(66, $references[2]->getId());
        $this->assertNull($references[0]->getCloseArtifactKeyword());
        $this->assertNull($references[1]->getCloseArtifactKeyword());
        $this->assertNull($references[2]->getCloseArtifactKeyword());
    }

    public function testItRetrievesTuleapReferencesWithMixedCharsInSingleLineMessage(): void
    {
        $references_collection = $this->parser->extractCollectionOfTuleapReferences('artifact TULEAP-12a3 01');
        $references            = $references_collection->getTuleapReferences();

        $this->assertCount(1, $references);
        $this->assertSame(12, $references[0]->getId());
        $this->assertNull($references[0]->getCloseArtifactKeyword());
    }

    public function testItRetrievesTuleapReferencesInMultipleLinesMessage(): void
    {
        $references_collection = $this->parser->extractCollectionOfTuleapReferences(
            "aaaaaaaaaaaaaaaaaaaaaaaasqsdfsdfsdfsfd TULEAP-123qsqsdqsdqsdqd TULEAP-254\n\nsdfsfhsudfTULEAP-aaaa\n\n\nTULEAP-898"
        );
        $references            = $references_collection->getTuleapReferences();

        $this->assertCount(3, $references);
        $this->assertSame(123, $references[0]->getId());
        $this->assertSame(254, $references[1]->getId());
        $this->assertSame(898, $references[2]->getId());
        $this->assertNull($references[0]->getCloseArtifactKeyword());
        $this->assertNull($references[1]->getCloseArtifactKeyword());
        $this->assertNull($references[2]->getCloseArtifactKeyword());
    }

    public function testItRetrievesTuleapReferencesOnlyOnce(): void
    {
        $references_collection = $this->parser->extractCollectionOfTuleapReferences("aaaaaaaaaaaaaaaaaaaaaaaasqsdfsdfsdfsfd TULEAP-123qsqsdqsdqsdqd TULEAP-254\n\nsdfsfhsudfTULEAP-aaaa\n\n\nTULEAP-123");
        $references            = $references_collection->getTuleapReferences();

        $this->assertCount(2, $references);
        $this->assertSame(123, $references[0]->getId());
        $this->assertSame(254, $references[1]->getId());
    }

    public function testItRetrievesTuleapReferencesCaseInsensitive(): void
    {
        $references_collection = $this->parser->extractCollectionOfTuleapReferences('artifact tuleap-123 01');
        $references            = $references_collection->getTuleapReferences();

        $this->assertCount(1, $references);
        $this->assertSame(123, $references[0]->getId());
        $this->assertNull($references[0]->getCloseArtifactKeyword());
    }

    public function testItDoesNotRetrievesTuleapReferenceThatDoesNotReferenceIntegerIds(): void
    {
        $references_collection = $this->parser->extractCollectionOfTuleapReferences('artifact TULEAP-abc 01');
        $references            = $references_collection->getTuleapReferences();

        $this->assertEmpty($references);
    }

    /**
     * @testWith [""]
     *           [" "]
     *           [","]
     *           ["."]
     *           ["|"]
     *           ["["]
     *           ["]"]
     *           ["("]
     *           [")"]
     *           ["{"]
     *           ["}"]
     *           ["'"]
     *           ["\""]
     */
    public function testItAcceptsALimitedListOfCharactersForReferenceBoundary(string $char): void
    {
        $references_collection = $this->parser->extractCollectionOfTuleapReferences($char . 'TULEAP-123');
        $references            = $references_collection->getTuleapReferences();

        $this->assertCount(1, $references, "${char}TULEAP-123 should be parsed");
        $this->assertSame(123, $references[0]->getId());
        $this->assertNull($references[0]->getCloseArtifactKeyword());
    }

    /**
     * @testWith ["#"]
     *           ["-"]
     *           ["_"]
     *           ["é"]
     *           ["è"]
     *           ["à"]
     *           ["e"]
     *           ["&"]
     *           ["語"]
     */
    public function testItRejectsInvalidReferenceBoundary(string $char): void
    {
        $references_collection = $this->parser->extractCollectionOfTuleapReferences($char . 'TULEAP-123');
        $references            = $references_collection->getTuleapReferences();

        $this->assertEmpty($references, "${char}TULEAP-123 should not be parsed");
    }

    public function testItRetrievesTheTuleapReferenceAndTheCloseKeywordResolveWhenGiven(): void
    {
        $references_collection = $this->parser->extractCollectionOfTuleapReferences('vroom resolve TULEAP-15 vroom resolves tuleap-3');
        $references            = $references_collection->getTuleapReferences();

        $this->assertCount(2, $references);

        $this->assertSame(3, $references[0]->getId());
        $this->assertSame('resolves', $references[0]->getCloseArtifactKeyword());

        $this->assertSame(15, $references[1]->getId());
        $this->assertSame('resolve', $references[1]->getCloseArtifactKeyword());
    }

    /**
     * @testWith [","]
     *           ["."]
     *           ["|"]
     *           ["["]
     *           ["]"]
     *           ["("]
     *           [")"]
     *           ["{"]
     *           ["}"]
     *           ["'"]
     *           ["\""]
     */
    public function testItRetrievesEachTheTuleapReferenceAndTheCloseKeywordResolveIfTheCloseKeywordIsGiven(string $accepted_boundary): void
    {
        $references_collection = $this->parser->extractCollectionOfTuleapReferences("vroom resolve TULEAP-15 tuleap-36 resolve" . $accepted_boundary . "tuleAp-88] (resolves [tuleap-68 vroom");
        $references            = $references_collection->getTuleapReferences();

        $this->assertCount(4, $references);

        $this->assertSame(15, $references[0]->getId());
        $this->assertSame('resolve', $references[0]->getCloseArtifactKeyword());

        $this->assertSame(36, $references[1]->getId());
        $this->assertNull($references[1]->getCloseArtifactKeyword());

        $this->assertSame(68, $references[2]->getId());
        $this->assertSame('resolves', $references[2]->getCloseArtifactKeyword());

        $this->assertSame(88, $references[3]->getId());
        $this->assertSame('resolve', $references[3]->getCloseArtifactKeyword());
    }
}
