<?php
/**
 * Copyright © Enalean, 2011 - 2018. All Rights Reserved.
 *
 * Originally written by Marc Nazarian, 2009
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

class Docman_View_ItemDetailsSectionReferences extends Docman_View_ItemDetailsSection
{


    public function __construct($item, $url)
    {
        parent::__construct($item, $url, 'references', dgettext('tuleap-docman', 'References'));
    }

    public function getContent($params = [])
    {
        $crf = new CrossReferenceFactory($this->item->getId(), ReferenceManager::REFERENCE_NATURE_DOCUMENT, $this->item->getGroupId());
        $crf->fetchDatas();
        $content = $crf->getHTMLDisplayCrossRefs();
        return $content;
    }
}
