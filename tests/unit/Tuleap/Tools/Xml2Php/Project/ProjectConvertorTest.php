<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Tools\Xml2Php\Project;

use PhpParser\PrettyPrinter;
use Psr\Log\NullLogger;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ProjectConvertorTest extends TestCase
{
    public function testItExportsABasicProject(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <project unix-name="lorem" full-name="Ipsum" description="Doloret" access="public">
            </project>
            EOS
        );

        $nodes = ProjectConvertor::buildFromXml($xml)->get(new NullLogger());

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '$project = new \Tuleap\Project\XML\XMLProject(\'lorem\', \'Ipsum\', \'Doloret\', \'public\');',
            $printer->prettyPrint($nodes)
        );
    }

    public function testItExportsAProjectWithServices(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <project unix-name="lorem" full-name="Ipsum" description="Doloret" access="public">
                <services>
                    <service shortname="docman" enabled="0"/>
                    <service shortname="git" enabled="1"/>
                </services>
            </project>
            EOS
        );

        $nodes = ProjectConvertor::buildFromXml($xml)->get(new NullLogger());

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '$project = (new \Tuleap\Project\XML\XMLProject(\'lorem\', \'Ipsum\', \'Doloret\', \'public\'))'
                . '->withService(\Tuleap\Project\Service\XML\XMLService::buildDisabled(\'docman\'))'
                . '->withService(\Tuleap\Project\Service\XML\XMLService::buildEnabled(\'git\'));',
            $printer->prettyPrint($nodes)
        );
    }

    public function testItExportsAProjectWithDashboards(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <project unix-name="lorem" full-name="Ipsum" description="Doloret" access="public">
                <dashboards>
                    <dashboard name="Team view">
                        <line layout= "two-columns-big-small">
                            <column>
                                <widget name="plugin_tracker_projectrenderer">
                                    <preference name="renderer">
                                        <reference name="id" REF="F17"></reference>
                                        <value name="title">All issues Priority Chart</value>
                                    </preference>
                                </widget>
                            </column>
                        </line>
                        <line>
                            <column>
                                <widget name="projectnote">
                                    <preference name="note">
                                        <value name="title">Note from the Tuleap team</value>
                                        <value name="content"><![CDATA[Welcome to your new project!]]></value>
                                    </preference>
                                </widget>
                                <widget name="projectmembers" />
                            </column>
                        </line>
                    </dashboard>
                </dashboards>
            </project>
            EOS
        );

        $nodes = ProjectConvertor::buildFromXml($xml)->get(new NullLogger());

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '$project = (new \Tuleap\Project\XML\XMLProject(\'lorem\', \'Ipsum\', \'Doloret\', \'public\'))'
                . '->withDashboard((new \Tuleap\Dashboard\XML\XMLDashboard(\'Team view\'))'
                .   '->withLine(\Tuleap\Dashboard\XML\XMLLine::withLayout(\'two-columns-big-small\')'
                .       '->withColumn((new \Tuleap\Dashboard\XML\XMLColumn())'
                .           '->withWidget((new \Tuleap\Widget\XML\XMLWidget(\'plugin_tracker_projectrenderer\'))'
                .               '->withPreference((new \Tuleap\Widget\XML\XMLPreference(\'renderer\'))'
                .                   '->withValue(\Tuleap\Widget\XML\XMLPreferenceValue::ref(\'id\', \'F17\'))'
                .                   '->withValue(\Tuleap\Widget\XML\XMLPreferenceValue::text(\'title\', \'All issues Priority Chart\'))'
                .               ')'
                .           ')'
                .       ')'
                .   ')'
                .   '->withLine(\Tuleap\Dashboard\XML\XMLLine::withDefaultLayout()'
                .       '->withColumn((new \Tuleap\Dashboard\XML\XMLColumn())'
                .           '->withWidget((new \Tuleap\Widget\XML\XMLWidget(\'projectnote\'))'
                .               '->withPreference((new \Tuleap\Widget\XML\XMLPreference(\'note\'))'
                .                   '->withValue(\Tuleap\Widget\XML\XMLPreferenceValue::text(\'title\', \'Note from the Tuleap team\'))'
                .                   '->withValue(\Tuleap\Widget\XML\XMLPreferenceValue::text(\'content\', \'Welcome to your new project!\'))'
                .               ')'
                .           ')'
                .           '->withWidget(new \Tuleap\Widget\XML\XMLWidget(\'projectmembers\'))'
                .       ')'
                .   ')'
                . ');',
            $printer->prettyPrint($nodes)
        );
    }
}
