<?php

// this page outputs the request report

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/completionlib.php');
require_once('locallib.php');

$report = required_param('report', PARAM_TEXT);
$download = optional_param('download', 1, PARAM_INT) === 1;
$email = optional_param('email',0, PARAM_INT) === 1;
$contextid = optional_param('context',0,PARAM_INT);

$dates = new stdClass();
$dates->from = optional_param('from', strtotime('today 00:00:00'), PARAM_INT);
$dates->to = optional_param('to', time(), PARAM_INT);

// internals
$pagetitle = get_string('pluginname', 'local_avantassist');
$context = context_system::instance();

$source = [];
$source[] = (object)[
	"sheet" => 'GP',
	"idnumber" => 'aa_gp'
];
$source[] = (object)[
	"sheet" => 'Surgeon',
	"idnumber" => 'aa_surgeon'
];

// require authentication and capability
if (!$email) {
	require_login();
}
// if (!core_course_category::has_capability_on_any(array('moodle/category:manage', 'moodle/course:create'))) {
// 	require_capability('local/avantassist:viewreports', $context);
// }

// set up page
$PAGE->set_url('/local/classreport/report.php');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

$report_name = $report;
switch ($report) {
	case "engagement": $report_name = "Avant_Assist_Engagement_detail"; break;
}

$renderer = $PAGE->get_renderer('local_avantassist');
require_once('./classes/report.php');
$inst = new \local_avantassist\output\report($report, $dates, $source, $download);
$filename = clean_filename($report_name . "_" . date_format(date_create("now"),"YmdHis")).'.xlsx';

if ($download) {

	createExcelWorksheet($inst, $filename, true);
	exit(0);

} else if ($email) {

	echo "Emailing report", PHP_EOL;
    $output = createExcelWorksheet($inst, $filename, false);
	die($output);

} else {

	echo $OUTPUT->header();
	echo $renderer->render_main($inst);
	echo $OUTPUT->footer();

}