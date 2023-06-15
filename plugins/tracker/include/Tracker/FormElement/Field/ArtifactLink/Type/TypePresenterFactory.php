<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Type;

use EventManager;
use Project;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Events\GetEditableTypesInProject;

class TypePresenterFactory implements AllTypesRetriever, VisibleTypesRetriever, IRetrieveAllUsableTypesInProject, RetrieveCurrentlyUsedArtifactLinkTypesInTracker
{
    /**
     * Add new artifact link types
     *
     * Parameters:
     *  - types: List of existing types
     */
    public const EVENT_GET_ARTIFACTLINK_TYPES = 'event_get_artifactlink_types';

    /**
     * Return presenter from type shortname
     *
     * Parameters:
     *  - type: input type shortname
     */
    public const EVENT_GET_TYPE_PRESENTER = 'event_get_type_presenter';

    /**
     * @var TypeDao
     */
    private $dao;
    /**
     * @var ArtifactLinksUsageDao
     */
    private $artifact_links_usage_dao;

    public function __construct(TypeDao $dao, ArtifactLinksUsageDao $artifact_links_usage_dao)
    {
        $this->dao                      = $dao;
        $this->artifact_links_usage_dao = $artifact_links_usage_dao;
    }

    /** @return TypePresenter[] */
    public function getAllTypes(): array
    {
        $types = $this->getDefaultTypes();
        $types = array_merge($types, $this->getPluginsTypes());
        $types = array_merge($types, $this->getCustomTypes());

        return $types;
    }

    /** @return TypePresenter[] */
    public function getAllTypesEditableInProject(Project $project)
    {
        $types = $this->getDefaultTypes();
        $types = array_merge($types, $this->getPluginsTypesEditableInProject($project));
        $types = array_merge($types, $this->getCustomTypes());

        return $types;
    }

    /**
     * @return TypePresenter[]
     * @psalm-return list<TypePresenter>
     */
    public function getAllUsableTypesInProject(Project $project): array
    {
        $types = $this->getAllTypesEditableInProject($project);
        foreach ($types as $key => $type) {
            if ($this->artifact_links_usage_dao->isTypeDisabledInProject((int) $project->getID(), $type->shortname)) {
                unset($types[$key]);
            }
        }

        return array_values($types);
    }

    /** @return TypePresenter[] */
    public function getOnlyVisibleTypes(): array
    {
        return array_filter(
            $this->getAllTypes(),
            function (TypePresenter $type) {
                return $type->is_visible;
            }
        );
    }

    private function getDefaultTypes()
    {
        return [new TypeIsChildPresenter()];
    }

    private function getPluginsTypes()
    {
        $types = [];

        $params = [
            'types' => &$types,
        ];

        EventManager::instance()->processEvent(
            self::EVENT_GET_ARTIFACTLINK_TYPES,
            $params
        );

        return $types;
    }

    private function getPluginsTypesEditableInProject(Project $project)
    {
        $event = new GetEditableTypesInProject($project);
        EventManager::instance()->processEvent($event);

        return $event->getTypes();
    }

    private function getCustomTypes()
    {
        $types = [];

        foreach ($this->dao->searchAll() as $row) {
            $types[] = $this->instantiateFromRow($row);
        }

        return $types;
    }

    /** @return string[] */
    public function getAllUsedTypesByProject(Project $project): array
    {
        $types = [];

        foreach ($this->dao->searchAllUsedTypesByProject($project->getGroupId()) as $row) {
            $types[] = $row['nature'];
        }

        return $types;
    }

    /** @return TypePresenter[] */
    public function getAllCurrentlyUsedTypePresentersByTracker(\Tracker $tracker): array
    {
        $types = [];

        foreach ($this->dao->searchAllCurrentlyUsedTypesByTrackerID($tracker->getId()) as $row) {
            if ($row['forward_label'] !== null && $row['reverse_label'] !== null) {
                $types[] = TypePresenter::buildVisibleType($row['nature'], $row['forward_label'], $row['reverse_label']);
            } else {
                $types[] = $this->getFromShortname($row['nature']);
            }
        }

        return array_filter($types);
    }

    public function getFromShortname($shortname): ?TypePresenter
    {
        if ($shortname == \Tracker_FormElement_Field_ArtifactLink::NO_TYPE) {
            return new TypePresenter('', '', '', true);
        }

        if ($shortname == \Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD) {
            return new TypeIsChildPresenter();
        }

        $type_presenter = $this->getTypePresenterByShortname($shortname);
        if ($type_presenter) {
            return $type_presenter;
        }

        $row = $this->dao->getFromShortname($shortname);
        if (! $row) {
            return null;
        }
        return $this->instantiateFromRow($row);
    }

    public function getTypeEnabledInProjectFromShortname(Project $project, string $shortname): ?TypePresenter
    {
        if ($this->artifact_links_usage_dao->isTypeDisabledInProject((int) $project->getID(), $shortname)) {
            return null;
        }

        return $this->getFromShortname($shortname);
    }

    private function getTypePresenterByShortname($shortname)
    {
        $presenter = null;

        $params = [
            'presenter' => &$presenter,
            'shortname' => $shortname,
        ];

        EventManager::instance()->processEvent(
            self::EVENT_GET_TYPE_PRESENTER,
            $params
        );

        return $presenter;
    }

    public function instantiateFromRow($row)
    {
        return new TypePresenter(
            $row['shortname'],
            $row['forward_label'],
            $row['reverse_label'],
            true
        );
    }
}
