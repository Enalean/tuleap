<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub;

use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;

final class CreateNewChangesetStub implements \Tuleap\Tracker\Artifact\Changeset\CreateNewChangeset
{
    private int $calls_count             = 0;
    private ?NewChangeset $new_changeset = null;

    /** @var callable(NewChangeset, PostCreationContext): \Tracker_Artifact_Changeset | null */
    private $callback;

    /**
     * @param callable(NewChangeset, PostCreationContext): \Tracker_Artifact_Changeset | null $callback
     */
    private function __construct(
        private readonly ?\Tracker_Artifact_Changeset $changeset,
        private readonly ?\Throwable $exception,
        ?callable $callback,
    ) {
        $this->callback = $callback;
    }

    private function defaultCallback(): ?\Tracker_Artifact_Changeset
    {
        if ($this->exception) {
            throw $this->exception;
        }
        return $this->changeset;
    }

    public static function withReturnChangeset(\Tracker_Artifact_Changeset $changeset): self
    {
        return new self($changeset, null, null);
    }

    public static function withNullReturnChangeset(): self
    {
        return new self(null, null, null);
    }

    public static function withException(\Throwable $param): self
    {
        return new self(null, $param, null);
    }

    /**
     * @param callable(NewChangeset, PostCreationContext): \Tracker_Artifact_Changeset $callback
     */
    public static function withCallback(callable $callback): self
    {
        return new self(null, null, $callback);
    }

    public function getNewChangeset(): ?NewChangeset
    {
        return $this->new_changeset;
    }

    public function create(NewChangeset $changeset, PostCreationContext $context): ?\Tracker_Artifact_Changeset
    {
        $this->new_changeset = $changeset;
        $this->calls_count++;
        if ($this->callback !== null) {
            return ($this->callback)($changeset, $context);
        }
        return $this->defaultCallback();
    }

    public function getCallsCount(): int
    {
        return $this->calls_count;
    }
}
