<?php
defined('_JEXEC') or die('Restricted access');
jimport('joomla.environment.browser');

class JUIdeiModelIdea extends JModelLegacy {

    public function vote($idea_id,$option_id) {
        $db = JFactory::getDBO();
        $user = JFactory::getUser();
        $date = JFactory::getDate();

        $idea_id = (int)$idea_id;
        $option_id = (int)$option_id;

        setcookie('_doneidea' . $idea_id,1,time() + 60,JURI::base(true));

        $ip = ip2long($this->get_ip());

        $browser = JBrowser::getInstance();
        $agent = $browser->getAgentString();
        $agent = MD5($agent);

        $dt = $date->toSql();

        $query = "INSERT INTO #__mir_votes (date, option_id, idea_id, ip, browser, user_id) VALUES ('{$dt}', '{$option_id}', '{$idea_id}', '{$ip}', '{$agent}', '{$user->id}')";
        $db->setQuery($query);

        if (!$db->query()) {
            $msg = $db->stderr();
            $tom = "error";
        }

        return true;
    }

    public function getOptions() {
        $db = JFactory::getDBO();

        $idea_id = JFactory::getApplication()->input->getInt('id',0);

        $query = "SELECT o.*, COUNT(v.id) AS hits,
    	(SELECT COUNT(id) FROM #__mir_votes WHERE idea_id=" . $idea_id . ") AS voters"
                . " FROM #__mir_options AS o"
                . " LEFT JOIN #__mir_votes AS v"
                . " ON (o.id = v.option_id AND v.idea_id = " . $idea_id . ")"
                . " WHERE o.idea_id = " . $idea_id
                . " AND o.text <> ''"
                . " GROUP BY o.id "
                . " ORDER BY o.ordering ";

        $db->setQuery($query);

        if ($votes = $db->loadObjectList()) {
            return $votes;
        } else {
            return $db->stderr();
        }
    }

    public function getIdei() {
        $db = JFactory::getDBO();

        $query = $db->getQuery(true);
        $query->select("id, title, CASE WHEN CHAR_LENGTH(alias) THEN CONCAT_WS(':', id, alias) ELSE id END AS slug");
        $query->from('#__mir_idei');
        $query->where('published = 1');
        $query->order('id');
        $db->setQuery($query);

        if ($pList = $db->loadObjectList()) {
            return $pList;
        } else {
            return $db->stderr();
        }
    }

    public function ipVoted($idea,$idea_id) {
        $params = new JRegistry($idea->params);

        if ($params->get('ip_check') == 0) {
            return false;
        }

        $idea_id = (int)$idea_id;
        $ip = ip2long($this->get_ip());

        $browser = JBrowser::getInstance();
        $agent = $browser->getAgentString();
        $agent = MD5($agent);

        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from('#__mir_votes');
        $query->where('idea_id = ' . $db->Quote($idea_id));
        $query->where('(ip = ' . $db->Quote($ip) . ' AND browser = ' . $db->Quote($agent) . ')');
        $db->setQuery($query);
        $res = $db->loadResult();

        if (!empty($res)) {
            return true;
        } else {
            return false;
        }
    }

    public function get_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        $pieceip = explode(",",$ip);
        $_ip = trim($pieceip[0]);

        return $_ip;
    }
}