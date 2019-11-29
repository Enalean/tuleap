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

use Mockery as M;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Project\XML\ConsistencyChecker;
use Tuleap\XML\ProjectXMLMerger;

class TemplateFactoryTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration, ForgeConfigSandbox;

    /**
     * @var TemplateFactory
     */
    private $factory;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ConsistencyChecker
     */
    private $consistency_checker;

    protected function setUp(): void
    {
        \ForgeConfig::set('codendi_cache_dir', vfsStream::setup('TemplateFactoryTest')->url());

        $this->consistency_checker = M::mock(ConsistencyChecker::class);
        $this->consistency_checker->shouldReceive('areAllServicesAvailable')->andReturnTrue()->byDefault();
        $this->factory  = new TemplateFactory(
            new GlyphFinder(new \EventManager()),
            new ProjectXMLMerger(),
            $this->consistency_checker,
            M::mock(TemplateDao::class),
        );
    }

    public function testItReturnsTemplates(): void
    {
        $templates = $this->factory->getValidTemplates();
        $this->assertCount(1, $templates);
        $this->assertInstanceOf(ScrumTemplate::class, $templates[0]);
    }

    public function testItReturnsScrumTemplate(): void
    {
        $template = $this->factory->getTemplate(ScrumTemplate::NAME);
        $this->assertInstanceOf(ScrumTemplate::class, $template);
    }

    public function testItReturnsScrumTemplateXML(): void
    {
        $template = $this->factory->getTemplate(ScrumTemplate::NAME);
        $xml = simplexml_load_string(file_get_contents($template->getXMLPath()));
        $this->assertNotEmpty($xml->services);
        $this->assertNotEmpty($xml->agiledashboard);
        $this->assertNotEmpty($xml->trackers);
    }

    public function testItThrowsAnExceptionWhenTemplateDoesntExist(): void
    {
        $this->expectException(InvalidXMLTemplateNameException::class);

        $this->factory->getTemplate('stuff');
    }

    public function testItDoesntReturnTheTemplatesWhoseServicesAreNotAvailable(): void
    {
        $this->consistency_checker->shouldReceive('areAllServicesAvailable')->andReturnFalse();

        $this->assertEmpty($this->factory->getValidTemplates());
    }

    public function testItDoesntReturnTheTemplateThatIsNotAvailable(): void
    {
        $this->consistency_checker->shouldReceive('areAllServicesAvailable')->andReturnFalse();

        $this->expectException(InvalidXMLTemplateNameException::class);

        $this->factory->getTemplate(ScrumTemplate::NAME);
    }
}
