<?php

require_once($CFG->dirroot . '/report/outline/locallib.php');

define('EMAIL_TO', 'avantassist@avant.org.au');
// define('EMAIL_TO', 'avantassist@frumbert.org');

// get the named set of sections accessible to this user
function local_avantassist_report_coursestatistics($idnumber, $datestart = 0, $dateend = 0) {
global $DB;
    $data = [];
    $course = $DB->get_record('course',['idnumber'=>$idnumber],'*',MUST_EXIST);
    $section_hits = local_avantassist_sectionviews($course->idnumber, $datestart, $dateend);
    $modinfo = get_fast_modinfo($course);
    if ($sections = $modinfo->get_section_info_all()) {
        foreach ($sections as $number => $section) {
            if ($section->uservisible && $section->available) {
                $obj = new stdClass();
                $obj->title = get_section_name($course, $section);
                $obj->hits = $section_hits[$number];
                foreach ($modinfo->sections[$number] as $modnumber) {
                    $mod = $modinfo->cms[$modnumber];
                    if (!$mod->uservisible) { // || !$mod->is_visible_on_course_page()) {
                        continue;
                    }

                }
            }
        }
    }
    return $data;
}

function local_avantassist_sectionviews($idnumber, $date_start = 0, $date_end = 0) {
global $DB;
$sql = "
    SELECT n.section, count(s.userid) hits
    FROM {logstore_standard_log} s
    INNER JOIN {course} c ON s.courseid = c.id
    INNER JOIN {course_sections} n ON n.course = c.id AND n.section = SUBSTRING_INDEX(SUBSTRING_INDEX(s.other,CHAR(59),2),CHAR(58),-1)
    WHERE s.target='course' AND s.origin='web' AND s.action='viewed' AND c.idnumber = ?
    AND s.timecreated between ? and ?
    AND s.userid in (select userid from mdl_cohort_members chm inner join mdl_cohort h on h.id=chm.cohortid where h.idnumber = ?)
    GROUP BY s.other
    ORDER BY n.section
    ";
    $params_array = [$idnumber, $date_start, $date_end, $idnumber];
    $rows = $DB->get_records_sql($sql, $params_array);
    $result = [];
    foreach ($rows as $row) {
        $result[$row->section] = $row->hits;
    }
    return $result;
}

