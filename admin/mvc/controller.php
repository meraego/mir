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

defined('_JEXEC') or die ('Restricted access');

jimport('joomla.application.component.controller');

class JUIdeiController extends JControllerLegacy
{
    public function __construct()
    {
        parent::__construct();

        $this->registerTask('add', 'edit');
        $this->registerTask('apply', 'save');
        $this->registerTask('unpublish', 'publish');
        $this->registerTask('deleteVotes', 'deleteVotes');
        $this->registerTask('importIdei', 'importIdei');
    }

    public function display($cachable = false, $urlparams = false)
    {
        if(JFactory::getApplication()->isAdmin())
        {
            $controller = $this->input->get('controller', 'idei');
        }
        else
        {
            $controller = $this->input->get('view', 'idei');
        }

        $this->input->set('view', $controller);

        parent::display($cachable, $urlparams);
    }

    public function edit()
    {
        $this->input->set('view', 'idea');
        $this->input->set('edit', true);
        $this->input->set('hidemainmenu', 1);

        parent::display();
    }

    public function save()
    {
        // Check for request forgeries
        JSession::checkToken() or jexit('Invalid Token');

        $db = JFactory::getDBO();

        // save the aidea parent information
        $row = JTable::getInstance('Idea', 'Table');

        $post = JRequest::get('post');
        if(!$row->bind($post))
        {
            JError::raiseError(500, $row->getError());
        }

        $isNew = ($row->id == 0);

        //reset the idea, erases hits and voters
        if($optionReset = JRequest::getVar('reset'))
        {
            $model = $this->getModel('idei');
            $model->resetVotes((int) $row->id);
        }

        if(!$row->check())
        {
            JError::raiseError(500, $row->getError());
        }

        if(!$row->store())
        {
            JError::raiseError(500, $row->getError());
        }
        $row->checkin();

        // put all idea options and their colors and ordering in arrays
        $options   = JArrayHelper::getValue($post, 'ideaoption', array(), 'array');
        $orderings = JArrayHelper::getValue($post, 'ordering', array(), 'array');

        //options represented by id=>text
        foreach ($options as $i => $text)
        {
            // turns ' into &#039;
            $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

            if($isNew)
            {
                if($text != '')
                {
                    $obj           = new stdClass();
                    $obj->idea_id  = (int) $row->id;
                    $obj->text     = $text;
                    $obj->ordering = $orderings[$i];
                    $db->insertObject('#__mir_options', $obj);
                }
            }
            else
            {
                if($text != '')
                {
                    $obj           = new stdClass();
                    $obj->id       = (int) $i;
                    $obj->text     = $text;
                    $obj->ordering = $orderings[$i];
                    $db->updateObject('#__mir_options', $obj, 'id');
                }
                else
                {
                    //If there are empty options delete them so we don't waste database space
                    $model = $this->getModel('idea');
                    if(!$model->deleteOption($i))
                    {
                        JError::raiseError(500, $model->getError());
                    }
                }
            }
        }

        // Are there any new options that are added
        if(JRequest::getVar('is_there_extra'))
        {
            $extra_options  = JArrayHelper::getValue($post, 'ideaoptionextra', array(), 'array');
            $extra_ordering = JArrayHelper::getValue($post, 'extra_ordering', array(), 'array');
            $extra_colors   = JArrayHelper::getValue($post, 'extra_colors', array(), 'array');

            //Insert in the database the newly created options
            foreach ($extra_options as $k => $text)
            {
                if($text != '')
                {
                    $obj           = new stdClass();
                    $obj->idea_id  = (int) $row->id;
                    $obj->text     = $text;
                    $obj->ordering = $extra_ordering[$k];
                    $db->insertObject('#__mir_options', $obj);
                }
            }
        }

        switch (JFactory::getApplication()->input->get('task'))
        {
            case 'apply':
                $msg  = JText::_('COM_MIR_IDEA_SAVED');
                $link = 'index.php?option=com_mir&controller=idea&task=edit&cid[]=' . $row->id;
                break;
            case 'save':
            default:
                $msg  = JText::_('COM_MIR_IDEA_SAVED');
                $link = 'index.php?option=com_mir';
                break;
        }

        $this->setRedirect($link, $msg);
    }

    public function remove()
    {
        // Check for request forgeries
        JSession::checkToken() or jexit('Invalid Token');

        $db  = JFactory::getDBO();
        $cid = JRequest::getVar('cid', array(), '', 'array');

        JArrayHelper::toInteger($cid);
        $msg = '';

        for ($i = 0, $n = count($cid); $i < $n; $i++)
        {
            $aidea = JTable::getInstance('idea', 'Table');
            if(!$aidea->delete($cid[$i]))
            {
                $msg .= $aidea->getError();
                $tom = "error";
            }
            else
            {
                $msg = JTEXT::_('COM_MIR_IDEA_DELETED');
                $tom = "";
            }
        }

        $this->setRedirect('index.php?option=com_mir', $msg, $tom);
    }

