<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU GeLneral Public License as published by
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

use Tuleap\Layout\IncludeAssets;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\SemanticStatusRepresentation;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneDao;
use Tuleap\Tracker\Semantic\Status\Open\AdminPresenterBuilder;
use Tuleap\Tracker\Semantic\Status\SemanticStatusNotDefinedException;
use Tuleap\Tracker\Semantic\Status\StatusColorForChangesetProvider;
use Tuleap\Tracker\Semantic\Status\StatusValueForChangesetProvider;

class Tracker_Semantic_Status extends Tracker_Semantic
{
    public const NAME   = 'status';
    public const OPEN   = 'Open';
    public const CLOSED = 'Closed';

    private ?Tracker_FormElement_Field_List $list_field;

    /**
     * @var array
     */
    protected $open_values;

    /**
     * Constructor
     *
     * @param Tracker                        $tracker     The tracker
     * @param Tracker_FormElement_Field_List $list_field  The field
     * @param array                          $open_values The values with the meaning "Open"
     */
    public function __construct(Tracker $tracker, ?Tracker_FormElement_Field_List $list_field = null, $open_values = [])
    {
        parent::__construct($tracker);
        $this->list_field  = $list_field;
        $this->open_values = $open_values;
    }

    private function getDao()
    {
        return new Tracker_Semantic_StatusDao();
    }

    /**
     * The short name of the semantic: tooltip, title, status, owner, ...
     *
     * @return string
     */
    public function getShortName()
    {
        return self::NAME;
    }

    /**
     * The label of the semantic: Tooltip, ...
     *
     * @return string
     */
    public function getLabel()
    {
        return dgettext('tuleap-tracker', 'Status');
    }

    /**
     * The description of the semantics. Used for breadcrumbs
     *
     * @return string
     */
    public function getDescription()
    {
        return dgettext('tuleap-tracker', 'Define the status of an artifact');
    }

    public function getFieldId(): int
    {
        if ($this->list_field) {
            return $this->list_field->getId();
        } else {
            return 0;
        }
    }

    public function getField(): ?Tracker_FormElement_Field_List
    {
        return $this->list_field;
    }

    /**
     * The Ids of open values for this status semantic
     *
     * @return array of int The Id of the open values for this status semantic
     */
    public function getOpenValues()
    {
        return $this->open_values;
    }

    public function isOpen(Artifact $artifact)
    {
        if (! $this->getField()) {
            return true;
        }

        $status = $artifact->getStatus();
        if (! $status) {
            return false;
        }

        return in_array($status, $this->getOpenLabels());
    }

    public function isOpenAtGivenChangeset(Tracker_Artifact_Changeset $changeset)
    {
        if (! $this->getField()) {
            return true;
        }

        $status = $changeset->getArtifact()->getStatusForChangeset($changeset);
        return in_array($status, $this->getOpenLabels());
    }

    /**
     * Get status label independent of language (hence english)
     *
     * @return string
     */
    public function getNormalizedStatusLabel(Artifact $artifact)
    {
        if ($this->isOpen($artifact)) {
            return self::OPEN;
        }

        return self::CLOSED;
    }

    /**
     * Get status label according to current user language preference
     *
     * @return string
     */
    public function getLocalizedStatusLabel(Artifact $artifact)
    {
        if ($this->isOpen($artifact)) {
            return dgettext('tuleap-tracker', 'Open');
        }

        return dgettext('tuleap-tracker', 'Closed');
    }

    /**
     * @deprecated in favor of getLocalizedStatusLabel
     * @return string
     */
    public function getStatus(Artifact $artifact)
    {
        return $this->getLocalizedStatusLabel($artifact);
    }

    /**
     * @return string[]
     */
    public function getOpenLabels(): array
    {
        $labels = [];

        if (! $this->list_field instanceof Tracker_FormElement_Field_List) {
            return $labels;
        }
        $field_values = $this->list_field->getAllValues();

        foreach ($this->open_values as $value) {
            if (isset($field_values[$value])) {
                $labels[] = $field_values[$value]->getLabel();
            }
        }

        return $labels;
    }

    public function fetchForSemanticsHomepage(): string
    {
        if ($this->list_field) {
            $purifier = Codendi_HTMLPurifier::instance();
            $html     = "<p>" . sprintf(dgettext('tuleap-tracker', 'An artifact is considered to be open when its field %s will have one of the following values:'), $purifier->purify($this->list_field->getLabel())) . "</p>";
            if ($this->open_values) {
                $html        .= '<ul>';
                $field_values = $this->list_field->getAllValues();
                foreach ($this->open_values as $v) {
                    if (isset($field_values[$v])) {
                        $html .= '<li><strong>' . $purifier->purify($field_values[$v]->getLabel()) . '</strong></li>';
                    }
                }
                $html .= '</ul>';
            } else {
                $html .= '<blockquote><em>' . dgettext('tuleap-tracker', 'No value has been set') . '</em></blockquote>';
            }

            return $html;
        }

        return dgettext('tuleap-tracker', '<p>The artifacts of this tracker does not have any <em>status</em> yet.</p>');
    }

