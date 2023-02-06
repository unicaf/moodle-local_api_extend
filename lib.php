<?php
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
 * Library of useful functions
 *
 * @package    local_api_extend
 * @copyright  2020 UNICAF LTD <info@unicaf.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


/**
 * Get Course Module Info
 *
 * @param $instanceId
 * @param $module
 * @return bool|mixed
 * @throws dml_exception
 * @throws moodle_exception
 */
function get_course_mod_info($instanceId, $module)
{
    global $DB;

    $sql = "SELECT m.*, gi.grademax, gi.gradepass, cm.idnumber, gi.aggregationcoef AS weight, cm.deletioninprogress, cm.visible
                  FROM {" . $module . "} m
                  INNER JOIN {course_modules} cm ON cm.course = m.course AND cm.instance = m.id
                  INNER JOIN {grade_items} gi ON gi.itemtype= :itemtype AND gi.itemmodule = :module AND gi.iteminstance = m.id
                 WHERE m.id = :id";

    return $DB->get_record_sql($sql, ['itemtype' => 'mod', 'module' => $module, 'id' => $instanceId], MUST_EXIST);

}