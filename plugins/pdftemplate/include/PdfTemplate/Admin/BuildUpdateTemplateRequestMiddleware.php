<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\PdfTemplate\Admin;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Export\Pdf\Template\Identifier\InvalidPdfTemplateIdentifierStringException;
use Tuleap\Export\Pdf\Template\Identifier\PdfTemplateIdentifierFactory;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\PdfTemplate\PdfTemplateBuilder;
use Tuleap\PdfTemplate\RetrieveTemplate;
use Tuleap\Request\NotFoundException;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;

final readonly class BuildUpdateTemplateRequestMiddleware implements MiddlewareInterface
{
    public function __construct(
        private RedirectWithFeedbackFactory $redirect_with_feedback_factory,
        private PdfTemplateIdentifierFactory $identifier_factory,
        private RetrieveTemplate $retriever,
        private ProvideUserAvatarUrl $provide_user_avatar_url,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $request->getAttribute(\PFUser::class);
        if (! $user instanceof \PFUser) {
            throw new \LogicException('PFUser is missing');
        }

        $id = $request->getAttribute('id');
        if (! is_string($id)) {
            throw new NotFoundException();
        }

        try {
            $identifier = $this->identifier_factory->buildFromHexadecimalString($id);
        } catch (InvalidPdfTemplateIdentifierStringException) {
            throw new NotFoundException();
        }

        $original_template = $this->retriever->retrieveTemplate($identifier);
        if (! $original_template) {
            throw new NotFoundException();
        }

        $parsed_body = $request->getParsedBody();

        $label = (string) ($parsed_body['label'] ?? '');
        if (! $label) {
            return $this->redirect_with_feedback_factory->createResponseForUser(
                $user,
                PdfTemplatePresenter::fromPdfTemplate($original_template, $user, $this->provide_user_avatar_url)->update_url,
                NewFeedback::error(dgettext('tuleap-pdftemplate', 'The template label is mandatory')),
            );
        }

        $enriched_request = $request->withAttribute(
            UpdateTemplateRequest::class,
            new UpdateTemplateRequest(
                $original_template,
                PdfTemplateBuilder::build(
                    $original_template->identifier,
                    $label,
                    (string) ($parsed_body['description'] ?? ''),
                    (string) ($parsed_body['style'] ?? ''),
                    (string) ($parsed_body['title-page-content'] ?? ''),
                    (string) ($parsed_body['header-content'] ?? ''),
                    (string) ($parsed_body['footer-content'] ?? ''),
                    $user,
                    new \DateTimeImmutable(),
                )
            ),
        );

        return $handler->handle($enriched_request);
    }
}
