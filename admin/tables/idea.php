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

class TableIdea extends JTable
{
    public $id = 0;
    public $title = '';
    public $alias = '';
    public $checked_out = 0;
    public $checked_out_time = 0;
    public $published = 0;
    public $publish_up = 0;
    public $publish_down = 0;
    public $params = null;
    public $access = 0;
    public $lag = 1440;

    function __construct(&$db)
    {
        parent::__construct('#__mir_idei', 'id', $db);
    }

    function bind($array, $ignore = '')
    {
        if(isset($array['params']) && is_array($array['params']))
        {
            $registry = new JRegistry();
            $registry->loadArray($array['params']);
            $array['params'] = (string) $registry;
        }

        return parent::bind($array, $ignore);
    }

    function check()
    {
        // check for valid name
        if(trim($this->title) == '')
        {
            $this->setError(JText::_('Your Idea must contain a title.'));

            return false;
        }

        // check for valid lag
        $this->lag = floatval($this->lag * 60);
        if($this->lag == 0)
        {
            $this->setError(JText::_('Your Idea must have a non-zero lag time.'));

            return false;
        }

        if(empty($this->alias))
        {
            $this->alias = $this->title;

            if(JFactory::getConfig()->get('unicodeslugs') == 1)
            {
                $this->alias = JFilterOutput::stringURLUnicodeSlug($this->title);
            }
            else
            {
                $this->alias = JFilterOutput::stringURLSafe($this->title);
            }

            if(empty($this->alias) || trim(str_replace('-', '', $this->alias)) == '')
            {
                $this->alias = 'idea';
            }
        }

        return true;
    }

    // overloaded delete function
    function delete($oid = null)
    {
        $k = $this->_tbl_key;
        if($oid)
        {
            $this->$k = intval($oid);
        }

        if(parent::delete($oid))
        {
            $db = JFactory::getDBO();

            $db->setQuery("DELETE FROM #__mir_options WHERE idea_id = " . (int) $oid);
            if(!$db->query())
            {
                $this->_error .= $db->getErrorMsg() . "\n";
            }

            $db->setQuery("DELETE FROM #__mir_votes WHERE idea_id = " . (int) $oid);
            if(!$db->query())
            {
                $this->_error .= $db->getErrorMsg() . "\n";
            }

            return true;
        }

        return false;
    }

    // function to get the options for current idea
    function getOptions($idea_id)
    {
        $query = "SELECT o.*, COUNT(v.id) AS hits"
            . " FROM #__mir_options AS o"
            . " LEFT JOIN #__mir_votes AS v"
            . " ON (o.id = v.option_id AND v.idea_id = " . (int) $idea_id . ")"
            . " WHERE o.idea_id = " . (int) $idea_id
            . " AND text <> '' GROUP BY o.id ORDER BY o.ordering";

        $this->_db->setQuery($query);

        return $this->_db->loadObjectList();
    }
}