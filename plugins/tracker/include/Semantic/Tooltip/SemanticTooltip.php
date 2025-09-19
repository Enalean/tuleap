<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Tooltip;

use Codendi_HTMLPurifier;
use Codendi_Request;
use PFUser;
use TemplateRendererFactory;
use Tracker_FormElementFactory;
use TrackerManager;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\SystemTypePresenterBuilder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Semantic\Progress\MethodBuilder;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressBuilder;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressDao;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Title\CachedSemanticTitleFieldRetriever;
use Tuleap\Tracker\Semantic\TrackerSemantic;
use Tuleap\Tracker\Semantic\TrackerSemanticManager;

class SemanticTooltip extends TrackerSemantic implements TooltipFields
{
    public const NAME = 'tooltip';

    public $fields = [];

    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    #[\Override]
    public function getFields(): array
    {
        if (empty($this->fields)) {
            $tf           = Tracker_FormElementFactory::instance();
            $this->fields = [];
            foreach ($this->getDao()->searchByTrackerId($this->tracker->id) as $row) {
                if ($field = $tf->getUsedFormElementById($row['field_id'])) {
                    $this->fields[$field->id] = $field;
                }
            }
        }
        return $this->fields;
    }

    private function getDao()
    {
        return new SemanticTooltipDao();
    }

    /**
     * Save this semantic
     *
     * @return bool true if success, false otherwise
     */
    #[\Override]
    public function save()
    {
        $dao = $this->getDao();
        foreach ($this->fields as $fld) {
            $dao->add($this->tracker->id, $fld->id, 'end');
        }
        $this->fields = [];
    }

    /**
     * Process the form
     *
     * @param TrackerSemanticManager $semantic_manager The semantic manager
     * @param TrackerManager          $tracker_manager  The tracker manager
     * @param Codendi_Request         $request          The request
     * @param PFUser                  $current_user     The user who made the request
     *
     * @return void
     */
    #[\Override]
    public function process(
        TrackerSemanticManager $semantic_manager,
        TrackerManager $tracker_manager,
        Codendi_Request $request,
        PFUser $current_user,
    ) {
        if ($request->get('add-field') && (int) $request->get('field')) {
            $this->getCSRFToken()->check();
            //retrieve the field if used
            $f = Tracker_FormElementFactory::instance()->getUsedFormElementById($request->get('field'));

            //store the new field
            $this->getDao()->add($this->tracker->id, $f->id, 'end');
        } elseif ((int) $request->get('remove')) {
            $this->getCSRFToken()->check();
            //retrieve the field if used
            $f = Tracker_FormElementFactory::instance()->getUsedFormElementById($request->get('remove'));

            //store the new field
            $this->getDao()->remove($this->tracker->id, $f->id);
        }
        $this->displayAdmin($semantic_manager, $tracker_manager, $request, $current_user);
    }

    /**
     * The short name of the semantic: tooltip, title, status, owner, ...
     *
     * @return string
     */
    #[\Override]
    public function getShortName()
    {
        return 'tooltip';
    }

    /**
     * The label of the semantic: Tooltip, ...
     *
     * @return string
     */
    #[\Override]
    public function getLabel()
    {
        return dgettext('tuleap-tracker', 'Tooltip');
    }

    /**
     * The description of the semantics. Used for breadcrumbs
     *
     * @return string
     */
    #[\Override]
    public function getDescription()
    {
        return dgettext('tuleap-tracker', 'Manage tooltip');
    }

    /**
     * @return string[]
     */
    private function getOtherSemanticsLabels(): array
    {
        $others = [];

        $title_field = CachedSemanticTitleFieldRetriever::instance()->fromTracker($this->tracker);
        if ($title_field !== null) {
            $others[] = $title_field->getLabel();
        }

        $progress_dao     = new SemanticProgressDao();
        $progress_builder = new SemanticProgressBuilder(
            $progress_dao,
            new MethodBuilder(
                Tracker_FormElementFactory::instance(),
                $progress_dao,
                new TypePresenterFactory(
                    new TypeDao(),
                    new ArtifactLinksUsageDao(),
                    new SystemTypePresenterBuilder(\EventManager::instance())
                )
            )
        );

        $timeframe = SemanticTimeframeBuilder::build()->getSemantic($this->tracker);
        if ($timeframe->isDefined()) {
            $others[] = $timeframe->getLabel();
        }

        $progress = $progress_builder->getSemantic($this->tracker);
        if ($progress->isDefined()) {
            $others[] = $progress->getLabel();
        }

        return $others;
    }