// assumption: cohort idnumber and course idnumber match
function local_avantassist_get_cohort_users($idnumber) {
global $DB;
    $results = [];
    $rows = $DB->get_records_sql("SELECT * FROM {user} u
        WHERE id IN (SELECT userid FROM {cohort_members} m INNER JOIN {cohort} c ON m.cohortid = c.id WHERE c.idnumber = ?)
        ORDER BY lastname ASC, firstname ASC", array($idnumber));
    foreach ($rows as $row) {
        $login = intval($row->currentlogin);
        if ($login === 0) $login = intval($row->lastlogin);
        $results[] = (object)[
            "id" => $row->id,
            "memberid" => get_user_preferences('memberid', '', $row),
            "name" => fullname($row),
            "login" => $login,
        ];
    }
    return $results;
}
/*
// BORKED - the login might double-count becasuse the subquery can match records already counted on subsequent rows
// just a quick cache for this page - we hit this for every user
function local_avantassist_get_hits($andtime, $params) {
global $DB, $CFG;

    $cache = "hits_". $params['courseid'];

    if (!isset($CFG->$cache)) {

        // first timecreated of a \core\event\course_viewed that takes place after a \core\event\user_loggedin in an avant-assist course
        // so find the course-view and ensure it follows a user_loggedin.
        // multipe course-views will probably follow a loggedin, so group-by the user/course and order by timecreated to grab the first of each group
        // then limit it to the user you need.

        $sql = "SELECT id, timecreated, userid from {logstore_standard_log} l
            where (eventname = :viewed and l.courseid = :courseid)
            and l.userid = (
            select userid from {logstore_standard_log}
                where (eventname = :loggedin and courseid = 0)
                and id < l.id
                and userid = l.userid
                order by id desc limit 1
            )
            {$andtime}
            order by timecreated";
        $CFG->$cache = serialize($DB->get_records_sql($sql,$params));
    }

    return unserialize($CFG->$cache);

}

// count actual logons by finding records matching a pattern (a logon preceding a course open matching the course we want).
// SLOW AND SAFE - yes there is lots of looping but it's being careful
function local_avantassist_alt_hits($andtime, $params) {
global $DB;

    // find ALL logins for this user
    $sql = "SELECT id, timecreated FROM {logstore_standard_log}
            WHERE eventname = :loggedin
            AND courseid = 0
            AND userid = :userid
            {$andtime}
            ORDER BY timecreated";
    $logons = $DB->get_records_sql($sql, $params);

    // find ALL course views for this user/course
    $sql = "SELECT id, timecreated, courseid FROM {logstore_standard_log}
            WHERE eventname = :viewed
            AND userid = :userid
            {$andtime}
            ORDER BY timecreated";
    $views = $DB->get_records_sql($sql, $params);

    $found = [];

    // iterate all the course views
    foreach ($views as $view) {

        // starting at the end of the logons, walk backwards
        for (end($logons); key($logons)!==null; prev($logons)) {
            $element = current($logons);

            // until we find a logon id that is earlier than the current view, but not one that is already counted
            if ($element->id < $view->id && !in_array($element->id, $found)) {

                // and remember this one
                $found[] = $element->id;
            }
        }

    }

    $total = count($found); // how many times a logon was found that included a course view of the course we wanted to see.

    $first = reset($found); // the first logon record found
    $first = array_filter($logons, function($rec) use ($first) { return $rec->id == $first; }); // now it is the matching logons record
    $first = reset($first)->timecreated; // get the first element of the array, which is a stdClass, and get its time created
    $first = ($first > 0) ? userdate($first, "%d/%m/%y, %H:%M:%S") : '-'; // first coerces null to zero, format the date

    $last = end($found);
    $last = array_filter($logons, function($rec) use ($last) { return $rec->id == $last; });
    $last = reset($last)->timecreated;
    $last = ($last > 0) ? userdate($last, "%d/%m/%y, %H:%M:%S") : '-';

    return [
        "first" => $first,
        "last" => $last,
        "total" => $total,
    ];

}

// count actual logons by finding records matching a pattern (a logon preceding a course open matching the course we want).
// SLOW AND SAFE - yes there is lots of looping but it's being careful

    // find ALL logins for this user
    $sql = "SELECT id, timecreated FROM {logstore_standard_log}
            WHERE eventname = :loggedin
            AND courseid = 0
            AND userid = :userid
            {$andtime}
            ORDER BY timecreated";
    $logons = $DB->get_records_sql($sql, $params);

    // find ALL course views for this user
    $sql = "SELECT id, timecreated, courseid FROM {logstore_standard_log}
            WHERE eventname = :viewed
            AND userid = :userid
            {$andtime}
            ORDER BY timecreated";
    $views = $DB->get_records_sql($sql, $params);

    $found = [];
    $keys = array_keys($logons);
    foreach(array_keys($keys) as $index) {
        $ckey = current($keys);
        $current_id = $logons[$ckey]->id;
        $nkey = next($keys);
        $next_id = $logons[$nkey]->id ?? 0;
        foreach ($views as $view) {
            if ($view->id > $current_id && $view->id < $next_id && $view->courseid == $courseid) {
                $found[$current_id] = $logons[$ckey];
            }
        }
    }

    $total = count($found); // how many times a logon was found that included a course view of the course we wanted to see.

    $first = reset($found); // the first logon record found
    $first = array_filter($logons, function($rec) use ($first) { return $rec->id == $first; }); // now it is the matching logons record
    $first = reset($first)->timecreated; // get the first element of the array, which is a stdClass, and get its time created
    $first = ($first > 0) ? userdate($first, "%d/%m/%y, %H:%M:%S") : '-'; // first coerces null to zero, format the date

    $last = end($found);
    $last = array_filter($logons, function($rec) use ($last) { return $rec->id == $last; });
    $last = reset($last)->timecreated;
    $last = ($last > 0) ? userdate($last, "%d/%m/%y, %H:%M:%S") : '-';


    // $sql = "SELECT COUNT(1) FROM {logstore_standard_log} WHERE eventname=:eventname AND userid=:userid{$andtime}";
    // $params['eventname'] = "\\core\\event\\user_loggedin";

    // $logins = 0 ; // $DB->count_records_sql($sql, $params);

    // $sql = "SELECT COUNT(1) FROM {logstore_standard_log}
    //         WHERE eventname=:viewed
    //         AND courseid=:courseid
    //         AND userid=:userid
    //         {$andtime}";
    // $views = $DB->count_records_sql($sql, $params);

    // $records = [];
    // if ($cache = local_avantassist_get_hits($andtime,$params)) {
    //     foreach ($cache as $row) {
    //         if ($row->userid == $user->id) {
    //             $records[] = $row;
    //         }
    //     }
    // }

    // if ($records) {
    //     $first = reset($records); if ($first)  $firstlogin = userdate($first->timecreated);
    //     $last = end($records); if ($last)  $lastlogin = userdate($last->timecreated);
    //     $logins = count($records);
    // }

    // $stuff2 = local_avantassist_alt_hits($andtime,$params);

    // $views = local_avantassist_count_section_hits($andtime, $user->id, $course->id, 0); // clicks for this user on section 0 (home)



    // return (object)[
    //     "views" => $views,
    //     "logins" => $logins,
    //     "lastlogin" => $lastlogin,
    //     "firstlogin" => $firstlogin
    // ];

*/

function local_avantassist_third_times_the_charm($andtime, $params) {
global $DB, $CFG;

    $courseid = $params['courseid'];
    $userid = $params['userid'];
    $cache = "hits_{$courseid}";

    // precache data without user context since it will be iterated many times
    // logon might be one or two ids behind view due to session timeout during sso  - unsure of cause
    if (!isset($CFG->$cache)) {
        $sql = "SELECT id, userid, timecreated FROM mdl_logstore_standard_log l
            WHERE eventname = :viewed
            AND l.courseid = :courseid
            AND (select count(1) from mdl_logstore_standard_log
                where (eventname = :loggedin and userid = l.userid)
                and (id = l.id-1 or id = l.id-2)
            ) > 0
            {$andtime}
            order by userid, timecreated";
        $CFG->$cache = serialize($DB->get_records_sql($sql,$params));
    }

    $table = unserialize($CFG->$cache);
    $source = [];
    
    // now filter the cache to just this user
    foreach ($table as $row) {
        if ($row->userid == $userid) $source[] = $row;
    }

    $first = '-';
    $last = '-';
    $total = 0;

    if (!empty($source)) {
        $first = reset($source); $first = userdate($first->timecreated);
        $last = end($source); $last = userdate($last->timecreated);
        $total = count($source);
    }

    return [
        "first" => $first,
        "last" => $last,
        "total" => $total,
    ];

}

function local_avantassist_count_section_hits($dates, $user, $course, $section_index) {
global $DB;

    if ($section_index == 0) $section_index = 'N;';
    $params = [];
    $params['viewed'] = "\\core\\event\\course_viewed";
    $params['courseid'] = $course->id;
    $params['userid'] = $user->id;
    $params['s'] = $section_index;

    $andtime = '';
    if ($dates->from>0 && $dates->to>0) {
        $andtime = " AND timecreated between :datefrom and :dateto";
        $params['datefrom'] = $dates->from;
        $params['dateto'] = $dates->to;
    }

    $sql = "SELECT COUNT(1) FROM {logstore_standard_log}
            WHERE eventname=:viewed
            AND courseid=:courseid
            AND userid=:userid
            AND SUBSTRING_INDEX(SUBSTRING_INDEX(other,CHAR(59),2),CHAR(58),-1)=:s      
            {$andtime}";
    return $DB->count_records_sql($sql, $params);
}

function local_avantassist_count_logins($course, $user, $datefrom = 0, $dateto = 0) {
global $DB;

    $params = [];
    $params['loggedin'] = "\\core\\event\\user_loggedin";
    $params['viewed'] = "\\core\\event\\course_viewed";
    $params['courseid'] = $course->id;
    $params['userid'] = $user->id;

    $andtime = '';
    if ($datefrom>0 && $dateto>0) {
        $andtime = " AND timecreated between :datefrom and :dateto";
        $params['datefrom'] = $datefrom;
        $params['dateto'] = $dateto;
    }
    $stuff = local_avantassist_third_times_the_charm($andtime, $params);
    return (object)[
        "logins" => $stuff['total'],
        "firstlogin" => $stuff['first'],
        "lastlogin" => $stuff['last'],
    ];

}

// this uses data from and is similar to the /report/outline/index.php report
function local_avantassist_report_engagement($report, $renderer) {
global $CFG, $DB;

    $idnumber = '';

    foreach ($report->source as $inst) {
        if ($inst->sheet == $renderer->context) {
            $idnumber = $inst->idnumber;
        }
    }

    if (empty($idnumber)) return false;

    $course = $DB->get_record('course', ['idnumber' => $idnumber]);
    $modinfo = get_fast_modinfo($course, -1);
    $sections = $modinfo->get_section_info_all();
    $startdate = $report->dates->from;
    $enddate = $report->dates->to;

    $data = new stdClass();
    $data->sections = [];

    $data->sections[] = (object)[
        "title" => "Portal",
        "activities" => [(object)[
            "type" => "logins",
            "title" => "Log ins",
            "id" => -1
        ], (object)[
            "type" => "firstlogin",
            "title" => "First login",
            "id" => -1
        ], (object)[
            "type" => "lastlogin",
            "title" => "Last login",
            "id" => -1
        ]],
        "activitycount" => 3
    ];

// , (object)[
//             "type" => "views",
//             "title" => "Home Views",
//             "id" => -1
//         ]

    foreach ($sections as $i => $section) {
        if (!empty($modinfo->sections[$i])) {
            $s = new stdClass();
            $s->title = html_to_text(get_section_name($course, $section), 0, false);
            if ($s->title === 'Home') $s->title = 'Menu options'; // WHY?
            $s->activities = [];
            if ($i === 0) {
                $a = new stdClass();
                $a->type = "views";
                $a->title = get_string('home_title','local_avantassist');
                $s->activities[] = $a;
            } else {
                $a = new stdClass();
                $a->type = "views";
                $a->title = get_string('clicks_title','local_avantassist');
                $s->activities[] = $a;
            }
            foreach ($modinfo->sections[$i] as $cmid) {
                $mod = $modinfo->cms[$cmid];
                $row = $DB->get_record("$mod->modname", array("id"=>$mod->instance));
                $a = new stdClass();
                $a->type = 'activity';
                $a->instance = $row;
                $a->title = html_to_text($row->name, 0, false);
                $a->cmid = $cmid;
                $a->mod = $mod;
                if ($mod->visible == 0) $a->title .= ' (hidden)';
                $s->activities[] = $a;
            }
            $s->activitycount = count($s->activities);
            $data->sections[] = $s;
        }
    }
    $data->users = local_avantassist_get_cohort_users($idnumber);

    foreach ($data->users as &$user) {
        $hits = local_avantassist_count_logins($course, $user, $startdate, $enddate);
        foreach ($data->sections as $si => $section) {
            foreach ($section->activities as $activity) {
                $cell = 0;
                if ($activity->type === "logins") {
                    $cell = $hits->logins;
                } else if ($activity->type === "firstlogin") {
                    $cell = $hits->firstlogin;
                } else if ($activity->type === "lastlogin") {
                    $cell = $hits->lastlogin;
                } else if ($activity->type === "views") {
                    $cell = local_avantassist_count_section_hits($report->dates, $user, $course, $si - 1);
                } else {
    // $path = -1;
    // $s = microtime(true);
                    $mod = $activity->mod;
                    $libfile = "$CFG->dirroot/mod/$mod->modname/lib.php";
                    if (file_exists($libfile)) {
                        require_once($libfile);
                        $user_outline = $mod->modname."_user_outline";
                        if (function_exists($user_outline)) {
    // $path = 1;
                            $output = $user_outline($course, $user, $mod, $activity->instance);
    // $e = microtime(true);
                            if (!is_null($output)) {
                                $cell = preg_replace('/[^0-9]/', '', $output->info);
                            }
                        } else {
    // $path = 2;
                            $cell = local_avantassist_report_outline_user_outline($user->id, $activity->cmid, $mod->modname, $activity->instance->id, $startdate, $enddate);
    // $e = microtime(true);
                        }
                    }
    // if ($path == 1) {
    //     $p = $e - $s;
    //     $stophere = 1;
    // }
               }
                $user->records[] = $cell;
            }
        }
    }

    return $data;

}

function local_avantassist_report_outline_user_outline($userid, $cmid, $module, $instanceid, $datefrom, $dateto) {
    global $DB;

    $result = 0;
    list($uselegacyreader, $useinternalreader, $minloginternalreader, $logtable) = report_outline_get_common_log_variables();

// i ran some tests and it doesn't look like we need to query this table.
// it's pretty slow anyway. I think including info in the query fails to match an index.
$uselegacyreader= false;

    // If using legacy log then get users from old table.
    if ($uselegacyreader) {
        // Create the params for the query.
        $params = array('userid' => $userid, 'module' => $module, 'action' => 'view', 'info' => $instanceid);
        // If we are going to use the internal (not legacy) log table, we should only get records
        // from the legacy table that exist before we started adding logs to the new table.
        $limittime = '';
        if (!empty($minloginternalreader)) {
            $limittime = ' AND time < :timeto ';
            $params['timeto'] = $minloginternalreader;
        }
        $andtime = '';
        if ($datefrom>0 && $dateto>0) {
            $andtime = "AND time between :datefrom and :dateto";
            $params['datefrom'] = $datefrom;
            $params['dateto'] = $dateto;
        }
        $select = "SELECT COUNT(id) ";
        $from = "FROM {log} ";
        $where = "WHERE userid = :userid
                    AND module = :module
                    AND action = :action
                    $andtime
                    AND info = :info ";
        if ($legacylogcount = $DB->count_records_sql($select . $from . $where . $limittime, $params)) {
            $result = $legacylogcount;
            // if ($legacylogcount > 0) {
            //     $breakpoint = 1;
            // }
        }
    }

    // Get record from sql_internal_table_reader and combine with the number of views from the legacy log table (if needed).
    if ($useinternalreader) {
        $params = array('userid' => $userid, 'contextlevel' => CONTEXT_MODULE, 'contextinstanceid' => $cmid, 'crud' => 'r',
            'edulevel1' => core\event\base::LEVEL_PARTICIPATING, 'edulevel2' => core\event\base::LEVEL_TEACHING,
            'edulevel3' => core\event\base::LEVEL_OTHER); //, 'anonymous' => 0);
        $andtime = '';
        if ($datefrom>0 && $dateto>0) {
            $andtime = "AND timecreated between :datefrom and :dateto";
            $params['datefrom'] = $datefrom;
            $params['dateto'] = $dateto;
        }
        $select = "SELECT COUNT(*) as count ";
        $from = "FROM {" . $logtable . "} ";
        $where = "WHERE userid = :userid
                    AND contextlevel = :contextlevel
                    AND contextinstanceid = :contextinstanceid
                    AND crud = :crud
                    AND edulevel IN (:edulevel1, :edulevel2, :edulevel3)
                    $andtime
                    "; 
                    // AND anonymous = :anonymous"; // want to avoid using another index
        if ($internalreadercount = $DB->count_records_sql($select . $from . $where, $params)) {
            $result += $internalreadercount;
        }
    }
    return $result;
}

// get accessible activities in section 0
function block_sectionmenu_get_zeros($course, $page) {
    $result = [];
    $modinfo = get_fast_modinfo($course);

    if (!empty($modinfo->sections[0])) {
        foreach ($modinfo->sections[0] as $modnumber) {
            $mod = $modinfo->cms[$modnumber];
            if (!$mod->uservisible) { // || !$mod->is_visible_on_course_page()) {
                continue;
            }
            $result[] = [
                "name" => $mod->get_formatted_name(),
                "link" => $mod->url,
                "current" => $mod->url->compare($page->url, URL_MATCH_PARAMS)
           
            ];
        }
    }

    return $result;
}

function createExcelWorksheet($instance, $filename, $download) {
global $PAGE, $CFG;
    require_once($CFG->libdir . '/excellib.class.php');

    $objOutput = new \PhpOffice\PhpSpreadsheet\Spreadsheet(); // the final spreadsheet
    $renderer = $PAGE->get_renderer('local_avantassist');

    foreach ($instance->source as $index => $source) {

        // each source has a sheet and a idnumber
        $sheet = $source->sheet;

        // a temporary sheet for this report instance
        $excel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $asheet = $excel->getActiveSheet();
        $asheet->setTitle($sheet);    

        // render table using template
        $table = $renderer->render_sheet($instance, $sheet);
        $table = "<!doctype html><html><body>{$table}</body></html>";

        // save the html to a temp file
        $tmpfile = tempnam(sys_get_temp_dir(), 'html');
        file_put_contents($tmpfile, $table);

        // read the html back in as a sheet
        $excelHTMLReader = new \PhpOffice\PhpSpreadsheet\Reader\Html;
        $excelHTMLReader->loadIntoExisting($tmpfile, $excel);
        unlink($tmpfile);

        // append this sheet to the overall spreadsheet
        $objOutput->addExternalSheet($asheet, $index);

    }

    // set which tab is selected by default
    $objOutput->setActiveSheetIndex(0);

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objOutput);
    // $outpfn = $CFG->dataroot . '/' . $filename;
    // $writer->save($outpfn);

    // destination is either download or email
    if ($download) {

        // send to browser as an attachment
        // header('Content-type: application/excel');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition:attachment;filename={$filename}");

        // send to php output stream directly
        $writer->save('php://output');
 
    } else {

        // email_to_user can only pick up attachments from
        // $CFG->cachedir, $CFG->dataroot, $CFG->dirroot, $CFG->localcachedir, $CFG->tempdir

        // $tmpfile = tempnam(sys_get_temp_dir(), 'xlsx'); // won't attach
        $tmpfile = tempnam($CFG->tempdir, 'xlsx');
        $writer->save($tmpfile);

        $from = '';
        $to = '';

        $on = userdate(time(), get_string('strftimedaydatetime', 'langconfig'));
        if ($instance->dates->from > 0) $from = userdate($instance->dates->from, get_string('strftimedaydatetime', 'langconfig'));
        if ($instance->dates->from > 0) $to = userdate($instance->dates->to, get_string('strftimedaydatetime', 'langconfig'));

        $messagehtml  = "<p><b>Data current as to</b>: {$on}</p>";
        if (!empty($from)) $messagehtml .= "<p><b>From</b>: {$from}</p>";
        if (!empty($to)) $messagehtml .= "<p><b>To</b>: {$to}</p>";
        if (strpos($CFG->wwwroot, ".uat.")!==false) {
            $messagehtml .= "<p><b>Server</b>: UAT</p>";
        } else if (strpos($CFG->wwwroot, ".test")!==false) {
            $messagehtml .= "<p><b>Server</b>: Development</p>";
        } else {
            $messagehtml .= "<p><b>Server</b>: Production</p>";
        }
        $messagetext = html_to_text($messagehtml);
        $subject = "Avant Assist Engagement Report";

        $emailfrom = core_user::get_noreply_user();
        $emailto = core_user::get_support_user();
        $emailto->firstname = '';
        $emailto->lastname = '';
        $emailto->email = EMAIL_TO;

        return email_to_user($emailto, $emailfrom,
                    $subject,
                    $messagetext, $messagehtml,
                    $tmpfile, $filename);
    
    }

}