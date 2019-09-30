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
 * Form for editing HTML block instances.
 *
 * @package   block_modlib
 * @copyright 1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_modlib extends block_base {

    function has_config() {
        return true;
    }


//----------------------------------------------------------------------------------------------------------------------
    function init() {
        $this->title = get_string('pluginname', 'block_modlib');
    }

//----------------------------------------------------------------------------------------------------------------------
    function get_content() {
        global $PAGE;

        // only show this when the user is editing
        if (!$this->page->user_is_editing()) {
            $this->content = new stdClass;
            $this->content->text = '';
            $this->content->footer = '';
        } else {
            $PAGE->requires->js_call_amd('block_modlib/install_module', 'init', array());

            $this->content = new stdClass;
            $this->content->text = $this->get_library_modules();
            $this->content->footer = '';
        }
        return $this->content;
    }

//----------------------------------------------------------------------------------------------------------------------
    function get_library_modules() {
        global $DB;

        // The ID of the 'Templete Course' course
        $lib_course_id = $this->config->template_course;

        // The ID of the section 1 of that course since this is the one containing the currently valid library
        $rec = $DB->get_record('course_sections', array('course' => $lib_course_id, 'section' => "1"));
        $libsec_id = $rec->id;

        // get the modules
        $raw_mods = $DB->get_records('course_modules', array('course' => $lib_course_id, 'section' => $libsec_id));
        if(sizeof($raw_mods) == 0) {
            return "No library found!";
        }

        // Show what we found
        return $this->render_modules($raw_mods);
    }

//----------------------------------------------------------------------------------------------------------------------
    function render_modules($raw_mods) {
        global $DB;
        $o = '';
        // create a modal dialog that will be shown when installing modules
        $o .= '<div class="modlib-modal" style="display: none;" id="modlib-spinner-modal">';
        $o .= '<div class="spinner-container">';
        $o .= '<img src="https://localhost/moodle/theme/image.php/boost/core/1569403484/i/loading" class="spinner">';
        $o .= '<div id="modlib-modal-msg" style="margin-top: 10px;">'.get_string('please_wait', 'block_modlib').'</div>';
        $o .= '</div></div>';

        // An introduction
        $o .= html_writer::div(get_string('intro_text', 'block_modlib'));
        $o .= '<hr>';

        // A table with available modules
        $o .= html_writer::start_tag('table', array());

        foreach($raw_mods as $raw_mod) {
            // get the module type
            $module_type = $DB->get_record('modules', array('id' => $raw_mod->module));
            // get the module record
            $module = $DB->get_record($module_type->name, array('id' => $raw_mod->instance));

            $o .= html_writer::start_tag('tr', array('id' => $module->id, 'class' => 'module_row'));
            $o .= html_writer::tag('td', '<input type="checkbox" class="module" value="'.$module->id.'" cmid ="'.$raw_mod->id.'" module_type ="'.$module_type->name.'" name="'.$module->name.'"> ');
            $o .= html_writer::tag('td','<b>'.ucfirst($module_type->name).'</b>: '.$module->name, array());
            $o .= html_writer::end_tag('tr');
        }
        $o .= html_writer::end_tag('table');

        $o .= $this->build_topics_menu();

        return $o;
    }

//----------------------------------------------------------------------------------------------------------------------
    function build_topics_menu() {
        global $COURSE, $PAGE;
        $o = '';

        // build a drop down menu to select a target topic
        $course = $this->page->course;
        $courseformat = course_get_format($course);
        $coursesections = $courseformat->get_sections();

        $o .= html_writer::empty_tag('hr');
        $o .= "<form method='post'>";

        $title = get_string('select_section_mouseover', 'block_modlib');
        $o .= html_writer::start_tag('button', array('type' => 'button', 'id'=>'target_topic_btn', 'class' => 'btn dropdown-toggle btn-primary disabled', 'data-toggle' => 'dropdown', 'title' => $title));
        $o .= get_string('select_section', 'block_modlib');
        $o .= html_writer::end_tag('button');

        $o .= html_writer::start_tag('div', array('class' => 'dropdown-menu modlib-sections'));
        foreach($coursesections as $section) {
            $o.= html_writer::tag('a', $section->name, array(
                'class' => 'dropdown-item',
                'value' => $section->id
            ));
        }
        $o .= html_writer::end_tag('div');

        $o .= "</form>";
        return $o;
    }

}

