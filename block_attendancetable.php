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
 * Renders information from attendance
 *
 * @package    block_attendancetable
 * @copyright  2023, Alexis Navas <a22alenavest@inspedralbes.cat> <alexisnavas98@hotmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


class block_attendancetable extends block_base
{
    public function init()
    {
        $this->title = get_string('attendancetable', 'block_attendancetable');
    }

    public function get_content()
    {
        global $DB, $PAGE, $CFG, $USER, $COURSE;
        require_once $CFG->dirroot . '/mod/attendance/locallib.php';
        $id = required_param('id', PARAM_INT);
        $allattendances = get_coursemodules_in_course('attendance', $id);
        $attendanceparams = new mod_attendance_view_page_params(); // Page parameters, necessary to create mod_attendance_structure object.

        $attendanceparams->studentid   = null;
        $attendanceparams->view        = null;
        $attendanceparams->curdate     = null;
        $attendanceparams->mode        = 1;
        $attendanceparams->groupby     = 'course';
        $attendanceparams->sesscourses = 'current';

        if (count($allattendances) > 0) {
            $shownusers = [];

            $context_course = context_course::instance($id);
            $users = get_enrolled_users($context_course, '');

            $firstattendance = $allattendances[array_keys($allattendances)[0]];
            $course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
            $attendance = $DB->get_record('attendance', array('id' => $firstattendance->instance), '*', MUST_EXIST);
            $context = context_module::instance($firstattendance->id);

            require_login($course, true, $firstattendance);

            $attstructure = new mod_attendance_structure($attendance, $firstattendance, $course, $context, $attendanceparams);
            $attendanceparams->init($firstattendance);


            if ($this->content !== null) {
                return $this->content;
            }

            if (
                has_capability('mod/attendance:canbelisted', $context, null, false) &&
                has_capability('mod/attendance:view', $context)
            ) {

                $attendances = get_all_instances_in_course('attendance', $COURSE, null, true);
                $userdata = new attendance_user_data($attstructure, $USER->id);
                $sessioninfo = [];
                foreach ($attendances as $attinst) {
                    $cmid = $attinst->coursemodule;
                    $cm  = get_coursemodule_from_id('attendance', $cmid, 0, false, MUST_EXIST);
                    if (!empty($cm->deletioninprogress)) {
                        // Don't display if this attendance is in recycle bin.
                        continue;
                    }
                    $context = context_module::instance($cmid, MUST_EXIST);
                    $attendance = $DB->get_record('attendance', ['id' => $cm->instance], '*', MUST_EXIST);

                    $sessionsql = "SELECT * FROM mdl_attendance_sessions WHERE attendanceid = {$attinst->id};";
                    $sessionresult = $DB->get_records_sql($sessionsql);
                    foreach ($sessionresult as $session) {
                        $logsql = "SELECT * FROM mdl_attendance_log WHERE studentid = {$USER->id} AND sessionid={$session->id};";
                        $logresult = $DB->get_record_sql($logsql);

                        if ($logresult->statusid != NULL) {
                            $statussql = "SELECT * FROM mdl_attendance_statuses WHERE id = {$logresult->statusid};";
                            $statusresult = $DB->get_record_sql($statussql);
                            array_push($sessioninfo, [date("d-m-Y", $session->sessdate), $statusresult->description]);
                        }
                    }
                }
                $sessioninfo = $this->sort_array($sessioninfo, 'student');
                $sessionscount = count($sessioninfo);
                $this->content->text .= '<div class="progress">';
                foreach ($sessioninfo as $session) {
                    //$this->content->text .= '<p>' . $session[0] . ' ' . $session[1] . '</p>';
                    $this->content->text .= '
                    <div class="progress-bar bg-info" role="progressbar" style="width: ' . 100/$sessionscount . '%" aria-valuenow="33" aria-valuemin="0" aria-valuemax="100"></div>';
                    
                }
                $this->content->text .= '</div>';
                

                /*$this->content->text .= '<div class="progress">
                                            <div class="progress-bar" role="progressbar" style="width: 34%" aria-valuenow="33" aria-valuemin="0" aria-valuemax="100"></div>
                                            <div class="progress-bar bg-success" role="progressbar" style="width: 33%" aria-valuenow="33" aria-valuemin="0" aria-valuemax="100"></div>
                                            <div class="progress-bar bg-info" role="progressbar" style="width: 33%" aria-valuenow="33" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>';*/

                $averagepercentage = 0;
                $totaluf = 0;
                $totalpercentage = 0;
                foreach ($userdata->coursesatts as $ca) {
                    $userattendancesummary = new mod_attendance_summary($ca->attid, $USER->id);
                    $totalstats = 0;
                    $ufpercentage = floatval(format_float($userattendancesummary->get_all_sessions_summary_for($USER->id)->takensessionspercentage * 100));
                    $userstats = $userattendancesummary->get_taken_sessions_summary_for($USER->id)->userstakensessionsbyacronym[0] ?: null;
                    $totalstats += $userstats['P'] ?: 0;
                    $totalstats += $userstats['A'] ?: 0;
                    $totalstats += $userstats['T'] ?: 0;
                    $totalstats += $userstats['J'] ?: 0;
                    if ($totalstats != 0) {
                        $totalpercentage += $ufpercentage;
                        $totaluf++;
                    }
                }

                $averagepercentage = $totalpercentage / $totaluf;

                $table = new html_table();
                $head = new stdClass();

                $head->cells[] = get_string('tablestudent', 'block_attendancetable');
                $head->cells[] = get_string('tablepercentage', 'block_attendancetable');

                $table->attributes['border'] = 1;
                $table->attributes['class'] = "studenttable";
                $table->head = $head->cells;

                $rows = new html_table_row();
                $namecell = new html_table_cell();
                $namecell = html_writer::start_div();
                $namecell .= html_writer::div('Hola');
                $namecell .= html_writer::end_div();
                $percentangecell = html_writer::start_div();
                $percentangecell .= html_writer::div($averagepercentage . "%");
                $percentangecell .= html_writer::end_div();
                $rows->cells[] = $namecell;
                $rows->cells[] = $percentangecell;
                $table->data[] = $rows;

                $this->content->text .= html_writer::div(html_writer::table($table), '', ['id' => 'studenttable']);

                return $this->content;
            } else if (
                has_capability('mod/attendance:takeattendances', $context) or
                has_capability('mod/attendance:changeattendances', $context)
            ) {
                foreach ($users as $user) {
                    $roles = get_user_roles($context_course, $user->id, true);
                    $role = key($roles);
                    $rolename = $roles[$role]->shortname;
                    if ($rolename == 'student') {
                        $userdata = new attendance_user_data($attstructure, $user->id);
                        $averagepercentage = 0;
                        $totaluf = 0;
                        $totalpercentage = 0;
                        foreach ($userdata->coursesatts as $ca) {
                            $userattendancesummary = new mod_attendance_summary($ca->attid, $user->id);
                            $totalstats = 0;
                            $ufpercentage = floatval(format_float($userattendancesummary->get_all_sessions_summary_for($user->id)->takensessionspercentage * 100));
                            $userstats = $userattendancesummary->get_taken_sessions_summary_for($user->id)->userstakensessionsbyacronym[0] ?: null;
                            $totalstats += $userstats['P'] ?: 0;
                            $totalstats += $userstats['A'] ?: 0;
                            $totalstats += $userstats['T'] ?: 0;
                            $totalstats += $userstats['J'] ?: 0;
                            if ($totalstats != 0) {
                                $totalpercentage += $ufpercentage;
                                $totaluf++;
                            }
                        }

                        $averagepercentage = $totalpercentage / $totaluf;

                        if ($totaluf != 0) array_push($shownusers, [$user->firstname, $user->id, round($averagepercentage, 2)]);
                        $shownusers = $this->sort_array($shownusers, 'teacher');
                    }
                    $shownusers = array_slice($shownusers, 0, $this->config->amount ?: 5);
                }
                $this->content = new stdClass;


                $this->content->text .= html_writer::div(get_string('tablemessage', 'block_attendancetable'));
                $this->content->text .= html_writer::empty_tag('br');


                $table = new html_table();
                $head = new stdClass();

                $head->cells[] = get_string('tablestudent', 'block_attendancetable');
                $head->cells[] = get_string('tablepercentage', 'block_attendancetable');

                $table->attributes['border'] = 1;
                $table->attributes['class'] = "studenttable";
                $table->head = $head->cells;

                foreach ($shownusers as $shownuser) {
                    $rows = new html_table_row();
                    $namecell = new html_table_cell();
                    $namecell = html_writer::start_div();
                    $namecell = html_writer::link("{$CFG->wwwroot}/user/profile.php?id={$shownuser[1]}", $shownuser[0]);
                    $namecell .= html_writer::end_div();
                    $percentangecell = html_writer::start_div();
                    $percentangecell .= html_writer::div($shownuser[2] . "%");
                    $percentangecell .= html_writer::end_div();
                    $rows->cells[] = $namecell;
                    $rows->cells[] = $percentangecell;
                    $table->data[] = $rows;
                }

                $this->content->text .= html_writer::div(html_writer::table($table), '', ['id' => 'studenttable']);

                $formattributes = array('action' => $CFG->wwwroot . '/report/attendancetable/', 'method' => 'get');
                $form .= html_writer::start_tag('form', $formattributes);
                $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id', 'value' => $id));
                $form .= html_writer::empty_tag('input', array('type' => 'submit', 'class' => 'btn btn-secondary', 'value' => get_string('gototext', 'block_attendancetable')));
                $form .= html_writer::end_tag('form');
                $summarybutton = html_writer::start_div();
                $summarybutton .= html_writer::div($form, 'centerItem');
                $summarybutton .= html_writer::end_div();

                $this->content->text .= $summarybutton;




                return $this->content;
            }
        }
    }

    /**
     * Sorts array for users shown on this block
     *
     * @param array $arr The array you want to sort
     * @return array The sorted array
     */
    private function sort_array($arr, $role)
    {
        if ($role == 'teacher') {
            $len = count($arr);
            for ($i = 0; $i < $len; $i++) {
                for ($j = 0; $j < $len - $i - 1; $j++) {
                    if ($arr[$j][2] > $arr[$j + 1][2]) {
                        $temp = $arr[$j];
                        $arr[$j] = $arr[$j + 1];
                        $arr[$j + 1] = $temp;
                    }
                }
            }
        } else if ($role == 'student') {
            $len = count($arr);
            for ($i = 0; $i < $len; $i++) {
                for ($j = 0; $j < $len - $i - 1; $j++) {
                    if ($arr[$j][0] > $arr[$j + 1][0]) {
                        $temp = $arr[$j];
                        $arr[$j] = $arr[$j + 1];
                        $arr[$j + 1] = $temp;
                    }
                }
            }
        }
        return $arr;
    }
}
