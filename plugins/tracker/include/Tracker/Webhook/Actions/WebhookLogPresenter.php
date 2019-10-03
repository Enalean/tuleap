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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Webhook\Actions;

class WebhookLogPresenter
{

    /**
     * @var string
     */
    public $time;

    /**
     * @var string
     */
    public $status_message;

    /**
     * @var bool
     */
    public $status_ok;

    public function __construct($created_on, $status_message)
    {
        $this->time           = $this->formatCreatedOnDate($created_on);
        $this->status_message = $status_message;
        $this->status_ok      = $this->isStatusOk($status_message);
    }

    private function formatCreatedOnDate($created_on)
    {
        return format_date($GLOBALS['Language']->getText('system', 'datefmt'), $created_on);
    }

    private function isStatusOk($status_message)
    {
        return $status_message[0] === '2';
    }
}
