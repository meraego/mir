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

jimport('joomla.environment.browser');

class modJUIdeiHelper
{
    function getIdeaOptions($idea_id)
    {
        $db = JFactory::getDBO();

        $query = "SELECT o.id, o.text, o.ordering" .
            " FROM #__mir_options AS o " .
            " WHERE o.idea_id = " . (int) $idea_id .
            " AND o.text <> ''" .
            " ORDER BY o.ordering";

        $db->setQuery($query);

        if(!($options = $db->loadObjectList()))
        {
            return "helper " . $db->stderr();
        }

        return $options;
    }

    // checks if user has voted (if cookie is set)
    function alreadyVoted($id)
    {
        $app  = JFactory::getApplication();
        $cookieName = JApplicationHelper::getHash($app->getName() . 'idea' . $id);
        $voted      = JRequest::getVar($cookieName, '0', 'COOKIE', 'INT');

        return $voted;
    }

    function userVoted($user_id, $idea_id)
    {
        $db    = JFactory::getDBO();
        $query = "SELECT date FROM #__mir_votes WHERE idea_id=" . (int) $idea_id . " AND user_id=" . (int) $user_id;
        $db->setQuery($query);

        return $userVoted = ($db->loadResult()) ? 1 : 0;
    }

    function ipVoted($idea_id)
    {
        $db = JFactory::getDBO();
        $ip = ip2long($_SERVER['REMOTE_ADDR']);

        $browser = JBrowser::getInstance();
        $agent   = $browser->getAgentString();
        $agent   = MD5($agent);

        $query = $db->getQuery(true);
        $query->select('ip');
        $query->from('#__mir_votes');
        $query->where('idea_id = ' . $db->Quote($idea_id));
        $query->where('(ip = ' . $db->Quote($ip) . ' AND browser = ' . $db->Quote($agent) . ')');
        $db->setQuery($query);

        return $ipVoted = ($db->loadResult()) ? 1 : 0;
    }

    function getResults($idea_id)
    {
        $db    = JFactory::getDBO();
        $query = "SELECT o.*, COUNT(v.id) AS hits,
		(SELECT COUNT(id) FROM #__mir_votes WHERE idea_id=" . $idea_id . ") AS votes
		FROM #__mir_options AS o
		LEFT JOIN  #__mir_votes AS v
		ON (o.id = v.option_id AND v.idea_id = " . (int) $idea_id . ")
		WHERE o.idea_id=" . (int) $idea_id . "
		AND o.text <> ''
		GROUP BY o.id
		ORDER BY o.ordering";

        $db->setQuery($query);

        return $db->loadObjectList();
    }

    function getActiveIdei()
    {
        $db    = JFactory::getDBO();
        $query = 'SELECT id FROM `#__mir_idei` WHERE published = 1 AND (NOW() <= publish_down) ORDER BY rand()';
        $db->setQuery($query, 0, 1);

        if($ids = $db->loadResult())
        {
            return $ids;
        }
        else
        {
            return 0;
        }
    }

    function getItemid($idea_id)
    {
        $component = JComponentHelper::getComponent('com_mir');
        $menus     = JApplication::getMenu('site', array());
        $items     = $menus->getItems('component_id', $component->id);

        $match   = false;
        $item_id = '';

        if(isset($items))
        {
            foreach ($items as $item)
            {
                if((@$item->query['view'] == 'idea') && (@$item->query['id'] == $idea_id))
                {
                    $itemid = $item->id;
                    $match  = true;

                    break;
                }
            }
        }

        if($match)
        {
            $item_id = '&Itemid=' . $itemid;
        }

        return $item_id;
    }
}