<?php
namespace Qbus\Autoflush\Hooks;

use Qbus\Autoflush\Command\AutoflushCommandController;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;

/**
 * PostInstallHook
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PostInstallHook
{
    /**
     * Initialize the Clear cache post processor.
     *
     * @param  string         $extensionKey
     * @param  InstallUtility $installUtility
     * @return void
     */
    public function afterExtensionInstall($extensionKey, InstallUtility $installUtility)
    {
        if ($extensionKey !== 'autoflush') {
            return;
        }

        /* @var $registry Registry */
        $registry = GeneralUtility::makeInstance(Registry::class);

        if (!$registry->get('tx_autoflush', AutoflushCommandController::REGISTRY_KEY)) {
            $registry->set('tx_autoflush', AutoflushCommandController::REGISTRY_KEY, time());
        }
    }
}