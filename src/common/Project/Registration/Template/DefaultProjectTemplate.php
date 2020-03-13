<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\Registration\Template;

use Tuleap\Glyph\Glyph;
use Tuleap\Glyph\GlyphFinder;

class DefaultProjectTemplate implements ProjectTemplate
{

    private const NAME = 'default-and-company-template';

    /**
     * @var int
     */
    private $template_id;
    /**
     * @var string
     */
    private $title;
    /**
     * @var string
     */
    private $description;
    /**
     * @var Glyph
     */
    private $glyph;


    public function __construct(\Project $project, GlyphFinder $glyph_finder)
    {
        $this->template_id = $project->getGroupId();
        $this->description = _('The legacy default template. No more available soon. To be recreated as a "Custom template"');
        $this->title       = _('Legacy default template');
        $this->glyph       = $glyph_finder->get(self::NAME);
    }

    public function getId(): string
    {
        return (string) $this->template_id;
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
        return $this->glyph;
    }

    public function isBuiltIn(): bool
    {
        return true;
    }
}
