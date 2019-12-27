<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\reference;

use ReferenceDao;

class ReferenceValidator
{
    public const REFERENCE_PATTERN = "^[a-z0-9_]+$";
    /**
     * @var ReferenceDao
     */
    private $reference_dao;
    /**
     * @var ReservedKeywordsRetriever
     */
    private $reserved_keyword_retriever;

    public function __construct(ReferenceDao $reference_dao, ReservedKeywordsRetriever $reserved_keyword_retriever)
    {
        $this->reference_dao              = $reference_dao;
        $this->reserved_keyword_retriever = $reserved_keyword_retriever;
    }

    public function isValidKeyword($keyword): bool
    {
        return preg_match('/' . self::REFERENCE_PATTERN . '/', $keyword) === 1;
    }

    public function isReservedKeyword($keyword)
    {
        return in_array($keyword, $this->reserved_keyword_retriever->loadReservedKeywords());
    }

    public function isSystemKeyword($keyword)
    {
        $dar = $this->reference_dao->searchByScope('S');
        while ($row = $dar->getRow()) {
            if ($keyword === $row['keyword']) {
                return true;
            }
        }

        return false;
    }
}
