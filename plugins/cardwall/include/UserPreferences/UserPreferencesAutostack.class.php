<?php
/**
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

abstract class Cardwall_UserPreferences_UserPreferencesAutostack
{
    public const DONT_STACK = 'no_stack';
    public const STACK      = 'auto_stack';

    /**
     * @var PFUser
     */
    protected $user;

    public function __construct(PFUser $user)
    {
        $this->user = $user;
    }

    abstract public function getName(Cardwall_Column $column);

    public function setColumnPreference(Cardwall_Column $column)
    {
        $column->setAutostack($this->isColumnAutoStacked($column))
            ->setAutostackPreference($this->getName($column));
    }

    public function forceColumnAutoStacked(Cardwall_Column $column)
    {
        $preference_name = $this->getName($column);
        $this->user->setPreference($preference_name, self::STACK);
        $column->setAutostack(self::STACK)
            ->setAutostackPreference($preference_name);
    }

    public function columnHasPreference(Cardwall_Column $column)
    {
        return ($this->user->getPreference($this->getName($column)) != false);
    }

    private function isColumnAutoStacked(Cardwall_Column $column)
    {
        return ($this->getValue($column) == self::STACK);
    }

    private function getValue(Cardwall_Column $column)
    {
        return $this->user->getPreference($this->getName($column));
    }
}
