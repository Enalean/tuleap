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

use Tuleap\Glyph\GlyphFinder;
use Tuleap\XML\ProjectXMLMerger;

class TemplateFactory
{
    /**
     * @var ProjectTemplate[]
     */
    private $templates;

    public function __construct(GlyphFinder $glyph_finder, ProjectXMLMerger $project_xml_merger)
    {
        $this->templates = [
            ScrumTemplate::NAME => new ScrumTemplate($glyph_finder, $project_xml_merger),
        ];
    }

    /**
     * @return ProjectTemplate[]
     */
    public function getValidTemplates(): array
    {
        return array_values($this->templates);
    }

    /**
     * @throws InvalidTemplateException
     */
    public function getTemplate(string $name): ProjectTemplate
    {
        if (! isset($this->templates[$name])) {
            throw new InvalidTemplateException();
        }
        return $this->templates[$name];
    }
}