    public function displayAdmin(
        Tracker_SemanticManager $semantic_manager,
        TrackerManager $tracker_manager,
        Codendi_Request $request,
        PFUser $current_user,
    ): void {
        $this->tracker->displayAdminItemHeaderBurningParrot(
            $tracker_manager,
            'editsemantic',
            $this->getLabel()
        );

        $template_rendreder      = TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR);
        $admin_presenter_builder = new AdminPresenterBuilder(Tracker_FormElementFactory::instance(), new SemanticDoneDao());

        $GLOBALS['HTML']->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset(
            new IncludeAssets(__DIR__ . '/../../../scripts/tracker-admin/frontend-assets', '/assets/trackers/tracker-admin'),
            'status-semantic.js'
        ));

        echo $template_rendreder->renderToString(
            'semantics/admin-status-open',
            $admin_presenter_builder->build($this, $this->tracker, $this->getCSRFToken())
        );

        $semantic_manager->displaySemanticFooter($this, $tracker_manager);
    }

    private function getDisabledValues(): array
    {
        $dao = new SemanticDoneDao();

        $disabled_values = [];
        foreach ($dao->getSelectedValues($this->getTracker()->getId()) as $value_row) {
            $disabled_values[] = $value_row['value_id'];
        }

        return $disabled_values;
    }

    public function process(Tracker_SemanticManager $semantic_manager, TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user): void
    {
        if ($request->exist('delete')) {
            $this->getCSRFToken()->check();
            $this->processDelete();
        }

        if ($request->exist('update')) {
            $this->getCSRFToken()->check();
            $this->processUpdate($request);
        }
        $this->displayAdmin($semantic_manager, $tracker_manager, $request, $current_user);
    }

    /**
     * @return array
     */
    private function getFilteredOpenValues(Codendi_Request $request)
    {
        $filtered_values = [];
        $open_values     = $request->get('open_values');

        if (
            ! $open_values ||
            ! is_array($open_values) ||
            ! isset($open_values[$this->getFieldId()]) ||
            ! is_array($open_values[$this->getFieldId()])
        ) {
            return $filtered_values;
        }

        $selected_open_values = $open_values[$this->getFieldId()];
        $filtered_values      = array_diff($selected_open_values, $this->getDisabledValues());

        if ($filtered_values !== $selected_open_values) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                dgettext('tuleap-tracker', 'Some selected values was not saved because they are used in another semantic.')
            );
        }

        return $filtered_values;
    }

    /**
     * Delete this semantic
     */
    private function processDelete()
    {
        if (! $this->getField()) {
            return;
        }

        if ($this->doesTrackerNotificationUseStatusSemantic()) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-tracker', 'The semantic status cannot de deleted because tracker notifications is set to "Status change notifications".')
            );
            return;
        }

        if ($this->doesSemanticDoneHaveDefinedValues($this->getTracker())) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-tracker', 'The semantic status cannot de deleted because the semantic done is defined for this tracker.')
            );
            return;
        }

        $this->list_field  = null;
        $this->open_values = [];
        $dao               = new Tracker_Semantic_StatusDao();
        $dao->delete($this->tracker->getId());
    }

    private function doesSemanticDoneHaveDefinedValues(Tracker $tracker): bool
    {
        $dao             = new SemanticDoneDao();
        $selected_values = $dao->getSelectedValues($tracker->getId());

        return count($selected_values) > 0;
    }

    private function doesTrackerNotificationUseStatusSemantic()
    {
        return $this->tracker->getNotificationsLevel() === \Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE;
    }

    /**
     * Save this semantic
     *
     * @return bool
     */
    public function save()
    {
        $dao         = new Tracker_Semantic_StatusDao();
        $open_values = [];
        foreach ($this->open_values as $v) {
            if (is_scalar($v)) {
                $open_values[] = $v;
            } else {
                $open_values[] = $v->getId();
            }
        }
        $this->open_values = $open_values;
        return $dao->save($this->tracker->getId(), $this->getFieldId(), $this->open_values);
    }

    /**
     * @throws SemanticStatusNotDefinedException
     */
    public function addOpenValue(string $new_value): ?int
    {
        if (! $this->list_field) {
            throw new SemanticStatusNotDefinedException();
        }
        $dao = $this->getDao();

        $dao->startTransaction();
        $new_id              = $this->list_field->addBindValue($new_value);
        $this->open_values[] = $new_id;
        $this->save();
        $dao->commit();

        return $new_id;
    }

    public function removeOpenValue($value)
    {
        $this->open_values = array_diff($this->open_values, [$value]);
        return $this->save();
    }

    protected static $_instances;
    /**
     * Load an instance of a Tracker_Semantic_Status
     *
     * @param Tracker $tracker the tracker
     *
     * @return Tracker_Semantic_Status
     */
    public static function load(Tracker $tracker)
    {
        if (! isset(self::$_instances[$tracker->getId()])) {
            return self::forceLoad($tracker);
        }

        return self::$_instances[$tracker->getId()];
    }

    public static function forceLoad(Tracker $tracker)
    {
        $field_id    = null;
        $open_values = [];
        $dao         = new Tracker_Semantic_StatusDao();

        foreach ($dao->searchByTrackerId($tracker->getId()) as $row) {
            $field_id      = $row['field_id'];
            $open_values[] = (int) $row['open_value_id'];
        }

        if (! $open_values) {
            $open_values[] = 100;
        }

        $fef   = Tracker_FormElementFactory::instance();
        $field = $fef->getFieldById($field_id);

        self::$_instances[$tracker->getId()] = new Tracker_Semantic_Status($tracker, $field, $open_values);

        return self::$_instances[$tracker->getId()];
    }

    /**
     * Export semantic to XML
     *
     * @param SimpleXMLElement &$root      the node to which the semantic is attached (passed by reference)
     * @param array            $xml_mapping correspondance between real ids and xml IDs
     *
     * @return void
     */
    public function exportToXml(SimpleXMLElement $root, $xml_mapping)
    {
        if ($this->getFieldId() && in_array($this->getFieldId(), $xml_mapping)) {
            $child = $root->addChild('semantic');
            $child->addAttribute('type', $this->getShortName());
            $cdata = new \XML_SimpleXMLCDATAFactory();
            $cdata->insert($child, 'shortname', $this->getShortName());
            $cdata->insert($child, 'label', $this->getLabel());
            $cdata->insert($child, 'description', $this->getDescription());
            $child->addChild('field')->addAttribute('REF', array_search($this->getFieldId(), $xml_mapping));
            $node_open_values = $child->addChild('open_values');
            foreach ($this->open_values as $value) {
                if ($ref = array_search($value, $xml_mapping['values'])) {
                    $node_open_values->addChild('open_value')->addAttribute('REF', $ref);
                }
            }
        }
    }

     /**
     * Is the field used in semantics?
     *
     * @param Tracker_FormElement_Field the field to test if it is used in semantics or not
     *
     * @return bool returns true if the field is used in semantics, false otherwise
     */
    public function isUsedInSemantics(Tracker_FormElement_Field $field)
    {
        return $this->getFieldId() == $field->getId();
    }

    public function exportToREST(PFUser $user)
    {
        $field = $this->getFieldUserCanRead($user);
        if ($field) {
            $semantic_status_representation = new SemanticStatusRepresentation();
            $semantic_status_representation->build($field->getId(), $this->getOpenValues());
            return $semantic_status_representation;
        }
        return false;
    }

    public function isFieldBoundToStaticValues()
    {
        $bindType = $this->list_field?->getBind()->getType();

        return ($bindType == Tracker_FormElement_Field_List_Bind_Static::TYPE);
    }

    public function isBasedOnASharedField()
    {
        return $this->list_field?->isTargetSharedField();
    }

    public function isOpenValue($label)
    {
        return in_array($label, $this->getOpenLabels());
    }

    /**
     * Allows to inject a fake Semantic for tests. DO NOT USE IT IN PRODUCTION!
     */
    public static function setInstance(Tracker_Semantic_Status $status, Tracker $tracker)
    {
        self::$_instances[$tracker->getId()] = $status;
    }

    /**
     * Allows to clear Semantics for tests. DO NOT USE IT IN PRODUCTION!
     */
    public static function clearInstances()
    {
        self::$_instances = null;
    }

    private function processUpdate(Codendi_Request $request): void
    {
        $field = Tracker_FormElementFactory::instance()->getUsedListFieldById(
            $this->tracker,
            $request->get('field_id')
        );
        if (! $field) {
            $GLOBALS['Response']->addFeedback(
                'error',
                dgettext('tuleap-tracker', 'The field you submitted is not a list field')
            );
            return;
        }

        if ($this->getFieldId() == $request->get('field_id') && ! $request->get('open_values')) {
            return;
        }

        if ($this->getFieldId() !== $field->getId()) {
            if ($this->doesSemanticDoneHaveDefinedValues($this->getTracker())) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    dgettext('tuleap-tracker', 'The field for semantic status cannot be updated because semantic done is defined for this tracker.')
                );
                return;
            }
        }

        $this->list_field = $field;
        $open_values      = $this->getFilteredOpenValues($request);
        if (count($open_values) <= 0) {
            return;
        }

        $this->open_values = $open_values;
        if ($this->save()) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                sprintf(dgettext('tuleap-tracker', 'The status is now bind to: %1$s'), $field->getLabel())
            );
            $GLOBALS['Response']->redirect($this->getUrl());
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-tracker', 'Unable to save the status')
            );
        }
    }

    public function getColor(?Tracker_Artifact_Changeset $changeset, PFUser $user): ?string
    {
        if (! $changeset) {
            return "";
        }

        $value_provider = new StatusValueForChangesetProvider();
        return (new StatusColorForChangesetProvider($value_provider))->provideColor($changeset, $this->tracker, $user);
    }
}
