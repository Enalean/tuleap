<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Dashboard\Project;

use Tuleap\Event\Dispatchable;

final class DisplayCreatedProjectModalPresenter implements Dispatchable
{
    public const NAME = "displayCreatedProjectModal";
    public bool $should_display_created_project_modal;
    /**
     * @psalm-var null|array{label: string, href: string}
     */
    public ?array $custom_primary_action   = null;
    public bool $has_custom_primary_action = false;

    private \Project $project;
    private string $xml_template_name;

    public function __construct(
        bool $should_display_created_project_modal,
        \Project $project,
        string $xml_template_name,
    ) {
        $this->should_display_created_project_modal = $should_display_created_project_modal;
        $this->project                              = $project;
        $this->xml_template_name                    = $xml_template_name;
    }

    public function setCustomPrimaryAction(string $label, string $href): void
    {
        $this->custom_primary_action     = ['label' => $label, 'href' => $href];
        $this->has_custom_primary_action = true;
    }

    public function getProject(): \Project
    {
        return $this->project;
    }

    public function getXmlTemplateName(): string
    {
        return $this->xml_template_name;
    }
}
