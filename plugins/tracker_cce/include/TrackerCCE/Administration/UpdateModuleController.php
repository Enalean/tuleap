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

namespace Tuleap\TrackerCCE\Administration;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use PFUser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\TrackerCCE\WASM\WASMModulePathHelper;

final class UpdateModuleController extends DispatchablePSR15Compatible
{
    public function __construct(
        private readonly RedirectWithFeedbackFactory $redirect_with_feedback_factory,
        private readonly LoggerInterface $logger,
        private readonly WASMModulePathHelper $module_path_helper,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tracker = $request->getAttribute(\Tracker::class);
        if (! $tracker instanceof \Tracker) {
            throw new \LogicException('Tracker is missing');
        }

        $user = $request->getAttribute(\PFUser::class);
        if (! $user instanceof \PFUser) {
            throw new \LogicException('PFUser is missing');
        }

        return $this->getUploadedModule($request)
            ->andThen($this->getMovableModule(...))
            ->andThen(fn (UploadedFileInterface $file) => $this->moveFile($tracker, $file))
            ->match(
                fn () => $this->redirectWithFeedback(
                    $user,
                    $tracker,
                    NewFeedback::success(dgettext('tuleap-tracker_cce', 'The module has been uploaded'))
                ),
                fn (Fault $fault) => $this->redirectWithFeedback(
                    $user,
                    $tracker,
                    NewFeedback::error((string) $fault),
                )
            );
    }

    private function getUploadedModule(ServerRequestInterface $request): Ok|Err
    {
        $files = $request->getUploadedFiles();
        if (! isset($files['wasm-module'])) {
            return Result::err(
                Fault::fromMessage(dgettext('tuleap-tracker_cce', 'No module has been uploaded'))
            );
        }

        return Result::ok($files['wasm-module']);
    }

    private function getMovableModule(UploadedFileInterface $uploaded_file): Ok|Err
    {
        return match ($uploaded_file->getError()) {
            UPLOAD_ERR_OK => Result::ok($uploaded_file),
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => Result::err(Fault::fromMessage(
                dgettext('tuleap-tracker_cce', 'The uploaded file exceeds the maximum allowed file size.')
            )),
            UPLOAD_ERR_PARTIAL => Result::err(Fault::fromMessage(
                dgettext('tuleap-tracker_cce', 'The uploaded file was only partially uploaded.')
            )),
            UPLOAD_ERR_NO_FILE => Result::err(Fault::fromMessage(
                dgettext('tuleap-tracker_cce', 'No file was uploaded.')
            )),
            UPLOAD_ERR_NO_TMP_DIR => Result::err(Fault::fromMessage(
                dgettext('tuleap-tracker_cce', 'Missing a temporary folder.')
            )),
            UPLOAD_ERR_CANT_WRITE => Result::err(Fault::fromMessage(
                dgettext('tuleap-tracker_cce', 'Failed to write file to disk.')
            )),
            UPLOAD_ERR_EXTENSION => Result::err(Fault::fromMessage(
                dgettext('tuleap-tracker_cce', 'File upload stopped by extension.')
            )),
        };
    }

    private function moveFile(\Tracker $tracker, UploadedFileInterface $uploaded_file): Ok|Err
    {
        try {
            $path   = $this->module_path_helper->getPathForTracker($tracker);
            $folder = dirname($path);
            if (! is_dir($folder) && ! mkdir($folder, 0700, true) && ! is_dir($folder)) {
                throw new \Exception(sprintf('Directory "%s" was not created', $folder));
            }

            $uploaded_file->moveTo($path);

            return Result::ok(null);
        } catch (\Exception $exception) {
            $this->logger->error('Unable to upload the WASM module for tracker ' . $tracker->getId(), ['exception' => $exception]);

            return Result::err(Fault::fromMessage(dgettext('tuleap-tracker_cce', 'Unable to upload the module')));
        }
    }

    private function redirectWithFeedback(PFUser $user, \Tracker $tracker, NewFeedback $feedback): ResponseInterface
    {
        return $this->redirect_with_feedback_factory->createResponseForUser(
            $user,
            AdministrationController::getUrl($tracker),
            $feedback,
        );
    }

    public static function getUrl(\Tracker $tracker): string
    {
        return '/tracker_cce/' . urlencode((string) $tracker->getId()) . '/admin';
    }
}
