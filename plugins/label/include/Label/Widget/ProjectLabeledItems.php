<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Label\Widget;

use DataAccessException;
use Feedback;
use ProjectManager;
use Tuleap\Project\Label\LabelDao;
use Widget;

class ProjectLabeledItems extends Widget
{
    const NAME = 'projectlabeleditems';

    /**
     * @var ProjectLabelBuilder
     */
    private $labels_presenter_builder;

    /**
     * @var ProjectLabelConfigRetriever
     */
    private $config_retriever;

    /**
     * @var ProjectLabelRequestDataValidator
     */
    private $project_data_validator;

    /**
     * @var ProjectLabelRetriever
     */
    private $labels_retriever;

    /**
     * @var Dao
     */
    private $dao;

    /**
     * @var \TemplateRenderer
     */
    private $renderer;

    public function __construct()
    {
        parent::__construct(self::NAME);

        $this->renderer = \TemplateRendererFactory::build()->getRenderer(
            LABEL_BASE_DIR . '/templates/widgets'
        );

        $this->dao                      = new Dao();
        $this->labels_retriever         = new ProjectLabelRetriever(new LabelDao());
        $this->project_data_validator   = new ProjectLabelRequestDataValidator();
        $this->config_retriever         = new ProjectLabelConfigRetriever(new ProjectLabelConfigDao());
        $this->labels_presenter_builder = new ProjectLabelBuilder();
    }

    public function loadContent($id)
    {
        $this->content_id = $id;
    }

    public function getTitle()
    {
        return dgettext('tuleap-label', 'Labeled Items');
    }

    public function getDescription()
    {
        return dgettext('tuleap-label', 'Displays items with configured labels in the project. For example you can search all Pull Requests labeled "Emergency" and "v3.0"');
    }

    public function isUnique()
    {
        return false;
    }

    public function getContent()
    {
        return $this->renderer->renderToString(
            'project-labeled-items',
            new ProjectLabeledItemsPresenter()
        );
    }

    public function getPreferences($widget_id)
    {
        $labels = $this->getProjectLabelsPresenter($widget_id);

        return $this->renderer->renderToString(
            'project-label-selector',
            new ProjectLabelSelectorPresenter($labels)
        );
    }

    public function create(&$request)
    {
        $content_id = $this->dao->create();

        return $content_id;
    }


    public function updatePreferences(&$request)
    {
        try {
            $labels = $this->getProjectLabelsPresenter($request->get('widget-id'));
            $this->project_data_validator->validateDataFromRequest($request, $labels);
            $this->dao->storeLabelsConfiguration($this->content_id, $request->get('project-labels'));
        } catch (ProjectLabelDoesNotBelongToProjectException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-label', 'Error: label does not belong to project or does not exist.')
            );
        } catch (ProjectLabelAreNotValidException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-label', 'Error: one projects label is invalid.')
            );
        } catch (ProjectLabelAreMandatoryException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-label', 'Error: you should specify at least one project label.')
            );
        } catch (DataAccessException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-label', 'An exception occurred while saving data.')
            );
        }
    }

    protected function getProjectLabelsPresenter($widget_id)
    {
        $project_id     = $this->dao->getProjectIdByWidgetAndContentId($widget_id, $this->content_id);
        $project        = ProjectManager::instance()->getProject($project_id);
        $project_labels = $this->labels_retriever->getLabelsByProject($project);
        $config_label   = $this->config_retriever->getLabelConfig($this->content_id);

        $labels = $this->labels_presenter_builder->build($project_labels, $config_label);

        return $labels;
    }
}
