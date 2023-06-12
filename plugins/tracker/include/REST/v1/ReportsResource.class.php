<?php
/**
 * Copyright (c) Enalean, 2013-present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1;

use Luracast\Restler\RestException;
use PFUser;
use Tracker_Artifact_PriorityDao;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tracker_ReportFactory;
use Tracker_URLVerification;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\REST\RESTCollectionTransformer;
use Tuleap\Session\SessionPopulator;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\CachingTrackerPrivateCommentInformationRetriever;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\PermissionChecker;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentInformationRetriever;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupEnabledDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\Report\Renderer\Table\TableRendererForReportRetriever;
use Tuleap\Tracker\Report\Renderer\Table\UsedFieldsRetriever;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\Changeset\ChangesetRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\CommentRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\FlatArtifactRepresentationTransformer;
use Tuleap\Tracker\REST\Artifact\StatusValueRepresentation;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\REST\ReportRepresentation;
use Tuleap\Tracker\REST\v1\Report\MatchingArtifactRepresentationBuilder;
use Tuleap\Tracker\REST\v1\Report\MatchingIdsOrderer;
use Tuleap\Tracker\Semantic\Status\StatusColorForChangesetProvider;
use Tuleap\Tracker\Semantic\Status\StatusValueForChangesetProvider;
use UserManager;

/**
 * Wrapper for Tracker Report related REST methods
 * @psalm-import-type FlatRepresentation from RESTCollectionTransformer
 */
class ReportsResource extends AuthenticatedResource
{
    public const MAX_LIMIT             = 50;
    public const DEFAULT_LIMIT         = 10;
    public const DEFAULT_OFFSET        = 0;
    public const DEFAULT_VALUES        = null;
    public const ALL_VALUES            = 'all';
    public const TABLE_RENDERER_VALUES = 'from_table_renderer';

    /** @var ReportArtifactFactory */
    private $report_artifact_factory;

    public function __construct()
    {
        $artifact_factory              = Tracker_ArtifactFactory::instance();
        $this->report_artifact_factory = new ReportArtifactFactory(
            $artifact_factory,
            new MatchingIdsOrderer(new Tracker_Artifact_PriorityDao()),
        );
    }

    /**
     * @url OPTIONS {id}
     *
     * @param string $id Id of the report
     */
    public function optionsId($id, bool $with_unsaved_changes = false)
    {
        Header::allowOptionsGet();
    }

    /**
     * Get report
     *
     * Get the definition of the given report
     *
     * @url GET {id}
     * @access hybrid
     * @oauth2-scope read:tracker
     *
     * @param int $id Id of the report
     * @param bool $with_unsaved_changes Enable to take into account unsaved changes made to the report on your ongoing session {@from query}{@required false}
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getId(int $id, bool $with_unsaved_changes = false): ReportRepresentation
    {
        $this->checkAccess();
        $user   = UserManager::instance()->getCurrentUser();
        $report = $this->getReportById($user, $id, $with_unsaved_changes);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $report->getTracker()->getProject()
        );

        $rest_report = new ReportRepresentation($report);

        Header::allowOptionsGet();

        return $rest_report;
    }

    /**
     * @url OPTIONS {id}/artifacts
     *
     * @param string $id Id of the report
     */
    public function optionsArtifacts($id, bool $with_unsaved_changes = false)
    {
        Header::allowOptionsGet();
    }

