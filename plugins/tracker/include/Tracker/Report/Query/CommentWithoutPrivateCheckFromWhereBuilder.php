<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Report\Query;

final class CommentWithoutPrivateCheckFromWhereBuilder implements CommentFromWhereBuilder
{
    public function getFromWhereWithComment(string $value, string $suffix): IProvideParametrizedFromAndWhereSQLFragments
    {
        $value = $this->removeEnclosingSimpleQuoteToNotFailMatchSqlQuery($value);
        $value = $this->surroundValueWithSimpleAndThenDoubleQuotesForFulltextMatching($value);

        $from = " LEFT JOIN (
                    tracker_changeset_comment_fulltext AS TCCF_$suffix
                    INNER JOIN tracker_changeset_comment AS TCC_$suffix
                     ON (
                        TCC_$suffix.id = TCCF_$suffix.comment_id
                        AND TCC_$suffix.parent_id = 0
                        AND match(TCCF_$suffix.stripped_body) against (? IN BOOLEAN MODE)
                     )
                     INNER JOIN  tracker_changeset AS TC_$suffix  ON TC_$suffix.id = TCC_$suffix.changeset_id
                 ) ON TC_$suffix.artifact_id = artifact.id";

        $where = "TCC_$suffix.changeset_id IS NOT NULL";

        return new ParametrizedFromWhere($from, $where, [$value], []);
    }

    public function getFromWhereWithoutComment(string $suffix): IProvideParametrizedFromAndWhereSQLFragments
    {
        $from = " LEFT JOIN (
                    tracker_changeset AS TC_$suffix
                    JOIN tracker_changeset_comment AS TCC_$suffix
                       ON TC_$suffix.id = TCC_$suffix.changeset_id
                    JOIN tracker_changeset_comment_fulltext AS TCCF_$suffix
                        ON TCC_$suffix.id = TCCF_$suffix.comment_id
                    ) ON TC_$suffix.artifact_id = artifact.id";

        $where = "TCCF_$suffix.comment_id IS NULL";

        return new ParametrizedFromWhere($from, $where, [], []);
    }

    private function removeEnclosingSimpleQuoteToNotFailMatchSqlQuery(string $value): string
    {
        return trim($value, "'");
    }

    private function surroundValueWithSimpleAndThenDoubleQuotesForFulltextMatching(string $value): string
    {
        return '"' . $value . '"';
    }
}
