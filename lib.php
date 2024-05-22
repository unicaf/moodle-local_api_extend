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

/**
 * Create Role with Permissions v2
 *
 * To avoid troubles, the function first
 * will check if the role with the same shortname already exist if not it will
 * proceed otherwise it will return the existing role id.
 *
 * @param array $role
 * @param int $fromroleid
 * @return int $roleid
 */
function createRoleWithPermissions($role, $fromroleid = null)
{
    global $DB, $CFG;
    require_once($CFG->libdir . "/accesslib.php");

    // check if role exist
    $role_exist = $DB->get_record('role', array('shortname' => $role['shortname']), '*');
    if ($role_exist !== false) {
        return $role_exist->id;
    }

    // create role
    $roleid = create_role($role['name'], $role['shortname'], $role['description'], $role['archetype']);

    // assign roles that will be visible to the new role created above.
    if (!empty($role['allowview']['shortname'])){
        foreach ($role['allowview']['shortname'] as $key => $shortname) {
            $role_exist = $DB->get_record('role', array('shortname' => $shortname), '*');
            if ($role_exist) {
                core_role_set_view_allowed($roleid, $role_exist->id);
            }

        }
    } elseif (!empty($role['archetype'])) {
        $allowed_view = get_default_role_archetype_allows('view', $role['archetype']);
        foreach ($allowed_view as $id) {
            core_role_set_view_allowed($roleid, $id);
        }
    }

    // make the role to be assignable by the webservice role.
    if (!empty($fromroleid)) {
        core_role_set_assign_allowed($fromroleid, $targetroleid = $roleid);
    }

    if (!empty($role['allowassign']['shortname'])){

        if (is_array($role['allowassign']['shortname'])) {
            foreach ($role['allowassign']['shortname'] as $key => $shortname) {

                $role_exist = $DB->get_record('role', array('shortname' => $shortname), '*');

                if ($role_exist) {
                    core_role_set_assign_allowed($roleid, $role_exist->id);

                }
            }
        } else {
            $role_exist = $DB->get_record('role', array('shortname' => $role['allowassign']['shortname']), '*');
            if ($role_exist) {
                core_role_set_assign_allowed($roleid, $role_exist->id);

            }
        }


    }

    // set context where the role can be used.
    if (!empty($role['contextlevels'])) {
        set_role_contextlevels($roleid, $role['contextlevels']);
    }

    // assign permissions
    if (!empty($role['permissions'])) {

        if (!empty($role['permissions']['allow'])) {

            foreach ($role['permissions']['allow'] as $cap) {

                // Proceed if capability is found, otherwise the whole process will resault in an fatal error.
                if ($capinfo = get_capability_info($cap)) {
                    $reponse = assign_capability($cap, 1, $roleid, 1);
                }
            }
        }
    }
    update_capabilities();

    return $roleid;
}

function getcontextid($cxt){
    /*
    define('CONTEXT_SYSTEM', 10);
    define('CONTEXT_USER', 30);
    define('CONTEXT_COURSECAT', 40);
    define('CONTEXT_COURSE', 50);
    define('CONTEXT_MODULE', 70);
    define('CONTEXT_BLOCK', 80);
    */
    $defined = [
        'system' => 10,
        'user' => 30,
        'coursecat' => 40,
        'course' => 50,
        'module' => 70,
        'block' => 80,
    ];

    return !(empty($defined[$cxt])) ? $defined[$cxt] : null;
}

function xml_decode($filepath){
    $xmlstring = file_get_contents($filepath);
    $xml = simplexml_load_string($xmlstring, "SimpleXMLElement", LIBXML_NOCDATA);
    $json = json_encode($xml);
    $array = json_decode($json,TRUE);
    return $array;
}

function prep_role_array($filepath){
    $array = xml_decode($filepath);

    // replace context name with context ids
    // and remove 'level'
    if(is_array($array['contextlevels']['level']))
    {
        foreach ($array['contextlevels']['level'] as $key => $value) {
            $array['contextlevels'][$key] = getcontextid($value);
        }
        unset($array['contextlevels']['level']);
    } else {
        $array['contextlevels'] = [getcontextid($array['contextlevels']['level'])];
    }

    // This is what Moodle will be expecting, instead of empty array, an empty string.
    if(is_array($array['archetype']) && empty($array['archetype'])){
        $array['archetype'] = '';
    }

    return $array;
}