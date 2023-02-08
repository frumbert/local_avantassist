<?php

// hit the index with the report required and it asks for the dates to show
// then redirect to the report with these added

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/completionlib.php');
require_once('locallib.php');

$report = optional_param('report', 'engagement', PARAM_TEXT);
$range = optional_param('range','', PARAM_TEXT);

$params = ['report'=>$report, 'range'=>$range];

// internals
$pagetitle = get_string('pluginname', 'local_avantassist');
$context = context_system::instance();

// require authentication and capability
require_login();
// require_capability('local/avantassist:viewreports', $context);

// set up page
$PAGE->set_url('/local/classreport/index.php', $params);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

// $renderer = $PAGE->get_renderer('local_avantassist');
// echo $renderer->render_index($);

$data = new stdClass();
$data->report = $report;
switch ($range) {
	case "thisweek":
		$data->from = strtotime("-1 week monday 00:00:00");
		$data->to = strtotime("today 23:59:59");
	break;

	case "lastweek":
		$data->from = strtotime("-2 week 00:00:00");
		$data->to = strtotime("-1 week sunday 23:59:59");
	break;

	case "lastmonth":
		$data->from = mktime(0, 0, 0, date('m')-1, 01);
		$data->to = mktime(23, 59, 59, date('m'), 0);
	break;

	case "month":
		$data->from = mktime(0, 0, 0, date('m'), 01);
		$data->to = strtotime("yesterday 23:59:59");
	break;

	case "yesterday":
		$data->from = strtotime("yesterday", time());
		$data->to = strtotime("today", time()) - 1;
	break;

	case "all":
		$data->from = strtotime("-10 year 00:00:00", time());
		$data->to = strtotime("tomorrow", time()) - 1;
		break;

	case "today":
	default:
		$data->from = strtotime("today", time());
		$data->to = strtotime("tomorrow", $data->from) - 1;
}

require_once('filter_form.php');
$filterform = new local_avantassist_dateselector(null, $data);
// $filterform->set_data($formdata);

if ($filterform->is_cancelled()) {
	die;
}

$formdata = $filterform->get_data();
if ($formdata) {

	$url = new moodle_url('report.php', ['report'=>$formdata->report, 'from'=>$formdata->filter_starttime, 'to'=>$formdata->filter_endtime]);

	redirect($url);

}

echo $OUTPUT->header();
$filterform->display();
echo $OUTPUT->footer();