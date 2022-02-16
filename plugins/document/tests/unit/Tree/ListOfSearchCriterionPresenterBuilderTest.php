<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Document\Tree;

use Tuleap\Test\PHPUnit\TestCase;

class ListOfSearchCriterionPresenterBuilderTest extends TestCase
{
    public function testItShouldAlwaysReturnTypeCriterion(): void
    {
        $metadata_factory = $this->createMock(\Docman_MetadataFactory::class);
        $metadata_factory->method('getMetadataForGroup')
            ->with(true)
            ->willReturn([]);

        $project = $this->createMock(\Project::class);
        $project->method('usesWiki')->willReturn(true);

        $criteria = (new ListOfSearchCriterionPresenterBuilder())->getCriteria($metadata_factory, $project);

        self::assertCount(1, $criteria);
        self::assertEquals('type', $criteria[0]->name);
        self::assertEquals(
            ['', 'folder', 'file', 'embedded', 'wiki', 'empty'],
            array_map(
                static fn(SearchCriterionListOptionPresenter $option): string => $option->value,
                $criteria[0]->options
            )
        );
    }

    public function testItShouldOmitWikiTypeIfProjectDoesNotUseWikiService(): void
    {
        $metadata_factory = $this->createMock(\Docman_MetadataFactory::class);
        $metadata_factory->method('getMetadataForGroup')
            ->with(true)
            ->willReturn([]);

        $project = $this->createMock(\Project::class);
        $project->method('usesWiki')->willReturn(false);

        $criteria = (new ListOfSearchCriterionPresenterBuilder())->getCriteria($metadata_factory, $project);

        self::assertCount(1, $criteria);
        self::assertEquals('type', $criteria[0]->name);
        self::assertEquals(
            ['', 'folder', 'file', 'embedded', 'empty'],
            array_map(
                static fn(SearchCriterionListOptionPresenter $option): string => $option->value,
                $criteria[0]->options
            )
        );
    }

    /**
     * @testWith ["title"]
     *           ["description"]
     *           ["owner"]
     *           ["create_date"]
     *           ["update_date"]
     *           ["obsolescence_date"]
     */
    public function testItShouldReturnCriteriaBasedOnSupportedMetadata(string $metadata_name): void
    {
        $metadata = new \Docman_Metadata();
        $metadata->setSpecial(true);
        $metadata->setLabel($metadata_name);
        $metadata->setName($metadata_name);
        $metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);

        $metadata_factory = $this->createMock(\Docman_MetadataFactory::class);
        $metadata_factory->method('getMetadataForGroup')
            ->with(true)
            ->willReturn([$metadata]);

        $project = $this->createMock(\Project::class);
        $project->method('usesWiki')->willReturn(true);

        $criteria = (new ListOfSearchCriterionPresenterBuilder())->getCriteria($metadata_factory, $project);

        self::assertCount(2, $criteria);
        self::assertEquals($metadata_name, $criteria[1]->name);
    }

    public function testItShouldOmitHardcodedStatusMetadataBecauseItIsNotImplementedYet(): void
    {
        $metadata = new \Docman_Metadata();
        $metadata->setSpecial(true);
        $metadata->setLabel('status');
        $metadata->setName('status');

        $project = $this->createMock(\Project::class);
        $project->method('usesWiki')->willReturn(true);

        $metadata_factory = $this->createMock(\Docman_MetadataFactory::class);
        $metadata_factory->method('getMetadataForGroup')
            ->with(true)
            ->willReturn([$metadata]);

        $criteria = (new ListOfSearchCriterionPresenterBuilder())->getCriteria($metadata_factory, $project);

        self::assertCount(1, $criteria);
    }

    public function testItShouldOmitCustomMetadataBecauseWeDoNotSupportThemYet(): void
    {
        $metadata = new \Docman_Metadata();
        $metadata->setSpecial(false);
        $metadata->setLabel('whatever');
        $metadata->setName('whatever');

        $project = $this->createMock(\Project::class);
        $project->method('usesWiki')->willReturn(true);

        $metadata_factory = $this->createMock(\Docman_MetadataFactory::class);
        $metadata_factory->method('getMetadataForGroup')
            ->with(true)
            ->willReturn([$metadata]);

        $criteria = (new ListOfSearchCriterionPresenterBuilder())->getCriteria($metadata_factory, $project);

        self::assertCount(1, $criteria);
    }
}
