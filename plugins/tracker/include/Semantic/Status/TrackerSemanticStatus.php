<?php
/*
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Status;

use Codendi_HTMLPurifier;
use Codendi_Request;
use Feedback;
use PFUser;
use SimpleXMLElement;
use TemplateRendererFactory;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElementFactory;
use TrackerManager;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\REST\SemanticStatusRepresentation;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneDao;
use Tuleap\Tracker\Semantic\Status\Open\AdminPresenterBuilder;
use Tuleap\Tracker\Semantic\TrackerSemantic;
use Tuleap\Tracker\Semantic\TrackerSemanticManager;
use Tuleap\Tracker\Tracker;

class TrackerSemanticStatus extends TrackerSemantic
{
    public const NAME   = 'status';
    public const OPEN   = 'Open';
    public const CLOSED = 'Closed';

    private ?ListField $list_field;

    /**
     * @var array
     */
    protected $open_values;

    /**
     * Constructor
     *
     * @param Tracker $tracker The tracker
     * @param ListField $list_field The field
     * @param array $open_values The values with the meaning "Open"
     */
    public function __construct(Tracker $tracker, ?ListField $list_field = null, $open_values = [])
    {
        parent::__construct($tracker);
        $this->list_field  = $list_field;
        $this->open_values = $open_values;
    }

    private function getDao()
    {
        return new StatusSemanticDAO();
    }

    /**
     * The short name of the semantic: tooltip, title, status, owner, ...
     *
     * @return string
     */
    #[\Override]
    public function getShortName()
    {
        return self::NAME;
    }

    /**
     * The label of the semantic: Tooltip, ...
     *
     * @return string
     */
    #[\Override]
    public function getLabel()
    {
        return dgettext('tuleap-tracker', 'Status');
    }

    /**
     * The description of the semantics. Used for breadcrumbs
     *
     * @return string
     */
    #[\Override]
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

    public function getField(): ?ListField
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
     * @return string
     * @deprecated in favor of getLocalizedStatusLabel
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

        if (! $this->list_field instanceof ListField) {
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

    #[\Override]
    public function fetchForSemanticsHomepage(): string
    {
        if ($this->list_field) {
            $purifier = Codendi_HTMLPurifier::instance();
            $html     = '<p>' . sprintf(dgettext('tuleap-tracker', 'An artifact is considered to be open when its field %s will have one of the following values:'), $purifier->purify($this->list_field->getLabel())) . '</p>';
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

    #[\Override]
    public function displayAdmin(
        TrackerSemanticManager $semantic_manager,
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

    #[\Override]
    public function process(TrackerSemanticManager $semantic_manager, TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user): void
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
        $dao               = new StatusSemanticDAO();
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
        return $this->tracker->getNotificationsLevel() === \Tuleap\Tracker\Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE;
    }

    /**
     * Save this semantic
     *
     * @return bool
     */
    #[\Override]
    public function save()
    {
        $dao         = new StatusSemanticDAO();
        $open_values = [];
        foreach ($this->open_values as $v) {
            if (is_scalar($v)) {
                $open_values[] = (int) $v;
            } else {
                $open_values[] = (int) $v->getId();
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
        if ($this->list_field === null) {
            throw new SemanticStatusNotDefinedException();
        }

        $transaction_executor = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());
        return $transaction_executor->execute(function () use ($new_value) {
            assert($this->list_field !== null);
            $new_id              = $this->list_field->addBindValue($new_value);
            $this->open_values[] = $new_id;
            $this->save();
            return $new_id;
        });
    }

    public function removeOpenValue($value)
    {
        $this->open_values = array_diff($this->open_values, [$value]);
        return $this->save();
    }

    /**
     * Export semantic to XML
     *
     * @param SimpleXMLElement &$root the node to which the semantic is attached (passed by reference)
     * @param array $xml_mapping correspondance between real ids and xml IDs
     *
     * @return void
     */
    #[\Override]
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
     * @param TrackerField the field to test if it is used in semantics or not
     *
     * @return bool returns true if the field is used in semantics, false otherwise
     */
    #[\Override]
    public function isUsedInSemantics(TrackerField $field)
    {
        return $this->getFieldId() == $field->getId();
    }

    #[\Override]
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
            return '';
        }

        $value_provider = new StatusValueForChangesetProvider();
        return (new StatusColorForChangesetProvider($value_provider))->provideColor($changeset, $this->tracker, $user);
    }
}
