<?php

 /*
 plugins/generic/copyrightSearchPage/copyrightSearchPagePlugin.inc.php
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class CopyrightSearchPagePlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        // If the system isn't installed, or is performing an upgrade, don't
        // register hooks. This will prevent DB access attempts before the
        // schema is installed.
        if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) {
            return true;
        }

        if ($success) {
            if ($this->getEnabled($mainContextId)) {
                if ($this->getEnabled()) {
                    $this->addLocaleData();

                    // register locale files for reviews grid controller classes
                    $locale = AppLocale::getLocale();
                    AppLocale::registerLocaleFile($locale, 'plugins/generic/copyrightSearchPage/locale/'.$locale.'/locale.po');

                    // register hooks
                    HookRegistry::register('LoadHandler', [$this, 'loadCopyrightSearchPageHandler']);
                    HookRegistry::register('TemplateResource::getFilename', [$this, 'getTemplateFilePath']);
                }
            }

            return $success;
        }

        return $success;
    }

    public function loadCopyrightSearchPageHandler($hookname, $params)
    {
        $page = $params[0];
        $op = &$params[1];
        $sourceFile = &$params[2];

        switch ($page) {
            case 'copyrightSearch':
                switch ($op) {
                    case 'index':
                        define('HANDLER_CLASS', 'CopyrightSearchPageHandler');
                        $this->import('CopyrightSearchPageHandler');
                        break;
                }

                return true;
            }

        return false;
    }

    public function getDisplayName()
    {
        return __('plugins.generic.copyrightSearchPage.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.copyrightSearchPage.description');
    }

    public function getTemplateFilePath($hookname, $args)
    {
        switch ($args[1]) {
        case 'copyrightSearch.tpl':
            $args[0] = 'plugins/generic/copyrightSearchPage/templates/copyrightSearch.tpl';

            return false;
        }
    }
}
