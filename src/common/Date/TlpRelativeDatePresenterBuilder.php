<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Date;

class TlpRelativeDatePresenterBuilder
{
    private const POSITION_RIGHT = 'right';
    private const POSITION_TOP   = 'top';

    private const WITH_TIME    = true;
    private const WITHOUT_TIME = false;

    public function getTlpRelativeDatePresenterInBlockContext(\DateTimeImmutable $date, \PFUser $user): TlpRelativeDatePresenter
    {
        return $this->getPresenter($date, $user, self::POSITION_TOP, self::WITH_TIME);
    }

    public function getTlpRelativeDatePresenterInInlineContext(\DateTimeImmutable $date, \PFUser $user): TlpRelativeDatePresenter
    {
        return $this->getPresenter($date, $user, self::POSITION_RIGHT, self::WITH_TIME);
    }

    public function getTlpRelativeDatePresenterInInlineContextWithoutTime(\DateTimeImmutable $date, \PFUser $user): TlpRelativeDatePresenter
    {
        return $this->getPresenter($date, $user, self::POSITION_RIGHT, self::WITHOUT_TIME);
    }

    private function getPresenter(\DateTimeImmutable $date, \PFUser $user, string $position, bool $with_time): TlpRelativeDatePresenter
    {
        switch ($user->getPreference(DateHelper::PREFERENCE_NAME)) {
            case DateHelper::PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN:
                $preference = "absolute";
                $placement  = $position;
                break;
            case DateHelper::PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP:
                $preference = "absolute";
                $placement  = "tooltip";
                break;
            case DateHelper::PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN:
                $preference = "relative";
                $placement  = $position;
                break;
            case DateHelper::PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP:
                $preference = "relative";
                $placement  = "tooltip";
                break;
            default:
                $default_display = DefaultRelativeDatesDisplayPreferenceRetriever::getDefaultPlacementAndPreference($position);
                $preference      = $default_display->getPreference();
                $placement       = $default_display->getPlacement();
        }

        $time = $date->getTimestamp();

        return new TlpRelativeDatePresenter(
            date('c', $time),
            date(
                $with_time
                    ? $GLOBALS['Language']->getText('system', 'datefmt')
                    : $GLOBALS['Language']->getText('system', 'datefmt_short'),
                $time
            ),
            $placement,
            $preference,
            $user->getLocale(),
        );
    }
}