    private function getAdminSemanticUrl(): string
    {
        return TRACKER_BASE_URL . '/?' . http_build_query([
            'tracker' => $this->tracker->getId(),
            'func' => 'admin-semantic',
        ]);
    }

    /**
     * Display the form to let the admin change the semantic
     *
     * @param TrackerSemanticManager $semantic_manager The semantic manager
     * @param TrackerManager          $tracker_manager  The tracker manager
     * @param Codendi_Request         $request          The request
     * @param PFUser                  $current_user     The user who made the request
     *
     * @return void
     */
    #[\Override]
    public function displayAdmin(
        TrackerSemanticManager $semantic_manager,
        TrackerManager $tracker_manager,
        Codendi_Request $request,
        PFUser $current_user,
    ) {
        $this->tracker->displayAdminItemHeaderBurningParrot(
            $tracker_manager,
            'editsemantic',
            $this->getLabel()
        );

        $fields = $this->getFields();

        $select_options = (new SelectOptionsBuilder(Tracker_FormElementFactory::instance()))
            ->build($this->tracker, $current_user, $fields);

        $renderer  = TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../templates/semantics');
        $presenter = new SemanticTooltipAdminPresenter(
            $this->getOtherSemanticsLabels(),
            $this->getCSRFToken(),
            array_values(
                array_map(
                    static fn (\Tuleap\Tracker\FormElement\TrackerFormElement $field) => TooltipFieldPresenter::buildFromFormElement($field),
                    $fields,
                ),
            ),
            $this->getUrl(),
            $this->getAdminSemanticUrl(),
            $select_options,
        );
        $renderer->renderToPage('admin-tooltip', $presenter);

        $semantic_manager->displaySemanticFooter($this, $tracker_manager);
    }

    #[\Override]
    public function fetchForSemanticsHomepage(): string
    {
        $html = '';
        $hp   = Codendi_HTMLPurifier::instance();

        $fields                 = $this->getFields();
        $other_semantics_labels = $this->getOtherSemanticsLabels();

        $html .= '<p>';
        if (empty($fields) && empty($other_semantics_labels)) {
            $html .= dgettext('tuleap-tracker', 'There isn\'t any information in the tooltip yet.');
        } else {
            $html .= dgettext('tuleap-tracker', 'The following information will be displayed in the tooltip:');
            $html .= '<ul>';
            foreach ($other_semantics_labels as $semantic) {
                $html .= '<li><strong>' . sprintf(dgettext('tuleap-tracker', 'Semantic %s'), $hp->purify($semantic, CODENDI_PURIFIER_CONVERT_HTML)) . '</strong></li>';
            }
            foreach ($fields as $f) {
                $html .= '<li><strong>' . $hp->purify($f->getLabel(), CODENDI_PURIFIER_CONVERT_HTML) . '</strong></li>';
            }
            $html .= '</ul>';
        }
        $html .= '</p>';

        return $html;
    }

    /**
     * Transforms tooltip into a SimpleXMLElement
     *
     * @param \SimpleXMLElement &$root the node to which the semantic is attached (passed by reference)
     * @param array             $xml_mapping correspondance between real field ids and xml IDs
     *
     * @return void
     */
    #[\Override]
    public function exportToXml(\SimpleXMLElement $root, $xml_mapping)
    {
        $child = $root->addChild('semantic');
        $child->addAttribute('type', $this->getShortName());
        foreach ($this->getFields() as $field) {
            $child->addChild('field')->addAttribute('REF', array_search($field->id, $xml_mapping));
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
        $fields = $this->getFields();
        foreach ($fields as $f) {
            if ($f->getId() == $field->getId()) {
                return true;
            }
        }
        return false;
    }
}
