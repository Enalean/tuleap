<?php
/**
 * Copyright (c) Enalean, 2012 - 2016. All Rights Reserved.
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
use Tuleap\Admin\AdminPageRenderer;

/**
 * Controller for site admin views
 */
class FullTextSearch_Controller_Admin extends FullTextSearch_Controller_Search {

    /* FullTextSearch_DocmanSystemEventManager */
    private $docman_system_event_manager;

    /* FullTextSearch_WikiSystemEventManager */
    private $wiki_system_event_manager;

    /* FullTextSearch_TrackerSystemEventManager */
    private $tracker_system_event_manager;
    /**
     * @var AdminPageRenderer
     */
    private $admin_page_renderer;

    public function __construct(
        Codendi_Request $request,
        FullTextSearch_ISearchDocumentsForAdmin $client,
        FullTextSearch_DocmanSystemEventManager $docman_system_event_manager,
        FullTextSearch_WikiSystemEventManager $wiki_system_event_manager,
        FullTextSearch_TrackerSystemEventManager $tracker_system_event_manager,
        AdminPageRenderer $admin_page_renderer
    ) {
        parent::__construct($request, $client);

        $this->docman_system_event_manager  = $docman_system_event_manager;
        $this->wiki_system_event_manager    = $wiki_system_event_manager;
        $this->tracker_system_event_manager = $tracker_system_event_manager;
        $this->admin_page_renderer          = $admin_page_renderer;
    }

    public function getIndexStatus() {
        return $this->client->getStatus();
    }

    public function index() {
        $admin_presenter = new FullTextSearch_Presenter_AdminPresenter();
        $this->admin_page_renderer->renderAPresenter(
            $GLOBALS['Language']->getText('plugin_fulltextsearch', 'admin_title'),
            FULLTEXTSEARCH_TEMPLATE_DIR,
            'admin',
            $admin_presenter
        );
    }

    public function reindexDocman($project_id)
    {
        if (! $this->docman_system_event_manager->isProjectReindexationAlreadyQueued($project_id)) {
            $this->docman_system_event_manager->queueDocmanProjectReindexation($project_id);
        }
    }

    public function reindexWiki($project_id)
    {
        if (! $this->wiki_system_event_manager->isProjectReindexationAlreadyQueued($project_id)) {
            $this->wiki_system_event_manager->queueWikiProjectReindexation($project_id);
        }
    }

    public function reindexTrackers($project_id)
    {
        if (! $this->tracker_system_event_manager->isProjectReindexationAlreadyQueued($project_id)) {
            $this->tracker_system_event_manager->queueTrackersProjectReindexation($project_id);
        }
    }

    public function reindexTracker(Tracker $tracker)
    {
        $this->tracker_system_event_manager->queueTrackerReindexation($tracker);
    }
}
