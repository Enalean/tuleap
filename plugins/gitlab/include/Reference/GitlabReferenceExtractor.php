<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Reference;

use Project;
use Tuleap\Gitlab\Reference\Branch\GitlabBranchReference;
use Tuleap\Gitlab\Reference\Branch\GitlabBranchReferenceSplitValuesBuilder;
use Tuleap\Gitlab\Reference\Commit\GitlabCommitReference;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequestReference;
use Tuleap\Gitlab\Reference\Tag\GitlabTagReference;
use Tuleap\Gitlab\Reference\Tag\GitlabTagReferenceSplitValuesBuilder;

final class GitlabReferenceExtractor
{
    public function __construct(
        private GitlabReferenceValueWithoutSeparatorSplitValuesBuilder $commit_reference_split_value_builder,
        private GitlabReferenceValueWithoutSeparatorSplitValuesBuilder $merge_request_reference_split_value_builder,
        private GitlabBranchReferenceSplitValuesBuilder $branch_reference_split_value_builder,
        private GitlabTagReferenceSplitValuesBuilder $tag_reference_split_value_builder,
    ) {
    }

    public function extractReferenceSplitValuesByReferenceKeywordAndValue(
        Project $project,
        string $keyword,
        string $value,
    ): GitlabReferenceSplittedValues {
        return match ($keyword) {
            GitlabCommitReference::REFERENCE_NAME => $this->commit_reference_split_value_builder->splitRepositoryNameAndReferencedItemId(
                $value,
                (int) $project->getID(),
            ),
            GitlabMergeRequestReference::REFERENCE_NAME => $this->merge_request_reference_split_value_builder->splitRepositoryNameAndReferencedItemId(
                $value,
                (int) $project->getID(),
            ),
            GitlabBranchReference::REFERENCE_NAME => $this->branch_reference_split_value_builder->splitRepositoryNameAndReferencedItemId(
                $value,
                (int) $project->getID(),
            ),
            GitlabTagReference::REFERENCE_NAME => $this->tag_reference_split_value_builder->splitRepositoryNameAndReferencedItemId(
                $value,
                (int) $project->getID(),
            ),
            default => GitlabReferenceSplittedValues::buildNotFoundReference(),
        };
    }

    public function extractReferenceSplitValuesByReferenceTypeAndValue(
        Project $project,
        string $type,
        string $value,
    ): GitlabReferenceSplittedValues {
        return match ($type) {
            GitlabCommitReference::NATURE_NAME => $this->commit_reference_split_value_builder->splitRepositoryNameAndReferencedItemId(
                $value,
                (int) $project->getID(),
            ),
            GitlabMergeRequestReference::NATURE_NAME => $this->merge_request_reference_split_value_builder->splitRepositoryNameAndReferencedItemId(
                $value,
                (int) $project->getID(),
            ),
            GitlabBranchReference::NATURE_NAME => $this->branch_reference_split_value_builder->splitRepositoryNameAndReferencedItemId(
                $value,
                (int) $project->getID(),
            ),
            GitlabTagReference::NATURE_NAME => $this->tag_reference_split_value_builder->splitRepositoryNameAndReferencedItemId(
                $value,
                (int) $project->getID(),
            ),
            default => GitlabReferenceSplittedValues::buildNotFoundReference(),
        };
    }
}
