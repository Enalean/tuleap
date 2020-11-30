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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\View\Reference;

class CrossReferenceLinkPresenter
{
    /**
     * @var string
     */
    public $id;
    /**
     * @var string
     */
    public $ref;
    /**
     * @var string
     */
    public $url;
    /**
     * @var null|string
     */
    public $params;
    /**
     * @var bool
     */
    public $display_comma;
    /**
     * @var string
     */
    public $target_id;
    /**
     * @var string
     */
    public $target_gid;
    /**
     * @var string
     */
    public $target_type;
    /**
     * @var string
     */
    public $target_key;
    /**
     * @var string
     */
    public $source_id;
    /**
     * @var string
     */
    public $source_gid;
    /**
     * @var string
     */
    public $source_type;
    /**
     * @var string
     */
    public $source_key;

    public function __construct(string $id, string $ref, string $url, bool $display_comma, \CrossReference $cross_reference)
    {
        $this->id            = $id;
        $this->ref           = $ref;
        $this->url           = $url;
        $this->display_comma = $display_comma;
        $this->target_id     = $cross_reference->getRefTargetId();
        $this->target_gid    = $cross_reference->getRefTargetGid();
        $this->target_type   = $cross_reference->getRefTargetType();
        $this->target_key    = $cross_reference->getRefTargetKey();
        $this->source_id     = $cross_reference->getRefSourceId();
        $this->source_gid    = $cross_reference->getRefSourceGid();
        $this->source_type   = $cross_reference->getRefSourceType();
        $this->source_key    = $cross_reference->getRefSourceKey();
    }
}
