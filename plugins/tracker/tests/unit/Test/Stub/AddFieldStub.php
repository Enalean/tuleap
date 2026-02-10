<?php
/**
 * Copyright (c) Enalean, 2026-present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub;

use Override;
use Tuleap\Tracker\FormElement\Field\AddField;

final class AddFieldStub implements AddField
{
    private(set) int $call_count = 0;

    private function __construct()
    {
    }

    public static function build(): self
    {
        return new AddFieldStub();
    }

    #[Override]
    public function addFormElement(int $form_element_id): bool
    {
        $this->call_count++;
        return true;
    }
}
