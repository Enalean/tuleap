<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Dashboard\Widget;

class DashboardWidget
{
    public const string USER_PREF_IS_MINIMIZED_PREFIX = 'dashboard_widget_is_minimized_';

    private $id;
    private $name;
    private $content_id;
    private $column_id;
    private $rank;

    public function __construct(
        $id,
        $name,
        $content_id,
        $column_id,
        $rank,
    ) {
        $this->id         = $id;
        $this->name       = $name;
        $this->content_id = $content_id;
        $this->column_id  = $column_id;
        $this->rank       = $rank;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getContentId()
    {
        return $this->content_id;
    }

    /**
     * @return string
     */
    public function getColumnId()
    {
        return $this->column_id;
    }

    /**
     * @return string
     */
    public function getRank()
    {
        return $this->rank;
    }

    public static function getMinimizedPreferenceName(int $widget_id): string
    {
        return self::USER_PREF_IS_MINIMIZED_PREFIX . $widget_id;
    }
}
