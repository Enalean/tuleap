<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\JiraImport\Project\ArtifactLinkType;

use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\AllTypesRetriever;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeCreatorInterface;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;

final class ArtifactLinkTypeImporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItCatchesWhenDataReturnedByJiraIsNotWellFormed(): void
    {
        $client = new class extends \Tuleap\Tracker\Test\Tracker\Creation\JiraImporter\Stub\JiraCloudClientStub {
            public function getUrl(string $url): ?array
            {
                return [
                    'issueLinkTypes' => 'YOU MUST PAY!'
                ];
            }
        };

        $all_natures = new class implements AllTypesRetriever
        {
            public function getAllTypes(): array
            {
                return [];
            }
        };

        $creator = new class implements TypeCreatorInterface
        {
            public function createFromType(TypePresenter $type): void
            {
            }
        };

        $this->expectException(\RuntimeException::class);

        $importer = new ArtifactLinkTypeImporter($all_natures, $creator);
        $importer->import($client);
    }

    public function testItReturnsAnArtifactLinkTypeWithAccurateLabels(): void
    {
        $client = new class extends \Tuleap\Tracker\Test\Tracker\Creation\JiraImporter\Stub\JiraCloudClientStub {
            public function getUrl(string $url): ?array
            {
                return [
                    'issueLinkTypes' => [
                        [
                            "id"      => "10000",
                            "name"    => "Blocks",
                            "inward"  => "is blocked by",
                            "outward" => "blocks",
                            "self"    => "https://jira.example.com/rest/api/3/issueLinkType/10000",
                        ],
                    ],
                ];
            }
        };

        $all_natures = new class implements AllTypesRetriever
        {
            public function getAllTypes(): array
            {
                return [];
            }
        };

        $creator = new class implements TypeCreatorInterface
        {
            public array $natures = [];
            public function createFromType(TypePresenter $type): void
            {
                $this->natures[] = $type;
            }
        };

        $importer = new ArtifactLinkTypeImporter($all_natures, $creator);
        $importer->import($client);

        self::assertCount(1, $creator->natures);
        self::assertSame('Blocks', $creator->natures[0]->shortname);
        self::assertSame('blocks', $creator->natures[0]->forward_label);
        self::assertSame('is blocked by', $creator->natures[0]->reverse_label);
    }

    public function testItDoesntReturnAnythingWhenTypeAlreadyExists(): void
    {
        $client = new class extends \Tuleap\Tracker\Test\Tracker\Creation\JiraImporter\Stub\JiraCloudClientStub {
            public function getUrl(string $url): ?array
            {
                return [
                    'issueLinkTypes' => [
                        [
                            "id"      => "10000",
                            "name"    => "Blocks",
                            "inward"  => "is blocked by",
                            "outward" => "blocks",
                            "self"    => "https://jira.example.com/rest/api/3/issueLinkType/10000",
                        ],
                    ],
                ];
            }
        };

        $all_natures = new class implements AllTypesRetriever
        {
            public function getAllTypes(): array
            {
                return [TypePresenter::buildVisibleType('Blocks', '', '')];
            }
        };

        $creator = new class implements TypeCreatorInterface
        {
            public array $natures = [];
            public function createFromType(TypePresenter $type): void
            {
                $this->natures[] = $type;
            }
        };

        $importer = new ArtifactLinkTypeImporter($all_natures, $creator);
        $importer->import($client);

        self::assertEmpty($creator->natures);
    }
}
