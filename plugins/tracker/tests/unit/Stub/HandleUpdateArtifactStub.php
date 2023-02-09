<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Test\Stub;

use PFUser;
use Throwable;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfReverseLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValuesContainer;
use Tuleap\Tracker\Artifact\Link\HandleUpdateArtifact;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\NewChangesetCommentRepresentation;

final class HandleUpdateArtifactStub implements HandleUpdateArtifact
{
    private int $unlink_reverse_artifact_method_call_count;
    private int $link_reverse_artifact_method_call_count;
    private int $update_forward_artifact_method_call_count;

    private function __construct(private ?Throwable $exception)
    {
        $this->unlink_reverse_artifact_method_call_count = 0;
        $this->link_reverse_artifact_method_call_count   = 0;
        $this->update_forward_artifact_method_call_count = 0;
    }

    public static function build(): self
    {
        return new self(null);
    }

    public static function withException(Throwable $exception): self
    {
        return new self($exception);
    }

    public function removeReverseLinks(Artifact $current_artifact, PFUser $submitter, CollectionOfReverseLinks $removed_reverse_links, ?NewChangesetCommentRepresentation $comment = null): Ok|Err
    {
        $this->unlink_reverse_artifact_method_call_count++;
        return Result::ok(null);
    }

    public function addReverseLink(Artifact $current_artifact, PFUser $submitter, CollectionOfReverseLinks $added_reverse_link, ?NewChangesetCommentRepresentation $comment = null): Ok|Err
    {
        $this->link_reverse_artifact_method_call_count++;
        return Result::ok(null);
    }

    public function updateForwardLinks(Artifact $current_artifact, PFUser $submitter, ChangesetValuesContainer $changeset_values_container, ?NewChangesetCommentRepresentation $comment = null,): void
    {
        $this->update_forward_artifact_method_call_count++;

        if ($this->exception) {
            throw $this->exception;
        }
    }

    public function getUnlinkReverseArtifactMethodCallCount(): int
    {
        return $this->unlink_reverse_artifact_method_call_count;
    }

    public function getLinkReverseArtifactMethodCallCount(): int
    {
        return $this->link_reverse_artifact_method_call_count;
    }

    public function getUpdateForwardArtifactMethodCallCount(): int
    {
        return $this->update_forward_artifact_method_call_count;
    }
}
