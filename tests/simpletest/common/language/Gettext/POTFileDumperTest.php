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

namespace Tuleap\Language\Gettext;

class POTFileDumperTest extends \TuleapTestCase
{
    public function itDumpsAnEmptyPot()
    {
        $path       = $this->getTmpDir() .'/template.pot';
        $dumper     = new POTFileDumper();
        $collection = new POTEntryCollection('whatever');

        $dumper->dump($collection, $path);

        $this->assertEqual(strlen(file_get_contents($path)), 0);
    }

    public function itDumpsOneSingularEntry()
    {
        $path       = $this->getTmpDir() .'/template.pot';
        $dumper     = new POTFileDumper();
        $collection = new POTEntryCollection('whatever');
        $collection->add('whatever', new POTEntry('whenever, wherever', ''));

        $dumper->dump($collection, $path);

        $this->assertEqual(file_get_contents($path), <<<'POT'
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"

msgid "whenever, wherever"
msgstr ""


POT
        );
    }

    public function itDumpsTwoSingularEntries()
    {
        $path       = $this->getTmpDir() .'/template.pot';
        $dumper     = new POTFileDumper();
        $collection = new POTEntryCollection('whatever');
        $collection->add('whatever', new POTEntry('whenever, wherever', ''));
        $collection->add('whatever', new POTEntry('We are meant to be together', ''));

        $dumper->dump($collection, $path);

        $this->assertEqual(file_get_contents($path), <<<'POT'
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"

msgid "whenever, wherever"
msgstr ""

msgid "We are meant to be together"
msgstr ""


POT
        );
    }

    public function itDumpsPluralStrings()
    {
        $path       = $this->getTmpDir() .'/template.pot';
        $dumper     = new POTFileDumper();
        $collection = new POTEntryCollection('whatever');
        $collection->add('whatever', new POTEntry('singular', 'plural'));

        $dumper->dump($collection, $path);

        $this->assertEqual(file_get_contents($path), <<<'POT'
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"

msgid "singular"
msgid_plural "plural"
msgstr[0] ""
msgstr[1] ""


POT
        );
    }

    public function itShouldEscapeDoubleQuote()
    {
        $path       = $this->getTmpDir() .'/template.pot';
        $dumper     = new POTFileDumper();
        $collection = new POTEntryCollection('whatever');
        $collection->add('whatever', new POTEntry('" should be written escaped', ''));

        $dumper->dump($collection, $path);

        $this->assertEqual(file_get_contents($path), <<<'POT'
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"

msgid "\" should be written escaped"
msgstr ""


POT
        );
    }

    public function itShouldEscapeAntislash()
    {
        $path       = $this->getTmpDir() .'/template.pot';
        $dumper     = new POTFileDumper();
        $collection = new POTEntryCollection('whatever');
        $collection->add('whatever', new POTEntry('\\ should be written escaped', ''));

        $dumper->dump($collection, $path);

        $this->assertEqual(file_get_contents($path), <<<'POT'
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"

msgid "\\ should be written escaped"
msgstr ""


POT
        );
    }

    public function itShouldEscapeNewline()
    {
        $path       = $this->getTmpDir() .'/template.pot';
        $dumper     = new POTFileDumper();
        $collection = new POTEntryCollection('whatever');
        $collection->add('whatever', new POTEntry("\n should be written escaped", ''));

        $dumper->dump($collection, $path);

        $this->assertEqual(file_get_contents($path), <<<'POT'
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"

msgid "\n should be written escaped"
msgstr ""


POT
        );
    }
}
