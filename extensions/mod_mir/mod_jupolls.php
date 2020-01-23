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

$lang_file = JFactory::getLanguage();
$lang_file->load('com_mir', JPATH_SITE);

require_once(__DIR__ . '/helper.php');

$menu    = $app->getMenu();
$items   = $menu->getItems('link', 'index.php?option=com_mir&view=idea');
$itemid  = isset($items[0]) ? '&Itemid=' . $items[0]->id : '';
$details = "";

$idea_id = $params->get('id');

if(!$idea_id)
{
    $ids = modJUIdeiHelper::getActiveIdei();

    if(count($ids) > 1)
    {
        $idea_id = $ids[array_rand($ids)];
    }
    else
    {
        $idea_id = $ids;
    }
}

if($idea_id > 0)
{
    $results = modJUIdeiHelper::getResults($idea_id);
}
else
{
    return '<div class="panel panel-default panel-flat"><div class="panel-body"><b class="text-grey">Опитування відсутні!</b><br><a href="/idei">Переглянути архів »»</a></div></div>';
}

JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_mir/tables');
$idea = JTable::getInstance('Idea', 'Table');

if(!$idea->load($idea_id)) return;

$ideaParams = new JRegistry($idea->params);
$params     = clone($params);
$params->merge($ideaParams);

$slug = ($idea->alias == '') ? $idea->id : $idea->id . ":" . $idea->alias;

$voted = modJUIdeiHelper::alreadyVoted($idea_id);

$user      = JFactory::getUser();
$userVoted = modJUIdeiHelper::userVoted($user->id, $idea_id);
$guest     = $user->guest;

$ipVoted = modJUIdeiHelper::ipVoted($idea_id);

$display_idea = 0;

$app = JFactory::getApplication();
$date      = JFactory::getDate();

$now          = JHtml::date($date->toSql(), 'Y-m-d H:i:s');
$now          = strtotime($now);
$publish_up   = strtotime($idea->publish_up);
$publish_down = strtotime($idea->publish_down);

if(($now > $publish_up) && ($now < $publish_down))
{
    $display_submit = 1;

    // if only registered users can vote
    if($params->get('only_registered'))
    {
        //if the user is not a guest
        if(!$guest)
        {
            //if only one vote is allowed per logged user
            if($params->get('one_vote_per_user'))
            {
                //check if user has voted
                if($userVoted)
                {
                    //display the idea with disabled options
                    $display_submit = 0;
                    $msg            = JText::_("MOD_MIR_ALREADY_VOTED");
                    $details        = JText::_("MOD_MIR_ONLY_ONE_VOTE_PER_USER");
                    //user has not voted yet
                }
                else
                {
                    //display the idea
                    $display_idea   = 1;
                    $display_submit = 1;
                    $msg            = "";
                }
                // if loggedin user are allowed to vote unlimited times
            }
            else
            {
                // Check the cookie
                if($voted)
                {
                    $display_idea   = 0;
                    $display_submit = 0;
                    $msg            = JText::_("MOD_MIR_ALREADY_VOTED");
                    $details        = JText::sprintf("MOD_MIR_ONLY_ONE_VOTE_PER_HOUR", $idea->lag / 60);

                    //hm check the ip please but only if allowed to do that
                }
                elseif($params->get('ip_check'))
                {
                    if($ipVoted)
                    {
                        //display the idea with disabled options
                        $display_idea   = 0;
                        $display_submit = 0;
                        $msg            = JText::_("MOD_MIR_ALREADY_VOTED");
                        $details        = JText::_("MOD_MIR_ONLY_ONE_VOTE_PER_IP");
                        //if user's ip has not been logged
                    }
                    //if user has not voted
                }
                else
                {
                    //display the idea
                    $display_idea   = 1;
                    $display_submit = 1;
                    $msg            = "";
                }
            }
            //if the user has not logged in
        }
        else
        {
            $display_idea   = 1;
            $display_submit = 0;

            $return = JRequest::getURI();
            $return = base64_encode($return);
            $link   = 'index.php?option=com_users&view=login&return=' . $return;

            $msg = JText::sprintf('MOD_MIR_PLEASE_REGISTER_TO_VOTE', '<a href="' . $link . '">', '</a>');
        }
    }
    else
    {
        if($voted)
        {
            $display_idea   = 0;
            $display_submit = 0;
            $msg            = JText::_("MOD_MIR_ALREADY_VOTED");
            $details        = JText::sprintf("MOD_MIR_ONLY_ONE_VOTE_PER_HOUR", $idea->lag / 60);
        }
        else
        {
            if($params->get('ip_check'))
            {
                if($ipVoted)
                {
                    $display_idea   = 0;
                    $display_submit = 0;
                    $msg            = JText::_("MOD_MIR_ALREADY_VOTED");
                    $details        = JText::_("MOD_MIR_ONLY_ONE_VOTE_PER_IP");
                }
                else
                {
                    $display_idea   = 1;
                    $display_submit = 1;
                    $msg            = "";
                }
            }
            else
            {
                $display_idea   = 1;
                $display_submit = 1;
                $msg            = "";
            }
        }
    }
}
else
{
    $display_submit = 0;
    $msg            = JText::_("MOD_MIR_VOTING_HAS_NOT_STARTED");
    $publish_up     = JFactory::getDate($idea->publish_up);
    $details        = JText::_("MOD_MIR_IT_WILL_START_ON") . ": " . $publish_up->format($params->get('msg_date_format'));
}

if($now > $publish_down)
{
    $display_idea = 0;
    $msg          = JText::_("MOD_MIR_VOTING_HAS_ENDED");
    $publish_down = JFactory::getDate($idea->publish_down);
    $details      = JText::_("MOD_MIR_ON") . ": " . $publish_down->format($params->get('msg_date_format'));
}

$disabled = ($display_submit) ? '' : 'disabled="disabled"';


if($idea && $idea->id)
{
    $layout  = JModuleHelper::getLayoutPath('mod_mir');
    $tabcnt  = 0;
    $options = modJUIdeiHelper::getIdeaOptions($idea_id);
    $itemid  = modJUIdeiHelper::getItemid($idea_id);
    require($layout);
}