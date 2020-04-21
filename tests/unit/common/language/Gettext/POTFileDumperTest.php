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

declare(strict_types=1);

namespace Tuleap\Language\Gettext;

use org\bovigo\vfs\vfsStream;

final class POTFileDumperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    private $tmp_dir;

    protected function setUp(): void
    {
        $this->tmp_dir = vfsStream::setup()->url();
    }

    public function testItDumpsAnEmptyPot(): void
    {
        $path       = $this->tmp_dir . '/template.pot';
        $dumper     = new POTFileDumper();
        $collection = new POTEntryCollection('whatever');

        $dumper->dump($collection, $path);

        $this->assertEquals(strlen(file_get_contents($path)), 0);
    }

    public function testItDumpsOneSingularEntry(): void
    {
        $path       = $this->tmp_dir . '/template.pot';
        $dumper     = new POTFileDumper();
        $collection = new POTEntryCollection('whatever');
        $collection->add('whatever', new POTEntry('whenever, wherever', ''));

        $dumper->dump($collection, $path);

        $this->assertEquals(
            <<<'POT'
            msgid ""
            msgstr ""
            "Content-Type: text/plain; charset=UTF-8\n"

            msgid "whenever, wherever"
            msgstr ""


            POT,
            file_get_contents($path)
        );
    }

    public function testItDumpsTwoSingularEntries(): void
    {
        $path       = $this->tmp_dir . '/template.pot';
        $dumper     = new POTFileDumper();
        $collection = new POTEntryCollection('whatever');
        $collection->add('whatever', new POTEntry('whenever, wherever', ''));
        $collection->add('whatever', new POTEntry('We are meant to be together', ''));

        $dumper->dump($collection, $path);

        $this->assertEquals(
            <<<'POT'
            msgid ""
            msgstr ""
            "Content-Type: text/plain; charset=UTF-8\n"

            msgid "whenever, wherever"
            msgstr ""

            msgid "We are meant to be together"
            msgstr ""


            POT,
            file_get_contents($path)
        );
    }

    public function testItDumpsPluralStrings(): void
    {
        $path       = $this->tmp_dir . '/template.pot';
        $dumper     = new POTFileDumper();
        $collection = new POTEntryCollection('whatever');
        $collection->add('whatever', new POTEntry('singular', 'plural'));

        $dumper->dump($collection, $path);

        $this->assertEquals(
            <<<'POT'
            msgid ""
            msgstr ""
            "Content-Type: text/plain; charset=UTF-8\n"

            msgid "singular"
            msgid_plural "plural"
            msgstr[0] ""
            msgstr[1] ""


            POT,
            file_get_contents($path)
        );
    }

    public function testItShouldEscapeDoubleQuote(): void
    {
        $path       = $this->tmp_dir . '/template.pot';
        $dumper     = new POTFileDumper();
        $collection = new POTEntryCollection('whatever');
        $collection->add('whatever', new POTEntry('" should be written escaped', ''));

        $dumper->dump($collection, $path);

        $this->assertEquals(
            <<<'POT'
            msgid ""
            msgstr ""
            "Content-Type: text/plain; charset=UTF-8\n"

            msgid "\" should be written escaped"
            msgstr ""


            POT,
            file_get_contents($path)
        );
    }

    public function testItShouldEscapeAntislash(): void
    {
        $path       = $this->tmp_dir . '/template.pot';
        $dumper     = new POTFileDumper();
        $collection = new POTEntryCollection('whatever');
        $collection->add('whatever', new POTEntry('\\ should be written escaped', ''));

        $dumper->dump($collection, $path);

        $this->assertEquals(
            <<<'POT'
            msgid ""
            msgstr ""
            "Content-Type: text/plain; charset=UTF-8\n"

            msgid "\\ should be written escaped"
            msgstr ""


            POT,
            file_get_contents($path)
        );
    }

    public function testItShouldEscapeNewline(): void
    {
        $path       = $this->tmp_dir . '/template.pot';
        $dumper     = new POTFileDumper();
        $collection = new POTEntryCollection('whatever');
        $collection->add('whatever', new POTEntry("\n should be written escaped", ''));

        $dumper->dump($collection, $path);

        $this->assertEquals(
            <<<'POT'
            msgid ""
            msgstr ""
            "Content-Type: text/plain; charset=UTF-8\n"

            msgid "\n should be written escaped"
            msgstr ""


            POT,
            file_get_contents($path)
        );
    }
}
