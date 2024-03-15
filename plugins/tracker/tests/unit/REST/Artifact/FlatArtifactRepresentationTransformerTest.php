<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact;

use Tuleap\NeverThrow\Result;
use Tuleap\Project\REST\MinimalUserGroupRepresentation;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\TrackerRepresentation;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;
use Tuleap\User\REST\UserRepresentation;

final class FlatArtifactRepresentationTransformerTest extends TestCase
{
    public function testTransformRepresentationWithMultipleFieldValues(): void
    {
        $field_1 = $this->buildField(1, 'f1');
        $field_2 = $this->buildField(2, 'f2');

        $field_value_1 = new ArtifactFieldValueFullRepresentation();
        $field_value_1->build(1, 'string', 'label1', 'val1');
        $field_value_2 = new ArtifactFieldValueFullRepresentation();
        $field_value_2->build(2, 'string', 'label2', 'val2');

        $artifact_representation = $this->buildArtifactRepresentation([
            $field_value_1,
            $field_value_2,
        ]);

        $transformer = $this->buildTransformer([$field_1, $field_2]);
        $result      = $transformer($artifact_representation);

        self::assertEquals(
            ['f1' => 'val1', 'f2' => 'val2'],
            $result->unwrapOr('Unexpected error'),
        );
    }

    /**
     * @dataProvider dataProviderFieldValues
     */
    public function testTransformAllFieldValues(
        ArtifactFieldValueFullRepresentation|ArtifactFieldValueTextRepresentation|ArtifactFieldComputedValueFullRepresentation|ArtifactFieldValueListFullRepresentation|ArtifactFieldValueOpenListFullRepresentation $field_value,
        array $expected_flat_value,
    ): void {
        $field_1 = $this->buildField(1, 'f1');

        $artifact_representation = $this->buildArtifactRepresentation([$field_value]);

        $transformer = $this->buildTransformer([$field_1]);
        $result      = $transformer($artifact_representation);

        self::assertEquals(
            $expected_flat_value,
            $result->unwrapOr('Unexpected error'),
        );
    }

    public static function dataProviderFieldValues(): iterable
    {
        $build_base_field_value = static function (string $type, string|int|float|null $value): ArtifactFieldValueFullRepresentation {
            $field_value = new ArtifactFieldValueFullRepresentation();
            $field_value->build(1, $type, 'label1', $value);
            return $field_value;
        };

        yield 'string field' => [$build_base_field_value('string', 'value'), ['f1' => 'value']];
        yield 'int field' => [$build_base_field_value('int', 111), ['f1' => 111]];
        yield 'float field' => [$build_base_field_value('float', 111.222), ['f1' => 111.222]];
        yield 'aid field' => [$build_base_field_value('aid', 222), ['f1' => 222]];
        yield 'atid field' => [$build_base_field_value('atid', 222), ['f1' => 222]];
        yield 'priority field' => [$build_base_field_value('priority', 999), ['f1' => 999]];
        yield 'date field' => [$build_base_field_value('date', '2021-03-08T13:06:30+01:00'), ['f1' => '2021-03-08T13:06:30+01:00']];
        yield 'subon field' => [$build_base_field_value('date', '2021-03-08T13:06:30+01:00'), ['f1' => '2021-03-08T13:06:30+01:00']];
        yield 'lud field' => [$build_base_field_value('date', '2021-03-08T13:06:30+01:00'), ['f1' => '2021-03-08T13:06:30+01:00']];
        yield 'base field with no set value' => [$build_base_field_value('string', null), ['f1' => null]];
        $build_text_field_value = static fn (string $format, string $value): ArtifactFieldValueTextRepresentation
            => new ArtifactFieldValueTextRepresentation(1, 'text', 'label1', $value, $format);
        yield 'text field (plain text)' => [$build_text_field_value('text', 'plaintext'), ['f1' => 'plaintext']];
        yield 'text field (html text)' => [$build_text_field_value('html', '<p>html</p>'), ['f1' => 'html']];
        yield 'text field (commonmark text)' => [$build_text_field_value('commonmark', '<em>commonmark</em>'), ['f1' => 'commonmark']];
        $build_computed_field_value = static function (bool $is_autocomputed, float $value): ArtifactFieldComputedValueFullRepresentation {
            $field_value = new ArtifactFieldComputedValueFullRepresentation();
            $field_value->build(1, 'computed', 'label1', $is_autocomputed, $is_autocomputed ? $value : 404, $is_autocomputed ? 404 : $value);
            return $field_value;
        };
        yield 'computed field (computed value)' => [$build_computed_field_value(true, 123.456), ['f1' => 123.456]];
        yield 'computed field (manual value)' => [$build_computed_field_value(false, 456.123), ['f1' => 456.123]];
        yield 'unknown field type' => [$build_base_field_value('unknown', 'val'), []];
        $build_list_field = static function (?array $list_values): ArtifactFieldValueListFullRepresentation {
            $field_value = new ArtifactFieldValueListFullRepresentation();
            $field_value->build(1, 'sb', 'label1', $list_values, []);
            return $field_value;
        };
        yield 'list field (static values)' => [$build_list_field([['label' => 'value1'], ['label' => 'value2']]), ['f1' => ['value1', 'value2']]];
        yield 'list field (user bind)' => [$build_list_field([UserRepresentation::build(UserTestBuilder::anActiveUser()->withId(103)->withLocale('en_US')->build())]), ['f1' => ['User #103 ()']]];
        yield 'list field (user bind empty)' => [$build_list_field([UserRepresentation::build(\UserManager::instance()->getUserAnonymous())]), ['f1' => []]];
        yield 'list field (user group bind)' => [$build_list_field([new MinimalUserGroupRepresentation(102, new \ProjectUGroup(['ugroup_id' => 999, 'name' => 'ugroup_name']))]), ['f1' => ['ugroup_name']]];
        $openlist_representation = new ArtifactFieldValueOpenListFullRepresentation();
        $openlist_representation->build(1, 'tbl', 'label1', 'static', [['label' => 'value1']], []);
        yield 'openlist field' => [$openlist_representation, ['f1' => ['value1']]];
    }

