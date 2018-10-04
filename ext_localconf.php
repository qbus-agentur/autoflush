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

$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);

$signalSlotDispatcher->connect(
    \TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class,
    'afterExtensionInstall',
    \Qbus\Autoflush\Hooks\PostInstallHook::class,
    'afterExtensionInstall'
);


$resourceSignals = [
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileAdd,
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileAdd,
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileCreate,
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileCopy,
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileCopy,
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileMove,
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileMove,
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileDelete,
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileDelete,
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileRename,
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileRename,
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileReplace,
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileReplace,
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileSetContents,
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderAdd,
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderAdd,
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderCopy,
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderCopy,
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderMove,
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderMove,
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderDelete,
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderDelete,
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderRename,
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderRename,
    //\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreGeneratePublicUrl,
];

foreach ($resourceSignals as $signal) {
    $signalSlotDispatcher->connect(
        \TYPO3\CMS\Core\Resource\ResourceStorage::class,
        $signal,
        \Qbus\Autoflush\Hooks\ResourceStorageHook::class,
        'flushAll'
    );
}

unset($signal);
unset($resourceSignals);
unset($signalSlotDispatcher);
