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

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tuleap\Language\Gettext\POTEntryCollection;
use Tuleap\Language\Gettext\POTFileDumper;

class DomainExtractor
{
    /**
     * @var POTFileDumper
     */
    private $pot_file_dumper;
    /**
     * @var GettextExtractor
     */
    private $extractor;

    public function __construct(POTFileDumper $pot_file_dumper, GettextExtractor $extractor)
    {
        $this->pot_file_dumper = $pot_file_dumper;
        $this->extractor       = $extractor;
    }

    public function extract(string $domain, array $sources, string $destination_template): void
    {
        $collection = new POTEntryCollection($domain);

        foreach ($sources as $source_path) {
            $this->fillCollection($source_path, $collection);
        }

        $this->pot_file_dumper->dump($collection, $destination_template);
    }

    private function fillCollection(string $sources, POTEntryCollection $collection): void
    {
        if (! file_exists($sources)) {
            return;
        }

        $iterator = new RecursiveDirectoryIterator(
            $sources,
            RecursiveDirectoryIterator::SKIP_DOTS
        );
        foreach (new RecursiveIteratorIterator($iterator) as $file) {
            $filename = $file->getRealPath();
            if (pathinfo($filename, PATHINFO_EXTENSION) !== 'mustache') {
                continue;
            }

            $template = file_get_contents($filename);
            $this->extractor->extract($template, $collection);
        }
    }
}
