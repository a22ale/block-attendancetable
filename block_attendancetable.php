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
require($CFG->dirroot . '/blocks/attendancetable/locallib.php');
define('SORT_STUDENT', 'sessiontime');
define('SORT_TEACHER', 'averagepercentage');

class block_attendancetable extends block_base
{
    public function init()
    {
        $this->title = get_string('attendancetable', 'block_attendancetable');
    }

    public function get_content()
    {
        global $DB, $CFG, $USER, $COURSE;
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
                // This code is run if the current user is a student
                $this->page->requires->js('/blocks/attendancetable/lib.js');
                $attendances = get_all_instances_in_course('attendance', $COURSE, null, true);
                $userdata = new attendance_user_data($attstructure, $USER->id);
                $usersessions = [];

                foreach ($attendances as $index => $attinst) {
                    $cmid = $attinst->coursemodule;
                    $cm  = get_coursemodule_from_id('attendance', $cmid, 0, false, MUST_EXIST);
                    if (!empty($cm->deletioninprogress)) {
                        // Don't display if this attendance is in recycle bin.
                        continue;
                    }

                    $context = context_module::instance($cmid, MUST_EXIST);
                    $attendance = $DB->get_record('attendance', ['id' => $cm->instance], '*', MUST_EXIST);

                    $selectattendancesessions = "SELECT * FROM mdl_attendance_sessions WHERE attendanceid = {$attinst->id};";
                    $attendancesessions = $DB->get_records_sql($selectattendancesessions);
                    foreach ($attendancesessions as $attendancesession) {
                        $selectlog = "SELECT * FROM mdl_attendance_log WHERE studentid = {$USER->id} AND sessionid={$attendancesession->id};";
                        $logresult = $DB->get_record_sql($selectlog);

                        if ($logresult->statusid != NULL) {
                            $selectstatus = "SELECT * FROM mdl_attendance_statuses WHERE id = {$logresult->statusid};";
                            $attendancestatusresult = $DB->get_record_sql($selectstatus);
                            $attendanceurl = 'mod/attendance/view.php?id=' . $cm->id;
                            $attendanceurllong = $CFG->wwwroot . '/mod/attendance/view.php?id=' . $cm->id;

                            $currentsession = new user_session(
                                date("d/m/Y H:i", $attendancesession->sessdate),
                                $attendancestatusresult->description,
                                get_string(strtolower($attendancestatusresult->description), 'block_attendancetable'),
                                $attinst->name,
                                $attendanceurl,
                                $attendanceurllong,
                                $attendancesession->sessdate
                            );
                            array_push($usersessions, $currentsession);
                        }
                    }
                }

                if (count($usersessions) > 0) {
                    if ($this->config->show ?? 1) {
                        $usersessions = $this->sort_array($usersessions, SORT_STUDENT);
                        $usersessioncount = count($usersessions);
                        $this->content->text = html_writer::start_div("progress border border-secondary progressBar rounded");
                        foreach ($usersessions as $index => $session) {
                            $barclass = '';
                            switch ($session->attendanceenglish) {
                                case 'Absent':
                                    $barclass = 'bg-danger';
                                    break;
                                case 'Present':
                                    $barclass = 'bg-success';
                                    break;
                                case 'Late':
                                    $barclass = 'bg-warning';
                                    break;
                                case 'Excused':
                                    $barclass = 'bg-info';
                                    break;
                            }
                            if ($index < $usersessioncount - 1) {
                                $barclass .= ' border-secondary border-right';
                            }

                            $writerbar = html_writer::start_div('progress-bar '  . $barclass, array(
                                'onmouseover' => 'showInfo("../blocks/attendancetable/pix/",' .
                                    json_encode($session) . ')', 'role' => 'progress-bar', 'style' => 'width: ' . 100 / $usersessioncount . '%',
                                'aria-value' => 100 / $usersessioncount, 'onclick' => 'onClick("' . $session->attendanceurl . '&view=1&curdate=' . $session->sessiontime . '")'
                            ));
                            $writerbar .= html_writer::end_div();
                            $this->content->text .= $writerbar;
                        }
                        $this->content->text .= html_writer::end_div();
                        $writerdivunderbar .= html_writer::start_div();
                        $writersmall = html_writer::start_tag('small', array('id' => 'hideOnHover'));
                        $writersmall .= get_string('hovermessage', 'block_attendancetable');
                        $writersmall .= html_writer::end_tag('small');
                        $writerdivunderbar .= html_writer::div($writersmall);
                        $writerdivunderbar .= html_writer::start_div('', array('id' => 'attendanceInfoBox', 'style' => 'display: none'));
                        $writerdivunderbar .= html_writer::end_div();
                        $writerdivunderbar .= html_writer::end_div();
                        $this->content->text .= $writerdivunderbar;
                    }

                    $userattendancepercentages = $this->get_attendance_percentages($userdata, $USER->id, $id);

                    //Text shown on the average part
                    $avgpercentagetext = get_string('avgpercentage', 'block_attendancetable') . ': ';
                    $avgpercentagevalue = $userattendancepercentages->averagepercentage . '%';
                    $avgcoursetext = get_string('avgcoursepercentage', 'block_attendancetable') . ': ';
                    $avgcoursevalue = $userattendancepercentages->averagecoursepercentage . '%';

                    $table = new html_table();
                    $table->attributes['class'] = 'attendancetable';

                    foreach ($userattendancepercentages->sectionpercentages as $sectionpercentage) {
                        //Link to the current's section mod_attendance
                        $linkrow = new html_table_row();
                        $writerlinkb = html_writer::tag('b', $sectionpercentage[0]);
                        $writerlink = html_writer::tag('a', $writerlinkb, array('href' => $sectionpercentage[2]));
                        $linkcell = new html_table_cell();
                        $linkcell = html_writer::start_div();
                        $linkcell = html_writer::div($writerlink);
                        $linkcell .= html_writer::end_div();
                        $linkrow->cells[] = $linkcell;

                        //Row containing this section's attendance percentage
                        $percentagerow = new html_table_row();
                        $messagecell = new html_table_cell();
                        $messagecell = html_writer::start_div();
                        $messagecell = html_writer::div(get_string('sectionpercentagetext', 'block_attendancetable') . ': ');
                        $messagecell .= html_writer::end_div();
                        $valuecell = new html_table_cell();
                        $valuecell = html_writer::start_div();
                        $valuecell .= html_writer::div($sectionpercentage[1] . '%');
                        $valuecell .= html_writer::end_div();
                        $percentagerow->cells[] = $messagecell;
                        $percentagerow->cells[] = $valuecell;

                        $table->data[] = $linkrow;
                        $table->data[] = $percentagerow;
                    }

                    //Check report_attendancetable link
                    $checklinkrow = new html_table_row();
                    $writerchecklinkb = html_writer::tag('b', get_string('gototext', 'block_attendancetable'));
                    $writerchecklink = html_writer::tag('a', $writerchecklinkb, array('href' => $CFG->wwwroot . '/report/attendancetable/?id=' . $id));
                    $checklinkcell = new html_table_cell();
                    $checklinkcell = html_writer::start_div();
                    $checklinkcell = html_writer::div($writerchecklink);
                    $checklinkcell .= html_writer::end_div();
                    $checklinkrow->cells[] = $checklinkcell;

                    //All courses' average
                    $avgrow = new html_table_row();
                    $avgpercentagetextcell = new html_table_cell();
                    $avgpercentagetextcell = html_writer::start_div();
                    $avgpercentagetextcell = html_writer::div($avgpercentagetext);
                    $avgpercentagetextcell .= html_writer::end_div();
                    $avgpercentagevaluecell = html_writer::start_div();
                    $avgpercentagevaluecell .= html_writer::div($avgpercentagevalue);
                    $avgpercentagevaluecell .= html_writer::end_div();
                    $avgrow->cells[] = $avgpercentagetextcell;
                    $avgrow->cells[] = $avgpercentagevaluecell;

                    //Current course's average
                    $courserow = new html_table_row();
                    $coursepercentagetextcell = new html_table_cell();
                    $coursepercentagetextcell = html_writer::start_div();
                    $coursepercentagetextcell = html_writer::div($avgcoursetext);
                    $coursepercentagetextcell .= html_writer::end_div();
                    $coursepercentagevaluecell = html_writer::start_div();
                    $coursepercentagevaluecell .= html_writer::div($avgcoursevalue);
                    $coursepercentagevaluecell .= html_writer::end_div();
                    $courserow->cells[] = $coursepercentagetextcell;
                    $courserow->cells[] = $coursepercentagevaluecell;

                    $table->data[] = $checklinkrow;
                    $table->data[] = $avgrow;
                    $table->data[] = $courserow;
                    $this->content->text .= html_writer::div(html_writer::table($table), '', ['id' => 'attendancetable']);
                } else {
                    $this->content->text = get_string('nosession', 'block_attendancetable');
                }


                return $this->content;
            } else if (
                has_capability('mod/attendance:takeattendances', $context) or
                has_capability('mod/attendance:changeattendances', $context)
            ) {
                // This code is run if the current user is a (non-editing) teacher or admin
                foreach ($users as $user) {
                    $roles = get_user_roles($context_course, $user->id, true);
                    $role = key($roles);
                    $rolename = $roles[$role]->shortname;
                    if ($rolename == 'student') {
                        $userdata = new attendance_user_data($attstructure, $user->id);
                        $userpercentage = $this->get_attendance_percentages($userdata, $user->id);

                        if ($userpercentage->totalsection != 0) {
                            $currentstudent = new student_info(
                                $user->firstname,
                                $user->id,
                                floatval(str_replace(',', '.', $userpercentage->averagepercentage))
                            );
                            array_push($shownusers, $currentstudent);
                        }
                        $shownusers = $this->sort_array($shownusers, SORT_TEACHER);
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
                    $namecell = html_writer::link("{$CFG->wwwroot}/user/profile.php?id={$shownuser->id}", $shownuser->firstname);
                    $namecell .= html_writer::end_div();
                    $percentangecell = html_writer::start_div();
                    $percentangecell .= html_writer::div(number_format($shownuser->averagepercentage, 1, ',', '') . "%");
                    $percentangecell .= html_writer::end_div();
                    $rows->cells[] = $namecell;
                    $rows->cells[] = $percentangecell;
                    $table->data[] = $rows;
                }

                $this->content->text .= html_writer::div(html_writer::table($table), '', ['id' => 'studenttable']);

                //Button to check report_attendancetable
                $formattributes = array('action' => $CFG->wwwroot . '/report/attendancetable/', 'method' => 'get');
                $form .= html_writer::start_tag('form', $formattributes);
                $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id', 'value' => $id));
                $form .= html_writer::empty_tag('input', array(
                    'type' => 'submit', 'class' => 'btn btn-secondary',
                    'value' => get_string('gototext', 'block_attendancetable')
                ));
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
     * @param string $role Either SORT_TEACHER or SORT_STUDENT
     * @return array The sorted array
     */
    private function sort_array($arr, $role)
    {
        $len = count($arr);
        for ($i = 0; $i < $len; $i++) {
            for ($j = 0; $j < $len - $i - 1; $j++) {
                if ($arr[$j]->$role > $arr[$j + 1]->$role) {
                    $temp = $arr[$j];
                    $arr[$j] = $arr[$j + 1];
                    $arr[$j + 1] = $temp;
                }
            }
        }
        return $arr;
    }

    /**
     * Returns the average course' and all courses' attendance for the specified student, and if
     * the user is a student also returns each section's percentage for the specified course
     *
     * @param attendance_user_data $userdata The student's user data
     * @param string $userid The student's id
     * @param int $courseid The current course's id, only used if the current user is a student
     * @return user_attendance_percentages An object containing both average percentages and number of sections, and 
     * if the user is a student also an array containing all sections' info
     */
    private function get_attendance_percentages($userdata, $userid, $courseid = 0)
    {
        $userattendance = new user_attendance_percentages();
        $courseinfo = new course_info();

        foreach ($userdata->coursesatts as $ca) {
            $userattendancesummary = new mod_attendance_summary($ca->attid, $userid);
            $usertotalstats = 0;
            $currentsectionpercentage = round(($userattendancesummary->get_all_sessions_summary_for($userid)->takensessionspercentage * 100), 1);
            $userstats = $userattendancesummary->get_taken_sessions_summary_for($userid)->userstakensessionsbyacronym[0] ?: null;
            $usertotalstats += $userstats['P'] ?: 0;
            $usertotalstats += $userstats['A'] ?: 0;
            $usertotalstats += $userstats['T'] ?: 0;
            $usertotalstats += $userstats['J'] ?: 0;
            if ($usertotalstats != 0) {
                $userattendance->totalpercentage += $currentsectionpercentage;
                $userattendance->totalsection++;
                if ($ca->courseid == $courseid) {
                    $this->get_current_course_percentages($ca, $userattendance, $currentsectionpercentage, $courseinfo);
                }
            }
        }

        $userattendance->averagecoursepercentage = $courseinfo->get_average();
        $userattendance->averagepercentage = $userattendance->get_average();

        return $userattendance;
    }

    /**
     * This function is called from get_attendance_percentages to get the course's section info
     * if the user is a student
     * 
     * @param object $ca The current course attendance
     * @param user_attendance_percentages $coursesectionpercentages The user's attendance information
     * @param float $sectionpercentage Current section's attendance percentage
     * @param object $courseinfo Stores the current course's total percentage and number of sections
     */
    private function get_current_course_percentages($ca, $userattendance, $sectionpercentage, $courseinfo)
    {
        global $CFG;
        $url = $CFG->wwwroot . '/mod/attendance/view.php?id=' . $ca->cmid;
        $courseinfo->totalpercentage += $sectionpercentage;
        array_push($userattendance->sectionpercentages, [$ca->attname, number_format($sectionpercentage, 1, ',', ''), $url]);
        $courseinfo->coursesections++;
    }
}
