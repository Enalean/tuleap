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

namespace Tuleap\Captcha\Administration;

use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Captcha\Configuration;
use Tuleap\Captcha\ConfigurationDataAccessException;
use Tuleap\Captcha\ConfigurationMalformedDataException;
use Tuleap\Captcha\ConfigurationSaver;

class Controller
{
    /**
     * @var ConfigurationSaver
     */
    private $saver;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var AdminPageRenderer
     */
    private $renderer;
    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(Configuration $configuration, ConfigurationSaver $saver, AdminPageRenderer $renderer)
    {
        $this->saver         = $saver;
        $this->csrf_token    = new CSRFSynchronizerToken(CAPTCHA_BASE_URL . '/admin/');
        $this->renderer      = $renderer;
        $this->configuration = $configuration;
    }

    public function display()
    {
        $presenter = new Presenter($this->csrf_token, $this->configuration);
        $this->renderer->renderAPresenter(
            dgettext('tuleap-captcha', 'Captcha configuration'),
            CAPTCHA_TEMPLATE_DIR,
            'configuration',
            $presenter
        );
    }

    public function processFormSubmission(HTTPRequest $request)
    {
        $this->csrf_token->check();

        try {
            $this->saver->save($request->get('site_key'), $request->get('secret_key'));
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-captcha', 'Your keys has been successfully saved')
            );
        } catch (ConfigurationMalformedDataException $ex) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-captcha', 'The provided keys are not valid')
            );
        } catch (ConfigurationDataAccessException $ex) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-captcha', 'An error occurred while saving your keys, please retry')
            );
        }

        $GLOBALS['Response']->redirect(CAPTCHA_BASE_URL . '/admin/');
    }
}
