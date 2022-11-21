<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\User\History;

use Tuleap\Glyph\Glyph;
use Tuleap\QuickLink\SwitchToQuickLink;

/**
 * @psalm-immutable
 */
final class HistoryEntry
{
    /**
     * @param SwitchToQuickLink[] $quick_links
     * @param HistoryEntryBadge[] $badges
     */
    public function __construct(
        private int $visit_time,
        private ?string $xref,
        private string $link,
        private string $title,
        private string $color,
        private string $type,
        private int $per_type_id,
        private ?Glyph $small_icon,
        private ?Glyph $normal_icon,
        private string $icon_name,
        private \Project $project,
        private array $quick_links,
        private array $badges,
    ) {
    }

    public function getVisitTime(): int
    {
        return $this->visit_time;
    }

    public function getXref(): ?string
    {
        return $this->xref;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getPerTypeId(): int
    {
        return $this->per_type_id;
    }

    public function getSmallIcon(): ?Glyph
    {
        return $this->small_icon;
    }

    public function getNormalIcon(): ?Glyph
    {
        return $this->normal_icon;
    }

    public function getProject(): \Project
    {
        return $this->project;
    }

    /**
     * @return SwitchToQuickLink[]
     */
    public function getQuickLinks(): array
    {
        return $this->quick_links;
    }

    public function getIconName(): string
    {
        return $this->icon_name;
    }

    /**
     * @return HistoryEntryBadge[]
     */
    public function getBadges(): array
    {
        return $this->badges;
    }
}
