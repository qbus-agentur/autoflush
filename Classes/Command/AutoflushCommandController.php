<?php
namespace Qbus\Autoflush\Command;

/**
 * AutoflushCommandController
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AutoflushCommandController extends \TYPO3\CMS\Extbase\Mvc\Controller\CommandController
{
    const REGISTRY_KEY = 'cachecommand_publish_pages_last_run';

    /**
     * @var \TYPO3\CMS\Core\Cache\CacheManager
     */
    protected $cacheManager;

    /**
     * @var \TYPO3\CMS\Core\Registry
     */
    protected $registry;

    /**
     * @param  \TYPO3\CMS\Core\Cache\CacheManager $cacheManager
     * @return void
     */
    public function injectCacheManager(\TYPO3\CMS\Core\Cache\CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * @param  \TYPO3\CMS\Core\Registry $registry
     * @return void
     */
    public function injectRegistry(\TYPO3\CMS\Core\Registry $registry)
    {
        $this->registry = $registry;
    }


    /**
     * Flush menu cache for pages that were automatically published
     * between two runs of this command
     *
     * @return void
     */
    public function clearMenuForPulishedPagesCommand()
    {
        $current = time();
        $last = $this->registry->get('tx_autoflush', self::REGISTRY_KEY, $current);

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

        $this->registry->set('tx_autoflush', self::REGISTRY_KEY, $current);
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
