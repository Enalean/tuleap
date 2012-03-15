<?php
/**
 *
 * Copyright (C) Villanova University 2009.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'Action.php';

class Browse extends Action {
    
    /**
     * Build an array containing options describing a top-level Browse option.
     *
     * @access  private
     * @param   string  $action         The name of the Action for this option
     * @param   string  $description    A description of this Browse option
     * @return  array                   The Browse option array
     */
    private function buildBrowseOption($action, $description)
    {
        return array('action' => $action, 'description' => $description);
    }
    
    /**
     * Constructor
     *
     * Sets up the common data shared by all Browse modules.
     *
     * @access  public
     */
    public function __construct()
    {
        global $interface;
        global $configArray;
        
        // Read configuration settings for LC / Dewey call number display; default
        // to LC only if no settings exist in config.ini.
        if (!isset($configArray['Browse']['dewey']) && 
            !isset($configArray['Browse']['lcc'])) {
            $lcc = true;
            $dewey = false;
        } else {
            $lcc = (isset($configArray['Browse']['lcc']) && 
                $configArray['Browse']['lcc']);
            $dewey = (isset($configArray['Browse']['dewey']) && 
                $configArray['Browse']['dewey']);
        }
        
        // Build an array of top-level browse options; we may eventually want this
        // to be dynamically generated based on config.ini options.
        $browseOptions = array();
        $browseOptions[] = $this->buildBrowseOption('Tag', 'Tag');
        
        // Add the call number options as needed -- note that if both options exist,
        // we need to use special text to disambiguate them.
        if ($dewey) {
            $browseOptions[] = $this->buildBrowseOption('Dewey', 
                ($lcc ? 'browse_dewey' : 'Call Number'));
        }
        if ($lcc) {
            $browseOptions[] = $this->buildBrowseOption('LCC', 
                ($dewey ? 'browse_lcc' : 'Call Number'));
        }
        
        $browseOptions[] = $this->buildBrowseOption('Author', 'Author');
        $browseOptions[] = $this->buildBrowseOption('Topic', 'Topic');
        $browseOptions[] = $this->buildBrowseOption('Genre', 'Genre');
        $browseOptions[] = $this->buildBrowseOption('Region', 'Region');
        $browseOptions[] = $this->buildBrowseOption('Era', 'Era');
        
        $interface->assign('browseOptions', $browseOptions);
    }
}

?>
