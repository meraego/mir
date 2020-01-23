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

class JUIdeiViewIdei extends JUIdeiView
{

    function display($tpl = null)
    {
        $doc = JFactory::getDocument();
        $doc->addStyleSheet('components/com_mir/assets/css/mir.css');

        JToolBarHelper::title(JText::_('COM_MIR_ideaS'), 'mir');
        JToolBarHelper::addNew();
        JToolBarHelper::editList();
        JToolBarHelper::deleteList();
        JToolBarHelper::divider();
        JToolBarHelper::publishList();
        JToolBarHelper::unpublishList();
        JToolBarHelper::divider();
        JToolBarHelper::custom('resetVotes', 'cancel.png', 'cancel.png', JText::_('COM_MIR_RESET_VOTES'), true);
        JToolBarHelper::preferences('com_mir', 500);

        $this->mainframe = JFactory::getApplication();
        $this->option    = JRequest::getWord('option');

        $filter_order     = $this->mainframe->getUserStateFromRequest($this->option . '.idei.filter_order', 'filter_order', 'm.publish_down', 'string');
        $filter_order_Dir = $this->mainframe->getUserStateFromRequest($this->option . '.idei.filter_order_Dir', 'filter_order_Dir', 'DESC', 'word');
        $filter_state     = $this->mainframe->getUserStateFromRequest($this->option . '.idei.filter_state', 'filter_state', '', 'word');
        $search           = $this->mainframe->getUserStateFromRequest($this->option . '.idei.search', 'search', '', 'string');

        JHTML::_('behavior.tooltip');

        // state filter
        $lists['state'] = JHTML::_('grid.state', $filter_state);

        // table ordering
        $lists['order_Dir'] = $filter_order_Dir;
        $lists['order']     = $filter_order;

        // search filter
        $lists['search'] = $search;

        $this->user       = JFactory::getUser();
        $this->lists      = $lists;
        $this->items      = $this->get('Data');
        $this->pagination = $this->get('Pagination');

        parent::display($tpl);
    }
}