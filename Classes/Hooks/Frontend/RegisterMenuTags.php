<?php

namespace Qbus\Autoflush\Hooks\Frontend;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject;
use TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuFilterPagesHookInterface;

/**
 * RegisterMenuTags
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RegisterMenuTags implements AbstractMenuFilterPagesHookInterface, SingletonInterface
{
    /**
     * @var array
     */
    protected $tags = [];

    /**
     * Register tags for rendered menu's.
     *
     * Using the processFilter since thats the only available hook.
     *
     * @param  array                     $data        Array of menu items
     * @param  array                     $banUidArray Array of page uids which are to be excluded
     * @param  bool                      $spacer      If set, then the page is a spacer.
     * @param  AbstractMenuContentObject $obj         The menu object
     * @return bool                      Returns TRUE if the page can be safely included.
     */
    public function processFilter(
        array &$data,
        array $banUidArray,
        $spacer,
        AbstractMenuContentObject $obj
    ) {
        $criterion = $this->getMenuCriterion($data);

        switch ($criterion) {
            case 'pid':
                if (!isset($data['pid'])) {
                    return true;
                }
                $tag = 'menu_pid_' . $data['pid'];
                if (!in_array($tag, $this->tags)) {
                    $GLOBALS['TSFE']->addCacheTags([$tag]);
                    $this->tags[] = $tag;
                }
                break;
        }

        /* Always return true since we do not want to influence menu rendering */
        return true;
    }

    /**
     * getMenuCriterion – Return the property that identifies the menu rendering
     *
     * @param  array  $conf
     * @return string
     */
    public function getMenuCriterion($conf)
    {
        $mapping = [
            'directory' => 'pid',
            'browse' => 'pid',
            'rootline' => 'pid',
            'list' => 'uid',
            'updated' => 'uid',
            'categories' => 'category',
            'keywords' => 'keywords',
            'language' => 'language',
        ];

        if (isset($conf['special']) && isset($mapping[$conf['special']])) {
            return $mapping[$conf['special']];
        }

        /* If no special is given, it's a pid-based rendering */
        return 'pid';
    }
}
