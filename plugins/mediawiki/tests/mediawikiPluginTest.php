<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once 'bootstrap.php';

class mediawikiPluginTest extends TuleapTestCase {

    public function itReplacesTheTemplateNameInUrlByTheProjectName() {
        $template = array ('name' => 'toto');
        $link   = 'example.com/plugins/mediawiki/wiki/toto';
        $project = stub('Project')->getUnixName()->returns('yaya');

        $params = array(
            'template' => $template,
            'project'  => $project,
            'link'     => &$link
        );

        $mediawiki_plugin = new MediaWikiPlugin();
        $mediawiki_plugin->service_replace_template_name_in_link($params);
        $this->assertEqual('example.com/plugins/mediawiki/wiki/yaya', $link);
    }

}
?>
