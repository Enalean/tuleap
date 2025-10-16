<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use CSRFSynchronizerToken;

abstract class ProvideFactoryButtonInformation
{
    /**
     * @return string the label of the formElement (mainly used in admin part)
     */
    abstract public static function getFactoryLabel();

    /**
     * @return string the description of the formElement (mainly used in admin part)
     */
    abstract public static function getFactoryDescription();

    /**
     * @return string the path to the icon to use an element
     */
    abstract public static function getFactoryIconUseIt();

    /**
     * @return string the path to the icon to create an element
     */
    abstract public static function getFactoryIconCreate();

    /**
     * @return bool say if the element is a unique one
     */
    abstract public static function getFactoryUniqueField();

    /**
     * Return the tracker id of this formElement
     *
     * @return int
     */
    abstract public function getTrackerId();

    final public function getCSRFTokenForElementUpdate(): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(TRACKER_BASE_URL . '/?' . http_build_query(['func' => 'admin-formElements', 'tracker' => $this->getTrackerId()]));
    }
}
