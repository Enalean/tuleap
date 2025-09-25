<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use Tuleap\Tracker\Workflow\PostAction\Update\SetDateValue;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PostActionFieldIdValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private PostActionFieldIdValidator $field_ids_validator;

    #[\Override]
    protected function setUp(): void
    {
        $this->field_ids_validator = new PostActionFieldIdValidator();
    }

    public function testValidateDoesNotThrowWhenValid(): void
    {
        $first_date_value  = new SetDateValue(1, 0);
        $second_date_value = new SetDateValue(2, 0);

        $this->field_ids_validator->validate($first_date_value, $second_date_value);
        $this->expectNotToPerformAssertions();
    }

    public function testValidateThrowsWhenDuplicateFieldIds(): void
    {
        $first_identical_field_id  = new SetDateValue(3, 0);
        $second_identical_field_id = new SetDateValue(3, 1);

        $this->expectException(DuplicateFieldIdException::class);

        $this->field_ids_validator->validate($first_identical_field_id, $second_identical_field_id);
    }
}
