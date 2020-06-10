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

class FieldPresenter
{
    /**
     * @var array
     */
    public $card_fields;
    /**
     * @var bool
     */
    public $has_field;
    /**
     * @var string
     */
    public $delete_image;
    /**
     * @var array
     */
    public $possible_fields;
    /**
     * @var bool
     */
    public $has_at_least_one_field_selectabe_for_card;

    public function __construct(
        array $fields,
        array $possible_fields
    ) {
        $this->card_fields                               = $fields;
        $this->has_field                                 = count($fields) > 0;
        $this->possible_fields                           = $possible_fields;
        $this->has_at_least_one_field_selectabe_for_card = count($this->possible_fields) > 0;
        $this->delete_image                              = $GLOBALS['HTML']->getimage(
            'ic/cross.png',
            [
                'alt' => dgettext('tuleap-cardwall', 'The following fields are added to the cards:')
            ]
        );
    }
}
