<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\AllTypesRetriever;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactLinkTypeConverterTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('getGetMissingArtifactLinkTypeData')]
    public function testGetMissingArtifactLinkType(array $jira_json, array $types, callable $tests): void
    {
        $all_natures = new class ($types) implements AllTypesRetriever
        {
            public function __construct(public array $types)
            {
            }

            public function getAllTypes(): array
            {
                return $this->types;
            }
        };

        $converter   = new ArtifactLinkTypeConverter($all_natures);
        $tuleap_type = $converter->getMissingArtifactLinkTypes($jira_json);
        $tests($tuleap_type);
    }

    public static function getGetMissingArtifactLinkTypeData(): iterable
    {
        return [
            'it converts a Jira type to a Tuleap type ' => [
                'jira_json' => [
                    'id'   => '10003',
                    'name' => 'Relates',
                    'inward' => 'relates to',
                    'outward' => 'relates to',
                    'self' => '...',
                ],
                'types' => [],
                'tests' => function (?TypePresenter $type) {
                    self::assertEquals('Relates', $type->shortname);
                    self::assertEquals('relates to', $type->forward_label);
                    self::assertEquals('relates to', $type->reverse_label);
                },
            ],
            'it does not return anything when type already exists' => [
                'jira_json' => [
                    'id'   => '10003',
                    'name' => 'Relates',
                    'inward' => 'relates to',
                    'outward' => 'relates to',
                    'self' => '...',
                ],
                'types' => [TypePresenter::buildVisibleType('Relates', 'relates to', 'relates to')],
                'tests' =>  function (?TypePresenter $type) {
                    self::assertNull($type);
                },
            ],
            'it transforms link types that do not match the expected pattern' => [
                'jira_json' =>                             [
                    'id'      => '10000',
                    'name'    => 'Problem/Incident',
                    'inward'  => 'is caused by',
                    'outward' => 'causes',
                    'self'    => 'https://jira.example.com/rest/api/3/issueLinkType/10000',
                ],
                'types' => [],
                'tests' => function (?TypePresenter $type) {
                    self::assertSame('Problem_Incident', $type->shortname);
                    self::assertSame('causes', $type->forward_label);
                    self::assertSame('is caused by', $type->reverse_label);
                },
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getExistingArtifactLinkTypeData')]
    public function testGetExistingArtifactLinkType(array $jira_json, array $types, callable $tests): void
    {
        $all_natures = new class ($types) implements AllTypesRetriever
        {
            public function __construct(public array $types)
            {
            }

            public function getAllTypes(): array
            {
                return $this->types;
            }
        };

        $converter   = new ArtifactLinkTypeConverter($all_natures);
        $tuleap_type = $converter->getExistingArtifactLinkTypes($jira_json);
        $tests($tuleap_type);
    }

    public static function getExistingArtifactLinkTypeData(): iterable
    {
        return [
            'it returns null when type does not exist on Tuleap yet' => [
                'jira_json' => [
                    'id'   => '10003',
                    'name' => 'Relates',
                    'inward' => 'relates to',
                    'outward' => 'relates to',
                    'self' => '...',
                ],
                'types' => [],
                'tests' => function (?TypePresenter $type) {
                    self::assertNull($type);
                },
            ],
            'it returns the type that matches' => [
                'jira_json' => [
                    'id'   => '10003',
                    'name' => 'Relates',
                    'inward' => 'relates to',
                    'outward' => 'relates to',
                    'self' => '...',
                ],
                'types' => [TypePresenter::buildVisibleType('Relates', 'relates to', 'relates to')],
                'tests' =>  function (?TypePresenter $type) {
                    self::assertEquals('Relates', $type->shortname);
                    self::assertEquals('relates to', $type->forward_label);
                    self::assertEquals('relates to', $type->reverse_label);
                },
            ],
            'it transforms link types that do not match the expected pattern' => [
                'jira_json' =>                             [
                    'id'      => '10000',
                    'name'    => 'Problem/Incident',
                    'inward'  => 'is caused by',
                    'outward' => 'causes',
                    'self'    => 'https://jira.example.com/rest/api/3/issueLinkType/10000',
                ],
                'types' => [TypePresenter::buildVisibleType('Problem_Incident', 'causes', 'is caused by')],
                'tests' => function (?TypePresenter $type) {
                    self::assertSame('Problem_Incident', $type->shortname);
                    self::assertSame('causes', $type->forward_label);
                    self::assertSame('is caused by', $type->reverse_label);
                },
            ],
            'it do not transform type that were injected to recreate parent/child relationship out of epic/issues links' => [
                'jira_json' =>                             [
                    'name'    => GetExistingArtifactLinkTypes::FAKE_JIRA_TYPE_TO_RECREATE_CHILDREN,
                ],
                'types' => [TypePresenter::buildVisibleType(\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::TYPE_IS_CHILD, 'Child', 'Parent')],
                'tests' => function (?TypePresenter $type) {
                    self::assertSame(\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::TYPE_IS_CHILD, $type->shortname);
                },
            ],
        ];
    }
}
