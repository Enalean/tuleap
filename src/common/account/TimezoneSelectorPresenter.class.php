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

class Account_TimezoneSelectorPresenter
{
    /**
     * @var bool
     */
    public $has_one_selected;
    /**
     * @var string
     */
    public $placeholder;
    /**
     * @var array
     */
    public $list_of_timezones;

    /**
     * @param string $current_timezone falsy if no current timezone
     */
    public function __construct($current_timezone)
    {
        $this->has_one_selected = $current_timezone !== false;
        $this->placeholder      = _('Timezone');

        $collection = new Account_TimezonesCollection();

        $this->list_of_timezones = $collection->getTimezonePresenters($current_timezone);
    }
}
