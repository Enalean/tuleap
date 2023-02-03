<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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

namespace Tuleap\MyTuleapContactSupport;

use HTTPRequest;
use ForgeConfig;
use TemplateRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\MyTuleapContactSupport\Presenter\FormPresenter;
use Tuleap\MyTuleapContactSupport\Presenter\ModalPresenter;

class ContactSupportFormController implements DispatchableWithRequest
{
    /** @var TemplateRenderer */
    private $renderer;

    public function __construct(TemplateRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $modal_presenter = new ModalPresenter(
            $this->getFormPresenter(),
            $this->getHelpPageContent()
        );

        $is_burning_parrot_compatible = (bool) $request->get('is-burning-parrot-compatible');
        $mustache_template            = 'modal-flaming-parrot';
        if ($is_burning_parrot_compatible) {
            $mustache_template = 'modal-burning-parrot';
        }

        echo $this->renderer->renderToString($mustache_template, $modal_presenter);
    }

    private function getHelpPageContent()
    {
        ob_start();
        include($GLOBALS['Language']->getContent('help/site'));
        return ob_get_clean();
    }

    public function getFormContent(): string
    {
        $form_presenter = $this->getFormPresenter();

        return $this->renderer->renderToString('form-burning-parrot', $form_presenter);
    }

    private function getFormPresenter(): FormPresenter
    {
        return new FormPresenter(
            ForgeConfig::get('sys_email_admin')
        );
    }
}
