<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\Registration\Template;

use PHPUnit\Framework\TestCase;
use Tuleap\Glyph\GlyphFinder;

class TemplateFactoryTest extends TestCase
{

    private $factory;

    protected function setUp(): void
    {
        $this->factory = new TemplateFactory(new GlyphFinder(new \EventManager()));
    }

    public function testItReturnsTemplates(): void
    {
        $templates = $this->factory->getTemplates();
        $this->assertCount(1, $templates);
        $this->assertInstanceOf(ScrumTemplate::class, $templates[0]);
    }

    public function testItReturnsScrumTemplate(): void
    {
        $template = $this->factory->getTemplate(ScrumTemplate::NAME);
        $this->assertInstanceOf(ScrumTemplate::class, $template);
    }

    public function testItThrowsAnExceptionWhenTemplateDoesntExist(): void
    {
        $this->expectException(InvalidTemplateException::class);

        $this->factory->getTemplate('stuff');
    }
}
