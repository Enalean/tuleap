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

class TemplatePresenter
{
    /**
     * @var string
     * @readonly
     */
    public $title;
    /**
     * @var string
     * @readonly
     */
    public $description;
    /**
     * @var string
     * @readonly
     */
    public $id;
    /**
     * @var string
     * @readonly
     */
    public $glyph;
    /**
     * @var bool
     * @readonly
     */
    public $is_built_in;

    public function __construct(ProjectTemplate $template)
    {
        $this->title       = $template->getTitle();
        $this->description = $template->getDescription();
        $this->id          = $template->getId();
        $this->glyph       = $template->getGlyph()->getInlineString();
        $this->is_built_in = $template->isBuiltIn();
    }
}
