<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\Text;

use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreation;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\Changeset\Comment\NewComment;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TextValueValidatorTest extends TestCase
{
    public function testItReturnsAnErrorIfContentIsNotString(): void
    {
        $validator = new TextValueValidator();

        $result = $validator->isValueValid(
            $this->buildTextField(),
            1,
        );

        self::assertTrue(Result::isErr($result));

        $result_array = $validator->isValueValid(
            $this->buildTextField(),
            [
                'content' => 1,
            ],
        );

        self::assertTrue(Result::isErr($result_array));

        $result_array_without_key = $validator->isValueValid(
            $this->buildTextField(),
            [1],
        );

        self::assertTrue(Result::isErr($result_array_without_key));
    }

    public function testItReturnsAnErrorIfContentIsATooBigStringContent(): void
    {
        $validator = new TextValueValidator();

        $result = $validator->isValueValid(
            $this->buildTextField(),
            $this->generatesTooBigStringContent(),
        );

        self::assertTrue(Result::isErr($result));
    }

    public function testItReturnsOkIfContentIsValid(): void
    {
        $validator = new TextValueValidator();

        $result = $validator->isValueValid(
            $this->buildTextField(),
            $this->generatesValidStringContent(),
        );

        self::assertTrue(Result::isOk($result));
    }

    public function testItReturnsAnErrorIfCommentContentIsValid(): void
    {
        $validator = new TextValueValidator();

        $result = $validator->isCommentContentValid(
            CommentCreation::fromNewComment(
                NewComment::fromParts(
                    str_repeat('a', 70000),
                    CommentFormatIdentifier::TEXT,
                    UserTestBuilder::aUser()->build(),
                    (new \DateTimeImmutable())->getTimestamp(),
                    []
                ),
                1459,
                new CreatedFileURLMapping()
            )
        );

        self::assertTrue(Result::isErr($result));
    }

    public function testItReturnsOkIfCommentContentIsValid(): void
    {
        $validator = new TextValueValidator();

        $result = $validator->isCommentContentValid(
            CommentCreation::fromNewComment(
                NewComment::fromParts(
                    'metavoltine huggermugger',
                    CommentFormatIdentifier::TEXT,
                    UserTestBuilder::aUser()->build(),
                    (new \DateTimeImmutable())->getTimestamp(),
                    []
                ),
                1459,
                new CreatedFileURLMapping()
            )
        );

        self::assertTrue(Result::isOk($result));
    }

    private function buildTextField(): TextField
    {
        $field = $this->createMock(TextField::class);
        $field->method('getLabel')->willReturn('Text Field');

        return $field;
    }

    private function generatesTooBigStringContent(): string
    {
        return str_repeat('a', 70000);
    }

    private function generatesValidStringContent(): string
    {
        return str_repeat('a', 60000);
    }
}
