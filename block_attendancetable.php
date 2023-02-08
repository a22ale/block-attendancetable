<?php
defined('MOODLE_INTERNAL') || die();
require_once "../config.php";
require_once $CFG->dirroot . '/mod/attendance/locallib.php';


class block_attendancetable extends block_base
{
    public function init()
    {
        $this->title = get_string('attendancetable', 'block_attendancetable');
    }

    public function get_content()
    {
        global $DB;
        global $PAGE;
        $id = required_param('id', PARAM_INT);
        $allAttendances = get_coursemodules_in_course('attendance', $id);
        $attendanceParams = new mod_attendance_view_page_params(); // Page parameters, necessary to create mod_attendance_structure object.

        $attendanceParams->studentid   = null;
        $attendanceParams->view        = null;
        $attendanceParams->curdate     = null;
        $attendanceParams->mode        = 1;
        $attendanceParams->groupby     = 'course';
        $attendanceParams->sesscourses = 'current';

        if (count($allAttendances) > 0) {
            $shownUsers = [];

            $context_course = context_course::instance($id);
            $users          = get_enrolled_users($context_course, '');

            $firstAttendance = $allAttendances[array_keys($allAttendances)[0]];
            $course          = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
            $attendance      = $DB->get_record('attendance', array('id' => $firstAttendance->instance), '*', MUST_EXIST);
            $context         = context_module::instance($firstAttendance->id);

            require_login($course, true, $firstAttendance);

            $attStructure = new mod_attendance_structure($attendance, $firstAttendance, $course, $context, $attendanceParams);
            $attendanceParams->init($firstAttendance);

            foreach ($users as $user) {
                $roles = get_user_roles($context_course, $user->id, true);
                $role = key($roles);
                $rolename = $roles[$role]->shortname;
                if ($rolename == "student") {
                    $userdata = new attendance_user_data($attStructure, $user->id);
                    $averagePercentage = 0;
                    $count = 0;
                    foreach ($userdata->coursesatts as $ca) {
                        $userAttendanceSummary = new mod_attendance_summary($ca->attid, $user->id);
                        //$totalSessions = $userAttendanceSummary->get_taken_sessions_summary_for($user->id)->numtakensessions;
                        $totalSessions = 2;
                        $percentageAttendance = format_float($userAttendanceSummary->get_taken_sessions_summary_for($user->id)->percentagesessionscompleted);
                        if($percentageAttendance != 0) $averagePercentage += $percentageAttendance;
                        $count++;
                        if($count == $totalSessions) {
                            if($percentageAttendance != 0) $averagePercentage /= $totalSessions;
                            if (count($shownUsers) < 5) {
                                array_push($shownUsers, $averagePercentage);
                                $this->sortArray($shownUsers);
                            } else {
                                if (floatval($shownUsers[4]) > floatval($averagePercentage)) {
                                    $shownUsers[4] = $percentageAttendance;
                                    $this->sortArray($shownUsers);
                                }
                            }
                            $count = 0;
                            $averagePercentage = 0;
                        }
                    }
                }
            }
            var_dump($shownUsers);
            //var_dump($users);
            if ($this->content !== null) {
                return $this->content;
            }

            $this->content = new stdClass;
            $this->content->text = '<div class="centerButton"> <form method="GET" action="../report/attendancetable/">
                                    <input type="hidden" name="id" value="' . $id . '">
                                    <input class="btn btn-secondary" type="submit" value="' . get_string('goTo_text', 'block_attendancetable') . '" />
                                </form></div>';

            return $this->content;
        }
    }

    private function sortArray($array)
    {
        foreach ($array as $index => $item) {
            if (count($array) > 1) {
                for ($i = 0; $i < count($array); $i++) {
                    if ($array[$index] > $array[$index + 1]) {
                        $temp = $array[$index];
                        $array[$index] = $array[$index + 1];
                        $array[$index + 1] = $temp;
                    }
                }
            }
        }
    }
}
