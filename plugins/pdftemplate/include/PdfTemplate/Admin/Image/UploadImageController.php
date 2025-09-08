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

namespace Tuleap\PdfTemplate\Admin\Image;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\PdfTemplate\Image\CreateImage;
use Tuleap\PdfTemplate\Image\Identifier\PdfTemplateImageIdentifierFactory;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\ForbiddenException;

final class UploadImageController extends DispatchablePSR15Compatible
{
    public const ROUTE = '/pdftemplate/admin/images/upload';

    public function __construct(
        private readonly RedirectWithFeedbackFactory $redirect_with_feedback_factory,
        private readonly CreateImage $image_creator,
        private readonly PdfTemplateImageIdentifierFactory $image_identifier_factory,
        private readonly StorePdfTemplateImage $storage,
        private readonly LoggerInterface $logger,
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

        if (! isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
            throw new ForbiddenException();
        }

        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            return $this->redirect_with_feedback_factory->createResponseForUser(
                $user,
                IndexImagesController::ROUTE,
                NewFeedback::error(dgettext('tuleap-pdftemplate', 'An error occurred while uploading your image.')),
            );
        }

        $identifier = $this->image_identifier_factory->buildIdentifier();
        if (! $this->storage->storeUploadedImage($_FILES['image']['tmp_name'], $identifier)) {
            return $this->redirect_with_feedback_factory->createResponseForUser(
                $user,
                IndexImagesController::ROUTE,
                NewFeedback::error(dgettext('tuleap-pdftemplate', 'An error occurred while storing your image.')),
            );
        }
        $image = $this->image_creator->create(
            $identifier,
            $_FILES['image']['name'],
            $_FILES['image']['size'],
            $user,
            new \DateTimeImmutable(),
        );

        $this->logger->info(
            sprintf(
                'User %s(%d) uploaded a new image %s(%s).',
                $user->getUserName(),
                $user->getId(),
                $image->filename,
                $identifier->toString(),
            ),
        );

        return $this->redirect_with_feedback_factory->createResponseForUser(
            $user,
            IndexImagesController::ROUTE,
            NewFeedback::success(dgettext('tuleap-pdftemplate', 'The image has been uploaded.')),
        );
    }
}
