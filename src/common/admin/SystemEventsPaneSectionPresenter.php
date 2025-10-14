<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Admin\SystemEvents;

class HomepagePaneSectionPresenter
{
    public const string FLAG_SUCCESS = 'success';
    public const string FLAG_WARNING = 'warning';
    public const string FLAG_ERROR   = 'error';

    public $title;
    public $name;
    public $flag_type;
    public $new_count;
    public $running_count;
    public $done_count;
    public $warning_count;
    public $error_count;
    public $has_running;
    public $has_warning;
    public $has_error;
    public $has_new;
    public $has_warning_or_error;

    public function __construct(
        $title,
        $name,
        $new_count,
        $running_count,
        $done_count,
        $warning_count,
        $error_count,
    ) {
        $this->title         = $title;
        $this->name          = $name;
        $this->flag_type     = $error_count ? self::FLAG_ERROR : ($warning_count ? self::FLAG_WARNING : self::FLAG_SUCCESS);
        $this->new_count     = $new_count;
        $this->running_count = $running_count;
        $this->done_count    = $done_count;
        $this->warning_count = $warning_count;
        $this->error_count   = $error_count;

        $this->has_new                   = $this->new_count > 0;
        $this->has_running               = $this->running_count > 0;
        $this->has_warning               = $this->warning_count > 0;
        $this->has_error                 = $this->error_count > 0;
        $this->has_warning_or_error      = $this->has_warning || $this->has_error;
        $this->has_warning_without_error = $this->has_warning && ! $this->has_error;

        $this->new_label_tooltip     = _('New events');
        $this->running_label_tooltip = _('Running events');
        $this->done_label_tooltip    = _('Done events');
        $this->warning_label_tooltip = _('Events in warning');
        $this->error_label_tooltip   = _('Events in error');
        $this->warning_label         = _('Warnings');
        $this->error_label           = _('Errors');
    }
}
