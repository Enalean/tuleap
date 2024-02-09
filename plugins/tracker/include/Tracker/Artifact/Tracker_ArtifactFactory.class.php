<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\AfterNewChangesetHandler;
use Tuleap\Tracker\Artifact\Changeset\ArtifactChangesetSaver;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\Changeset\InitialChangesetCreator;
use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValueSaver;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Artifact\MyArtifactsCollection;
use Tuleap\Tracker\Artifact\PaginatedArtifactDao;
use Tuleap\Tracker\Artifact\RetrieveArtifact;
use Tuleap\Tracker\Artifact\RetrieveViewableArtifact;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_ArtifactFactory implements RetrieveArtifact, RetrieveViewableArtifact, \Tuleap\Tracker\Artifact\SaveArtifact
{
    protected $artifacts;
    /**
     * A protected constructor; prevents direct creation of object
     */
    protected function __construct()
    {
        $this->artifacts = [];
    }

    /**
     * Hold an instance of the class
     */
    protected static $instance;

    /**
     * The singleton method
     *
     * @return Tracker_ArtifactFactory an instance of this class
     */
    public static function instance()
    {
        if (! isset(self::$instance)) {
            $c = self::class;
            self::setInstance(new $c());
        }
        return self::$instance;
    }

    /**
     * Allows to inject a fake factory for test. DO NOT USE IT IN PRODUCTION!
     *
     */
    public static function setInstance(Tracker_ArtifactFactory $factory)
    {
        self::$instance = $factory;
    }

    /**
     * Allows clear factory instance for test. DO NOT USE IT IN PRODUCTION!
     */
    public static function clearInstance()
    {
        self::$instance = null;
    }

    /**
     * Return the artifact with the id $id, or null if not found
     *
     * @param int $id the id of the artifact to retrieve
     */
    public function getArtifactById($id): ?Artifact
    {
        if (! isset($this->artifacts[$id])) {
            $this->artifacts[$id] = null;
            $row                  = $this->getDao()->searchById($id)->getRow();
            if ($row) {
                $this->artifacts[$id] = $this->getInstanceFromRow($row);
            }
        }
        return $this->artifacts[$id];
    }

    /**
     * Return the artifact corresponding to $id the user can access
     *
     * @param int $id
     */
    public function getArtifactByIdUserCanView(PFUser $user, $id): ?Artifact
    {
        $artifact = $this->getArtifactById($id);
        if ($artifact && $artifact->userCanView($user)) {
            return $artifact;
        }
        return null;
    }

    /**
     * Returns all the artifacts of the tracker racker_id
     *
     * @param int $tracker_id the id of the tracker
     *
     * @return Artifact[] identified by id (array() if not found)
     */
    public function getArtifactsByTrackerId($tracker_id)
    {
        $artifacts = [];
        foreach ($this->getDao()->searchByTrackerId($tracker_id) as $row) {
            $artifacts[$row['id']] = $this->getInstanceFromRow($row);
        }
        return $artifacts;
    }

    /**
     * @param int $tracker_id The id of the tracker
     * @param int $limit      The maximum number of artifacts returned
     * @param int $offset
     *
     * @return Tracker_Artifact_PaginatedArtifacts
     */
    public function getPaginatedArtifactsByTrackerId($tracker_id, $limit, $offset, $reverse_order)
    {
        $artifacts = [];
        foreach ($this->getDao()->searchPaginatedByTrackerId($tracker_id, $limit, $offset, $reverse_order) as $row) {
            $artifacts[$row['id']] = $this->getInstanceFromRow($row);
        }

        $size = (int) $this->getDao()->foundRows();

        return new Tracker_Artifact_PaginatedArtifacts($artifacts, $size);
    }

    /**
     * @param int[] $tracker_ids
     */
    public function getPaginatedArtifactsByListOfTrackerIds(array $tracker_ids, int $limit, int $offset): Tracker_Artifact_PaginatedArtifacts
    {
        if (empty($tracker_ids)) {
            return new Tracker_Artifact_PaginatedArtifacts([], 0);
        }

        $dao                              = new PaginatedArtifactDao();
        $paginated_by_list_of_tracker_ids = $dao->searchPaginatedByListOfTrackerIds(
            $tracker_ids,
            $limit,
            $offset
        );
        $size                             = $paginated_by_list_of_tracker_ids->total_size;
        if (! $size) {
            return new Tracker_Artifact_PaginatedArtifacts([], 0);
        }

        $artifacts = [];
        foreach ($paginated_by_list_of_tracker_ids->artifact_rows as $row) {
            $artifacts[$row['id']] = $this->getInstanceFromRow($row);
        }


        return new Tracker_Artifact_PaginatedArtifacts($artifacts, $size);
    }

    /**
     * @param int[] $artifact_ids
     */
    public function getPaginatedArtifactsByListOfArtifactIds(array $artifact_ids, int $limit, int $offset): Tracker_Artifact_PaginatedArtifacts
    {
        if (empty($artifact_ids)) {
            return new Tracker_Artifact_PaginatedArtifacts([], 0);
        }

        $dao       = new PaginatedArtifactDao();
        $paginated = $dao->searchPaginatedByListOfArtifactIds($artifact_ids, $limit, $offset);
        $size      = $paginated->total_size;
        if (! $size) {
            return new Tracker_Artifact_PaginatedArtifacts([], 0);
        }

        $artifacts = [];
        foreach ($paginated->artifact_rows as $row) {
            $artifacts[$row['id']] = $this->getInstanceFromRow($row);
        }

        return new Tracker_Artifact_PaginatedArtifacts($artifacts, $size);
    }

    /**
     * Given a list of artifact ids, return corresponding artifact objects if any
     *
     * @param array $artifact_ids
     *
     * @return Artifact[]
     */
    public function getArtifactsByArtifactIdList(array $artifact_ids)
    {
        $artifact_ids            = array_unique($artifact_ids);
        $artifacts               = [];
        $not_cached_artifact_ids = [];

        foreach ($artifact_ids as $artifact_id) {
            if (isset($this->artifacts[$artifact_id])) {
                $artifacts[] = $this->artifacts[$artifact_id];
            } else {
                $not_cached_artifact_ids[] = $artifact_id;
            }
        }

        if (empty($not_cached_artifact_ids)) {
            return $artifacts;
        }

        $rows = $this->getDao()->searchByIds($not_cached_artifact_ids);
        foreach ($rows as $row) {
            $artifact                            = $this->getInstanceFromRow($row);
            $this->artifacts[$artifact->getId()] = $artifact;
            $artifacts[]                         = $artifact;
        }

        return $artifacts;
    }

    /**
     * Returns all the artifacts of the tracker with id $tracker_id the User $user can read
     *
     * @param PFUser $user       User who want to access to artifacts
     * @param int  $tracker_id the id of the tracker
     *
     * @return Artifact[] identified by id (array() if not found)
     */
    public function getArtifactsByTrackerIdUserCanView(PFUser $user, $tracker_id): array
    {
        $artifacts = [];
        foreach ($this->getDao()->searchByTrackerId($tracker_id) as $row) {
            $artifact = $this->getInstanceFromRow($row);
            if ($artifact->userCanView($user)) {
                $artifacts[$row['id']] = $artifact;
            }
        }
        return $artifacts;
    }

    public function getOpenArtifactsByTrackerIdUserCanView(PFUser $user, $tracker_id)
    {
        $artifacts = [];
        foreach ($this->getDao()->searchOpenByTrackerId($tracker_id) as $row) {
            $artifact = $this->getInstanceFromRow($row);
            if ($artifact->userCanView($user)) {
                $artifacts[$row['id']] = $artifact;
            }
        }
        return $artifacts;
    }

    public function getPaginatedPossibleParentArtifactsUserCanView(PFUser $user, int $tracker_id, int $limit, int $offset): Tracker_Artifact_PaginatedArtifacts
    {
        $artifacts = [];
        foreach ($this->getDao()->searchOpenByTrackerIdWithTitle($tracker_id, $limit, $offset)->instanciateWith([$this, 'getInstanceFromRow']) as $artifact) {
            if ($artifact->userCanView($user)) {
                $artifacts[$artifact->getId()] = $artifact;
            }
        }

        $size = (int) $this->getDao()->foundRows();

        return new Tracker_Artifact_PaginatedArtifacts($artifacts, $size);
    }

    /**
     * Returns the "open" artifacts
     *  - assigned to user $user_id OR
     *  - submitted by user $user_id OR
     *  - submitted by or assigned to user $user_id.
     * regarding the callback method (respectively
     *  - searchOpenAssignedToUserId,
     *  - searchOpenSubmittedByUserId
     *  - searchOpenSubmittedByOrAssignedToUserId)
     */
    protected function getUserOpenArtifacts(PFUser $user, string $callback, ?int $offset, ?int $limit): MyArtifactsCollection
    {
        $my_artifacts = new MyArtifactsCollection(TrackerFactory::instance());
        $dar          = $this->getDao()->$callback($user, $offset, $limit);
        $my_artifacts->setTotalNumberOfArtifacts($this->getDao()->foundRows());
        foreach ($dar as $row) {
            $tracker_id  = (int) $row['tracker_id'];
            $artifact_id = (int) $row['id'];

            $tracker = $my_artifacts->setTracker($tracker_id, $user);
            if (! $my_artifacts->trackerHasArtifactId($tracker, $artifact_id)) {
                $artifact = $this->getInstanceFromRow($my_artifacts->getRowAccordingToTrackerPermissions($tracker, $row));
                $my_artifacts->addArtifactForTracker($tracker, $artifact);
            }
        }
        return $my_artifacts;
    }

    /**
     * Returns the "open" artifacts assigned to user $user_id
     */
    public function getUserOpenArtifactsAssignedTo(PFUser $user, ?int $offset = null, ?int $limit = null): MyArtifactsCollection
    {
        return $this->getUserOpenArtifacts($user, 'searchOpenAssignedToUserId', $offset, $limit);
    }

    /**
     * Returns the "open" artifacts submitted by user $user_id
     */
    public function getUserOpenArtifactsSubmittedBy(PFUser $user, ?int $offset = null, ?int $limit = null): MyArtifactsCollection
    {
        return $this->getUserOpenArtifacts($user, 'searchOpenSubmittedByUserId', $offset, $limit);
    }

    /**
     * Returns the "open" artifacts assigned to or submitted by user $user_id
     */
    public function getUserOpenArtifactsSubmittedByOrAssignedTo(PFUser $user, ?int $offset = null, ?int $limit = null): MyArtifactsCollection
    {
        return $this->getUserOpenArtifacts($user, 'searchOpenSubmittedByOrAssignedToUserId', $offset, $limit);
    }

    /**
     * Build an instance of artifact
     *
     * @param array $row the value of the artifact form the db
     */
    public function getInstanceFromRow(array $row): Artifact
    {
        $artifact = new Artifact(
            $row['id'],
            $row['tracker_id'],
            $row['submitted_by'],
            (int) $row['submitted_on'],
            $row['use_artifact_permissions']
        );

        if (isset($row['title'])) {
            $artifact->setTitle($this->getTitleFromRowAsText($row));
        }

        return $artifact;
    }

    protected $dao;
    /**
     * Returns the Tracker_ArtifactDao
     *
     * @return Tracker_ArtifactDao
     */
    public function getDao()
    {
        if (! $this->dao) {
            $this->dao = new Tracker_ArtifactDao();
        }
        return $this->dao;
    }

    public function setDao(Tracker_ArtifactDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Add an artefact in the tracker
     *
     * @param Tracker $tracker           The tracker this artifact belongs to
     * @param array   $fields_data       The data of the artifact to create
     * @param PFUser    $user              The user that want to create the artifact
     * @param bool $send_notification true if a notification must be sent, false otherwise
     *
     * @return Artifact|false false if an error occurred
     */
    public function createArtifact(Tracker $tracker, $fields_data, PFUser $user, bool $should_visit_be_recorded, $send_notification = true)
    {
        $creator = $this->getArtifactCreator();

        $submitted_on = $_SERVER['REQUEST_TIME'];
        $artifact     = $creator->create(
            $tracker,
            $fields_data,
            $user,
            $submitted_on,
            $send_notification,
            $should_visit_be_recorded,
            new \Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext()
        );

        if ($artifact === null) {
            return false;
        }

        return $artifact;
    }

    public function save(Artifact $artifact): bool
    {
        return $this->getDao()->save($artifact->getId(), $artifact->getTrackerId(), $artifact->useArtifactPermissions());
    }

    /**
     * @return Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
     */
    public function getLinkedArtifacts(Artifact $artifact)
    {
        return $this->getDao()->getLinkedArtifacts($artifact->getId())->instanciateWith([$this, 'getInstanceFromRow']);
    }

    /**
     * @return Artifact[]
     */
    public function getChildren(Artifact $artifact): array
    {
        $childrens     = [];
        $children_rows = $this->getDao()->getChildren($artifact->getId());
        foreach ($children_rows as $row) {
            $childrens[] = $this->getInstanceFromRow($row);
        }

        return $childrens;
    }

    public function hasChildren(Artifact $artifact): bool
    {
        $children_count = $this->getChildrenCount([$artifact->getId()]);
        return $children_count[$artifact->getId()] > 0;
    }

    public function hasChildrenInSameProject(Artifact $artifact): bool
    {
        $children_count = $this->getDao()->getChildrenCountInSameProjectOfParent($artifact->getId());
        return $children_count > 0;
    }

    /**
     * @param int[] $artifact_ids
     */
    public function getChildrenCount(array $artifact_ids): array
    {
        return $this->getDao()->getChildrenCount($artifact_ids);
    }

    /**
     * Return children of all given artifacts.
     *
     * @param Artifact[] $artifacts
     *
     * @return Artifact[]
     */
    public function getChildrenForArtifacts(PFUser $user, array $artifacts)
    {
        $children = [];
        if (count($artifacts) > 1) {
            foreach ($this->getDao()->getChildrenForArtifacts($this->getArtifactIds($artifacts))->instanciateWith([$this, 'getInstanceFromRow']) as $artifact) {
                if ($artifact->userCanView($user)) {
                    $children[] = $artifact;
                }
            }
        }
        return $children;
    }

    private function getArtifactIds(array $artifacts)
    {
        return array_map(
            static function (Artifact $artifact) {
                return $artifact->getId();
            },
            $artifacts
        );
    }

    /**
     * Sort an array of artifact according to their priority
     *
     * Nota: it's better to do it directly in SQL.
     *
     * @param Artifact[] $artifacts
     *
     * @return Artifact[]
     */
    public function sortByPriority(array $artifacts)
    {
        if (! $artifacts) {
            return $artifacts;
        }

        $sorted_artifacts = [];
        $ids              = $this->getArtifactIds($artifacts);

        if ($ids) {
            $artifacts        = array_combine($ids, $artifacts);
            $sorted_ids       = $this->getDao()->getIdsSortedByPriority($ids);
            $sorted_artifacts = array_flip($sorted_ids);
        }

        foreach ($sorted_artifacts as $id => $nop) {
            $sorted_artifacts[$id] = $artifacts[$id];
        }

        return $sorted_artifacts;
    }

    /**
     * Build the list of parents according to a list of artifact ids
     *
     * @param int[] $artifact_ids
     *
     * @return Artifact[]
     */
    public function getParents(array $artifact_ids): array
    {
        if (empty($artifact_ids)) {
            return [];
        }

        $parents = [];
        foreach ($this->getDao()->getParents($artifact_ids) as $row) {
            if (! isset($parents[$row['child_id']])) {
                $parents[$row['child_id']] = $this->getInstanceFromRow($row);
            }
        }

        return $parents;
    }

    /**
     * Batch search and update given artifact titles
     *
     * @param Artifact[] $artifacts
     */
    public function setTitles(array $artifacts)
    {
        $artifact_ids = [];
        $index_map    = [];
        foreach ($artifacts as $index_in_source_array => $artifact) {
            $artifact_ids[]                  = $artifact->getId();
            $index_map[$artifact->getId()][] = $index_in_source_array;
        }

        foreach ($this->getDao()->getTitles($artifact_ids) as $row) {
            $artifact_id = $row['id'];
            if (isset($index_map[$artifact_id])) {
                foreach ($index_map[$artifact_id] as $child_id) {
                    if (isset($artifacts[$child_id])) {
                        $artifacts[$child_id]->setTitle($this->getTitleFromRowAsText($row));
                    }
                }
            }
        }
    }

    /**
     * Filters a list of artifact IDs.
     * For each artifact, checks if it is linked by another artifact belonging
     * to a set of trackers.
     *
     * @param array $artifact_ids
     * @param array $tracker_ids
     * @return array Hash array where keys are artifact IDs
     */
    public function getArtifactIdsLinkedToTrackers($artifact_ids, $tracker_ids)
    {
        $filtered_ids = [];

        $result = $this->getDao()->getArtifactIdsLinkedToTrackers($artifact_ids, $tracker_ids);
        if (! $result) {
            return $filtered_ids;
        }

        foreach ($result as $row) {
            $filtered_ids[$row['id']] = true;
        }

        return $filtered_ids;
    }

    public function getTitleFromRowAsText($row)
    {
        if (! isset($row['title_format'])) {
            return $row['title'];
        }

        $purifier = Codendi_HTMLPurifier::instance();
        if ($row['title_format'] === Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT) {
            return $purifier->purify($row['title'], CODENDI_PURIFIER_STRIP_HTML);
        }

        return $row['title'];
    }

    private function getArtifactCreator(): TrackerArtifactCreator
    {
        $fields_validator     = Tracker_Artifact_Changeset_InitialChangesetFieldsValidator::build();
        $logger               = new WrapperLogger(BackendLogger::getDefaultLogger(), self::class);
        $form_element_factory = \Tracker_FormElementFactory::instance();
        $fields_retriever     = new FieldsToBeSavedInSpecificOrderRetriever($form_element_factory);

        $changeset_creator = new InitialChangesetCreator(
            Tracker_Artifact_Changeset_InitialChangesetFieldsValidator::build(),
            $fields_retriever,
            new Tracker_Artifact_Changeset_ChangesetDataInitializator($form_element_factory),
            $logger,
            ArtifactChangesetSaver::build(),
            new AfterNewChangesetHandler($this, $fields_retriever),
            \WorkflowFactory::instance(),
            new InitialChangesetValueSaver()
        );

        return TrackerArtifactCreator::build($changeset_creator, $fields_validator, $logger);
    }
}
