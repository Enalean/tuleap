<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic\Status\Done;

use Codendi_Request;
use CSRFSynchronizerToken;
use Feedback;
use PDOException;
use PFUser;
use SimpleXMLElement;
use TemplateRendererFactory;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Value;
use Tracker_Semantic;
use Tracker_Semantic_Status;
use Tracker_SemanticManager;
use TrackerManager;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use XML_SimpleXMLCDATAFactory;

class SemanticDone extends Tracker_Semantic
{
    public const NAME = 'done';

    /**
     * @var Tracker_Semantic_Status
     */
    private $semantic_status;

    /**
     * @var SemanticDoneDao
     */
    private $dao;

    /**
     * @var array
     */
    private $done_values;

    /**
     * @var SemanticDoneValueChecker
     */
    private $value_checker;

    public function __construct(
        Tracker $tracker,
        Tracker_Semantic_Status $semantic_status,
        SemanticDoneDao $dao,
        SemanticDoneValueChecker $value_checker,
        array $done_values,
    ) {
        parent::__construct($tracker);

        $this->semantic_status = $semantic_status;
        $this->dao             = $dao;
        $this->done_values     = $done_values;
        $this->value_checker   = $value_checker;
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
        return dgettext('tuleap-tracker', 'Done');
    }

    /**
     * The description of the semantics. Used for breadcrumbs
     *
     * @return string
     */
    public function getDescription()
    {
        return dgettext('tuleap-tracker', 'Define the closed status that are considered Done');
    }

