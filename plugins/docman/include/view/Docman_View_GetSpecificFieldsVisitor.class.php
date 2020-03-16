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

use Tuleap\Docman\Item\ItemVisitor;

class Docman_MetadataHtmlWiki extends Docman_MetadataHtml
{
    public $pagename;

    public function __construct($pagename)
    {
        $this->pagename = $pagename;
    }

    public function getLabel($show_mandatory_information = true)
    {
        return dgettext('tuleap-docman', 'Page Name:');
    }

    public function getField()
    {
        $hp = Codendi_HTMLPurifier::instance();
        return '<input type="text" class="docman_text_field" name="item[wiki_page]" value="' . $hp->purify($this->pagename) . '" /> ';
    }

    public function &getValidator()
    {
        $msg = dgettext('tuleap-docman', 'The page name is required.');
        $validator = new Docman_ValidateValueNotEmpty($this->pagename, $msg);
        return $validator;
    }
}

class Docman_MetadataHtmlLink extends Docman_MetadataHtml
{
    public $link_url;

    public function __construct($link_url)
    {
        $this->link_url = $link_url;
    }

    public function getLabel($show_mandatory_information = true)
    {
        return dgettext('tuleap-docman', 'Url:');
    }

    public function getField()
    {
        $hp = Codendi_HTMLPurifier::instance();
        return '<input type="text" class="docman_text_field" name="item[link_url]" value="' . $hp->purify($this->link_url) . '" />';
    }

    public function &getValidator()
    {
        $msg = dgettext('tuleap-docman', 'The URL is required.');
        $validator = new Docman_ValidateValueNotEmpty($this->link_url, $msg);
        return $validator;
    }
}

class Docman_MetadataHtmlFile extends Docman_MetadataHtml
{

    public function __construct()
    {
    }

    public function getLabel($show_mandatory_information = true)
    {
        return dgettext('tuleap-docman', 'Content:');
    }

    public function getField()
    {
        $html = '<input type="file" name="file" />';
        $html .= '<br /><em>' . sprintf(dgettext('tuleap-docman', '(The maximum upload file size is %1$s MByte)'), formatByteToMb((int) ForgeConfig::get(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING))) . '</em>';

        return $html;
    }

    public function &getValidator($request = null)
    {
        if ($request === null) {
            $request = HTTPRequest::instance();
        }
        $validator = new Docman_ValidateUpload($request);
        return $validator;
    }
}

class Docman_MetadataHtmlEmbeddedFile extends Docman_MetadataHtml
{
    public $content;
    public function __construct($content)
    {
        $this->content = $content;
    }

    public function getLabel($show_mandatory_information = true)
    {
        return dgettext('tuleap-docman', 'Content:');
    }

    public function getField()
    {
        $hp = Codendi_HTMLPurifier::instance();
        $html  = '';
        $html .= '<textarea id="embedded_content" name="content" cols="80" rows="20">' . $hp->purify($this->content) . '</textarea>';
        return $html;
    }

    public function &getValidator()
    {
        $validator = null;
        return $validator;
    }
}

class Docman_MetadataHtmlEmpty extends Docman_MetadataHtml
{

    public function __construct()
    {
    }

    public function getLabel($show_mandatory_information = true)
    {
        return dgettext('tuleap-docman', 'An empty document has no type. You will be able to choose document\'s type later. It\'s useful to create an entry in a hierarchy without the actual document.');
    }

    public function getField()
    {
        return '';
    }

    public function &getValidator()
    {
        $validator = null;
        return $validator;
    }
}

class Docman_View_GetSpecificFieldsVisitor implements ItemVisitor
{

    public function visitFolder(Docman_Folder $item, $params = array())
    {
        return array();
    }
    public function visitWiki(Docman_Wiki $item, $params = array())
    {
        $pagename = '';
        if (isset($params['force_item'])) {
            if (Docman_ItemFactory::getItemTypeForItem($params['force_item']) == PLUGIN_DOCMAN_ITEM_TYPE_WIKI) {
                $pagename = $params['force_item']->getPagename();
            }
        } else {
            $pagename = $item->getPagename();
        }
        return array(new Docman_MetadataHtmlWiki($pagename));
    }

    public function visitLink(Docman_Link $item, $params = array())
    {
        $link_url = '';
        if (isset($params['force_item'])) {
            if ($params['force_item']->getType() == PLUGIN_DOCMAN_ITEM_TYPE_LINK) {
                $link_url = $params['force_item']->getUrl();
            }
        } else {
            $link_url = $item->getUrl();
        }
        return array(new Docman_MetadataHtmlLink($link_url));
    }

    public function visitFile(Docman_File $item, $params = array())
    {
        return array(new Docman_MetadataHtmlFile($params['request']));
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, $params = array())
    {
        $content = '';
        $version = $item->getCurrentVersion();
        if ($version) {
            $content = $version->getContent();
        }
        return array(new Docman_MetadataHtmlEmbeddedFile($content));
    }

    public function visitEmpty(Docman_Empty $item, $params = array())
    {
        return array(new Docman_MetadataHtmlEmpty());
    }

    public function visitItem(Docman_Item $item, array $params = [])
    {
        throw new LogicException('Cannot get the specific fields of a non specialized item');
    }
}
