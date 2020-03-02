<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Test\Psalm\Plugin\ConcealedString;

use Psalm\CodeLocation;
use Psalm\Issue\PluginIssue;

final class NoReturnUnwrappedConcealedString extends PluginIssue
{
    public function __construct(CodeLocation $code_location)
    {
        parent::__construct(
            'Do not return unwrapped ConcealedString, the ConcealedString instance should be returned instead',
            $code_location
        );
    }
}
