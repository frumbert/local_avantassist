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

if (!defined('MOODLE_INTERNAL')) {
    // It must be included from a Moodle page.
    die('Direct access to this script is forbidden.');
}

require_once($CFG->libdir.'/formslib.php');

class local_avantassist_dateselector extends moodleform {
    public function definition() {
        global $COURSE;

        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('filter', 'local_avantassist', $this->_customdata->report));
        $mform->addElement('html', get_string('predefined_dates', 'local_avantassist', $this->_customdata->report));

        $mform->addElement('date_time_selector', 'filter_starttime', get_string('starttime', 'local_avantassist'));
        $mform->setDefault('filter_starttime', $this->_customdata->from); // time() - 3600 * 24);
        $mform->addElement('date_time_selector', 'filter_endtime', get_string('endtime', 'local_avantassist'));
        $mform->setDefault('filter_endtime', $this->_customdata->to); // time() + 3600 * 24);

        $mform->addElement('hidden', 'report', $this->_customdata->report);
        $mform->addElement('hidden', 'courseid', $COURSE->id);
        $mform->setType('report', PARAM_TEXT);
        $mform->setType('courseid', PARAM_INT);

        // Buttons.
        $this->add_action_buttons(true, get_string('filter_apply', 'local_avantassist'));
    }
}
