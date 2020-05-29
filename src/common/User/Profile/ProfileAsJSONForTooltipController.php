<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\User\Profile;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use PFUser;
use Psr\Http\Message\ResponseFactoryInterface;
use TemplateRendererFactory;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Layout\TooltipJSON;

class ProfileAsJSONForTooltipController
{
    /**
     * @var JSONResponseBuilder
     */
    private $json_response_builder;
    /**
     * @var EmitterInterface
     */
    private $emitter;
    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var TemplateRendererFactory
     */
    private $template_renderer_factory;

    public function __construct(
        JSONResponseBuilder $json_response_builder,
        EmitterInterface $emitter,
        ResponseFactoryInterface $response_factory,
        TemplateRendererFactory $template_renderer_factory
    ) {
        $this->json_response_builder     = $json_response_builder;
        $this->emitter                   = $emitter;
        $this->response_factory          = $response_factory;
        $this->template_renderer_factory = $template_renderer_factory;
    }

    public function process(PFUser $current_user, PFUser $user): void
    {
        if ($current_user->isAnonymous()) {
            $this->emitter->emit($this->response_factory->createResponse(403));

            return;
        }

        $renderer = $this->template_renderer_factory->getRenderer(__DIR__);
        $output   = new TooltipJSON(
            $renderer->renderToString('tooltip-title', $user),
            $renderer->renderToString('tooltip-body', $user),
        );

        $response = $this->json_response_builder->fromData($output);
        $this->emitter->emit($response);
    }
}
