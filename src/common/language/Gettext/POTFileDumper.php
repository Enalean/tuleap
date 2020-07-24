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

class POTFileDumper
{
    public function dump(POTEntryCollection $collection, $path)
    {
        $entries = $collection->getEntries();
        if (count($entries) === 0) {
            file_put_contents($path, '');
            return;
        }

        $content = <<<'EOS'
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"


EOS;

        foreach ($entries as $entry) {
            $msgid = $this->escape($entry->getMsgid());
            $content .= "msgid \"$msgid\"\n";

            $msgid_plural = $this->escape($entry->getMsgidPlural());
            if ($msgid_plural) {
                $content .= "msgid_plural \"$msgid_plural\"\n";
                $content .= "msgstr[0] \"\"\n";
                $content .= "msgstr[1] \"\"\n";
            } else {
                $content .= "msgstr \"\"\n";
            }
            $content .= "\n";
        }

        file_put_contents($path, $content);
    }

    private function escape($string)
    {
        $search  = ['\\', "\n", '"'];
        $replace = ['\\\\', "\\n", '\\"'];

        return str_replace($search, $replace, $string);
    }
}
