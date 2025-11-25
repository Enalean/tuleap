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
use Tracker_Artifact_Changeset;
use Tuleap\Artidoc\Builders\RetrievedSectionBuilder;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\PartiallyReadableDocumentFault;
use Tuleap\Artidoc\Domain\Document\UserCannotReadDocumentFault;
use Tuleap\Artidoc\REST\v1\ArtifactSection\ArtifactVersionRepresentation;
use Tuleap\Artidoc\REST\v1\ArtifactSection\ArtifactVersionRepresentationBuilder;
use Tuleap\Artidoc\Stubs\Document\FreetextIdentifierStub;
use Tuleap\Artidoc\Stubs\Document\SearchAllArtifactSectionsStub;
use Tuleap\Artidoc\Stubs\Domain\Document\ArtidocStub;
use Tuleap\Artidoc\Stubs\Domain\Document\RetrieveArtidocWithContextStub;
use Tuleap\Mapper\ValinorMapperBuilderFactory;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnArtifacts;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Stub\Permission\RetrieveUserPermissionOnArtifactsStub;
use Tuleap\Tracker\Test\Stub\Permission\TrackersPermissionsPassthroughRetriever;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;
use Tuleap\User\REST\MinimalUserRepresentation;
use function Psl\Json\encode;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GETArtidocVersionsHandlerTest extends TestCase
{
    private const int CHANGESET_1001_ID = 1001;

    private PFUser $current_user;
    private ArtidocWithContext $artidoc;
    private int $limit;
    private int $offset;
    private PFUser $user_110;
    private PFUser $user_204;
    private Tracker_Artifact_Changeset $changeset_1001;
    private Tracker_Artifact_Changeset $changeset_1002;
    private Tracker_Artifact_Changeset $changeset_1003;
    private Tracker_Artifact_Changeset $changeset_1004;
    private ProvideUserAvatarUrlStub $provide_user_avatar_url;
    private RetrieveArtidocWithContextStub $artidoc_retriever;
    private SearchAllArtifactSectionsStub $sections_retriever;
    private RetrieveArtifactStub $artifact_retriever;
    private RetrieveUserPermissionOnArtifacts $artifact_permissions_retriever;
    private string $query = '';

    #[\Override]
    protected function setUp(): void
    {
        $this->current_user = UserTestBuilder::buildWithId(145);
        $this->artidoc      = new ArtidocWithContext(ArtidocStub::build());
        $this->limit        = 50;
        $this->offset       = 0;

        $this->user_110 = UserTestBuilder::buildWithId(110);
        $this->user_204 = UserTestBuilder::buildWithId(204);

        $this->changeset_1001 = ChangesetTestBuilder::aChangeset(self::CHANGESET_1001_ID)
            ->submittedBy((int) $this->user_110->getId())
            ->submittedOn(1480673101) // 2016-12-02T11:05:01
            ->build();

        $this->changeset_1002 = ChangesetTestBuilder::aChangeset(1002)
            ->submittedBy((int) $this->user_204->getId())
            ->submittedOn(1519685514) // 2018-02-26T23:51:54
            ->build();

        $this->changeset_1003 = ChangesetTestBuilder::aChangeset(1003)
            ->submittedBy((int) $this->user_110->getId())
            ->submittedOn(1531014435) // 2018-07-08T03:47:15
            ->build();

        $this->changeset_1004 = ChangesetTestBuilder::aChangeset(1004)
            ->submittedBy((int) $this->user_110->getId())
            ->submittedOn(1694788177) // 2023-09-15T16:29:37
            ->build();

        $this->provide_user_avatar_url = ProvideUserAvatarUrlStub::build();

        $artifact_1 = ArtifactTestBuilder::anArtifact(1)
            ->withChangesets($this->changeset_1004)
            ->build();
        $artifact_2 = ArtifactTestBuilder::anArtifact(2)
            ->withChangesets($this->changeset_1001, $this->changeset_1002)
            ->build();
        $artifact_3 = ArtifactTestBuilder::anArtifact(3)
            ->withChangesets($this->changeset_1003)
            ->build();

        $section_1 = RetrievedSectionBuilder::anArtifactSection($this->artidoc->document->getId(), $artifact_2->getId())->build();
        $section_2 = RetrievedSectionBuilder::anArtifactSection($this->artidoc->document->getId(), $artifact_3->getId())->build();
        $section_3 = RetrievedSectionBuilder::anArtifactSection($this->artidoc->document->getId(), $artifact_1->getId())->build();

        $this->artidoc_retriever              = RetrieveArtidocWithContextStub::withDocumentUserCanRead($this->artidoc);
        $this->sections_retriever             = SearchAllArtifactSectionsStub::build()->withSections($this->artidoc, $section_1, $section_2, $section_3);
        $this->artifact_retriever             = RetrieveArtifactStub::withArtifacts($artifact_1, $artifact_2, $artifact_3);
        $this->artifact_permissions_retriever = new TrackersPermissionsPassthroughRetriever();
    }

    /**
     * @return Ok<PaginatedArtidocVersionRepresentationsCollection>|Err<Fault>
     */
    private function handle(): Ok|Err
    {
        $handler = new GETArtidocVersionsHandler(
            $this->artidoc_retriever,
            $this->sections_retriever,
            $this->artifact_retriever,
            $this->artifact_permissions_retriever,
            new ArtifactVersionRepresentationBuilder(
                $this->provide_user_avatar_url,
                RetrieveUserByIdStub::withUsers($this->user_110, $this->user_204),
            ),
            new QueryToSearchVersionsQueryConverter(ValinorMapperBuilderFactory::mapperBuilder()->mapper()),
        );

        return $handler->handle(
            $this->current_user,
            $this->artidoc->document->getId(),
            $this->query,
            $this->limit,
            $this->offset
        );
    }

    private function buildMinimalUserRepresentation(PFUser $user): MinimalUserRepresentation
    {
        return MinimalUserRepresentation::build($user, $this->provide_user_avatar_url);
    }

    public function testHappyPath(): void
    {
        $paginated_versions = $this->handle();

        self::assertTrue(Result::isOk($paginated_versions));
        self::assertEquals(
            [
                new ArtifactVersionRepresentation((int) $this->changeset_1004->id, '2023-09-15T16:29:37+02:00', $this->buildMinimalUserRepresentation($this->user_110)),
                new ArtifactVersionRepresentation((int) $this->changeset_1003->id, '2018-07-08T03:47:15+02:00', $this->buildMinimalUserRepresentation($this->user_110)),
                new ArtifactVersionRepresentation((int) $this->changeset_1002->id, '2018-02-26T23:51:54+01:00', $this->buildMinimalUserRepresentation($this->user_204)),
                new ArtifactVersionRepresentation((int) $this->changeset_1001->id, '2016-12-02T11:05:01+01:00', $this->buildMinimalUserRepresentation($this->user_110)),
            ],
            $paginated_versions->value->versions
        );
    }

    public function testItReturnsPaginatedVersions(): void
    {
        $this->limit  = 1;
        $this->offset = 2;

        $paginated_versions = $this->handle();

        self::assertTrue(Result::isOk($paginated_versions));

        $versions_collection = $paginated_versions->value;

        self::assertCount(1, $versions_collection->versions);
        self::assertSame($this->changeset_1002->id, $versions_collection->versions[0]->id);
        self::assertSame(4, $versions_collection->total);
        self::assertSame($this->limit, $versions_collection->limit);
        self::assertSame($this->offset, $versions_collection->offset);
    }

    public function testItReturnsEmptyArrayWhenArtidocHasNoArtifactSections(): void
    {
        $freetext_section_1 = RetrievedSectionBuilder::aFreeTextSection(
            $this->artidoc->document->getId(),
            FreetextIdentifierStub::create()
        )->build();

        $this->sections_retriever = SearchAllArtifactSectionsStub::build()->withSections($this->artidoc, $freetext_section_1);
        $this->artifact_retriever = RetrieveArtifactStub::withNoArtifact();

        $paginated_versions = $this->handle();

        self::assertTrue(Result::isOk($paginated_versions));
        self::assertEmpty($paginated_versions->value->versions);
        self::assertSame(0, $paginated_versions->value->total);
    }

    public function testItReturnsUserCannotReadDocumentFaultWhenUserCannotReadTheArtidoc(): void
    {
        $this->artidoc_retriever = RetrieveArtidocWithContextStub::withoutDocument();

        $paginated_versions = $this->handle();

        self::assertTrue(Result::isErr($paginated_versions));
        self::assertInstanceOf(UserCannotReadDocumentFault::class, $paginated_versions->error);
    }

    public function testItReturnsPartiallyReadableDocumentFaultWhenUserCannotReadAnArtifactSection(): void
    {
        $this->artifact_permissions_retriever = RetrieveUserPermissionOnArtifactsStub::build();

        $paginated_versions = $this->handle();

        self::assertTrue(Result::isErr($paginated_versions));
        self::assertInstanceOf(PartiallyReadableDocumentFault::class, $paginated_versions->error);
    }

    public function testItReturnsOnlyTheTargetVersion(): void
    {
        $this->query = encode(['versions_ids' => [self::CHANGESET_1001_ID]]);

        $paginated_versions = $this->handle();

        self::assertTrue(Result::isOk($paginated_versions));
        self::assertSame(1, $paginated_versions->value->total);
        self::assertEquals(
            [
                new ArtifactVersionRepresentation((int) $this->changeset_1001->id, '2016-12-02T11:05:01+01:00', $this->buildMinimalUserRepresentation($this->user_110)),
            ],
            $paginated_versions->value->versions
        );
    }

    public function testItReturnsAVersionNotFoundFaultWhenTargetVersionIsNotFound(): void
    {
        $this->query = encode(['versions_ids' => [14200]]);

        $paginated_versions = $this->handle();

        self::assertTrue(Result::isErr($paginated_versions));
        self::assertInstanceOf(VersionNotFoundFault::class, $paginated_versions->error);
    }
}
