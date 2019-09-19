<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature;

use EventManager;
use Project;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Events\GetEditableTypesInProject;

class NaturePresenterFactory
{
    /**
     * Add new artifact link natures
     *
     * Parameters:
     *  - natures: List of existing natures
     */
    public const EVENT_GET_ARTIFACTLINK_NATURES = 'event_get_artifactlink_natures';

    /**
     * Return presneter from nature shortname
     *
     * Parameters:
     *  - nature: input nature shortname
     */
    public const EVENT_GET_NATURE_PRESENTER = 'event_get_nature_presenter';

    /**
     * @var NatureDao
     */
    private $dao;
    /**
     * @var ArtifactLinksUsageDao
     */
    private $artifact_links_usage_dao;

    public function __construct(NatureDao $dao, ArtifactLinksUsageDao $artifact_links_usage_dao)
    {
        $this->dao                      = $dao;
        $this->artifact_links_usage_dao = $artifact_links_usage_dao;
    }

    /** @return NaturePresenter[] */
    public function getAllNatures()
    {
        $natures = $this->getDefaultNatures();
        $natures = array_merge($natures, $this->getPluginsNatures());
        $natures = array_merge($natures, $this->getCustomNatures());

        return $natures;
    }

    /** @return NaturePresenter[] */
    public function getAllTypesEditableInProject(Project $project)
    {
        $types = $this->getDefaultNatures();
        $types = array_merge($types, $this->getPluginsTypesEditableInProject($project));
        $types = array_merge($types, $this->getCustomNatures());

        return $types;
    }

    /** @return NaturePresenter[] */
    public function getAllUsableTypesInProject(Project $project)
    {
        $types = $this->getAllTypesEditableInProject($project);
        foreach ($types as $key => $type) {
            if ($this->artifact_links_usage_dao->isTypeDisabledInProject($project->getID(), $type->shortname)) {
                unset($types[$key]);
            }
        }

        return $types;
    }

    public function getOnlyVisibleNatures()
    {
        return array_filter(
            $this->getAllNatures(),
            function (NaturePresenter $nature) {
                return $nature->is_visible;
            }
        );
    }

    private function getDefaultNatures()
    {
        return array(new NatureIsChildPresenter());
    }

    private function getPluginsNatures()
    {
        $natures = array();

        $params  = array(
            'natures' => &$natures
        );

        EventManager::instance()->processEvent(
            self::EVENT_GET_ARTIFACTLINK_NATURES,
            $params
        );

        return $natures;
    }

    private function getPluginsTypesEditableInProject(Project $project)
    {
        $event = new GetEditableTypesInProject($project);
        EventManager::instance()->processEvent($event);

        return $event->getTypes();
    }

    private function getCustomNatures()
    {
        $natures = array();

        foreach ($this->dao->searchAll() as $row) {
            $natures[] = $this->instantiateFromRow($row);
        }

        return $natures;
    }

    /** @return NaturePresenter[] */
    public function getAllUsedNaturesByProject(Project $project)
    {
        $natures = array();

        foreach ($this->dao->searchAllUsedNatureByProject($project->getGroupId()) as $row) {
            $natures[] = $row['nature'];
        }

        return $natures;
    }

    /** @return NaturePresenter | null */
    public function getFromShortname($shortname)
    {
        if ($shortname == \Tracker_FormElement_Field_ArtifactLink::NO_NATURE) {
            return new NaturePresenter('', '', '', true);
        }

        if ($shortname == \Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD) {
            return new NatureIsChildPresenter();
        }

        $nature_presenter = $this->getNaturePresenterByShortname($shortname);
        if ($nature_presenter) {
            return $nature_presenter;
        }

        $row = $this->dao->getFromShortname($shortname);
        if (!$row) {
            return null;
        }
        return $this->instantiateFromRow($row);
    }

    private function getNaturePresenterByShortname($shortname)
    {
        $presenter = null;

        $params  = array(
            'presenter' => &$presenter,
            'shortname' => $shortname
        );

        EventManager::instance()->processEvent(
            self::EVENT_GET_NATURE_PRESENTER,
            $params
        );

        return $presenter;
    }

    public function instantiateFromRow($row)
    {
        return new NaturePresenter(
            $row['shortname'],
            $row['forward_label'],
            $row['reverse_label'],
            true
        );
    }
}
