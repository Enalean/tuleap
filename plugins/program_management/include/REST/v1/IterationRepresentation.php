<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);


namespace Tuleap\ProgramManagement\REST\v1;

use Psr\Log\LoggerInterface;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\ArtifactProxy;
use Tuleap\REST\JsonCast;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

/**
 * @psalm-immutable
 */
final class IterationRepresentation
{
    /**
     * @var int
     */
    public int $id;
    /**
     * @var string
     */
    public string $uri;
    /**
     * @var string
     */
    public string $xref;
    /**
     * @var ?string
     */
    public ?string $title;
    /**
     * @var string | null
     */
    public ?string $status;
    /**
     * @var string | null {@type date}{@required false}
     */
    public ?string $start_date;
    /**
     * @var string | null {@type date}{@required false}
     */
    public ?string $end_date;
    /**
     * @var bool {@type bool}
     */
    public bool $user_can_update;

    private function __construct(
        int $id,
        string $title,
        string $uri,
        string $xref,
        bool $user_can_update,
        ?string $status,
        ?int $start_date,
        ?int $end_date
    ) {
        $this->id              = $id;
        $this->uri             = $uri;
        $this->xref            = $xref;
        $this->title           = $title;
        $this->status          = $status;
        $this->start_date      = JsonCast::toDate($start_date);
        $this->end_date        = JsonCast::toDate($end_date);
        $this->user_can_update = JsonCast::toBoolean($user_can_update);
    }

    public static function buildFromArtifact(
        SemanticTimeframeBuilder $semantic_timeframe_builder,
        LoggerInterface $logger,
        Artifact $iteration,
        \PFUser $user
    ): ?self {
        $proxy = ArtifactProxy::buildFromArtifact($semantic_timeframe_builder, $logger, $iteration, $user);
        if (! $proxy) {
            return null;
        }
        return new self(
            $iteration->getId(),
            $proxy->getTitle(),
            $iteration->getUri(),
            $iteration->getXRef(),
            $iteration->userCanUpdate($user),
            $proxy->getStatus(),
            $proxy->getStartDate(),
            $proxy->getEndDate()
        );
    }
}
