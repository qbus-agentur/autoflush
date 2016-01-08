<?php
namespace Qbus\Autoflush\Command;

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * AutoflushCommandController
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AutoflushCommandController extends \TYPO3\CMS\Extbase\Mvc\Controller\CommandController
{
    /**
     * @var \TYPO3\CMS\Core\Cache\CacheManager
     */
    protected $cacheManager;

    /**
     * @param  \TYPO3\CMS\Core\Cache\CacheManager $cacheManager
     * @return void
     */
    public function injectCacheManager(\TYPO3\CMS\Core\Cache\CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Flush menu cache for pages that were automatically published
     * between two runs of this command
     *
     * @return void
     */
    public function clearMenuForPulishedPagesCommand()
    {
        $hash = md5('autoflush_cachecommand_publish_pages_last_run');
        $current = time();

        $last = BackendUtility::getHash($hash);
        if (!$last) {
            // We've got a StoreLastFlush hook, which should prevent this situation.
            // But this may still happen, when the admin cleared all caches in install tool
            // (were we can't hook into)
            BackendUtility::storeHash($hash, $current, 'AUTOFLUSH_LAST_RUN');

            return;
        }

        $pages = $this->findPagesPublishedBetween($last, $current);
        $pids = array();
        if ($pages) {
            foreach ($pages as $page) {
                $pids[$page['pid']] = $page['pid'];
            }
        }

        foreach ($pids as $pid) {
            $this->cacheManager->flushCachesInGroupByTag('pages', 'menu_pid_' . $pid);
        }

        BackendUtility::storeHash($hash, $current, 'AUTOFLUSH_LAST_RUN');
    }

    /**
     * Find publish changes in range
     */
    protected function findPagesPublishedBetween($a, $b)
    {
        $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'pid, uid',
            'pages',
            '(' . $a . ' < starttime and ' . $b . ' >= starttime) or ' .
            '(' . $a . ' < endtime   and ' . $b . ' >= endtime )'
        );

        if ($rows) {
            return $rows;
        }

        return $r;
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
