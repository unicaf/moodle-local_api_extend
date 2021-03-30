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
 * External API
 *
 * @package    local_api_extend
 * @copyright  2020 UNICAF LTD <info@unicaf.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . "/local/api_extend/lib.php");

/**
 * Class api_extend
 */
class api_extend extends external_api
{

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_assign_parameters()
    {
        return new external_function_parameters([
            'instanceid' => new external_value(PARAM_INT, 'The assignment id')
        ]);
    }

    /**
     * Get Single Assign
     *
     * @param $instanceid
     * @return array welcome message
     * @throws invalid_parameter_exception
     * @throws required_capability_exception|dml_exception
     * @throws moodle_exception
     */
    public static function get_assign($instanceid)
    {
        global $DB;

        //Parameter validation
        $params = self::validate_parameters(self::get_assign_parameters(), ['instanceid' => $instanceid]);

        $context = context_system::instance();
        require_capability('mod/assign:view', $context);

        $sql = "SELECT a.id, a.course, a.name, a.intro, a.grade, a.duedate, cm.visible, gi.grademax, gi.gradepass, gi.aggregationcoef AS weight
                  FROM {assign} a
            INNER JOIN {course_modules} cm ON cm.course = a.course AND cm.instance = a.id
            INNER JOIN {modules} m ON m.id = cm.module
            INNER JOIN {grade_items} gi ON gi.itemtype= :itemtype AND gi.itemmodule = m.name AND gi.iteminstance = a.id
                 WHERE a.id = :id AND m.name = :module";

        $record = $DB->get_record_sql($sql, ['itemtype' => 'mod', 'id' => $params['instanceid'], 'module' => 'assign'], MUST_EXIST);

        return (array)$record;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_assign_returns()
    {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'Assignment id'),
                'course' => new external_value(PARAM_INT, 'Course id'),
                'name' => new external_value(PARAM_TEXT, 'Assignment name'),
                'intro' => new external_value(PARAM_RAW, 'Intro Text'),
                'duedate' => new external_value(PARAM_INT, 'Due Date'),
                'grademax' => new external_value(PARAM_FLOAT, 'Max Grade'),
                'gradepass' => new external_value(PARAM_FLOAT, 'Passing Grade'),
                'weight' => new external_value(PARAM_FLOAT, 'Weight'),
                'visible' => new external_value(PARAM_INT, 'Status'),
            ]
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_quiz_parameters()
    {
        return new external_function_parameters([
            'instanceid' => new external_value(PARAM_INT, 'The quiz id')
        ]);
    }

