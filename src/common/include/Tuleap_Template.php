<?php
/*
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

/**
 * Basic Template mechanism
 *
 * Usage:
 * <pre>
 * $t = new Tuleap_Template('/path/to/my_template.tpl');
 * $t->set('title', $title);
 * $t->set('description', $desc);
 * echo $t->fetch();
 * </pre>
 */
class Tuleap_Template
{
    /**
     * @var array<literal-string,string|bool|list<string>> The variables to pass to the template
     */
    private array $vars = [];

    /**
     * @var The file name of the template
     */
    protected $file;

    /**
     * Constructor
     *
     * @param $file string the file name you want to load
     */
    public function __construct($file = null)
    {
        $this->file = $file;
    }

    /**
     * @param literal-string $name
     * @param string|bool|list<string> $value
     */
    public function set(string $name, string|bool|array $value): void
    {
        $this->vars[$name] = $value;
    }

    /**
     * Open, parse, and return the template file.
     *
     * @param $file string the template file name, by default use the
     *
     * @throws Exception if there is no file to load
     *
     * @return string
     */
    public function fetch($file = null)
    {
        if (! $file) {
            $file = $this->file;
        }
        if (! $file) {
            throw new Exception('A template file name is required');
        }

        /*
         * Note: As we properly control the name of the variables we consider
         * we can remove the extract taint by making sure `file` cannot be manipulated.
         * This is not great, ideally the code should be rewritten to avoid the dependency
         * on extract.
         */
        $vars = $this->vars;
        /** @psalm-taint-escape extract */
        unset($vars['file']);
        extract($vars);          // Extract the vars to local namespace
        ob_start();                    // Start output buffering
        include($file);                // Include the file
        $contents = ob_get_contents(); // Get the contents of the buffer
        ob_end_clean();                // End buffering and discard
        return $contents;              // Return the contents
    }
}
