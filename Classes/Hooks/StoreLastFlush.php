<?php
namespace Qbus\Autoflush\Hooks;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * StoreLastFlush
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class StoreLastFlush
{
    /**
     * Clear cache post processor.
     *
     * @param array       $params
     * @param DataHandler $pObj
     *
     * @return void
     */
    public function clearCachePostProc(array $params, DataHandler $dataHandler)
    {
        $hash = md5('autoflush_cachecommand_publish_pages_last_run');
        if (BackendUtility::getHash($hash) === null) {
            BackendUtility::storeHash($hash, time(), 'AUTOFLUSH_LAST_RUN');
        }
    }
}