    /**
     * Get artifacts
     *
     * Get artifacts matching criteria of a report.
     *
     * <p>
     * By default it does not return the values of the fields for performance reasons.
     * You can ask to include some specific values with the <strong>values</strong> parameter.<br>
     * Eg:
     * <ul>
     *  <li>…?id=123&values=all            //add all fields values
     *  <li>…?id=123&values=from_table_renderer //add all fields selected in the table renderer of the report
     *  <li>…?id=123&values=               //(empty string) do not add any field values
     * </ul>
     * </p>
     *
     * <p>
     *   "from_table_renderer" values option only work if there is only one table renderer in the report.<br/>
     *    An error will be thrown if there is no or multiple table renderers with no specific choice.<br/>
     *    <strong>Warning:</strong> Please note that artifact link field values are not exported in this value format.
     * </p>
     *
     * <p>
     *   <strong>Warning:</strong> Please note the flat output_format contains a limited set of information and requires
     *   either values=all or values=from_table_renderer
     * </p>
     *
     * @url GET {id}/artifacts
     * @access hybrid
     * @oauth2-scope read:tracker
     *
     * @param int $id Id of the report
     * @param bool $with_unsaved_changes Enable to take into account unsaved changes made to the report on your ongoing session {@from query}{@required false}
     * @param string $values Which fields to include in the response. Default is no field values {@from query}{@choice ,all,from_table_renderer}
     * @param int | null $table_renderer_id Which table renderer to use when values=from_table_renderer {@from query}{@required false}
     * @param string $output_format Format of the response: nested (default) or a simplified and incomplete flat format {@from query}{@choice nested,flat}
     * @psalm-param 'nested'|'flat' $output_format
     * @param int $limit Number of elements displayed per page {@from query}{@min 1} {@max 50}
     * @param int $offset Position of the first element to display {@from query}{@min 0}
     *
     * @return array {@type Tuleap\Tracker\REST\Artifact\ArtifactRepresentation}
     * @psalm-return list<ArtifactRepresentation>|list<FlatRepresentation>
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getArtifacts(
        int $id,
        bool $with_unsaved_changes = false,
        ?string $values = self::DEFAULT_VALUES,
        ?int $table_renderer_id = null,
        string $output_format = 'nested',
        int $limit = self::DEFAULT_LIMIT,
        int $offset = self::DEFAULT_OFFSET,
    ): array {
        $this->checkAccess();
        Header::allowOptionsGet();

        $user   = UserManager::instance()->getCurrentUser();
        $report = $this->getReportById($user, $id, $with_unsaved_changes);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $report->getTracker()->getProject()
        );

        if ($values === self::TABLE_RENDERER_VALUES) {
            $builder = new MatchingArtifactRepresentationBuilder(
                $this->report_artifact_factory,
                new TableRendererForReportRetriever(),
                new UsedFieldsRetriever(),
                new StatusColorForChangesetProvider(new StatusValueForChangesetProvider())
            );

            $artifact_collection = $builder->buildMatchingArtifactRepresentationCollection(
                $user,
                $report,
                $table_renderer_id,
                $limit,
                $offset
            );

            Header::sendPaginationHeaders($limit, $offset, $artifact_collection->getTotalSize(), self::MAX_LIMIT);
            return $this->getArtifactRepresentationsInExpectedFormat($output_format, $artifact_collection->getArtifactRepresentations());
        }

        $with_all_field_values = $values === self::ALL_VALUES;

        $artifact_collection = $this->report_artifact_factory->getArtifactsMatchingReport(
            $report,
            $limit,
            $offset
        );

        Header::sendPaginationHeaders($limit, $offset, $artifact_collection->getTotalSize(), self::MAX_LIMIT);

        $artifact_representations = $this->getListOfArtifactRepresentation(
            $user,
            $artifact_collection->getArtifacts(),
            $with_all_field_values
        );

        return $this->getArtifactRepresentationsInExpectedFormat($output_format, $artifact_representations);
    }

    /**
     * @psalm-param list<ArtifactRepresentation> $artifact_representations
     * @psalm-return list<ArtifactRepresentation>|list<FlatRepresentation>
     */
    private function getArtifactRepresentationsInExpectedFormat(string $output_format, array $artifact_representations): array
    {
        return match ($output_format) {
            'flat' => RESTCollectionTransformer::flattenRepresentations(
                $artifact_representations,
                new FlatArtifactRepresentationTransformer(Tracker_FormElementFactory::instance(), \Codendi_HTMLPurifier::instance())
            ),
            'nested' => $artifact_representations,
        };
    }

    /**
     * @psalm-return list<ArtifactRepresentation>
     */
    private function getListOfArtifactRepresentation(PFUser $user, $artifacts, $with_all_field_values): array
    {
        $form_element_factory = Tracker_FormElementFactory::instance();
        $builder              = new ArtifactRepresentationBuilder(
            $form_element_factory,
            Tracker_ArtifactFactory::instance(),
            new TypeDao(),
            new ChangesetRepresentationBuilder(
                UserManager::instance(),
                $form_element_factory,
                new CommentRepresentationBuilder(
                    CommonMarkInterpreter::build(\Codendi_HTMLPurifier::instance())
                ),
                new PermissionChecker(new CachingTrackerPrivateCommentInformationRetriever(new TrackerPrivateCommentInformationRetriever(new TrackerPrivateCommentUGroupEnabledDao())))
            )
        );

        $build_artifact_representation = function (?Artifact $artifact) use (
            $builder,
            $user,
            $with_all_field_values
        ) {
            if (! $artifact || ! $artifact->userCanView($user)) {
                return;
            }

            if ($with_all_field_values) {
                $tracker_representation = MinimalTrackerRepresentation::build($artifact->getTracker());

                return $builder->getArtifactRepresentationWithFieldValues($user, $artifact, $tracker_representation, StatusValueRepresentation::buildFromArtifact($artifact, $user));
            } else {
                return $builder->getArtifactRepresentation($user, $artifact, StatusValueRepresentation::buildFromArtifact($artifact, $user));
            }
        };

        $list_of_artifact_representation = array_map($build_artifact_representation, $artifacts);

        return array_values(array_filter($list_of_artifact_representation));
    }

    private function getReportById(\PFUser $user, int $id, bool $load_report_from_session): \Tracker_Report
    {
        if ($load_report_from_session) {
            SessionPopulator::populateSessionIfNeeded();
        }

        $report = Tracker_ReportFactory::instance()->getReportById(
            $id,
            $user->getId(),
            $load_report_from_session
        );

        if (! $report) {
            throw new RestException(404);
        }

        $tracker = $report->getTracker();
        if (! $tracker->userCanView($user)) {
            throw new RestException(403);
        }

        ProjectAuthorization::userCanAccessProject($user, $tracker->getProject(), new Tracker_URLVerification());

        return $report;
    }
}
