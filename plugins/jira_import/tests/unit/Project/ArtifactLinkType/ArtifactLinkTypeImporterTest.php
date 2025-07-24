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

use Psr\Log\NullLogger;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\GetMissingArtifactLinkTypes;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\InvalidTypeParameterException;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeCreatorInterface;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactLinkTypeImporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('getData')]
    public function testImportOfArtifactLinkTypes(array $urls, ?TypePresenter $type, TypeCreatorInterface $creator, callable $expectations, callable $tests): void
    {
        $client       = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub {
        };
        $client->urls = $urls;

        $type_converter = new class ($type) implements GetMissingArtifactLinkTypes
        {
            public function __construct(public ?TypePresenter $type)
            {
            }

            #[\Override]
            public function getMissingArtifactLinkTypes(array $json_representation): ?TypePresenter
            {
                return $this->type;
            }
        };

        $expectations($this);

        $importer = new ArtifactLinkTypeImporter($type_converter, $creator);
        $importer->import($client, new NullLogger());

        $tests($creator);
    }

    public static function getData(): iterable
    {
        return [
            'it throws an exception when data returned by Jira is not well formed' => [
                'urls' => [
                    'issueLinkTypes' => 'YOU MUST PAY!',
                ],
                'type' => null,
                'creator' => self::getDefaultCreator(),
                'expectations' => function (ArtifactLinkTypeImporterTest $test_case) {
                    $test_case->expectException(\RuntimeException::class);
                },
                'tests' => fn () => null,
            ],
            'it returns an artifact link type with accurate labels' => [
                'urls' => self::getDefaultIssueTypeResponse(),
                'type' => TypePresenter::buildVisibleType('Blocks', 'blocks', 'is blocked by'),
                'creator' => self::getDefaultCreator(),
                'expectations' => fn () => null,
                'tests' => function (mixed $creator) {
                    self::assertCount(1, $creator->natures);
                    self::assertSame('Blocks', $creator->natures[0]->shortname);
                    self::assertSame('blocks', $creator->natures[0]->forward_label);
                    self::assertSame('is blocked by', $creator->natures[0]->reverse_label);
                },
            ],
            'it does not return anything when type already exists' => [
                'urls' => self::getDefaultIssueTypeResponse(),
                'type' => null,
                'creator' => self::getDefaultCreator(),
                'expectations' => fn () => null,
                'tests' => function (mixed $creator) {
                    self::assertEmpty($creator->natures);
                },
            ],
            'it skips links that cannot be created' => [
                'urls' => self::getDefaultIssueTypeResponse(),
                'type' => null,
                'creator' => new class implements TypeCreatorInterface
                {
                    public array $natures = [];
                    #[\Override]
                    public function createFromType(TypePresenter $type): void
                    {
                        throw new InvalidTypeParameterException();
                    }
                },
                'expectations' => fn () => null,
                'tests' => function (mixed $creator) {
                    self::assertEmpty($creator->natures);
                },
            ],
        ];
    }

    private static function getDefaultIssueTypeResponse(): array
    {
        return [
            '/rest/api/2/issueLinkType' => [
                'issueLinkTypes' => [
                    [
                        'id'      => '10000',
                        'name'    => 'Blocks',
                        'inward'  => 'is blocked by',
                        'outward' => 'blocks',
                        'self'    => 'https://jira.example.com/rest/api/3/issueLinkType/10000',
                    ],
                ],
            ],
        ];
    }

    private static function getDefaultCreator(): TypeCreatorInterface
    {
        return new class implements TypeCreatorInterface
        {
            public array $natures = [];
            #[\Override]
            public function createFromType(TypePresenter $type): void
            {
                $this->natures[] = $type;
            }
        };
    }
}
