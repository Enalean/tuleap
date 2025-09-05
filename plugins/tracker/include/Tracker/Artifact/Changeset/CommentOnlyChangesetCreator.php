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

namespace Tuleap\Tracker\Artifact\Changeset;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\NewComment;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;

final class CommentOnlyChangesetCreator implements CreateCommentOnlyChangeset
{
    public function __construct(private CreateNewChangeset $changeset_creator)
    {
    }

    #[\Override]
    public function createCommentOnlyChangeset(NewComment $new_comment, Artifact $artifact): Ok|Err
    {
        $empty_fields_data = [];
        $new_changeset     = NewChangeset::fromFieldsDataArray(
            $artifact,
            $empty_fields_data,
            $new_comment->getBody(),
            $new_comment->getFormat(),
            $new_comment->getUserGroupsThatAreAllowedToSee(),
            $new_comment->getSubmitter(),
            $new_comment->getSubmissionTimestamp(),
            new CreatedFileURLMapping()
        );
        try {
            $changeset = $this->changeset_creator->create($new_changeset, PostCreationContext::withNoConfig(true));
        } catch (\Tracker_Exception $e) {
            return Result::err(
                Fault::fromThrowableWithMessage(
                    $e,
                    sprintf('An error occurred during the creation of the changeset: %s', $e->getMessage())
                )
            );
        }
        if ($changeset === null) {
            return Result::err(Fault::fromMessage('Error during new changeset creation'));
        }
        return Result::ok($changeset);
    }
}
