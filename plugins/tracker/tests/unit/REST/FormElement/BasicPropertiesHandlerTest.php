<?php
/**
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\FormElement;

use Luracast\Restler\RestException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\FieldDao;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFieldPatchRepresentationTestBuilder;
use Tuleap\Tracker\Test\Stub\FormElement\RetrieveFormElementByNameStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BasicPropertiesHandlerTest extends TestCase
{
    private const string ORIGINAL_NAME        = 'summary';
    private const string ORIGINAL_LABEL       = 'Summary';
    private const string ORIGINAL_DESCRIPTION = 'Description';

    public function testItDoesNothingIfNewLabelIsNotPartOfPatch(): void
    {
        $field   = StringFieldBuilder::aStringField(1)->withLabel(self::ORIGINAL_LABEL)->build();
        $factory = RetrieveFormElementByNameStub::withFormElements($field);
        $patch   = TrackerFieldPatchRepresentationTestBuilder::aPatch()->build();

        $dao = $this->createMock(FieldDao::class);
        $dao->expects($this->never())->method('save');

        $handler = new BasicPropertiesHandler($dao, $factory);
        $handler->handle($field, $patch, UserTestBuilder::buildWithDefaults());

        self::assertSame(self::ORIGINAL_LABEL, $field->getLabel());
    }

    public function testItRaisesAnExceptionIfLabelIsEmpty(): void
    {
        $field   = StringFieldBuilder::aStringField(1)->withLabel(self::ORIGINAL_LABEL)->build();
        $factory = RetrieveFormElementByNameStub::withFormElements($field);
        $patch   = TrackerFieldPatchRepresentationTestBuilder::aPatch()->withLabel('   ')->build();

        $dao = $this->createMock(FieldDao::class);
        $dao->expects($this->never())->method('save');

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $handler = new BasicPropertiesHandler($dao, $factory);
        $handler->handle($field, $patch, UserTestBuilder::buildWithDefaults());
    }

    public function testItUpdatesTheLabel(): void
    {
        $field   = StringFieldBuilder::aStringField(1)->withLabel(self::ORIGINAL_LABEL)->build();
        $factory = RetrieveFormElementByNameStub::withFormElements($field);
        $patch   = TrackerFieldPatchRepresentationTestBuilder::aPatch()->withLabel('New label')->build();

        $dao = $this->createMock(FieldDao::class);
        $dao->expects($this->once())->method('save');

        $handler = new BasicPropertiesHandler($dao, $factory);
        $handler->handle($field, $patch, UserTestBuilder::buildWithDefaults());

        self::assertSame('New label', $field->getLabel());
    }

    public function testItUpdatesTheDescription(): void
    {
        $field   = StringFieldBuilder::aStringField(1)
            ->withLabel(self::ORIGINAL_LABEL)
            ->withDescription(self::ORIGINAL_DESCRIPTION)
            ->build();
        $factory = RetrieveFormElementByNameStub::withFormElements($field);
        $patch   = TrackerFieldPatchRepresentationTestBuilder::aPatch()->withDescription('New description')->build();

        $dao = $this->createMock(FieldDao::class);
        $dao->expects($this->once())->method('save');

        $handler = new BasicPropertiesHandler($dao, $factory);
        $handler->handle($field, $patch, UserTestBuilder::buildWithDefaults());

        self::assertSame(self::ORIGINAL_LABEL, $field->getLabel());
        self::assertSame('New description', $field->getDescription());
    }

    public function testItFormatsTheName(): void
    {
        $field   = StringFieldBuilder::aStringField(1)
            ->withName(self::ORIGINAL_NAME)
            ->withLabel(self::ORIGINAL_LABEL)
            ->withDescription(self::ORIGINAL_DESCRIPTION)
            ->build();
        $factory = RetrieveFormElementByNameStub::withFormElements($field);
        $patch   = TrackerFieldPatchRepresentationTestBuilder::aPatch()->withName(" L'été   ")->build();

        $dao = $this->createMock(FieldDao::class);
        $dao->expects($this->once())->method('save');

        $handler = new BasicPropertiesHandler($dao, $factory);
        $handler->handle($field, $patch, UserTestBuilder::buildWithDefaults());

        self::assertSame('l___t__', $field->getName());
        self::assertSame(self::ORIGINAL_LABEL, $field->getLabel());
        self::assertSame(self::ORIGINAL_DESCRIPTION, $field->getDescription());
    }

    public function testItRaisesAnExceptionIfNameIsEmpty(): void
    {
        $field   = StringFieldBuilder::aStringField(1)
            ->withName(self::ORIGINAL_NAME)
            ->withLabel(self::ORIGINAL_LABEL)
            ->withDescription(self::ORIGINAL_DESCRIPTION)
            ->build();
        $factory = RetrieveFormElementByNameStub::withFormElements($field);
        $patch   = TrackerFieldPatchRepresentationTestBuilder::aPatch()->withName('   ')->build();

        $dao = $this->createMock(FieldDao::class);
        $dao->expects($this->never())->method('save');

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $handler = new BasicPropertiesHandler($dao, $factory);
        $handler->handle($field, $patch, UserTestBuilder::buildWithDefaults());
    }

    public function testItRaisesAnExceptionIfNameIsUsedByAnotherField(): void
    {
        $field         = StringFieldBuilder::aStringField(1)
            ->withName(self::ORIGINAL_NAME)
            ->withLabel(self::ORIGINAL_LABEL)
            ->withDescription(self::ORIGINAL_DESCRIPTION)
            ->build();
        $another_field = StringFieldBuilder::aStringField(2)
            ->withName('summary2')
            ->build();
        $factory       = RetrieveFormElementByNameStub::withFormElements($field, $another_field);
        $patch         = TrackerFieldPatchRepresentationTestBuilder::aPatch()->withName('summary2')->build();

        $dao = $this->createMock(FieldDao::class);
        $dao->expects($this->never())->method('save');

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $handler = new BasicPropertiesHandler($dao, $factory);
        $handler->handle($field, $patch, UserTestBuilder::buildWithDefaults());
    }

    #[DataProvider('provideFullPatchRepresentation')]
    public function testItUpdatesTheLabelAndDescription(
        string $new_name,
        string $new_label,
        string $new_description,
        string $expected_name,
        string $expected_label,
        string $expected_description,
    ): void {
        $field   = StringFieldBuilder::aStringField(1)
            ->withName(self::ORIGINAL_NAME)
            ->withLabel(self::ORIGINAL_LABEL)
            ->withDescription(self::ORIGINAL_DESCRIPTION)
            ->build();
        $factory = RetrieveFormElementByNameStub::withFormElements($field);
        $patch   = TrackerFieldPatchRepresentationTestBuilder::aPatch()
            ->withName($new_name)
            ->withLabel($new_label)
            ->withDescription($new_description)
            ->build();

        $dao = $this->createMock(FieldDao::class);
        $dao->expects($this->once())->method('save');

        $handler = new BasicPropertiesHandler($dao, $factory);
        $handler->handle($field, $patch, UserTestBuilder::buildWithDefaults());

        self::assertSame($expected_name, $field->getName());
        self::assertSame($expected_label, $field->getLabel());
        self::assertSame($expected_description, $field->getDescription());
    }

    public static function provideFullPatchRepresentation(): iterable
    {
        yield 'Changes everything'
            => ['newname', 'New label', 'New description', 'newname', 'New label', 'New description'];
        yield 'Changes label and empty description'
            => [self::ORIGINAL_NAME, 'New label', '', self::ORIGINAL_NAME, 'New label', ''];
        yield 'Changes label and try to put only spaces in description'
            => [self::ORIGINAL_NAME, 'New label', '    ', self::ORIGINAL_NAME, 'New label', ''];
        yield 'Changes only description'
            => [self::ORIGINAL_NAME, self::ORIGINAL_LABEL, 'New description', self::ORIGINAL_NAME, self::ORIGINAL_LABEL, 'New description'];
        yield 'Changes only label'
            => [self::ORIGINAL_NAME, 'New label', self::ORIGINAL_DESCRIPTION, self::ORIGINAL_NAME, 'New label', self::ORIGINAL_DESCRIPTION];
        yield 'Changes only name'
            => ['newname', self::ORIGINAL_LABEL, self::ORIGINAL_DESCRIPTION, 'newname', self::ORIGINAL_LABEL, self::ORIGINAL_DESCRIPTION];
    }

    public function testItDoesNotChangeAnythingIfPatchDoesNotContainAnyChange(): void
    {
        $field   = StringFieldBuilder::aStringField(1)
            ->withLabel(self::ORIGINAL_LABEL)
            ->withDescription(self::ORIGINAL_DESCRIPTION)
            ->build();
        $factory = RetrieveFormElementByNameStub::withFormElements($field);
        $patch   = TrackerFieldPatchRepresentationTestBuilder::aPatch()
            ->withLabel(self::ORIGINAL_LABEL)
            ->withDescription(self::ORIGINAL_DESCRIPTION)
            ->build();

        $dao = $this->createMock(FieldDao::class);
        $dao->expects($this->never())->method('save');

        $handler = new BasicPropertiesHandler($dao, $factory);
        $handler->handle($field, $patch, UserTestBuilder::buildWithDefaults());

        self::assertSame(self::ORIGINAL_LABEL, $field->getLabel());
        self::assertSame(self::ORIGINAL_DESCRIPTION, $field->getDescription());
    }

    public function testItDoesNotChangeAnythingIfPatchTriesToSetADescriptionWithOnlySpaces(): void
    {
        $field   = StringFieldBuilder::aStringField(1)
            ->withLabel(self::ORIGINAL_LABEL)
            ->withDescription('')
            ->build();
        $factory = RetrieveFormElementByNameStub::withFormElements($field);
        $patch   = TrackerFieldPatchRepresentationTestBuilder::aPatch()
            ->withLabel(self::ORIGINAL_LABEL)
            ->withDescription('   ')
            ->build();

        $dao = $this->createMock(FieldDao::class);
        $dao->expects($this->never())->method('save');

        $handler = new BasicPropertiesHandler($dao, $factory);
        $handler->handle($field, $patch, UserTestBuilder::buildWithDefaults());

        self::assertSame(self::ORIGINAL_LABEL, $field->getLabel());
        self::assertSame('', $field->getDescription());
    }
}
