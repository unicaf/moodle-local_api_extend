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
 * Web service local plugin to retrieve role id by shortname.
 *
 * @package    local_api_extend
 * @copyright  2020 UNICAF LTD <info@unicaf.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// We defined the web service functions to install.
$functions = [
    'api_ext_get_assign' => [
        'classname' => 'api_extend',
        'methodname' => 'get_assign',
        'classpath' => 'local/api_extend/externallib.php',
        'description' => 'Get Single Assignment',
        'capabilities'  => 'mod/assign:view',
        'type' => 'read',
    ],
    'api_ext_get_quiz' => [
        'classname' => 'api_extend',
        'methodname' => 'get_quiz',
        'classpath' => 'local/api_extend/externallib.php',
        'description' => 'Get Single Quiz',
        'capabilities'  => 'mod/quiz:view',
        'type' => 'read',
    ],
    'api_ext_get_feedback' => [
        'classname' => 'api_extend',
        'methodname' => 'get_feedback',
        'classpath' => 'local/api_extend/externallib.php',
        'description' => 'Get Assignment/Quiz Feedback',
        'capabilities'  => 'mod/assign:view',
        'type' => 'read',
    ],
    'api_ext_get_course_modules' => [
        'classname' => 'api_extend',
        'methodname' => 'get_course_modules',
        'classpath' => 'local/api_extend/externallib.php',
        'description' => 'Get Course Modules',
        'capabilities'  => 'moodle/course:view',
        'type' => 'read',
    ],
    'api_ext_get_assignment_files' => [
        'classname' => 'api_extend',
        'methodname' => 'get_assignment_files',
        'classpath' => 'local/api_extend/externallib.php',
        'description' => 'Get Assignment Files',
        'capabilities'  => 'mod/assign:view',
        'type' => 'read',
    ],
    'api_ext_get_grade' => [
        'classname' => 'api_extend',
        'methodname' => 'get_grade',
        'classpath' => 'local/api_extend/externallib.php',
        'description' => 'Get Grade Item',
        'capabilities'  => 'mod/assign:view, mod/quiz:view',
        'type' => 'read',
    ],
    'api_ext_get_roleid_by_shortname' => [
        'classname' => 'api_extend',
        'methodname' => 'get_roleid_by_shortname',
        'classpath' => 'local/api_extend/externallib.php',
        'description' => 'Get Role Id by shortname',
        'capabilities'  => 'moodle/role:manage',
        'type' => 'read',
    ],
    'api_ext_update_course_module' => [
        'classname' => 'api_extend',
        'methodname' => 'update_course_module',
        'classpath' => 'local/api_extend/externallib.php',
        'description' => 'Update Course Module',
        'capabilities'  => 'moodle/course:update',
        'type' => 'write',
    ],
    'api_ext_get_markers_feedback' => [
        'classname' => 'api_extend',
        'methodname' => 'get_markers_feedback',
        'classpath' => 'local/api_extend/externallib.php',
        'description' => 'Get Markers feedbacks',
        'capabilities'  => 'mod/assign:view',
        'type' => 'read',
    ],
    'api_ext_update_assign_activity' => [
        'classname' => 'api_extend',
        'methodname' => 'update_assign_activity',
        'classpath' => 'local/api_extend/externallib.php',
        'description' => 'Update Activities Dates',
        'capabilities'  => 'moodle/course:update',
        'type' => 'write',
    ],
    'api_ext_update_quiz_activity' => [
        'classname' => 'api_extend',
        'methodname' => 'update_quiz_activity',
        'classpath' => 'local/api_extend/externallib.php',
        'description' => 'Update Quiz Dates',
        'capabilities'  => 'moodle/course:update',
        'type' => 'write',
    ],
    'api_ext_get_override' => [
        'classname' => 'api_extend',
        'methodname' => 'get_override',
        'classpath' => 'local/api_extend/externallib.php',
        'description' => 'Get assign and quiz overrides',
        'capabilities'  => 'mod/assign:view',
        'type' => 'read',
    ],
    'unicaf_get_roles_by_shortname' => [
        'classname'   => 'unicaf_roles_by_shortname',
        'methodname'  => 'unicaf_get_roleid',
        'classpath'   => 'local/unicafws/externallib.php',
        'description' => 'Return ROLE id , search by shortname',
        'type'        => 'read',
    ]
];

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = [
    'API Extend Calls' => [
        'functions' => [
            'api_ext_get_assign',
            'api_ext_get_quiz',
            'api_ext_get_feedback',
            'api_ext_get_course_modules',
            'api_ext_get_assignment_files',
            'api_ext_get_grade',
            'api_ext_get_roleid_by_shortname',
            'api_ext_update_course_module',
            'api_ext_get_markers_feedback',
            'api_ext_update_assign_activity',
            'api_ext_update_quiz_activity',
            'api_ext_get_override'
        ],
        'restrictedusers' => 1,
        'enabled' => 1,
        'shortname' => 'apiextendcalls'
    ],
    'UNICAF Webservice' => [
        'functions' => [
            'unicaf_get_roles_by_shortname'
        ],
        'restrictedusers' => 1,
        'enabled'=>1,
        'shortname'=>'getroleid'
    ]
];
