<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class FullTextSearch_DocmanSystemEventManager {

    /**
     * @var fulltextsearchPlugin
     */
    private $plugin;

    /**
     * @var ElasticSearch_IndexClientFacade
     */
    private $index_client;

    /**
     * @var SystemEventManager
     */
    private $system_event_manager;

    public function __construct(
            SystemEventManager $system_event_manager,
            ElasticSearch_IndexClientFacade $index_client,
            fulltextsearchPlugin $plugin
            ) {
        $this->system_event_manager = $system_event_manager;
        $this->index_client         = $index_client;
        $this->plugin               = $plugin;
    }

    public function getSystemEventClass($type, &$class, &$dependencies) {
        switch($type) {
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_INDEX::NAME:
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_EMPTY_INDEX::NAME:
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATE::NAME:
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATE_PERMISSIONS::NAME:
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATE_METADATA::NAME:
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_DELETE::NAME:
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_APPROVAL_TABLE_COMMENT::NAME:
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_REINDEX_PROJECT::NAME:
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_WIKI_INDEX::NAME:
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_WIKI_UPDATE::NAME:
                $class = 'SystemEvent_'. $type;
                $dependencies = array(
                    $this->getDocmanActions(),
                    new Docman_ItemFactory(),
                    new Docman_VersionFactory()
                );
                break;
        }
    }

    private function getDocmanActions() {
        return new FullTextSearchDocmanActions(
            $this->index_client,
            new ElasticSearch_1_2_RequestDocmanDataFactory(
                $this->getBareDocmanMetadataFactory(),
                new Docman_PermissionsItemManager(),
                new Docman_ApprovalTableFactoriesFactory()
            ),
            new BackendLogger()
        );
    }

    private function getBareDocmanMetadataFactory() {
        $empty_group_id = 0;

        return new Docman_MetadataFactory($empty_group_id);
    }

    public function queueUpdateMetadata(Docman_Item $item) {
         if ($this->plugin->isAllowed($item->getGroupId())) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATE_METADATA::NAME,
                $this->getDocmanSerializedParameters($item),
                SystemEvent::PRIORITY_MEDIUM
            );
        }
    }

    public function queueNewDocument(Docman_Item $item, Docman_Version $version) {
        if ($this->plugin->isAllowed($item->getGroupId())) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_DOCMAN_INDEX::NAME,
                $this->getDocmanSerializedParameters($item, array($version->getNumber())),
                SystemEvent::PRIORITY_MEDIUM
            );
        }
    }

    public function queueNewEmptyDocument(Docman_Item $item) {
        if ($this->plugin->isAllowed($item->getGroupId())) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_DOCMAN_EMPTY_INDEX::NAME,
                $this->getDocmanSerializedParameters($item),
                SystemEvent::PRIORITY_MEDIUM
            );
        }
    }

    public function queueNewWikiDocument(Docman_Item $item) {
        if ($this->plugin->isAllowed($item->getGroupId())) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_DOCMAN_WIKI_INDEX::NAME,
                $this->getDocmanSerializedParameters($item),
                SystemEvent::PRIORITY_MEDIUM
            );
        }
    }

    public function queueDeleteDocument(Docman_Item $item) {
        if ($this->plugin->isAllowed($item->getGroupId())) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_DOCMAN_DELETE::NAME,
                $this->getDocmanSerializedParameters($item),
                SystemEvent::PRIORITY_HIGH
            );
        }
    }

    public function queueUpdateDocumentPermissions(Docman_Item $item) {
        if ($this->plugin->isAllowed($item->getGroupId())) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATE_PERMISSIONS::NAME,
                $this->getDocmanSerializedParameters($item),
                SystemEvent::PRIORITY_HIGH
            );
        }
    }

    public function queueNewDocumentVersion(Docman_Item $item, Docman_Version $version) {
        if ($this->plugin->isAllowed($item->getGroupId()) && $version->getNumber() > 1) {
            // will be done in plugin_docman_after_new_document since we
            // receive both event for a new document
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATE::NAME,
                $this->getDocmanSerializedParameters($item, array($version->getNumber())),
                SystemEvent::PRIORITY_MEDIUM
            );
        }
    }

    public function queueNewWikiDocumentVersion(Docman_Item $item, $wiki_metadata) {
        if ($this->plugin->isAllowed($item->getGroupId())) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_DOCMAN_WIKI_UPDATE::NAME,
                $this->getDocmanSerializedParameters($item, array($wiki_metadata)),
                SystemEvent::PRIORITY_MEDIUM
            );
        }
    }

    public function queueNewApprovalTableComment(Docman_Item $item, $version_nb, Docman_ApprovalTable $table, Docman_ApprovalReviewer $review) {
        if ($this->plugin->isAllowed($item->getGroupId())) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_DOCMAN_APPROVAL_TABLE_COMMENT::NAME,
                $this->getDocmanSerializedParameters($item, array($version_nb, $table->getId(), $review->getId())),
                SystemEvent::PRIORITY_MEDIUM
            );
        }
    }

    public function queueDocmanProjectReindexation($project_id) {
        if ($this->plugin->isAllowed($project_id)) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_DOCMAN_REINDEX_PROJECT::NAME,
                $project_id,
                SystemEvent::PRIORITY_LOW
            );
        }
    }

    private function getDocmanSerializedParameters(Docman_Item $item, array $additional_params = array()) {
        return implode(
            SystemEvent::PARAMETER_SEPARATOR,
            array_merge(
                array($item->getGroupId(), $item->getId()),
                $additional_params
            )
        );
    }
}
