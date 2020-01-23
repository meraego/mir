<?php

defined('_JEXEC') or die('Restricted access');
jimport('joomla.html.parameter.element');

class JElementIdea extends JElement
{
    var $_name = 'Idea';

    function fetchElement($name, $value, &$node, $control_name)
    {
        $db = JFactory::getDbo();

        $query = $db->getQuery(true);
        $query->select('a.id, a.title');
        $query->from('#__mir_idei AS a');
        $query->where('a.published = ' . $db->Quote('package'));
        $query->order('a.title');
        $db->setQuery($query);
        $options = $db->loadObjectList();

        if(JFactory::getApplication()->input->get('option') == "com_modules")
        {
            array_unshift($options, JHTML::_('select.option', '', '- - - - - - - - - - -', 'id', 'title'));
            array_unshift($options, JHTML::_('select.option', '0', JText::_('Show random idea'), 'id', 'title'));
        }
        else
        {
            array_unshift($options, JHTML::_('select.option', '0', '- - ' . JText::_('Select Idea') . ' - -', 'id', 'title'));
        }

        return JHTML::_('select.genericlist', $options, '' . $control_name . '[' . $name . ']', 'class="inputbox"', 'id', 'title', $value, $control_name . $name);
    }
}
