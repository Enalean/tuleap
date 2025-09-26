<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\JiraImport\JiraAgile;

use DateTimeImmutable;
use Tuleap\Tracker\Creation\JiraImporter\UnexpectedFormatException;

/**
 * @psalm-immutable
 */
final class JiraSprint
{
    public const string STATE_FUTURE = 'future';
    public const string STATE_ACTIVE = 'active';
    public const string STATE_CLOSED = 'closed';

    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $url;
    /**
     * @var self::STATE_*
     */
    public $state;
    /**
     * @var string
     */
    public $name;
    /**
     * @var ?DateTimeImmutable
     */
    public $start_date;
    /**
     * @var ?DateTimeImmutable
     */
    public $end_date;
    /**
     * @var ?DateTimeImmutable
     */
    public $complete_date;

    /**
     * @param self::STATE_* $state
     */
    private function __construct(int $id, string $url, string $state, string $name)
    {
        $this->id    = $id;
        $this->url   = $url;
        $this->state = $state;
        $this->name  = $name;
    }

    public function withStartDate(DateTimeImmutable $start_date): self
    {
        $new             = clone $this;
        $new->start_date = $start_date;
        return $new;
    }

    public function withEndDate(DateTimeImmutable $end_date): self
    {
        $new           = clone $this;
        $new->end_date = $end_date;
        return $new;
    }

    public function withCompleteDate(DateTimeImmutable $complete_date): self
    {
        $new                = clone $this;
        $new->complete_date = $complete_date;
        return $new;
    }

    /**
     * @param array{id: int, self: string, state: string, name: string, startDate?: string, endDate?: string, completeDate?: string} $json
     */
    public static function buildFromAPI(array $json): self
    {
        if (! in_array($json['state'], [self::STATE_ACTIVE, self::STATE_FUTURE, self::STATE_CLOSED], true)) {
            throw new UnexpectedFormatException(sprintf('According to Jira documentation, %s is not a valid state for Sprints', $json['state']));
        }
        $self = new self(
            $json['id'],
            $json['self'],
            $json['state'],
            $json['name']
        );
        if (isset($json['startDate'])) {
            $self = $self->withStartDate(new DateTimeImmutable($json['startDate']));
        }
        if (isset($json['endDate'])) {
            $self = $self->withEndDate(new DateTimeImmutable($json['endDate']));
        }
        if (isset($json['completeDate'])) {
            $self = $self->withCompleteDate(new DateTimeImmutable($json['completeDate']));
        }
        return $self;
    }

    public static function buildActive(int $id, string $name): self
    {
        return new self($id, sprintf('https://jira.example.com/rest/agile/1.0/sprint/%d', $id), self::STATE_ACTIVE, $name);
    }

    public static function buildFuture(int $id, string $name): self
    {
        return new self($id, sprintf('https://jira.example.com/rest/agile/1.0/sprint/%d', $id), self::STATE_FUTURE, $name);
    }

    public static function buildClosed(int $id, string $name): self
    {
        return new self($id, sprintf('https://jira.example.com/rest/agile/1.0/sprint/%d', $id), self::STATE_CLOSED, $name);
    }
}
