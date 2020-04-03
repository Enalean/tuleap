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

final class GettextHelperTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItTranslateASimpleString(): void
    {
        $helper = new GettextHelper(new GettextSectionContentTransformer());

        $this->assertEquals('A text', $helper->gettext('A text'));
        $this->assertEquals('A text', $helper->dgettext('domain | A text'));
    }

    public function testItReplacesArgumentsIfTheyAreGiven(): void
    {
        $helper = new GettextHelper(new GettextSectionContentTransformer());

        $this->assertEquals('A useful text', $helper->gettext('A %s text|useful'));
        $this->assertEquals('A useful text', $helper->dgettext('domain | A %s text|useful'));
    }

    public function testItTrimsTextAndArguments(): void
    {
        $helper = new GettextHelper(new GettextSectionContentTransformer());

        $this->assertEquals('A useful text', $helper->gettext(' A %s text | useful '));
        $this->assertEquals('A useful text', $helper->dgettext(' domain | A %s text | useful '));
    }

    public function testItReturnsSingular(): void
    {
        $helper = new GettextHelper(new GettextSectionContentTransformer());
        $lambda = \Mockery::mock(\Mustache_LambdaHelper::class)->shouldReceive('render')->with('{{ nb }}')->andReturns('1')->getMock();

        $this->assertEquals(
            'A text',
            $helper->ngettext('A text | Several texts | {{ nb }}', $lambda),
        );
        $this->assertEquals(
            'A text',
            $helper->dngettext('domain | A text | Several texts | {{ nb }}', $lambda),
        );
    }

    public function testItReturnsPlural(): void
    {
        $helper = new GettextHelper(new GettextSectionContentTransformer());
        $lambda = \Mockery::mock(\Mustache_LambdaHelper::class)->shouldReceive('render')->with('{{ nb }}')->andReturns('2')->getMock();

        $this->assertEquals(
            'Several texts',
            $helper->ngettext('A text | Several texts | {{ nb }}', $lambda),
        );
        $this->assertEquals(
            'Several texts',
            $helper->dngettext('domain | A text | Several texts | {{ nb }}', $lambda),
        );
    }

    public function testItUsesNbAsArgumentByDefault(): void
    {
        $helper = new GettextHelper(new GettextSectionContentTransformer());
        $lambda = \Mockery::mock(\Mustache_LambdaHelper::class)->shouldReceive('render')->with('{{ nb }}')->andReturns('2')->getMock();

        $this->assertEquals(
            '2 texts',
            $helper->ngettext('%d text | %d texts | {{ nb }}', $lambda),
        );
        $this->assertEquals(
            '2 texts',
            $helper->dngettext('domain | %d text | %d texts | {{ nb }}', $lambda),
        );
    }

    public function testItDoesNotUseNbAsArgumentIfDevelopersGaveExtraArguments(): void
    {
        $helper = new GettextHelper(new GettextSectionContentTransformer());
        $lambda = \Mockery::mock(\Mustache_LambdaHelper::class)->shouldReceive('render')->with('{{ nb }}')->andReturns('2')->getMock();

        $this->assertEquals(
            'Users "{{ name }}"',
            $helper->ngettext('User "%s" | Users "%s" | {{ nb }} | {{ name }}', $lambda),
        );
        $this->assertEquals(
            'Users "{{ name }}"',
            $helper->dngettext('domain | User "%s" | Users "%s" | {{ nb }} | {{ name }}', $lambda),
        );
    }

    public function testItFailsIfStringIsEmpty(): void
    {
        $this->expectException(\Tuleap\Templating\Mustache\InvalidGettextStringException::class);

        $helper = new GettextHelper(new GettextSectionContentTransformer());
        $helper->gettext('');
    }

    public function testItFailsIfThereIsOnlyTheDomain(): void
    {
        $this->expectException(\Tuleap\Templating\Mustache\InvalidGettextStringException::class);

        $helper = new GettextHelper(new GettextSectionContentTransformer());
        $helper->dgettext('domain');
        $helper->dgettext('domain|');
    }

    public function testItFailsIfNbIsNotGiven(): void
    {
        $this->expectException(\Tuleap\Templating\Mustache\InvalidGettextStringException::class);

        $lambda = \Mockery::mock(\Mustache_LambdaHelper::class)->shouldReceive('render')->with('{{ nb }}')->andReturns('2')->getMock();

        $helper = new GettextHelper(new GettextSectionContentTransformer());
        $helper->ngettext('%d text | %d texts', $lambda);
        $helper->dngettext('domain | %d text | %d texts', $lambda);
    }
}
