<?php
/**
 * avant utility plugin
 *
 * @package    local/avantassist
 * @copyright  2022 tim st.clair (https://github.com/frumbert)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'AvantAssist Reports';
$string['viewreports']= 'View or download Avant Assist reports';

$string['filter'] = 'Date filter for report ”{$a}”';
$string['filter_apply'] = 'Download';
$string['starttime'] = 'Start date/time';
$string['endtime'] = 'End date/time';

$string['predefined_dates'] = '<div class="row"><div class="col-md-3">Predefined ranges:</div><div class="col-md-9">
<a href="?report={$a}&range=thisweek" class="btn btn-link">This week</a> 
<a href="?report={$a}&range=lastweek" class="btn btn-link">Last week</a> 
<a href="?report={$a}&range=lastmonth" class="btn btn-link">Last month</a> 
<a href="?report={$a}&range=month" class="btn btn-link">Month to date</a> 
<a href="?report={$a}&range=yesterday" class="btn btn-link">Yesterday</a> 
<a href="?report={$a}&range=today" class="btn btn-link">Today</a>
<a href="?report={$a}&range=all" class="btn btn-link">All time</a> </div></div>';

$string['generate_reports'] = 'Generate Reports';