<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

class Tracker_FormElement_Container_Column extends Tracker_FormElement_Container
{

    /**
     * Fetch the element for the update artifact form
     *
     *
     * @return string html
     */
    public function fetchArtifact(
        Tracker_Artifact $artifact,
        array $submitted_values,
        array $additional_classes
    ) {
        return $this->fetchWithColumnGroup('fetchArtifact', array($artifact, $submitted_values));
    }

    public function fetchArtifactInGroup(Tracker_Artifact $artifact, array $submitted_values)
    {
        return $this->fetchRecursiveArtifact('fetchArtifact', $artifact, $submitted_values, []);
    }

    /**
     * Fetch the element for the update artifact form
     *
     * @param Tracker_Artifact $artifact The artifact
     *
     * @return string html
     */
    public function fetchArtifactReadOnly(Tracker_Artifact $artifact, array $submitted_values)
    {
        return $this->fetchWithColumnGroup('fetchArtifactReadOnly', [$artifact, $submitted_values]);
    }

    public function fetchArtifactReadOnlyInGroup(Tracker_Artifact $artifact, array $submitted_values)
    {
        return $this->fetchRecursiveArtifact('fetchArtifactReadOnly', $artifact, $submitted_values, []);
    }

    public function fetchMailArtifact($recipient, Tracker_Artifact $artifact, $format = 'text', $ignore_perms = false)
    {
        return $this->fetchWithColumnGroup('fetchMailArtifact', array($recipient, $artifact, $format, $ignore_perms));
    }

    public function fetchMailArtifactInGroup($recipient, Tracker_Artifact $artifact, $format = 'text', $ignore_perms = false)
    {
        return $this->fetchMailRecursiveArtifact($format, 'fetchMailArtifact', array($recipient, $artifact, $format, $ignore_perms));
    }

    /**
     * @see Tracker_FormElement::fetchArtifactCopyMode
     */
    public function fetchArtifactCopyMode(Tracker_Artifact $artifact, array $submitted_values)
    {
        return $this->fetchWithColumnGroup('fetchArtifactCopyMode', [$artifact, $submitted_values]);
    }

    public function fetchArtifactCopyModeInGroup(Tracker_Artifact $artifact, array $submitted_values)
    {
        return $this->fetchRecursiveArtifact('fetchArtifactCopyMode', $artifact, $submitted_values, []);
    }

    /**
     * Fetch the element for the submit new artifact form
     * @param array $submitted_values the values already submitted
     *
     * @return string html
     */
    public function fetchSubmit(array $submitted_values)
    {
        return $this->fetchWithColumnGroup('fetchSubmit', [$submitted_values]);
    }

    public function fetchSubmitInGroup(array $submitted_values)
    {
        return $this->fetchRecursiveArtifactForSubmit('fetchSubmit', $submitted_values);
    }


    /**
     * Fetch the element for the submit masschange form
     */
    public function fetchSubmitMasschange()
    {
        return $this->fetchWithColumnGroup('fetchSubmitMasschange', []);
    }

    public function fetchSubmitMasschangeInGroup()
    {
        return $this->fetchRecursiveArtifactForSubmit('fetchSubmitMasschange', []);
    }

    /**
     * Fetch the admin preview form
     *
     * @return string html
     */
    public function fetchAdmin($tracker)
    {
        return $this->fetchWithColumnGroup('fetchAdmin', array($tracker));
    }
    public function fetchAdminInGroup($tracker)
    {
        $html = '';
        $hp = Codendi_HTMLPurifier::instance();
        $html .= $this->fetchColumnPrefix('class="tracker-admin-container tracker-admin-column" id="tracker-admin-formElements_' . $this->id . '" style="min-width:200px; min-height:80px; border:1px dashed #ccc; margin: 1px; padding: 4px;"');
        $html .= '<div><label title="' . $hp->purify($this->getDescription(), CODENDI_PURIFIER_CONVERT_HTML) . '">';
        $html .= $hp->purify($this->getLabel(), CODENDI_PURIFIER_CONVERT_HTML);
        $html .= '<span class="tracker-admin-field-controls">';
        $html .= '<a class="edit-field" href="' . $this->getAdminEditUrl() . '">' . $GLOBALS['HTML']->getImage('ic/edit.png', array('alt' => 'edit')) . '</a> ';

        if ($this->canBeRemovedFromUsage()) {
            $html .= '<a href="?' . http_build_query(array(
                'tracker'  => $this->tracker_id,
                'func'     => 'admin-formElement-remove',
                'formElement' => $this->id,
            )) . '">' . $GLOBALS['HTML']->getImage('ic/cross.png', array('alt' => 'remove')) . '</a>';
        } else {
            $cannot_remove_message = $this->getCannotRemoveMessage();
            $html .= '<span style="color:gray;" title="' . $cannot_remove_message . '">';
            $html .= $GLOBALS['HTML']->getImage('ic/cross-disabled.png', array('alt' => 'remove'));
            $html .= '</span>';
        }

        $html .= '</span></label>';
        $html .= '</div>';
        $content = array();
        foreach ($this->getFormElements() as $formElement) {
            $content[] = $formElement->fetchAdmin($tracker);
        }
        $html .= implode('', $content);
        $html .= $this->fetchColumnSuffix();
        $this->has_been_displayed = true;
        return $html;
    }

    protected function fetchWithColumnGroup($method, $params = array())
    {
        $html = '';
        //Fetch only if it has not been already done
        if (!$this->hasBeenDisplayed()) {
            //search for next siblings
            $next = array();
            $tf   = Tracker_FormElementFactory::instance();
            $cur = $this;
            while ($cur instanceof \Tracker_FormElement_Container_Column) {
                $next[] = $cur;
                $cur = $tf->getNextSibling($cur);
            }
            //delegates the fetch to the group of next sibblings
            $group = new Tracker_FormElement_Container_Column_Group();
            $html .= call_user_func_array(array($group, $method), array_merge(array($next), $params));
        }
        return $html;
    }

    protected function fetchArtifactPrefix()
    {
        return $this->fetchColumnPrefix();
    }

    protected function fetchArtifactSuffix()
    {
        return $this->fetchColumnSuffix();
    }

    protected function fetchMailArtifactPrefix($format)
    {
        return '';
    }

    protected function fetchMailArtifactSuffix($format)
    {
        return '';
    }

    /**
     * Close th table if needed
     */
    protected function fetchColumnSuffix()
    {
        $html = '</div>';
        return $html;
    }

    /**
     * Open the table if needed
     */
    protected function fetchColumnPrefix($htmlparams = '')
    {
        $html = '<div ' . $htmlparams . '>';
        return $html;
    }

    public static function getFactoryLabel()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'column');
    }

    public static function getFactoryDescription()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'column_description');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/layout-2.png');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/layout-2--plus.png');
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        $html = '';
        return $html;
    }
}
