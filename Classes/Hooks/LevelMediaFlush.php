<?php

namespace Qbus\Autoflush\Hooks;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * LevelMediaFlush
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class LevelMediaFlush implements SingletonInterface
{
    /**
     * @var array
     */
    protected $pageIdsToFlushRecursively = [];

    /**
     * Hook executed after the DataHandler performed one database operation
     *
     * @param  string      $status
     * @param  string      $table
     * @param  int|string  $id
     * @param  array       $fieldArray
     * @param  DataHandler $dataHandler
     */
    public function processDatamap_afterDatabaseOperations(
        $status,
        $table,
        $id,
        &$fieldArray,
        DataHandler $dataHandler
    ) {
        /* pages.media changes when a new image is added or an existing is removed */
        if ($table === 'pages' && $status === 'update' && isset($fieldArray['media'])) {
            $this->pageIdsToFlushRecursively[] = $id;
        }

        /**
         * We consider a file_reference to be changed when a tstamp is set in the fieldArray.
         *
         * Note that tstamp is also set when a media file collapsible is opened, so we may clear to much.
         * But thats better than to less.
         */
        if ($table === 'sys_file_reference' && $status === 'update' && isset($fieldArray['tstamp'])) {
            /*
            if ($status === 'new') {
                $id = $dataHandler->substNEWwithIDs[$id];
            }
            */

            $fileReference = BackendUtility::getRecord(
                'sys_file_reference',
                (int)$id,
                'uid_foreign',
                " AND tablenames = 'pages' AND fieldname = 'media' AND table_local = 'sys_file'"
            );

            if ($fileReference) {
                $this->pageIdsToFlushRecursively[] = (int)($fileReference['uid_foreign']);
            }
        }
    }

    /**
     * processDatamap_afterAllOperations
     *
     * @param DataHandler $dataHandler
     */
    public function processDatamap_afterAllOperations(DataHandler $dataHandler)
    {
        $cumulatedPageIds = [];

        if (empty($this->pageIdsToFlushRecursively)) {
            return;
        }

        $queryGenerator = $this->createQueryGenerator();
        $cacheManager = $this->getCacheManager();

        $this->pageIdsToFlushRecursively = array_unique($this->pageIdsToFlushRecursively);
        foreach ($this->pageIdsToFlushRecursively as $pageId) {
            $begin = 0;
            $depth = 1000;
            $perms_clause = '1';
            $treeList = $queryGenerator->getTreeList($pageId, $depth, $begin, $perms_clause);

            $ids = GeneralUtility::trimExplode(',', $treeList, true);
            foreach ($ids as $id) {
                $cumulatedPageIds[] = $id;
            }
        }

        $cumulatedPageIds = array_unique($cumulatedPageIds);
        foreach ($cumulatedPageIds as $pageId) {
            $cacheManager->flushCachesInGroupByTag('pages', 'pageId_' . $pageId);
        }

        /* Since this hook is called multiple times, clear the pageIds to not clear twice */
        $this->pageIdsToFlushRecursively = [];
    }

    /**
     * @return QueryGenerator
     */
    protected function createQueryGenerator()
    {
        return GeneralUtility::makeInstance(QueryGenerator::class);
    }

    /**
     * @return CacheManager ;
     */
    protected function getCacheManager()
    {
        return GeneralUtility::makeInstance(CacheManager::class);
    }
}
