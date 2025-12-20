<?php
/**
 * Copyright (c) Enalean, 2013 - present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\Artifact\Renderer;

use EventManager;
use Tuleap\HTTPRequest;
use Tuleap\Tracker\Artifact\Event\IsArtifactViewInBurningParrotEvent;
use Tuleap\Tracker\Artifact\View\TrackerArtifactView;

final class ViewCollection
{
    /** @var TrackerArtifactView[] */
    public $views = [];

    public function __construct(private readonly EventManager $event_manager)
    {
    }

    public function add(TrackerArtifactView $view): void
    {
        $this->views[$view->getIdentifier()] = $view;
    }

    public function fetchRequestedView(\Tuleap\HTTPRequest $request): string
    {
        $requested_view = $this->getRequestedView($request);

        $is_burning_parrot = $this->event_manager
            ->dispatch(new IsArtifactViewInBurningParrotEvent(HTTPRequest::instance()))
            ->isBurningParrot();
        if ($is_burning_parrot) {
            $html  = '';
            $html .= $this->fetchTabs($requested_view);
            $html .= '<div class="tlp-framed">';
            $html .= $requested_view->fetch();
            $html .= '</div>';
            return $html;
        }

        $event = new GetAdditionalCssAssetsForArtifactDisplay($requested_view->getIdentifier());
        $this->event_manager->dispatch($event);
        foreach ($event->getCssAssets() as $asset) {
            $GLOBALS['HTML']->addCssAsset($asset);
        }

        $html  = '';
        $html .= $this->fetchFlamingParrotTabs($requested_view);
        $html .= '<div class="tracker-artifact-view-content">';
        $html .= $requested_view->fetch();
        $html .= '</div>';

        return $html;
    }

    /**
     * @return TrackerArtifactView
     */
    private function getRequestedView(\Tuleap\HTTPRequest $request)
    {
        if (isset($this->views[$request->get('view')])) {
            return $this->views[$request->get('view')];
        } else {
            return current($this->views);
        }
    }

    /**
     * Display tabs to let user choose its view
     *
     * @return string html
     */
    private function fetchFlamingParrotTabs(TrackerArtifactView $current_view)
    {
        $html  = '';
        $html .= '<div class="main-project-tabs"><ul class="nav nav-tabs tracker-artifact-nav">';
        foreach ($this->views as $view) {
            $class = '';
            if ($view == $current_view) {
                $class = 'active';
            }
            $html .= '<li class="' . $class . '">
                <a href="' . $view->getURL() . '" data-test="' . $view->getIdentifier() . '">' . $view->getTitle() . '</a>
            </li>';
        }
        $html .= '</ul></div>';
        return $html;
    }

    private function fetchTabs(TrackerArtifactView $current_view): string
    {
        $html = '<div class="main-project-tabs"><nav class="tlp-tabs">';
        foreach ($this->views as $view) {
            $tab_active_class = $view === $current_view ? ' tlp-tab-active' : '';
            $html            .= '<a class="tlp-tab' . $tab_active_class . '" href="' . $view->getURL() . '" data-test="' . $view->getIdentifier() . '">' . $view->getTitle() . '</a>';
        }
        $html .= '</nav></div>';
        return $html;
    }
}
