<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ChangesetValue;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Mapper\ValinorMapperBuilderFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\RetrieveAnArtifactLinkField;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValueBuilder;
use Tuleap\Tracker\REST\v1\LinkWithDirectionRepresentation;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveAnArtifactLinkFieldStub;
use Tuleap\Tracker\Test\Stub\RetrieveForwardLinksStub;
use function Psl\Json\encode as psl_json_encode;

#[DisableReturnValueGenerationForTestDoubles]
final class ChangesetValuesContainerBuilderTest extends TestCase
{
    private RetrieveAnArtifactLinkField $artifact_link_field_retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->artifact_link_field_retriever = RetrieveAnArtifactLinkFieldStub::withoutAnArtifactLinkField();
    }

    private function build(array $fields_data): ChangesetValuesContainer
    {
        return (new ChangesetValuesContainerBuilder(
            $this->artifact_link_field_retriever,
            ValinorMapperBuilderFactory::mapperBuilder()->allowPermissiveTypes()->mapper(),
            new NewArtifactLinkChangesetValueBuilder(RetrieveForwardLinksStub::withoutLinks()),
            new NewArtifactLinkInitialChangesetValueBuilder(),
        ))->buildChangesetValuesContainer(
            $fields_data,
            TrackerTestBuilder::aTracker()->withId(452)->build(),
            ArtifactTestBuilder::anArtifact(9845)->build(),
            UserTestBuilder::buildWithDefaults(),
        );
    }

    public function testItReturnsNothingIfNoArtifactLinkField(): void
    {
        $changeset_values_container = $this->build([456 => 'some value']);
        self::assertTrue($changeset_values_container->getArtifactLinkValue()->isNothing());
        self::assertSame([456 => 'some value'], $changeset_values_container->getFieldsData());
    }

    public function testItReturnsNothingIfNoValueSubmittedForField(): void
    {
        $this->artifact_link_field_retriever = RetrieveAnArtifactLinkFieldStub::withAnArtifactLinkField(ArtifactLinkFieldBuilder::anArtifactLinkField(12)->build());

        $changeset_values_container = $this->build([456 => 'some value']);
        self::assertTrue($changeset_values_container->getArtifactLinkValue()->isNothing());
        self::assertSame([456 => 'some value'], $changeset_values_container->getFieldsData());
    }

    public function testItReturnsNothingIfValueHasWrongFormat(): void
    {
        $this->artifact_link_field_retriever = RetrieveAnArtifactLinkFieldStub::withAnArtifactLinkField(
            ArtifactLinkFieldBuilder::anArtifactLinkField(12)->withSpecificProperty('can_edit_reverse_links', ['value' => 1])->build()
        );

        $changeset_values_container = $this->build([456 => 'some value', 12 => 'invalid_value']);
        self::assertTrue($changeset_values_container->getArtifactLinkValue()->isNothing());
        self::assertSame([456 => 'some value', 12 => 'invalid_value'], $changeset_values_container->getFieldsData());
    }

    public function testItReturnsChangesetValuesContainer(): void
    {
        $this->artifact_link_field_retriever = RetrieveAnArtifactLinkFieldStub::withAnArtifactLinkField(
            ArtifactLinkFieldBuilder::anArtifactLinkField(12)->withSpecificProperty('can_edit_reverse_links', ['value' => 1])->build()
        );

        $changeset_values_container = $this->build([
            12 => psl_json_encode([
                'field_id'  => 12,
                'all_links' => [
                    new LinkWithDirectionRepresentation(9846, 'forward', ''),
                    new LinkWithDirectionRepresentation(9847, 'reverse', '_is_child'),
                ],
            ]),
        ]);
        self::assertTrue($changeset_values_container->getArtifactLinkValue()->isValue());
        self::assertSame([
            12 => [
                'new_values'     => '9846',
                'removed_values' => [],
                'types'          => [
                    9846 => '',
                ],
            ],
        ], $changeset_values_container->getFieldsData());
    }

    public function testItReturnsInitialChangesetValuesContainer(): void
    {
        $changeset_values_container = (new ChangesetValuesContainerBuilder(
            RetrieveAnArtifactLinkFieldStub::withAnArtifactLinkField(
                ArtifactLinkFieldBuilder::anArtifactLinkField(12)->withSpecificProperty('can_edit_reverse_links', ['value' => 1])->build()
            ),
            ValinorMapperBuilderFactory::mapperBuilder()->allowPermissiveTypes()->mapper(),
            new NewArtifactLinkChangesetValueBuilder(RetrieveForwardLinksStub::withoutLinks()),
            new NewArtifactLinkInitialChangesetValueBuilder(),
        ))->buildInitialChangesetValuesContainer(
            [
                12 => psl_json_encode([
                    'field_id'  => 12,
                    'all_links' => [
                        new LinkWithDirectionRepresentation(9846, 'forward', ''),
                        new LinkWithDirectionRepresentation(9847, 'reverse', '_is_child'),
                    ],
                ]),
            ],
            TrackerTestBuilder::aTracker()->withId(452)->build(),
            UserTestBuilder::buildWithDefaults(),
        );
        self::assertTrue($changeset_values_container->getArtifactLinkValue()->isValue());
        self::assertSame([
            12 => [
                'new_values' => '9846',
                'types'      => [
                    9846 => '',
                ],
            ],
        ], $changeset_values_container->getFieldsData());
    }
}
