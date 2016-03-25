<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature;

use DataAccessObject;

class NatureDao extends DataAccessObject {

    public function create($shortname, $forward_label, $reverse_label) {
        $shortname     = $this->da->quoteSmart($shortname);
        $forward_label = $this->da->quoteSmart($forward_label);
        $reverse_label = $this->da->quoteSmart($reverse_label);

        $this->da->startTransaction();

        $sql = "SELECT * FROM plugin_tracker_artifactlink_natures WHERE shortname = $shortname";
        if ($this->retrieve($sql)->count() > 0) {
            $this->rollBack();
            throw new UnableToCreateNatureException(
                $GLOBALS['Language']->getText(
                    'plugin_tracker_artifact_links_natures',
                    'create_same_name_error',
                    $shortname
                )
            );
        }

        $sql = "INSERT INTO plugin_tracker_artifactlink_natures (shortname, forward_label, reverse_label)
                VALUES ($shortname, $forward_label, $reverse_label)";

        if (! $this->update($sql)) {
            $this->rollBack();
            return false;
        }

        $this->commit();
        return true;
    }

    public function edit($shortname, $forward_label, $reverse_label) {
        $shortname     = $this->da->quoteSmart($shortname);
        $forward_label = $this->da->quoteSmart($forward_label);
        $reverse_label = $this->da->quoteSmart($reverse_label);

        $sql = "UPDATE plugin_tracker_artifactlink_natures
                   SET forward_label = $forward_label, reverse_label = $reverse_label
                WHERE shortname = $shortname";

        return $this->update($sql);
    }

    public function delete($shortname) {
        $shortname = $this->da->quoteSmart($shortname);

        $sql = "DELETE FROM plugin_tracker_artifactlink_natures WHERE shortname = $shortname";

        return $this->update($sql);
    }

    public function isOrHasBeenUsed($shortname) {
        $shortname = $this->da->quoteSmart($shortname);

        $sql = "SELECT nature
                  FROM tracker_changeset_value_artifactlink
                 WHERE nature = $shortname
                 LIMIT 1";

        $row = $this->retrieve($sql)->getRow();

        return (bool)$row['nature'];
    }

    public function searchAll() {
        $sql = "SELECT DISTINCT shortname, forward_label, reverse_label, IF(cv.nature IS NULL, 0, 1) as is_used
                           FROM plugin_tracker_artifactlink_natures AS n
                      LEFT JOIN tracker_changeset_value_artifactlink AS cv ON n.shortname = cv.nature
                       ORDER BY shortname ASC";

        return $this->retrieve($sql);
    }

    public function getFromShortname($shortname) {
        $shortname = $this->da->quoteSmart($shortname);

        $sql = "SELECT DISTINCT shortname, forward_label, reverse_label, IF(cv.nature IS NULL, 0, 1) as is_used
                  FROM plugin_tracker_artifactlink_natures AS n
             LEFT JOIN tracker_changeset_value_artifactlink AS cv ON n.shortname = cv.nature
                 WHERE shortname = $shortname";

        return $this->retrieveFirstRow($sql);
    }

}
