<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

/* abstract */ class Docman_View_View //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    public $dfltSortParams   = [];
    public $dfltSearchParams = [];

    /**
     * @var Docman_Controller
     */
    public $_controller; //phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

    /**
     * @var string
     */
    public $javascript;

    /**
     * @var Codendi_HTMLPurifier
     */
    public $hp;

    public function __construct(&$controller)
    {
        $this->_controller = $controller;
        $this->hp          = Codendi_HTMLPurifier::instance();
        $this->javascript  = '';
    }

    public function display($params = [])
    {
        $this->_header($params);
        $this->_scripts($params);
        $this->_feedback($params);
        $this->displayTitle($params);
        echo '<div class="docman-content">';
        $this->displayOldBreadcrumbs($params);
        $this->displayMode($params);
        $this->_filter($params);
        $this->_content($params);
        $this->_javascript($params);
        $this->_footer($params);
        echo '</div>';
    }

    /* protected */ public function _header($params) //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
    }

    /* protected */ public function _scripts($params)//phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
    }

    /* protected */ public function _feedback($params)//phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
    }

    protected function displayTitle(array $params): void
    {
    }

    protected function displayOldBreadcrumbs(array $params): void
    {
    }

    protected function displayMode(array $params): void
    {
    }

    /* protected */ public function _filter($params)//phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
    }

    /* protected */ public function _content($params)//phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
    }

    /* protected */ public function _javascript($params)//phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->javascript != '') {
            $purifier  = Codendi_HTMLPurifier::instance();
            $csp_nonce = \Tuleap\ContentSecurityPolicy\CSPNonce::build();
            echo '<script type="text/javascript" nonce="' . $purifier->purify($csp_nonce->value) . '">' . "\n" .
            "//<!--\n" .
            $this->javascript .
            "//-->\n" .
            "</script>\n";
        }
    }

    /* protected */ public function _footer($params)//phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
    }

    public function &_getVersionFactory($params) //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $vf = new Docman_VersionFactory();
        return $vf;
    }

    public function &_getDocmanIcons($params)//phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $icons = new Docman_Icons($params['theme_path'] . '/images/ic/', EventManager::instance());
        return $icons;
    }

    public function _getItemFactory()//phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return new Docman_ItemFactory();
    }

    /**
     * This method build the paramater list of the current url for filters and
     * sort.
     */
    public function _initSearchAndSortParams($params)//phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (! count($this->dfltSortParams)) {
            $this->dfltSortParams   = [];
            $this->dfltSearchParams = [];

            if (isset($params['filter']) && $params['filter'] !== null) {
                // Report paramters
                $this->dfltSearchParams = $params['filter']->getUrlParameters();

                // Filters paramters
                $fi = $params['filter']->getFilterIterator();
                if ($fi !== null) {
                    $fi->rewind();
                    while ($fi->valid()) {
                        $f = $fi->current();

                        if ($f !== null) {
                            $this->dfltSearchParams = array_merge(
                                $this->dfltSearchParams,
                                $f->getUrlParameters()
                            );
                        }

                        $fi->next();
                    }
                }

                // Columns (sort) paramters
                $ci = $params['filter']->getColumnIterator();
                if ($ci !== null) {
                    $ci->rewind();
                    while ($ci->valid()) {
                        $c = $ci->current();
                        // The second part of the test aims to avoid to add
                        // sort_update_date=0 in the URL as it's the default
                        // sort (no need to define it)
                        if ($c !== null && ! ($c->md !== null && $c->md->getLabel() == 'update_date' && $c->sort == PLUGIN_DOCMAN_SORT_DESC)) {
                            $sort = $c->getSort();
                            if ($sort !== null) {
                                $this->dfltSortParams[$c->getSortParameter()] = $sort;
                            }
                        }
                        $ci->next();
                    }
                }
            }
        }
    }

    /**
     * Get the JS action for the item/user couple
     *
     */
    public function getActionForItem(Docman_Item $item)
    {
        return '';
    }

    public function getItemMenu(&$item, $params, $bc = false)
    {
        $html  = '';
        $html .= '</span>';
        return $html;
    }
}
