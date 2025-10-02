<?php
/**
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
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

use Tuleap\AgileDashboard\FormElement\BurnupFieldRetriever;
use Tuleap\AgileDashboard\Milestone\Backlog\BacklogItemCollectionFactory;
use Tuleap\AgileDashboard\Milestone\Backlog\MilestoneBacklogFactory;
use Tuleap\AgileDashboard\Milestone\Pane\Details\DetailsPresenterBuilder;

/**
 * Like RepRap, I build builders
 */
class AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    public function __construct(
        private readonly MilestoneBacklogFactory $backlog_factory,
        private readonly BacklogItemCollectionFactory $row_collection_factory,
        private readonly BurnupFieldRetriever $field_retriever,
        private readonly EventManager $event_manager,
    ) {
    }

    /**
     * @return DetailsPresenterBuilder
     */
    public function getDetailsPresenterBuilder()
    {
        return new DetailsPresenterBuilder(
            $this->backlog_factory,
            $this->row_collection_factory,
            $this->field_retriever,
            $this->event_manager
        );
    }
}
