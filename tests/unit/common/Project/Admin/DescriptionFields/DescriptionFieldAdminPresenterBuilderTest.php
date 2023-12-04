<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

use Tuleap\GlobalLanguageMock;

final class DescriptionFieldAdminPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    public function testItBuildsAListOfFields(): void
    {
        $row = [
            'group_desc_id' => 1,
            'desc_name' => 'field_name',
            'desc_description' => 'field_description',
            'desc_required' => 1,
            'desc_type' => 'line',
            'desc_rank' => 1,
        ];

        $expected_presenters =
            [
                new FieldPresenter(
                    'short_description',
                    'Short description',
                    'What is the purpose of your project?',
                    true,
                    null,
                    "",
                    '',
                    0,
                    true
                ),
                new FieldPresenter(
                    1,
                    'field_name',
                    'field_description',
                    true,
                    null,
                    "line",
                    '',
                    1,
                    false
                ),
            ];

        $presenter_builder = new DescriptionFieldAdminPresenterBuilder();
        $fields_presenters = $presenter_builder->build([$row]);

        self::assertEquals($expected_presenters, $fields_presenters);
    }
}
