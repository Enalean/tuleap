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

class markdownPlugin extends Plugin {

    public function getPluginInfo() {
        if (! is_a($this->pluginInfo, 'MarkdownPluginInfo')) {
            $this->pluginInfo = new MarkdownPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getHooksAndCallbacks() {
        $this->addHook(Event::FORMAT_TEXT);
        return parent::getHooksAndCallbacks();
    }

    /**
     * @see Event::FORMAT_TEXT
     */
    public function format_text(array $params) {
        require_once '/usr/share/php-markdown/Michelf/Markdown.inc.php';
        $params['formatted_content'] = \Michelf\Markdown::defaultTransform($params['content']);
        $params['has_been_formatted'] = true;
    }
}