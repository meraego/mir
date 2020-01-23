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

class JUIdeiModelAjaxvote extends JUIdeiModel
{

    var $_query = null;
    var $_data = null;
    var $_total = null;
    var $_voted = null;

    function getVoted()
    {
        // Check for request forgeries
        JSession::checkToken() or jexit('Invalid Token');

        $app = JFactory::getApplication();
        $idea_id   = JRequest::getInt('id', 0);
        $option_id = JRequest::getInt('voteid', 0);
        $idea      = JTable::getInstance('Idea', 'Table');

        if(!$idea->load($idea_id) || $idea->published != 1)
        {
            $app->redirect('index.php', JText::_('ALERTNOTAUTH 1'));

            return true;
        }

        require_once(JPATH_COMPONENT . '/models/idea.php');
        $model      = new JUIdeiModelIdea();
        $params     = new JRegistry($idea->params);
        $cookieName = JApplicationHelper::getHash($app->getName() . 'idea' . $idea_id);


        $voted_cookie = JRequest::getVar($cookieName, '0', 'COOKIE', 'INT');
        $voted_ip     = $model->ipVoted($idea, $idea_id);

        if($params->get('ip_check') and ($voted_cookie or $voted_ip or !$option_id))
        {
            /*if($voted_cookie || $voted_ip)
            {
                $msg = JText::_('COM_MIR_ALREADY_VOTED');
                $tom = "error";
            }

            if(!$option_id)
            {
                $msg = JText::_('COM_MIR_NO_SELECTED');
                $tom = "error";
            }
            */

            $this->_voted = 0;
        }
        else
        {
            if($model->vote($idea_id, $option_id))
            {
                $this->_voted = 1;

                setcookie($cookieName, '1', time() + 60 * $idea->lag);
            }
            else
            {
                $this->_voted = 0;
            }
        }

        return $this->_voted = 1;
    }

    function getData()
    {
        if(empty($this->_data))
        {
            $query       = $this->_buildQuery();
            $this->_data = $this->_getList($query);
        }

        return $this->_data;
    }

    function getTotal()
    {
        if(empty($this->_total))
        {
            $query        = $this->_buildQuery();
            $this->_total = $this->_getListCount($query);
        }

        return $this->_total;
    }

    function _buildQuery()
    {
        if(empty($this->_query))
        {
            $db      = JFactory::getDBO();
            $idea_id = JRequest::getVar('id', 0, 'POST', 'int');

            $this->_query = "SELECT o.id, o.text, COUNT(v.id) AS votes"
                . " FROM #__mir_options AS o "
                . " LEFT JOIN #__mir_votes AS v "
                . " ON o.id = v.option_id "
                . " WHERE o.idea_id = " . (int) $idea_id
                . " GROUP BY o.id ";
        }

        return $this->_query;
    }
}