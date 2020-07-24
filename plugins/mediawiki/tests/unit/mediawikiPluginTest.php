<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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

namespace Tuleap\Mediawiki;

use MediaWikiPlugin;
use Mockery;
use PHPUnit\Framework\TestCase;

require_once 'bootstrap.php';

class mediawikiPluginTest extends TestCase //phpcs:ignore
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItReplacesTheTemplateNameInUrlByTheProjectName()
    {
        $template =  ['name' => 'toto'];
        $link     = 'example.com/plugins/mediawiki/wiki/toto';
        $project  = Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->andReturn('yaya');

        $params = [
            'template' => $template,
            'project'  => $project,
            'link'     => &$link
        ];

        $mediawiki_plugin = new MediaWikiPlugin();
        $mediawiki_plugin->service_replace_template_name_in_link($params);

        $this->assertSame('example.com/plugins/mediawiki/wiki/yaya', $link);
    }
}
