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

$component  = JComponentHelper::getComponent('com_mir');
$app        = JFactory::getApplication();
$menus      = $app->getMenu('site');
$menu_items = $menus->getItems('component_id', $component->id);
$doc        = JFactory::getDocument();
$param      = $this->params;

?>

<?php if($param->get('show_title', 1)) : ?>
    <h1 class="head"><?php echo $param->get('page_title'); ?></h1>
<?php endif; ?>

<?php if(!$this->allowToVote && $param->get('show_component_msg')) : ?>
    <div class="alert alert-<?php echo($this->msgdone == 1 ? 'success' : 'danger'); ?>" role="alert"
         id="idea_comp_form">
        <?php echo JText::_($this->msg); ?>
    </div>
<?php endif; ?>

<?php if($param['cover'] || $param['description']): ?>
    <div class="row row-fluid">
        <?php if($param['cover'] != ''): ?>
            <div class="<?php echo($param['description'] ? 'col-xs-6 span6' : 'col-xs-12 span12'); ?>">
                <figure class="thumbnail">
                    <img src="<?php echo $param['cover']; ?>" alt="<?php echo $this->title; ?>"/>
                </figure>
            </div>
        <?php endif; ?>

        <?php if($param['description'] != ''): ?>
            <div class="<?php echo($param['cover'] ? 'col-xs-6 span6' : 'col-xs-12 span12'); ?>">
                <p>
                    <?php
                    $desc = str_replace("\r\n", '</p><p>', $param['description']);
                    echo trim($desc);
                    ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

    <div class="container-fluid panel panel-default panel-flat">
    <div class="panel-body">

        <div class="row">
            <?php if($this->allowToVote) : ?>
                <div class="col-md-6 span6" id="idea_comp_form">
                    <form action="<?php echo JRoute::_('index.php'); ?>" method="post" name="idea_form2" role="form">
                        <?php
                        $i = 0;
                        foreach ($this->options as $idea_option) : ?>
                            <div>
                                <label for="voteid<?php echo $idea_option->id; ?>" class="idea">
                                    <input type="radio" name="voteid" id="voteid<?php echo $idea_option->id; ?>"
                                           value="<?php echo $idea_option->id; ?>"<?php echo($i == 0 ? ' required' : ''); ?> />
                                    <?php
                                    $idea = explode("===", $idea_option->text);
                                    echo strip_tags(html_entity_decode(trim($idea[0])));
                                    ?>
                                </label>
                            </div>
                            <?php
                            $i++;
                        endforeach;
                        ?>

                        <div class="form-group" style="padding-top: 18px;">
                            <input type="submit" name="task_button" class="btn btn-primary"
                                   value="<?php echo JText::_('COM_MIR_VOTE'); ?>"/>
                        </div>

                        <input type="hidden" name="option" value="com_mir"/>
                        <input type="hidden" name="task" value="vote"/>
                        <input type="hidden" name="id" value="<?php echo $this->idea->id; ?>"/>
                        <?php echo JHTML::_('form.token'); ?>
                    </form>
                </div>
            <?php endif; ?>

            <?php if($param->get('show_voters') || $param->get('show_times')) : ?>
            <?php if(!$this->allowToVote) : ?>
            <div class="col-md-12 span12">
                <div class="row">
                    <div class="col-md-6 span6">
                        <?php else: ?>
                        <div class="col-md-6 span6">
                            <?php endif; ?>
                            <dl class="dl-horizontal">
                                <dt>
                                    <?php echo JText::_('COM_MIR_NUM_OF_VOTERS'); ?>:
                                </dt>
                                <dd>
                                    <strong class="text-primary"><?php if(isset($this->options[0])) echo $this->options[0]->voters; ?></strong>
                                </dd>
                                <?php if($param->get('show_times')): ?>
                                    <dt>
                                        <?php echo JText::_('COM_MIR_START'); ?>:
                                    </dt>
                                    <dd class="text-muted"><?php echo JHtml::date($this->idea->publish_up, JText::_('DATE_FORMAT_LC')); ?></dd>
                                    <dt>
                                        <?php echo JText::_('COM_MIR_END'); ?>:
                                    </dt>
                                    <dd class="text-muted">
                                        <?php echo JHtml::date($this->idea->publish_down, JText::_('DATE_FORMAT_LC')); ?>
                                    </dd>
                                <?php endif; ?>
                            </dl>
                            <?php if(!$this->allowToVote) : ?>
                        </div>
                        <div class="col-md-6 span6">
                            <?php endif; ?>
                            <a class="btn btn-success pull-right"
                               href="<?php echo JRoute::_('index.php?option=com_mir&view=idea&Itemid=' . $menu_items[0]->id); ?>">
                                <i class="icon-list fa fa-chart-bar"></i> <?php echo JText::_('COM_MIR_ideaS'); ?>
                            </a>
                            <?php if(!$this->allowToVote) : ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <hr>

<?php if($component->params->get('show_dropdown') == 1 && $param->get('show_dropdown') == 1) : ?>
    <div class="<?php echo $param->get('pageclass_sfx') ?>">
        <form action="<?php echo JRoute::_('index.php'); ?>" method="post" name="idea" id="idea">
            <label for="id">
                <?php echo JText::_('COM_MIR_VIEW_RESULTS'); ?>
                <?php echo $this->lists['idei']; ?>
            </label>
        </form>
    </div>
<?php endif; ?>

<?php
if($component->params->get('show_what') == 1)
{
    if($param->get('show_what', '0') == '1')
    {
        echo $this->loadTemplate('pie');
    }
    else
    {
        echo $this->loadTemplate('chart');
    }
}
else
{
    echo $this->loadTemplate('chart');
}
?>

<?php if(!JRequest::getVar('print')): ?>
    <div id="jursssocial" class="jursssocial-widget"></div>
<?php endif; ?>

<?php
if($component->params->get('show_comments') == 1 && $param->get('show_comments', '0') == 1)
{
    $jcomments = JPATH_SITE . '/components/com_jcomments/jcomments.php';
    if(file_exists($jcomments))
    {
        require_once($jcomments);
        echo JComments::showComments($this->idea->id, 'com_mir', $this->idea->title);
    }
}