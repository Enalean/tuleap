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

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Export\Pdf\Template\Identifier\InvalidPdfTemplateIdentifierStringException;
use Tuleap\Export\Pdf\Template\Identifier\PdfTemplateIdentifierFactory;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\PdfTemplate\DeleteTemplate;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\NotFoundException;

final class DeletePdfTemplateController extends DispatchablePSR15Compatible
{
    public const ROUTE = '/pdftemplate/admin/delete';

    public function __construct(
        private readonly RedirectWithFeedbackFactory $redirect_with_feedback_factory,
        private readonly LoggerInterface $logger,
        private readonly DeleteTemplate $deletor,
        private readonly PdfTemplateIdentifierFactory $identifier_factory,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $request->getAttribute(\PFUser::class);
        if (! $user instanceof \PFUser) {
            throw new \LogicException('PFUser is missing');
        }

        $parsed_body = $request->getParsedBody();

        $id = (string) ($parsed_body['id'] ?? '');
        if (! $id) {
            throw new NotFoundException();
        }

        try {
            $identifier = $this->identifier_factory->buildFromHexadecimalString($id);
        } catch (InvalidPdfTemplateIdentifierStringException) {
            throw new NotFoundException();
        }


        $this->deletor->delete($identifier);

        $this->logger->info(
            sprintf(
                'User %s(%d) deleted template %s.',
                $user->getUserName(),
                $user->getId(),
                $identifier->toString(),
            ),
        );

        return $this->redirect_with_feedback_factory->createResponseForUser(
            $user,
            IndexPdfTemplateController::ROUTE,
            NewFeedback::success(dgettext('tuleap-pdftemplate', 'The template has been deleted')),
        );
    }
}
