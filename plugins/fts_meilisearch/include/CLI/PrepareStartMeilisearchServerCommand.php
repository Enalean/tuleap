<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\FullTextSearchMeilisearch\CLI;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\FullTextSearchMeilisearch\Index\Asynchronous\ProcessPendingItemsToIndexTask;
use Tuleap\Option\Option;
use Tuleap\Queue\EnqueueTaskInterface;
use Tuleap\Search\IdentifyAllItemsToIndexEvent;
use Symfony\Component\Process\Process;
use function Psl\File\read;
use function Psl\Filesystem\delete_directory;
use function Psl\Filesystem\exists;
use function Psl\Str\before_last;
use function Psl\Str\Byte\after;

final class PrepareStartMeilisearchServerCommand extends Command
{
    public const NAME                           = 'full-text-search-meilisearch:prepare-start-meilisearch-server';
    private const MEILISEARCH_DATA_PATH         = "/var/lib/tuleap/fts_meilisearch_server/data.ms";
    private const MEILISEARCH_DATA_VERSION_FILE = self::MEILISEARCH_DATA_PATH . "/VERSION";

    public function __construct(
        private readonly EventDispatcherInterface $event_dispatcher,
        private readonly EnqueueTaskInterface $enqueue_task,
    ) {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this->setDescription('Prepare environment to start the local Meilisearch server')->setHidden(true);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->getMeilisearchDataVersion()->apply(
            function (string $meilisearch_data_version) use ($output): void {
                $meilisearch_server_version = $this->getMeilisearchServerVersion();
                if ($meilisearch_data_version === $meilisearch_server_version) {
                    return;
                }

                $output->writeln(
                    sprintf(
                        'Server version (%s) does not match the data version (%s), all items will be re-indexed',
                        OutputFormatter::escape($meilisearch_server_version),
                        OutputFormatter::escape($meilisearch_data_version)
                    ),
                );

                delete_directory(self::MEILISEARCH_DATA_PATH, true);
                $this->event_dispatcher->dispatch(new IdentifyAllItemsToIndexEvent());
                $this->enqueue_task->enqueue(ProcessPendingItemsToIndexTask::build());
            }
        );

        return self::SUCCESS;
    }

    /**
     * @psalm-return Option<string>
     */
    private function getMeilisearchDataVersion(): Option
    {
        if (! exists(self::MEILISEARCH_DATA_VERSION_FILE)) {
            return Option::nothing(\Psl\Type\string());
        }

        $meilisearch_data_version = before_last(read(self::MEILISEARCH_DATA_VERSION_FILE), '.');

        if ($meilisearch_data_version === null) {
            return Option::nothing(\Psl\Type\string());
        }

        return Option::fromValue($meilisearch_data_version);
    }

    private function getMeilisearchServerVersion(): string
    {
        $process = new Process(['/usr/bin/tuleap-meilisearch', '--version']);
        $process->mustRun();

        $server_version = $process->getOutput();

        return before_last(after($server_version, 'meilisearch ') ?? '', '.') ?? '';
    }
}
