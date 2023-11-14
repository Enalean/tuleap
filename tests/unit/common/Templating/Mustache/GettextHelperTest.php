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

use PHPUnit\Framework\MockObject\MockObject;

final class GettextHelperTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItTranslateASimpleString(): void
    {
        $helper = new GettextHelper(new GettextSectionContentTransformer());

        self::assertEquals('A text', $helper->gettext('A text'));
        self::assertEquals('A text', $helper->dgettext('domain | A text'));
    }

    public function testItReplacesArgumentsIfTheyAreGiven(): void
    {
        $helper = new GettextHelper(new GettextSectionContentTransformer());

        self::assertEquals('A useful text', $helper->gettext('A %s text|useful'));
        self::assertEquals('A useful text', $helper->dgettext('domain | A %s text|useful'));
    }

    public function testItTrimsTextAndArguments(): void
    {
        $helper = new GettextHelper(new GettextSectionContentTransformer());

        self::assertEquals('A useful text', $helper->gettext(' A %s text | useful '));
        self::assertEquals('A useful text', $helper->dgettext(' domain | A %s text | useful '));
    }

    public function testItReturnsSingular(): void
    {
        $helper = new GettextHelper(new GettextSectionContentTransformer());
        $lambda = $this->buildLambda('1');

        self::assertEquals(
            'A text',
            $helper->ngettext('A text | Several texts | {{ nb }}', $lambda),
        );
        self::assertEquals(
            'A text',
            $helper->dngettext('domain | A text | Several texts | {{ nb }}', $lambda),
        );
    }

    public function testItReturnsPlural(): void
    {
        $helper = new GettextHelper(new GettextSectionContentTransformer());
        $lambda = $this->buildLambda('2');

        self::assertEquals(
            'Several texts',
            $helper->ngettext('A text | Several texts | {{ nb }}', $lambda),
        );
        self::assertEquals(
            'Several texts',
            $helper->dngettext('domain | A text | Several texts | {{ nb }}', $lambda),
        );
    }

    public function testItUsesNbAsArgumentByDefault(): void
    {
        $helper = new GettextHelper(new GettextSectionContentTransformer());
        $lambda = $this->buildLambda('2');

        self::assertEquals(
            '2 texts',
            $helper->ngettext('%d text | %d texts | {{ nb }}', $lambda),
        );
        self::assertEquals(
            '2 texts',
            $helper->dngettext('domain | %d text | %d texts | {{ nb }}', $lambda),
        );
    }

    public function testItDoesNotUseNbAsArgumentIfDevelopersGaveExtraArguments(): void
    {
        $helper = new GettextHelper(new GettextSectionContentTransformer());
        $lambda = $this->buildLambda('2');

        self::assertEquals(
            'Users "{{ name }}"',
            $helper->ngettext('User "%s" | Users "%s" | {{ nb }} | {{ name }}', $lambda),
        );
        self::assertEquals(
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

        $lambda = $this->buildLambda('2');

        $helper = new GettextHelper(new GettextSectionContentTransformer());
        $helper->ngettext('%d text | %d texts', $lambda);
        $helper->dngettext('domain | %d text | %d texts', $lambda);
    }

    private function buildLambda(string $value): \Mustache_LambdaHelper&MockObject
    {
        $lambda = $this->createMock(\Mustache_LambdaHelper::class);
        $lambda->method('render')->with('{{ nb }}')->willReturn($value);

        return $lambda;
    }
}
