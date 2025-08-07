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


 
function xmldb_local_api_extend_upgrade($oldversion) {

    global $CFG, $DB;

    if ($oldversion < 2023022404) {

        /****  Create the required custom field for the user profile  ****/

        require_once($CFG->dirroot.'/user/profile/definelib.php');
        require_once($CFG->dirroot.'/user/profile/field/text/define.class.php');

        // Get all categories.
        $categories = $DB->get_records('user_info_category', null, 'sortorder ASC');


        // Check if field already exist
        foreach ($categories as $category) {
            if ($fields = $DB->get_records('user_info_field', array('categoryid' => $category->id), 'sortorder ASC')) {
                foreach ($fields as $field) {
                    if ($field->shortname === 'unicaf_uuid') {
                        $field_exist = true;
                        break;
                    }
                }
            }
        }

        // create field only if it doesn't exist
        if (empty($field_exist)){
            $data = new stdClass();
            $data->id = 0;
            $data->action = 'editfield';
            $data->datatype = 'text';
            $data->shortname = 'unicaf_uuid';
            $data->name = 'Unicaf UUID';
            $data->description =  '';
            $data->required = '0';
            $data->locked = '1';
            $data->forceunique = '1';
            $data->signup = '0';
            $data->visible = '0';
            $data->categoryid = '1';
            $data->defaultdata = '';
            $data->param1 = 30;
            $data->param2 = 2048;
            $data->param3 = '0';
            $data->param4 = '';
            $data->param5 = '';
            $data->submitbutton = 'Save changes';
            $data->descriptionformat =  '1';
            $formfield = new profile_define_text();
            $formfield->define_save($data);
        }

        /**** End for the custom field creation ****/


        /**** Create custom roles ****/

        // Create the rest roles
        $path = __DIR__.'/../roles/';
        $dir = new DirectoryIterator($path);

        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {

                $filename = $fileinfo->getFilename();

                // separate webservice
                if ($filename === 'webservice.xml') {
                    $filename_webservice = $filename;
                } else {
                    $filename_array[] = $filename;
                }
            }
        }

        // proceed creating webservice role if we have the file.
        if (!empty($filename_webservice) && !empty($filename_array)){
            $filepath = $path . $filename_webservice;

            if (file_exists($filepath)) {
                $array = prep_role_array($filepath);
                $webservice_role_id = createRoleWithPermissions($array);
            }

            if (!empty($webservice_role_id)) {
                foreach ($filename_array as $filename) {
                    $filepath = $path . $filename;
                    if (file_exists($filepath)) {
                        $array = prep_role_array($filepath);
                        createRoleWithPermissions($array, $webservice_role_id);
                    }
                }
            }

        }
        /**** End of custom role creation ****/

        return true;

    } // End of version 20210423000.00


    return true;
}
	

 
