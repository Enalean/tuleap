<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace unit\Metadata;

use Tuleap\Docman\Metadata\CreationMetadataValidator;
use Tuleap\Docman\Tests\Stub\ResponseFeedbackWrapperStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CreationMetadataValidatorTest extends TestCase
{
    /**
     * @var \Docman_MetadataFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $factory;
    private CreationMetadataValidator $validator;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory   = $this->createMock(\Docman_MetadataFactory::class);
        $this->validator = new CreationMetadataValidator($this->factory);
    }

    public function testMetadataIsInvalidWhenPropertyNameIsEmpty(): void
    {
        $feedback = ResponseFeedbackWrapperStub::buildWithNoPredefinedLevel();
        $name     = '';

        self::assertFalse($this->validator->validateNewMetadata($name, $feedback));
        self::assertEquals('error', $feedback->getLevel());
    }

    public function testMetadataIsInvalidWhenPropertyIsAlreadyCreatedWithTheSameName(): void
    {
        $feedback = ResponseFeedbackWrapperStub::buildWithNoPredefinedLevel();
        $name     = 'existing';
        $this->factory->method('findByName')->willReturn(new \ArrayIterator(['name' => $name]));

        self::assertFalse($this->validator->validateNewMetadata($name, $feedback));
        self::assertEquals('error', $feedback->getLevel());
    }

    public function testMetadataIsValid(): void
    {
        $feedback = ResponseFeedbackWrapperStub::buildWithNoPredefinedLevel();
        $name     = 'My metadata';
        $this->factory->method('findByName')->willReturn(new \ArrayIterator());

        self::assertTrue($this->validator->validateNewMetadata($name, $feedback));
        self::assertEquals('', $feedback->getLevel());
    }
}
