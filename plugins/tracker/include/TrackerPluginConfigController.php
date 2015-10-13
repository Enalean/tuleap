<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class TrackerPluginConfigController {

    private static $TEMPLATE = 'siteadmin-config';

    /** @var TrackerPluginConfig */
    private $config;

    /** @var Config_LocalIncFinder */
    private $localincfinder;

    public function __construct(TrackerPluginConfig $config, Config_LocalIncFinder $localincfinder) {
        $this->config         = $config;
        $this->localincfinder = $localincfinder;
    }

    public function index(CSRFSynchronizerToken $csrf, Response $response) {
        $title  = $GLOBALS['Language']->getText('plugin_tracker_config', 'title');
        $params = array(
            'title' => $title
        );
        $renderer = TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR);

        $response->header($params);
        $renderer->renderToPage(
            self::$TEMPLATE,
            new TrackerPluginConfigPresenter(
                $csrf,
                $title,
                $this->localincfinder->getLocalIncPath(),
                $this->config
            )
        );
        $response->footer($params);
    }

    public function update(Codendi_Request $request, Response $response) {
        $emailgateway_mode = $request->get('emailgateway_mode');
        if ($emailgateway_mode && $this->config->setEmailgatewayMode($emailgateway_mode)) {
            $response->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('admin_main', 'successfully_updated'));
        }
        $response->redirect($_SERVER['REQUEST_URI']);
    }
}
