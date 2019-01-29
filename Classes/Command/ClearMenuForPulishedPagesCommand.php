<?php
namespace Qbus\Autoflush\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * ClearMenuForPulishedPagesCommand
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ClearMenuForPulishedPagesCommand extends Command
{
    const REGISTRY_KEY = 'cachecommand_publish_pages_last_run';

    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param string       $commandName
     * @param CacheManager $cacheManager
     * @param Registry     $registry
     */
    public function __construct(string $name = null, CacheManager $cacheManager = null, Registry $registry = null)
    {
        $this->cacheManager = $cacheManager ?? GeneralUtility::makeInstance(CacheManager::class);
        $this->registry = $registry ?? GeneralUtility::makeInstance(Registry::class);
        parent::__construct($name);
    }

    /**
     * Defines the description for this command
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Flushes menu cache for pages that were automatically published');
    }

    /**
     * Flush menu cache for pages that were automatically published
     * between two runs of this command
     *
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
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
     *
     * @param string|int $a
     * @param string|int $b
     */
    protected function findPagesPublishedBetween($a, $b)
    {
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
