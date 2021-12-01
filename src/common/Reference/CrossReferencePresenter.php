<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Reference;

use Tuleap\Date\TlpRelativeDatePresenter;
use Tuleap\Reference\Metadata\CreatedByPresenter;

/**
 * @psalm-immutable
 */
final class CrossReferencePresenter
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $title;
    /**
     * @var string
     */
    public $url;
    /**
     * @var string
     */
    public $delete_url;
    /**
     * @var string
     */
    public $type;
    /**
     * @var int
     */
    public $target_gid;
    /**
     * @var string
     */
    public $target_value;
    /**
     * @var TitleBadgePresenter|null
     */
    public $title_badge;
    /**
     * @var AdditionalBadgePresenter[]
     */
    public $additional_badges;
    /**
     * @var CreationMetadataPresenter|null
     */
    public $creation_metadata;

    /**
     * @param AdditionalBadgePresenter[] $additional_badges
     */
    public function __construct(
        int $id,
        string $type,
        string $title,
        string $url,
        string $delete_url,
        int $project_id,
        string $value,
        ?TitleBadgePresenter $title_badge,
        array $additional_badges,
        ?CreationMetadataPresenter $creation_metadata,
    ) {
        $this->id                = $id;
        $this->type              = $type;
        $this->title             = $title;
        $this->url               = $url;
        $this->delete_url        = $delete_url;
        $this->target_gid        = $project_id;
        $this->target_value      = $value;
        $this->title_badge       = $title_badge;
        $this->additional_badges = $additional_badges;
        $this->creation_metadata = $creation_metadata;
    }

    public function withTitle(string $title, ?TitleBadgePresenter $title_badge): self
    {
        return new self(
            $this->id,
            $this->type,
            $title,
            $this->url,
            $this->delete_url,
            $this->target_gid,
            $this->target_value,
            $title_badge,
            $this->additional_badges,
            $this->creation_metadata,
        );
    }

    /**
     * @param AdditionalBadgePresenter[] $additional_badges
     */
    public function withAdditionalBadges(array $additional_badges): self
    {
        return new self(
            $this->id,
            $this->type,
            $this->title,
            $this->url,
            $this->delete_url,
            $this->target_gid,
            $this->target_value,
            $this->title_badge,
            $additional_badges,
            $this->creation_metadata,
        );
    }

    public function withCreationMetadata(?CreatedByPresenter $created_by, TlpRelativeDatePresenter $created_on): self
    {
        return new self(
            $this->id,
            $this->type,
            $this->title,
            $this->url,
            $this->delete_url,
            $this->target_gid,
            $this->target_value,
            $this->title_badge,
            $this->additional_badges,
            new CreationMetadataPresenter($created_by, $created_on),
        );
    }
}
