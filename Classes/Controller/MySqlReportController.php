<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/mysqlreport.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Mysqlreport\Controller;

use StefanFroemken\Mysqlreport\Menu\Page;
use TYPO3\CMS\Backend\View\BackendTemplateView;

/**
 * Controller to show a basic analysis of MySQL variables and status
 */
class MySqlReportController extends AbstractController
{
    /**
     * @var BackendTemplateView
     */
    protected $view;

    /**
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    public function overviewAction(): void
    {
        $page = $this->pageFinder->getPageByIdentifier('overview');
        if ($page instanceof Page) {
            $this->view->assign('renderedInfoBoxes', $page->getRenderedInfoBoxes());
        }
    }

    public function queryCacheAction(): void
    {
    }

    public function innoDbBufferAction(): void
    {
    }

    public function threadCacheAction(): void
    {
    }

    public function tableCacheAction(): void
    {
    }
}
