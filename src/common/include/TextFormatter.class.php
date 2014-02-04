<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class TextFormatter {

    public function format($text) {
        $formatted_content  = $text;
        $has_been_formatted = false;

        EventManager::instance()->processEvent(
            Event::FORMAT_TEXT,
            array(
                'content'            => $text,
                'formatted_content'  => &$formatted_content,
                'has_been_formatted' => &$has_been_formatted
            )
        );

        if (! $has_been_formatted) {
            return Codendi_HTMLPurifier::instance()->purify($formatted_content, CODENDI_PURIFIER_BASIC);
        }

        return Codendi_HTMLPurifier::instance()->purify($formatted_content, CODENDI_PURIFIER_FULL);
    }
}