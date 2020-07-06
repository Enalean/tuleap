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

use PFUser;
use Tracker;

class SemanticCardPresenter
{
    /**
     * @var BackgroundColorSelectorPresenter
     */
    public $background_color_presenter;
    /**
     * @var FieldPresenter
     */
    public $fields_presenter;
    /**
     * @var string
     */
    public $back_url;
    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;
    /**
     * @var string
     */
    public $semantic_url;
    /**
     * @var string
     */
    public $tracker_shortname;
    /**
     * @var array
     */
    public $card_preview;

    /**
     * @var bool
     */
    public $user_has_accessibility_mode;

    public function __construct(
        FieldPresenter $fields_presenter,
        BackgroundColorSelectorPresenter $background_color_presenter,
        Tracker $tracker,
        \CSRFSynchronizerToken $token,
        $form_url,
        array $card_preview,
        PFUser $user
    ) {
        $this->background_color_presenter = $background_color_presenter;
        $this->fields_presenter           = $fields_presenter;
        $this->csrf_token                 = $token;
        $this->semantic_url               = $form_url;
        $this->tracker_shortname          = $tracker->getItemName();
        $this->card_preview               = $card_preview;
        $this->back_url                   = TRACKER_BASE_URL . '/?' . http_build_query(
            [
                'tracker' => $tracker->getId(),
                'func'    => 'admin-semantic'
            ]
        );

        $this->user_has_accessibility_mode = (bool) $user->getPreference(PFUser::ACCESSIBILITY_MODE);
    }
}
