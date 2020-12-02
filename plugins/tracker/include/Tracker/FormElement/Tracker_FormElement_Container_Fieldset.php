<?php
/**
 * Copyright Enalean (c) 2016 - Present. All rights reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Container\Fieldset\HiddenFieldsetChecker;
use Tuleap\Tracker\FormElement\Container\FieldsExtractor;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDao;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDetector;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;

class Tracker_FormElement_Container_Fieldset extends Tracker_FormElement_Container
{
    /**
     * Process the request
     *
     * @param Tracker_IDisplayTrackerLayout  $layout          Displays the page header and footer
     * @param Codendi_Request                $request         The data coming from the user
     * @param PFUser                         $current_user    The user who mades the request
     *
     * @return void
     */
    public function process(Tracker_IDisplayTrackerLayout $layout, $request, $current_user)
    {
        switch ($request->get('func')) {
            case 'toggle-collapse':
                $current_user = $request->getCurrentUser();
                $current_user->togglePreference('fieldset_' . $this->getId(), 1, 0);
                break;
            default:
                parent::process($layout, $request, $current_user);
        }
    }

    protected function fetchRecursiveArtifact(
        $method,
        Artifact $artifact,
        array $submitted_values,
        array $additional_classes
    ) {
        $html = '';
        $content = $this->getContainerContent($method, [$artifact, $submitted_values, $additional_classes]);

        if (count($content)) {
            $extra_class = '';
            if ($this->getHiddenFieldsetChecker()->mustFieldsetBeHidden($this, $artifact)) {
                $extra_class = 'tracker_artifact_fieldset_hidden';
            }

            $html .= $this->fetchArtifactPrefix($extra_class);
            $html .= $this->fetchArtifactContent($content);
            $html .= $this->fetchArtifactSuffix();
        }

        $this->has_been_displayed = true;
        return $html;
    }

    private function getHiddenFieldsetChecker(): HiddenFieldsetChecker
    {
        return new HiddenFieldsetChecker(
            new HiddenFieldsetsDetector(
                new TransitionRetriever(
                    new StateFactory(
                        TransitionFactory::instance(),
                        new SimpleWorkflowDao()
                    ),
                    new TransitionExtractor()
                ),
                HiddenFieldsetsRetriever::instance(),
                Tracker_FormElementFactory::instance()
            ),
            new FieldsExtractor()
        );
    }

    protected function fetchArtifactPrefix($extra_class = '')
    {
        $hp           = Codendi_HTMLPurifier::instance();
        $current_user = UserManager::instance()->getCurrentUser();
        $always_collapsed      = '';
        $fieldset_is_collapsed = $this->isCollapsed();
        $fieldset_is_expanded  = ! $fieldset_is_collapsed;
        if ($fieldset_is_collapsed) {
            $always_collapsed = 'active';
        }

        $purified_extra_class = $hp->purify($extra_class);

        $html  = '';
        $html .= "<fieldset class=\"tracker_artifact_fieldset $purified_extra_class\">";
        $html .= '<legend title="' . $hp->purify($this->getDescription(), CODENDI_PURIFIER_CONVERT_HTML) . '"
                          class="' . Toggler::getClassName('fieldset_' . $this->getId(), $fieldset_is_expanded, true) . '"
                          id="fieldset_' . $this->getId() . '"
                          data-id="' . $this->getId() . '">';
        $html .= '<table><tr><td class="tracker_artifact_fieldset_title">';
        $html .= $hp->purify($this->getLabel(), CODENDI_PURIFIER_CONVERT_HTML);
        $html .= '</td>';
        $html .= '<td class="tracker_artifact_fieldset_alwayscollapsed ' . $always_collapsed . '">';
        if ($current_user->isLoggedIn()) {
            $html .= '<i class="fas fa-thumbtack"></i>';
        }
        $html .= '</td></tr></table>';
        $html .= '</legend>';
        $html .= '<div class="tracker_artifact_fieldset_content">';

        return $html;
    }

    protected function fetchArtifactSuffix()
    {
        $html = '</div>';
        $html .= '</fieldset>';
        return $html;
    }

    protected function fetchMailArtifactPrefix($format)
    {
        $label = $this->getLabel();
        if ($format == 'text') {
            return $label . PHP_EOL . str_pad('', strlen($label), '-') . PHP_EOL;
        } else {
            $purifier = Codendi_HTMLPurifier::instance();
            return '
                <tr><td colspan="2">&nbsp;</td></tr>
                <tr style="color: #444444; background-color: #F6F6F6;">
                    <td align="left" colspan="2">
                        <h3>' . $purifier->purify($label) . '</h3>
                    </td>
                </tr>';
        }
    }

    protected function fetchMailArtifactSuffix($format)
    {
        if ($format == 'text') {
            return PHP_EOL;
        } else {
            return '';
        }
    }

    public function fetchAdmin($tracker)
    {
        $html = '';
        $hp = Codendi_HTMLPurifier::instance();
        $html .= '<fieldset class="tracker-admin-container tracker-admin-fieldset" id="tracker-admin-formElements_' . $this->id . '"><legend title="' . $hp->purify($this->getDescription(), CODENDI_PURIFIER_CONVERT_HTML) . '"><label>';
        $html .= $hp->purify($this->getLabel(), CODENDI_PURIFIER_CONVERT_HTML);
        $html .= '</label><span class="tracker-admin-field-controls">';
        $html .= '<a class="edit-field" href="' . $this->getAdminEditUrl() . '">' . $GLOBALS['HTML']->getImage('ic/edit.png', ['alt' => 'edit']) . '</a> ';

        if ($this->canBeRemovedFromUsage()) {
            $html .= '<a href="?' . http_build_query([
                'tracker'  => $this->tracker_id,
                'func'     => 'admin-formElement-remove',
                'formElement' => $this->id,
            ]) . '">' . $GLOBALS['HTML']->getImage('ic/cross.png', ['alt' => 'remove']) . '</a>';
        } else {
            $cannot_remove_message = $this->getCannotRemoveMessage();
            $html .= '<span style="color:gray;" title="' . $cannot_remove_message . '">';
            $html .= $GLOBALS['HTML']->getImage('ic/cross-disabled.png', ['alt' => 'remove']);
            $html .= '</span>';
        }
        $html .= '</span>';
        $html .= '</legend>';
        $content = [];
        foreach ($this->getFormElements() as $formElement) {
            $content[] = $formElement->fetchAdmin($tracker);
        }
        $html .= implode('', $content);
        $html .= '</fieldset>';
        return $html;
    }

    public function canBeRemovedFromUsage(): bool
    {
        return parent::canBeRemovedFromUsage() && ! $this->isFieldsetUsedInPostAction();
    }

    /**
     * @psalm-mutation-free
     */
    public function getLabel(): string
    {
        if (substr($this->label, -8) !== '_lbl_key') {
            return $this->label;
        }

        switch ($this->label) {
            case 'fieldset_default_lbl_key':
                return dgettext('tuleap-tracker', 'Details');
            case 'fieldset_default_bugs_lbl_key':
                return dgettext('tuleap-tracker', 'Details');
            case 'fieldset_status_bugs_lbl_key':
                return dgettext('tuleap-tracker', 'Status');
            case 'fieldset_default_patches_lbl_key':
                return dgettext('tuleap-tracker', 'Details');
            case 'fieldset_patchtext_patches_lbl_key':
                return dgettext('tuleap-tracker', 'Paste the patch here (text only)');
            case 'fieldset_status_patches_lbl_key':
                return dgettext('tuleap-tracker', 'Status');
            case 'fieldset_default_SR_lbl_key':
                return dgettext('tuleap-tracker', 'Details');
            case 'fieldset_status_SR_lbl_key':
                return dgettext('tuleap-tracker', 'Status');
            case 'fieldset_default_tasks_lbl_key':
                return dgettext('tuleap-tracker', 'Details');
            case 'fieldset_status_tasks_lbl_key':
                return dgettext('tuleap-tracker', 'Status');
            case 'fieldset_default_slmbugs_lbl_key':
                return dgettext('tuleap-tracker', 'Details');
            case 'fieldset_salome_slmbugs_lbl_key':
                return dgettext('tuleap-tracker', 'Salome');
            case 'fieldset_status_slmbugs_lbl_key':
                return dgettext('tuleap-tracker', 'Status');
            case 'fieldset_scrum_status_lbl_key':
                return dgettext('tuleap-tracker', 'Status');
            case 'fieldset_scrum_description_lbl_key':
                return dgettext('tuleap-tracker', 'Description');
            default:
                return $this->label;
        }
    }

    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Fieldset');
    }

    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'Group fields in a set');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/application-form.png');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/application-form--plus.png');
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

    public function isCollapsed(): bool
    {
        $current_user = UserManager::instance()->getCurrentUser();

        return (bool) $current_user->getPreference('fieldset_' . $this->getId());
    }

    /**
     * getID - get this Tracker_FormElement_FieldSet ID.
     *
     * @return int The id.
     *
     * @psalm-mutation-free
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * Is the form element can be removed from usage?
     * This method is to prevent tracker inconsistency
     *
     * @return string
     */
    public function getCannotRemoveMessage()
    {
        if ($this->isFieldsetUsedInPostAction() === true) {
            return dgettext(
                'tuleap-tracker',
                'Not allowed to delete a fieldset used in workflow.'
            );
        }

        return parent::getCannotRemoveMessage();
    }

    protected function getHiddenFieldsetsDao(): HiddenFieldsetsDao
    {
        return new HiddenFieldsetsDao();
    }

    private function isFieldsetUsedInPostAction(): bool
    {
        return $this->getHiddenFieldsetsDao()->isFieldsetUsedInPostAction((int) $this->getID());
    }
}
