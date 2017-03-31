<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Admin\Homepage;

class NbUsersByStatus
{
    /**
     * @var int
     */
    private $nb_active;
    /**
     * @var int
     */
    private $nb_restricted;
    /**
     * @var int
     */
    private $nb_suspended;
    /**
     * @var int
     */
    private $nb_deleted;
    /**
     * @var int
     */
    private $nb_pending;
    /**
     * @var int
     */
    private $nb_validated_restricted;
    /**
     * @var int
     */
    private $nb_validated;

    /**
     * @param int $nb_active
     * @param int $nb_restricted
     * @param int $nb_suspended
     * @param int $nb_deleted
     * @param int $nb_pending
     * @param int $nb_validated
     * @param int $nb_validated_restricted
     */
    public function __construct(
        $nb_active,
        $nb_restricted,
        $nb_suspended,
        $nb_deleted,
        $nb_pending,
        $nb_validated,
        $nb_validated_restricted
    ) {
        $this->nb_active               = $nb_active;
        $this->nb_restricted           = $nb_restricted;
        $this->nb_suspended            = $nb_suspended;
        $this->nb_deleted              = $nb_deleted;
        $this->nb_pending              = $nb_pending;
        $this->nb_validated            = $nb_validated;
        $this->nb_validated_restricted = $nb_validated_restricted;
    }

    /**
     * @return int
     */
    public function getNbActive()
    {
        return $this->nb_active;
    }

    /**
     * @return int
     */
    public function getNbRestricted()
    {
        return $this->nb_restricted;
    }

    /**
     * @return int
     */
    public function getNbSuspended()
    {
        return $this->nb_suspended;
    }

    /**
     * @return int
     */
    public function getNbDeleted()
    {
        return $this->nb_deleted;
    }

    /**
     * @return int
     */
    public function getNbPending()
    {
        return $this->nb_pending;
    }

    /**
     * @return int
     */
    public function getNbAllValidated()
    {
        return $this->nb_validated + $this->nb_validated_restricted;
    }
}
