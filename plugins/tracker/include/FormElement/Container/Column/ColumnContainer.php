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

namespace Tuleap\Tracker\FormElement\Container\Column;

use Codendi_HTMLPurifier;
use Tracker_FormElement_Container_Column_Group;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Container\Column\XML\XMLColumn;
use Tuleap\Tracker\FormElement\Container\TrackerFormElementContainer;
use Tuleap\Tracker\FormElement\TrackerFormElement;
use Tuleap\Tracker\FormElement\XML\XMLFormElement;

class ColumnContainer extends TrackerFormElementContainer
{
    /**
     * Fetch the element for the update artifact form
     *
     *
     * @return string html
     */
    #[\Override]
    public function fetchArtifact(
        Artifact $artifact,
        array $submitted_values,
        array $additional_classes,
    ) {
        return $this->fetchWithColumnGroup('fetchArtifact', [$artifact, $submitted_values]);
    }

    public function fetchArtifactInGroup(Artifact $artifact, array $submitted_values)
    {
        return $this->fetchRecursiveArtifact('fetchArtifact', $artifact, $submitted_values, []);
    }

    /**
     * Fetch the element for the update artifact form
     *
     * @param Artifact $artifact The artifact
     *
     * @return string html
     */
    #[\Override]
    public function fetchArtifactReadOnly(Artifact $artifact, array $submitted_values)
    {
        return $this->fetchWithColumnGroup('fetchArtifactReadOnly', [$artifact, $submitted_values]);
    }

    public function fetchArtifactReadOnlyInGroup(Artifact $artifact, array $submitted_values)
    {
        return $this->fetchRecursiveArtifact('fetchArtifactReadOnly', $artifact, $submitted_values, []);
    }

    #[\Override]
    public function fetchMailArtifact($recipient, Artifact $artifact, $format = 'text', $ignore_perms = false)
    {
        return $this->fetchWithColumnGroup('fetchMailArtifact', [$recipient, $artifact, $format, $ignore_perms]);
    }

    public function fetchMailArtifactInGroup($recipient, Artifact $artifact, $format = 'text', $ignore_perms = false)
    {
        return $this->fetchMailRecursiveArtifact($format, 'fetchMailArtifact', [$recipient, $artifact, $format, $ignore_perms]);
    }

    /**
     * @see TrackerFormElement::fetchArtifactCopyMode
     */
    #[\Override]
    public function fetchArtifactCopyMode(Artifact $artifact, array $submitted_values)
    {
        return $this->fetchWithColumnGroup('fetchArtifactCopyMode', [$artifact, $submitted_values]);
    }

    public function fetchArtifactCopyModeInGroup(Artifact $artifact, array $submitted_values)
    {
        return $this->fetchRecursiveArtifact('fetchArtifactCopyMode', $artifact, $submitted_values, []);
    }

    /**
     * Fetch the element for the submit new artifact form
     * @param array $submitted_values the values already submitted
     *
     * @return string html
     */
    #[\Override]
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
    #[\Override]
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
    #[\Override]
    public function fetchAdmin($tracker)
    {
        return $this->fetchWithColumnGroup('fetchAdmin', [$tracker]);
    }

    public function fetchAdminInGroup($tracker)
    {
        $hp    = Codendi_HTMLPurifier::instance();
        $html  = $this->fetchColumnPrefix('class="tracker-admin-container tracker-admin-column" id="tracker-admin-formElements_' . $hp->purify((string) $this->id) . '" style="min-width:200px; min-height:80px; border:1px dashed #ccc; margin: 1px; padding: 4px;"');
        $html .= '<div><label title="' . $hp->purify($this->getDescription(), CODENDI_PURIFIER_CONVERT_HTML) . '">';
        $html .= $hp->purify($this->getLabel(), CODENDI_PURIFIER_CONVERT_HTML);
        $html .= '<span class="tracker-admin-field-controls">';
        $html .= '<a class="edit-field" href="' . $this->getAdminEditUrl() . '">' . $GLOBALS['HTML']->getImage('ic/edit.png', ['alt' => 'edit']) . '</a> ';

        if ($this->canBeRemovedFromUsage()) {
            $csrf_token = $this->getCSRFTokenForElementUpdate();
            $html      .= '<form method="POST" action="?">';
            $html      .= $csrf_token->fetchHTMLInput();
            $html      .= '<input type="hidden" name="func" value="' . $hp->purify(\Tuleap\Tracker\Tracker::TRACKER_ACTION_NAME_FORM_ELEMENT_REMOVE) . '" />';
            $html      .= '<input type="hidden" name="tracker" value="' . $hp->purify((string) $tracker->getId()) . '" />';
            $html      .= '<input type="hidden" name="formElement" value="' . $hp->purify((string) $this->id) . '" />';
            $html      .= '<button type="submit" class="btn-link">' . $GLOBALS['HTML']->getImage('ic/cross.png', ['alt' => 'remove']) . '</button>';
            $html      .= '</form>';
        } else {
            $cannot_remove_message = $this->getCannotRemoveMessage();
            $html                 .= '<span style="color:gray;" title="' . $hp->purify($cannot_remove_message) . '">';
            $html                 .= $GLOBALS['HTML']->getImage('ic/cross-disabled.png', ['alt' => 'remove']);
            $html                 .= '</span>';
        }

        $html   .= '</span></label>';
        $html   .= '</div>';
        $content = [];
        foreach ($this->getFormElements() as $formElement) {
            $content[] = $formElement->fetchAdmin($tracker);
        }
        $html                    .= implode('', $content);
        $html                    .= $this->fetchColumnSuffix();
        $this->has_been_displayed = true;
        return $html;
    }

    protected function fetchWithColumnGroup($method, $params = [])
    {
        $html = '';
        //Fetch only if it has not been already done
        if (! $this->hasBeenDisplayed()) {
            //search for next siblings
            $next = [];
            $tf   = Tracker_FormElementFactory::instance();
            $cur  = $this;
            while ($cur instanceof ColumnContainer) {
                $next[] = $cur;
                $cur    = $tf->getNextSibling($cur);
            }
            //delegates the fetch to the group of next sibblings
            $group = new Tracker_FormElement_Container_Column_Group();
            $html .= call_user_func_array([$group, $method], array_merge([$next], $params));
        }
        return $html;
    }

    #[\Override]
    protected function fetchArtifactPrefix()
    {
        return $this->fetchColumnPrefix();
    }

    #[\Override]
    protected function fetchArtifactSuffix()
    {
        return $this->fetchColumnSuffix();
    }

    #[\Override]
    protected function fetchMailArtifactPrefix($format)
    {
        return '';
    }

    #[\Override]
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

    #[\Override]
    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Column');
    }

    #[\Override]
    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'Group fields in a column');
    }

    #[\Override]
    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/layout-2.png');
    }

    #[\Override]
    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/layout-2--plus.png');
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    #[\Override]
    protected function fetchAdminFormElement()
    {
        $html = '';
        return $html;
    }

    #[\Override]
    protected function getXMLInternalRepresentation(): XMLFormElement
    {
        return new XMLColumn($this->getXMLId(), $this->getName());
    }
}
