<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Git\GitViews\RepoManagement\Pane\Gerrit;

use Git_Driver_Gerrit_Template_Template;

/**
 * @psalm-immutable
 */
final readonly class GerritTemplatePresenter
{
    public int $template_id;
    public string $template_name;

    public function __construct(
        Git_Driver_Gerrit_Template_Template $template,
    ) {
        $this->template_id   = (int) $template->getId();
        $this->template_name = $template->getName();
    }
}
