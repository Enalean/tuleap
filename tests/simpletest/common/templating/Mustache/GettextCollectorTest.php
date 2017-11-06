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

use Tuleap\Language\Gettext\POTEntry;

class GettextCollectorTest extends \TuleapTestCase
{
    public function itCollectsGettext()
    {
        $collector = new GettextCollector(new GettextSectionContentTransformer());
        $entries = mock('Tuleap\Language\Gettext\POTEntryCollection');

        expect($entries)->add('tuleap-core', new POTEntry('whatever', ''))->once();

        $collector->collectEntry('gettext', 'whatever', $entries);
    }

    public function itCollectsNgettext()
    {
        $collector = new GettextCollector(new GettextSectionContentTransformer());
        $entries = mock('Tuleap\Language\Gettext\POTEntryCollection');

        expect($entries)->add('tuleap-core', new POTEntry('singular', 'plural'))->once();

        $collector->collectEntry('ngettext', 'singular | plural', $entries);
    }

    public function itCollectsDgettext()
    {
        $collector = new GettextCollector(new GettextSectionContentTransformer());
        $entries = mock('Tuleap\Language\Gettext\POTEntryCollection');

        expect($entries)->add('mydomain', new POTEntry('whatever', ''))->once();

        $collector->collectEntry('dgettext', 'mydomain | whatever', $entries);
    }

    public function itCollectsDngettext()
    {
        $collector = new GettextCollector(new GettextSectionContentTransformer());
        $entries = mock('Tuleap\Language\Gettext\POTEntryCollection');

        expect($entries)->add('mydomain', new POTEntry('singular', 'plural'))->once();

        $collector->collectEntry('dngettext', 'mydomain | singular | plural', $entries);
    }

    public function itRaisesAnExceptionIfSectionNameIsUnknown()
    {
        $collector = new GettextCollector(new GettextSectionContentTransformer());
        $entries = mock('Tuleap\Language\Gettext\POTEntryCollection');

        $this->expectException('RuntimeException');

        $collector->collectEntry('not-gettext', 'whatever', $entries);
    }
}
