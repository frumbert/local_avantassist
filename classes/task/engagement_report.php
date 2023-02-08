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
 * Task schedule configuration for the plugintype_pluginname plugin.
 *
 * @package   local_avantassist
 * @copyright 2023 <tim.stclair@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_avantassist\task;

/**
 * An example of a scheduled task.
 */
class engagement_report extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('generate_reports', 'local_avantassist');
    }

    /**
     * Execute the task.
     */
    public function execute() {
    global $CFG, $DB;
        $params = http_build_query([
            "report" => "engagement",
            "email" => 1,
            "download" => 0,
            "from" => strtotime("-10 year 00:00:00", time()),
            "to" => strtotime("tomorrow", time()) - 1,
        ],'','&');
        if (debugging('', DEBUG_DEVELOPER)) {
            echo "Calling {$CFG->wwwroot}/local/avantassist/report.php?{$params}", PHP_EOL;
        }
        // the transaction lets you (roughly) track the dbqueries
        if (($transaction = $DB->start_delegated_transaction()) === null) throw new \coding_exception('Invalid delegated transaction object');
        $ok = false;
        try {
            $ok = file_get_contents("{$CFG->wwwroot}/local/avantassist/report.php?{$params}");
            $transaction->allow_commit();
        }
        catch(\Exception $e) {
            $transaction->rollback($e);
        }
        echo "Result: ", $ok ? 'Success' : 'Fail', PHP_EOL;
    }
}