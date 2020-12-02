<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

interface Tracker_FormElement_IProvideFactoryButtonInformation
{

    /**
     * @return string the label of the formElement (mainly used in admin part)
     */
    public static function getFactoryLabel();

    /**
     * @return string the description of the formElement (mainly used in admin part)
     */
    public static function getFactoryDescription();

    /**
     * @return string the path to the icon to use an element
     */
    public static function getFactoryIconUseIt();

    /**
     * @return string the path to the icon to create an element
     */
    public static function getFactoryIconCreate();

    /**
     * @return bool say if the element is a unique one
     */
    public static function getFactoryUniqueField();
}
