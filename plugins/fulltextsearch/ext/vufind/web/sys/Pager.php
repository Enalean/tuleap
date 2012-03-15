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
require_once 'Pager/Pager.php';
 
/**
 * VuFind Pager Class
 *
 * This is a wrapper class around the PEAR Pager mechanism to make it easier
 * to modify default settings shared by a variety of VuFind modules.
 *
 * @author      Demian Katz <demian.katz@villanova.edu>
 * @access      public
 */
class VuFindPager
{
    var $pager;
    
    /**
     * Constructor
     *
     * Initialize the PEAR pager object.
     *
     * @param   array   $options        The Pager options to override.
     * @access  public
     */
    public function __construct($options = array())
    {
        // Set default Pager options:
        $finalOptions = array(
            'mode'       => 'sliding',
            'path'       => "",
            'delta'      => 5,
            'perPage'    => 20,
            'nextImg'    => translate('Next') . ' &raquo;',
            'prevImg'    => '&laquo; ' . translate('Prev'),
            'separator'  => '',
            'spacesBeforeSeparator' => 0,
            'spacesAfterSeparator'  => 0,
            'append'          => false,
            'clearIfVoid'     => true,
            'urlVar'          => 'page',
            'curPageSpanPre'  => '<span>',
            'curPageSpanPost' => '</span>');
            
        // Override defaults with user-provided values:
        foreach($options as $optionName => $optionValue) {
            $finalOptions[$optionName] = $optionValue;
        }
        
        // Create the pager object:
        $this->pager =& Pager::factory($finalOptions);
    }

    /**
     * Generate the pager HTML using the options passed to the constructor.
     *
     * @access  public
     * @return  array
     */
    public function getLinks()
    {
        return $this->pager->getLinks();
    }
}
?>