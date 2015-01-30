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
     * @var Logger
     */
    private $logger;

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
        fulltextsearchPlugin $plugin,
        Logger $logger
    ) {
        $this->system_event_manager = $system_event_manager;
        $this->index_client         = $index_client;
        $this->plugin               = $plugin;
        $this->logger               = $logger;
    }

    public function getSystemEventClass($type, &$class, &$dependencies) {
        switch($type) {
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_INDEX::NAME:
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_EMPTY_INDEX::NAME:
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_LINK_INDEX::NAME:
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_FOLDER_INDEX::NAME:
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATE::NAME:
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATELINK::NAME:
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATE_PERMISSIONS::NAME:
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATE_METADATA::NAME:
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_DELETE::NAME:
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_APPROVAL_TABLE_COMMENT::NAME:
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_REINDEX_PROJECT::NAME:
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_COPY::NAME:
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_WIKI_INDEX::NAME:
            case SystemEvent_FULLTEXTSEARCH_DOCMAN_WIKI_UPDATE::NAME:
                $class = 'SystemEvent_'. $type;
                $dependencies = array(
                    $this->getDocmanActions(),
                    new Docman_ItemFactory(),
                    new Docman_VersionFactory(),
                    new Docman_LinkVersionFactory()
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
            $this->logger
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
                SystemEvent::PRIORITY_MEDIUM,
                SystemEvent::OWNER_APP
            );
        }
    }

    public function queueNewDocument(Docman_Item $item, Docman_Version $version) {
        if ($this->plugin->isAllowed($item->getGroupId())) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_DOCMAN_INDEX::NAME,
                $this->getDocmanSerializedParameters($item, array($version->getNumber())),
                SystemEvent::PRIORITY_MEDIUM,
                SystemEvent::OWNER_APP
            );
        }
    }

    public function queueNewEmptyDocument(Docman_Item $item) {
        if ($this->plugin->isAllowed($item->getGroupId())) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_DOCMAN_EMPTY_INDEX::NAME,
                $this->getDocmanSerializedParameters($item),
                SystemEvent::PRIORITY_MEDIUM,
                SystemEvent::OWNER_APP
            );
        }
    }

    public function queueNewLinkDocument(Docman_Item $item) {
        if ($this->plugin->isAllowed($item->getGroupId())) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_DOCMAN_LINK_INDEX::NAME,
                $this->getDocmanSerializedParameters($item),
                SystemEvent::PRIORITY_MEDIUM,
                SystemEvent::OWNER_APP
            );
        }
    }

    public function queueNewDocmanFolder(Docman_Item $item) {
        if ($this->plugin->isAllowed($item->getGroupId())) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_DOCMAN_FOLDER_INDEX::NAME,
                $this->getDocmanSerializedParameters($item),
                SystemEvent::PRIORITY_MEDIUM,
                SystemEvent::OWNER_APP
            );
        }
    }

    public function queueNewWikiDocument(Docman_Item $item) {
        if ($this->plugin->isAllowed($item->getGroupId())) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_DOCMAN_WIKI_INDEX::NAME,
                $this->getDocmanSerializedParameters($item),
                SystemEvent::PRIORITY_MEDIUM,
                SystemEvent::OWNER_APP
            );
        }
    }

    public function queueDeleteDocument(Docman_Item $item) {
        if ($this->plugin->isAllowed($item->getGroupId())) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_DOCMAN_DELETE::NAME,
                $this->getDocmanSerializedParameters($item),
                SystemEvent::PRIORITY_HIGH,
                SystemEvent::OWNER_APP
            );
        }
    }

    public function queueUpdateDocumentPermissions(Docman_Item $item) {
        if ($this->plugin->isAllowed($item->getGroupId())) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATE_PERMISSIONS::NAME,
                $this->getDocmanSerializedParameters($item),
                SystemEvent::PRIORITY_HIGH,
                SystemEvent::OWNER_APP
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
                SystemEvent::PRIORITY_MEDIUM,
                SystemEvent::OWNER_APP
            );
        }
    }

    public function queueNewDocumentLinkVersion(Docman_Link $item, Docman_LinkVersion $version) {
        if ($this->plugin->isAllowed($item->getGroupId()) && $version->getNumber() > 1) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATELINK::NAME,
                $this->getDocmanSerializedParameters($item, array($version->getNumber())),
                SystemEvent::PRIORITY_MEDIUM,
                SystemEvent::OWNER_APP
            );
        }
    }

    public function queueNewWikiDocumentVersion(Docman_Item $item) {
        if ($this->plugin->isAllowed($item->getGroupId())) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_DOCMAN_WIKI_UPDATE::NAME,
                $this->getDocmanSerializedParameters($item),
                SystemEvent::PRIORITY_MEDIUM,
                SystemEvent::OWNER_APP
            );
        }
    }

    public function queueNewApprovalTableComment(Docman_Item $item) {
        if ($this->plugin->isAllowed($item->getGroupId())) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_DOCMAN_APPROVAL_TABLE_COMMENT::NAME,
                $this->getDocmanSerializedParameters($item),
                SystemEvent::PRIORITY_MEDIUM,
                SystemEvent::OWNER_APP
            );
        }
    }

    public function queueDocmanProjectReindexation($project_id) {
        if ($this->plugin->isAllowed($project_id)) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_DOCMAN_REINDEX_PROJECT::NAME,
                $project_id,
                SystemEvent::PRIORITY_LOW,
                SystemEvent::OWNER_APP
            );
        }
    }

    public function queueCopyDocument(Docman_Item $item) {
        if ($this->plugin->isAllowed($item->getGroupId())) {
            $this->system_event_manager->createEvent(
                SystemEvent_FULLTEXTSEARCH_DOCMAN_COPY::NAME,
                $this->getDocmanSerializedParameters($item),
                SystemEvent::PRIORITY_MEDIUM,
                SystemEvent::OWNER_APP
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
