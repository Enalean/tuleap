<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

/**
 * @deprecated
 */
class XML_Security
{
    public static function enableExternalLoadOfEntities(callable $callback): void
    {
        \libxml_disable_entity_loader(false); // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found
        try {
            $callback();
        } finally {
            \libxml_disable_entity_loader(true); // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found
        }
    }
}
