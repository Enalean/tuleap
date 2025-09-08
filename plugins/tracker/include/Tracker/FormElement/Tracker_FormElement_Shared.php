<?php
/**
 * Copyright (c) Enalean, 2012-present. All Rights Reserved.
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

use Tuleap\Tracker\FormElement\ProvideFactoryButtonInformation;
use Tuleap\Tracker\Tracker;

class Tracker_FormElement_Shared extends ProvideFactoryButtonInformation
{
    /**
     * @var Tracker
     */
    private $tracker;

    #[\Override]
    public static function getFactoryLabel()
    {
        return 'Shared field';
    }

    #[\Override]
    public static function getFactoryDescription()
    {
        return 'Use a field defined in another tracker';
    }

    #[\Override]
    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/-shared-field.png');
    }

    #[\Override]
    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-shared-field.png');
    }

    /**
     * @return bool say if the field is a unique one
     */
    #[\Override]
    public static function getFactoryUniqueField()
    {
        return false;
    }

    /**
     * @return Tracker
     */
    public function getTracker()
    {
        return $this->tracker;
    }

    public function setTracker(Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    public function accept(Tracker_FormElement_Visitor $visitor)
    {
        $visitor->visit($this);
    }

    public function __construct($id, private int $tracker_id, $parent_id, $name, $label, $description, $use_it, $scope, $required, $notifications, $rank)
    {
        $this->id            = $id;
        $this->parent_id     = $parent_id;
        $this->name          = $name;
        $this->label         = $label;
        $this->description   = $description;
        $this->use_it        = $use_it;
        $this->scope         = $scope;
        $this->required      = $required;
        $this->notifications = $notifications;
        $this->rank          = $rank;
    }

    #[\Override]
    public function getTrackerId(): int
    {
        return $this->tracker_id;
    }
}
