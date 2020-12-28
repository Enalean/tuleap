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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Reference\Presenters;

/**
 * @psalm-immutable
 */
class CrossReferenceLinkPresenter
{
    /**
     * @var string
     */
    public $id;
    /**
     * @var string
     */
    public $ref;
    /**
     * @var string
     */
    public $url;
    /**
     * @var null|string
     */
    public $params;
    /**
     * @var string
     */
    public $message_to_delete;
    /**
     * @var string
     */
    public $icon_to_delete;
    /**
     * @var bool
     */
    public $display_comma;
    /**
     * @var string
     */
    public $icon_to_delete_message;

    public function __construct(string $id, string $ref, string $url, ?string $params, bool $display_comma)
    {
        $this->id                     = $id;
        $this->ref                    = $ref;
        $this->url                    = $url;
        $this->params                 = $params;
        $this->display_comma          = $display_comma;
        $this->message_to_delete      = addslashes($GLOBALS['Language']->getText('cross_ref_fact_include', 'confirm_delete'));
        $this->icon_to_delete_message = $GLOBALS['Language']->getText('cross_ref_fact_include', 'delete');
    }
}
