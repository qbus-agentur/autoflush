<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \Qbus\Autoflush\Hooks\LevelMediaFlush::class;

/* Collect menu tags when rendering */
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/tslib/class.tslib_menu.php']['filterMenuPages'][] =  \Qbus\Autoflush\Hooks\Frontend\RegisterMenuTags::class;

/* Flush menu tags when editing */
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \Qbus\Autoflush\Hooks\MenuFlush::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] =  \Qbus\Autoflush\Hooks\MenuFlush::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] = \Qbus\Autoflush\Hooks\TablePidFlush::class . '->clearCachePostProc';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \Qbus\Autoflush\Command\AutoflushCommandController::class;

\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class)->connect(
    \TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class,
    'afterExtensionInstall',
    \Qbus\Autoflush\Hooks\PostInstallHook::class,
    'afterExtensionInstall'
);
