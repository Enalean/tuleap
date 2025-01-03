<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Account_TimezonesCollection
{
    public function getTimezones(): array
    {
        return DateTimeZone::listIdentifiers() ?: [];
    }

    public function isValidTimezone(string $timezone): bool
    {
        return in_array($timezone, $this->getTimezones(), true);
    }

    /**
     * @return object[]
     */
    public function getTimezonePresenters(string $current_timezone): array
    {
        $list_of_presenters = [];
        foreach ($this->getTimezones() as $timezone) {
            $presenter              = new stdClass();
            $presenter->value       = $timezone;
            $presenter->label       = $timezone;
            $presenter->is_selected = $current_timezone === $timezone;

            $list_of_presenters[] = $presenter;
        }

        return $list_of_presenters;
    }
}
