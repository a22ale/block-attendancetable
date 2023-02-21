<?php
class block_attendancetable_edit_form extends block_edit_form
{

    protected function specific_definition($mform)
    {

        // Section header title according to language file.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        // Select to pick how many students to show.
        $studentCount = array(1 => 1, 2, 3, 4, 5);
        $mform->addElement('select', 'config_amount', get_string('studentcount', 'block_attendancetable'), $studentCount);
        $mform->setDefault('config_amount', 5);
        $this->add_action_buttons();
    }
}