<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_View_GetSpecificFieldsVisitor implements \Tuleap\Docman\Item\ItemVisitor
{
    public function visitFolder(\Docman_Folder $item, $params = [])
    {
        return [];
    }
    public function visitWiki(\Docman_Wiki $item, $params = [])
    {
        $pagename = '';
        if (isset($params['force_item'])) {
            if (\Docman_ItemFactory::getItemTypeForItem($params['force_item']) == \PLUGIN_DOCMAN_ITEM_TYPE_WIKI) {
                $pagename = $params['force_item']->getPagename();
            }
        } else {
            $pagename = $item->getPagename();
        }
        return [new \Docman_MetadataHtmlWiki($pagename)];
    }
    public function visitLink(\Docman_Link $item, $params = [])
    {
        $link_url = '';
        if (isset($params['force_item'])) {
            if ($params['force_item']->getType() == \PLUGIN_DOCMAN_ITEM_TYPE_LINK) {
                $link_url = $params['force_item']->getUrl();
            }
        } else {
            $link_url = $item->getUrl();
        }
        return [new \Docman_MetadataHtmlLink($link_url)];
    }
    public function visitFile(\Docman_File $item, $params = [])
    {
        return [new \Docman_MetadataHtmlFile($params['request'])];
    }
    public function visitEmbeddedFile(\Docman_EmbeddedFile $item, $params = [])
    {
        $content = '';
        $version = $item->getCurrentVersion();
        if ($version) {
            $content = $version->getContent();
        }
        return [new \Docman_MetadataHtmlEmbeddedFile($content)];
    }
    public function visitEmpty(\Docman_Empty $item, $params = [])
    {
        return [new \Docman_MetadataHtmlEmpty()];
    }
    public function visitItem(\Docman_Item $item, array $params = [])
    {
        throw new \LogicException('Cannot get the specific fields of a non specialized item');
    }
}
