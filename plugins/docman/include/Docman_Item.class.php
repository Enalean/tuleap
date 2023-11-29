<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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

use Tuleap\Docman\Item\ItemVisitor;

/**
 * Item is a transport object (aka container) used to share data between
 * Model/Controler and View layer of the application
 */
class Docman_Item
{
    public $id          = null;
    public $title       = null;
    public $titlekey    = null;
    public $description = null;

    public $createDate = null;
    public $updateDate = null;
    public $deleteDate = null;

    public $rank = null;

    public $parentId = null;
    public $groupId  = null;
    public $ownerId  = null;

    public $status = null;

    public $obsolescenceDate = null;
    public $isObsolete       = null;

    protected $_actions  = [];
    protected $_metadata = [];
    public $pathId       = [];
    public $pathTitle    = [];



    public function __construct($data = null)
    {
        if ($data) {
            $this->initFromRow($data);
        }
    }

    public function setId($id)
    {
        $this->id = (int) $id;
    }

    /**
     * @psalm-taint-escape file
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        if ('roottitle_lbl_key' === $title) {
            $this->title    = dgettext('tuleap-docman', 'Project Documentation');
            $this->titlekey = $title;
        } else {
            $this->title = $title;
        }
    }

    public function getTitle($key = false)
    {
        if ($key && $this->titlekey !== null) {
            return $this->titlekey;
        }
        return $this->title;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setCreateDate($date)
    {
        $this->createDate = (int) $date;
    }

    public function getCreateDate()
    {
        return $this->createDate;
    }

    public function setUpdateDate($date)
    {
        $this->updateDate = (int) $date;
    }

    public function getUpdateDate()
    {
        return $this->updateDate;
    }

    public function setDeleteDate($date)
    {
        $this->deleteDate = (int) $date;
    }

    public function getDeleteDate()
    {
        return $this->deleteDate;
    }

    public function setRank($rank)
    {
        $this->rank = (int) $rank;
    }

    public function getRank()
    {
        return $this->rank;
    }

    public function setParentId($id)
    {
        $this->parentId = (int) $id;
    }

    public function getParentId()
    {
        return $this->parentId;
    }

    public function setGroupId($id)
    {
        $this->groupId = (int) $id;
    }

    public function getGroupId()
    {
        return $this->groupId;
    }

    public function setOwnerId($id)
    {
        $this->ownerId = (int) $id;
    }

    public function getOwnerId()
    {
        return $this->ownerId;
    }

    public function setStatus($v)
    {
        $this->status = (int) $v;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setObsolescenceDate($v)
    {
        $this->obsolescenceDate = (int) $v;
        $this->isObsolete       = null; // Clear cache
    }

    public function getObsolescenceDate()
    {
        return $this->obsolescenceDate;
    }

    /*
     * Convenient accessors
     */
    public function isObsolete()
    {
        if ($this->isObsolete == null) {
            $this->isObsolete = false;
            $date             = $this->getObsolescenceDate();
            if ($date > 0) {
                $today            = getdate();
                $time             = mktime(0, 0, 1, $today['mon'], $today['mday'], $today['year']);
                $this->isObsolete = ($date < $time);
            }
        }
        return $this->isObsolete;
    }

    public function getType()
    {
        return dgettext('tuleap-docman', 'Docman item');
    }

    public function initFromRow($row)
    {
        if (isset($row['item_id'])) {
            $this->setId($row['item_id']);
        }
        if (isset($row['title'])) {
            $this->setTitle($row['title']);
        }
        if (isset($row['description'])) {
            $this->setDescription($row['description']);
        }
        if (isset($row['create_date'])) {
            $this->setCreateDate($row['create_date']);
        }
        if (isset($row['update_date'])) {
            $this->setUpdateDate($row['update_date']);
        }
        if (isset($row['delete_date'])) {
            $this->setDeleteDate($row['delete_date']);
        }
        if (isset($row['rank'])) {
            $this->setRank($row['rank']);
        }
        if (isset($row['parent_id'])) {
            $this->setParentId($row['parent_id']);
        }
        if (isset($row['group_id'])) {
            $this->setGroupId($row['group_id']);
        }
        if (isset($row['user_id'])) {
            $this->setOwnerId($row['user_id']);
        }
        if (isset($row['status'])) {
            $this->setStatus($row['status']);
        }
        if (isset($row['obsolescence_date'])) {
            $this->setObsolescenceDate($row['obsolescence_date']);
        }
    }

