<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document\Field;

use PFUser;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldIsDescriptionSemanticFault;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldIsTitleSemanticFault;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldNotFoundFault;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldNotSupportedFault;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\Semantic\Title\RetrieveSemanticTitleField;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ExternalFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;
use Tuleap\Tracker\Test\Stub\Semantic\Description\RetrieveSemanticDescriptionFieldStub;
use Tuleap\Tracker\Test\Stub\Semantic\Title\RetrieveSemanticTitleFieldStub;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SuitableFieldRetrieverTest extends TestCase
{
    private const FIELD_ID = 513;
    private PFUser $user;
    private Tracker $tracker;
    private RetrieveUsedFieldsStub $field_retriever;
    private RetrieveSemanticDescriptionFieldStub $description_field_retriever;
    private RetrieveSemanticTitleField $title_field_retriever;

    protected function setUp(): void
    {
        $this->tracker                     = TrackerTestBuilder::aTracker()->withId(1001)->build();
        $this->user                        = UserTestBuilder::buildWithDefaults();
        $this->field_retriever             = RetrieveUsedFieldsStub::withNoFields();
        $this->description_field_retriever = RetrieveSemanticDescriptionFieldStub::withNoField();
        $this->title_field_retriever       = RetrieveSemanticTitleFieldStub::build();
    }

    /**
     * @return Ok<StringField> | Ok<\Tracker_FormElement_Field_List> | Ok<ArtifactLinkField> | Err<Fault>
     */
    private function retrieve(): Ok|Err
    {
        $retriever = new SuitableFieldRetriever($this->field_retriever, $this->description_field_retriever, $this->title_field_retriever);
        return $retriever->retrieveField(self::FIELD_ID, $this->user);
    }

    public function testErrForFieldThatIsNotASupportedType(): void
    {
        $this->field_retriever = RetrieveUsedFieldsStub::withFields(
            ExternalFieldBuilder::anExternalField(self::FIELD_ID)->withReadPermission($this->user, true)->build(),
        );

        $result = $this->retrieve();
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldNotSupportedFault::class, $result->error);
    }

    public function testErrForFieldThatIsNotReadable(): void
    {
        $this->field_retriever = RetrieveUsedFieldsStub::withFields(
            StringFieldBuilder::aStringField(self::FIELD_ID)
                ->withReadPermission($this->user, false)
                ->build()
        );

        $result = $this->retrieve();
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldNotFoundFault::class, $result->error);
    }

    public function testErrForFieldThatIsSemanticTitle(): void
    {
        $field                 = StringFieldBuilder::aStringField(self::FIELD_ID)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->build();
        $this->field_retriever = RetrieveUsedFieldsStub::withFields($field);

        $this->title_field_retriever = RetrieveSemanticTitleFieldStub::build()->withTitleField($this->tracker, $field);

        $result = $this->retrieve();
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldIsTitleSemanticFault::class, $result->error);
    }

    public function testErrForFieldThatIsSemanticDescription(): void
    {
        $field                             = StringFieldBuilder::aStringField(self::FIELD_ID)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->build();
        $this->field_retriever             = RetrieveUsedFieldsStub::withFields($field);
        $this->description_field_retriever = RetrieveSemanticDescriptionFieldStub::withTextField($field);

        $result = $this->retrieve();
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldIsDescriptionSemanticFault::class, $result->error);
    }

    public function testHappyPath(): void
    {
        $string_field          = StringFieldBuilder::aStringField(self::FIELD_ID)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->build();
        $this->field_retriever = RetrieveUsedFieldsStub::withFields($string_field);

        $result = $this->retrieve();
        self::assertTrue(Result::isOk($result));
        self::assertSame($string_field, $result->value);
    }

    public function testItAllowsListFieldBoundToUserGroups(): void
    {
        $list_field            = ListUserGroupBindBuilder::aUserGroupBind(
            ListFieldBuilder::aListField(self::FIELD_ID)
                ->withMultipleValues()
                ->inTracker($this->tracker)
                ->withReadPermission($this->user, true)
                ->build(),
        )->build()->getField();
        $this->field_retriever = RetrieveUsedFieldsStub::withFields($list_field);

        $result = $this->retrieve();
        self::assertTrue(Result::isOk($result));
        self::assertSame($list_field, $result->value);
    }

    public function testItAllowsListFieldBoundToStaticValues(): void
    {
        $list_field            = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(self::FIELD_ID)
                ->withMultipleValues()
                ->inTracker($this->tracker)
                ->withReadPermission($this->user, true)
                ->build(),
        )->build()->getField();
        $this->field_retriever = RetrieveUsedFieldsStub::withFields($list_field);

        $result = $this->retrieve();
        self::assertTrue(Result::isOk($result));
        self::assertSame($list_field, $result->value);
    }

    public function testItAllowsListFieldBoundToUsers(): void
    {
        $list_field            = ListUserBindBuilder::aUserBind(
            ListFieldBuilder::aListField(self::FIELD_ID)
                ->inTracker($this->tracker)
                ->withReadPermission($this->user, true)
                ->build(),
        )->build()->getField();
        $this->field_retriever = RetrieveUsedFieldsStub::withFields($list_field);

        $result = $this->retrieve();
        self::assertTrue(Result::isOk($result));
        self::assertSame($list_field, $result->value);
    }

    public function testItAllowsArtifactLinkField(): void
    {
        $link_field            = ArtifactLinkFieldBuilder::anArtifactLinkField(self::FIELD_ID)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->build();
        $this->field_retriever = RetrieveUsedFieldsStub::withFields($link_field);

        $result = $this->retrieve();
        self::assertTrue(Result::isOk($result));
        self::assertSame($link_field, $result->value);
    }
}
