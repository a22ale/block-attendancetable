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
 * Classes related to block_attendancetable
 *
 * @package    block_attendancetable
 * @copyright  2023, Alexis Navas <a22alenavest@inspedralbes.cat> <alexisnavas98@hotmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/attendance/locallib.php');

/**
 * Class that stores the user's attendance percentages
 *
 * @package    block_attendancetable
 * @copyright  2023, Alexis Navas <a22alenavest@inspedralbes.cat> <alexisnavas98@hotmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_attendance_percentages
{
    /** @var float user's average attedance for all courses */
    public $averagepercentage = 0;
    /** @var float user's average attedance for current course */
    public $averagecoursepercentage = 0;
    /** @var int course's section count */
    public $totalsection = 0;
    /** @var array course's sections' percentages */
    public $sectionpercentages = [];
    /** @var float all courses' total percentage  */
    public $totalpercentage = 0;
    /** @var array array containing user_section_info */
    public $sections = [];
    /** @var array array containing each courses' percentage */
    public $coursepercentages = [];

    /**
     * Returns the user's attendance average, rounded to the specified
     * decimal count (1 by default)
     * 
     * @param int $decimals How many decimals you want your average to have
     * @return float The user's attendance average
     */
    public function get_average($decimals = 1)
    {
        return number_format($this->totalpercentage / $this->totalsection, $decimals, ',', '');
    }
}

/**
 * Class that stores the course's total attendance and section amount
 *
 * @package    block_attendancetable
 * @copyright  2023, Alexis Navas <a22alenavest@inspedralbes.cat> <alexisnavas98@hotmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_info
{
    /** @var float course's total percentage */
    public $totalpercentage = 0;
    /** @var float course's number of attendance modules */
    public $coursesections = 0;

    /**
     * Returns the course's attendance average, rounded to the specified
     * decimal count (1 by default)
     * 
     * @param int $decimals How many decimals you want your average to have
     * @return float The user's attendance average
     */
    public function get_average($decimals = 1)
    {
        return number_format($this->totalpercentage / $this->coursesections, $decimals, ',', '');
    }
}

/**
 * Class that stores the section's attendance percentage and course id
 *
 * @package    block_attendancetable
 * @copyright  2023, Alexis Navas <a22alenavest@inspedralbes.cat> <alexisnavas98@hotmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_section_info
{
    /** @var float section's percentage */
    public $attendancepercentage = 0;
    /** @var int course's id */
    public $courseid = 0;
    /** @var string course's name */
    public $coursename = 0;
}


/**
 * Class that stores the user's session info
 *
 * @package    block_attendancetable
 * @copyright  2023, Alexis Navas <a22alenavest@inspedralbes.cat> <alexisnavas98@hotmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_session
{
    /** @var string session's date in DD/MM/YYYY HH:mm format */
    public $sessiondate;
    /** @var string session's attendance in english */
    public $attendanceenglish;
    /** @var string session's localized attendance */
    public $attendance;
    /** @var string session's attendance name */
    public $attendancename;
    /** @var string url to mod_attendance's page -short- */
    public $attendanceurl;
    /** @var string url to mod_attendance's page -long- */
    public $attendanceurllong;
    /** @var int session's time in seconds */
    public $sessiontime;
    /** @var string course's name */
    public $coursename;
    /** @var string url to course */
    public $courseurl;

    public function __construct($sessiondate, $attendanceenglish, $attendance, $attendancename, $attendanceurl, $attendanceurllong, $sessiontime)
    {
        $this->sessiondate = $sessiondate;
        $this->attendanceenglish = $attendanceenglish;
        $this->attendance = $attendance;
        $this->attendancename = $attendancename;
        $this->attendanceurl = $attendanceurl;
        $this->attendanceurllong = $attendanceurllong;
        $this->sessiontime = $sessiontime;
    }
}

/**
 * Class that stores the student's info
 *
 * @package    block_attendancetable
 * @copyright  2023, Alexis Navas <a22alenavest@inspedralbes.cat> <alexisnavas98@hotmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class student_info
{
    /** @var string student's first name */
    public $firstname;
    /** @var int student's id */
    public $id;
    /** @var float student's attendance percentage */
    public $averagepercentage;

    public function __construct($firstname, $id, $averagepercentage)
    {
        $this->firstname = $firstname;
        $this->id = $id;
        $this->averagepercentage = $averagepercentage;
    }
}

/**
 * Class that stores the course's shown info
 *
 * @package    block_attendancetable
 * @copyright  2023, Alexis Navas <a22alenavest@inspedralbes.cat> <alexisnavas98@hotmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_percentage
{
    /** @var string course's percentage in string */
    public $percentage;
    /** @var int student's id */
    public $id;
    /** @var string course's url */
    public $url;
    /** @var string course's name */
    public $coursename;

    public function __construct($percentage, $id, $url, $coursename)
    {
        $this->percentage = $percentage;
        $this->id = $id;
        $this->url = $url;
        $this->coursename = $coursename;
    }
}
