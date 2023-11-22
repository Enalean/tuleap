<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Date\Admin;

use Tuleap\Date\DateHelper;
use Tuleap\Date\RelativeDatesDisplayPreferencesSelectboxPresenter;
use Tuleap\Date\RelativeDatesDisplayPreferencesSelectboxPresenterBuilder;

class RelativeDatesDisplayAdminPresenter
{
    /**
     * @var string
     * @psalm-readonly
     */
    public $tlp_relative_date_component_purified;

    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;

    /**
     * @var RelativeDatesDisplayPreferencesSelectboxPresenter
     * @psalm-readonly
     */
    public $relative_dates_display_preference_sb_presenter;

    public function __construct(\PFUser $user, \CSRFSynchronizerToken $csrf_token, string $selected_preference)
    {
        $period_one_week     = 'P1W';
        $last_week_timestamp = (new \DateTimeImmutable())->sub(
            new \DateInterval($period_one_week)
        )->getTimestamp();

        $this->tlp_relative_date_component_purified = DateHelper::relativeDateInlineContext($last_week_timestamp, $user);
        $this->csrf_token                           = $csrf_token;

        $presenter_builder                                    = new RelativeDatesDisplayPreferencesSelectboxPresenterBuilder();
        $this->relative_dates_display_preference_sb_presenter = $presenter_builder->build($selected_preference);
    }
}
