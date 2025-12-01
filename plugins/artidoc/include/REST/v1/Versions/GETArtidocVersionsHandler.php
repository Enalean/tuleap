<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Artidoc\REST\v1\Versions;

use PFUser;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\PartiallyReadableDocumentFault;
use Tuleap\Artidoc\Domain\Document\RetrieveArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\SearchAllArtifactSections;
use Tuleap\Artidoc\REST\v1\ArtifactSection\ArtifactVersionRepresentationBuilder;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\RetrieveArtifact;
use Tuleap\Tracker\Permission\ArtifactPermissionType;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnArtifacts;

final readonly class GETArtidocVersionsHandler
{
    public function __construct(
        private RetrieveArtidocWithContext $retrieve_artidoc_with_context,
        private SearchAllArtifactSections $search_all_artifact_sections,
        private RetrieveArtifact $retrieve_artifact,
        private RetrieveUserPermissionOnArtifacts $permissions_on_artifacts,
        private ArtifactVersionRepresentationBuilder $version_representation_builder,
        private QueryToSearchVersionsQueryConverter $query_converter,
    ) {
    }

    /**
     * @return Ok<PaginatedArtidocVersionRepresentationsCollection>|Err<Fault>
     */
    public function handle(PFUser $current_user, int $artidoc_id, string $query, int $limit, int $offset): Ok|Err
    {
        return $this->retrieve_artidoc_with_context->retrieveArtidocUserCanRead($artidoc_id)
            ->andThen($this->getArtidocArtifacts(...))
            ->andThen(
                /** @param list<Artifact> $artifacts */
                fn (array $artifacts) => $this->checkUserCanReadAllArtifacts($current_user, $artifacts)
            )
            ->andThen(
                /** @param list<Artifact> $artifacts */
                function (array $artifacts) use ($limit, $offset, $query) {
                    $search_versions_query = $this->query_converter->convert($query);
                    if (Result::isErr($search_versions_query)) {
                        return $search_versions_query;
                    }

                    return $this->getPaginatedChangesets($artifacts, $search_versions_query->value, $limit, $offset);
                }
            )
            ->map(function (PaginatedChangesetsCollection $changesets_collection) use ($limit, $offset) {
                return new PaginatedArtidocVersionRepresentationsCollection(
                    $this->version_representation_builder->build($changesets_collection->changesets),
                    $limit,
                    $offset,
                    $changesets_collection->total
                );
            });
    }

    /**
     * @return Ok<list<Artifact>>
     */
    private function getArtidocArtifacts(ArtidocWithContext $artidoc): Ok
    {
        $artifact_sections = $this->search_all_artifact_sections->searchAllArtifactSectionsOfDocument($artidoc);
        $artifacts         = [];

        foreach ($artifact_sections as $section) {
            $artifact = $section->content->apply(
                fn (int $artifact_id) => Result::ok($this->retrieve_artifact->getArtifactById($artifact_id)),
                static fn () => Result::ok(null),
            );

            if (Result::isErr($artifact) || $artifact->value === null) {
                continue;
            }

            $artifacts[] = $artifact->value;
        }

        return Result::ok($artifacts);
    }

    /**
     * @param list<Artifact> $artifacts
     * @return Ok<PaginatedChangesetsCollection>|Err<Fault>
     */
    private function getPaginatedChangesets(array $artifacts, SearchVersionsQuery $search_versions_query, int $limit, int $offset): Ok|Err
    {
        $all_changesets = [];
        foreach ($artifacts as $artifact) {
            array_push($all_changesets, ...$artifact->getChangesets());
        }

        if ($search_versions_query->versions_ids !== []) {
            $target_version_id = $search_versions_query->versions_ids[0];
            $target_changeset  = array_find(
                $all_changesets,
                function (\Tracker_Artifact_Changeset $changeset) use ($target_version_id) {
                    return (int) $changeset->id === $target_version_id;
                }
            );

            if ($target_changeset === null) {
                return Result::err(VersionNotFoundFault::build());
            }

            return Result::ok(new PaginatedChangesetsCollection([$target_changeset], 1));
        }

        usort($all_changesets, static fn ($a, $b) => ($a->getId() < $b->getId()) ? 1 : -1);

        return Result::ok(
            new PaginatedChangesetsCollection(
                array_slice($all_changesets, $offset, $limit),
                count($all_changesets),
            )
        );
    }

    /**
     * @param list<Artifact> $artifacts
     * @return Ok<list<Artifact>>|Err<Fault>
     */
    private function checkUserCanReadAllArtifacts(PFUser $current_user, array $artifacts): Ok|Err
    {
        $permissions = $this->permissions_on_artifacts->retrieveUserPermissionOnArtifacts($current_user, $artifacts, ArtifactPermissionType::PERMISSION_VIEW);
        if (count($permissions->not_allowed) > 0) {
            return Result::err(PartiallyReadableDocumentFault::build());
        }

        return Result::ok($artifacts);
    }
}
