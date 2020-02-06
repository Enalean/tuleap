<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

declare(strict_types = 1);

namespace Tuleap\Project\Registration\Template;

use Tuleap\Glyph\Glyph;
use Tuleap\Glyph\GlyphFinder;

class EmptyTemplate implements TuleapTemplate
{
    private const PROJECT_XML = __DIR__ . '/../../../../../tools/utils/setup_templates/empty_template/project.xml';

    public const NAME = 'empty';

    /**
     * @var string
     */
    private $title;
    /**s
     * @var string
     */
    private $description;
    /**
     * @var GlyphFinder
     */
    private $glyph_finder;
    /**
     * @var string
     */
    private $xml_path;

    public function __construct(GlyphFinder $glyph_finder)
    {
        $this->title        = _('Empty');
        $this->description  = _('A template without any service enabled.');
        $this->glyph_finder = $glyph_finder;
    }

    /**
     * Actual XML file is generated "on the fly" in order to guaranty the consistency between the AgileDashboard
     * "Start Scrum" template and the "Project Creation" Scrum template
     */
    public function getXMLPath(): string
    {
        if ($this->xml_path === null) {
            $base_dir = \ForgeConfig::getCacheDir() . '/empty_template';
            if (! is_dir($base_dir) && ! mkdir($base_dir, 0755) && ! is_dir($base_dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $base_dir));
            }
            $this->xml_path = \ForgeConfig::getCacheDir() . '/empty_template/project.xml';
            if (! copy(self::PROJECT_XML, $this->xml_path)) {
                throw new \RuntimeException("Can not copy empty file for tuleap template import");
            }
        }

        return $this->xml_path;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getGlyph(): Glyph
    {
        return $this->glyph_finder->get("default-and-company-template");
    }

    public function getId(): string
    {
        return self::NAME;
    }

    /**
     * This template is never available, we manually add it only if we need
     */
    public function isAvailable(): bool
    {
        return false;
    }

    public function isBuiltIn(): bool
    {
        return true;
    }
}
