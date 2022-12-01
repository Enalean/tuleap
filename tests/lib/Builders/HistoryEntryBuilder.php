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
use Tuleap\User\History\HistoryEntry;
use Tuleap\User\History\HistoryEntryBadge;

final class HistoryEntryBuilder
{
    private ?string $xref = null;
    private string $uri;
    private string $title       = 'La congolexicomatisation';
    private string $color_name  = 'coral-pink';
    private string $type        = 'tollery';
    private int $per_type_id    = 67;
    private ?Glyph $small_icon  = null;
    private ?Glyph $normal_icon = null;
    private string $icon_name   = 'fa-solid fa-list-ol';
    private \Project $project;
    /**
     * @var SwitchToQuickLink[]
     */
    private array $quick_links = [];
    /**
     * @var HistoryEntryBadge[]
     */
    private array $badges = [];

    private function __construct(private int $visit_timestamp)
    {
        $this->uri     = '/plugins/dichopodial/?id=' . $this->per_type_id;
        $this->project = ProjectTestBuilder::aProject()->withId(149)->build();
    }

    public static function anEntryVisitedAt(int $visit_timestamp): self
    {
        return new self($visit_timestamp);
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
    public function withBadges(HistoryEntryBadge $first_badge, HistoryEntryBadge ...$other_badges): self
    {
        $this->badges = [$first_badge, ...$other_badges];
        return $this;
    }

    public function build(): HistoryEntry
    {
        return new HistoryEntry(
            $this->visit_timestamp,
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
            $this->badges
        );
    }
}