    public function testArtifactRepresentationWithFieldValueReturnsAnError(): void
    {
        $artifact_representation = $this->buildArtifactRepresentation(null);

        $transformer = $this->buildTransformer([]);

        self::assertTrue(Result::isErr($transformer($artifact_representation)));
    }

    public function testThrowsWhenFieldUsedInRepresentationCannotBeFoundAgain(): void
    {
        $field_value = new ArtifactFieldValueFullRepresentation();
        $field_value->build(1, 'string', 'label1', 'val1');

        $artifact_representation = $this->buildArtifactRepresentation([$field_value]);

        $transformer = $this->buildTransformer([]);

        $this->expectException(\LogicException::class);
        $transformer($artifact_representation);
    }

    /**
     * @psalm-param \Tracker_FormElement_Field[] $fields
     */
    private function buildTransformer(array $fields): FlatArtifactRepresentationTransformer
    {
        $html_purifier = $this->createStub(\Codendi_HTMLPurifier::class);
        $html_purifier->method('purify')->willReturnCallback(fn (string $value): string => strip_tags($value));

        if (count($fields) > 0) {
            $fields_retriever = RetrieveUsedFieldsStub::withFields(...$fields);
        } else {
            $fields_retriever = RetrieveUsedFieldsStub::withNoFields();
        }

        return new FlatArtifactRepresentationTransformer($fields_retriever, $html_purifier);
    }

    private function buildField(int $id, string $name): \Tracker_FormElement_Field
    {
        $field = $this->createStub(\Tracker_FormElement_Field::class);
        $field->method('getId')->willReturn($id);
        $field->method('getName')->willReturn($name);

        return $field;
    }

    private function buildArtifactRepresentation(?array $values): ArtifactRepresentation
    {
        $artifact = $this->createStub(Artifact::class);
        $artifact->method('getId')->willReturn(777);
        $artifact->method('getAssignedTo')->willReturn([]);
        $artifact->method('getSubmittedByUser')->willReturn(UserTestBuilder::aUser()->build());
        $artifact->method('getXRef')->willReturn('foo #777');
        $artifact->method('getUri')->willReturn('/foo/777');
        $artifact->method('getSubmittedOn')->willReturn(1);
        $artifact->method('getLastUpdateDate')->willReturn(12);
        $artifact->method('isOpen')->willReturn(false);
        $artifact->method('getTitle')->willReturn('title');
        $tracker = $this->createStub(\Tracker::class);
        $tracker->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());
        $artifact->method('getTracker')->willReturn($tracker);


        return ArtifactRepresentation::build(
            UserTestBuilder::aUser()->build(),
            $artifact,
            $values,
            null,
            $this->createStub(TrackerRepresentation::class),
            StatusValueRepresentation::buildFromValues('value', null),
        );
    }
}
