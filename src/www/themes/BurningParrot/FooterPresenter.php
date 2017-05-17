<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

namespace Tuleap\Theme\BurningParrot;

class FooterPresenter
{
    public $javascript_in_footer = array();
    public $tuleap_version;
    public $is_footer_shown;
    public $footer;

    public function __construct(
        array $javascript_in_footer,
        $is_footer_shown,
        $tuleap_version
    ) {
        foreach ($javascript_in_footer as $javascript) {
            if (isset($javascript['file'])) {
                $content    = $javascript['file'];
                $is_snippet = false;
            } else {
                $content    = $javascript['snippet'];
                $is_snippet = true;
            }
            $this->javascript_in_footer[] = new JavascriptPresenter($content, $is_snippet);
        }
        $this->tuleap_version  = $tuleap_version;
        $this->is_footer_shown = $is_footer_shown;
        $this->footer          = $this->getFooter();
    }

    private function getFooter()
    {
        $version = $this->tuleap_version;
        ob_start();
        include($GLOBALS['Language']->getContent('layout/footer'));
        return ob_get_clean();
    }
}
