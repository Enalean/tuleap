<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Dashboard\Widget\Add;

use Widget;

class WidgetPresenter
{
    public $id;
    public $name;
    public $description;
    public $image_source;
    public $image_title;
    public $has_image;
    public $is_used;
    public $can_be_added_from_widget_list;
    public $already_used_label;
    public $need_configuration;
    public $configurations;

    public function __construct(Widget $widget, $is_used)
    {
        $this->id                            = $widget->getId();
        $this->name                          = $widget->getTitle();
        $this->description                   = $widget->getDescription();
        $this->image_source                  = $widget->getImageSource();
        $this->image_title                   = $widget->getImageTitle();
        $this->has_image                     = ! empty($this->image_source);
        $this->is_used                       = $is_used;
        $this->configurations                = $widget->getInstallPreferences();
        $this->can_be_added_from_widget_list = $widget->canBeAddedFromWidgetList();
        $this->need_configuration            = ! empty($this->configurations);
        $this->already_used_label            = _('Already used');
    }
}
