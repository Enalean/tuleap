<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/system_event/SystemEvent.class.php';

abstract class SystemEvent_FULLTEXTSEARCH_DOCMAN extends SystemEvent {

    /**
     * @var FullTextSearchDocmanActions
     */
    protected $actions;

    /**
     * @var Docman_ItemFactory
     */
    protected $item_factory;

    /**
     * @var Docman_VersionFactory
     */
    protected $version_factory;

    public function injectDependencies(FullTextSearchDocmanActions $actions, Docman_ItemFactory $item_factory, Docman_VersionFactory $version_factory) {
        parent::injectDependencies();
        $this->setFullTextSearchActions($actions)
             ->setItemFactory($item_factory)
             ->setVersionFactory($version_factory);
    }

    public function setFullTextSearchActions(FullTextSearchDocmanActions $actions) {
        $this->actions = $actions;
        return $this;
    }

    public function setItemFactory(Docman_ItemFactory $item_factory) {
        $this->item_factory = $item_factory;
        return $this;
    }

    public function setVersionFactory(Docman_VersionFactory $version_factory) {
        $this->version_factory = $version_factory;
        return $this;

    }

    /**
     * Process the system event
     *
     * @return bool
     */
    public function process() {
        try {
            $group_id = (int)$this->getRequiredParameter(0);
            $item_id  = (int)$this->getRequiredParameter(1);

            $item = $this->getItem($item_id);
            if ($item) {
                if ($this->processItem($item)) {
                    $this->done();
                    return true;
                }
            } else {
                $this->error('Item not found');
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        return false;
    }

    /**
     * Execute action on the given item
     *
     * @see process()
     *
     * @param Docman_Item $item The item
     *
     * @return bool true if success (means status=done), false otherwise
     */
    protected abstract function processItem(Docman_Item $item);

    /**
     * @param int   $item_id The id of the item to retrieve
     * @param array $params  Various parameters for the retrieval. @see Docman_ItemFactory->getItemFromDb()
     *
     * @return Docman_Item
     */
    protected function getItem($item_id, $params = array()) {
        return $this->item_factory->getItemFromDb($item_id, $params);
    }

    /**
     * @return Docman_Version
     */
    protected function getVersion(Docman_Item $item, $version_number) {
        return $this->version_factory->getSpecificVersion($item, $version_number);
    }

    /**
     * @return string a human readable representation of parameters
     */
    public function verbalizeParameters($with_link) {
        $txt = '';

        $group_id = (int)$this->getRequiredParameter(0);
        $item_id  = (int)$this->getRequiredParameter(1);
        $version  = (int)$this->getParameter(2);
        $txt .= 'project: '. $this->verbalizeProjectId($group_id, $with_link) .', item id: '. $this->verbalizeDocmanItemId($group_id, $item_id, $with_link);
        if ($version) {
            $txt .= ', version: '. $version;
        }
        return $txt;
    }

    private function verbalizeDocmanItemId($group_id, $item_id, $with_link) {
        $txt = '#'. $item_id;
        if ($with_link) {
            $txt = '<a href="/plugins/docman/?group_id='. $group_id .'&action=details&id='. $item_id .'&section=properties">'. $txt .'</a>';
        }
        return $txt;
    }
}
?>
