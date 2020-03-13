<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class HTML_Table
{
    private $id            = '';
    private $titles        = array();
    private $table_classes = array();
    private $body          = '';

    public function __construct()
    {
    }

    public function setColumnsTitle(array $titles)
    {
        $this->titles = $titles;
        return $this;
    }

    public function addColumnTitle($title)
    {
        $this->titles[] = $title;
        return $this;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    public function addTableClass($class)
    {
        $this->table_classes[] = $class;
        return $this;
    }

    public function render()
    {
        return '<table' . $this->getTableAttributes() . '>
                 ' . $this->renderHead() . '
                 ' . $this->renderBody() . '
                </table>';
    }

    private function getTableAttributes()
    {
        $attributes = array();
        if (count($this->table_classes)) {
            $attributes[] = 'class="' . implode(' ', $this->table_classes) . '"';
        }
        if ($this->id) {
            $attributes[] = 'id="' . $this->id . '"';
        }
        if (count($attributes)) {
            return ' ' . implode(' ', $attributes);
        }
        return '';
    }

    private function renderHead()
    {
        if (count($this->titles)) {
            return '  <thead>
                        <tr>
                          <th>' . implode('</th><th>', $this->titles) . '</th>
                        </tr>
                      </thead>';
        }
        return '';
    }

    private function renderBody()
    {
        if ($this->body) {
            return '<tbody>' . $this->body . '</tbody>';
        }
        return;
    }

    protected function setTableClasses(array $classes)
    {
        $this->table_classes = $classes;
        return $this;
    }
}
