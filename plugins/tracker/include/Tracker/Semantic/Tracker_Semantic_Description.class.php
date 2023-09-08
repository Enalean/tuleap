<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */


class Tracker_Semantic_Description extends Tracker_Semantic
{
    public const NAME = 'description';

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
     * The short name of the semantic: tooltip, description, status, owner, ...
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
        return dgettext('tuleap-tracker', 'Description');
    }

    /**
     * The description of the semantics. Used for breadcrumbs
     *
     * @return string
     */
    public function getDescription()
    {
        return dgettext('tuleap-tracker', 'Define the description of an artifact');
    }

    /**
     * The Id of the (text) field used for description semantic
     *
     * @return int The Id of the (text) field used for description semantic, or 0 if no field
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
     * The (text) field used for description semantic
     *
     * @return Tracker_FormElement_Field_Text The (text) field used for description semantic, or null if no field
     */
    public function getField()
    {
        return $this->text_field;
    }

    public function fetchForSemanticsHomepage(): string
    {
        $warning = '';
        $field   = Tracker_FormElementFactory::instance()->getUsedFormElementById($this->getFieldId());

        if ($field) {
            $purifier = Codendi_HTMLPurifier::instance();
            $content  = '<p>' . sprintf(
                dgettext('tuleap-tracker', 'The artifacts of this tracker will be described by the field %s.'),
                '<strong>' . $purifier->purify($field->getLabel()) . '</strong>'
            ) . '</p>';

            if (Tracker_FormElementFactory::instance()->getUsedFieldByIdAndType($this->tracker, $field->getId(), ['string', 'ref'])) {
                $warning = '<p class="alert alert-warning">' .
                    dgettext('tuleap-tracker', 'String fields are no longer supported for description semantic. Please update.') .
                    '</p>';
            }
        } else {
            $content = '<p>' . sprintf(
                dgettext(
                    'tuleap-tracker',
                    'The artifacts of this tracker do not have any %s description %s yet.'
                ),
                '<em>',
                '</em>'
            ) . '</p>';
        }

        return $warning . '<p>' .
            dgettext('tuleap-tracker', 'The description is a free-text account of the artifact.') . '</p>' .
            $content;
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
        $admin_presenter_builder = new \Tuleap\Tracker\Semantic\Description\AdminPresenterBuilder(Tracker_FormElementFactory::instance());

        echo $template_rendreder->renderToString(
            'semantics/admin-description',
            $admin_presenter_builder->build($this, $this->tracker, $this->getCSRFToken())
        );

        $semantic_manager->displaySemanticFooter($this, $tracker_manager);
    }

    public function process(
        Tracker_SemanticManager $semantic_manager,
        TrackerManager $tracker_manager,
        Codendi_Request $request,
        PFUser $current_user,
    ): void {
        if ($request->exist('update')) {
            $this->getCSRFToken()->check();
            if ($field = Tracker_FormElementFactory::instance()->getUsedFieldByIdAndType($this->tracker, $request->get('text_field_id'), ['text'])) {
                $this->text_field = $field;
                if ($this->save()) {
                    $GLOBALS['Response']->addFeedback('info', dgettext('tuleap-tracker', 'Semantic description updated'));
                    $GLOBALS['Response']->redirect($this->getUrl());
                } else {
                    $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker', 'Unable to save the description'));
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker', 'The field you submitted is not a text field'));
            }
        } elseif ($request->exist('delete')) {
            $this->getCSRFToken()->check();
            if ($this->delete()) {
                $GLOBALS['Response']->addFeedback('info', dgettext('tuleap-tracker', 'Semantic description unset'));
                $GLOBALS['Response']->redirect($this->getUrl());
            } else {
                $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker', 'Unable to save the description'));
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
        $dao = new Tracker_Semantic_DescriptionDao();
        return $dao->save($this->tracker->getId(), $this->getFieldId());
    }

    public function delete()
    {
        $dao = new Tracker_Semantic_DescriptionDao();
        return $dao->delete($this->tracker->getId());
    }

    protected static $_instances;
    /**
     * Load an instance of a Tracker_Semantic_Description
     *
     *
     * @return Tracker_Semantic_Description
     */
    public static function load(Tracker $tracker)
    {
        if (! isset(self::$_instances[$tracker->getId()])) {
            $field_id = null;
            $dao      = new Tracker_Semantic_DescriptionDao();
            if ($row = $dao->searchByTrackerId($tracker->getId())->getRow()) {
                $field_id = $row['field_id'];
            }
            $field = null;
            if ($field_id) {
                $field = Tracker_FormElementFactory::instance()->getFieldById($field_id);
            }
            self::$_instances[$tracker->getId()] = new Tracker_Semantic_Description($tracker, $field);
        }
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

    /**
     * Allows to inject a fake Semantic for tests. DO NOT USE IT IN PRODUCTION!
     */
    public static function setInstance(Tracker_Semantic_Description $description, Tracker $tracker)
    {
        self::$_instances[$tracker->getId()] = $description;
    }

    /**
     * Allows to clear Semantics for tests. DO NOT USE IT IN PRODUCTION!
     */
    public static function clearInstances()
    {
        self::$_instances = null;
    }
}
