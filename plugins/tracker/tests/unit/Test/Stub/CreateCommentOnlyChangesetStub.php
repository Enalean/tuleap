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

use Tracker_Artifact_Changeset;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\NewComment;
use Tuleap\Tracker\Artifact\Changeset\CreateCommentOnlyChangeset;

final class CreateCommentOnlyChangesetStub implements CreateCommentOnlyChangeset
{
    /** @var null | callable(NewComment, Artifact): (Ok<Tracker_Artifact_Changeset>|Err<Fault>) */
    private $callback;

    /**
     * @param null | callable(NewComment, Artifact): (Ok<Tracker_Artifact_Changeset>|Err<Fault>) $callback
     */
    private function __construct(
        private ?Tracker_Artifact_Changeset $return_value,
        ?callable $callback,
    ) {
        $this->callback = $callback;
    }

    /**
     * @return Ok<Tracker_Artifact_Changeset>|Err<Fault>
     */
    private function defaultCallback(): Ok|Err
    {
        if ($this->return_value === null) {
            throw new \LogicException('Did not expect to be called');
        }
        return Result::ok($this->return_value);
    }

    public static function withChangeset(Tracker_Artifact_Changeset $changeset): self
    {
        return new self($changeset, null);
    }

    /**
     * @param callable(NewComment, Artifact): (Ok<Tracker_Artifact_Changeset>|Err<Fault>) $callback
     */
    public static function withCallback(callable $callback): self
    {
        return new self(null, $callback);
    }

    #[\Override]
    public function createCommentOnlyChangeset(NewComment $new_comment, Artifact $artifact): Ok|Err
    {
        if ($this->callback !== null) {
            return ($this->callback)($new_comment, $artifact);
        }
        return $this->defaultCallback();
    }
}
