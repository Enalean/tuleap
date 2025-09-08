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

namespace Tuleap\Project\Registration\Template\Upload;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Event\Dispatchable;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class ArchiveWithoutDataChecker implements CheckArchiveContent, Dispatchable
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public function checkArchiveContent(\SimpleXMLElement $xml_element): Ok|Err
    {
        $errors = new ArchiveWithoutDataCheckerErrorCollection($xml_element, $this->logger);

        $this->checkArchiveDoesNotContainUsers($xml_element, $errors);
        $this->checkFrsDoesNotContainPackages($xml_element, $errors);
        $this->checkPluginsDoNotContainData($errors);

        return $this->returnXmlOrError($xml_element, $errors);
    }

    private function checkArchiveDoesNotContainUsers(\SimpleXMLElement $xml_element, ArchiveWithoutDataCheckerErrorCollection $errors): void
    {
        $this->logger->debug('Checking that archive does not contain users');
        if (! empty($xml_element->xpath('//ugroups/ugroup/members/member'))) {
            $errors->addError(gettext('Archive should not contain users.'));
        }
    }

    private function checkFrsDoesNotContainPackages(\SimpleXMLElement $xml_element, ArchiveWithoutDataCheckerErrorCollection $errors): void
    {
        $this->logger->debug('Checking that archive does not contain FRS packages');
        if (! empty($xml_element->xpath('//frs/package'))) {
            $errors->addError(gettext('Archive should not contain FRS packages.'));
        }
    }

    private function checkPluginsDoNotContainData(ArchiveWithoutDataCheckerErrorCollection $errors): void
    {
        $this->logger->debug('Checking that plugins do not contain data');
        $this->dispatcher->dispatch($errors);
    }

    /**
     * @return Ok<\SimpleXMLElement>|Err<Fault>
     */
    private function returnXmlOrError(\SimpleXMLElement $xml_element, ArchiveWithoutDataCheckerErrorCollection $collection): Ok|Err
    {
        $errors = $collection->getErrors();
        if (count($errors) > 0) {
            return Result::err(Fault::fromMessage(implode(PHP_EOL, $errors)));
        }

        return Result::ok($xml_element);
    }
}
