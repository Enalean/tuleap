<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Request;

use Exception;

final class MalformedQueryParameterException extends Exception
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function invalidQueryParameter(): self
    {
        return new self('Query is malformed. Expecting {"period":"future"} or {"period":"current"} or {"status":"open"} or {"status":"closed"}.');
    }

    public static function invalidQueryStatusParameter(): self
    {
        return new self('Query is malformed. Expecting {"status":"open"} or {"status":"closed"}.');
    }

    public static function invalidQueryOnlyAllStatusParameter(): self
    {
        return new self('Query is malformed. Expecting {"status":"all"}.');
    }

    public static function invalidQueryPeriodParameter(): self
    {
        return new self('Query is malformed. Expecting {"period":"future"} or {"period":"current"}.');
    }
}
