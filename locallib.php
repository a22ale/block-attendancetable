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
    /** @var int all courses' total percentage  */
    public $totalpercentage = 0;

    /**
     * Returns the user's attendance average, rounded to the specified
     * decimal count (2 by default)
     * 
     * @param int $decimals How many decimals you want your average to have
     * @return float The user's attendance average
     */
    public function get_average($decimals = 2)
    {
        return round($this->totalpercentage / $this->totalsection, $decimals);
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
    /** @var int course's total percentage */
    public $totalpercentage = 0;
    /** @var float course's number of attendance modules */
    public $coursesections = 0;

    /**
     * Returns the course's attendance average, rounded to the specified
     * decimal count (2 by default)
     * 
     * @param int $decimals How many decimals you want your average to have
     * @return float The user's attendance average
     */
    public function get_average($decimals = 2)
    {
        return round($this->totalpercentage / $this->coursesections, $decimals);
    }
}

class user_session
{
}
