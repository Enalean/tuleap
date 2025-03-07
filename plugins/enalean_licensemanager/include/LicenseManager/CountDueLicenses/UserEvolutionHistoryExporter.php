<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Enalean\LicenseManager\CountDueLicenses;

use DateTimeImmutable;
use Symfony\Component\Console\Output\OutputInterface;

class UserEvolutionHistoryExporter
{
    /**
     * @var string
     */
    private $history_file_location;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(OutputInterface $output, string $history_file_location)
    {
        $this->history_file_location = $history_file_location;
        $this->output                = $output;
    }

    public function exportHistory(
        DateTimeImmutable $current_timestamp,
        int $nb_active_users,
        int $nb_real_users,
    ): void {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $this->history_file_location . '"');

        if (file_exists($this->history_file_location) && $this->hasScriptBeenRanToday()) {
            $this->removeLastEntry();
        }

        if (! file_exists($this->history_file_location)) {
            $csv_file = fopen($this->history_file_location, 'a');
            fputcsv($csv_file, ['date', 'nb active users', 'nb visitor users', 'nb due licenses'], ',', '"', '\\');
        } else {
            $csv_file = fopen($this->history_file_location, 'a');
        }

        $nb_visitors = $nb_active_users - $nb_real_users;
        $data        = [$current_timestamp->format('Y-m-d'), $nb_active_users, $nb_visitors, $nb_real_users];

        fputcsv($csv_file, $data, ',', '"', '\\');
        fclose($csv_file);

        $this->output->writeln('<comment>Forge users evolution history saved. You can consult it here : ' . $this->history_file_location . '</comment>');
    }

    private function hasScriptBeenRanToday(): bool
    {
        $rows     = file($this->history_file_location);
        $last_row = array_pop($rows);
        $data     = str_getcsv($last_row);

        try {
            $last_entry_date = new DateTimeImmutable($data[0]);
            $today           = new DateTimeImmutable();
        } catch (\Exception $exception) {
            return false;
        }

        return ($last_entry_date->diff($today))->days === 0;
    }

    private function removeLastEntry(): void
    {
        $rows = file($this->history_file_location);
        array_pop($rows);
        $file = fopen($this->history_file_location, 'w');
        fwrite($file, implode($rows));
        fclose($file);
    }
}
