<?php
/**
 * Copyright (c) Enalean, 2011 - 2017. All Rights Reserved.
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

class Widget_ProjectClassification extends Widget {

    public function __construct()
    {
        parent::__construct('projectclassification');
    }

    public function getTitle()
    {
        return $GLOBALS['Language']->getText('include_project_home','project_classification');
    }

    public function getContent()
    {
        if (ForgeConfig::get('sys_use_trove') != 0) {
            $renderer = TemplateRendererFactory::build()->getRenderer(
                ForgeConfig::get('tuleap_dir') . '/src/templates/widgets'
            );

            $request = HTTPRequest::instance();

            $collection_retriever = new \Tuleap\Trove\TroveCatCollectionRetriever(new TroveCatDao());

            return $renderer->renderToString(
                'project-classification',
                array(
                    'trovecats' => $collection_retriever->getCollection($request->getProject()->getID())
                )
            );
        }
        return '';
    }

    function getDescription()
    {
        return $GLOBALS['Language']->getText('widget_description_project_classification','description');
    }
}
