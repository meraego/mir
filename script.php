<?php
defined('_JEXEC') or die('Restricted access');

jimport('joomla.installer.installer');
jimport('joomla.filesystem.folder');

class com_JUIdeiInstallerScript {

    private $_is_new_installation = true;

    public function preflight($type,$parent) {
        $app = JFactory::getApplication();
        $db = JFactory::getDbo();

        if (!file_exists(JPATH_ROOT . '/images/idei')) {
            if (JFolder::create(JPATH_ROOT . '/images/idei')) {
                $app->enqueueMessage('Create folder: idei','message');
            } else {
                $app->enqueueMessage('Unable to create folder: idei','message');
            }
        }

        $query = $db->getQuery(true);
        $query->select('params');
        $query->from('#__extensions');
        $query->where($db->quoteName('element') . ' = ' . $db->quote('com_mir'));
        $query->where($db->quoteName('type') . ' = ' . $db->quote('component'));
        $db->setQuery($query);
        $config = $db->loadResult();

        if (!empty($config)) {
            $this->_is_new_installation = false;
        }
    }

    public function postflight($type,$parent) {
        $status = new JObject();
        $app = JFactory::getApplication();
        $db = JFactory::getDBO();

        if ($this->_is_new_installation == true) {
            $db->setQuery("CREATE TABLE IF NOT EXISTS `#__mir_idei` (
              `id` int(11) unsigned NOT NULL auto_increment,
              `title` varchar(255) NOT NULL default '',
              `alias` varchar(255) NOT NULL default '',
              `checked_out` int(11) NOT NULL default '0',
              `checked_out_time` datetime NOT NULL default '0000-00-00 00:00:00',
              `published` tinyint(1) NOT NULL default '0',
              `publish_up` datetime NOT NULL default '0000-00-00 00:00:00',
              `publish_down` datetime default '0000-00-00 00:00:00',
              `params` text NOT NULL,
              `access` int(11) NOT NULL default '0',
              `lag` int(11) NOT NULL default '0',
              PRIMARY KEY  (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;");
            $db->execute();

            $db->setQuery("CREATE TABLE IF NOT EXISTS `#__mir_options` (
              `id` int(11) NOT NULL auto_increment,
              `idea_id` int(11) NOT NULL default '0',
              `text` text NOT NULL,
              `link` varchar(255) DEFAULT NULL,
              `ordering` int(11) NOT NULL,
              PRIMARY KEY  (`id`),
              KEY `idea_id` (`idea_id`,`text`(1))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;");
            $db->execute();

            $db->setQuery("CREATE TABLE IF NOT EXISTS `#__mir_votes` (
              `id` bigint(20) NOT NULL auto_increment,
              `date` datetime NOT NULL default '0000-00-00 00:00:00',
              `option_id` int(11) NOT NULL default '0',
              `idea_id` int(11) NOT NULL default '0',
              `ip` int(10) unsigned NOT NULL,
              `browser` varchar(155) NOT NULL,
              `user_id` int(11) DEFAULT NULL,
              PRIMARY KEY  (`id`),
              KEY `idea_id` (`idea_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;");
            $db->execute();

            // Migration
            $nq_p = $db->getQuery(true);
            $nq_p->select('*')
                    ->from('#__mijoidei_idei');
            $db->setQuery($nq_p);
            $config = $db->loadResult();

            if (!empty($config)) {
                $db->setQuery("INSERT IGNORE `#__mir_idei` SELECT * FROM `#__mijoidei_idei`;");
                $db->execute();
            }

            $nq_o = $db->getQuery(true);
            $nq_o->select('*')
                    ->from('#__mijoidei_options');
            $db->setQuery($nq_o);
            $config = $db->loadResult();

            if (!empty($config)) {
                $db->setQuery("INSERT IGNORE `#__mir_options` SELECT * FROM `#__mijoidei_options`;");
                $db->execute();
            }

            $nq_v = $db->getQuery(true);
            $nq_v->select('*')
                    ->from('#__mijoidei_votes');
            $db->setQuery($nq_v);
            $config = $db->loadResult();

            if (!empty($config)) {
                $db->setQuery("INSERT IGNORE `#__mir_votes` SELECT * FROM `#__mijoidei_votes`;");
                $db->execute();
            }
        }

        $src = $parent->getParent()->getPath('source');

        $installer = new JInstaller();
        $installer->install($src . '/extensions/mod_mir');

        $installer = new JInstaller();
        $installer->install($src . '/extensions/plg_mirsearch');

        ob_start();
        $this->_installationOutput($status);
        $html = ob_get_contents();
        ob_end_clean();

        $version = new JVersion;
        $joomla = substr($version->getShortVersion(),0,3);

        if ($joomla < '3.4') {
            echo $html;
        } else {
            $app->enqueueMessage($html,'message');
        }

        return true;
    }

    public function uninstall($parent) {
        $status = new JObject();
        $app = JFactory::getApplication();

        $db = JFactory::getDBO();

        $q_mod = $db->getQuery(true);
        $q_mod
                ->select('extension_id')
                ->from('#__extensions')
                ->where($db->quoteName('type') . ' = ' . $db->quote('module'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('mod_mir'));
        $db->setQuery($q_mod,0,1);
        $id = $db->loadResult();

        if ($id) {
            $installer = new JInstaller();
            $installer->uninstall('module',$id);
        }

        $q_plg = $db->getQuery(true);
        $q_plg
                ->select('extension_id')
                ->from('#__extensions')
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('plg_mirsearch'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote('search'));
        $db->setQuery($q_plg,0,1);
        $id = $db->loadResult();

        if ($id) {
            $installer = new JInstaller();
            $installer->uninstall('plugin',$id);
        }

        ob_start();
        $this->_uninstallationOutput($status);
        $html = ob_get_contents();
        ob_end_clean();

        $version = new JVersion;
        $joomla = substr($version->getShortVersion(),0,3);

        if ($joomla < '3.4') {
            echo $html;
        } else {
            $app->enqueueMessage($html,'message');
        }

        return true;
    }

    protected function _installationOutput($status) {
        ?>
        <style type="text/css">
            .juinstall {
                clear: both;
                color: #333 !important;
                font-weight: normal;
                margin: 0 !important;
                padding: 0;
                background: #fff !important;
                position: absolute !important;
                top: 83px !important;
                left: 0 !important;
                overflow: hidden;
                min-width: 100%;
                max-width: 100%;
                width: 100%;
                height: 100%;
                z-index: 100 !important;
            }

            .juinstall-content {
                margin: 5% auto 8% auto !important;
                padding: 0 0 18px 0;
                width: 40%;
            }

            .juinstall hr {
                margin-top: 6px;
                margin-bottom: 6px;
                border: 0;
                border-top: 1px solid #eee
            }
        </style>
        <div class="juinstall">
            <div class="juinstall-content">
                <h2>JUIdei: <?php echo JText::_('Installed');?></h2>
                <p><a href="index.php?option=com_mir" class="btn btn-success">Go to JUIdei</a></p>
                <hr>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><?php echo JText::_('Extension');?></th>
                            <th><?php echo JText::_('Status');?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo 'JUIdei ' . JText::_('Component');?></td>
                            <td><span class="label label-success"><?php echo JText::_('Installed');?></span</td>
                        </tr>
                        <tr>
                            <td><?php echo 'JUIdei ' . JText::_('Module');?></td>
                            <td><span class="label label-success"><?php echo JText::_('Installed');?></span></td>
                        </tr>
                        <tr>
                            <td><?php echo 'Search - JUIdei';?></td>
                            <td><span class="label label-success"><?php echo JText::_('Installed');?></span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        return;
    }

    private function _uninstallationOutput($status) {
        ?>
        <style type="text/css">
            .juinstall {
                clear: both;
                color: #333 !important;
                font-weight: normal;
                margin: 0 !important;
                padding: 0;
                background: #fff !important;
                position: absolute !important;
                top: 83px !important;
                left: 0 !important;
                overflow: hidden;
                min-width: 100%;
                max-width: 100%;
                width: 100%;
                height: 100%;
                z-index: 100 !important;
            }

            .juinstall-content {
                margin: 5% auto 8% auto !important;
                padding: 0 0 18px 0;
                width: 40%;
            }

            .juinstall hr {
                margin-top: 6px;
                margin-bottom: 6px;
                border: 0;
                border-top: 1px solid #eee
            }
        </style>
        <div class="juinstall">
            <div class="juinstall-content">
                <h2>JUIdei: <?php echo JText::_('Removed');?></h2>
                <hr>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><?php echo JText::_('Extension');?></th>
                            <th><?php echo JText::_('Status');?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo JText::_('Component');?></td>
                            <td><span class="label label-success"><?php echo JText::_('Removed');?></span></td>
                        </tr>
                        <tr>
                            <td><?php echo 'JUIdei ' . JText::_('Module');?></td>
                            <td><span class="label label-success"><?php echo JText::_('Removed');?></span></td>
                        </tr>
                        <tr>
                            <td><?php echo 'Search - JUIdei';?></td>
                            <td><span class="label label-success"><?php echo JText::_('Removed');?></span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        return;
    }

    public function unlinkRecursive($dir,$deleteRootToo) {
        if (!$dh = @opendir($dir)) {
            return;
        }

        while (false !== ($obj = readdir($dh))) {
            if ($obj == '.' || $obj == '..') {
                continue;
            }

            if (!@unlink($dir . '/' . $obj)) {
                $this->unlinkRecursive($dir . '/' . $obj,true);
            }
        }
        closedir($dh);

        if ($deleteRootToo == 1) {
            @rmdir($dir);
        }

        return;
    }
}