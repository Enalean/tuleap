<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved.
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
 * @template-implements ItemVisitor<array>
 */
class Docman_View_GetFieldsVisitor implements ItemVisitor
{
    public $mdLabelToSkip;

    public function __construct($mdLabelToSkip = array())
    {
        $this->mdLabelToSkip = $mdLabelToSkip;
    }

    public function buildFieldArray($mdIter, $params)
    {
        $formName = '';
        if (isset($params['form_name'])) {
            $formName = $params['form_name'];
        }
        $themePath = '';
        if (isset($params['theme_path'])) {
            $themePath = $params['theme_path'];
        }
        $mdHtmlFactory = new Docman_MetadataHtmlFactory();
        return $mdHtmlFactory->buildFieldArray($mdIter, $this->mdLabelToSkip, false, $formName, $themePath);
    }

    public function visitItem(Docman_Item $item, $params = array())
    {
        $mdIter = $item->getMetadataIterator();
        return $this->buildFieldArray($mdIter, $params);
    }

    public function visitFolder(Docman_Folder $item, $params = array())
    {
        $folderMetadata = array('title', 'description','create_date', 'update_date');
        $mda = array();
        foreach ($folderMetadata as $mdLabel) {
            $mda[] = $item->getMetadataFromLabel($mdLabel);
        }
        $mdIter = new ArrayIterator($mda);
        return $this->buildFieldArray($mdIter, $params);
    }

    public function visitDocument(Docman_Document $item, $params = array())
    {
        return $this->visitItem($item, $params);
    }
    public function visitWiki(Docman_Wiki $item, $params = array())
    {
        return $this->visitItem($item, $params);
    }
    public function visitLink(Docman_Link $item, $params = array())
    {
        return $this->visitItem($item, $params);
    }
    public function visitFile(Docman_File $item, $params = array())
    {
        return $this->visitItem($item, $params);
    }
    public function visitEmbeddedFile(Docman_EmbeddedFile $item, $params = array())
    {
        return $this->visitItem($item, $params);
    }

    public function visitEmpty(Docman_Empty $item, $params = array())
    {
        return $this->visitItem($item, $params);
    }
}
