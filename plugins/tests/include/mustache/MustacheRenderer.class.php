<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once(dirname(__FILE__).'/Mustache.php');
require_once(dirname(__FILE__).'/MustacheLoader.php');

class MustacheRenderer {

    /**
     * @var Mustache
     */
    private $mustache;
    
    /**
     * @var MustacheLoader
     */
    private $templates;
    
    private $options = array('throws_exceptions' => array(
		MustacheException::UNKNOWN_VARIABLE         => true,
		MustacheException::UNCLOSED_SECTION         => true,
		MustacheException::UNEXPECTED_CLOSE_SECTION => true,
		MustacheException::UNKNOWN_PARTIAL          => true,
		MustacheException::UNKNOWN_PRAGMA           => true,
	));
    
    public function __construct($path) {
        $this->mustache  = new Mustache(null, null, null, $this->options);
        $this->templates = new MustacheLoader($path);
        
    }
    public function render($template_name, $presenter) {
        echo $this->mustache->render($this->templates[$template_name], $presenter, $this->templates);
    }
}

?>
