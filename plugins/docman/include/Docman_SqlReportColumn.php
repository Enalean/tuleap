<?php
/*
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
class Docman_SqlReportColumn extends \Docman_MetadataSqlQueryChunk
{
    public $column;
    public function __construct($column)
    {
        $this->column = $column;
        parent::__construct($column->md);
    }

    #[\Override]
    public function getOrderBy()
    {
        $sql  = '';
        $sort = $this->column->getSort();
        if ($sort !== \null) {
            if ($sort == \PLUGIN_DOCMAN_SORT_ASC) {
                $sql = $this->field . ' ASC';
            } else {
                $sql = $this->field . ' DESC';
            }
        }
        return $sql;
    }

    /**
     * @param string[] $previous_from_statement
     * @return string[]
     */
    public function getCustomMetadataFromIfNeeded(array $previous_from_statement): array
    {
        $tables = [];
        if ($this->isCustomMetadata() && ! $this->isCustomMetadataTableAlreadyInFromStatement($previous_from_statement) && $this->isReportColumnCanBeSorted()) {
            $tables[] = $this->_getMdvJoin();
        }
        return $tables;
    }

    /**
     * @param string[] $from_statements
     */
    private function isCustomMetadataTableAlreadyInFromStatement(array $from_statements): bool
    {
        if ($this->isCustomMetadata()) {
            foreach ($from_statements as $statement) {
                if (str_contains($statement, $this->mdv)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function isCustomMetadata(): bool
    {
        return $this->isRealMetadata;
    }

    private function isReportColumnCanBeSorted(): bool
    {
        return $this->column->getSort() !== null;
    }
}
