<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Templating\Mustache;

final class MustacheEngineTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItContainsGettextHelpersToDoI18nDirectlyInTemplates(): void
    {
        $engine = new MustacheEngine(
            \Mockery::mock(\Mustache_Loader::class),
            \Mockery::spy(\Tuleap\Templating\TemplateCache::class)
        );

        $engine->getHelper('gettext');
        $engine->getHelper('ngettext');
        $engine->getHelper('dgettext');
        $engine->getHelper('dngettext');
        $engine->getHelper('glyph');

        $this->expectNotToPerformAssertions();
    }
}
