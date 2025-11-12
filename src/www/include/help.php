<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
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

function help_button(string $type, string $prompt = '[?]')
{
    $purifier = Codendi_HTMLPurifier::instance();
    $lang     = \Tuleap\HTTPRequest::instance()->getCurrentUser()->getShortLocale();
    $href     = '/doc/' . urlencode($lang) . '/user-guide/' . $type;
    return '<a data-help-window href="' . $purifier->purify($href) . '"><b>' . $purifier->purify($prompt) . '</b></a>';
}
