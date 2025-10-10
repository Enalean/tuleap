<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Layout;

use Tuleap\Layout\HeaderConfiguration\InProject;
use Tuleap\Layout\HeaderConfiguration\InSiteAdministration;
use Tuleap\Layout\NewDropdown\NewDropdownLinkSectionPresenter;
use Tuleap\OpenGraph\OpenGraphPresenter;

/**
 * @psalm-immutable
 */
final readonly class HeaderConfiguration
{
    /**
     * @param string[] $body_class
     * @param string[] $main_class
     * @psalm-internal \Tuleap\Layout
     */
    public function __construct(
        public string $title,
        public ?InProject $in_project,
        public array $body_class,
        public array $main_class,
        public int $printer_version,
        public ?NewDropdownLinkSectionPresenter $new_dropdown_link_section_presenter,
        public bool $include_fat_combined,
        public ?InSiteAdministration $in_site_administration,
        private ?OpenGraphPresenter $open_graph,
    ) {
    }

    public static function fromTitle(string $title): self
    {
        return HeaderConfigurationBuilder::get($title)->build();
    }

    /**
     * @psalm-internal \Tuleap\Layout
     * @psalm-internal \Layout
     * @psalm-internal \FlamingParrot_Theme
     */
    public function flatten(): array
    {
        return [
            'title'                                => $this->title,
            'body_class'                           => $this->body_class,
            'main_classes'                         => $this->main_class,
            'open_graph'                           => $this->open_graph,
            'pv'                                   => $this->printer_version,
            'new_dropdown_current_context_section' => $this->new_dropdown_link_section_presenter,
            ...($this->include_fat_combined ? ['include_fat_combined' => true] : []),
            ...($this->in_project ? [
                'project'                        => $this->in_project->project,
                'toptab'                         => $this->in_project->current_service_shortname,
                'without-project-in-breadcrumbs' => ! $this->in_project->in_breadcrumbs,
                'active-promoted-item-id'        => $this->in_project->active_promoted_item_id,
            ] : []),
            ...($this->in_site_administration ? $this->in_site_administration->flatten() : []),
        ];
    }
}
