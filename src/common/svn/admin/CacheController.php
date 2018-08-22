<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\SvnCore\Admin;

use CSRFSynchronizerToken;
use Exception;
use Feedback;
use ForgeConfig;
use HTTPRequest;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\SvnCore\Cache\Parameters;
use Tuleap\SvnCore\Cache\ParameterSaver;

class CacheController implements Controller
{
    /**
     * @var Parameters
     */
    private $parameters;
    /**
     * @var ParameterSaver
     */
    private $parameter_saver;
    /**
     * @var AdminPageRenderer
     */
    private $renderer;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(
        Parameters $parameters,
        ParameterSaver $parameter_saver,
        AdminPageRenderer $renderer,
        CSRFSynchronizerToken $csrf_token
    ) {
        $this->parameters      = $parameters;
        $this->parameter_saver = $parameter_saver;
        $this->renderer        = $renderer;
        $this->csrf_token      = $csrf_token;
    }

    public function process(HTTPRequest $request)
    {
        if ($request->isPost()) {
            $this->processFormSubmission($request);
        }
        $this->display();
    }

    private function processFormSubmission(HTTPRequest $request)
    {
        $this->csrf_token->check();

        try {
            $this->parameter_saver->save(
                $request->get('cache-maximum-credentials'),
                $request->get('cache-lifetime')
            );
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('svn_cache', 'save_success')
            );
        } catch (Exception $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('svn_cache', 'save_failure')
            );
        }

        $GLOBALS['Response']->redirect('/admin/svn/index.php?pane=cache');
    }

    private function display()
    {
        $presenter = new CachePresenter($this->parameters, $this->csrf_token);

        $this->renderer->renderANoFramedPresenter(
            $this->getTitle(),
            ForgeConfig::get('codendi_dir') . '/src/templates/svn',
            'admin',
            $presenter
        );
    }

    /**
     * @return string
     */
    private function getTitle()
    {
        return $GLOBALS['Language']->getText('svn_admin_index', 'admin');
    }
}
