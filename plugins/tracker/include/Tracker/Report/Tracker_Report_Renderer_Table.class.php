<?php
/**
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

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\date\RelativeDatesAssetsRetriever;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\MappingRegistry;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\PossibleParentsRetriever;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\DisplayArtifactLinkEvent;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeSelectorPresenter;
use Tuleap\Tracker\Report\CSVExport\CSVFieldUsageChecker;
use Tuleap\Tracker\Report\Renderer\Table\GetExportOptionsMenuItemsEvent;
use Tuleap\Tracker\Report\Renderer\Table\ProcessExportEvent;
use Tuleap\Tracker\Report\Renderer\Table\Sort\SortWithIntegrityChecked;
use Tuleap\Tracker\Report\WidgetAdditionalButtonPresenter;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_Report_Renderer_Table extends Tracker_Report_Renderer implements Tracker_Report_Renderer_ArtifactLinkable
{
    public const EXPORT_LIGHT = 1;
    public const EXPORT_FULL  = 0;

    public $chunksz;
    public $multisort;
    /**
     * @var Tracker_Report_Session
     */
    private $report_session;

    /**
     * Constructor
     *
     * @param int $id the id of the renderer
     * @param Tracker_Report $report the id of the report
     * @param string $name the name of the renderer
     * @param string $description the description of the renderer
     * @param int $rank the rank
     * @param int $chunksz the size of the chunk (Browse X at once)
     * @param bool $multisort use multisort?
     */
    public function __construct($id, $report, $name, $description, $rank, $chunksz, $multisort)
    {
        parent::__construct($id, $report, $name, $description, $rank);
        $this->chunksz   = $chunksz;
        $this->multisort = $multisort;
    }

    public function initiateSession()
    {
        $this->report_session = new Tracker_Report_Session($this->report->id);
        $this->report_session->changeSessionNamespace("renderers");
        $this->report_session->set("{$this->id}.chunksz", $this->chunksz);
        $this->report_session->set("{$this->id}.multisort", $this->multisort);
    }

    /**
     * Delete the renderer
     */
    public function delete()
    {
        $this->getSortDao()->delete($this->id);
        $this->getColumnsDao()->delete($this->id);
        $this->getAggregatesDao()->deleteByRendererId($this->id);
    }

    protected $_sort;
    /**
     * @param array $sort
     */
    public function setSort($sort)
    {
        $this->_sort = $sort;
    }

    /**
     * Get field ids used to (multi)sort results
     * @return array [{'field_id' => 12, 'is_desc' => 0, 'rank' => 2}, [...]]
     */
    public function getSort($store_in_session = true)
    {
        $sort = null;
        if ($store_in_session) {
            if (isset($this->report_session)) {
                $sort = $this->report_session->get("{$this->id}.sort");
            }
        }

        if ($sort) {
                $ff = $this->report->getFormElementFactory();
            foreach ($sort as $field_id => $properties) {
                if ($properties) {
                    if ($field = $ff->getFormElementById($field_id)) {
                        if ($field->canBeUsedToSortReport() && $field->userCanRead()) {
                            $this->_sort[$field_id]          = [
                                'renderer_id ' => $this->id,
                                'field_id'    => $field_id,
                                'is_desc'     => $properties['is_desc'],
                                'rank'        => $properties['rank'],
                            ];
                            $this->_sort[$field_id]['field'] = $field;
                        }
                    }
                }
            }
        } elseif (! isset($this->report_session) || ! $this->report_session->hasChanged()) {
            if (! is_array($this->_sort)) {
                $ff          = $this->getFieldFactory();
                $this->_sort = [];
                foreach ($this->getSortDao()->searchByRendererId($this->id) as $row) {
                    if ($field = $ff->getUsedFormElementById($row['field_id'])) {
                        if ($field->canBeUsedToSortReport() && $field->userCanRead()) {
                            $this->_sort[$row['field_id']]          = $row;
                            $this->_sort[$row['field_id']]['field'] = $field;
                        }
                    }
                }
            }
            $sort = $this->_sort;
            if ($store_in_session && isset($this->report_session)) {
                foreach ($sort as $field_id => $properties) {
                    $this->report_session->set("{$this->id}.sort.{$field_id}.is_desc", $properties['is_desc']);
                    $this->report_session->set("{$this->id}.sort.{$field_id}.rank", $properties['rank']);
                }
            }
        } else {
            $this->_sort = [];
        }
        return $this->_sort;
    }

    /**
     * Adds sort values to database
     *
     * @param array $sort
     */
    public function saveSort($sort)
    {
        $dao = $this->getSortDao();
        if (is_array($sort)) {
            foreach ($sort as $key => $s) {
                $dao->create($this->id, $s['field']->id);
            }
        }
    }

    protected $_columns;
    /**
     * @param array $cols
     */
    public function setColumns($cols)
    {
        $this->_columns = $cols;
    }

    /**
     * Adds columns to database
     *
     * @param array $cols
     */
    public function saveColumns($cols)
    {
        $dao   = $this->getColumnsDao();
        $rank  = -1;
        $width = 0;

        foreach ($cols as $key => $col) {
            $rank++;

            $artlink_nature        = (isset($col['artlink_nature']) ? $col['artlink_nature'] : null);
            $artlink_nature_format = (isset($col['artlink_nature_format']) ? $col['artlink_nature_format'] : null);

            $dao->create($this->id, $col['field']->id, $width, $rank, $artlink_nature, $artlink_nature_format);
        }
    }

    /**
     * Get field ids and width used to display results
     * @return array  [{'field_id' => 12, 'width' => 33, 'rank' => 5}, [...]]
     */
    public function getColumns()
    {
        $session_renderer_table_columns = null;
        if (isset($this->report_session)) {
            $session_renderer_table_columns = $this->report_session->get("{$this->id}.columns");
        }

        if ($session_renderer_table_columns) {
            $columns        = $session_renderer_table_columns;
            $ff             = $this->report->getFormElementFactory();
            $this->_columns = [];
            foreach ($columns as $key => $column) {
                $field_id = $this->fallbackFieldId($key, $column);
                if ($formElement = $ff->getUsedFormElementFieldById($field_id)) {
                    if ($formElement->userCanRead()) {
                        $artlink_nature        = null;
                        $artlink_nature_format = null;
                        if (isset($column['artlink_nature'])) {
                            $artlink_nature = $column['artlink_nature'];
                        }
                        if (isset($column['artlink_nature_format'])) {
                            $artlink_nature_format = $column['artlink_nature_format'];
                        }
                        $this->_columns[$key] = [
                            'field'                 => $formElement,
                            'field_id'              => $formElement->getId(),
                            'width'                 => $column['width'],
                            'rank'                  => $column['rank'],
                            'artlink_nature'        => $artlink_nature,
                            'artlink_nature_format' => $artlink_nature_format,
                        ];
                    }
                }
            }
        } else {
            if (empty($this->_columns)) {
                $this->_columns = $this->getColumnsFromDb();
            }
        }

        return $this->_columns;
    }

    protected $_aggregates;
    /**
     * @param array $aggs
     */
    public function setAggregates($aggs)
    {
        $this->_aggregates = $aggs;
    }

    /**
     * Adds aggregates to database
     *
     * @param array $aggs
     */
    public function saveAggregates($aggs)
    {
        $dao = $this->getAggregatesDao();
        foreach ($aggs as $field_id => $aggregates) {
            foreach ($aggregates as $aggregate) {
                $dao->create($this->id, $field_id, $aggregate);
            }
        }
    }

    public function getAggregates()
    {
        $session_renderer_table_functions = null;
        if (isset($this->report_session)) {
            $session_renderer_table_functions = &$this->report_session->get("{$this->id}.aggregates");
        }
        if ($session_renderer_table_functions) {
            $aggregates = $session_renderer_table_functions;
            $ff         = $this->report->getFormElementFactory();
            foreach ($aggregates as $field_id => $aggregates) {
                if ($formElement = $ff->getFormElementById($field_id)) {
                    if ($formElement->userCanRead()) {
                        $this->_aggregates[$field_id] = $aggregates;
                    }
                }
            }
        } else {
            if (empty($this->_aggregates)) {
                $ff                = $this->getFieldFactory();
                $this->_aggregates = [];
                foreach ($this->getAggregatesDao()->searchByRendererId($this->id) as $row) {
                    if ($field = $ff->getUsedFormElementById($row['field_id'])) {
                        if ($field->userCanRead()) {
                            if (! isset($this->_aggregates[$row['field_id']])) {
                                $this->_aggregates[$row['field_id']] = [];
                            }
                            $this->_aggregates[$row['field_id']][] = $row;
                        }
                    }
                }
            }
            if (isset($this->report_session)) {
                $aggregates = $this->_aggregates;
                foreach ($aggregates as $field_id => $agg) {
                    $this->report_session->set("{$this->id}.aggregates.{$field_id}", $agg);
                }
            }
        }
        return $this->_aggregates;
    }

    public function storeColumnsInSession()
    {
        $columns = $this->_columns;
        foreach ($columns as $key => $column) {
            $field_id = $this->fallbackFieldId($key, $column);
            $this->report_session->set("{$this->id}.columns.{$key}.field_id", $field_id);
            $this->report_session->set("{$this->id}.columns.{$key}.width", isset($column['width']) ? $column['width'] : 0);
            $this->report_session->set("{$this->id}.columns.{$key}.rank", isset($column['rank']) ? $column['rank'] : 0);
            $this->report_session->set(
                "{$this->id}.columns.{$key}.artlink_nature",
                isset($column['artlink_nature']) ? $column['artlink_nature'] : null
            );
            $this->report_session->set(
                "{$this->id}.columns.{$key}.artlink_nature_format",
                isset($column['artlink_nature_format']) ? $column['artlink_nature_format'] : null
            );
        }
    }

    /**
     * Before, there was no field_id stored in session as the index of the column was the field id.
     * Now that we can have '1234' and '1234_fixed_in' as indexes, we need to store it in the session.
     *
     * As we don't want to break existing session once the Tuleap server is upgraded to the new version,
     * if we don't find any field_id information then we fallback on the key used as index.
     */
    private function fallbackFieldId($key, $column)
    {
        if (isset($column['field_id'])) {
            return $column['field_id'];
        }

        return $key;
    }

     /**
     * Get field ids and width used to display results
     * @return array  [{'field_id' => 12, 'width' => 33, 'rank' => 5}, [...]]
     */
    public function getColumnsFromDb()
    {
        $ff             = $this->getFieldFactory();
        $this->_columns = [];
        foreach ($this->getColumnsDao()->searchByRendererId($this->id) as $row) {
            if ($field = $ff->getUsedFormElementFieldById($row['field_id'])) {
                if ($field->userCanRead()) {
                    $key = $row['field_id'];
                    if (! is_null($row['artlink_nature'])) {
                        $key .= '_' . $row['artlink_nature'];
                    }
                    $this->_columns[$key]          = $row;
                    $this->_columns[$key]['field'] = $field;
                }
            }
        }
        return $this->_columns;
    }

    protected function getSortDao()
    {
        return new Tracker_Report_Renderer_Table_SortDao();
    }

    protected function getColumnsDao()
    {
        return new Tracker_Report_Renderer_Table_ColumnsDao();
    }

    protected function getAggregatesDao()
    {
        return new Tracker_Report_Renderer_Table_FunctionsAggregatesDao();
    }

    /**
     * Fetch content of the renderer
     * @return string
     */
    public function fetch($matching_ids, $request, $report_can_be_modified, PFUser $user)
    {
        $html       = '';
        $total_rows = $matching_ids['id'] ? substr_count($matching_ids['id'], ',') + 1 : 0;
        $offset     = (int) $request->get('offset');
        if ($offset < 0) {
            $offset = 0;
        }
        if ($request->get('renderer')) {
            $renderer_data = $request->get('renderer');
            if (isset($renderer_data[$this->id]) && isset($renderer_data[$this->id]['chunksz'])) {
                $this->report_session->set("{$this->id}.chunksz", $renderer_data[$this->id]['chunksz']);
                $this->report_session->setHasChanged();
                $this->chunksz = $renderer_data[$this->id]['chunksz'];
            }
        }

        $extracolumn = self::EXTRACOLUMN_MASSCHANGE;
        if ((int) $request->get('link-artifact-id')) {
            $extracolumn = self::EXTRACOLUMN_LINK;
        }

        if ($report_can_be_modified) {
            $with_sort_links = true;
        } else {
            $with_sort_links = false;
        }
        $only_one_column  = null;
        $use_data_from_db = false;
        $aggregates       = false;
        $store_in_session = true;

        $columns = $this->getTableColumns($only_one_column, $use_data_from_db);

        $limited_matching_ids = $this->getLimitedResult($store_in_session, $matching_ids, $offset, $aggregates);
        $queries              = $this->buildOrderedQuery(
            $limited_matching_ids,
            $columns,
            $aggregates,
            $store_in_session,
        );

        $html .= $this->fetchHeader($report_can_be_modified, $user, $total_rows, $queries);
        $html .= $this->fetchTHead($extracolumn, $only_one_column, $with_sort_links);
        $html .= $this->fetchTBody($matching_ids, $total_rows, $queries, $columns, $extracolumn);

        //Display next/previous
        $html .= $this->fetchNextPrevious($total_rows, $offset, $report_can_be_modified, (int) $request->get('link-artifact-id'));

        //Display masschange controls
        if ((int) $request->get('link-artifact-id')) {
            //TODO
        } else {
            $html .= $this->fetchMassChange($matching_ids, $total_rows, $offset);
        }

        return $html;
    }

    private function fetchHeader($report_can_be_modified, PFUser $user, $total_rows, array $queries)
    {
        $html = '';

        $html .= $this->fetchViewButtons($report_can_be_modified, $user);

        if ($this->sortHasUsedField() && ! $this->columnsCanBeTechnicallySorted($queries)) {
            $html .= '<div class="tracker_report_renderer_table_sort_warning">
                <ul class="feedback_warning">
                    <li>' . dgettext('tuleap-tracker', 'You have too many columns, the sort won\'t work. Please remove some columns (and refresh the page) to be able to sort.') . '</li>
                </ul>
            </div>';
        }

        //Display sort info
        $html .= '<div class="tracker_report_renderer_table_information">';
        if ($report_can_be_modified) {
            $html .= $this->fetchSort();
        }

        $html .= $this->fetchMatchingNumber($total_rows);
        $html .= '</div>';

        return $html;
    }

    /**
     * Fetch content of the renderer
     * @return string|string[]
     */
    public function fetchAsArtifactLink(
        $matching_ids,
        $field_id,
        $read_only,
        $prefill_removed_values,
        $prefill_types,
        $is_reverse,
        $only_rows = false,
        $from_aid = null,
    ) {
        $html             = '';
        $total_rows       = $matching_ids['id'] ? substr_count($matching_ids['id'], ',') + 1 : 0;
        $offset           = 0;
        $use_data_from_db = true;
        $extracolumn      = $read_only ? self::NO_EXTRACOLUMN : self::EXTRACOLUMN_UNLINK;
        $with_sort_links  = false;
        $only_one_column  = null;
        $store_in_session = true;
        $head             = '';

        //Display the head of the table
        $is_type_col = isset($matching_ids['type']);
        $suffix      = '_' . $field_id . '_' . $this->report->id . '_' . $this->id;
        if ($is_reverse) {
            $suffix .= '_reverse';
        }
        $head .= $this->fetchTHead($extracolumn, $only_one_column, $with_sort_links, $use_data_from_db, $suffix, '', $is_type_col);
        if (! $only_rows) {
            $html .= $head;
        }
        //Display the body of the table
        $aggregates = false;

        $columns = $this->getTableColumns($only_one_column, $use_data_from_db);
        $queries = $this->buildOrderedQuery($matching_ids, $columns, $aggregates);

        $html .= $this->fetchTBody(
            $matching_ids,
            $total_rows,
            $queries,
            $columns,
            $extracolumn,
            $only_one_column,
            $use_data_from_db,
            $field_id,
            $prefill_removed_values,
            $prefill_types,
            $only_rows,
            $read_only,
            $from_aid
        );

        if (! $only_rows) {
            $html .= $this->fetchArtifactLinkGoToTracker();
        }

        if ($only_rows) {
            return ['head' => $head, 'rows' => $html];
        }
        return $html;
    }

    /**
     * Get the item of the menu options.
     *
     * If no items is returned, the menu won't be displayed.
     *
     * @return array of 'item_key' => {url: '', icon: '', label: ''}
     */
    public function getOptionsMenuItems(PFUser $current_user): array
    {
        if ($current_user->isAnonymous()) {
            return parent::getOptionsMenuItems($current_user);
        }

        $my_items            = ['export' => ''];
        $my_items['export'] .= '<div class="btn-group">';
        $my_items['export'] .= '<a class="btn btn-mini dropdown-toggle" data-toggle="dropdown" href="#">';
        $my_items['export'] .= '<i class="fa fa-download"></i> ';
        $my_items['export'] .= dgettext('tuleap-tracker', 'Export');
        $my_items['export'] .= ' <span class="caret"></span>';
        $my_items['export'] .= '</a>';
        $my_items['export'] .= '<ul class="dropdown-menu" role="menu">';
        $my_items['export'] .= '<li class="almost-tlp-menu-title">';
        $my_items['export'] .= dgettext('tuleap-tracker', 'CSV');
        $my_items['export'] .= '</li>';
        $my_items['export'] .= '<li>';
        $my_items['export'] .= '<a href="' . $this->getExportResultURL(self::EXPORT_LIGHT) . '">';
        $my_items['export'] .= dgettext('tuleap-tracker', 'Export all report columns');
        $my_items['export'] .= '</a>';
        $my_items['export'] .= '</li>';
        $my_items['export'] .= '<li>';
        $my_items['export'] .= '<a href="' . $this->getExportResultURL(self::EXPORT_FULL) . '">';
        $my_items['export'] .= dgettext('tuleap-tracker', 'Export all columns');
        $my_items['export'] .= '</a>';
        $my_items['export'] .= '</li>';

        $event = new GetExportOptionsMenuItemsEvent($this);
        EventManager::instance()->processEvent($event);
        $my_items['export'] .= $event->getItems();

        $my_items['export'] .= '</ul>';
        $my_items['export'] .= '</div>';
        $my_items['export'] .= $event->getAdditionalContentThatGoesOutsideOfTheMenu();

        foreach ($event->getJavascriptAssets() as $javascript_asset) {
            $GLOBALS['HTML']->addJavascriptAsset($javascript_asset);
        }

        return $my_items + parent::getOptionsMenuItems($current_user);
    }

    private function getExportResultURL($export_only_displayed_fields)
    {
        return TRACKER_BASE_URL . '/?' . http_build_query(
            [
                'report'         => $this->report->id,
                'renderer'       => $this->id,
                'func'           => 'renderer',
                'renderer_table' => [
                    'export'                       => 1,
                    'export_only_displayed_fields' => $export_only_displayed_fields,
                ],
            ]
        );
    }

    private function fetchFormStart($id = '', $func = 'renderer')
    {
        $html  = '';
        $html .= '<form method="POST" action="" id="' . $id . '" class="form-inline">';
        $html .= '<input type="hidden" name="report" value="' . $this->report->id . '" />';
        $html .= '<input type="hidden" name="renderer" value="' . $this->id . '" />';
        $html .= '<input type="hidden" name="func" value="' . $func . '" />';
        return $html;
    }

    /**
     * Fetch content to be displayed in widget
     */
    public function fetchWidget(PFUser $user)
    {
        $html                   = '';
        $use_data_from_db       = true;
        $store_in_session       = false;
        $matching_ids           = $this->report->getMatchingIds(null, $use_data_from_db);
        $total_rows             = $matching_ids['id'] ? substr_count($matching_ids['id'], ',') + 1 : 0;
        $offset                 = 0;
        $extracolumn            = self::NO_EXTRACOLUMN;
        $with_sort_links        = false;
        $only_one_column        = null;
        $artifactlink_field_id  = null;
        $prefill_removed_values = null;
        $prefill_types          = [];
        $only_rows              = false;
        $read_only              = true;
        $id_suffix              = '';
        //Display the head of the table
        $html .= $this->fetchAdditionnalButton($this->report->getTracker());
        $html .= $this->fetchTHead($extracolumn, $only_one_column, $with_sort_links, $use_data_from_db, $id_suffix, $store_in_session);

        //Display the body of the table
        $aggregates = false;

        $columns              = $this->getTableColumns($only_one_column, $use_data_from_db);
        $limited_matching_ids = $this->getLimitedResult($store_in_session, $matching_ids, $offset, $aggregates);
        $queries              = $this->buildOrderedQuery($limited_matching_ids, $columns, $aggregates);

        $html .= $this->fetchTBody(
            $matching_ids,
            $total_rows,
            $queries,
            $columns,
            $extracolumn,
            $only_one_column,
            $use_data_from_db,
            $artifactlink_field_id,
            $prefill_removed_values,
            $prefill_types,
            $only_rows,
            $read_only
        );

        //Display range
        if ($total_rows > 0) {
            $offset_last = min($offset + $this->chunksz - 1, $total_rows - 1);
            $html       .= '<div class="tracker_report_table_pager">';
            $html       .= $this->fetchRange($offset + 1, $offset_last + 1, $total_rows, $this->fetchWidgetGoToReport());
            $html       .= '</div>';
        } else {
            $html .= $this->fetchWidgetGoToReport();
        }

        return $html;
    }

    private function fetchMatchingNumber($total_rows)
    {
        $html = '<p>' . sprintf(dgettext('tuleap-tracker', 'Matching artifacts: <strong>%1$s</strong>'), $total_rows) . '</p>';
        return $html;
    }

    private function fetchSort(): string
    {
        $purifier     = Codendi_HTMLPurifier::instance();
        $html         = '<div class="tracker_report_table_sortby_panel">';
        $sort_columns = SortWithIntegrityChecked::getSortOnUsedFields($this->getSort());
        if (count($sort_columns) > 0) {
            $html .= dgettext('tuleap-tracker', 'Sort by:');
            $html .= ' ';
            $sort  = [];
            foreach ($sort_columns as $row) {
                $sort[] = '<a id="tracker_report_table_sort_by_' . $purifier->purify($row['field_id']) . '"
                              href="?' .
                    $purifier->purify(http_build_query([
                        'report' => $this->report->id,
                        'renderer' => $this->id,
                        'func' => 'renderer',
                        'renderer_table[sort_by]' => $row['field_id'],
                    ])) . '">' .
                    $purifier->purify($row['field']->getLabel()) .
                    $this->getSortIcon($row['is_desc']) .
                    '</a>';
            }
            $html .= implode(' <i class="fa fa-angle-right"></i> ', $sort);
        }
        $html .= '</div>';
        return $html;
    }

    private function fetchAddColumn()
    {
        $add_columns_presenter = new Templating_Presenter_ButtonDropdownsMini(
            'tracker_report_add_columns_dropdown',
            dgettext('tuleap-tracker', 'Columns'),
            $this->report->getFieldsAsDropdownOptions('tracker_report_add_column', $this->getColumns(), Tracker_Report::TYPE_TABLE)
        );
        $add_columns_presenter->setIcon('fa-solid fa-eye-slash');

        return $this->report->getTemplateRenderer()->renderToString('button_dropdowns', $add_columns_presenter);
    }

    private function fetchRange($from, $to, $total_rows, $additionnal_html)
    {
        $html  = '';
        $html .= '<span class="tracker_report_table_pager_range">';
        $html .= dgettext('tuleap-tracker', 'Items');
        $html .= ' <strong>' . $from . '</strong> – <strong>' . $to . '</strong>';
        $html .= ' ' . dgettext('tuleap-tracker', 'of') . ' <strong>' . $total_rows . '</strong>';
        $html .= $additionnal_html;
        $html .= '</span>';

        return $html;
    }

    private function fetchNextPrevious($total_rows, $offset, $report_can_be_modified, $link_artifact_id = null)
    {
        $html = '';
        if ($total_rows) {
            $parameters = [
                'report'   => $this->report->id,
                'renderer' => $this->id,
            ];
            if ($link_artifact_id) {
                $parameters['link-artifact-id'] = (int) $link_artifact_id;
                $parameters['only-renderer']    = 1;
            }
            //offset should be the last parameter to ease the concat later
            $parameters['offset'] = '';
            $url                  = '?' . http_build_query($parameters);

            $chunk  = '<span class="tracker_report_table_pager_chunk">';
            $chunk .= dgettext('tuleap-tracker', 'Items per page :');
            $chunk .= ' ';
            if ($report_can_be_modified) {
                $chunk .= '<div class="input-append">';
                $chunk .= '<input id="renderer_table_chunksz_input" type="text" name="renderer_table[chunksz]" size="1" maxlength="5" value="' . (int) $this->chunksz . '" />';
                $chunk .= '<button type="submit" class="btn btn-small">Ok</button> ';
                $chunk .= '</div> ';
            } else {
                $chunk .= (int) $this->chunksz;
            }
            $chunk .= '</span>';

            $html .= $this->fetchFormStart('tracker_report_table_next_previous_form');
            $html .= '<div class="tracker_report_table_pager">';
            if ($total_rows < $this->chunksz) {
                $html .= $this->fetchRange(1, $total_rows, $total_rows, $chunk);
            } else {
                if ($offset > 0) {
                    $html .= $this->getPagerButton($url . 0, 'begin');
                    $html .= $this->getPagerButton($url . ($offset - $this->chunksz), 'prev');
                } else {
                    $html .= $this->getDisabledPagerButton('begin');
                    $html .= $this->getDisabledPagerButton('prev');
                }

                $offset_last = min($offset + $this->chunksz - 1, $total_rows - 1);
                $html       .= $this->fetchRange($offset + 1, $offset_last + 1, $total_rows, $chunk);

                if (($offset + $this->chunksz) < $total_rows) {
                    if ($this->chunksz > 0) {
                        $offset_end = ($total_rows - ($total_rows % $this->chunksz));
                    } else {
                        $offset_end = PHP_INT_MAX; //weird! it will take many steps to reach the last page if the user is browsing 0 artifacts at once
                    }
                    if ($offset_end >= $total_rows) {
                        $offset_end -= $this->chunksz;
                    }
                    $html .= $this->getPagerButton($url . ($offset + $this->chunksz), 'next');
                    $html .= $this->getPagerButton($url . $offset_end, 'end');
                } else {
                    $html .= $this->getDisabledPagerButton('next');
                    $html .= $this->getDisabledPagerButton('end');
                }
            }
            $html .= '</div>';
            $html .= '</form>';
        }
        return $html;
    }

    private function getDisabledPagerButton($direction)
    {
        $icons = [
            'begin' => 'fa fa-angle-double-left',
            'end'   => 'fa fa-angle-double-right',
            'prev'  => 'fa fa-angle-left',
            'next'  => 'fa fa-angle-right',
        ];
        $title = [
            'begin' => $GLOBALS['Language']->getText('global', 'begin'),
            'end'   => $GLOBALS['Language']->getText('global', 'end'),
            'prev'  => $GLOBALS['Language']->getText('global', 'prev'),
            'next'  => $GLOBALS['Language']->getText('global', 'next'),
        ];
        $html  = '';
        $html .= '<button
            class="btn disabled"
            type="button"
            title="' . $title[$direction] . '"
            >';
        $html .= '<i class="' . $icons[$direction] . '"></i>';
        $html .= '</button> ';

        return $html;
    }

    private function getPagerButton($url, $direction)
    {
        $icons = [
            'begin' => 'fa fa-angle-double-left',
            'end'   => 'fa fa-angle-double-right',
            'prev'  => 'fa fa-angle-left',
            'next'  => 'fa fa-angle-right',
        ];
        $title = [
            'begin' => $GLOBALS['Language']->getText('global', 'begin'),
            'end'   => $GLOBALS['Language']->getText('global', 'end'),
            'prev'  => $GLOBALS['Language']->getText('global', 'prev'),
            'next'  => $GLOBALS['Language']->getText('global', 'next'),
        ];
        $html  = '';
        $html .= '<a
            href="' . $url . '"
            class="btn"
            title="' . $title[$direction] . '"
            >';
        $html .= '<i class="' . $icons[$direction] . '"></i>';
        $html .= '</a> ';

        return $html;
    }

    protected function reorderColumnsByRank($columns)
    {
        $array_rank = [];
        foreach ($columns as $key => $properties) {
            $array_rank[$key] = $properties['rank'];
        }
        asort($array_rank);
        $columns_sort = [];
        foreach ($array_rank as $key => $rank) {
            $columns_sort[$key] = $columns[$key];
        }
        return $columns_sort;
    }

    public const NO_EXTRACOLUMN         = 0;
    public const EXTRACOLUMN_MASSCHANGE = 1;
    public const EXTRACOLUMN_LINK       = 2;
    public const EXTRACOLUMN_UNLINK     = 3;

    private function fetchTHead($extracolumn = 1, $only_one_column = null, $with_sort_links = true, $use_data_from_db = false, $id_suffix = '', $store_in_session = true, $is_type_col = false)
    {
        $current_user = UserManager::instance()->getCurrentUser();

        $html  = '';
        $html .= '<table';
        if (! $only_one_column) {
            $html .= ' id="tracker_report_table' . $id_suffix . '"  width="100%" data-test="artifact-report-table"';
        }

        $classnames = '';
        if ($with_sort_links && ! $current_user->isAnonymous()) {
            $classnames .= ' reorderable resizable';
        }
        $html .= ' class="tracker_report_table table tlp-table ' . $classnames . '"';

        $html .= '>';

        $html .= '<thead class="table-sticky-header">';

        $html .= '<tr>';

        if ($extracolumn) {
            $display_extracolumn = true;
            $classname           = 'tracker_report_table_';
            $content             = '&nbsp';
            if ($extracolumn === self::EXTRACOLUMN_MASSCHANGE && $this->report->getTracker()->userIsAdmin($current_user)) {
                $classname .= 'masschange';
            } elseif ($extracolumn === self::EXTRACOLUMN_LINK) {
                $classname .= 'link';
            } elseif ($extracolumn === self::EXTRACOLUMN_UNLINK) {
                $classname .= 'unlink';
                $content    = '<input type="checkbox" disabled title="' . dgettext('tuleap-tracker', 'Mark all links to be removed') . '" class="tracker-artifact-link-mass-unlink">';
            } else {
                $display_extracolumn = false;
            }

            if ($display_extracolumn) {
                $html .= '<th class="' . $classname . '">' . $content . '</th>';
            }
        }

        //the link to the artifact
        if (! $only_one_column) {
            $html .= '<th></th>';
        }

        $ff  = $this->getFieldFactory();
        $url = '?' . http_build_query([
            'report'                  => $this->report->id,
            'renderer'                => $this->id,
            'func'                    => 'renderer',
            'renderer_table[sort_by]' => '',
        ]);
        if ($use_data_from_db) {
            $all_columns = $this->reorderColumnsByRank($this->getColumnsFromDb());
        } else {
            $all_columns = $this->reorderColumnsByRank($this->getColumns());
        }
        if ($only_one_column) {
            if (isset($all_columns[$only_one_column])) {
                $columns = [$only_one_column => $all_columns[$only_one_column]];
            } else {
                $columns = [$only_one_column => [
                    'width' => 0,
                    'field' => $ff->getUsedFormElementById($only_one_column),
                ],
                ];
            }
        } else {
            $columns = $all_columns;
        }
        $sort_columns = SortWithIntegrityChecked::getSort($this->getSort($store_in_session));

        $purifier               = Codendi_HTMLPurifier::instance();
        $type_presenter_factory = $this->getTypePresenterFactory();
        foreach ($columns as $key => $column) {
            if ($column['width']) {
                $width = 'width="' . $purifier->purify($column['width'] . '%') . '"';
            } else {
                $width = '';
            }
            if (! empty($column['field']) && $column['field']->isUsed()) {
                $data_type        = '';
                $data_type_format = '';
                if (isset($column['artlink_nature'])) {
                    $data_type = 'data-field-artlink-type="' . $purifier->purify($column['artlink_nature']) . '"';
                }
                if (isset($column['artlink_nature_format'])) {
                    $data_type_format = 'data-field-artlink-type-format="' . $purifier->purify($column['artlink_nature_format']) . '"';
                }
                $html .= '<th class="tracker_report_table_column"
                    id="tracker_report_table_column_' . $purifier->purify($key) . '"
                    data-column-id="' . $purifier->purify($key) . '"
                    data-field-id="' . $purifier->purify($column['field']->id) . '"
                    ' . $data_type . '
                    ' . $data_type_format . '
                    ' . $width . '>';

                $field_label = $column['field']->getLabel();
                if (isset($column['artlink_nature'])) {
                    $type = $type_presenter_factory->getFromShortname($column['artlink_nature']);
                    if ($type) {
                        $type_label = $type->forward_label;
                        if (! $type_label) {
                            $type_label = dgettext('tuleap-tracker', 'No type');
                        }
                        $field_label .= $purifier->purify(" ($type_label)");
                    }
                }
                $label = $purifier->purify($field_label);

                if ($with_sort_links) {
                    $sort_url = $url . $column['field']->id;

                    $html .= '<table width="100%" border="0" cellpadding="0" cellspacing="0"><tbody><tr>';

                    if (! $current_user->isAnonymous()) {
                        $html .= '<td class="tracker_report_table_column_grip">&nbsp;&nbsp;</td>';
                    }

                    $html .= '<td class="tracker_report_table_column_title">';
                    if (! isset($column['artlink_nature']) && $column['field']->canBeUsedToSortReport()) {
                        $html .= '<a href="' . $purifier->purify($sort_url) . '">';
                        $html .= $label;
                        $html .= '</a>';
                    } else {
                        $html .= $label;
                    }
                    $html .= '</td>';

                    if (! isset($column['artlink_nature']) && isset($sort_columns[$key])) {
                        $html .= '<td class="tracker_report_table_column_caret">';
                        if ($column['field']->canBeUsedToSortReport()) {
                            $html .= '<a href="' . $purifier->purify($sort_url) . '">';
                            $html .= $this->getSortIcon($sort_columns[$column['field']->getId()]['is_desc']);
                            $html .= '</a>';
                        } else {
                            $warning_message = dgettext(
                                'tuleap-tracker',
                                'The report was sorted against this column. This column can not be used to sort a report, the sort has been ignored. Please choose another column.'
                            );
                            $html           .= '<i class="fa fa-exclamation-triangle" title="' . $warning_message . '"></i>';
                        }
                        $html .= '</td>';
                    }

                    if (isset($column['artlink_nature']) && ! $current_user->isAnonymous()) {
                        $column_editor_popover_placement = 'bottom';

                        if (end($columns) === $column) {
                            $column_editor_popover_placement = 'left';
                        }

                        $html .= '<td class="tracker_report_table_column_type_editor">';
                        $html .= '<a href="#" class="type-column-editor" data-placement="' . $column_editor_popover_placement . '"><i class="fa fa-cog"></i></a>';
                        $html .= '</td>';
                    }

                    $html .= '</tr></tbody></table>';
                } else {
                    $html .= $label;
                }
                $html .= '</th>';
            }
        }
        if ($is_type_col) {
            $type_label = dgettext('tuleap-tracker', 'Type');
            $html      .= "<th>$type_label</th>";
        }
        $html .= '</tr>';
        $html .= '</thead>';
        return $html;
    }

    private function fetchAdditionnalButton()
    {
        $is_a_table_renderer = true;

        $html = $this->getTemplateRenderer()->renderToString(
            'widget-additionnal-button',
            new WidgetAdditionalButtonPresenter(
                $this->report->getTracker(),
                $is_a_table_renderer
            )
        );

        return $html;
    }

    private function getTemplateRenderer()
    {
        return TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR . '/report');
    }

    public function getTableColumns($only_one_column, $use_data_from_db, $store_in_session = true)
    {
        $columns = [];
        if ($use_data_from_db) {
            $all_columns = $this->reorderColumnsByRank($this->getColumnsFromDb());
        } else {
            $all_columns = $this->reorderColumnsByRank($this->getColumns());
        }
        if ($only_one_column) {
            if (isset($all_columns[$only_one_column])) {
                $columns = [$only_one_column => $all_columns[$only_one_column]];
            } else {
                $columns = [$only_one_column => [
                    'width' => 0,
                    'field' => $this->getFieldFactory()->getUsedFormElementFieldById($only_one_column),
                ],
                ];
            }
        } else {
            $columns = $all_columns;
        }
        return $columns;
    }

    /**
     * Display the body of the table
     *
     * @param array $matching_ids           The matching ids to display array('id' => '"1,4,8,10", 'last_matching_ids' => "123,145,178,190")
     * @param int   $total_rows             The number of total rows (pagination powwwa)
     * @param int   $extracolumn            Need for an extracolumn? NO_EXTRACOLUMN | EXTRACOLUMN_MASSCHANGE | EXTRACOLUMN_LINK | EXTRACOLUMN_UNLINK. Default is EXTRACOLUMN_MASSCHANGE.
     * @param int   $only_one_column        The column (field_id) to display. null if all columns are needed. Default is null
     * @param bool  $use_data_from_db       true if we need to retrieve data from the db instead of the session. Default is false.
     * @param int   $artifactlink_field_id  The artifactlink field id. Needed to display report in ArtifactLink field. Default is null
     * @param array $prefill_removed_values Array of artifact_id to pre-check. array(123 => X, 345 => X, ...). Default is null
     * @param bool  $only_rows              Display only rows, no aggregates or stuff like that. Default is false.
     * @param bool  $read_only              Display the table in read only mode. Default is false.
     *
     * @return string html
     */
    private function fetchTBody(
        $matching_ids,
        $total_rows,
        array $queries,
        array $columns,
        $extracolumn = 1,
        $only_one_column = null,
        $use_data_from_db = false,
        $artifactlink_field_id = null,
        $prefill_removed_values = null,
        $prefill_types = [],
        $only_rows = false,
        $read_only = false,
        $from_aid = null,
    ) {
        $html = '';
        if (! $only_rows) {
            $html                .= "\n<!-- table renderer body -->\n";
            $html                .= '<tbody>';
            $additional_classname = '';
        } else {
            $additional_classname = 'additional';
        }
        if ($total_rows) {
            $dao     = new DataAccessObject();
            $results = [];
            foreach ($queries as $sql) {
                $results[] = $dao->retrieve($sql);
            }
            // test if first result is valid (if yes, we consider that others are valid too)
            if (! empty($results[0])) {
                $current_user                 = UserManager::instance()->getCurrentUser();
                $artifact_factory             = Tracker_ArtifactFactory::instance();
                $is_parent_selector_displayed = false;
                if ($from_aid) {
                    $artifact = $artifact_factory->getArtifactById((int) $from_aid);
                    if ($artifact && $artifact->getParentWithoutPermissionChecking() === null) {
                        $retriever = new PossibleParentsRetriever($artifact_factory, EventManager::instance());

                        $possible_parents_selector = $retriever->getPossibleArtifactParents(
                            $artifact->getTracker(),
                            $current_user,
                            0,
                            0,
                            false,
                        );

                        $is_parent_selector_displayed = $possible_parents_selector->isSelectorDisplayed();
                    }
                }

                $renderer = TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR);
                $purifier = Codendi_HTMLPurifier::instance();
                //extract the first results
                $first_result = array_shift($results);
                //loop through it
                foreach ($first_result as $row) { //id, f1, f2
                    //merge the row with the other results
                    foreach ($results as $result) {
                        if ($result === false) {
                            continue;
                        }
                        //[id, f1, f2] + [id, f3, f4]
                        $row = array_merge($row, $result->getRow());
                        //row == id, f1, f2, f3, f4...
                    }
                    $html .= '<tr class="' . $additional_classname . '" data-test="tracker-report-table-results-artifact">';

                    $artifact_id                   = $row['id'];
                    $artifact_link_can_be_modified = true;
                    if (isset($matching_ids['type'][$artifact_id])) {
                        $type = $matching_ids['type'][$artifact_id];
                        if ($type instanceof TypePresenter) {
                            $event = EventManager::instance()->dispatch(
                                new DisplayArtifactLinkEvent($type)
                            );

                            $artifact_link_can_be_modified = $event->canLinkBeModified();
                        }
                    }

                    if ($extracolumn) {
                        $display_extracolumn = true;
                        $checked             = '';
                        $classname           = 'tracker_report_table_';
                        $name                = '';
                        if ($extracolumn === self::EXTRACOLUMN_MASSCHANGE && $this->report->getTracker()->userIsAdmin($current_user)) {
                            $classname .= 'masschange';
                            $name       = 'masschange_aids';
                        } elseif ($extracolumn === self::EXTRACOLUMN_LINK) {
                            $classname .= 'link';
                            $name       = 'link-artifact[search]';
                        } elseif ($extracolumn === self::EXTRACOLUMN_UNLINK) {
                            $classname .= 'unlink';
                            $name       = 'artifact[' . (int) $artifactlink_field_id . '][removed_values][' . $row['id'] . ']';
                            if (isset($prefill_removed_values[$row['id']])) {
                                $checked = 'checked="checked"';
                            }
                        } else {
                            $display_extracolumn = false;
                        }

                        if ($display_extracolumn) {
                            $html .= '<td class="' . $purifier->purify($classname) . '" width="1">';
                            if ($artifact_link_can_be_modified) {
                                $html .= '<span><input type="checkbox" name="' . $purifier->purify($name) . '[]" value="' . $purifier->purify($row['id']) . '" ' . $checked . ' /></span>';
                            }
                            $html .= '</td>';
                        }
                    }
                    if (! $only_one_column) {
                        $params = [
                            'aid' => $row['id'],
                        ];
                        if ($from_aid != null) {
                            $params['from_aid'] = $from_aid;
                        }
                        $url = TRACKER_BASE_URL . '/?' . http_build_query($params);

                        $html .= '<td>';
                        $html .= '<a
                            class="direct-link-to-artifact"
                            data-test="direct-link-to-artifact"
                            href="' . $purifier->purify($url) . '"
                            title="' . $purifier->purify(dgettext('tuleap-tracker', 'Show') . ' artifact #' . $row['id']) . '">';
                        $html .= '<i class="fa fa-edit"></i>';
                        $html .= '</td>';
                    }
                    foreach ($columns as $key => $column) {
                        if ($column['field']->isUsed()) {
                            $field_name = $column['field']->getPrefixedName();
                            $value      = isset($row[$field_name]) ? $row[$field_name] : null;
                            $html      .= '<td data-column-id="' . $purifier->purify($key) . '">';

                            if (isset($column['artlink_nature'])) {
                                $html .= $column['field']->fetchChangesetValueForType(
                                    $row['id'],
                                    $row['changeset_id'],
                                    $value,
                                    $column['artlink_nature'],
                                    $column['artlink_nature_format'],
                                    $this->report,
                                    $from_aid
                                );
                            } else {
                                $html .= $column['field']->fetchChangesetValue(
                                    (int) $row['id'],
                                    (int) $row['changeset_id'],
                                    $value,
                                    $this->report,
                                    (int) $from_aid
                                );
                            }
                            $html .= '</td>';
                        }
                    }
                    if (isset($matching_ids['type'][$artifact_id])) {
                        $type = $matching_ids['type'][$artifact_id];
                        if (! $type instanceof TypePresenter) {
                            continue;
                        }

                        $forward_label = $purifier->purify($type->forward_label);
                        $html         .= '<td class="tracker_formelement_read_and_edit_read_section">' . $forward_label . '</td>';
                        if (! $read_only) {
                            $project         = $this->report->getTracker()->getProject();
                            $types           = $this->getAllUsableTypesInProjectWithCache($project);
                            $types_presenter = [];
                            $selected_type   = $type->shortname;
                            if (isset($prefill_types[$artifact_id])) {
                                $selected_type = $prefill_types[$artifact_id];
                            }
                            $is_a_usable_type_selected = false;
                            foreach ($types as $type) {
                                $should_select_current_type = $selected_type === $type->shortname;
                                $is_a_usable_type_selected  = $is_a_usable_type_selected || $should_select_current_type;
                                $types_presenter[]          = [
                                    'shortname'     => $type->shortname,
                                    'forward_label' => $type->forward_label,
                                    'is_selected'   => $should_select_current_type,
                                ];

                                if ($is_parent_selector_displayed) {
                                    continue;
                                }

                                if ($type->shortname === \Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD) {
                                    $should_select_current_type = \Tracker_FormElement_Field_ArtifactLink::FAKE_TYPE_IS_PARENT === $selected_type;
                                    $is_a_usable_type_selected  = $is_a_usable_type_selected || $should_select_current_type;
                                    $types_presenter[]          = [
                                        'shortname'     => \Tracker_FormElement_Field_ArtifactLink::FAKE_TYPE_IS_PARENT,
                                        'forward_label' => $type->reverse_label,
                                        'is_selected'   => $should_select_current_type,
                                    ];
                                }
                            }

                            if (! $is_a_usable_type_selected) {
                                $type = $this->getTypePresenterFactory()->getTypeEnabledInProjectFromShortname($project, $selected_type);
                                if ($type !== null) {
                                    $types_presenter[] = [
                                        'shortname'     => $type->shortname,
                                        'forward_label' => $type->forward_label,
                                        'is_selected'   => true,
                                    ];
                                }
                            }

                            $name  = "artifact[{$artifactlink_field_id}][types][{$row['id']}]";
                            $html .= '<td class="tracker_formelement_read_and_edit_edition_section">';
                            $html .= $renderer->renderToString(
                                'artifactlink-type-selector',
                                new TypeSelectorPresenter(
                                    $types_presenter,
                                    $name,
                                    '',
                                    ! $artifact_link_can_be_modified,
                                )
                            );
                            $html .= '</td>';
                        }
                    }
                    $html .= '</tr>';
                }
                if (! $only_rows) {
                    $html .= $this->fetchAggregates($matching_ids, $extracolumn, $only_one_column, $columns, $use_data_from_db, $read_only);
                }
            }
        } else {
            $html .= '<tr class="tracker_report_table_no_result" data-test="tracker-report-table-empty-state">
                          <td class="tlp-table-cell-empty table-cell-empty" colspan="' . (count($this->getColumns()) + 2)
                          . '" align="center">' . dgettext('tuleap-tracker', 'No activity yet') . '
                          </td>
                      </tr>';
        }
        if (! $only_rows) {
            $html .= '</tbody>';
            $html .= '</table>';
        }
        return $html;
    }

    private function getAllUsableTypesInProjectWithCache(Project $project)
    {
        static $all_types_project_cache = [];
        if (isset($all_types_project_cache[$project->getID()])) {
            return $all_types_project_cache[$project->getID()];
        }
        $type_presenter_factory                     = $this->getTypePresenterFactory();
        $all_types                                  = $type_presenter_factory->getAllUsableTypesInProject($project);
        $all_types_project_cache[$project->getID()] = $all_types;
        return $all_types;
    }

    public function fetchAggregates($matching_ids, $extracolumn, $only_one_column, $columns, $use_data_from_db, $read_only)
    {
        $html = '';

        //We presume that if EXTRACOLUMN_LINK then it means that we are in the ArtifactLink selector so we force read only mode
        if ($extracolumn === self::EXTRACOLUMN_LINK) {
            $read_only = true;
        }

        $current_user = UserManager::instance()->getCurrentUser();
        //Insert function aggregates
        if ($use_data_from_db) {
            $aggregate_functions_raw = [$this->getAggregatesDao()->searchByRendererId($this->getId())];
        } else {
            $aggregate_functions_raw = $this->getAggregates();
        }
        $aggregates = [];
        foreach ($aggregate_functions_raw as $rows) {
            if ($rows) {
                foreach ($rows as $row) {
                    //is the field used as a column?
                    if (isset($columns[$row['field_id']])) {
                        if (! isset($aggregates[$row['field_id']])) {
                            $aggregates[$row['field_id']] = [];
                        }
                        $aggregates[$row['field_id']][] = $row['aggregate'];
                    }
                }
            }
        }
        $results = [];
        if (count($aggregates) !== 0) {
            $queries = $this->buildOrderedQuery($matching_ids, $columns, $aggregates);
            $dao     = new DataAccessObject();
            foreach ($queries as $key => $sql) {
                if ($key === 'aggregates_group_by') {
                    foreach ($sql as $k => $s) {
                        $results[$k] = $dao->retrieve($s);
                    }
                } else {
                    if ($dar = $dao->retrieve($sql)) {
                        $results = array_merge($results, $dar->getRow());
                    }
                }
            }
        }

        $is_first = true;
        $html    .= '<tr valign="top" class="tracker_report_table_aggregates">';
        $html    .= $this->fetchAggregatesExtraColumns($extracolumn, $only_one_column, $current_user);
        foreach ($columns as $key => $column) {
            $field = $column['field'];
            if (! $field->isUsed()) {
                continue;
            }

            $html  .= '<td data-column-id="' . $key . '">';
            $html  .= '<table><thead><tr>';
            $html  .= $this->fetchAddAggregatesUsedFunctionsHeader($field, $aggregates, $results);
            $html  .= '<th>';
            $html  .= $this->fetchAddAggregatesButton($read_only, $field, $current_user, $aggregates, $is_first);
            $html  .= '</th>';
            $html  .= '</tr></thead><tbody><tr>';
            $result = $this->fetchAddAggregatesUsedFunctionsValue($field, $aggregates, $results);
            if (! $result) {
                $html .= '<td></td>';
            }
            $html .= $result;
            $html .= '</tr></tbody></table>';
            $html .= '</td>';

            $is_first = false;
        }
        if (isset($matching_ids['type'])) {
            $html .= '<td><table><thead><tr><th></th></tr></thead><tbody><tr></tr></tbody></table></td>';
        }
        $html .= '</tr>';

        return $html;
    }

    private function fetchAddAggregatesUsedFunctionsHeader(
        Tracker_FormElement_Field $field,
        array $used_aggregates,
        array $results,
    ) {
        if (! isset($used_aggregates[$field->getId()])) {
            return '';
        }

        $html = '';
        foreach ($used_aggregates[$field->getId()] as $function) {
            if (! isset($results[$field->getName() . '_' . $function])) {
                continue;
            }

            $html .= '<th>';
            $html .= $this->getAggregateLabel($function);
            $html .= '</th>';
        }

        return $html;
    }

    private function getAggregateLabel(string $function): string
    {
        switch ($function) {
            case 'AVG':
                return dgettext('tuleap-tracker', 'Average');
            case 'COUNT':
                return dgettext('tuleap-tracker', 'Count');
            case 'COUNT_GRBY':
                return dgettext('tuleap-tracker', 'Count (group by)');
            case 'MAX':
                return dgettext('tuleap-tracker', 'Maximum');
            case 'MIN':
                return dgettext('tuleap-tracker', 'Minimum');
            case 'STD':
                return dgettext('tuleap-tracker', 'Std deviation');
            case 'SUM':
            default:
                return dgettext('tuleap-tracker', 'Sum');
        }
    }

    private function fetchAddAggregatesUsedFunctionsValue(
        Tracker_FormElement_Field $field,
        array $used_aggregates,
        array $results,
    ) {
        if (! isset($used_aggregates[$field->getId()])) {
            return '';
        }

        $hp   = Codendi_HTMLPurifier::instance();
        $html = '';
        foreach ($used_aggregates[$field->getId()] as $function) {
            $result_key = $field->getName() . '_' . $function;
            if (! isset($results[$result_key])) {
                continue;
            }

            $result = $results[$result_key];
            $html  .= '<td>';
            if ($field->hasCustomFormatForAggregateResults()) {
                $html .= $field->formatAggregateResult($function, $result);
            } else {
                if ($result instanceof LegacyDataAccessResultInterface) {
                    if ($row = $result->getRow()) {
                        if (isset($row[$result_key])) {
                            //this case is for multiple selectbox/count
                            $html .= '<label  class="tracker-aggregate-single-line">';
                            $html .= $this->formatAggregateResult($row[$result_key]);
                            $html .= '<label>';
                        } else {
                            foreach ($result as $row) {
                                $html .= '<label  class="tracker-aggregate-single-line">';
                                if ($row['label'] === null) {
                                    $html .= '<em>' . $GLOBALS['Language']->getText('global', 'null') . '</em>';
                                } else {
                                    $html .= $hp->purify($row['label']);
                                }
                                $html .= ':&nbsp;';
                                $html .= $this->formatAggregateResult($row['value']);
                                $html .= '</label>';
                            }
                        }
                    }
                } else {
                    $html .= '<label>';
                    $html .= $this->formatAggregateResult($result);
                    $html .= '<label>';
                }
            }
            $html .= '</td>';
        }

        return $html;
    }

    private function fetchAddAggregatesButton(
        $read_only,
        Tracker_FormElement_Field $field,
        PFUser $current_user,
        array $used_aggregates,
        $is_first,
    ) {
        $aggregate_functions = $field->getAggregateFunctions();

        if ($read_only || $current_user->isAnonymous()) {
            return;
        }

        if (! $aggregate_functions) {
            return;
        }

        $html  = '';
        $html .= '<div class="btn-group">';
        $html .= '<a href="#"
            class="btn btn-mini dropdown-toggle"
            title="' . dgettext('tuleap-tracker', 'Toggle an aggregate function') . '"
            data-toggle="dropdown">';
        $html .= '<i class="fa fa-plus"></i> ';
        $html .= '<span class="caret"></span>';
        $html .= '</a>';
        $html .= '<ul class="dropdown-menu ' . ($is_first ? '' : 'pull-right') . '">';
        foreach ($aggregate_functions as $function) {
            $is_used = isset($used_aggregates[$field->getId()]) && in_array($function, $used_aggregates[$field->getId()]);
            $url     = $this->getAggregateURL($field, $function);
            $html   .= '<li>';
            $html   .= '<a href="' . $url . '">';
            if ($is_used) {
                $html .= '<i class="fa fa-check"></i> ';
            }
            $html .= $this->getAggregateLabel($function);
            $html .= '</a>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }

    private function getAggregateURL($field, $function)
    {
        $field_id = $field->getId();
        $params   = [
            'func'       => 'renderer',
            'report'     => $this->report->getId(),
            'renderer'   => $this->getId(),
            'renderer_table' => [
                'add_aggregate' => [
                    $field_id => $function,
                ],
            ],
        ];
        return TRACKER_BASE_URL . '/?' . http_build_query($params);
    }

    private function fetchAggregatesExtraColumns($extracolumn, $only_one_column, PFUser $current_user)
    {
        $html        = '';
        $inner_table = '<table><thead><tr><th></th></tr></thead></table>';
        if ($extracolumn) {
            $display_extracolumn = true;
            $classname           = 'tracker_report_table_';
            if ($extracolumn === self::EXTRACOLUMN_MASSCHANGE && $this->report->getTracker()->userIsAdmin($current_user)) {
                $classname .= 'masschange';
            } elseif ($extracolumn === self::EXTRACOLUMN_LINK) {
                $classname .= 'link';
            } elseif ($extracolumn === self::EXTRACOLUMN_UNLINK) {
                $classname .= 'unlink';
            } else {
                $display_extracolumn = false;
            }

            if ($display_extracolumn) {
                $html .= '<td class="' . $classname . '" width="1">';
                $html .= $inner_table;
                $html .= '</td>';
            }
        }
        if (! $only_one_column) {
            $html .= '<td>' . $inner_table . '</td>';
        }

        return $html;
    }

    protected function formatAggregateResult($value)
    {
        if (is_numeric($value)) {
            $decimals = 2;
            if (round($value) == $value) {
                $decimals = 0;
            }
            $value = round($value, $decimals);
        } else {
            $value = Codendi_HTMLPurifier::instance()->purify($value);
        }

        return '<span class="tracker_report_table_aggregates_value">' . $value . '</span>';
    }

    /**
     * Build oredered query
     *
     * @param array                       $matching_ids The artifact to display
     *
     * @return array of sql queries
     */
    public function buildOrderedQuery($matching_ids, $columns, $aggregates = false)
    {
        $select = $this->getBaseQuerySelect($aggregates);
        $from   = $this->getBaseQueryFrom();

        $changeset_ids = $this->getLegacyDataAccess()->escapeIntImplode(explode(',', $matching_ids['last_changeset_id']));

        $where = " WHERE c.id IN (" . $changeset_ids . ") ";
        if ($aggregates) {
            $ordering = false;
        } else {
            $ordering = true;
        }

        $additionnal_select = [];
        $additionnal_from   = [];
        $already_seen       = [];

        foreach ($columns as $column) {
            if (! $column['field']->isUsed() || $column['field']->isMultiple()) {
                continue;
            }

            if (isset($already_seen[$column['field']->getId()])) {
                continue;
            }
            $already_seen[$column['field']->getId()] = true;

            $sel = false;
            if ($aggregates) {
                if (isset($aggregates[$column['field']->getId()])) {
                    if ($a = $column['field']->getQuerySelectAggregate($aggregates[$column['field']->getId()])) {
                        $sel = $a['same_query'];
                        if ($sel) {
                            $additionnal_select[] = $sel;
                            $additionnal_from[]   = $column['field']->getQueryFromAggregate();
                        }
                    }
                }
            } else {
                $sel = $column['field']->getQuerySelect();
                if ($sel) {
                    $additionnal_select[] = $sel;
                    $additionnal_from[]   = $column['field']->getQueryFrom();
                }
            }
        }

        //build an array of queries (due to mysql max join limit
        $queries         = [];
        $sys_server_join = $this->getNumberServerJoin();

        $additionnal_select_chunked = array_chunk($additionnal_select, $sys_server_join);
        $additionnal_from_chunked   = array_chunk($additionnal_from, $sys_server_join);

        //both arrays are not necessary the same size
        $n = max(count($additionnal_select_chunked), count($additionnal_from_chunked));
        for ($i = 0; $i < $n; ++$i) {
            //init the select and the from...
            $inner_select = $select;
            $inner_from   = $from;

            //... and populate them
            if (isset($additionnal_select_chunked[$i]) && count($additionnal_select_chunked[$i])) {
                $inner_select .= ', ' . implode(', ', $additionnal_select_chunked[$i]);
            }
            if (isset($additionnal_from_chunked[$i]) && count($additionnal_from_chunked[$i])) {
                $inner_from .= implode(' ', $additionnal_from_chunked[$i]);
            }

            //build the query
            $sql = $inner_select . $inner_from . $where;

            //add it to the pool
            $queries[] = $sql;
        }

        //Add group by aggregates
        if ($aggregates) {
            $queries_aggregates_group_by = [];
            foreach ($columns as $column) {
                if ($column['field']->isUsed()) {
                    if (isset($aggregates[$column['field']->getId()])) {
                        if ($a = $column['field']->getQuerySelectAggregate($aggregates[$column['field']->getId()])) {
                            foreach ($a['separate_queries'] as $sel) {
                                $queries_aggregates_group_by[$column['field']->getName() . '_' . $sel['function']] = "SELECT " .
                                    $sel['select'] .
                                    $from . ' ' . $column['field']->getQueryFromAggregate() .
                                    $where .
                                    ($sel['group_by'] ? " GROUP BY " . $sel['group_by'] : '');
                            }
                        }
                    }
                }
            }

            if (count($queries_aggregates_group_by) > 0) {
                $queries['aggregates_group_by'] = $queries_aggregates_group_by;
            }
        }

        //only sort if we have 1 query
        // (too complicated to sort on multiple queries)
        if ($ordering && $this->columnsCanBeTechnicallySorted($queries)) {
            $sort = SortWithIntegrityChecked::getSortOnUsedFields($this->getSort());
            if (count($sort) > 0) {
                $order = [];
                foreach ($sort as $s) {
                    $order[] = $s['field']->getQueryOrderby() . ' ' . ($s['is_desc'] ? 'DESC' : 'ASC');
                }
                if (! empty($order)) {
                    $queries[0] .= " ORDER BY " . implode(', ', $order);
                }
            }
        }

        if (empty($queries)) {
            $queries[] = $select . $from . $where;
        }

        return $queries;
    }

    private function fetchMassChange($matching_ids, $total_rows, $offset)
    {
        $html    = '';
        $tracker = $this->report->getTracker();
        if ($tracker->userIsAdmin()) {
            $nb_art    = $matching_ids['id'] ? substr_count($matching_ids['id'], ',') + 1 : 0;
            $first_row = ($nb_art / $this->chunksz) + $offset;
            $last_row  = $first_row + $this->chunksz;
            $html     .= '<form method="POST" action="" id="tracker_report_table_masschange_form">';
            $html     .= '<input type="hidden" name="func" value="display-masschange-form" />';
            $html     .= '<div id="tracker_report_table_masschange_panel">';
            $html     .= '<input id="masschange_btn_checked" type="submit" class="btn" name="renderer_table[masschange_checked]" value="' . dgettext('tuleap-tracker', 'Mass Change Checked') . '" /> ';
            $html     .= '<input id="masschange_btn_all" type="submit" class="btn" name="renderer_table[masschange_all]" value="' . sprintf(dgettext('tuleap-tracker', 'Mass Change All (%1$s artifacts)'), $total_rows) . '" />';
            $html     .= '</div>';
            $html     .= '</form>';
        }
        return $html;
    }

    protected function getFieldFactory()
    {
        return Tracker_FormElementFactory::instance();
    }

    /**
     * Duplicate the renderer
     */
    public function duplicate($from_report_id, $field_mapping, MappingRegistry $mapping_registry): void
    {
        //duplicate sort
        $this->getSortDao()->duplicate($from_report_id->id, $this->id, $field_mapping);
        //duplicate columns
        $this->getColumnsDao()->duplicate($from_report_id->id, $this->id, $field_mapping);
        //duplicate aggregates
        $this->getAggregatesDao()->duplicate($from_report_id->id, $this->id, $field_mapping);
    }

    public function getType()
    {
        return self::TABLE;
    }

    /**
     * Process the request
     * @param HTTPRequest $request
     */
    public function processRequest(TrackerManager $tracker_manager, $request, PFUser $current_user)
    {
        $ff = $this->getFieldFactory();

        $renderer_parameters = $request->get('renderer_table');
        $this->initiateSession();
        if ($renderer_parameters && is_array($renderer_parameters)) {
            //Update the chunksz parameter
            if (isset($renderer_parameters['chunksz'])) {
                $new_chunksz = abs((int) $renderer_parameters['chunksz']);
                if ($new_chunksz && ($this->chunksz != $new_chunksz)) {
                    $this->report_session->set("{$this->id}.chunksz", $new_chunksz);
                    $this->report_session->setHasChanged();
                    $this->chunksz = $new_chunksz;
                }
            }

            //Add an aggregate function
            if (isset($renderer_parameters['add_aggregate']) && is_array($renderer_parameters['add_aggregate'])) {
                $column_id = key($renderer_parameters['add_aggregate']);
                $agg       = current($renderer_parameters['add_aggregate']);
                //Is the field used by the tracker?
                if ($field = $ff->getUsedFormElementById($column_id)) {
                    //Has the field already an aggregate function?
                    $aggregates = $this->getAggregates();
                    if (isset($aggregates[$column_id])) {
                        //Yes. Check if it has already the wanted aggregate function
                        $found = false;
                        foreach ($aggregates[$column_id] as $key => $row) {
                            if ($row['aggregate'] === $agg) {
                                $found = true;
                                //remove it (toggle)
                                unset($aggregates[$column_id][$key]);
                                $this->report_session->set("{$this->id}.aggregates.{$column_id}", $aggregates[$column_id]);
                                break;
                            }
                        }
                        if (! $found) {
                            //Add it
                            $aggregates[$column_id][] = ['renderer_id' => $this->id, 'field_id' => $column_id, 'aggregate' => $agg];
                            $this->report_session->set("{$this->id}.aggregates.{$column_id}", $aggregates[$column_id]);
                        }
                        $this->report_session->setHasChanged();
                        //TODO
                    } else {
                        //No. Add it
                        $this->report_session->set("{$this->id}.aggregates.{$column_id}", [['renderer_id' => $this->id, 'field_id' => $column_id, 'aggregate' => $agg]]);
                        $this->report_session->setHasChanged();
                    }
                }
            }

            //toggle a sort column
            if (isset($renderer_parameters['sort_by'])) {
                $sort_by = (int) $renderer_parameters['sort_by'];
                if ($sort_by) {
                    if ($field = $ff->getUsedFormElementById($sort_by)) {
                        if ($this->isFieldUsedAsColumn($field)) {
                            //Is the field already used to sort results?
                            $sort_fields = $this->getSort();
                            if (isset($sort_fields[$sort_by])) {
                                $is_desc = &$this->report_session->get("{$this->id}.sort.{$sort_by}.is_desc");
                                //toggle
                                $desc = 1;
                                if ($is_desc == 1) {
                                    $desc = 0;
                                }
                                $this->report_session->set("{$this->id}.sort.{$sort_by}.is_desc", $desc);
                                $this->report_session->setHasChanged();
                            } else {
                                if (! $this->multisort) {
                                    //Drop existing sort
                                    foreach ($sort_fields as $id => $sort_field) {
                                        $this->report_session->remove("{$this->id}.sort", $id);
                                    }
                                }
                                //Add new sort
                                $sort = $this->report_session->get("{$this->id}.sort");
                                $rank = 0;
                                if ($sort !== null) {
                                    $rank = count($sort);
                                }
                                $this->report_session->set("{$this->id}.sort.{$sort_by}", ['is_desc' => 0, 'rank' => $rank]);
                                $this->report_session->setHasChanged();
                            }
                        }
                    }
                }
            }

            //Reset sort
            if (isset($renderer_parameters['resetsort'])) {
                //Drop existing sort
                $this->report_session->remove("{$this->id}", "sort");
                $this->report_session->setHasChanged();
            }

            //Toggle multisort
            if (isset($renderer_parameters['multisort'])) {
                $sort_fields     = $this->getSort();
                $keep_it         = key($sort_fields);
                $this->multisort = ! $this->multisort;
                $this->report_session->set("{$this->id}.multisort", $this->multisort);
                if (! $this->multisort) {
                    $sort = $this->report_session->get("{$this->id}.sort");
                    foreach ($sort as $column_id => $properties) {
                        if ($column_id != $keep_it) {
                            $this->report_session->remove("{$this->id}.sort", $column_id);
                            $this->report_session->setHasChanged();
                        }
                    }
                }
            }

            //Remove column
            if (isset($renderer_parameters['remove-column'])) {
                $column_id = $renderer_parameters['remove-column'];
                if ($column_id) {
                    $columns = $this->getColumns();
                    if (isset($columns[$column_id])) {
                        //Is the field already used to sort results?
                        $sort_fields = $this->getSort();
                        if (isset($sort_fields[$column_id])) {
                            //remove from session
                            $this->report_session->remove("{$this->id}.sort", $column_id);
                            $this->report_session->setHasChanged();
                        }
                        //remove from session
                        $this->report_session->remove("{$this->id}.columns", $column_id);
                        $this->report_session->setHasChanged();
                    }
                }
            }

            //Add column
            if (isset($renderer_parameters['add-column']['field-id'])) {
                if ($field_id = (int) $renderer_parameters['add-column']['field-id']) {
                    if ($field = $ff->getUsedFormElementById($field_id)) {
                        $columns      = $this->getColumns();
                        $key          = $field->getId();
                        $artlink_type = null;
                        if (isset($renderer_parameters['add-column']['artlink-type'])) {
                            $artlink_type = $renderer_parameters['add-column']['artlink-type'];
                            $key         .= '_' . $artlink_type;
                        }
                        if (! isset($columns[$key])) {
                            $session_table_columns = $this->report_session->get("{$this->id}.columns") ?? [];
                            $nb_col                = count($session_table_columns);
                            //Update session with new column
                            $this->report_session->set(
                                "{$this->id}.columns.{$key}",
                                [
                                    'field_id'              => $field_id,
                                    'width'                 => 12,
                                    'rank'                  => $nb_col,
                                    'artlink_nature'        => $artlink_type,
                                    'artlink_nature_format' => null,
                                ]
                            );
                            $this->report_session->setHasChanged();

                            if ($request->isAjax()) {
                                $matching_ids     = $this->report->getMatchingIds();
                                $offset           = (int) $request->get('offset');
                                $extracolumn      = self::NO_EXTRACOLUMN;
                                $total_rows       = $matching_ids['id'] ? substr_count($matching_ids['id'], ',') + 1 : 0;
                                $link_artifact_id = (int) $request->get('link-artifact-id');

                                echo $this->fetchTHead($extracolumn, $key, ! $link_artifact_id);
                                $use_data_from_db = false;
                                $aggregates       = false;
                                $store_in_session = true;

                                $columns = $this->getTableColumns($key, $use_data_from_db);
                                $queries = $this->buildOrderedQuery(
                                    $matching_ids,
                                    $columns,
                                    $aggregates,
                                );

                                echo $this->fetchTBody(
                                    $matching_ids,
                                    $total_rows,
                                    $queries,
                                    $columns,
                                    $extracolumn,
                                    $key
                                );
                            }
                        }
                    }
                }
            }

            //Reorder columns
            if (isset($renderer_parameters['reorder-column']) && is_array($renderer_parameters['reorder-column'])) {
                $column_id    = key($renderer_parameters['reorder-column']);
                $new_position = (int) current($renderer_parameters['reorder-column']);
                if ($column_id) {
                    $columns = $this->getColumns();
                    if (isset($columns[$column_id])) {
                        if ($ff->getUsedFormElementById($columns[$column_id]['field_id'])) {
                            $columns = $this->report_session->get("{$this->id}.columns") ?? [];
                            if ($new_position == '-1') {
                                //beginning
                                foreach ($columns as $id => $properties) {
                                    $columns[$id]['rank'] = $properties['rank'] + 1;
                                    $this->report_session->set("{$this->id}.columns.{$id}.rank", $columns[$id]['rank']);
                                }
                                $columns[$column_id]['rank'] = 0;
                                $this->report_session->set("{$this->id}.columns.{$column_id}.rank", $columns[$column_id]['rank']);
                            } elseif ($new_position == '-2') {
                                //end
                                $max = 0;
                                foreach ($columns as $id => $properties) {
                                    if ($properties['rank'] > $max) {
                                        $max = $properties['rank'];
                                    }
                                    $properties['rank'] = $properties['rank'] - 1;
                                    $this->report_session->set("{$this->id}.columns.{$id}.rank", $properties['rank']);
                                }
                                $columns[$column_id]['rank'] = $max + 1;
                                $this->report_session->set(
                                    "{$this->id}.columns.{$column_id}.rank",
                                    $columns[$column_id]['rank']
                                );
                            } else {
                                //other case
                                $replaced_rank = $columns[$new_position]['rank'] + 1;   // rank of the element to shift right
                                foreach ($columns as $id => $properties) {
                                    if ($properties['rank'] >= $replaced_rank && $id != $column_id) {
                                        $columns[$id]['rank'] += 1;
                                        $this->report_session->set("{$this->id}.columns.{$id}.rank", $columns[$id]['rank']);
                                    }
                                }
                                $this->report_session->set("{$this->id}.columns.{$column_id}.rank", $replaced_rank);
                                $columns[$column_id]['rank'] = $replaced_rank;
                            }
                            $this->report_session->setHasChanged();
                        }
                    }
                }
            }

            //Resize column
            if (isset($renderer_parameters['resize-column']) && is_array($renderer_parameters['resize-column'])) {
                foreach ($renderer_parameters['resize-column'] as $column_id => $new_width) {
                    $new_width = (int) $new_width;
                    if ($column_id) {
                        $columns = $this->getColumns();
                        if (isset($columns[$column_id])) {
                            if ($ff->getUsedFormElementById($columns[$column_id]['field_id'])) {
                                $this->report_session->set("{$this->id}.columns.{$column_id}.width", $new_width);
                                $this->report_session->setHasChanged();
                            }
                        }
                    }
                }
            }

            // Define format of column
            if (isset($renderer_parameters['configure-column']) && is_array($renderer_parameters['configure-column'])) {
                foreach ($renderer_parameters['configure-column'] as $column_id => $format) {
                    if ($column_id) {
                        $columns = $this->getColumns();
                        if (isset($columns[$column_id])) {
                            if ($ff->getUsedFormElementById($columns[$column_id]['field_id'])) {
                                $this->report_session->set("{$this->id}.columns.{$column_id}.artlink_nature_format", $format);
                                $this->report_session->setHasChanged();
                            }
                        }
                    }
                }
            }

            //export
            if (isset($renderer_parameters['export']) && ! $current_user->isAnonymous()) {
                $event = new ProcessExportEvent($renderer_parameters, $this, $current_user, \Tuleap\ServerHostname::HTTPSUrl());
                EventManager::instance()->processEvent($event);
                $only_columns = isset($renderer_parameters['export_only_displayed_fields']) && $renderer_parameters['export_only_displayed_fields'];
                $this->exportToCSV($only_columns);
            }
        }
    }

    private function getFieldWhenUsingTypes(SimpleXMLElement $node, array $field_info, $xmlMapping)
    {
        $field = null;

        if (isset($field_info['artlink_nature']) || isset($field_info['artlink_nature_format'])) {
            $ref = array_search($field_info['field_id'], $xmlMapping);
            if ($ref) {
                $field = $node->addChild('field');
                $field->addAttribute('REF', $ref);
                if (isset($field_info['artlink_nature'])) {
                    $field->addAttribute('artlink-nature', $field_info['artlink_nature']);
                }
                if (isset($field_info['artlink_nature_format'])) {
                    $field->addAttribute('artlink-nature-format', $field_info['artlink_nature_format']);
                }
            }
        }

        return $field;
    }

    private function getField(SimpleXMLElement $node, $exported_field_id, $xmlMapping)
    {
        $field = null;

        $ref = array_search($exported_field_id, $xmlMapping);
        if ($ref) {
            $field = $node->addChild('field');
            $field->addAttribute('REF', $ref);
        }

        return $field;
    }

    /**
     * Transforms Tracker_Renderer into a SimpleXMLElement
     *
     * @param SimpleXMLElement $root the node to which the renderer is attached (passed by reference)
     */
    public function exportToXml(SimpleXMLElement $root, array $xmlMapping)
    {
        parent::exportToXml($root, $xmlMapping);
        $root->addAttribute('chunksz', $this->chunksz ?? '');
        if ($this->multisort) {
            $root->addAttribute('multisort', $this->multisort);
        }

        $child = $root->addChild('columns');
        foreach ($this->getColumns() as $key => $col) {
            $field = $this->getFieldWhenUsingTypes($child, $col, $xmlMapping);
            if (! $field) {
                $field = $this->getField($child, $key, $xmlMapping);
            }
        }

        //TODO : add aggregates in XML export
        /*if ($this->getAggregates()) {
            $child = $root->addChild('aggregates');
            foreach ($this->getAggregates() as $field_id => $aggregates) {
                foreach ($aggregates as $aggregate) {
                    $child->addChild('aggregate')->addAttribute('REF', array_search($field_id, $xmlMapping))
                                                 ->addAttribute('function', $aggregate);
                }
            }
        }*/

        if ($this->getSort()) {
            $child = $root->addChild('sort');
            foreach ($this->getSort() as $key => $sort) {
                 $child->addChild('field')->addAttribute('REF', array_search($key, $xmlMapping));
            }
        }
    }

    private function exportHeadAllReportColumns(array $column)
    {
        $title = $column['field']->getName();
        if (isset($column['artlink_nature'])) {
            $type = $column['artlink_nature'];
            if (! $type) {
                $type = dgettext('tuleap-tracker', 'No type');
            }
            $title .= " (" . $type . ")";
        }

        return $title;
    }

    private function exportHeadReportColumn(array $column)
    {
        $head  = [];
        $title = $column['field']->getName();
        if ($this->report->getTracker()->isProjectAllowedToUseType()) {
            if ($this->getFieldFactory()->getType($column['field']) === Tracker_FormElement_Field_ArtifactLink::TYPE) {
                $head[] = $title;
                foreach ($this->getTypePresenterFactory()->getAllUsedTypesByProject($this->report->getTracker()->getProject()) as $type) {
                    if (! $type) {
                        $type = dgettext('tuleap-tracker', 'No type');
                    }
                    $head[] = $title . " (" . $type . ")";
                }
            } else {
                $head[] = $title;
            }
        } else {
            $head[] = $title;
        }

        return $head;
    }

    private function exportAllReportColumn(array $column, array $row)
    {
        $line = [];

        $value  = isset($row[$column['field']->getPrefixedName()]) ? $row[$column['field']->getPrefixedName()] : null;
        $line[] = $column['field']->fetchCSVChangesetValue($row['id'], $row['changeset_id'], $value, $this->report);

        if (
            $this->report->getTracker()->isProjectAllowedToUseType() &&
            $this->getFieldFactory()->getType($column['field']) === Tracker_FormElement_Field_ArtifactLink::TYPE
        ) {
            foreach ($this->getTypePresenterFactory()->getAllUsedTypesByProject($this->report->getTracker()->getProject()) as $type) {
                $line[] = $column['field']->fetchCSVChangesetValueWithType(
                    $row['changeset_id'],
                    $type,
                    ''
                );
            }
        }

        return $line;
    }

    private function exportReportColumn(array $column, array $row)
    {
        $line = [];

        if (isset($column['artlink_nature'])) {
            $format = isset($column['artlink_nature_format']) ? $column['artlink_nature_format'] : '';
            $line[] = $column['field']->fetchCSVChangesetValueWithType(
                $row['changeset_id'],
                $column['artlink_nature'],
                $format
            );
        } else {
            $value  = isset($row[$column['field']->getPrefixedName()]) ? $row[$column['field']->getPrefixedName()] : null;
            $line[] = $column['field']->fetchCSVChangesetValue($row['id'], $row['changeset_id'], $value, $this->report);
        }

        return $line;
    }

    /**
     * Export results to csv
     *
     * @param bool $only_columns True if we need to export only the displayed columns. False for all the fields.
     *
     * @return void
     */
    protected function exportToCSV($only_columns)
    {
        $matching_ids = $this->report->getMatchingIds();

        if ($only_columns) {
            $columns = $this->reorderColumnsByRank($this->getColumns());
        } else {
            $columns     = [];
            $used_fields = $this->getFieldFactory()->getUsedFields($this->report->getTracker());
            foreach ($used_fields as $field) {
                $columns[]['field'] = $field;
            }
        }

        $lines = [];
        $head  = ['aid'];

        foreach ($columns as $column) {
            if (! CSVFieldUsageChecker::canFieldBeExportedToCSV($column['field'])) {
                continue;
            }

            if ($only_columns) {
                $head[] = $this->exportHeadAllReportColumns($column);
            } else {
                $head = array_merge($head, $this->exportHeadReportColumn($column));
            }
        }

        $lines[] = $head;

        $queries = $this->buildOrderedQuery($matching_ids, $columns);
        $dao     = new DataAccessObject();
        $results = [];
        foreach ($queries as $sql) {
            $results[] = $dao->retrieve($sql);
        }

        if (! empty($results[0])) {
            //extract the first results
            $first_result = array_shift($results);

            foreach ($first_result as $row) { //id, f1, f2
                //merge the row with the other results
                foreach ($results as $result) {
                    if ($result === false) {
                        continue;
                    }
                    //[id, f1, f2] + [id, f3, f4]
                    $row = array_merge($row, $result->getRow());
                    //row == id, f1, f2, f3, f4...
                }

                //build the csv line
                $line   = [];
                $line[] = $row['id'];

                foreach ($columns as $column) {
                    if (! CSVFieldUsageChecker::canFieldBeExportedToCSV($column['field'])) {
                        continue;
                    }

                    if ($only_columns) {
                        $line = array_merge($line, $this->exportReportColumn($column, $row));
                    } else {
                        $line = array_merge($line, $this->exportAllReportColumn($column, $row));
                    }
                }

                $lines[] = $line;
            }

            $separator                 = ",";   // by default, comma.
            $user                      = UserManager::instance()->getCurrentUser();
            $separator_csv_export_pref = $user->getPreference('user_csv_separator');
            switch ($separator_csv_export_pref) {
                case "comma":
                    $separator = ',';
                    break;
                case "semicolon":
                    $separator = ';';
                    break;
                case "tab":
                    $separator = chr(9);
                    break;
            }

            $http      = Codendi_HTTPPurifier::instance();
            $file_name = str_replace(' ', '_', 'artifact_' . $this->report->getTracker()->getItemName());
            header('Content-Disposition: filename=' . $http->purify($file_name) . '_' . $this->report->getTracker()->getProject()->getUnixName() . '.csv');
            header('Content-type: text/csv');
            $csv_file = fopen("php://output", "a");
            $this->addBOMToCSVContent($csv_file);
            foreach ($lines as $line) {
                fputcsv($csv_file, $line, $separator, '"');
            }
            die();
        } else {
            $GLOBALS['Response']->addFeedback('error', 'Unable to export (too many fields?)');
        }
    }

    private function addBOMToCSVContent($csv_file)
    {
        $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputs($csv_file, $bom);
    }

    /**
     * Save columns in db
     *
     * @param int $renderer_id the id of the renderer
     */
    protected function saveColumnsRenderer($renderer_id)
    {
        $columns = $this->getColumns();
        if (! is_array($columns)) {
            return;
        }

        $type_factory  = $this->getTypePresenterFactory();
        $field_factory = $this->getFieldFactory();
        foreach ($columns as $key => $properties) {
            $field = $field_factory->getUsedFormElementById($properties['field_id']);
            if (! $field) {
                continue;
            }

            $type = $properties['artlink_nature'];
            if (isset($type) && ! $type_factory->getFromShortname($type)) {
                continue;
            }

            $this->getColumnsDao()->create(
                $renderer_id,
                $properties['field_id'],
                $properties['width'],
                $properties['rank'],
                $properties['artlink_nature'],
                $properties['artlink_nature_format']
            );
        }
    }

    /**
     * Save aggregates in db
     *
     * @param int $renderer_id the id of the renderer
     */
    protected function saveAggregatesRenderer($renderer_id)
    {
        $aggregates = $this->getAggregates();
        $ff         = $this->getFieldFactory();
        //Add columns in db
        if (is_array($aggregates)) {
            $dao = $this->getAggregatesDao();
            foreach ($aggregates as $field_id => $aggs) {
                if ($field = $ff->getUsedFormElementById($field_id)) {
                    foreach ($aggs as $agg) {
                        $dao->create($renderer_id, $field_id, $agg['aggregate']);
                    }
                }
            }
        }
    }

    /**
     * Save multisort/chunksz in db
     *
     * @param int $renderer_id the id of the renderer
     */
    protected function saveRendererProperties($renderer_id)
    {
        $dao = new Tracker_Report_Renderer_TableDao();
        if (! $dao->searchByRendererId($renderer_id)->getRow()) {
            $dao->create($renderer_id, $this->chunksz);
        }
        $dao->save($renderer_id, $this->chunksz, $this->multisort);
    }

    /**
     * Save sort in db
     *
     * @param int $renderer_id the id of the renderer
     */
    protected function saveSortRenderer($renderer_id)
    {
        $sort = $this->getSort();
        if (is_array($sort)) {
            foreach ($sort as $field_id => $properties) {
                $this->getSortDao()->create($renderer_id, $field_id, $properties['is_desc'], $properties['rank']);
            }
        }
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
            //columns
            $this->saveColumnsRenderer($renderer_id);

            //aggregates
            $this->saveAggregatesRenderer($renderer_id);

            //MultiSort/Chunksz
            $this->saveRendererProperties($renderer_id);

            //Sort
            $this->saveSortRenderer($renderer_id);
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
            //first delete existing columns and sort
            $this->getSortDao()->delete($this->id);
            $this->getColumnsDao()->delete($this->id);
            $this->getAggregatesDao()->deleteByRendererId($this->id);

            //columns
            $this->saveColumnsRenderer($this->id);

            //aggregates
            $this->saveAggregatesRenderer($this->id);

            //MultiSort/Chunksz
            $this->saveRendererProperties($this->id);

            //Sort
            $this->saveSortRenderer($this->id);
        }
        return $success;
    }

    /**
     * Set the session
     *
     */
    public function setSession($renderer_id = null)
    {
        if (! $renderer_id) {
            $renderer_id = $this->id;
        }
        $this->report_session->set("{$this->id}.name", $this->name);
        $this->report_session->set("{$this->id}.description", $this->description);
        $this->report_session->set("{$this->id}.chunksz", $this->chunksz);
        $this->report_session->set("{$this->id}.multisort", $this->multisort);
        $this->report_session->set("{$this->id}.rank", $this->rank);
    }

    /**
     * Finnish saving renderer to database by creating colunms
     *
     * @param Tracker_Report_Renderer $renderer containing the columns
     */
    public function afterSaveObject(Tracker_Report_Renderer $renderer)
    {
        $renderer->injectUnsavedColumnsInRendererDB($this);
        $this->saveAggregates($renderer->getAggregates());
        $this->saveSort($renderer->getSort());
    }

    public function injectUnsavedColumnsInRendererDB(Tracker_Report_Renderer_Table $renderer)
    {
        $renderer->saveColumns($this->_columns);
    }

    public function sortHasUsedField($store_in_session = true): bool
    {
        $sort = SortWithIntegrityChecked::getSortOnUsedFields($this->getSort($store_in_session));
        return count($sort) > 0;
    }

    private function getSortIcon($is_desc)
    {
        return ' <i class="fa fa-caret-' . ( $is_desc ? 'down' : 'up' ) . '"></i>';
    }

    public function getIcon()
    {
        return 'fa fa-list-ul';
    }

    private function fetchViewButtons($report_can_be_modified, PFUser $current_user)
    {
        $html  = '';
        $html .= '<div id="tracker_report_renderer_view_controls">';
        if ($this->sortHasUsedField()) {
            //reset sort
            $reset_sort_params = [
                'report'                    => $this->report->id,
                'renderer'                  => $this->id,
                'func'                      => 'renderer',
                'renderer_table[resetsort]' => 1,
            ];
            $html             .= '<div class="btn-group"><a class="btn btn-mini" href="?' . http_build_query($reset_sort_params) . '">'
                . '<i class="fa fa-reply"></i> '
                . dgettext('tuleap-tracker', 'Reset sort')
                . '</a></div> ';

            //toggle multisort
            $multisort_params = [
                'report'                    => $this->report->id,
                'renderer'                  => $this->id,
                'func'                      => 'renderer',
                'renderer_table[multisort]' => 1,
            ];
            $multisort_label  = dgettext('tuleap-tracker', 'Enable multisort');
            if ($this->multisort) {
                $multisort_label = dgettext('tuleap-tracker', 'Disable multisort');
            }
            $html .= '<div class="btn-group"><a class="btn btn-mini" href="?' . http_build_query($multisort_params) . '">'
                . '<i class="fa fa-sort"></i> '
                . $multisort_label
                . '</a></div> ';
        }

        if ($report_can_be_modified && ! $current_user->isAnonymous()) {
            $html .= $this->fetchAddColumn();
        }
        $html .= '</div>';

        return $html;
    }

    private function isFieldUsedAsColumn(Tracker_FormElement_Field $field)
    {
        $columns = $this->getColumns();
        if (isset($columns[$field->getId()])) {
            return true;
        }

        foreach ($columns as $column) {
            if ($column['field_id'] == $field->getId()) {
                return true;
            }
        }

        return false;
    }

    private function columnsCanBeTechnicallySorted(array $queries)
    {
        return count($queries) <= 1;
    }

    /**
     * @return TypePresenterFactory
     */
    private function getTypePresenterFactory()
    {
        $type_dao                = new TypeDao();
        $artifact_link_usage_dao = new ArtifactLinksUsageDao();

        return new TypePresenterFactory($type_dao, $artifact_link_usage_dao);
    }

    public function getJavascriptDependencies()
    {
        return [
            ['file' => RelativeDatesAssetsRetriever::retrieveAssetsUrl(), 'unique-name' => 'tlp-relative-dates'],
        ];
    }

    public function getStylesheetDependencies(): CssAssetCollection
    {
        $assets = new IncludeAssets(
            __DIR__ . '/../../../frontend-assets',
            '/assets/trackers'
        );
        return new CssAssetCollection([new \Tuleap\Layout\CssAssetWithoutVariantDeclinaisons($assets, 'tracker-bp')]);
    }

    /**
     * @param bool|array $aggregates
     */
    private function getLimitedResult(bool $store_in_session, array $matching_ids, int $offset, $aggregates): array
    {
        $select = $this->getBaseQuerySelect($aggregates);

        if ($aggregates) {
            $ordering = false;
        } else {
            $ordering = true;
        }

        $from = $this->getBaseQueryFrom();

        $dao               = \Tuleap\DB\DBFactory::getMainTuleapDBConnection()->getDB();
        $sorts             = SortWithIntegrityChecked::getSortOnUsedFields($this->getSort($store_in_session));
        $additional_select = [];
        $additional_from   = [];
        if ($ordering && count($sorts) > 0) {
            $order = [];
            foreach ($sorts as $sort_field) {
                $additional_select[] = $sort_field['field']->getQuerySelect();
                $additional_from[]   = $sort_field['field']->getQueryFrom();
                $order[]             = $sort_field['field']->getQueryOrderby() . ' ' . ($sort_field['is_desc'] ? 'DESC' : 'ASC');
            }
        }

        $where_statement = EasyStatement::open()
            ->in('c.id IN (?*)', explode(',', $matching_ids['last_changeset_id']));

        $where = "WHERE $where_statement";

        $sys_server_join = $this->getNumberServerJoin();

        $can_be_sorted = $this->columnsCanBeTechnicallySorted(array_chunk($additional_select, $sys_server_join))
            && $this->columnsCanBeTechnicallySorted(array_chunk($additional_from, $sys_server_join));


        if (! empty($additional_select) && $can_be_sorted) {
            $select .= ', ' . implode(',', $additional_select);
            $from   .= implode("", $additional_from);
        }

        $query = $select . $from . $where;
        $limit = " LIMIT ?, ?";


        if (! empty($order)) {
            $query .= " ORDER BY " . implode(', ', $order);
        }

        $query .= $limit;

        $results = $dao->safeQuery($query, array_merge($where_statement->values(), [$offset, $this->chunksz]));

        $matching_ids_from_result                      = [];
        $matching_ids_from_result["last_changeset_id"] = "";
        $matching_ids_from_result["id"]                = "";

        if ($results && is_array($results)) {
            $matching_ids_from_result["last_changeset_id"] = implode(',', array_column($results, 'changeset_id'));
            $matching_ids_from_result["id"]                = implode(',', array_column($results, 'id'));
        }

        return $matching_ids_from_result;
    }

    private function getBaseQueryFrom(): string
    {
        return " FROM tracker_artifact AS a INNER JOIN tracker_changeset AS c ON (c.artifact_id = a.id) ";
    }

    /**
     * @param bool|array $aggregates
     */
    private function getBaseQuerySelect($aggregates): string
    {
        if ($aggregates) {
            $select = " SELECT 1 ";
        } else {
            $select = " SELECT a.id AS id, c.id AS changeset_id ";
        }
        return $select;
    }

    private function getNumberServerJoin(): int
    {
        $sys_server_join = (ForgeConfig::getInt('sys_server_join')) - 3;
        if ($sys_server_join <= 0) { //make sure that the admin is not dumb
            return 20; //default mysql 60 / 3 (max of 3 joins per field)
        }

        return $sys_server_join;
    }

    private function getLegacyDataAccess(): \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface
    {
        return CodendiDataAccess::instance();
    }
}
