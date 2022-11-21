<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Test\Builders;

use Tuleap\Glyph\Glyph;
use Tuleap\QuickLink\SwitchToQuickLink;
use Tuleap\Search\SearchResultEntry;
use Tuleap\Search\SearchResultEntryBadge;

final class SearchResultEntryBuilder
{
    private ?string $xref = null;
    private string $uri;
    private string $title       = 'phylogomènes généralisés';
    private string $color_name  = 'flamingo-pink';
    private string $type        = 'trabajo';
    private int $per_type_id    = 271;
    private ?Glyph $small_icon  = null;
    private ?Glyph $normal_icon = null;
    private string $icon_name   = 'fa-regular fa-bookmark';
    private \Project $project;
    private ?string $cropped_content = null;
    /**
     * @var SwitchToQuickLink[]
     */
    private array $quick_links = [];
    /**
     * @var SearchResultEntryBadge[]
     */
    private array $badges = [];

    private function __construct()
    {
        $this->uri     = '/plugins/desugar?id=' . $this->per_type_id;
        $this->project = ProjectTestBuilder::aProject()->withId(265)->build();
    }

    public static function anEntry(): self
    {
        return new self();
    }

    public function withTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function withColorName(string $color_name): self
    {
        $this->color_name = $color_name;
        return $this;
    }

    public function withCrossReference(string $xref): self
    {
        $this->xref = $xref;
        return $this;
    }

    public function withLink(string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    public function withType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function withPerTypeId(int $per_type_id): self
    {
        $this->per_type_id = $per_type_id;
        return $this;
    }

    public function withSmallIcon(Glyph $small_icon): self
    {
        $this->small_icon = $small_icon;
        return $this;
    }

    public function withNormalIcon(Glyph $normal_icon): self
    {
        $this->normal_icon = $normal_icon;
        return $this;
    }

    public function withIconName(string $icon_name): self
    {
        $this->icon_name = $icon_name;
        return $this;
    }

    public function inProject(\Project $project): self
    {
        $this->project = $project;
        return $this;
    }

    public function withCroppedContent(string $cropped_content): self
    {
        $this->cropped_content = $cropped_content;
        return $this;
    }

    /**
     * @no-named-arguments
     */
    public function withQuickLinks(SwitchToQuickLink $first_link, SwitchToQuickLink ...$other_links): self
    {
        $this->quick_links = [$first_link, ...$other_links];
        return $this;
    }

    /**
     * @no-named-arguments
     */
    public function withBadges(SearchResultEntryBadge $first_badge, SearchResultEntryBadge ...$other_badges): self
    {
        $this->badges = [$first_badge, ...$other_badges];
        return $this;
    }

    public function build(): SearchResultEntry
    {
        return new SearchResultEntry(
            $this->xref,
            $this->uri,
            $this->title,
            $this->color_name,
            $this->type,
            $this->per_type_id,
            $this->small_icon,
            $this->normal_icon,
            $this->icon_name,
            $this->project,
            $this->quick_links,
            $this->cropped_content,
            $this->badges,
        );
    }
}
