<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see < http://www.gnu.org/licenses/>.
 *
 */

namespace Tuleap\TestManagement\REST\v1\DefinitionRepresentations;

/**
 * @psalm-immutable
 */
interface DefinitionRepresentation
{
    public const ROUTE = 'testmanagement_definitions';

    public const FIELD_DESCRIPTION     = 'details';
    public const FIELD_STEPS           = 'steps';
    public const FIELD_SUMMARY         = 'summary';
    public const FIELD_CATEGORY        = 'category';
    public const FIELD_AUTOMATED_TESTS = 'automated_tests';
}
