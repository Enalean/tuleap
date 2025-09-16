<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
 * A swimline for the tracker's cardwall renderer
 */
class Cardwall_SwimlineTrackerRenderer extends Cardwall_Swimline // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const string FAKE_SWIMLINE_ID_FOR_TRACKER_RENDERER = 'FAKE_SWIMLINE_ID_FOR_TRACKER_RENDERER';

    /**
     * @var int
     */
    public $swimline_id = self::FAKE_SWIMLINE_ID_FOR_TRACKER_RENDERER;

    public function __construct(array $cells)
    {
        $this->cells = $cells;
    }

    #[\Override]
    public function getCardPresenter()
    {
        return null;
    }
}
