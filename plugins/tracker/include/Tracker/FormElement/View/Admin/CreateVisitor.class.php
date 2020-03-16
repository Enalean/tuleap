<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */


/**
 * Visit a FormElement and provides a create view
 */
class Tracker_FormElement_View_Admin_CreateVisitor extends Tracker_FormElement_View_Admin_Visitor
{
    private $type;
    private $label;

    protected function fetchForm()
    {
        $html = '';
        $html .= $this->adminElement->fetchTypeNotModifiable();
        $html .= $this->adminElement->fetchLabelForUpdate();
        $html .= $this->adminElement->fetchDescriptionForUpdate();
        $html .= $this->adminElement->fetchRanking();
        $html .= $this->adminElement->fetchAdminSpecificProperties();
        $html .= $this->adminElement->fetchAfterAdminCreateForm();
        $html .= $this->adminElement->fetchAdminButton(self::SUBMIT_CREATE);
        return $html;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * Display the form to create a new formElement
     *
     * @param TrackerManager  $tracker_manager The service
     * @param HTTPRequest     $request         The data coming from the user
     * @param string          $type            The internal name of type of the field
     * @param string          $factory_label   The label of the field (At factory
     *                                         level 'Selectbox, File, ...')
     *
     * @return void
     */
    public function display(TrackerManager $tracker_manager, HTTPRequest $request)
    {
        $hp    = Codendi_HTMLPurifier::instance();
        $title = 'Create a new ' . $this->label;
        $url   = TRACKER_BASE_URL . '/?tracker=' . (int) $this->element->getTracker()->getId() . '&amp;func=admin-formElements&amp;create-formElement[' .  $hp->purify($this->type, CODENDI_PURIFIER_CONVERT_HTML) . ']=1';

        echo $this->displayForm($tracker_manager, $request, $url, $title, $this->fetchForm());
    }
}
