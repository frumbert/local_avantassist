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
 * avantassist rendrer.
 *
 * @package   local_avantassist
 * @copyright 2020 onwards, tim.stclair@gmail.com (https://github.com/frumbert)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_avantassist\output;
defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;
use renderable;

class renderer extends plugin_renderer_base {

    public $context;

    /**
     * render one sheet (called by createWorkSheet)
     *
     * @param main $main The main renderable
     * @return string HTML string
     */
    public function render_sheet(report $main, $sheet) {
        $this->context = $sheet;
        $data = $main->export_for_template($this);
        return $this->render_from_template('local_avantassist/sheet', $data);
    }

    /**
     * render to output (html).
     *
     * @param main $main The main renderable
     * @return string HTML string
     */
    public function render_main(report $main) {
        $data = $main->export_for_template($this);
        return $this->render_from_template('local_avantassist/main', $data);
    }
}