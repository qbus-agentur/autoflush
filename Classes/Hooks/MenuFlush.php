<?php
namespace Qbus\Qbcache\Hooks;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * MenuFlush – flush menu_pid_###PID### when navigation related page-field's change
 *
 * The cache tag menu_pid_###PID### needs to be added to each page rendering menu's.
 * Therefore a helper exists which you can add to your typoscript setup using IProcFunc.
 *
 * Example:
 *   lib.navigation = HMENU
 *   lib.navigation {
 *     special = directory
 *     special.value = 1
 *
 *     1 = TMENU
 *     1 {
 *       wrap = <ul class="nav">|</ul>
 *       expAll = 1
 *
 *       # This will add a page cache tag for every rendered (sub)menu:
 *       # menu_pid_###PID### which is flushed by Qbus\Qbcache\Hooks\MenuFlush
 *       # if a child page of that pid is added/changed/removed
 *       IProcFunc = Qbus\Qbcache\Helper\RegisterMenu->register
 *     }
 *
 *     2 < .1
 *   }
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @package qbcache
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class MenuFlush
{
    /**
     * @var array
     */
    protected $tags = array();

    /**
     * Hook executed after the DataHandler performed one database operation
     *
     * @param  string      $status
     * @param  string      $table
     * @param  int|string  $id
     * @param  array       $fields
     * @param  DataHandler $dataHandler
     * @return void
     */
    public function processDatamap_afterDatabaseOperations(
        $status,
        $table,
        $id,
        &$fields,
        DataHandler $dataHandler
    ) {
        if ($status === 'update' && $table === 'pages') {

            /* TODO: Move this list to ext_conf_template.txt */
            $fieldsToCheck = 'title, hidden, nav_title, nav_hide, fe_group, starttime, endtime';
            $fieldsToCheck = GeneralUtility::trimExplode(',', $fieldsToCheck, true);

            $changed = false;
            foreach ($fieldsToCheck as $field) {
                if (isset($fields[$field])) {
                    $changed = true;
                    break;
                }
            }

            if ($changed) {
                $page = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
                    'pid',
                    'pages',
                    'uid = ' . intval($id) . BackendUtility::deleteClause('pages')
                );

                if ($page) {
                    $this->tags[] = 'menu_pid_' . intval($page['pid']);
                }
            }

            if (isset($fields['extend_to_subpages'])) {
                // TODO: generate recursive tree list and add cache tags for all of them.
            }
        }

        if ($status === 'new') {
            if (isset($fields['hidden']) && $fields['hidden']) {
                return;
            }

            if (isset($fields['nav_hide']) && $fields['nav_hide']) {
                return;
            }

            if (isset($fields['pid'])) {
                $this->tags[] = 'menu_pid_' . intval($fields['pid']);
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
        if (empty($this->tags)) {
            return;
        }

        $cacheManager = $this->getCacheManager();

        $this->tags = array_unique($this->tags);
        foreach ($this->tags as $tag) {
            $cacheManager->flushCachesInGroupByTag('pages', $tag);
        }
    }

    /*
     * @param string      $table
     * @param int         $id
     * @param array       $record
     * @param bool        $recordWasDeleted
     * @param DataHandler $dataHandler
     */
    public function processCmdmap_deleteAction(
        $table,
        $id,
        $record,
        $recordWasDeleted,
        DataHandler $dataHandler
    ) {
        if ($table === 'pages' && $recordWasDeleted) {
            if (isset($record['hidden']) && $record['hidden']) {
                return;
            }

            if (isset($record['nav_hide']) && $record['nav_hide']) {
                return;
            }

            if (isset($record['pid'])) {
                $cacheManager = $this->getCacheManager();
                $cacheManager->flushCachesInGroupByTag('pages', 'menu_pid_' .  intval($record['pid']));
            }
        }
    }

    /**
     * @return \TYPO3\CMS\Core\Database\QueryGenerator
     */
    protected function createQueryGenerator()
    {
        return GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\QueryGenerator::class);
    }

    /**
     * @return \TYPO3\CMS\Core\Cache\CacheManager;
     */
    protected function getCacheManager()
    {
        return GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
