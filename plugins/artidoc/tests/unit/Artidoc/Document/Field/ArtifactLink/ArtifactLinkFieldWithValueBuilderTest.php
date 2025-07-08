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

namespace Tuleap\Artidoc\Document\Field\ArtifactLink;

use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_ArtifactFactory;
use Tracker_ArtifactLinkInfo;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElement_Field_String;
use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\ArtifactLinkFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\ArtifactLinkStatusValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\ArtifactLinkValue;
use Tuleap\Color\ItemColor;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueArtifactLinkTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueListTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueStringTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\StaticBindDecoratorBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\FormElement\Field\ArtifactLink\Type\RetrieveTypeFromShortnameStub;
use Tuleap\Tracker\Test\Stub\Semantic\Title\RetrieveSemanticTitleFieldStub;
use Tuleap\Tracker\Tracker;

#[DisableReturnValueGenerationForTestDoubles]
final class ArtifactLinkFieldWithValueBuilderTest extends TestCase
{
    public function testItBuildsArtifactLinkField(): void
    {
        $project      = ProjectTestBuilder::aProject()->build();
        $tracker      = TrackerTestBuilder::aTracker()
            ->withShortName('my_tracker')
            ->withId(35)
            ->withProject($project)
            ->withColor(ItemColor::fromName('panther-pink'))
            ->build();
        $link_field   = ArtifactLinkFieldBuilder::anArtifactLinkField(456)->build();
        $title_field  = StringFieldBuilder::aStringField(854)->inTracker($tracker)->build();
        $open_value   = ListStaticValueBuilder::aStaticValue('Open')->withId(12)->build();
        $closed_value = ListStaticValueBuilder::aStaticValue('Closed')->withId(13)->build();
        $status_field = ListStaticBindBuilder::aStaticBind(ListFieldBuilder::aListField(855)->inTracker($tracker)->build())
            ->withBuildStaticValues([
                $open_value->getId()   => $open_value,
                $closed_value->getId() => $closed_value,
            ])
            ->withDecorators([
                $open_value->getId() => StaticBindDecoratorBuilder::withColor(ItemColor::fromName('neon-green'))->withValueId($open_value->getId())->build(),
            ])
            ->build()
            ->getField();

        $user             = UserTestBuilder::buildWithDefaults();
        $artifact_factory = $this->createPartialMock(Tracker_ArtifactFactory::class, ['getArtifactById']);
        $artifact_factory->method('getArtifactById')->willReturnCallback(fn(int $id) => match ($id) {
            15 => $this->buildArtifact(15, $tracker, $user, $title_field, 'Artifact 15', $status_field, $open_value),
            16 => $this->buildArtifact(16, $tracker, $user, $title_field, 'Artifact 16', $status_field, $closed_value),
            17 => $this->buildArtifact(17, $tracker, $user, $title_field, null, $status_field, null),
            21 => $this->buildArtifact(21, $tracker, $user, $title_field, 'Artifact 21', $status_field, $open_value),
            22 => $this->buildArtifact(22, $tracker, $user, $title_field, null, $status_field, $closed_value),
            23 => $this->buildArtifact(23, $tracker, $user, $title_field, 'Artifact 23', $status_field, null),
        });
        Tracker_ArtifactFactory::setInstance($artifact_factory);

        TrackerSemanticStatus::setInstance(new TrackerSemanticStatus($tracker, $status_field, [$open_value->getId()]), $tracker);

        $changeset = ChangesetValueArtifactLinkTestBuilder::aValue(12, ChangesetTestBuilder::aChangeset(852)->build(), $link_field)
            ->withForwardLinks([
                15 => new Tracker_ArtifactLinkInfo(15, 'art', 101, 35, 123, '_is_child'),
                16 => new Tracker_ArtifactLinkInfo(16, 'art', 101, 35, 124, '__covers'),
                17 => new Tracker_ArtifactLinkInfo(17, 'art', 101, 35, 125, null),
            ])
            ->withReverseLinks([
                21 => new Tracker_ArtifactLinkInfo(21, 'art', 101, 35, 235, '_is_child'),
                22 => new Tracker_ArtifactLinkInfo(22, 'art', 101, 35, 236, '__covers'),
                23 => new Tracker_ArtifactLinkInfo(23, 'art', 101, 35, 237, null),
            ])
            ->build();

        $builder = new ArtifactLinkFieldWithValueBuilder(
            $user,
            RetrieveSemanticTitleFieldStub::build()->withTitleField($tracker, $title_field),
            RetrieveTypeFromShortnameStub::build()
                ->withTypePresenter('_is_child', new TypePresenter('_is_child', 'Child', 'Parent', true))
                ->withTypePresenter('__covers', new TypePresenter('__covers', 'Covers', 'Covered by', true))
                ->withTypePresenter(null, new TypePresenter('', '', '', true)),
        );

        self::assertEquals(
            new ArtifactLinkFieldWithValue(
                $link_field->getLabel(),
                DisplayType::BLOCK,
                [
                    new ArtifactLinkValue(
                        'Child',
                        'my_tracker',
                        ItemColor::fromName('panther-pink'),
                        15,
                        'Artifact 15',
                        '/plugins/tracker/?aid=15',
                        new ArtifactLinkStatusValue('Open', ItemColor::fromName('neon-green'), true),
                    ),
                    new ArtifactLinkValue(
                        'Covers',
                        'my_tracker',
                        ItemColor::fromName('panther-pink'),
                        16,
                        'Artifact 16',
                        '/plugins/tracker/?aid=16',
                        new ArtifactLinkStatusValue('Closed', null, false),
                    ),
                    new ArtifactLinkValue(
                        '',
                        'my_tracker',
                        ItemColor::fromName('panther-pink'),
                        17,
                        '',
                        '/plugins/tracker/?aid=17',
                        null,
                    ),
                    new ArtifactLinkValue(
                        'Parent',
                        'my_tracker',
                        ItemColor::fromName('panther-pink'),
                        21,
                        'Artifact 21',
                        '/plugins/tracker/?aid=21',
                        new ArtifactLinkStatusValue('Open', ItemColor::fromName('neon-green'), true),
                    ),
                    new ArtifactLinkValue(
                        'Covered by',
                        'my_tracker',
                        ItemColor::fromName('panther-pink'),
                        22,
                        '',
                        '/plugins/tracker/?aid=22',
                        new ArtifactLinkStatusValue('Closed', null, false),
                    ),
                    new ArtifactLinkValue(
                        '',
                        'my_tracker',
                        ItemColor::fromName('panther-pink'),
                        23,
                        'Artifact 23',
                        '/plugins/tracker/?aid=23',
                        null,
                    ),
                ],
            ),
            $builder->buildArtifactLinkFieldWithValue(new ConfiguredField($link_field, DisplayType::BLOCK), $changeset)
        );
    }

    private function buildArtifact(
        int $id,
        Tracker $tracker,
        PFUser $user,
        Tracker_FormElement_Field_String $title_field,
        ?string $title,
        Tracker_FormElement_Field_List $status_field,
        ?Tracker_FormElement_Field_List_Bind_StaticValue $status_value,
    ): Artifact {
        $changeset = ChangesetTestBuilder::aChangeset(853)->build();
        $changeset->setFieldValue(
            $title_field,
            $title !== null ? ChangesetValueStringTestBuilder::aValue(1, $changeset, $title_field)
                ->withValue($title)
                ->build() : null,
        );
        $changeset->setFieldValue(
            $status_field,
            $status_value !== null ? ChangesetValueListTestBuilder::aListOfValue(2, $changeset, $status_field)
                ->withValues([$status_value])
                ->build() : null,
        );

        $artifact_builder = ArtifactTestBuilder::anArtifact($id)
            ->inTracker($tracker)
            ->withChangesets($changeset)
            ->userCanView($user);
        if ($status_value !== null) {
            $artifact_builder = $artifact_builder->withStatus($status_value->getLabel());
        }

        return $artifact_builder->build();
    }
}
