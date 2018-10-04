<?php
namespace Qbus\Autoflush\Command;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
    protected function findPagesPublishedBetweenLegacy($a, $b)
    {
        $rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            'pid, uid',
            'pages',
            '(' . $a . ' < starttime and ' . $b . ' >= starttime) or ' .
            '(' . $a . ' < endtime   and ' . $b . ' >= endtime )'
        );

        if ($rows) {
            return $rows;
        }

        return [];
    }

    /**
     * Find publish changes in range
     */
    protected function findPagesPublishedBetween($a, $b)
    {
        if (!class_exists(ConnectionPool::class)) {
            return $this->findPagesPublishedBetweenLegacy($a, $b);
        }
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');

        $result = $qb
            ->select('*')
            ->from('pages', 'p')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->gte('p.starttime', ':start'),
                        $qb->expr()->lt('p.starttime', ':end')
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->gte('p.endtime', ':start'),
                        $qb->expr()->lt('p.endtime', ':end')
                    )
                )
            )
            ->setParameter('start', $a)
            ->setParameter('end', $b)
            ->execute();

        return $result->fetchAll();
    }
}
