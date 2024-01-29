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

namespace Tuleap\TrackerFunctions\Administration;

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
use Tuleap\TrackerFunctions\WASM\WASMFunctionPathHelper;

final class UpdateFunctionController extends DispatchablePSR15Compatible
{
    public function __construct(
        private readonly RedirectWithFeedbackFactory $redirect_with_feedback_factory,
        private readonly LoggerInterface $logger,
        private readonly WASMFunctionPathHelper $function_path_helper,
        private readonly LogFunctionUploaded $history_saver,
        private readonly UpdateFunctionActivation $function_activation,
        private readonly MaxSizeProvider $max_size_provider,
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

        return $this->getUploadedFunction($request)
            ->andThen($this->getMovableFunction(...))
            ->andThen(fn (UploadedFileInterface $file) => $this->checkFunctionSize($file))
            ->andThen(fn (UploadedFileInterface $file) => $this->moveFile($tracker, $file))
            ->andThen(fn () => $this->activateFunction($tracker))
            ->andThen(fn () => $this->logInProjectHistory($user, $tracker))
            ->match(
                fn () => $this->redirectWithFeedback(
                    $user,
                    $tracker,
                    NewFeedback::success(dgettext('tuleap-tracker_functions', 'The function has been uploaded'))
                ),
                fn (Fault $fault) => $this->redirectWithFeedback(
                    $user,
                    $tracker,
                    NewFeedback::error((string) $fault),
                )
            );
    }

    private function getUploadedFunction(ServerRequestInterface $request): Ok|Err
    {
        $files = $request->getUploadedFiles();
        if (! isset($files['wasm-function'])) {
            return Result::err(
                Fault::fromMessage(dgettext('tuleap-tracker_functions', 'No function has been uploaded'))
            );
        }

        return Result::ok($files['wasm-function']);
    }

    private function getMovableFunction(UploadedFileInterface $uploaded_file): Ok|Err
    {
        return match ($uploaded_file->getError()) {
            UPLOAD_ERR_OK => Result::ok($uploaded_file),
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => Result::err(Fault::fromMessage(
                dgettext('tuleap-tracker_functions', 'The uploaded file exceeds the maximum allowed file size.')
            )),
            UPLOAD_ERR_PARTIAL => Result::err(Fault::fromMessage(
                dgettext('tuleap-tracker_functions', 'The uploaded file was only partially uploaded.')
            )),
            UPLOAD_ERR_NO_FILE => Result::err(Fault::fromMessage(
                dgettext('tuleap-tracker_functions', 'No file was uploaded.')
            )),
            UPLOAD_ERR_NO_TMP_DIR => Result::err(Fault::fromMessage(
                dgettext('tuleap-tracker_functions', 'Missing a temporary folder.')
            )),
            UPLOAD_ERR_CANT_WRITE => Result::err(Fault::fromMessage(
                dgettext('tuleap-tracker_functions', 'Failed to write file to disk.')
            )),
            UPLOAD_ERR_EXTENSION => Result::err(Fault::fromMessage(
                dgettext('tuleap-tracker_functions', 'File upload stopped by extension.')
            )),
        };
    }

    private function moveFile(\Tracker $tracker, UploadedFileInterface $uploaded_file): Ok|Err
    {
        try {
            $path   = $this->function_path_helper->getPathForTracker($tracker);
            $folder = dirname($path);
            if (! is_dir($folder) && ! mkdir($folder, 0700, true) && ! is_dir($folder)) {
                throw new \Exception(sprintf('Directory "%s" was not created', $folder));
            }

            $uploaded_file->moveTo($path);

            return Result::ok(null);
        } catch (\Exception $exception) {
            $this->logger->error('Unable to upload the WASM function for tracker ' . $tracker->getId(), ['exception' => $exception]);

            return Result::err(Fault::fromMessage(dgettext('tuleap-tracker_functions', 'Unable to upload the function')));
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
        return '/tracker_functions/' . urlencode((string) $tracker->getId()) . '/admin';
    }

    /**
     * @return Ok<null>
     */
    private function logInProjectHistory(\PFUser $user, \Tracker $tracker): Ok
    {
        $this->history_saver->logFunctionUploaded($user, $tracker);

        return Result::ok(null);
    }

    /**
     * @return Ok<null>
     */
    private function activateFunction(\Tracker $tracker): Ok
    {
        $this->function_activation->activateFunction($tracker->getId());

        return Result::ok(null);
    }

    /**
     * @return Ok<UploadedFileInterface>|Err<Fault>
     */
    private function checkFunctionSize(UploadedFileInterface $file): Ok | Err
    {
        $max_function_size_in_mb = $this->max_size_provider->getMaxSizeForFunctionInMb();

        if ($file->getSize() > $max_function_size_in_mb * (1024 ** 2)) {
            return Result::err(Fault::fromMessage(
                sprintf(
                    dgettext('tuleap-tracker_functions', 'The maximum file size for the function is %sMB.'),
                    $max_function_size_in_mb,
                )
            ));
        }

        return Result::ok($file);
    }
}
