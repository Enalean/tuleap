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

namespace Tuleap\Label\Widget;

use DataAccessException;
use Tuleap\Dashboard\Project\ProjectDashboardController;

class Dao extends \DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->enableExceptionsOnError();
    }

    public function storeLabelsConfiguration($content_id, array $labels_id)
    {
        $content_id = $this->da->escapeInt($content_id);

        $sql_values = array();
        foreach ($labels_id as $label_id) {
            $sql_values[] = "($content_id, " . $this->da->escapeInt($label_id) . ")";
        }

        if (count($sql_values) === 0) {
            return true;
        }

        $this->startTransaction();
        try {
            $sql = "DELETE FROM plugin_label_widget_config
                  WHERE content_id = $content_id";

            $this->update($sql);

            $sql = "INSERT INTO plugin_label_widget_config(content_id, label_id)
             VALUES " . implode(',', $sql_values);

            $this->update($sql);

            $this->commit();
        } catch (DataAccessException $exception) {
            $this->rollback();
            throw $exception;
        }
    }

    public function create()
    {
        $sql = "INSERT INTO plugin_label_widget(content_id)
             VALUES (null)";

        return $this->updateAndGetLastId($sql);
    }

    public function removeLabelById($label_id)
    {
        $label_id = $this->da->escapeInt($label_id);
        $sql      = "DELETE FROM plugin_label_widget_config
                       WHERE label_id = $label_id";

        return $this->update($sql);
    }


    public function mergeLabelInTransaction($label_to_edit_id, array $label_ids_to_remove)
    {
        $label_to_edit_id    = $this->da->escapeInt($label_to_edit_id);
        $label_ids_to_remove = $this->da->escapeIntImplode($label_ids_to_remove);
        $sql                 = "UPDATE IGNORE plugin_label_widget_config
                                  SET label_id = $label_to_edit_id
                                  WHERE label_id IN ($label_ids_to_remove)";
        $this->update($sql);

        $sql = "DELETE FROM plugin_label_widget_config
                  WHERE label_id IN ($label_ids_to_remove)";

        $this->update($sql);
    }

    public function removeLabelByContentId($content_id)
    {
        $content_id = $this->da->escapeInt($content_id);
        $sql        = "DELETE widget, config
                        FROM plugin_label_widget AS widget
                        LEFT JOIN plugin_label_widget_config AS config ON (
                            widget.content_id = config.content_id
                        )
                        WHERE widget.content_id = $content_id";

        $this->update($sql);
    }
}
