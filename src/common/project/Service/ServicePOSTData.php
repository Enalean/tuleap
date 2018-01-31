<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

class ServicePOSTData
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
     * @param string $short_name
     * @param string $label
     * @param string $description
     * @param string $link
     * @param int $rank
     * @param string $scope
     * @param bool $is_active
     * @param bool $is_used
     * @param bool $is_system_service
     * @param bool $is_in_iframe
     */
    public function __construct(
        $short_name,
        $label,
        $description,
        $link,
        $rank,
        $scope,
        $is_active,
        $is_used,
        $is_system_service,
        $is_in_iframe
    ) {
        $this->short_name        = $short_name;
        $this->label             = $label;
        $this->description       = $description;
        $this->link              = $link;
        $this->rank              = $rank;
        $this->scope             = $scope;
        $this->is_active         = $is_active;
        $this->is_used           = $is_used;
        $this->is_system_service = $is_system_service;
        $this->is_in_iframe      = $is_in_iframe;
    }

    /**
     * @return string
     */
    public function getShortName()
    {
        return $this->short_name;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @return int
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->is_active;
    }

    /**
     * @return bool
     */
    public function isSystemService()
    {
        return $this->is_system_service;
    }

    /**
     * @return bool
     */
    public function isUsed()
    {
        return $this->is_used;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @return bool
     */
    public function isInIframe()
    {
        return $this->is_in_iframe;
    }
}
