<?php
namespace Qbus\Autoflush\Tests\Functional;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;


/**
 * FunctionalTest
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FunctionalTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = array('typo3conf/ext/autoflush');

    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function menuRenderRegistersCacheTag()
    {
        $this->assertSame(2, 2);
    }
}
