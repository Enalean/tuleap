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
use Tuleap\Language\Gettext\POTEntryCollection;

class GettextCollector
{
    public const DEFAULT_DOMAIN = 'tuleap-core';

    /**
     * @var GettextSectionContentTransformer
     */
    private $transformer;

    public function __construct(GettextSectionContentTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function collectEntry($section_name, $section_content, POTEntryCollection $collection)
    {
        $parts = $this->transformer->splitTextInParts($section_content);

        switch ($section_name) {
            case GettextHelper::GETTEXT:
            case GettextHelper::NGETTEXT:
                $domain = self::DEFAULT_DOMAIN;
                break;
            case GettextHelper::DGETTEXT:
            case GettextHelper::DNGETTEXT:
                $domain = $this->transformer->shift($section_content, $parts);
                break;
            default:
                throw new \RuntimeException('Cannot collect gettext entries in following section: ' . $section_name);
        }

        switch ($section_name) {
            case GettextHelper::GETTEXT:
            case GettextHelper::DGETTEXT:
                $msgid        = $this->transformer->shift($section_content, $parts);
                $msgid_plural = '';
                break;
            case GettextHelper::NGETTEXT:
            case GettextHelper::DNGETTEXT:
                $msgid        = $this->transformer->shift($section_content, $parts);
                $msgid_plural = $this->transformer->shift($section_content, $parts);
                break;
            default:
                throw new \RuntimeException('Cannot collect gettext entries in following section: ' . $section_name);
        }

        $collection->add($domain, new POTEntry($msgid, $msgid_plural));
    }
}
