<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

use Tuleap\Tracker\Notifications\Settings\CalendarEventConfigDao;

class Tracker_Semantic_Title extends Tracker_Semantic
{
    public const NAME = 'title';

    /**
     * @var Tracker_FormElement_Field_Text
     */
    protected $text_field;

    /**
     * Cosntructor
     *
     * @param Tracker                        $tracker    The tracker
     * @param Tracker_FormElement_Field_Text $text_field The field
     */
    public function __construct(Tracker $tracker, ?Tracker_FormElement_Field_Text $text_field = null)
    {
        parent::__construct($tracker);
        $this->text_field = $text_field;
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
        return dgettext('tuleap-tracker', 'Title');
    }

    /**
     * The description of the semantics. Used for breadcrumbs
     *
     * @return string
     */
    public function getDescription()
    {
        return dgettext('tuleap-tracker', 'Define the title of an artifact');
    }

    /**
     * The Id of the (text) field used for title semantic
     *
     * @return int The Id of the (text) field used for title semantic, or 0 if no field
     */
    public function getFieldId()
    {
        if ($this->text_field) {
            return $this->text_field->getId();
        } else {
            return 0;
        }
    }

    /**
     * The (text) field used for title semantic
     *
     * @return Tracker_FormElement_Field_Text The (text) field used for title semantic, or null if no field
     */
    public function getField()
    {
        return $this->text_field;
    }

    public function fetchForSemanticsHomepage(): string
    {
        $html = '<p>' . dgettext('tuleap-tracker', 'The title summarizes an artifact and will be used in various places: widgets, artifact links, email notifications, ...') . '</p>';
        if ($field = Tracker_FormElementFactory::instance()->getUsedFormElementById($this->getFieldId())) {
            $purifier = Codendi_HTMLPurifier::instance();
            $html    .= '<p>' . sprintf(
                dgettext('tuleap-tracker', 'The artifacts of this tracker will be summarized by the field %s.'),
                '<strong>' . $purifier->purify($field->getLabel()) . '</strong>'
            ) . '</p>';
            return $html;
        }
        $html .= '<p>' . sprintf(
            dgettext('tuleap-tracker', 'The artifacts of this tracker do not have any %s title %s yet.'),
            '<em>',
            '</em>'
        ) . '</p>';
        return $html;
    }

    public function displayAdmin(Tracker_SemanticManager $semantic_manager, TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user): void
    {
        $this->tracker->displayAdminItemHeaderBurningParrot(
            $tracker_manager,
            'editsemantic',
            $this->getLabel()
        );

        $template_rendreder      = TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR);
        $admin_presenter_builder = new \Tuleap\Tracker\Semantic\Title\AdminPresenterBuilder(
            Tracker_FormElementFactory::instance(),
            new CalendarEventConfigDao(),
        );

        echo $template_rendreder->renderToString(
            'semantics/admin-title',
            $admin_presenter_builder->build($this, $this->tracker, $this->getCSRFToken())
        );

        $semantic_manager->displaySemanticFooter($this, $tracker_manager);
    }

    public function process(Tracker_SemanticManager $semantic_manager, TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user): void
    {
        if ($request->exist('update')) {
            $this->getCSRFToken()->check();
            if ($field = Tracker_FormElementFactory::instance()->getUsedTextFieldById($this->tracker, $request->get('text_field_id'))) {
                $this->text_field = $field;
                if ($this->save()) {
                    $GLOBALS['Response']->addFeedback(Feedback::INFO, dgettext('tuleap-tracker', 'Semantic title updated'));
                    $GLOBALS['Response']->redirect($this->getUrl());
                } else {
                    $GLOBALS['Response']->addFeedback(Feedback::ERROR, dgettext('tuleap-tracker', 'Unable to save the title'));
                }
            } else {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, dgettext('tuleap-tracker', 'The field you submitted is not a text field'));
            }
        } elseif ($request->exist('delete')) {
            $this->getCSRFToken()->check();
            if ($this->delete()) {
                $GLOBALS['Response']->addFeedback(Feedback::INFO, dgettext('tuleap-tracker', 'Semantic title unset'));
                $GLOBALS['Response']->redirect($this->getUrl());
            } else {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, dgettext('tuleap-tracker', 'Unable to save the title'));
            }
        }
        $this->displayAdmin($semantic_manager, $tracker_manager, $request, $current_user);
    }

    /**
     * Save this semantic
     *
     * @return bool true if success, false otherwise
     */
    public function save()
    {
        $dao = new Tracker_Semantic_TitleDao();
        return $dao->save($this->tracker->getId(), $this->getFieldId());
    }

    public function delete()
    {
        $dao = new Tracker_Semantic_TitleDao();
        return $dao->delete($this->tracker->getId());
    }

    protected static $_instances;
    /**
     * Load an instance of a Tracker_Semantic_Title
     *
     *
     * @return Tracker_Semantic_Title
     */
    public static function load(Tracker $tracker)
    {
        if (! isset(self::$_instances[$tracker->getId()])) {
            $field_id = null;
            $dao      = new Tracker_Semantic_TitleDao();
            if ($row = $dao->searchByTrackerId($tracker->getId())->getRow()) {
                $field_id = $row['field_id'];
            }
            $field = null;
            if ($field_id) {
                $field = Tracker_FormElementFactory::instance()->getFieldById($field_id);
            }
            self::$_instances[$tracker->getId()] = new Tracker_Semantic_Title($tracker, $field);
        }
        return self::$_instances[$tracker->getId()];
    }

    /**
     * Allows to inject a fake factory for test. DO NOT USE IT IN PRODUCTION!
     */
    public static function setInstance(Tracker_Semantic_Title $semantic_title, Tracker $tracker)
    {
        self::$_instances[$tracker->getId()] = $semantic_title;
    }

    /**
     * Allows clear factory instance for test. DO NOT USE IT IN PRODUCTION!
     */
    public static function clearInstances()
    {
        self::$_instances = null;
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
}
