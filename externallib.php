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
    public static function get_override_parameters(): external_function_parameters
    {
        return new external_function_parameters([
            'module_type' => new external_value(PARAM_TEXT, 'The module type'),
            'instanceid' => new external_value(PARAM_INT, 'The instance id'),
            'userorgroup' => new external_value(PARAM_TEXT, 'User or group'),
            'id' => new external_value(PARAM_INT, 'User or group id')
        ]);
    }

    /**
     * Get Single Assign
     *
     * @param $module_type
     * @param $instanceid
     * @param $userorgroup
     * @param $id
     * @return array welcome message
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     */
    public static function get_override($module_type, $instanceid, $userorgroup, $id): array
    {
        global $DB;

        //Parameter validation
        $params = self::validate_parameters(self::get_override_parameters(), [
            'module_type' => $module_type,
            'instanceid' => $instanceid,
            'userorgroup' => $userorgroup,
            'id' => $id
        ]);
        $parameters = [
            'assign' => [
                'columns' => 'a.id, a.duedate, a.cutoffdate',
                'column' => 'a.' . $params['module_type'] . 'id',
            ],
            'quiz' => [
                'columns' => 'a.id, NULL as duedate, a.timeclose as cutoffdate',
                'column' => 'a.' . $params['module_type']
            ]
        ];

        $values_userorgroup = [
            'user' => 'a.userid',
            'group'=> 'a.groupid'
        ];

        $context = context_system::instance();
        require_capability('mod/assign:view', $context);
        $table = $params['module_type'] . '_overrides';

        if(!isset($values_userorgroup[$params['userorgroup']])) {
            throw new invalid_parameter_exception('Invalid userorgroup given');
        }

        $sql = "SELECT " . $parameters[$params['module_type']]['columns'] . "
                  FROM {" . $table . "} a
                 WHERE " . $parameters[$params['module_type']]['column'] . " = :assignid AND {$values_userorgroup[$params['userorgroup']]} = :id";

        $record = $DB->get_record_sql($sql, [
            'assignid' => $params['instanceid'],
            'id' => $params['id']
        ], MUST_EXIST);

        return (array)$record;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_override_returns()
    {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'Assignment id'),
                'duedate' => new external_value(PARAM_INT, 'Due Date', VALUE_DEFAULT, null, NULL_ALLOWED),
                'cutoffdate' => new external_value(PARAM_INT, 'Cut off Date', VALUE_DEFAULT, null, NULL_ALLOWED)
            ]
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_assign_parameters(): external_function_parameters
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
    public static function get_assign($instanceid): array
    {
        global $DB;

        //Parameter validation
        $params = self::validate_parameters(self::get_assign_parameters(), ['instanceid' => $instanceid]);

        $context = context_system::instance();
        require_capability('mod/assign:view', $context);

        $sql = "SELECT a.id, cm.idnumber, a.course, a.name, a.intro, a.grade, a.duedate, cm.visible, gi.grademax, gi.gradepass, gi.aggregationcoef AS weight,
                    a.cutoffdate
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
                'idnumber' => new external_value(PARAM_TEXT, 'ID Number'),
                'course' => new external_value(PARAM_INT, 'Course id'),
                'name' => new external_value(PARAM_TEXT, 'Assignment name'),
                'intro' => new external_value(PARAM_RAW, 'Intro Text'),
                'duedate' => new external_value(PARAM_INT, 'Due Date'),
                'cutoffdate' => new external_value(PARAM_INT, 'Cut off Date'),
                'grade' => new external_value(PARAM_FLOAT, 'Grade'),
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
    public static function get_h5pactivity_parameters(): external_function_parameters
    {
        return new external_function_parameters([
            'instanceid' => new external_value(PARAM_INT, 'The h5p id')
        ]);
    }

     /**
     * Get Single H5PActivity
     *
     * @param $instanceid
     * @return array welcome message
     * @throws invalid_parameter_exception
     * @throws required_capability_exception|dml_exception
     * @throws moodle_exception
     */
    public static function get_h5pactivity($instanceid)
    {
        global $DB;

        //Parameter validation
        $params = self::validate_parameters(self::get_h5pactivity_parameters(), ['instanceid' => $instanceid]);

        $context = context_system::instance();
        require_capability('mod/h5pactivity:view', $context);

        $sql = "SELECT h5p.id, cm.idnumber, h5p.course, h5p.name, h5p.intro, h5p.grade, cm.visible, gi.grademax, gi.gradepass, 
       gi.aggregationcoef AS weight, NULL as duedate
                  FROM {h5pactivity} h5p
            INNER JOIN {course_modules} cm ON cm.course = h5p.course AND cm.instance = h5p.id
            INNER JOIN {modules} m ON m.id = cm.module
            INNER JOIN {grade_items} gi ON gi.itemtype= :itemtype AND gi.itemmodule = m.name AND gi.iteminstance = h5p.id
                 WHERE h5p.id = :id AND m.name = :module";

        $record = $DB->get_record_sql($sql, ['itemtype' => 'mod', 'id' => $params['instanceid'], 'module' => 'h5pactivity'], MUST_EXIST);

        return (array)$record;
    }    


    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_h5pactivity_returns()
    {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'h5p id'),
                'idnumber' => new external_value(PARAM_TEXT, 'ID Number'),
                'course' => new external_value(PARAM_INT, 'Course id'),
                'name' => new external_value(PARAM_TEXT, 'H5PActivity name'),
                'intro' => new external_value(PARAM_RAW, 'Intro Text'),
                'visible' => new external_value(PARAM_INT, 'Status'),
                'grade' => new external_value(PARAM_FLOAT, 'Grade'),
                'grademax' => new external_value(PARAM_FLOAT, 'Max Grade'),
                'gradepass' => new external_value(PARAM_FLOAT, 'Passing Grade'),
                'weight' => new external_value(PARAM_FLOAT, 'Weight'),
                'duedate' => new external_value(PARAM_INT, 'Due Date')
            ]
        );
    }
    

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_quiz_parameters(): external_function_parameters
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

        $sql = "SELECT q.id, cm.idnumber, q.course, q.name, q.intro, q.grade, cm.visible, gi.grademax, gi.gradepass, gi.aggregationcoef AS weight, NULL as duedate, `timeclose` as cutoffdate
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
                'idnumber' => new external_value(PARAM_TEXT, 'ID Number'),
                'course' => new external_value(PARAM_INT, 'Course id'),
                'name' => new external_value(PARAM_TEXT, 'Quiz name'),
                'intro' => new external_value(PARAM_RAW, 'Intro Text'),
                'visible' => new external_value(PARAM_INT, 'Status'),
                'grade' => new external_value(PARAM_FLOAT, 'Grade'),
                'grademax' => new external_value(PARAM_FLOAT, 'Max Grade'),
                'gradepass' => new external_value(PARAM_FLOAT, 'Passing Grade'),
                'weight' => new external_value(PARAM_FLOAT, 'Weight'),
                'duedate' => new external_value(PARAM_INT, 'Due Date'),
                'cutoffdate' => new external_value(PARAM_INT, 'Cut off Date'),
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
            if (!in_array($record->module_name, ['assign', 'quiz','h5pactivity', 'reengagement'])) {
                continue;
            }

            // for reengagement we need only minimal result for later actions
            if($record->module_name == 'reengagement'){
                $modules[] = [
                    'id' => $record->instance,
                    'idnumber' => '0',
                    'course' => $params['courseid'],
                    'name' => 'reengagement',
                    'intro' => 'reengagement',
                    'duedate' => false,
                    'grade' => 0.00,
                    'visible' => 0,
                    'module_type' => $record->module_name,
                    'grademax' => 0.00,
                    'gradepass' => 0.00,
                    'weight' => 0.00,
                    'deletioninprogress' => 0,
                    'category' => 'reengagement'
                ];

            }
            else{
                $info = get_course_mod_info($record->instance, $record->module_name);

                $category = null;
                if(!empty($info->categoryid)) {
                    $category_item = $DB->get_record('grade_items',
                        [
                            'itemtype' => 'category',
                            'iteminstance' => $info->categoryid,
                            'courseid' => $info->course,
                        ], '*');
                    if($category_item) {
                        $category = $category_item->iteminfo;
                    }
                }

                $modules[] = [
                    'id' => $info->id,
                    'idnumber' => $info->idnumber,
                    'course' => $info->course,
                    'name' => $info->name,
                    'intro' => $info->intro,
                    'duedate' => isset($info->duedate) ? $info->duedate : false,
                    'grade' => $info->grade,
                    'visible' => $info->visible,
                    'module_type' => $record->module_name,
                    'grademax' => $info->grademax,
                    'gradepass' => $info->gradepass,
                    'weight' => $info->weight,
                    'deletioninprogress' => $info->deletioninprogress,
                    'category' => $category
                ];
            }

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
                    'deletioninprogress' => new external_value(PARAM_INT, 'Deletion In Progress'),
                    'category' => new external_value(PARAM_TEXT, 'Category value issue'),
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
            'userid' => new external_value(PARAM_INT, 'The user id'),
            'submissionid' => new external_value(PARAM_INT, 'The submission id', VALUE_DEFAULT, null, NULL_ALLOWED)
        ]);
    }

    /**
     * Get Assignment files
     *
     * @param $assignmentid
     * @param $userid
     * @param null $submissionid
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     */
    public static function get_assignment_files($assignmentid, $userid, $submissionid = null)
    {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        //Parameter validation
        $params = self::validate_parameters(
            self::get_assignment_files_parameters(),
            ['assignmentid' => $assignmentid, 'userid' => $userid, 'submissionid' => $submissionid]
        );

        $context = context_system::instance();
        require_capability('mod/assign:view', $context);

        $course_module = get_coursemodule_from_instance('assign', $params['assignmentid']);
        $context_module = context_module::instance($course_module->id);

        $assign = new assign($context_module, $course_module, null);
        if ($submissionid) {
            $query_params = ['assignment' => $assign->get_instance()->id, 'id' => $submissionid];
            $user_submission = $DB->get_record('assign_submission', $query_params, '*', MUST_EXIST);
        } else {
            $user_submission = $assign->get_user_submission($params['userid'], false);
        }

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

        $sql = "SELECT gg.id, gi.courseid, gg.finalgrade, gi.iteminstance, gi.itemmodule, a.markingworkflow, asf.workflowstate
                  FROM {grade_items} gi
            INNER JOIN {grade_grades} gg ON gg.itemid = gi.id
            LEFT JOIN {assign} a ON a.course = gi.courseid AND a.id = gi.iteminstance AND gi.itemmodule = :itemmodule
            LEFT JOIN {assign_user_flags}  asf ON asf.assignment = gi.iteminstance AND asf.userid= gg.userid
            WHERE gi.id = :id AND gg.userid = :userid";

        $record = $DB->get_record_sql($sql, ['id' => $params['gradeitemid'], 'userid' => $params['userid'],
            'itemmodule' => 'assign'], MUST_EXIST);


        $sql_grade = "SELECT grade FROM {assign_grades} WHERE assignment= :assignment AND userid= :userid ORDER BY id DESC LIMIT 1";
        $record_grade = $DB->get_record_sql($sql_grade, ['assignment' => $record->iteminstance, 'userid' => $params['userid']]);


        return [
            'id' => $record->id,
            'grade' => $record->finalgrade,
            'courseid' => $record->courseid,
            'iteminstance' => $record->iteminstance,
            'itemmodule' => $record->itemmodule,
            'markingworkflow' => $record->markingworkflow,
            'workflowstate' => $record->workflowstate,
            'assign_grade' => $record_grade->grade,
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
                'markingworkflow' => new external_value(PARAM_INT, 'The Status of Marking Workflow'),
                'workflowstate' => new external_value(PARAM_TEXT, 'The State of Marking Workflow'),
                'assign_grade' => new external_value(PARAM_TEXT, 'The Grade even when it\'s not released'),
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
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function update_course_reengagement_parameters()
    {
        return new external_function_parameters([
            'instanceid' => new external_value(PARAM_INT, 'The assignment id'),
            'availability' => new external_value(PARAM_INT, 'Availability (until, timestamp)'),
        ]);
    }

    /**
     * Update Course Module (Reengagement)
     *
     * @param int $instanceid
     * @param int $availability
     * @return array | false
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     */
    public static function update_course_reengagement(int $instanceid, int $availability)
    {
        global $DB;

        //Parameter validation
        $params = self::validate_parameters(
            self::update_course_reengagement_parameters(),
            [
                'instanceid' => $instanceid,
                'availability' => $availability
            ]
        );

        $context = context_system::instance();
        require_capability('moodle/course:update', $context);

        $table = 'reengagement';

        $sql = "SELECT cm.id
                  FROM {" . $table . "} a
            INNER JOIN {course_modules} cm ON cm.course = a.course AND cm.instance = a.id
            INNER JOIN {modules} m ON m.id = cm.module
                 WHERE a.id = :id";

        $record = $DB->get_record_sql($sql, ['id' => $params['instanceid']], MUST_EXIST);

        if(empty($record)) {
            return false;
        }

        // Get all the columns
        $rec = new stdclass();
        $rec->id = $record->id;


        $availability_data = [
            "op" => "&",
            "c" => [
                [
                    "type" => "date",
                    "d" => "<",
                    "t" => $availability
                ]
            ],
            "showc" => [
                false
            ]
        ];

        $rec->availability = json_encode($availability_data);

        $DB->update_record('course_modules', $rec);

        return (array)$record;

    }


    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function update_course_reengagement_returns()
    {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'The course module id'),
            ]
        );
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


    /**
     * *
     * @param $assignment
     * @param $userid
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     */
    public static function get_markers_feedback($assignment, $userid) {
        global $DB;

        //Parameter validation
        $params = self::validate_parameters(
            self::get_markers_feedback_parameters(),
            ['assignment' => $assignment,
                'userid' => $userid]
        );

        $context = context_system::instance();

        $capability = 'mod/assign:view';
        require_capability($capability, $context);

        $itemid = $DB->get_record('assign_grades', ['assignment' => $params['assignment'], 'userid' => $params['userid']], 'id');

        $instanceid = $DB->get_record_sql('SELECT id FROM {grading_instances} WHERE itemid = :itemid ORDER BY id DESC LIMIT 1', ['itemid' => $itemid->id]);

        if(empty($instanceid)) return [];

        $filings = $DB->get_records('gradingform_guide_fillings', ['instanceid' => $instanceid->id], null, '*');

        if(empty($filings)) return [];
        $data = [];
        foreach($filings as $marker) {
            $data[$marker->criterionid]['feedback'] = $marker->remark;
            $criterion = $DB->get_record('gradingform_guide_criteria', ['id' => $marker->criterionid], 'shortname');
            $data[$marker->criterionid]['criterion'] = $criterion->shortname;
        }
        return $data;
    }

    /**
     * @return external_function_parameters
     */
    public static function get_markers_feedback_parameters()
    {
        return new external_function_parameters([
            'assignment' => new external_value(PARAM_INT, 'The assignment id'),
            'userid' => new external_value(PARAM_INT, 'The user id'),
        ]);
    }

    /**
     * @return external_multiple_structure
     */
    public static function get_markers_feedback_returns(): external_multiple_structure
    {
        return new external_multiple_structure(
            new external_single_structure([
                'criterion' => new external_value(PARAM_RAW, 'criterion remark'),
                'feedback' => new external_value(PARAM_RAW, 'Feedback'),
            ])
        );
    }


    /**
     * Update Assign Activity
     *
     * @param int $instance_id
     * @param int|null $starting_date
     * @param int|null $deadline
     * @param int|null $cut_off
     * @return array | false
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     */
    public static function update_assign_activity(int $instance_id, ?int $starting_date = null, ?int $deadline = null, ?int $cut_off = null)
    {
        global $DB;

        //Parameter validation
        $params = self::validate_parameters(
            self::update_assign_activity_parameters(),
            [
                'instanceid' => $instance_id,
                'starting_date' => $starting_date,
                'deadline' => $deadline,
                'cut_off' => $cut_off
            ]
        );

        $context = context_system::instance();

        require_capability('moodle/course:update', $context);
        $table = 'assign';
        $sql = "SELECT a.id
                FROM {" . $table . "} a
                WHERE a.id = :id";

        $record = $DB->get_record_sql($sql, ['id' => $instance_id, 'module' => $table], MUST_EXIST);

        if(empty($record)) {
            return false;
        }

        $has_something_to_update = false;
        $rec = new stdclass();
        $rec->id = $record->id;
        if($starting_date !== null) {
            $has_something_to_update = true;
            $rec->allowsubmissionsfromdate = $starting_date;
        }
        if($deadline !== null) {
            $has_something_to_update = true;
            $rec->duedate = $deadline;
        }
        if($cut_off !== null) {
            $has_something_to_update = true;
            $rec->cutoffdate = $cut_off;
        }

        if($has_something_to_update) {
            $DB->update_record($table, $rec);
            return (array)$record;
        }

        // nothing to update
        return false;
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function update_assign_activity_returns(): external_single_structure
    {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'The course assessment id'),
            ]
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function update_assign_activity_parameters(): external_function_parameters
    {
        return new external_function_parameters([
            'instanceid' => new external_value(PARAM_INT, 'The activity id'),
            'starting_date' => new external_value(PARAM_INT, 'The starting date of activity', VALUE_DEFAULT),
            'deadline' => new external_value(PARAM_INT, 'The soft deadline of activity', VALUE_DEFAULT),
            'cut_off' => new external_value(PARAM_INT, 'The hard deadline of activity', VALUE_DEFAULT)
        ]);
    }

    /**
     * Update Assign Activity
     *
     * @param int $instance_id
     * @param int|null $starting_date
     * @param int|null $cut_off
     * @return array | false
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     */
    public static function update_quiz_activity(int $instance_id, ?int $starting_date = null, ?int $cut_off = null)
    {
        global $DB;

        //Parameter validation
        $params = self::validate_parameters(
            self::update_quiz_activity_parameters(),
            [
                'instanceid' => $instance_id,
                'starting_date' => $starting_date,
                'cut_off' => $cut_off
            ]
        );

        $context = context_system::instance();

        require_capability('moodle/course:update', $context);
        $table = 'quiz';
        $sql = "SELECT a.id
                FROM {" . $table . "} a
                WHERE a.id = :id";

        $record = $DB->get_record_sql($sql, ['id' => $instance_id, 'module' => $table], MUST_EXIST);

        if(empty($record)) {
            return false;
        }

        $has_something_to_update = false;
        $rec = new stdclass();
        $rec->id = $record->id;
        if($starting_date !== null) {
            $has_something_to_update = true;
            $rec->timeopen = $starting_date;
        }

        if($cut_off !== null) {
            $has_something_to_update = true;
            $rec->timeclose = $cut_off;
        }

        if($has_something_to_update) {
            $DB->update_record($table, $rec);
            return (array)$record;
        }

        // nothing to update
        return false;
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function update_quiz_activity_returns(): external_single_structure
    {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'The course assessment id'),
            ]
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function update_quiz_activity_parameters(): external_function_parameters
    {
        return new external_function_parameters([
            'instanceid' => new external_value(PARAM_INT, 'The activity id'),
            'starting_date' => new external_value(PARAM_INT, 'The starting date of activity', VALUE_DEFAULT),
            'cut_off' => new external_value(PARAM_INT, 'The hard deadline of activity', VALUE_DEFAULT)
        ]);
    }

    public static function download_file_parameters() {
        return new external_function_parameters([
            'fileid' => new external_value(PARAM_INT, 'The file id')
        ]);
    }

    public static function download_file($fileid) {
        $params = self::validate_parameters(self::download_file_parameters(), ['fileid' => $fileid]);

        $context = context_system::instance();
        self::validate_context($context);

        $fs = get_file_storage();
        $file = $fs->get_file_by_id($params['fileid']);
        if (!$file) {
            throw new moodle_exception('filenotfound', 'error');
        }

        // Output file headers
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $file->get_mimetype());
        header('Content-Disposition: attachment; filename="' . $file->get_filename() . '"');
        header('Content-Length: ' . $file->get_filesize());

        // Send content and exit
        echo $file->get_content();
        die();
    }

    public static function download_file_returns() {
        return null;
    }

}