    public function toRow()
    {
        $row                      = [];
        $row['item_id']           = $this->getId();
        $row['title']             = $this->getTitle(true);
        $row['description']       = $this->getDescription();
        $row['create_date']       = $this->getCreateDate();
        $row['update_date']       = $this->getUpdateDate();
        $row['delete_date']       = $this->getDeleteDate();
        $row['rank']              = $this->getRank();
        $row['parent_id']         = $this->getParentId();
        $row['group_id']          = $this->getGroupId();
        $row['user_id']           = $this->getOwnerId();
        $row['status']            = $this->getStatus();
        $row['obsolescence_date'] = $this->getObsolescenceDate();
        return $row;
    }

    /**
     * @template ItemVisitorReturnType
     * @psalm-param ItemVisitor<ItemVisitorReturnType> $visitor
     * @psalm-return ItemVisitorReturnType
     *
     * @psalm-taint-specialize
     */
    public function accept(ItemVisitor $visitor, array $params = [])
    {
        return $visitor->visitItem($this, $params);
    }

    public function addMetadata(&$metadata)
    {
        $this->_metadata[$metadata->getLabel()] = $metadata;
    }

    public function setMetadata(&$metadata)
    {
        $this->_metadata = $metadata;
    }

    /**
     * @return Docman_Metadata[]
     */
    public function getMetadata()
    {
        return $this->_metadata;
    }

    public function getMetadataIterator()
    {
        return new ArrayIterator($this->_metadata);
    }

    public function getHardCodedMetadataValue($label)
    {
        $value = null;

        switch ($label) {
            case 'title':
                $value = $this->getTitle();
                break;

            case 'description':
                $value = $this->getDescription();
                break;

            case 'owner':
                $value = $this->getOwnerId();
                break;

            case 'create_date':
                $value = $this->getCreateDate();
                break;

            case 'update_date':
                $value = $this->getUpdateDate();
                break;

            case 'status':
                $status      = $this->getStatus();
                $status_list = [];
                if ($status !== null) {
                    $status_list[] = Docman_MetadataListOfValuesElementFactory::getStatusList($status);
                }
                $value = new ArrayIterator($status_list);
                break;

            case 'obsolescence_date':
                $value = $this->getObsolescenceDate();
                break;

            case 'rank':
                $value = $this->getRank();
                break;
        }

        return $value;
    }

    public function &getMetadataFromLabel($label)
    {
        $metadata_value = $this->getHardCodedMetadataValue($label);
        $metadata       = null; // can't refactor with early return as it returns value by ref :(
        if ($metadata_value !== null) {
            $metadata_factory = new Docman_MetadataFactory($this->groupId);
            $metadata         = $metadata_factory->getHardCodedMetadataFromLabel($label, $metadata_value);
        } elseif (isset($this->_metadata[$label])) {
            $metadata = $this->_metadata[$label];
        }
        return $metadata;
    }

    /**
     * Update item's hardcoded values according to Metadata settings.
     */
    public function updateHardCodedMetadata($metadata)
    {
        switch ($metadata->getLabel()) {
            case 'title':
                $this->setTitle($metadata->getValue());
                break;
            case 'description':
                $this->setDescription($metadata->getValue());
                break;
            case 'owner':
                $this->setOwnerId($metadata->getValue());
                break;
            case 'create_date':
                $this->setCreateDate($metadata->getValue());
                break;
            case 'update_date':
                $this->setUpdateDate($metadata->getValue());
                break;
            case 'status':
                // $metadata->getValue() return an array iterator
                $this->setStatus($metadata->getValue()->current()->getId());
                break;
            case 'obsolescence_date':
                $this->setObsolescenceDate($metadata->getValue());
                break;
            case 'rank':
                $this->setRank($metadata->getValue());
                break;
        }
    }

    public function setPathId(&$path_id)
    {
        $this->pathId = $path_id;
    }

    public function &getPathId()
    {
        return $this->pathId;
    }

    public function setPathTitle(&$path_title)
    {
        $this->pathTitle = $path_title;
    }

    public function &getPathTitle()
    {
        return $this->pathTitle;
    }

    public function fireEvent($event, $user, $parent = null)
    {
        $params = ['group_id' => $this->getGroupId(),
            'parent'   => $parent,
            'item'     => $this,
            'user'     => $user,
        ];
        $this->getEventManager()->processEvent($event, $params);
    }

    protected function getEventManager()
    {
        return EventManager::instance();
    }
}
