<?php

/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once 'StatisticsManager.php';
/*
 * Save datas thrown by boomerang and generate csv files with these datas.
 */

class BoomerangDatasProcessor {

    private $context_datas;
    private $files; // Files names which get content
    private $datas;
    private $date; // Initialized at construction
    private $log_suffix = ".log";
    private $quartile_extension = ".quartile";
    private $week_suffix; //Initialized in the constructor.
    private $context_data_prefix = "group_id=";
    private $quartile_documentation = "1st line = 25%, 2nd = 50%, 3rd = 75%";
    private $data_file_name = "data.csv";
    private $first_column_name = "week";
    private $storage_directory;

    /**
     *
     * @param array $files Data table containing the name of data files
     * @param array $datas Data table containing picked datas
     * @param string $context_datas Will be written in the log files.
     * @throws Exception Both arrays must have the same size.
     */
    public function __construct($storage_directory, $datas, $context_datas) {

        $this->storage_directory = $storage_directory;
        $this->week_suffix = "." . date("y") . '\'' . date("W");
        $this->date = getDate();
        $this->context_datas = $context_datas;
        $this->files = array();
        $this->data_file_name = $this->storage_directory . $this->data_file_name;
        $this->datas = array();
        foreach ($datas as $index => $data) {
            $this->files[] = $this->storage_directory . $index;
            $this->datas[] = $data;
        }
        $this->updateLogFiles();
    }

    /**
     * Generate statistics with the given datas and older datas.
     */
    public function handle() {
        $this->generateQuartileFiles();
        $this->exportCSV();
        $this->removeFilesWithExtensions(array($this->quartile_extension));
    }

    private function updateLogFiles() {
        $i = 0;
        foreach ($this->files as $file) {
            $log_file_name = $file . $this->week_suffix . $this->log_suffix;
            $log_file = fopen($log_file_name, "a");
            fwrite($log_file, $this->context_data_prefix . $this->context_datas . "," . $this->datas[$i] . "\n");
            fclose($log_file);
            $i++;
        }
    }

    private function generateQuartileFiles() {
        $files_list = glob($this->storage_directory . '*' . $this->log_suffix);
        foreach ($files_list as $file) {
            $quartile_file_name = $this->storage_directory . basename($file, $this->log_suffix) . $this->quartile_extension;
            $stats_manager = new StatisticsManager($this->extractDatasFromFormattedFile($file, ',', 1));
            $quartile_datas = $stats_manager->getQuartiles();
            $this->writeArrayToFile($quartile_file_name, 'w', $quartile_datas);
            $this->writeDocumentationtoFile($quartile_file_name, $this->quartile_documentation);
        }
    }

    private function extractDatasFromFormattedFile($file, /* & $array, */ $separator, $column = null) {
        $returned_array = array();
        foreach (file($file, FILE_IGNORE_NEW_LINES) as $line) {
            $data = split($separator, $line);
            $returned_array[] = ($column) ? $data[$column] : $data;
        }
        return $returned_array;
    }

    private function writeArrayToFile($file_name, $mode, $array) {
        $file = fopen($file_name, $mode);
        foreach ($array as $line) {
            fwrite($file, $line . "\n");
        }
        fclose($file);
    }

    private function writeDocumentationtoFile($file_name, $documentation) {
        $file = fopen($file_name, 'a');
        fwrite($file, $documentation);
        fclose($file);
    }

    private function exportCSV() {
        $datas = array();
        $output_file = fopen($this->data_file_name, "w+");

        //Write the first line (column names).
        fwrite($output_file, $this->first_column_name . ",");
        foreach ($this->files as $i => $name) {
            fwrite($output_file, $this->formatCell($i, $this->files, basename($name)));
        }
        fwrite($output_file, "\n");

        //Write the week column.
        $files_by_week = glob($this->files[0] . "*" . $this->quartile_extension);
        $column = array();
        foreach ($files_by_week as $week_file) {
            $splitted_week_file_name = split('\.', $week_file);
            $column[] = $splitted_week_file_name[1];
        }
        $datas[] = $column;

        //Fill the file.
        foreach ($this->files as $file_name) {
            $quartile_files_by_week = glob($file_name . "*" . $this->quartile_extension);
            $datas[] = $this->pickDatasInFiles($quartile_files_by_week, StatisticsManager::MEDIAN_QUARTILE);
        }
        for ($j = 0; $j < count($datas[0]); ++$j) {
            for ($i = 0; $i < count($datas); ++$i) {
                fwrite($output_file, $this->formatCell($i, $datas, $datas[$i][$j]));
            }
            fwrite($output_file, "\n");
        }
        fclose($output_file);
    }

    private function formatCell($index, $array, $cell_content) {
        if ($index != count($array) - 1) {
            $cell_content .= ",";
        }
        return $cell_content;
    }

    private function pickDatasInFiles($files, $line_index = null) {
        $column = array();
        foreach ($files as $file) {
            $opened_file = file($file);
            $column[] = ($line_index) ? preg_replace('~[\r\n]+~', '', $opened_file[$line_index]) : $opened_file;
        }
        return $column;
    }

    private function removeFilesWithExtensions($file_extensions) {
        foreach ($file_extensions as $file_extension) {
            $files_list = glob('*' . $file_extension);
            $this->removeFilesList($files_list);
        }
    }

    private function removeFilesList($files_list) {
        foreach ($files_list as $file_name) {
            unlink($file_name);
        }
    }
}
?>