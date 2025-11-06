<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

declare(strict_types=1);

namespace Tuleap\Git\GlobalAdmin;

use Git;
use Tuleap\Request\CSRFSynchronizerTokenInterface;

/**
 * @psalm-immutable
 */
final readonly class GerritTemplatesPresenter
{
    /**
     * List of repositories belonging to the project
     */
    private array $repository_list;

    /**
     * List of templates belonging to the project
     */
    private array $templates_list;

    /**
     * List of templates belonging to the parent project hierarchy
     */
    private array $parent_templates_list;

    /**
     * @param AdminExternalPanePresenter[] $external_pane_presenters
     */
    public function __construct(
        array $repository_list,
        array $templates_list,
        array $parent_templates_list,
        public int $project_id,
        public bool $has_gerrit_servers_set_up,
        public CSRFSynchronizerTokenInterface $csrf_token,
        public array $external_pane_presenters,
    ) {
        $this->repository_list       = $repository_list;
        $this->templates_list        = $templates_list;
        $this->parent_templates_list = $parent_templates_list;
    }

    public function config_option(): array //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return array_values($this->repository_list);
    }

    public function templates_option(): array //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->templates_list;
    }

    public function parent_templates_option(): array //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->parent_templates_list;
    }

    public function isEmpty(): bool
    {
        return $this->templates_list === [] && $this->parent_templates_list === [];
    }

    public function form_action(): string //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return '/plugins/git/?' . http_build_query(
            [
                'action' => Git::ADMIN_GERRIT_TEMPLATES_ACTION,
                'group_id' => $this->project_id,
            ]
        );
    }
}
