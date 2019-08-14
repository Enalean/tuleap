<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Project\Service;

final class ServicePOSTData
{
    /**
     * @var string
     */
    private $short_name;
    /**
     * @var string
     */
    private $label;
    /**
     * @var string
     */
    private $icon_name;
    /**
     * @var string
     */
    private $description;
    /**
     * @var string
     */
    private $link;
    /**
     * @var int
     */
    private $rank;
    /**
     * @var bool
     */
    private $is_active;
    /**
     * @var bool
     */
    private $is_system_service;
    /**
     * @var bool
     */
    private $is_used;
    /**
     * @var string
     */
    private $scope;
    /**
     * @var bool
     */
    private $is_in_iframe;
    /**
     * @var bool
     */
    private $is_in_new_tab;
    /**
     * @var int
     */
    private $id;

    public function __construct(
        int $id,
        string $short_name,
        string $label,
        string $icon_name,
        string $description,
        string $link,
        int $rank,
        string $scope,
        bool $is_active,
        bool $is_used,
        bool $is_system_service,
        bool $is_in_iframe,
        bool $is_in_new_tab
    ) {
        $this->id                = $id;
        $this->short_name        = $short_name;
        $this->label             = $label;
        $this->icon_name         = $icon_name;
        $this->description       = $description;
        $this->link              = $link;
        $this->rank              = $rank;
        $this->scope             = $scope;
        $this->is_active         = $is_active;
        $this->is_used           = $is_used;
        $this->is_system_service = $is_system_service;
        $this->is_in_iframe      = $is_in_iframe;
        $this->is_in_new_tab     = $is_in_new_tab;
    }

    public function getShortName(): string
    {
        return $this->short_name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIconName(): string
    {
        return $this->icon_name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getRank(): int
    {
        return $this->rank;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isSystemService(): bool
    {
        return $this->is_system_service;
    }

    public function isUsed(): bool
    {
        return $this->is_used;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function isInIframe(): bool
    {
        return $this->is_in_iframe;
    }

    public function isInNewTab(): bool
    {
        return $this->is_in_new_tab;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
