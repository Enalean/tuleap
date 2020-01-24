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


namespace Tuleap\AgileDashboard\Planning\Presenters;

use Planning_Milestone;
use Tuleap\Event\Dispatchable;

class AlternativeBoardLinkEvent implements Dispatchable
{
    public const NAME = 'alternativeBoardLinkEvent';

    /**
     * @var Planning_Milestone
     */
    private $milestone;

    /**
     * @var AlternativeBoardLinkPresenter | null
     */
    private $alternative_board_link;

    public function __construct(Planning_Milestone $milestone)
    {
        $this->milestone = $milestone;
    }

    public function getMilestone(): Planning_Milestone
    {
        return $this->milestone;
    }

    public function getAlternativeBoardLinkPresenter(): ?AlternativeBoardLinkPresenter
    {
        return $this->alternative_board_link;
    }

    public function setAlternativeBoardLink(AlternativeBoardLinkPresenter $alternative_board_link): void
    {
        $this->alternative_board_link = $alternative_board_link;
    }
}
