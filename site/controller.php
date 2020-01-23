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

jimport('joomla.application.component.controller');

class JUIdeiController extends JControllerLegacy
{
    /**
     * @param bool $cachable
     * @param bool $urlparams
     *
     * @return JControllerLegacy
     *
     * @since 1.5
     */
    public function display($cachable = false, $urlparams = false)
    {
        $cachable = false;
        $vName    = $this->input->get('view', 'idei');

        $this->input->set('view', $vName);

        return parent::display($cachable, array('Itemid' => 'INT'));
    }

    public function vote()
    {
        JSession::checkToken() or jexit('Invalid Token');

        $app       = JFactory::getApplication();
        $idea_id   = $app->input->getInt('id', 0);
        $option_id = $app->input->getInt('voteid', 0);
        $idea      = JTable::getInstance('Idea', 'Table');

        if(!$idea->load($idea_id) || $idea->published != 1)
        {
            $app->enqueueMessage(JText::_('ALERTNOTAUTH'), 'error');

            return;
        }

        $model = $this->getModel('Idea');

        $cookieName   = JUtility::getHash($app->getName() . 'idea' . $idea_id);
        $voted_cookie = JRequest::getVar($cookieName, '0', 'COOKIE', 'INT');
        $voted_ip     = $model->ipVoted($idea, $idea_id);

        $params = new JRegistry($idea->params);

        if($params->get('ip_check') and
            ($voted_cookie or $voted_ip or !$option_id)
        )
        {
            if($voted_cookie || $voted_ip)
            {
                $app->enqueueMessage(JText::_('COM_MIR_ALREADY_VOTED'), 'error');

                return;
            }

            if(!$option_id)
            {
                $app->enqueueMessage(JText::_('COM_MIR_NO_SELECTED'), 'error');

                return;
            }
        }
        else
        {
            if($model->vote($idea_id, $option_id))
            {
                setcookie($cookieName, '1', time() + 60 * $idea->lag);
            }

            if(JFactory::getUser()->id != 0)
            {
                JPluginHelper::importPlugin('mir');
                $dispatcher = JDispatcher::getInstance();
                $dispatcher->trigger('onAfterVote', array($idea, $option_id));
            }
        }

        $menu   = $app->getMenu();
        $items  = $menu->getItems('link', 'index.php?option=com_mir');
        $itemid = isset($items[0]) ? '&Itemid=' . $items[0]->id : '';

        $this->setRedirect(JRoute::_('index.php?option=com_mir&view=idea&id=' . $idea_id . ':' . $idea->alias . $itemid, false));
    }
}