    public function deleteVotes()
    {
        // Check for request forgeries
        JSession::checkToken() or jexit('Invalid Token');

        $idea_id = JRequest::getVar('idea_id', 0, 'POST', 'INT');
        $model   = $this->getModel('votes');

        if($model->deleteVotes())
        {
            $msg = Jtext::_("COM_MIR_DELETED_VOTES_YES");
            $tom = "";
        }
        else
        {
            $msg = Jtext::_("COM_MIR_DELETED_VOTES_NO");
            $tom = "error";
        }

        $this->setRedirect('index.php?option=com_mir&controller=votes&task=view&id=' . $idea_id, $msg, $tom);
    }

    public function publish()
    {
        $app = JFactory::getApplication();

        // Check for request forgeries
        JSession::checkToken() or jexit('Invalid Token');

        $user = JFactory::getUser();

        $cid     = JRequest::getVar('cid', array(), '', 'array');
        $publish = ($app->input->get('task') == 'publish' ? 1 : 0);

        $table = JTable::getInstance('idea', 'Table');
        JArrayHelper::toInteger($cid);

        if(!$table->publish($cid, $publish, $user->get('id')))
        {
            $table->getError();
        }

        if(count($cid) < 1)
        {
            $action = $publish ? 'publish' : 'unpublish';
            JError::raiseError(500, JText::_('Select an item to' . $action, true));
        }

        $app->redirect('index.php?option=com_mir');
    }

    public function cancel()
    {
        // Check for request forgeries
        JSession::checkToken() or jexit('Invalid Token');

        $id  = JRequest::getVar('id', 0, '', 'int');
        $row = JTable::getInstance('idea', 'Table');

        $row->checkin($id);

        $this->setRedirect('index.php?option=com_mir');
    }

    public function resetVotes()
    {
        // Check for request forgeries
        JSession::checkToken() or jexit('Invalid Token');

        $model = $this->getModel('idei');

        if($model->resetVotes())
        {
            $msg = Jtext::_("COM_MIR_DELETED_IDEA_VOTES_YES");
            $tom = "";
        }
        else
        {
            $msg = Jtext::_("VCOM_MIR_DELETED_IDEA_VOTES_NO");
            $tom = "error";
        }

        $this->setRedirect('index.php?option=com_mir&controller=idei', $msg, $tom);
    }

    public function vote()
    {
        // Check for request forgeries
        JSession::checkToken() or jexit('Invalid Token');

        $app = JFactory::getApplication();
        $idea_id   = $app->input->getInt('id', 0);
        $option_id = $app->input->getInt('voteid', 0);
        $idea      = JTable::getInstance('Idea', 'Table');

        if(!$idea->load($idea_id) || $idea->published != 1)
        {
            JError::raiseWarning(404, JText::_('ALERTNOTAUTH 2'));

            return;
        }

        $model = $this->getModel('Idea');

        $params     = new JRegistry($idea->params);
        $cookieName = JApplication::getHash($app->getName() . 'idea' . $idea_id);

        $voted_cookie = JRequest::getVar($cookieName, '0', 'COOKIE', 'INT');
        $voted_ip     = $model->ipVoted($idea, $idea_id);

        if($params->get('ip_check') and ($voted_cookie or $voted_ip or !$option_id))
        {
            if($voted_cookie || $voted_ip)
            {
                $msg = JText::_('COM_MIR_ALREADY_VOTED');
                $tom = "error";
            }

            if(!$option_id)
            {
                $msg = JText::_('COM_MIR_NO_SELECTED');
                $tom = "error";
            }
        }
        else
        {
            if($model->vote($idea_id, $option_id))
            {
                //Set cookie showing that user has voted
                setcookie($cookieName, '1', time() + 60 * $idea->lag);
            }

            $msg = JText::_('COM_MIR_THANK_YOU');
            $tom = "";

            if(JFactory::getUser()->id != 0)
            {
                JPluginHelper::importPlugin('mir');
                $dispatcher = JDispatcher::getInstance();
                $dispatcher->trigger('onAfterVote', array($idea, $option_id));
            }
        }

        // set Itemid id for links
        $menu  = $app->getMenu();
        $items = $menu->getItems('link', 'index.php?option=com_mir');

        $itemid = isset($items[0]) ? '&Itemid=' . $items[0]->id : '';

        $this->setRedirect(JRoute::_('index.php?option=com_mir&view=idea&id=' . $idea_id . ':' . $idea->alias . $itemid, false));
    }
}
