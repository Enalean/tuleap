<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/templating/TemplateRendererFactory.class.php';

class TemplateRendererFactoryTest extends TuleapTestCase {
    public function setUp() {
        parent::setUp();
        ForgeConfig::store();
        
        $this->factory              = new TemplateRendererFactory();
        $this->plugin_templates_dir = dirname(__FILE__);
    }
    
    public function tearDown() {
        parent::tearDown();
        ForgeConfig::restore();
    }
    
    public function itBuildsAMustacheRendererWhenDebugModeIsDisabled() {
        ForgeConfig::set('DEBUG_MODE', false);
        $renderer = $this->factory->getRenderer($this->plugin_templates_dir);
        $this->assertIsA($renderer, 'MustacheRenderer');
        $this->assertNotA($renderer, 'MustacheDebugRenderer');
    }
    
    public function itBuildsAMustacheDebugRendererWhenDebugModeIsEnabled() {
        ForgeConfig::set('DEBUG_MODE', true);
        $renderer = $this->factory->getRenderer($this->plugin_templates_dir);
        $this->assertIsA($renderer, 'MustacheDebugRenderer');
    }
}
?>
