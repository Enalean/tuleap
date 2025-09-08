<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Admin;

use GitPresenters_AdminPresenter;

/**
 * @psalm-immutable
 */
final class GitLabLinkGroupPanePresenter extends GitPresenters_AdminPresenter
{
    public function __construct(
        \Project $project,
        array $external_pane_presenters,
    ) {
        parent::__construct((int) $project->getID(), $external_pane_presenters);
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    #[\Override]
    public function form_action(): string
    {
        return '';
    }
}
