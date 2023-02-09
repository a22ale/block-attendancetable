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
                $totalUF = 0;
                $totalPercentage = 0;
                if ($rolename == "student") {
                    $userdata = new attendance_user_data($attStructure, $user->id);
                    $averagePercentage = 0;
                    foreach ($userdata->coursesatts as $ca) {
                        $userAttendanceSummary = new mod_attendance_summary($ca->attid, $user->id);
                        $totalZeroes = 0;
                        $totalPercentage += floatval(format_float($userAttendanceSummary->get_all_sessions_summary_for($user->id)->takensessionspercentage * 100));
                        $totalUF++;
                    }

                    $averagePercentage = $totalPercentage / $totalUF;

                    if (count($shownUsers) < 5) {
                        array_push($shownUsers, [$user->firstname, $averagePercentage]);
                        $shownUsers = $this->sortArray($shownUsers);
                    } else {
                        if ($shownUsers[count($shownUsers) - 1] > $averagePercentage) {
                            $shownUsers[count($shownUsers) - 1] = [$user->firstname, $averagePercentage];
                            $shownUsers = $this->sortArray($shownUsers);
                        }
                    }
                }
            }
            if ($this->content !== null) {
                return $this->content;
            }

            $this->content = new stdClass;
            $this->content->text .= '<table style="width: 80%">';
            foreach ($shownUsers as $shownUser) {
                $this->content->text .=
                    '<tr>
                    <td>' . $shownUser[0] . "</td><td>" .  $shownUser[1] . '</td>
                </tr>';
            }
            $this->content->text .= '</table>';
            $this->content->text .= '<div class="centerButton"> <form method="GET" action="../report/attendancetable/">
                                    <input type="hidden" name="id" value="' . $id . '">
                                    <input class="btn btn-secondary" type="submit" value="' . get_string('goTo_text', 'block_attendancetable') . '" />
                                </form></div>';

            return $this->content;
        }
    }

    /**
     * Sorts array for users shown on this block
     *
     * @param array $array The array you want to sort
     * @return array The sorted array
     */
    private function sortArray($array)
    {
        $len = count($array);
        for ($i = 0; $i < $len; $i++) {
            for ($j = 0; $j < $len - $i - 1; $j++) {
                if ($array[$j][1] > $array[$j + 1][1]) {
                    $temp = $array[$j];
                    $array[$j] = $array[$j + 1];
                    $array[$j + 1] = $temp;
                }
            }
        }
        return $array;
    }
}
