<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
declare(strict_types=1);

namespace Tuleap\HelpDropdown;

use PFUser;

class HelpDropdownPresenterBuilder
{
    public function build(PFUser $current_user): HelpDropdownPresenter
    {
        $documentation = "/doc/" . urlencode($current_user->getShortLocale()) . "/";

        $main_items = [
            $link_get_help = new HelpLinkPresenter(
                dgettext(
                    'tuleap-core',
                    'Get help'
                ),
                "/help/"
            ),
            $link_documentation = new HelpLinkPresenter(
                dgettext(
                    'tuleap-core',
                    'Documentation'
                ),
                $documentation
            )
        ];

        $footer_items = [
            $link_api = new HelpLinkPresenter(
                dgettext(
                    'tuleap-core',
                    'API'
                ),
                "/help/api.php"
            ),
            $link_terms = new HelpLinkPresenter(
                dgettext(
                    'tuleap-core',
                    'Terms'
                ),
                "/tos/tos.php"
            ),
            $link_contact = new HelpLinkPresenter(
                dgettext(
                    'tuleap-core',
                    'Contact'
                ),
                "/contact.php"
            )
        ];

        return new HelpDropdownPresenter(
            $main_items,
            $footer_items
        );
    }
}
