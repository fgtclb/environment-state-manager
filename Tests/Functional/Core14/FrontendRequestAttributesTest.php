<?php

declare(strict_types=1);

namespace FGTCLB\EnvironmentStateManager\Tests\Functional\Core14;

use FGTCLB\EnvironmentStateManager\EnvironmentBuilderFactoryInterface;
use FGTCLB\EnvironmentStateManager\StateBuildContext;
use FGTCLB\EnvironmentStateManager\Tests\Functional\AbstractEnvironmentStateManagerTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\RegisterStack;
use TYPO3\CMS\Frontend\Page\PageParts;
use TYPO3\CMS\Frontend\Response\ResponseData;

/**
 * TYPO3 v14 models the response data collector, the register stack and the page parts as request
 * attributes. On TYPO3 v13 these lived on the (now removed) TypoScriptFrontendController, so this
 * test set is TYPO3 v14 only and lives in the `Core14` test namespace.
 */
#[Group('not-core-13')]
final class FrontendRequestAttributesTest extends AbstractEnvironmentStateManagerTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF-8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['TYPO3_REQUEST'],
            $_SERVER['HTTP_HOST'],
            $_SERVER['REMOTE_ADDR'],
        );
        parent::tearDown();
    }

    /**
     * The frontend environment builder stands in for the `PrepareTypoScriptFrontendRendering`
     * middleware and must therefore create these request attributes. The ContentObjectRenderer used
     * within the built environment reads the register stack, so its absence would be a fatal error,
     * not merely a missing attribute.
     */
    #[Test]
    public function buildCreatesFrontendRequestAttributes(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/FrontendEnvironment/pages.csv');
        $this->writeSiteConfiguration(
            'main',
            $this->buildSiteConfiguration(1, 'https://example.com/'),
            [$this->buildDefaultLanguageConfiguration('EN', 'https://example.com/')],
        );
        $this->setUpFrontendRootPage(1, ['setup' => ['EXT:environment_state_manager/Tests/Functional/Fixtures/FrontendEnvironment/setup.typoscript']]);

        $state = $this->get(EnvironmentBuilderFactoryInterface::class)
            ->create($stateBuildContext = new StateBuildContext(applicationType: ApplicationType::FRONTEND, pageId: 1))
            ->build($stateBuildContext);

        $request = $state->request();
        $this->assertNotNull($request);
        $registerStack = $request->getAttribute('frontend.register.stack');
        $this->assertInstanceOf(ResponseData::class, $request->getAttribute('frontend.response.data'));
        $this->assertInstanceOf(RegisterStack::class, $registerStack);
        $this->assertInstanceOf(PageParts::class, $request->getAttribute('frontend.page.parts'));

        // Exercise the register stack through a ContentObjectRenderer, the way a consumer would. This
        // is the path that fails with a TypeError when `frontend.register.stack` is missing.
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $contentObjectRenderer->start([], 'pages');
        $registerStack->current()->set('ergebnis', '42');
        $this->assertSame('42', $contentObjectRenderer->getData('register:ergebnis'));
    }
}
