<?php
namespace Qbus\Qbcache\Helper;

/**
 * RegisterMenu
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @package qbcache
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RegisterMenu implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * ContentObjectRenderer – injected by ContentObjectRenderer::callUserFunction
     *
     * We dont use this property, but need to define it, since
     * ContentObjctRenderer always injects this property.
     *
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    public $cObj;

    /**
     * @var array
     */
    protected $tags = array();

    /**
     * @param array $I
     * @param array $conf
     */
    public function register($I, $conf)
    {
        if (!isset($I['pid'])) {
            return $I;
        }

        $tag = 'menu_pid_' . $I['pid'];
        if (!in_array($tag, $this->tags)) {
            $GLOBALS['TSFE']->addCacheTags(array($tag));
            $this->tags[] = $tag;
        }

        return $I;
    }
}
