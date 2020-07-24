<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

        $sql_values = [];
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

    public function duplicate(
        array $used_labels,
        $content_id,
        $template_project_id,
        $new_project_id
    ) {
        try {
            foreach ($used_labels as $used_label) {
                $this->duplicateLabel($used_label['id'], $content_id, $template_project_id, $new_project_id);
            }
        } catch (DataAccessException $exception) {
            return false;
        }

        return true;
    }

    private function duplicateLabel($used_label_id, $content_id, $template_project_id, $new_project_id)
    {
        $used_label_id       = $this->da->escapeInt($used_label_id);
        $content_id          = $this->da->escapeInt($content_id);
        $template_project_id = $this->da->escapeInt($template_project_id);
        $new_project_id      = $this->da->escapeInt($new_project_id);

        $sql = "INSERT INTO plugin_label_widget_config (content_id, label_id)
                SELECT $content_id, new_project_label.id
                FROM project_label as template_label
                  INNER JOIN project_label as new_project_label USING (name)
                WHERE template_label.project_id = $template_project_id
                  AND new_project_label.project_id = $new_project_id
                  AND template_label.id = $used_label_id";

        return $this->update($sql);
    }
}
