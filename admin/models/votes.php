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

class JUIdeiModelVotes extends JUIdeiModel
{

    var $_query = null;
    var $_data = null;
    var $_total = null;
    var $_pagination = null;

    function __construct()
    {
        parent::__construct();

        $this->mainframe = JFactory::getApplication();
        $this->option    = JRequest::getWord('option');

        // Get the pagination request variables
        $limit      = $this->mainframe->getUserStateFromRequest($this->option . '.votes.limit', 'limit', $this->mainframe->getCfg('list_limit'), 'int');
        $limitstart = $this->mainframe->getUserStateFromRequest($this->option . '.votes.limitstart', 'limitstart', 0, 'int');

        // In case limit has been changed, adjust limitstart accordingly
        $limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);

        $this->setState($this->option . '.votes.limit', $limit);
        $this->setState($this->option . '.votes.limitstart', $limitstart);

        $this->_buildViewQuery();
    }

    function getData()
    {
        if(empty($this->_data))
        {
            $this->_data = $this->_getList($this->_query, $this->getState('limitstart'), $this->getState('limit'));
        }

        return $this->_data;
    }

    function getTotal()
    {
        if(empty($this->_total))
        {
            $this->_total = $this->_getListCount($this->_query);
        }

        return $this->_total;
    }

    function getPagination()
    {
        if(empty($this->_pagination))
        {
            jimport('joomla.html.pagination');
            $this->_pagination = new JPagination($this->getTotal(), $this->getState($this->option . '.votes.limitstart'), $this->getState($this->option . '.votes.limit'));
        }

        return $this->_pagination;
    }

    function _buildViewQuery()
    {
        if(empty($this->_query))
        {
            $db = JFactory::getDBO();

            $where   = $this->_buildViewWhere();
            $orderby = $this->_buildViewOrderBy();

            $this->_query = "SELECT v.id, v.date, o.text, INET_NTOA(ip) AS ip, v.browser, 
			CASE WHEN v.user_id <> 0 THEN u.name ELSE " . $db->Quote(JText::_('Guest')) . " END AS name
			FROM #__mir_votes AS v
			LEFT JOIN #__mir_options AS o ON o.id = v.option_id
			LEFT JOIN #__users AS u ON u.id = v.user_id "
                . $where
                . $orderby;
        }

        return $this->_query;
    }

    function _buildViewOrderBy()
    {
        $filter_order     = $this->mainframe->getUserStateFromRequest($this->option . '.votes.filter_order', 'filter_order', 'v.date', 'cmd');
        $filter_order_Dir = $this->mainframe->getUserStateFromRequest($this->option . '.votes.filter_order_Dir', 'filter_order_Dir', '', 'word');

        $orderby = ' ORDER BY ' . $filter_order . ' ' . $filter_order_Dir;

        return $orderby;
    }

    function _buildViewWhere()
    {
        $db = JFactory::getDBO();

        $filter_order     = $this->mainframe->getUserStateFromRequest($this->option . '.votes.filter_order', 'filter_order', 'v.date', 'cmd');
        $filter_order_Dir = $this->mainframe->getUserStateFromRequest($this->option . '.votes.filter_order_Dir', 'filter_order_Dir', '', 'word');
        $search           = $this->mainframe->getUserStateFromRequest($this->option . '.votes.search', 'search', '', 'string');
        $search           = JString::strtolower($search);

        $idea_id = JRequest::getInt('id', 0, 'GET');

        $where   = array();
        $where[] = ' v.idea_id = ' . $idea_id;

        if($search)
        {
            $search  = $db->Quote('%' . $db->getEscaped($search, true) . '%', false);
            $where[] = ' LOWER(u.name) LIKE ' . $search;
        }

        $where = ' WHERE ' . implode(' AND ', $where);

        return $where;
    }

    function getList()
    {
        $db      = JFactory::getDBO();
        $idea_id = JRequest::getInt('id', 0, 'GET');

        // list of aidei for dropdown selection
        $query = "SELECT m.id, m.title, COUNT(v.id) AS votes"
            . " FROM #__mir_idei AS m"
            . " LEFT JOIN #__mir_votes AS v"
            . " ON m.id = v.idea_id"
            . " GROUP BY m.id ORDER BY id";

        $db->setQuery($query);
        $pList = $db->loadObjectList();

        //Get the title for the site=the active idea
        foreach ($pList as $p)
        {
            if($p->id == $idea_id) {
                $title = $p->title;
            }
        }

        //Make the URLs for the dropdown
        foreach ($pList as $k => $p)
        {
            $pList[$k]->url = 'index.php?option=com_mir&controller=votes&task=view&id=' . $p->id;
        }
        array_unshift($pList, JHTML::_('select.option', '', JText::_('Select Idea from the list'), 'url', 'title'));

        // dropdown output
        $lists = array();

        $lists['idei'] = JHTML::_('select.genericlist', $pList, 'id', 'class="inputbox" size="1" style="width:400px" onchange="if (this.options[selectedIndex].value != \'\') {document.location.href=this.options[selectedIndex].value}"',
            'url', 'title', 'index.php?option=com_mir&controller=votes&task=view&id=' . $idea_id);

        return $lists;
    }

    function getTitle()
    {
        $idea_id = JRequest::getInt('id', 0, 'GET');

        $db = JFactory::getDBO();
        $db->setQuery("SELECT title FROM #__mir_idei WHERE id = " . (int) $idea_id);

        return $db->loadResult();
    }

    function deleteVotes()
    {
        $db = JFactory::getDBO();

        $cid = JRequest::getVar('cid', array(), '', 'array');
        JArrayHelper::toInteger($cid);
        $cids = implode(',', $cid);

        //Delete the chosen votes, dates, ips, users, etc from #__aidei_date table
        $db->setQuery("DELETE FROM #__mir_votes WHERE id IN (" . $cids . ")");

        if(!$db->query())
        {
            return false;
        }
        else
        {
            return true;
        }
    }
}