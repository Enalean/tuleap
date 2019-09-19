<?php
/**
 * Copyright (c) Enalean, 2013. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

/**
 * This interface should be implemented by all classes that are able to represent
 * themselves to a PHP / json compatible format.
 * The output of this is supposed to be passed to 'json_encode' as is.
 *
 * WARNING: please represent yourself as a JSON equivalent of your data.
 * if your properties are 'id', 'name, 'foo_bar' your json representation should
 * be:
 * array(
 *   'id' => /.../
 *   'name' => /.../
 *   'foo_bar /.../
 * );
 *
 * If, for some reasons, you should format your data in a different fashion (to
 * map an external API for instance, you must declare a specific method for this
 * purpose).
 * The global idea is to ensure consistency across implementation (JS, PHP, etc)
 * with same terms and behaviours.
 */
interface Tracker_IProvideJsonFormatOfMyself
{

    /**
     * Return a JSon representation of self meant to be passed as is to json_encode
     *
     * @return mixed
     */
    public function fetchFormattedForJson();
}
