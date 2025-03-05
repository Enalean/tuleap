<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Project\Admin\DescriptionFields;

use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\Registration\ProjectRegistrationErrorsCollection;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectRegistrationSubmittedFieldsCollectionConsistencyCheckerTest extends TestCase
{
    private ProjectRegistrationSubmittedFieldsCollectionConsistencyChecker $checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&DescriptionFieldsFactory
     */
    private $fields_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fields_factory = $this->createMock(DescriptionFieldsFactory::class);

        $this->checker = new ProjectRegistrationSubmittedFieldsCollectionConsistencyChecker(
            $this->fields_factory
        );
    }

    public function testExceptionIsAddedToCollectionWhenSomeFieldsAreMissing(): void
    {
        $this->fields_factory->method('getAllDescriptionFields')->willReturn(
            [
                ['group_desc_id' => 1, 'desc_required' => true, 'desc_name' => 'field_name'],
            ]
        );

        $field_collection  = ProjectRegistrationSubmittedFieldsCollection::buildFromArray([]);
        $errors_collection = new ProjectRegistrationErrorsCollection();

        $this->checker->checkFieldConsistency(
            $field_collection,
            $errors_collection
        );

        self::assertCount(1, $errors_collection->getErrors());
        self::assertInstanceOf(MissingMandatoryFieldException::class, $errors_collection->getErrors()[0]);
    }

    public function testExceptionIsAddedToCollectionWhenUserProvidesFieldsWhoDoesNotExists(): void
    {
        $this->fields_factory->method('getAllDescriptionFields')->willReturn(
            [
                ['group_desc_id' => 1, 'desc_required' => false, 'desc_name' => 'field_name'],
            ]
        );

        $field_collection = ProjectRegistrationSubmittedFieldsCollection::buildFromArray([
            2 => 'test',
        ]);

        $errors_collection = new ProjectRegistrationErrorsCollection();

        $this->checker->checkFieldConsistency($field_collection, $errors_collection);

        self::assertCount(1, $errors_collection->getErrors());
        self::assertInstanceOf(FieldDoesNotExistException::class, $errors_collection->getErrors()[0]);
    }

    public function testBothExceptionsAreAddedToCollectionWhenSomeFieldsAreMissingAndOtherFieldsDoNotExist(): void
    {
        $this->fields_factory->method('getAllDescriptionFields')->willReturn(
            [
                ['group_desc_id' => 1, 'desc_required' => true, 'desc_name' => 'field_name'],
            ]
        );

        $field_collection = ProjectRegistrationSubmittedFieldsCollection::buildFromArray([
            2 => 'test',
        ]);

        $errors_collection = new ProjectRegistrationErrorsCollection();

        $this->checker->checkFieldConsistency(
            $field_collection,
            $errors_collection
        );

        self::assertCount(2, $errors_collection->getErrors());
        self::assertInstanceOf(MissingMandatoryFieldException::class, $errors_collection->getErrors()[0]);
        self::assertInstanceOf(FieldDoesNotExistException::class, $errors_collection->getErrors()[1]);
    }

    public function testFieldConsistencyIsValidWhenEverythingIsOk(): void
    {
        $this->fields_factory->method('getAllDescriptionFields')->willReturn(
            [
                ['group_desc_id' => 1, 'desc_required' => true, 'desc_name' => 'field_name'],
                ['group_desc_id' => 2, 'desc_required' => false, 'desc_name' => 'other_field_name'],
            ]
        );

        $field_collection = ProjectRegistrationSubmittedFieldsCollection::buildFromArray([
            1 => 'test',
        ]);

        $errors_collection = new ProjectRegistrationErrorsCollection();

        $this->checker->checkFieldConsistency($field_collection, $errors_collection);

        self::assertEmpty($errors_collection->getErrors());
    }
}
