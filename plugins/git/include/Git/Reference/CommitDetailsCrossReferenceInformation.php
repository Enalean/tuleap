<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Reference;

use Tuleap\Reference\CrossReferencePresenter;

/**
 * @psalm-immutable
 */
final class CommitDetailsCrossReferenceInformation
{
    /**
     * @var CommitDetails
     */
    private $commit_details;
    /**
     * @var CrossReferencePresenter
     */
    private $cross_reference_presenter;
    /**
     * @var string
     */
    private $section_label;

    public function __construct(
        CommitDetails $commit_details,
        CrossReferencePresenter $cross_reference_presenter,
        string $section_label,
    ) {
        $this->commit_details            = $commit_details;
        $this->cross_reference_presenter = $cross_reference_presenter;
        $this->section_label             = $section_label;
    }

    public function getCommitDetails(): CommitDetails
    {
        return $this->commit_details;
    }

    public function getCrossReferencePresenter(): CrossReferencePresenter
    {
        return $this->cross_reference_presenter;
    }

    public function getSectionLabel(): string
    {
        return $this->section_label;
    }
}
