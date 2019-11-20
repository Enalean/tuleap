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

use Tuleap\Glyph\Glyph;

/**
 * @psalm-immutable
 */
class ScrumTemplate implements ProjectTemplate
{
    public const NAME = 'scrum';

    /**
     * @var string
     */
    private $title;
    /**s
     * @var string
     */
    private $description;
    /**
     * @var Glyph
     */
    private $glyph;

    public function __construct(Glyph $glyph)
    {
        $this->title       = _('Scrum');
        $this->description = _('Manage your project using epics and user stories in releases and sprints');
        $this->glyph       = $glyph;
    }

    public function getXMLPath(): string
    {
        return __DIR__ . '/../../../../../tools/utils/setup_templates/scrum/project.xml';
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

    public function getName(): string
    {
        return self::NAME;
    }
}
