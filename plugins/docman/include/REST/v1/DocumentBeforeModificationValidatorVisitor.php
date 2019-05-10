<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Docman\REST\v1;

use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_Link;
use Docman_Wiki;
use Tuleap\Docman\Item\ItemVisitor;
use Tuleap\REST\I18NRestException;

class DocumentBeforeModificationValidatorVisitor implements ItemVisitor
{
    /**
     * @var String
     */
    private $document_type;

    public function __construct(String $document_type)
    {
        $this->document_type = $document_type;
    }

    public function visitFolder(Docman_Folder $item, array $params = []) : void
    {
        if ($this->document_type !== Docman_Folder::class) {
            $this->throwItemHasNotTheRightType();
        }
    }

    public function visitWiki(Docman_Wiki $item, array $params = []) : void
    {
        if ($this->document_type !== Docman_Wiki::class) {
            $this->throwItemHasNotTheRightType();
        }
    }

    public function visitLink(Docman_Link $item, array $params = []) : void
    {
        if ($this->document_type !== Docman_Link::class) {
            $this->throwItemHasNotTheRightType();
        }
    }

    public function visitFile(Docman_File $item, array $params = []) : void
    {
        if ($this->document_type !== Docman_File::class) {
            $this->throwItemHasNotTheRightType();
        }
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = []) : void
    {
        if ($this->document_type !== Docman_EmbeddedFile::class) {
            $this->throwItemHasNotTheRightType();
        }
    }

    public function visitEmpty(Docman_Empty $item, array $params = []) : void
    {
        if ($this->document_type !== Docman_Empty::class) {
            $this->throwItemHasNotTheRightType();
        }
    }

    public function visitItem(Docman_Item $item, array $params = []) : void
    {
        $this->throwItemHasNotTheRightType();
    }

    /**
     * @throws I18NRestException
     */
    private function throwItemHasNotTheRightType() : void
    {
        throw new I18NRestException(
            400,
            sprintf(
                'The provided item id references an item which is not a %s',
                $this->document_type
            )
        );
    }
}
