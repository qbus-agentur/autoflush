<?php
namespace Qbus\Autoflush\Hooks;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * ResourceStorageHook
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ResourceStorageHook
{
    /**
     * @return void
     */
    public function flushAll()
    {
        $cacheManager = $this->getCacheManager();

        $cacheManager->flushCachesInGroup('pages');
    }

    /**
     * @return \TYPO3\CMS\Core\Cache\CacheManager
     */
    protected function getCacheManager()
    {
        return GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
    }
}
