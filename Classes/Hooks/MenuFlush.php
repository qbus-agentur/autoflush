<?php

namespace Qbus\Autoflush\Hooks;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * MenuFlush – flush menu_pid_###PID### when navigation related page-field's change
 *
 * The cache tag menu_pid_###PID### needs to be added to each page rendering menu's.
 * That task is currently done by \Qbus\Autoflush\Hooks\Frontend\RegisterMenuTags.
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class MenuFlush
{
    /**
     * @var array
     */
    protected $tags = [];

    /**
     * @param  string      $table
     * @param  int|string  $id
     * @param  array       $fields
     */
    protected function onUpdate(string $table, $id, array $fields)
    {
        /* TODO: Move this list to ext_conf_template.txt */
        $fieldsToCheck = 'title, hidden, nav_title, nav_hide, doktype, alias, target, url_scheme, shortcut, shortcut_mode, sorting, fe_group, starttime, endtime, tx_realurl_pathsegment';
        $fieldsToCheck = GeneralUtility::trimExplode(',', $fieldsToCheck, true);

        $changed = false;
        foreach ($fieldsToCheck as $field) {
            if (isset($fields[$field])) {
                $changed = true;
                break;
            }
        }

        if ($changed) {
            $this->tags[] = 'menu_pid_' . (int)($this->getParentPage($table, $id, $fields));
        }

        if (isset($fields['extend_to_subpages'])) {
            // TODO: generate recursive tree list and add cache tags for all of them.
        }
    }

    /**
     * @param  string      $table
     * @param  int|string  $id
     * @param  array       $fields
     */
    protected function onAdd(string $table, $id, array $fields)
    {
        if (isset($fields['hidden']) && $fields['hidden']) {
            return;
        }

        if (isset($fields['nav_hide']) && $fields['nav_hide']) {
            return;
        }

        if (isset($fields['pid'])) {
            $this->tags[] = 'menu_pid_' . (int)($this->getParentPage($table, $id, $fields));
        }
    }

    /**
     * @param  string  $table
     * @param  int     $id
     * @param  array   $record
     */
    protected function onDelete(string $table, $id, array $record)
    {
        if (isset($record['hidden']) && $record['hidden']) {
            return;
        }

        if (isset($record['nav_hide']) && $record['nav_hide']) {
            return;
        }

        if (isset($record['pid'])) {
            $cacheManager = $this->getCacheManager();
            $cacheManager->flushCachesInGroupByTag('pages', 'menu_pid_' . (int)($this->getParentPage($table, $id, $record)));
        }
    }

    /**
     * Hook executed after the DataHandler performed one database operation
     *
     * @param  string      $status
     * @param  string      $table
     * @param  int|string  $id
     * @param  array       $fields
     * @param  DataHandler $dataHandler
     */
    public function processDatamap_afterDatabaseOperations(
        $status,
        $table,
        $id,
        &$fields,
        DataHandler $dataHandler
    ) {
        if (!in_array($table, ['pages', 'pages_language_overlay'])) {
            return;
        }

        switch ($status) {
            case 'update':
                $this->onUpdate($table, $id, $fields);
                break;
            case 'new':
                $this->onAdd($table, $id, $fields);
                break;
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
        array $record,
        &$recordWasDeleted,
        DataHandler $dataHandler
    ) {
        if (!in_array($table, ['pages', 'pages_language_overlay'])) {
            return;
        }

        $this->onDelete($table, $id, $record);
    }

    /**
     * @param  string      $command
     * @param  string      $table
     * @param  int         $id
     * @param  string      $value
     * @param  DataHandler $dataHandler
     * @param  array       $pasteUpdate
     */
    public function processCmdmap_preProcess(
        $command,
        $table,
        $id,
        $value,
        DataHandler $dataHandler,
        $pasteUpdate
    ) {
        if ($table !== 'pages') {
            return;
        }

        switch ($command) {
            case 'move':
                /* Flush the current pid */
                $page = BackendUtility::getRecord('pages', $id, 'pid');
                if ($page) {
                    $this->tags[] = 'menu_pid_' . (int)($page['pid']);
                }

                /* Flush the dest pid.
                 * $value <0   move behind record with uid=abs($value)
                 * $value >=0  move to page with uid=$value */
                if ($value < 0) {
                    $page = BackendUtility::getRecord('pages', -$value, 'pid');
                    if ($page) {
                        $this->tags[] = 'menu_pid_' . (int)($page['pid']);
                    }
                } else {
                    $this->tags[] = 'menu_pid_' . (int)$value;
                }
                break;
        }
    }

    /*
     * processCmdmap_afterFinish
     *
     * @param DataHandler $dataHandler
     */
    public function processCmdmap_afterFinish(DataHandler $dataHandler)
    {
        $this->flushTags();
    }

    /**
     * processDatamap_afterAllOperations
     *
     * @param DataHandler $dataHandler
     */
    public function processDatamap_afterAllOperations(DataHandler $dataHandler)
    {
        $this->flushTags();
    }

    /**
     * Flush the tags in $this->tags
     */
    protected function flushTags()
    {
        if (empty($this->tags)) {
            return;
        }

        $cacheManager = $this->getCacheManager();

        $this->tags = array_unique($this->tags);
        foreach ($this->tags as $tag) {
            $cacheManager->flushCachesInGroupByTag('pages', $tag);
        }

        $this->tags = [];
    }

    /**
     * Get parent page for $table which may be 'pages' or 'pages_language_overlay'.
     *
     * For 'pages_language_overlay' technically the grandparent page is retrieved.
     * $record is optional, providing it may save a SQL Query
     *
     * @param  string   $table
     * @param  int|string      $id
     * @param  array    $record
     * @return int|null
     */
    protected function getParentPage($table, $id, $record = [])
    {
        if (!isset($record['pid'])) {
            $record = BackendUtility::getRecord($table, (int)$id, 'pid');
            if (!$record) {
                /* FIXME: what to do? */
                return null;
            }
        }

        if ($table === 'pages') {
            return $record['pid'];
        }

        if ($table === 'pages_language_overlay') {
            $page = BackendUtility::getRecord('pages', $record['pid'], 'pid');
            if ($page) {
                return $page['pid'];
            }
        }

        /* FIXME: what to do? */
        return null;
    }

    /**
     * @return CacheManager ;
     */
    protected function getCacheManager()
    {
        return GeneralUtility::makeInstance(CacheManager::class);
    }
}