    /**
     * Get Single Quiz
     *
     * @param $instanceid
     * @return array welcome message
     * @throws invalid_parameter_exception
     * @throws required_capability_exception|dml_exception
     * @throws moodle_exception
     */
    public static function get_quiz($instanceid)
    {
        global $DB;

        //Parameter validation
        $params = self::validate_parameters(self::get_quiz_parameters(), ['instanceid' => $instanceid]);

        $context = context_system::instance();
        require_capability('mod/quiz:view', $context);

        $sql = "SELECT q.id, q.course, q.name, q.intro, q.grade, cm.visible, gi.grademax, gi.gradepass, gi.aggregationcoef AS weight
                  FROM {quiz} q
            INNER JOIN {course_modules} cm ON cm.course = q.course AND cm.instance = q.id
            INNER JOIN {modules} m ON m.id = cm.module
            INNER JOIN {grade_items} gi ON gi.itemtype= :itemtype AND gi.itemmodule = m.name AND gi.iteminstance = q.id
                 WHERE q.id = :id AND m.name = :module";

        $record = $DB->get_record_sql($sql, ['itemtype' => 'mod', 'id' => $params['instanceid'], 'module' => 'quiz'], MUST_EXIST);

        return (array)$record;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_quiz_returns()
    {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'Quiz id'),
                'course' => new external_value(PARAM_INT, 'Course id'),
                'name' => new external_value(PARAM_TEXT, 'Quiz name'),
                'intro' => new external_value(PARAM_RAW, 'Intro Text'),
                'visible' => new external_value(PARAM_INT, 'Status'),
                'grademax' => new external_value(PARAM_FLOAT, 'Max Grade'),
                'gradepass' => new external_value(PARAM_FLOAT, 'Passing Grade'),
                'weight' => new external_value(PARAM_FLOAT, 'Weight'),
            ]
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_course_modules_parameters()
    {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'The course id')
        ]);
    }

    /**
     * Get Course Module
     *
     * @param $courseid
     * @return array
     * @throws invalid_parameter_exception
     * @throws required_capability_exception|dml_exception
     * @throws moodle_exception
     */
    public static function get_course_modules($courseid)
    {
        global $DB;

        //Parameter validation
        $params = self::validate_parameters(self::get_course_modules_parameters(), ['courseid' => $courseid]);

        $context = context_system::instance();
        require_capability('moodle/course:view', $context);

        $sql = "SELECT cm.instance,m.name as module_name
                  FROM {course_modules} cm
            INNER JOIN {modules} m ON m.id = cm.module
                 WHERE cm.course = :course";

        $records = $DB->get_records_sql($sql, ['course' => $params['courseid']]);

        if ($records === false) {
            throw new moodle_exception('coursemodulesnotfound', 'Course modules not found');
        }

        $modules = [];

        foreach ($records as $record) {
            if (!in_array($record->module_name, ['assign', 'quiz'])) {
                continue;
            }
            $info = get_course_mod_info($record->instance, $record->module_name);

            $modules[] = [
                'id' => $info->id,
                'idnumber' => $info->idnumber,
                'course' => $info->course,
                'name' => $info->name,
                'intro' => $info->intro,
                'duedate' => isset($info->duedate) ? $info->duedate : false,
                'grade' => $info->grade,
                'visible' => $info->visibles,
                'module_type' => $record->module_name,
                'grademax' => $info->grademax,
                'gradepass' => $info->gradepass,
                'weight' => $info->weight,
            ];

        }

        return $modules;

    }

    /**
     * Returns description of method result value
     *
     * @return external_multiple_structure
     */
    public static function get_course_modules_returns()
    {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'id' => new external_value(PARAM_INT, 'Instance id'),
                    'idnumber' => new external_value(PARAM_TEXT, 'Instance id number'),
                    'course' => new external_value(PARAM_INT, 'Course id'),
                    'name' => new external_value(PARAM_TEXT, 'Assignment name'),
                    'intro' => new external_value(PARAM_RAW, 'Intro Text'),
                    'duedate' => new external_value(PARAM_RAW, 'Due Date'),
                    'grade' => new external_value(PARAM_FLOAT, 'Grade'),
                    'visible' => new external_value(PARAM_INT, 'Status'),
                    'module_type' => new external_value(PARAM_TEXT, 'The Module Type'),
                    'grademax' => new external_value(PARAM_FLOAT, 'Max Grade'),
                    'gradepass' => new external_value(PARAM_FLOAT, 'Passing Grade'),
                    'weight' => new external_value(PARAM_FLOAT, 'Weight'),
                ]
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_assignment_files_parameters()
    {
        return new external_function_parameters([
            'assignmentid' => new external_value(PARAM_INT, 'The assignment id'),
            'userid' => new external_value(PARAM_INT, 'The user id')
        ]);
    }

    /**
     * Get Assignment files
     *
     * @param $assignmentid
     * @param $userid
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     */
    public static function get_assignment_files($assignmentid, $userid)
    {
        global $CFG;

        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        //Parameter validation
        $params = self::validate_parameters(
            self::get_assignment_files_parameters(),
            ['assignmentid' => $assignmentid, 'userid' => $userid]
        );


        $context = context_system::instance();
        require_capability('mod/assign:view', $context);

        $course_module = get_coursemodule_from_instance('assign', $params['assignmentid']);
        $context_module = context_module::instance($course_module->id);

        $assign = new assign($context_module, $course_module, null);
        $user_submission = $assign->get_user_submission($params['userid'], false);

        $file_urls = [];

        if ($user_submission) {
            $fs = get_file_storage();
            $files = $fs->get_area_files(
                $context_module->id,
                'assignsubmission_file',
                ASSIGNSUBMISSION_FILE_FILEAREA,
                $user_submission->id
            );


            foreach ($files as $file) {
                if ($file->get_filename() == '.') {
                    continue;
                }

                $url = moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename()
                );
                $file_urls[] = [
                    'id' => $file->get_id(),
                    'filename' => $file->get_filename(),
                    'fileurl' => $url->out()
                ];
            }

        }

        return [
            'submission_date' => $user_submission->timemodified,
            'files' => $file_urls,
        ];

    }

    /**
     * Returns description of method result value
     *
     * @return external_function_parameters
     */
    public static function get_assignment_files_returns()
    {
        return new external_function_parameters([
            'submission_date' => new external_value(PARAM_INT, 'The Submission Date'),
            'files' => new external_multiple_structure(
                new external_single_structure(
                    [
                        'id' => new external_value(PARAM_INT, 'File id'),
                        'filename' => new external_value(PARAM_TEXT, 'Filename'),
                        'fileurl' => new external_value(PARAM_TEXT, 'File Url'),
                    ]
                )
            ),
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_grade_parameters()
    {
        return new external_function_parameters([
            'gradeitemid' => new external_value(PARAM_INT, 'The grade item id'),
            'userid' => new external_value(PARAM_INT, 'The user id'),
        ]);
    }

    /**
     * Get Assignment/Quiz Grade
     *
     * @param $gradeitemid
     * @param $userid
     * @param $module
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     */
    public static function get_grade($gradeitemid, $userid)
    {
        global $DB;

        //Parameter validation
        $params = self::validate_parameters(
            self::get_grade_parameters(),
            ['gradeitemid' => $gradeitemid, 'userid' => $userid]
        );

        $context = context_system::instance();

        $capability = 'mod/assign:view';
        require_capability($capability, $context);

        $sql = "SELECT gg.id, gi.courseid, gg.finalgrade, gi.iteminstance, gi.itemmodule
                  FROM {grade_items} gi
            INNER JOIN {grade_grades} gg ON gg.itemid = gi.id
                 WHERE gi.id = :id AND gg.userid = :userid";

        $record = $DB->get_record_sql($sql, ['id' => $params['gradeitemid'], 'userid' => $params['userid']], MUST_EXIST);

        return [
            'id' => $record->id,
            'grade' => $record->finalgrade,
            'courseid' => $record->courseid,
            'iteminstance' => $record->iteminstance,
            'itemmodule' => $record->itemmodule,
        ];
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function get_grade_returns()
    {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'The Id of the grade'),
                'grade' => new external_value(PARAM_TEXT, 'The Grade'),
                'courseid' => new external_value(PARAM_INT, 'The Course Id'),
                'iteminstance' => new external_value(PARAM_INT, 'The Item Instance Id'),
                'itemmodule' => new external_value(PARAM_TEXT, 'The Item Module'),
            ]
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function update_course_module_parameters()
    {
        return new external_function_parameters([
            'instanceid' => new external_value(PARAM_INT, 'The assignment id'),
            'idnumber' => new external_value(PARAM_TEXT, 'The id number'),
            'module' => new external_value(PARAM_TEXT, 'The module name'),
        ]);
    }

    /**
     * Update Course Module
     *
     * @param $instanceid
     * @param $idnumber
     * @param $module
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     */
    public static function update_course_module($instanceid, $idnumber, $module)
    {
        global $DB;

        //Parameter validation
        $params = self::validate_parameters(
            self::update_course_module_parameters(),
            [
                'instanceid' => $instanceid,
                'idnumber' => $idnumber,
                'module' => $module
            ]
        );

        $context = context_system::instance();
        require_capability('moodle/course:update', $context);

        $table = $module;

        $sql = "SELECT cm.id
                  FROM {" . $table . "} a
            INNER JOIN {course_modules} cm ON cm.course = a.course AND cm.instance = a.id
            INNER JOIN {modules} m ON m.id = cm.module
                 WHERE a.id = :id AND m.name = :module";

        $record = $DB->get_record_sql($sql, ['id' => $params['instanceid'], 'module' => $module], MUST_EXIST);

        // Get all the columns
        $rec = new stdclass();
        $rec->id = $record->id;
        $rec->idnumber = $idnumber;

        $DB->update_record('course_modules', $rec);

        return (array)$record;

    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function update_course_module_returns()
    {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'The course module id'),
            ]
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_roleid_by_shortname_parameters()
    {
        return new external_function_parameters(
            ['shortname' => new external_value(PARAM_TEXT, 'role shortname')]
        );
    }

    /**
     * Returns welcome message
     * @param string $shortname
     * @return array welcome message
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     */
    public static function get_roleid_by_shortname($shortname = 'teacher')
    {
        global $DB;

        //Parameter validation
        $params = self::validate_parameters(self::get_roleid_by_shortname_parameters(), ['shortname' => $shortname]);

        $context = context_system::instance();
        require_capability('moodle/role:manage', $context);

        $role = $DB->get_record('role', ['shortname' => $params['shortname']]);
        if ($role === false) {
            throw new moodle_exception('notexist', 'Invalid shortname');
        }

        return ['id' => $role->id, 'shortname' => $role->shortname];
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_roleid_by_shortname_returns()
    {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_TEXT, 'role id'),
                'shortname' => new external_value(PARAM_TEXT, 'short name'),
            ]
        );
    }

    /**
     * @return external_function_parameters
     */
    public static function get_feedback_parameters()
    {
        return new external_function_parameters([
            'instanceid' => new external_value(PARAM_INT, 'The instance id'),
        ]);
    }

    /**
     * @param $instanceid
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     */
    public static function get_feedback($instanceid)
    {
        global $DB;

        //Parameter validation
        $params = self::validate_parameters(
            self::get_feedback_parameters(),
            ['instanceid' => $instanceid]
        );

        $context = context_system::instance();

        $capability = 'mod/assign:view';
        require_capability($capability, $context);

        $record = $DB->get_record('grade_grades', ['id' => $params['instanceid']], 'feedback');

        return (array)$record;

    }

    /**
     * @return external_single_structure
     */
    public static function get_feedback_returns()
    {
        return new external_single_structure(
            [
                'feedback' => new external_value(PARAM_RAW, 'Feedback'),
            ]
        );
    }

}
