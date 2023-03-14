<?php
class block_attendancetable_edit_form extends block_edit_form
{

    protected function specific_definition($mform)
    {
        if (!self::on_site_page()) {
            // Section header title according to language file.
            $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

            // Select to pick how many students to show.
            $studentcount = array(1 => 1, 2, 3, 4, 5);
            $mform->addElement('select', 'config_amount', get_string('studentcount', 'block_attendancetable'), $studentcount);
            $mform->setDefault('config_amount', 5);

            //Enable/Disable option to know whether or not students can see their attendance bar
            $showarray = array(get_string('nooption', 'block_attendancetable'), get_string('yesoption', 'block_attendancetable'));
            $mform->addElement('select', 'config_show', get_string('showbarheader', 'block_attendancetable'), $showarray);
            $mform->setDefault('config_show', 1);

            $this->add_action_buttons();
        }
    }

    //If page is course, enable edit_form
    public static function on_site_page($page = null)
    {
        global $PAGE;
        $page = $page ?? $PAGE;
        $context = $page->context ?? null;

        if (!$page || !$context) {
            return false;
        } else if ($context->contextlevel === CONTEXT_SYSTEM && $page->requestorigin === 'restore') {
            return false;
        } else if ($context->contextlevel === CONTEXT_COURSE && $context->instanceid == SITEID) {
            return true;
        } else if ($context->contextlevel < CONTEXT_COURSE) {
            return true;
        } else {
            return false;
        }
    }
}
