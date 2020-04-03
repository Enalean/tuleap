<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

require_once __DIR__ . '/../../../../../bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Workflow\PostAction\Update\SetDateValue;

class PostActionFieldIdValidatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var PostActionFieldIdValidator */
    private $field_ids_validator;

    protected function setUp(): void
    {
        $this->field_ids_validator = new PostActionFieldIdValidator();
    }

    public function testValidateDoesNotThrowWhenValid()
    {
        $first_date_value  = new SetDateValue(1, 0);
        $second_date_value = new SetDateValue(2, 0);

        $this->field_ids_validator->validate($first_date_value, $second_date_value);
        $this->expectNotToPerformAssertions();
    }

    public function testValidateThrowsWhenDuplicateFieldIds()
    {
        $first_identical_field_id  = new SetDateValue(3, 0);
        $second_identical_field_id = new SetDateValue(3, 1);

        $this->expectException(DuplicateFieldIdException::class);

        $this->field_ids_validator->validate($first_identical_field_id, $second_identical_field_id);
    }
}
