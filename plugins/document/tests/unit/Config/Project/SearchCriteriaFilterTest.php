<?php
/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Document\Config\Project;

use Docman_MetadataFactory;
use Docman_SettingsBo;
use Tuleap\Document\Tree\ListOfSearchCriterionPresenterBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SearchCriteriaFilterTest extends TestCase
{
    private SearchCriteriaFilter $search_criteria_filter;

    protected function setUp(): void
    {
        $this->search_criteria_filter = new SearchCriteriaFilter(
            new ListOfSearchCriterionPresenterBuilder($this->searchCriteriaDAOStub(), EventDispatcherStub::withIdentityCallback()),
            $this->searchCriteriaDAOStub()
        );
    }

    public function testItGetsSelectedCriterion(): void
    {
        $metadata_factory = $this->createMock(\Docman_MetadataFactory::class);
        $metadata_factory->method('getMetadataForGroup')
            ->with(true)
            ->willReturn([]);
        $metadata_factory->method('appendAllListOfValues');

        $project = $this->createMock(\Project::class);
        $project->method('usesWiki')->willReturn(false);
        $project->method('getID')->willReturn(101);

        $docman_settings = $this->createMock(Docman_SettingsBo::class);
        $docman_settings->method('getMetadataUsage')->willReturn('1');

        $metadata_factory
            ->method('getMetadataForGroup')
            ->willReturn([
                $this->getHardcodedMetadata(Docman_MetadataFactory::HARDCODED_METADATA_TITLE_LABEL),
                $this->getHardcodedMetadata(Docman_MetadataFactory::HARDCODED_METADATA_DESCRIPTION_LABEL),
                $this->getCustomMetadata('field_1'),
                $this->getCustomMetadata('filename'),
            ]);


        $selected_criterion = $this->search_criteria_filter->getCriteria($project, $metadata_factory);
        $expected_criterion = [
            [
                'name' => 'id',
                'label' => 'Id',
                'is_selected' => false,
            ],
            [
                'name' => 'type',
                'label' => 'Type',
                'is_selected' => false,
            ],
            [
                'name' => 'filename',
                'label' => 'Filename',
                'is_selected' => true,
            ],
        ];
         self::assertEquals($expected_criterion, $selected_criterion);
    }

    private function searchCriteriaDAOStub(): IRetrieveCriteria
    {
        return new class implements IRetrieveCriteria {
            public function searchByProjectId(int $project_id): array
            {
                return [
                    'title',
                    'description',
                    'field_1',
                    'filename',
                ];
            }
        };
    }

    private function getHardcodedMetadata(string $label): \Docman_Metadata
    {
        $metadata = new \Docman_Metadata();
        $metadata->setLabel($label);
        $metadata->setName(\ucfirst($label));
        $metadata->setSpecial(true);

        return $metadata;
    }

    private function getCustomMetadata(string $label): \Docman_Metadata
    {
        $metadata = new \Docman_Metadata();
        $metadata->setLabel($label);
        $metadata->setName(\ucfirst($label));
        $metadata->setSpecial(false);

        return $metadata;
    }
}
