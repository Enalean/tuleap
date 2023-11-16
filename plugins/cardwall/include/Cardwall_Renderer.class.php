<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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

use Tuleap\Cardwall\AccentColor\AccentColorBuilder;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\Date\RelativeDatesAssetsRetriever;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\MappingRegistry;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDecoratorRetriever;
use Tuleap\Tracker\Report\WidgetAdditionalButtonPresenter;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Cardwall_Renderer extends Tracker_Report_Renderer
{
    /** @var Plugin  */
    protected $plugin;

    /** @var Cardwall_OnTop_Config */
    private $config;

    /** @var Tracker_FormElement_Field_Selectbox  */
    private $field;

    /** @var Tracker_Report_Session */
    private $report_session;

    /**
     * Constructor
     *
     * @param Plugin $plugin         the parent cardwall plugin
     * @param int    $id             the id of the renderer
     * @param Report $report         the id of the report
     * @param string $name           the name of the renderer
     * @param string $description    the description of the renderer
     * @param int    $rank           the rank
     * @param Tracker_FormElement_Field_Selectbox    $field       the field
     */
    public function __construct(
        Plugin $plugin,
        Cardwall_OnTop_IConfig $config,
        $id,
        $report,
        $name,
        $description,
        $rank,
        ?Tracker_FormElement_Field_Selectbox $field = null,
    ) {
        parent::__construct($id, $report, $name, $description, $rank);
        $this->plugin = $plugin;
        $this->field  = $field;
        $this->config = $config;
    }

    /**
     * @return Tracker_FormElement_Field_Selectbox
     */
    public function getField()
    {
        return $this->field;
    }

    public function initiateSession()
    {
        $this->report_session = new Tracker_Report_Session($this->report->id);
        $field_id             = '';
        if ($this->field !== null) {
            $field_id = $this->field->getId();
        }
        $this->report_session->changeSessionNamespace("renderers");
        $this->report_session->set("{$this->id}.field_id", $field_id);
    }

    private function getFormElementFactory()
    {
        return Tracker_FormElementFactory::instance();
    }

    /**
     * Fetch content of the renderer
     *
     * @param array $matching_ids
     * @param HTTPRequest $request
     *
     * @return string
     */
    public function fetch($matching_ids, $request, $report_can_be_modified, PFUser $user)
    {
        $used_sb = $this->getFormElementFactory()->getUsedFormElementsByType($this->report->getTracker(), ['sb']);
        $form    = new Cardwall_Form($this->report->id, $this->id, $request->get('pv'), $this->field, $used_sb);
        return $this->fetchCards($matching_ids, $user, $form);
    }

    private function fetchCards($matching_ids, PFUser $user, $form = null)
    {
        $total_rows = $matching_ids['id'] ? substr_count($matching_ids['id'], ',') + 1 : 0;
        if (! $total_rows) {
            if (! $form) {
                $renderer = TemplateRendererFactory::build()->getRenderer(dirname(__FILE__) . '/../../../src/templates/dashboard');
                return $renderer->renderToString("widget-empty-content-svg", null);
            }

            return '<p>' . dgettext('tuleap-tracker', 'No artifact found.') . '</p>';
        }

        $artifact_ids = explode(',', $matching_ids['id']);
        $presenter    = $this->getPresenter($artifact_ids, $user, $form);

        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(__FILE__) . '/../templates');

        return $renderer->renderToString('renderer', $presenter);
    }

    /**
     * @return Cardwall_RendererPresenter
     */
    private function getPresenter(array $artifact_ids, PFUser $user, $form = null)
    {
        $redirect_parameter = 'cardwall[renderer][' . $this->report->id . ']=' . $this->id;

        if ($this->field === null) {
            $board = new Cardwall_Board([], new Cardwall_OnTop_Config_ColumnCollection(), new Cardwall_MappingCollection());
        } else {
            $field_provider     = new Cardwall_FieldProviders_CustomFieldRetriever($this->field);
            $column_preferences = new Cardwall_UserPreferences_Autostack_AutostackRenderer($user, $this->report);

            $filter = '';
            if (! $this->report->is_in_expert_mode) {
                foreach ($this->report->getCriteria() as $criterion) {
                    if ($criterion->field->id === $this->field->getId()) {
                        $filter = $this->field->getCriteriaValue($criterion);
                        break;
                    }
                }
            }

            if (is_array($filter)) {
                $columns = $this->config->getFilteredRendererColumns($this->field, $filter);
            } else {
                $columns = $this->config->getRendererColumns($this->field);
            }

            $column_autostack = new Cardwall_UserPreferences_UserPreferencesAutostackFactory();
            $column_autostack->setAutostack($columns, $column_preferences);
            $display_preferences      = new Cardwall_UserPreferences_UserPreferencesDisplayUser(Cardwall_UserPreferences_UserPreferencesDisplayUser::DISPLAY_AVATARS);
            $mapping_collection       = $this->config->getCardwallMappings(
                [$this->field->getId() => $this->field],
                $columns
            );
            $background_color_builder = new BackgroundColorBuilder(new BindDecoratorRetriever());
            $accent_color_builder     = new AccentColorBuilder(
                $this->getFormElementFactory(),
                new BindDecoratorRetriever()
            );
            $presenter_builder        = new Cardwall_CardInCellPresenterBuilder(
                new Cardwall_CardInCellPresenterFactory($field_provider, $mapping_collection),
                new Cardwall_CardFields(Tracker_FormElementFactory::instance()),
                $display_preferences,
                $user,
                $background_color_builder,
                $accent_color_builder
            );

            $swimline_factory = new Cardwall_SwimlineFactory($this->config, $field_provider);

            $board_builder = new Cardwall_RendererBoardBuilder($presenter_builder, Tracker_ArtifactFactory::instance(), $swimline_factory);
            $board         = $board_builder->getBoard($artifact_ids, $columns, $mapping_collection);
        }

        return new Cardwall_RendererPresenter($board, $redirect_parameter, $this->field, $form);
    }

    /*----- Implements below some abstract methods ----*/

    public function delete()
    {
    }

    public function getType()
    {
        return 'plugin_cardwall';
    }

    public function processRequest(TrackerManager $tracker_manager, $request, PFUser $current_user)
    {
        $renderer_parameters = $request->get('renderer_cardwall');
        $this->initiateSession();
        if ($renderer_parameters && is_array($renderer_parameters)) {
            //Update the field_id parameter
            if (isset($renderer_parameters['columns'])) {
                $new_columns_field = (int) $renderer_parameters['columns'];
                if (
                    $new_columns_field && (
                        ($this->field !== null && ($this->field->getId() !== $new_columns_field))
                    ||
                        ($this->field === null)
                    )
                ) {
                    $this->report_session->set("{$this->id}.field_id", $new_columns_field);
                    $this->report_session->setHasChanged();
                    $this->field = $this->getFormElementFactory()->getFieldById($new_columns_field);
                }
            }
        }
    }

    /**
     * Fetch content to be displayed in widget
     *
     * @return string
     */
    public function fetchWidget(PFUser $user)
    {
        $html = '';

        $additional_button_presenter = new WidgetAdditionalButtonPresenter(
            $this->report->getTracker(),
            false
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(__FILE__) . '/../templates');

        $html .= $renderer->renderToString('additional-button', $additional_button_presenter);

        $use_data_from_db = true;

        $html .= $this->fetchCards($this->report->getMatchingIds(null, $use_data_from_db), $user);
        $html .= $this->fetchWidgetGoToReport();

        return $html;
    }

    /**
     * Create a renderer - add in db
     *
     * @return bool true if success, false if failure
     */
    public function create()
    {
        $success = true;
        $rrf     = Tracker_Report_RendererFactory::instance();
        if ($renderer_id = $rrf->saveRenderer($this->report, $this->name, $this->description, $this->getType())) {
            //field_id
            $this->saveRendererProperties($renderer_id);
        }
        return $success;
    }

    /**
     * Update the renderer
     *
     * @return bool true if success, false if failure
     */
    public function update()
    {
        $success = true;
        if ($this->id > 0) {
            //field_id
            $this->saveRendererProperties($this->id);
        }
        return $success;
    }

    public function duplicate($from_renderer, $field_mapping, MappingRegistry $mapping_registry): void
    {
    }

    public function afterSaveObject(Tracker_Report_Renderer $renderer)
    {
        if ($renderer->getField() !== null) {
            $this->getDao()->save($this->id, $renderer->getField()->getId());
        }
    }

    /**
     * Save field_id in db
     *
     * @param int $renderer_id the id of the renderer
     */
    protected function saveRendererProperties($renderer_id)
    {
        if ($this->field !== null) {
            $this->getDao()->save($renderer_id, $this->field->getId());
        }
    }

    /**
     * Wrapper for Cardwall_RendererDao
     */
    public function getDao()
    {
        return new Cardwall_RendererDao();
    }

    /**
     * Transforms Tracker_Renderer into a SimpleXMLElement
     *
     * @param SimpleXMLElement $root the node to which the renderer is attached (passed by reference)
     * @param $formsMapping the form elements mapping
     */
    public function exportToXml(SimpleXMLElement $root, array $formsMapping)
    {
        parent::exportToXml($root, $formsMapping);
        if ($this->field !== null && ($mapping = (string) array_search($this->field->getId(), $formsMapping))) {
            $root->addAttribute('field_id', $mapping);
        }
    }

    public function getIcon()
    {
        return 'fa fa-table';
    }

    public function getJavascriptDependencies()
    {
        return [
            ['file' => RelativeDatesAssetsRetriever::retrieveAssetsUrl(), 'unique-name' => 'tlp-relative-dates'],
        ];
    }

    public function getStylesheetDependencies(): CssAssetCollection
    {
        $tracker_assets  = new IncludeAssets(
            __DIR__ . '/../../tracker/frontend-assets',
            '/assets/trackers'
        );
        $cardwall_assets = new IncludeAssets(
            __DIR__ . '/../frontend-assets/',
            '/assets/cardwall/'
        );
        return new CssAssetCollection([
            new CssAssetWithoutVariantDeclinaisons($tracker_assets, 'style-fp'),
            new CssAssetWithoutVariantDeclinaisons($cardwall_assets, 'flamingparrot-theme'),
        ]);
    }
}
