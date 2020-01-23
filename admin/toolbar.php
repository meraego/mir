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

$controller = JFactory::getApplication()->input->get('controller', 'idei');

JHTML::_('behavior.switcher');

$controllers = array(
    'idei' => JText::_('COM_MIR_ideaS'),
    'votes' => JText::_('COM_MIR_VOTES')
);

foreach ($controllers as $key => $val)
{
    $active = ($controller == $key);
    JSubMenuHelper::addEntry($val, 'index.php?option=com_mir&controller=' . $key, $active);
}