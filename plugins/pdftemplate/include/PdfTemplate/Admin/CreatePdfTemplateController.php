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
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\PdfTemplate\CreateTemplate;
use Tuleap\PdfTemplate\Variable\VariableMisusageInTemplateDetector;
use Tuleap\Request\DispatchablePSR15Compatible;

final class CreatePdfTemplateController extends DispatchablePSR15Compatible
{
    public function __construct(
        private readonly RedirectWithFeedbackFactory $redirect_with_feedback_factory,
        private readonly LoggerInterface $logger,
        private readonly CreateTemplate $creator,
        private readonly VariableMisusageInTemplateDetector $variable_misusage_detector,
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

        $label = (string) ($parsed_body['label'] ?? '');
        if (! $label) {
            return $this->redirect_with_feedback_factory->createResponseForUser(
                $user,
                DisplayPdfTemplateCreationFormController::ROUTE,
                NewFeedback::error(dgettext('tuleap-pdftemplate', 'The template label is mandatory')),
            );
        }

        $template = $this->creator->create(
            $label,
            (string) ($parsed_body['description'] ?? ''),
            (string) ($parsed_body['style'] ?? ''),
            (string) ($parsed_body['title-page-content'] ?? ''),
            (string) ($parsed_body['header-content'] ?? ''),
            (string) ($parsed_body['footer-content'] ?? ''),
            $user,
            new \DateTimeImmutable(),
        );

        $this->logger->info(
            sprintf(
                'User %s(%d) created a new template %s(%s).',
                $user->getUserName(),
                $user->getId(),
                $template->label,
                $template->identifier->toString(),
            ),
        );

        return $this->redirect_with_feedback_factory->createResponseForUser(
            $user,
            IndexPdfTemplateController::ROUTE,
            NewFeedback::success(dgettext('tuleap-pdftemplate', 'The template has been created')),
            ...array_map(
                static fn (string $misusage) => NewFeedback::warn($misusage),
                $this->variable_misusage_detector->detectVariableMisusages($template),
            ),
        );
    }
}
