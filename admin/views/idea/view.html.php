<?php
/**
 * JUIdei
 *
 * @package          Joomla.Site
 * @subpackage       com_mir
 *
 * @author           Denys Nosov, denys@joomla-ua.org
 * @copyright        2016-2017 (C) Joomla! Ukraine, http://joomla-ua.org. All rights reserved.
 * @license          GNU General Public License version 2 or later; see LICENSE.txt
 * @license          GNU/GPL based on AceIdei www.joomace.net
 */

/**
 * @copyright      2009-2011 Mijosoft LLC, www.mijosoft.com
 * @license        GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @license        GNU/GPL based on AceIdei www.joomace.net
 *
 * @copyright (C)  2009 - 2011 Hristo Genev All rights reserved
 * @license        http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link           http://www.afactory.org
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.html.pane');

class JUIdeiViewIdea extends JUIdeiView
{
    function display($tpl = null)
    {
        $doc = JFactory::getDocument();
        $doc->addStyleSheet('components/com_mir/assets/css/mir.css');

        $cid  = JRequest::getVar('cid', array(0), '', 'array');
        $edit = JRequest::getVar('edit', true);
        $text = (($edit) ? JText::_('Edit') : JText::_('New'));

        JToolBarHelper::title(JText::_('COM_MIR_idea') . ': <small><small>[ ' . $text . ' ]</small></small>', 'mir');
        JToolBarHelper::save();
        JToolBarHelper::apply();
        JToolBarHelper::cancel();

        $this->mainframe = JFactory::getApplication();
        $user            = JFactory::getUser();

        $row = $this->get('ItemData');

        // fail if checked out not by 'me'
        if($row->isCheckedOut($user->get('id')))
        {
            $msg = JText::sprintf('DESCBEINGEDITTED', JText::_('COM_MIR_THE_idea'), $row->title);
            $this->setRedirect('index.php?option=com_mir', $msg);
        }

        if($row->id == 0)
        {
            $row->published = 1;
        }

        $options  = array();
        $ordering = array();

        if($edit)
        {
            $options = $row->getOptions($row->id);
        }
        else
        {
            $row->lag = 24 * 60;
        }

        $task         = JFactory::getApplication()->input->get('task');
        $this->params = $this->get('Form');

        $this->row     = $row;
        $this->options = $options;
        $this->edit    = $edit;

        parent::display($tpl);
    }
}