    public function fetchForSemanticsHomepage(): string
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR . '/semantics');

        $semantic_status_field = $this->semantic_status->getField();
        $selected_values       = [];

        if ($semantic_status_field) {
            $selected_values = $this->getFormattedDoneValues();
        }

        $event = \EventManager::instance()->dispatch(
            new SemanticDoneUsedExternalServiceEvent($this->tracker)
        );

        $presenter = new SemanticDoneIntroPresenter(
            $selected_values,
            $semantic_status_field,
            $event->getExternalServicesDescriptions()
        );

        return $renderer->renderToString('done-intro', $presenter);
    }

    public function displayAdmin(Tracker_SemanticManager $semantic_manager, TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user): void
    {
        $this->tracker->displayAdminItemHeaderBurningParrot(
            $tracker_manager,
            'editsemantic',
            $this->getLabel()
        );

        $semantic_status_field = $this->semantic_status->getField();
        $closed_values         = [];

        if ($semantic_status_field) {
            $closed_values = $this->getFormattedClosedValues($semantic_status_field);
        }

        $csrf = $this->getCSRFSynchronizerToken();

        $GLOBALS['HTML']->addJavascriptAsset(new JavascriptAsset(
            new IncludeAssets(__DIR__ . '/../../../../../scripts/tracker-admin/frontend-assets', '/assets/trackers/tracker-admin'),
            'done-semantic.js'
        ));
        $renderer  = TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR . '/semantics');
        $presenter = new SemanticDoneAdminPresenter(
            $csrf,
            $this->tracker,
            $closed_values,
            $this->getUrl(),
            $this->getAdminSemanticUrl(),
            count($this->getDoneValuesIds()) > 0,
            $semantic_status_field,
        );

        $renderer->renderToPage('admin-done', $presenter);

        $semantic_manager->displaySemanticFooter($this, $tracker_manager);
    }

    /**
     * @return CSRFSynchronizerToken
     */
    private function getCSRFSynchronizerToken()
    {
        return new CSRFSynchronizerToken($this->getAdminSemanticUrl());
    }

    /**
     * @return string
     */
    private function getAdminSemanticUrl()
    {
        return TRACKER_BASE_URL . '/?' . http_build_query([
            'tracker' => $this->tracker->getId(),
            'func'    => 'admin-semantic',
        ]);
    }

    /**
     * @return array
     */
    private function getFormattedDoneValues()
    {
        $formatted_done_values = [];

        foreach ($this->done_values as $done_value) {
            $formatted_done_values[] = [
                'label' => $done_value->getLabel(),
            ];
        }

        return $formatted_done_values;
    }

    /**
     * @return array
     */
    private function getFormattedClosedValues(Tracker_FormElement_Field_List $semantic_status_field)
    {
        $done_values_ids        = $this->getDoneValuesIds();
        $formated_closed_values = [];

        foreach ($this->getClosedValues($semantic_status_field) as $value_id => $value) {
            $formated_closed_values[] = [
                'id'       => $value->getId(),
                'label'    => $value->getLabel(),
                'selected' => in_array($value_id, $done_values_ids),
            ];
        }

        return $formated_closed_values;
    }

    /**
     * @return array
     */
    private function getClosedValues(Tracker_FormElement_Field_List $semantic_status_field)
    {
        $all_values    = $semantic_status_field->getAllVisibleValues();
        $open_values   = $this->semantic_status->getOpenValues();
        $closed_values = [];

        foreach ($all_values as $value_id => $value) {
            if (in_array($value_id, $open_values)) {
                continue;
            }

            $closed_values[$value_id] = $value;
        }

        return $closed_values;
    }

    /**
     * Process the form
     *
     * @param Tracker_SemanticManager $semantic_manager The semantic manager
     * @param TrackerManager $tracker_manager The tracker manager
     * @param Codendi_Request $request The request
     * @param PFUser $current_user The user who made the request
     *
     * @return void
     */
    public function process(Tracker_SemanticManager $semantic_manager, TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user)
    {
        $tracker_id = $this->tracker->getId();
        if ($request->exist('submit')) {
            $csrf = $this->getCSRFSynchronizerToken();
            $csrf->check();

            $semantic_status_field = $this->semantic_status->getField();

            $values = $request->get('done_values');

            if (! $semantic_status_field) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::WARN,
                    dgettext('tuleap-tracker', 'Semantic status is not defined.')
                );
            } elseif (isset($values[$tracker_id]) && is_array($values[$tracker_id])) {
                $this->updateValuesForTracker($semantic_status_field, $tracker_id, $values[$tracker_id]);
            } else {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    dgettext('tuleap-tracker', 'The request is not valid.')
                );
            }
        } elseif ($request->exist('delete')) {
            $this->clearValuesForTracker($tracker_id);
        }

        $this->displayAdmin($semantic_manager, $tracker_manager, $request, $current_user);
    }

    private function clearValuesForTracker(int $tracker_id): void
    {
        try {
            $this->dao->clearForTracker($tracker_id);

            $this->setNewDoneValues([]);

            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-tracker', 'Done values successfully cleared.')
            );
        } catch (PDOException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-tracker', 'An error occurred while clearing done values.')
            );
        }
    }

    private function updateValuesForTracker(
        Tracker_FormElement_Field_List $semantic_status_field,
        int $tracker_id,
        array $selected_values,
    ): void {
        $selected_values            = array_map('intval', $selected_values);
        $closed_values              = $this->getClosedValues($semantic_status_field);
        $non_closed_selected_values = array_diff($selected_values, array_keys($closed_values));

        if (count($non_closed_selected_values) > 0) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-tracker', 'Selected values are invalid because some are not closed values anymore.')
            );

            return;
        }

        try {
            $this->dao->updateForTracker($tracker_id, $selected_values);

            $this->setNewDoneValues($selected_values);

            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-tracker', 'Done values successfully updated.')
            );
        } catch (PDOException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-tracker', 'An error occurred while updating done values.')
            );
        }
    }

    private function setNewDoneValues(array $selected_values): void
    {
        $this->done_values = [];

        $field = $this->semantic_status->getField();

        if ($selected_values === [] || ! $field) {
            return;
        }

        foreach ($selected_values as $selected_value_id) {
            $value = $field->getBind()->getValue($selected_value_id);
            assert($value instanceof Tracker_FormElement_Field_List_Value);

            if ($value && $this->value_checker->isValueAPossibleDoneValue($value, $this->semantic_status)) {
                $this->done_values[$selected_value_id] = $value;
            }
        }
    }

    /**
     * Export semantic to XML
     *
     * @param SimpleXMLElement &$root the node to which the semantic is attached (passed by reference)
     * @param array $xml_mapping correspondance between real ids and xml IDs
     *
     * @return void
     */
    public function exportToXml(SimpleXMLElement $root, $xml_mapping)
    {
        $status_field = $this->semantic_status->getField();

        if (! $status_field) {
            return;
        }

        if (in_array($status_field->getId(), $xml_mapping)) {
            $child = $root->addChild('semantic');
            $child->addAttribute('type', $this->getShortName());
            $cdata = new XML_SimpleXMLCDATAFactory();
            $cdata->insert($child, 'shortname', $this->getShortName());
            $cdata->insert($child, 'label', $this->getLabel());
            $cdata->insert($child, 'description', $this->getDescription());
            $node_closed_values = $child->addChild('closed_values');
            foreach ($this->done_values as $value) {
                if ($ref = array_search($value->getId(), $xml_mapping['values'])) {
                    $node_closed_values->addChild('closed_value')->addAttribute('REF', $ref);
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
        return $this->semantic_status->isUsedInSemantics($field);
    }

    /**
     * Save the semantic in database
     *
     * @return bool true if success, false otherwise
     */
    public function save()
    {
        /* This method is called in the Tracker XML import context
           We assume that all the needed checks are done before, so we can save
           directly the values in the database.
        */

        $done_values_ids = $this->getDoneValuesIds();

        if (count($done_values_ids) === 0) {
            return true;
        }

        return $this->dao->addForTracker(
            $this->tracker->getId(),
            $done_values_ids
        );
    }

    /**
     * @return array
     */
    public function getDoneValuesIds()
    {
        $done_values_ids = [];
        foreach ($this->done_values as $done_value) {
            $done_values_ids[] = $done_value->getId();
        }

        return $done_values_ids;
    }

    /**
     * @return bool
     */
    public function isSemanticDefined()
    {
        return $this->semantic_status->getField() && count($this->getDoneValuesIds()) > 0;
    }

    /**
     * @return bool
     */
    public function isDone(Tracker_Artifact_Changeset $changeset)
    {
        $field = $this->semantic_status->getField();
        if ($field === null) {
            return false;
        }
        $status_value = $changeset->getValue($field);
        if ($status_value === null) {
            return false;
        }

        assert($status_value instanceof Tracker_Artifact_ChangesetValue_List);

        $list_values = $status_value->getListValues();
        foreach ($list_values as $list_value) {
            if ($this->dao->isValueADoneValue($changeset->getTracker()->getId(), (int) $list_value->getId())) {
                return true;
            }
        }
        return false;
    }

    /**
     * @var self[]
     */
    private static $instances;
    /**
     * Load an instance of a SemanticDone
     *
     * @param Tracker $tracker the tracker
     *
     * @return SemanticDone
     */
    public static function load(Tracker $tracker)
    {
        if (! isset(self::$instances[$tracker->getId()])) {
            return self::forceLoad($tracker);
        }

        return self::$instances[$tracker->getId()];
    }

    private static function forceLoad(Tracker $tracker): SemanticDone
    {
        $semantic_status = Tracker_Semantic_Status::load($tracker);
        $dao             = new SemanticDoneDao();
        $value_checker   = new SemanticDoneValueChecker();

        $semantic_done = (new SemanticDoneLoader($dao, $value_checker))->load($tracker, $semantic_status);

        self::$instances[$tracker->getId()] = $semantic_done;

        return self::$instances[$tracker->getId()];
    }

    /**
     * @return Tracker_Semantic_Status
     */
    public function getSemanticStatus()
    {
        return $this->semantic_status;
    }
}
