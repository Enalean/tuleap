<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Cardwall\Semantic;

use Tuleap\Tracker\Semantic\IDuplicateSemantic;

class BackgroundColorSemanticFactory implements IDuplicateSemantic
{
    /**
     * @var BackgroundColorDao
     */
    private $background_color_dao;

    public function __construct(BackgroundColorDao $background_color_dao)
    {
        $this->background_color_dao = $background_color_dao;
    }

    public function duplicate($from_tracker_id, $to_tracker_id, array $field_mapping)
    {
        $old_background_field = $this->background_color_dao->searchBackgroundColor($from_tracker_id);
        if (! $old_background_field) {
            return;
        }

        foreach ($field_mapping as $mapping) {
            if ((int) $mapping['from'] === $old_background_field) {
                $to_field_id = $mapping['to'];
                $this->background_color_dao->save($to_tracker_id, $to_field_id);
            }
        }
    }
}
