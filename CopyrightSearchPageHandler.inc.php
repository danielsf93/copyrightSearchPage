<?php

 /*
  plugins/generic/copyrightSearchPage/copyrightSearchPageHandler.inc.php
  */

import('lib.pkp.pages.catalog.PKPCatalogHandler');

define('PERFORMANCE_TEST', false);

class CopyrightSearchPageHandler extends PKPCatalogHandler
{
    /**
     * Show the series index page.
     *
     * @param $args array
     * @param $request PKPRequest
     */
    public function index($args, $request)
    {
        $query = $request->getUserVar('query');
        $templateMgr = TemplateManager::getManager($request);

        // Termo de pesquisa na caixa de pesquisa com htmlentities

        //linha onde evita cross site scripting
        $templateMgr->assign('searchQuery', htmlspecialchars($query));

        $this->setupTemplate($request);
        $context = $request->getContext();

        import('classes.submission.Submission'); // STATUS_ constants
        import('classes.submission.SubmissionDAO'); // ORDERBY_ constants

        $orderOption = $context->getData('copyrightSortOption') ? $context->getData('copyrightSortOption') : ORDERBY_DATE_PUBLISHED.'-'.SORT_DIRECTION_DESC;
        list($orderBy, $orderDir) = explode('-', $orderOption);

        if (PERFORMANCE_TEST) {
            $start = microtime(true);
        }

        $submissionService = Services::get('submission');
        $params = [
            'contextId' => $context->getId(),
            'orderByFeatured' => true,
            'orderBy' => $orderBy,
            'orderDirection' => $orderDir == SORT_DIRECTION_ASC ? 'ASC' : 'DESC',
            'status' => STATUS_PUBLISHED,
        ];
        $monographs = iterator_to_array($submissionService->getMany($params));

        $seriesDao = DAORegistry::getDAO('SeriesDAO'); /* @var $seriesDao SeriesDAO */

        // if pubState plugin is installed show label
        $pubStatePlugin = PluginRegistry::getPlugin('generic', 'pubstateplugin');

        foreach ($monographs as $monograph) {
            // Get the series for this monograph
            $series = $seriesDao->getById($monograph->getData('publications')[0]->getData('seriesId'), $context->getId());
            if ($series) {
                $monograph->setData('seriesPath', $series->getData('path'));
                if ($pubStatePlugin) {
                    $monograph->setData('pubStateLabel', $pubStatePlugin->getPubStateLabel($monograph));
                    $monograph->setData('pubState', $pubStatePlugin->getPubState($monograph));
                    $pubStatePlugin->loadStyleSheet($request, $templateMgr);
                }
            }
        }

        if (PERFORMANCE_TEST) {
            error_log('RS_PERF:CalatloSearch:foreach: '.print_r(microtime(true) - $start, true));
        }

        uasort($monographs, function ($a, $b) {
            // Aqui você pode adicionar sua lógica de ordenação se necessário
        });

        $templateMgr->assign([
            'monographs' => $monographs,
            'baseurl' => $request->getBaseUrl(),
        ]);

        $templateMgr->addJavaScript('copyrightSearch', $request->getBaseUrl().'/plugins/generic/copyrightSearchPage/js/copyrightSearch.js');

        $templateMgr->display('copyrightSearch.tpl');

        if (PERFORMANCE_TEST) {
            error_log('RS_PERF:CalatloSearch:display: '.print_r(microtime(true) - $start, true));
        }
    }
}
