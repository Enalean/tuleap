<?php
/**
 * Copyright (c) Enalean, 2014-Present. All rights reserved
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

class BrowserIEDeprecated extends Browser //phpcs:ignore
{

    /** @var PFUser */
    private $user;

    public function __construct(PFUser $user)
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getDeprecatedMessage()
    {
        if ($this->user->getPreference(PFUser::PREFERENCE_DISABLE_IE7_WARNING)) {
            return '';
        }

        $warning_message = $GLOBALS['Language']->getText('include_browser', 'ie_deprecated');
        if ($this->user->isAnonymous()) {
            return $warning_message;
        }

        $url   = '/account/disable_legacy_browser_warning';
        $csrf  = new CSRFSynchronizerToken($url);
        $form  = '<form action="' . $url . '" method="POST" style="margin: 0">';
        $form .= $csrf->fetchHTMLInput();
        $form .= $warning_message;
        $form .= ' <button
                    type="submit"
                    class="btn btn-small btn-inverse"
                  >
                    ' . $GLOBALS['Language']->getText('include_browser', 'ie_deprecated_button') . '
                  </button>
                  </form>';

        return $form;
    }
}
