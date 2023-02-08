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
        $allattendances = get_coursemodules_in_course('attendance', $id);
        $attendanceparams = new mod_attendance_view_page_params(); // Page parameters, necessary to create mod_attendance_structure object.

        $attendanceparams->studentid   = null;
        $attendanceparams->view        = null;
        $attendanceparams->curdate     = null;
        $attendanceparams->mode        = 1;
        $attendanceparams->groupby     = 'course';
        $attendanceparams->sesscourses = 'current';

        if (count($allattendances) > 0) {
            $firstattendance = $allattendances[array_keys($allattendances)[0]];
            $course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
            $attendance = $DB->get_record('attendance', array('id' => $firstattendance->instance), '*', MUST_EXIST);

            $context = context_module::instance($firstattendance->id);

            $attendanceparams->init($firstattendance);

            $attstructure = new mod_attendance_structure($attendance, $firstattendance, $course, $context, $attendanceparams);
            $context_course = context_course::instance($id);
            $dataattendancetable = new stdclass();
            //$printattendancetable = new attendancetable_print_table($dataattendancetable, $attstructure, $context, $context_course, $firstattendance->id);

            require_login($course, true, $firstattendance);
            var_dump($attstructure);
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
}
