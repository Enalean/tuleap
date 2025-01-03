<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
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

use Tuleap\Docman\REST\v1\DocmanItemsEventAdder;
use Tuleap\Docman\Version\Version;

/**
 * Version is a transport object (aka container) used to share data between
 * Model/Controler and View layer of the application
 */
class Docman_Version implements Version
{
    private string $authoring_tool;

    public function __construct($data = null)
    {
        $this->id             = null;
        $this->authorId       = null;
        $this->itemId         = null;
        $this->number         = null;
        $this->label          = null;
        $this->changelog      = null;
        $this->date           = null;
        $this->filename       = null;
        $this->filesize       = 0;
        $this->filetype       = null;
        $this->path           = null;
        $this->_content       = null;
        $this->authoring_tool = '';
        if ($data) {
            $this->initFromRow($data);
        }
    }

    public $id;
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public $authorId;
    public function getAuthorId()
    {
        return $this->authorId;
    }

    public function setAuthorId($authorId)
    {
        $this->authorId = $authorId;
    }

    public $itemId;
    public function getItemId()
    {
        return $this->itemId;
    }

    public function setItemId($itemId)
    {
        $this->itemId = $itemId;
    }

    public $number;

    /**
     * @psalm-mutation-free
     */
    public function getNumber()
    {
        return $this->number;
    }

    public function setNumber($number)
    {
        $this->number = $number;
    }

    public $label;
    public function getLabel()
    {
        return $this->label;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    public $changelog;
    public function getChangelog()
    {
        return $this->changelog;
    }

    public function setChangelog($changelog)
    {
        $this->changelog = $changelog;
    }

    public $date;
    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @var null|string
     */
    public $filename;
    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename ?? '';
    }

    public function setFilename(?string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * @var int
     */
    public $filesize;
    public function getFilesize(): int
    {
        return $this->filesize;
    }

    public function setFilesize(int $filesize)
    {
        $this->filesize = $filesize;
    }

    /**
     * @var null|string
     */
    public $filetype;

    /**
     * @return string
     * @psalm-mutation-free
     */
    public function getFiletype()
    {
        return $this->filetype ?? 'application/octet-stream';
    }

    public function setFiletype(?string $filetype)
    {
        $this->filetype = $filetype;
    }

    /**
     * @var string|null
     */
    public $path;

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path ?? '';
    }

    public function setPath(?string $path)
    {
        $this->path = $path;
    }

    protected $_content;
    public function getContent()
    {
        if ($this->_content === null && is_file($this->getPath())) {
            $this->_content = file_get_contents($this->getPath());
        }
        return $this->_content;
    }

    public function setContent($content)
    {
        $this->_content = $content;
    }

    public function initFromRow($row)
    {
        if (isset($row['id'])) {
            $this->setId($row['id']);
        }
        if (isset($row['user_id'])) {
            $this->setAuthorId($row['user_id']);
        }
        if (isset($row['item_id'])) {
            $this->setItemId($row['item_id']);
        }
        if (isset($row['number'])) {
            $this->setNumber($row['number']);
        }
        if (isset($row['label'])) {
            $this->setLabel($row['label']);
        }
        if (isset($row['changelog'])) {
            $this->setChangelog($row['changelog']);
        }
        if (isset($row['date'])) {
            $this->setDate($row['date']);
        }
        if (isset($row['filename'])) {
            $this->setFilename($row['filename']);
        }
        if (isset($row['filesize'])) {
            $this->setFilesize($row['filesize']);
        }
        if (isset($row['filetype'])) {
            $this->setFiletype($row['filetype']);
        }
        if (isset($row['path'])) {
            $this->setPath($row['path']);
        }
        if (isset($row['authoring_tool'])) {
            $this->authoring_tool = $row['authoring_tool'];
        }
    }

    /**
     * Generally invoked before file download, this method will log a version
     * access event
     *
     * @param Docman_Item    $item
     * @param PFUser           $user
     *
     * @return void
     */
    public function preDownload($item, $user)
    {
        $event_manager = EventManager::instance();
        $event_adder   = new DocmanItemsEventAdder($event_manager);
        $event_adder->addLogEvents();

        $event_manager->processEvent('plugin_docman_event_access', [
            'group_id' => $item->getGroupId(),
            'item'     => $item,
            'version'  => $this->getNumber(),
            'user'     => $user,
        ]);
    }

    /**
     * Logging a version deletion event
     *
     * @param Docman_Item    $item
     * @param PFUser           $user
     *
     * @return void
     */
    public function fireDeleteEvent($item, $user)
    {
        $value = $this->getNumber();
        if ($this->getLabel() != '') {
            $value .= ' (' . $this->getLabel() . ')';
        }
        $params = ['group_id'   => $item->getGroupId(),
            'item'       => $item,
            'old_value'  => $value,
            'user'       => $user,
        ];
        EventManager::instance()->processEvent('plugin_docman_event_del_version', $params);
    }

    public function getAuthoringTool(): string
    {
        return $this->authoring_tool;
    }
}
