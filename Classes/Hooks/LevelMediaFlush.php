<?php

namespace Qbus\Autoflush\Hooks;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
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

        $cacheManager = $this->getCacheManager();

        $this->pageIdsToFlushRecursively = array_unique($this->pageIdsToFlushRecursively);
        foreach ($this->pageIdsToFlushRecursively as $pageId) {
            $begin = 0;
            $depth = 1000;
            $perms_clause = '1';
            $treeList = $this->getTreeList($pageId, $depth, $begin, $perms_clause);

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
     * Recursively fetch all descendants of a given page
     *
     * @return string comma separated list of descendant pages
     */
    protected function getTreeList(int $id, int $depth, int $begin = 0, string $permsClause = ''): string
    {
        if ($id < 0) {
            $id = abs($id);
        }
        if ($begin === 0) {
            $theList = (string)$id;
        } else {
            $theList = '';
        }
        if ($id && $depth > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $statement = $queryBuilder->select('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('sys_language_uid', 0)
                )
                ->orderBy('uid');
            if ($permsClause !== '') {
                $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($permsClause));
            }
            $statement = $queryBuilder->executeQuery();
            while ($row = $statement->fetchAssociative()) {
                if ($begin <= 0) {
                    $theList .= ',' . $row['uid'];
                }
                if ($depth > 1) {
                    $theSubList = $this->getTreeList($row['uid'], $depth - 1, $begin - 1, $permsClause);
                    if (!empty($theList) && !empty($theSubList) && ($theSubList[0] !== ',')) {
                        $theList .= ',';
                    }
                    $theList .= $theSubList;
                }
            }
        }

        return $theList;
    }

    /**
     * @return CacheManager ;
     */
    protected function getCacheManager()
    {
        return GeneralUtility::makeInstance(CacheManager::class);
    }
}
