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

namespace Tuleap\Tracker\Search;

use Tracker_FormElement_Field;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Search\IndexedItemFound;
use Tuleap\Search\IndexedItemFoundToSearchResult;
use Tuleap\Search\SearchResultEntry;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stub\EventDispatcherStub;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class SearchResultRetrieverTest extends TestCase
{
    /**
     * @var \Tracker_ArtifactFactory&\PHPUnit\Framework\MockObject\Stub
     */
    private $artifact_factory;
    /**
     * @var \Tracker_FormElementFactory&\PHPUnit\Framework\MockObject\Stub
     */
    private $form_element_factory;
    private SearchResultRetriever $retriever;

    protected function setUp(): void
    {
        $this->artifact_factory     = $this->createStub(\Tracker_ArtifactFactory::class);
        $this->form_element_factory = $this->createStub(\Tracker_FormElementFactory::class);

        $glyph_finder = $this->createStub(GlyphFinder::class);
        $glyph_finder->method('get')->willReturn(null);

        $this->retriever = new SearchResultRetriever(
            $this->artifact_factory,
            $this->form_element_factory,
            EventDispatcherStub::withIdentityCallback(),
            $glyph_finder,
        );
    }

    public function testTransformIndexedFieldContentIntoASearchUserWhenUserCanAccessTheArtifactAndField(): void
    {
        $indexed_item_convertor = new IndexedItemFoundToSearchResult(
            [2 => new IndexedItemFound('plugin_artifact_field', ['artifact_id' => '123', 'field_id' => '777'])],
            UserTestBuilder::buildWithDefaults()
        );

        $project  = ProjectTestBuilder::aProject()->build();
        $artifact = ArtifactTestBuilder::anArtifact(123)->withTitle('title')->inProject($project)->build();
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($artifact);
        $field = $this->createStub(Tracker_FormElement_Field::class);
        $field->method('userCanRead')->willReturn(true);
        $this->form_element_factory->method('getUsedFormElementFieldById')->willReturn($field);

        $this->retriever->retrieveSearchResult($indexed_item_convertor);

        self::assertEquals(
            [
                2 => new SearchResultEntry(
                    $artifact->getXRef(),
                    $artifact->getUri(),
                    $artifact->getTitle(),
                    'fiesta-red',
                    null,
                    null,
                    'fa-list-ol',
                    $project,
                    []
                ),
            ],
            $indexed_item_convertor->search_results
        );
    }

    public function testDoesNotTouchIndexedItemsWithUnknownTypes(): void
    {
        $indexed_item_convertor = new IndexedItemFoundToSearchResult(
            [3 => new IndexedItemFound('something', ['name' => 'value'])],
            UserTestBuilder::buildWithDefaults()
        );

        $this->retriever->retrieveSearchResult($indexed_item_convertor);

        self::assertEmpty($indexed_item_convertor->search_results);
    }

    public function testDoesNotTransformIndexedItemWithCorruptedMetadata(): void
    {
        $indexed_item_convertor = new IndexedItemFoundToSearchResult(
            [4 => new IndexedItemFound('plugin_artifact_field', ['bad' => 'value'])],
            UserTestBuilder::buildWithDefaults()
        );

        $this->retriever->retrieveSearchResult($indexed_item_convertor);

        self::assertEmpty($indexed_item_convertor->search_results);
    }

    public function testDoesNotTransformIndexedItemRelatedToAnArtifactTheUserCannotSee(): void
    {
        $indexed_item_convertor = new IndexedItemFoundToSearchResult(
            [8 => new IndexedItemFound('plugin_artifact_field', ['artifact_id' => '403', 'field_id' => '777'])],
            UserTestBuilder::buildWithDefaults()
        );

        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn(null);

        $this->retriever->retrieveSearchResult($indexed_item_convertor);

        self::assertEmpty($indexed_item_convertor->search_results);
    }

    public function testDoesNotTransformIndexedItemRelatedToAFieldTheUserCannotRead(): void
    {
        $indexed_item_convertor = new IndexedItemFoundToSearchResult(
            [8 => new IndexedItemFound('plugin_artifact_field', ['artifact_id' => '147', 'field_id' => '403'])],
            UserTestBuilder::buildWithDefaults()
        );

        $artifact = ArtifactTestBuilder::anArtifact(147)->build();
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($artifact);
        $field = $this->createStub(Tracker_FormElement_Field::class);
        $field->method('userCanRead')->willReturn(false);
        $this->form_element_factory->method('getUsedFormElementFieldById')->willReturn($field);

        $this->retriever->retrieveSearchResult($indexed_item_convertor);

        self::assertEmpty($indexed_item_convertor->search_results);
    }

    public function testDoesNotTransformIndexedItemRelatedToANotUsedField(): void
    {
        $indexed_item_convertor = new IndexedItemFoundToSearchResult(
            [8 => new IndexedItemFound('plugin_artifact_field', ['artifact_id' => '147', 'field_id' => '404'])],
            UserTestBuilder::buildWithDefaults()
        );

        $artifact = ArtifactTestBuilder::anArtifact(147)->build();
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($artifact);
        $this->form_element_factory->method('getUsedFormElementFieldById')->willReturn(null);

        $this->retriever->retrieveSearchResult($indexed_item_convertor);

        self::assertEmpty($indexed_item_convertor->search_results);
    }
}
