<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

require_once __DIR__ . '/../../../src/www/include/pre.php';

$logger   = new Log_ConsoleLogger();
$purifier = Codendi_HTMLPurifier::instance();
$dao      = new DataAccessObject();

$sql = "SELECT id, body, body_format
                FROM tracker_changeset_comment AS comment
                    LEFT JOIN tracker_changeset_comment_fulltext AS stripped
                    ON (comment.id = stripped.comment_id)
                WHERE stripped.comment_id IS NULL";

$results = $dao->retrieve($sql);
$nb      = count($results);
$logger->info("Found $nb comments to store");
$values  = array();
$i = 1;
foreach ($dao->retrieve($sql) as $row) {
    if ($row['body_format'] === Tracker_Artifact_Changeset_Comment::HTML_COMMENT) {
        $stripped_body = $purifier->purify($row['body'], CODENDI_PURIFIER_STRIP_HTML);
    } else {
        $stripped_body = $row['body'];
    }

    $id            = $dao->da->escapeInt($row['id']);
    $stripped_body = $dao->da->quoteSmart($stripped_body);

    $sql = "INSERT INTO tracker_changeset_comment_fulltext (comment_id, stripped_body) VALUES ($id, $stripped_body)";
    $dao->update($sql);

    fwrite(STDERR, "Stored $i/$nb comments\r");
    flush();
    ++$i;
}
fwrite(STDERR, "\n");
$logger->info("done");
