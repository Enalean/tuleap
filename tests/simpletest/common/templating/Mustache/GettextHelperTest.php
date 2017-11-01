<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

class GettextHelperTest extends \TuleapTestCase
{
    public function itTranslateASimpleString()
    {
        $helper = new GettextHelper();

        $this->assertEqual($helper->gettext('A text'), 'A text');
        $this->assertEqual($helper->dgettext('domain | A text'), 'A text');
    }

    public function itReplacesArgumentsIfTheyAreGiven()
    {
        $helper = new GettextHelper();

        $this->assertEqual($helper->gettext('A %s text|usefull'), 'A usefull text');
        $this->assertEqual($helper->dgettext('domain | A %s text|usefull'), 'A usefull text');
    }

    public function itTrimsTextAndArguments()
    {
        $helper = new GettextHelper();

        $this->assertEqual($helper->gettext(' A %s text | usefull '), 'A usefull text');
        $this->assertEqual($helper->dgettext(' domain | A %s text | usefull '), 'A usefull text');
    }

    public function itReturnsSingular()
    {
        $helper = new GettextHelper();
        $lambda = stub('Mustache_LambdaHelper')->render('{{ nb }}')->returns('1');

        $this->assertEqual(
            $helper->ngettext('A text | Several texts | {{ nb }}', $lambda),
            'A text'
        );
        $this->assertEqual(
            $helper->dngettext('domain | A text | Several texts | {{ nb }}', $lambda),
            'A text'
        );
    }

    public function itReturnsPlural()
    {
        $helper = new GettextHelper();
        $lambda = stub('Mustache_LambdaHelper')->render('{{ nb }}')->returns('2');

        $this->assertEqual(
            $helper->ngettext('A text | Several texts | {{ nb }}', $lambda),
            'Several texts'
        );
        $this->assertEqual(
            $helper->dngettext('domain | A text | Several texts | {{ nb }}', $lambda),
            'Several texts'
        );
    }

    public function itUsesNbAsArgumentByDefault()
    {
        $helper = new GettextHelper();
        $lambda = stub('Mustache_LambdaHelper')->render('{{ nb }}')->returns('2');

        $this->assertEqual(
            $helper->ngettext('%d text | %d texts | {{ nb }}', $lambda),
            '2 texts'
        );
        $this->assertEqual(
            $helper->dngettext('domain | %d text | %d texts | {{ nb }}', $lambda),
            '2 texts'
        );
    }

    public function itDoesNotUseNbAsArgumentIfDevelopersGaveExtraArguments()
    {
        $helper = new GettextHelper();
        $lambda = stub('Mustache_LambdaHelper')->render('{{ nb }}')->returns('2');

        $this->assertEqual(
            $helper->ngettext('User "%s" | Users "%s" | {{ nb }} | {{ name }}', $lambda),
            'Users "{{ name }}"'
        );
        $this->assertEqual(
            $helper->dngettext('domain | User "%s" | Users "%s" | {{ nb }} | {{ name }}', $lambda),
            'Users "{{ name }}"'
        );
    }

    public function itFailsIfStringIsEmpty()
    {
        $this->expectException('\Tuleap\Templating\Mustache\InvalidGettextStringException');

        $helper = new GettextHelper();
        $helper->gettext('');
    }

    public function itFailsIfThereIsOnlyTheDomain()
    {
        $this->expectException('\Tuleap\Templating\Mustache\InvalidGettextStringException');

        $helper = new GettextHelper();
        $helper->dgettext('domain');
        $helper->dgettext('domain|');
    }

    public function itFailsIfNbIsNotGiven()
    {
        $this->expectException('\Tuleap\Templating\Mustache\InvalidGettextStringException');

        $lambda = stub('Mustache_LambdaHelper')->render('{{ nb }}')->returns('2');

        $helper = new GettextHelper();
        $helper->ngettext('%d text | %d texts', $lambda);
        $helper->dngettext('domain | %d text | %d texts', $lambda);
    }
}
