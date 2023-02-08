<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * avantassist plugin.
 *
 * @package   local_avantassist
 * @copyright 2020 onwards, tim.stclair@gmail.com (https://github.com/frumbert)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_avantassist\output;
defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

/**
 * Class to compile appropriate data for table renderer.
 *
 * @copyright 2020 onwards, tim.stclair@gmail.com (https://github.com/frumbert)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report implements renderable, templatable {

    protected $report;
    public $dates;
    public $source;
    protected $export;

    /**
     * main constructor.
     *
     * @param string $report - the name of the report to generate
     * @param stdClass $dates - containing start and end timestamps to filter between
     * @param boolean $export - whether this report is being downloaded or shown
     */
    public function __construct($report, $dates, $source, $export = false) {
        $this->report = $report;
        $this->dates = $dates;
        $this->export = $export;
        $this->source = $source;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
    	global $DB, $OUTPUT;

        switch ($this->report) {
            case "engagement":
                $data = local_avantassist_report_engagement($this, $output);
                break;

            case "selfassessment":

            break;

            case "evaluation":

            break;
        
        }

        // $table = local_avantassist_sectionviews('aa_surgeon', $this->dates->from, $this->dates->to);

        return $data;
    }
}