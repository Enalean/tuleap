<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\User\Account;

use CSRFSynchronizerToken;
use HTTPRequest;
use Psr\EventDispatcher\EventDispatcherInterface;
use TemplateRenderer;
use TemplateRendererFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\User\Account\Appearance\AppearancePresenterBuilder;

final class DisplayAppearanceController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public const URL = '/account/appearance';

    /**
     * @var TemplateRenderer
     */
    private $renderer;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var AppearancePresenterBuilder
     */
    private $appareance_presenter_builder;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        TemplateRendererFactory $renderer_factory,
        CSRFSynchronizerToken $csrf_token,
        AppearancePresenterBuilder $appareance_presenter_builder,
    ) {
        $this->dispatcher                   = $dispatcher;
        $this->renderer                     = $renderer_factory->getRenderer(__DIR__ . '/templates');
        $this->csrf_token                   = $csrf_token;
        $this->appareance_presenter_builder = $appareance_presenter_builder;
    }

    /**
     * @inheritDoc
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $user = $request->getCurrentUser();
        if ($user->isAnonymous()) {
            throw new ForbiddenException();
        }

        $layout->addJavascriptAsset(
            new JavascriptAsset(
                new \Tuleap\Layout\IncludeAssets(__DIR__ . '/../../../scripts/account/frontend-assets', '/assets/core/account'),
                'appearance.js'
            )
        );

        $tabs = $this->dispatcher->dispatch(new AccountTabPresenterCollection($user, self::URL));

        $presenter = $this->appareance_presenter_builder->getAppareancePresenterForUser(
            $this->csrf_token,
            $tabs,
            $user
        );

        (new UserPreferencesHeader())->display(_('Appearance & language'), $layout);
        $this->renderer->renderToPage('appearance', $presenter);
        $layout->footer([]);
    }

    public static function getCSRFToken(): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(self::URL);
    }
}
