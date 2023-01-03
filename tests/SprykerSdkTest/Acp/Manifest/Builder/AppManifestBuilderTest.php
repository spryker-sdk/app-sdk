<?php

/**
 * Copyright © 2019-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerSdkTest\Acp\Manifest\Builder;

use Codeception\Test\Unit;
use SprykerSdk\Acp\Manifest\Builder\AppManifestBuilder;
use SprykerSdkTest\Acp\Tester;

/**
 * @group SprykerSdk
 * @group Acp
 * @group Reader
 * @group AppManifestReaderTest
 */
class AppManifestBuilderTest extends Unit
{
    /**
     * @var \SprykerSdkTest\Acp\Tester
     */
    protected Tester $tester;

    /**
     * @return void
     */
    public function testShouldCreateManifestWithTheExampleDataSuccessfully()
    {
        // Arrange
        $manifestRequestTransfer = $this->tester->haveManifestCreateRequest();

        // Act
        $appManifestBuilder = new AppManifestBuilder();
        $appManifestBuilder->createManifest($manifestRequestTransfer);

        // Assert
        $this->assertJsonStringEqualsJsonFile(
            $manifestRequestTransfer->getManifestPath() . $manifestRequestTransfer->getManifest()->getLocaleName() . '.json',
            json_encode([
                'name' => $manifestRequestTransfer->getManifest()->getName(),
                'provider' => $manifestRequestTransfer->getManifest()->getName(),
                'description' => 'A short description to be shown below the app name with a promotional text',
                'descriptionShort' => 'A long description explaining what the app does or talking about your business.',
                'url' => 'https://url-not-visible-in-app-catalog-to-your-app-homepage.com',
                'isAvailable' => true,
                'business_models' => [
                    '** CHOOSE ONE OR MORE BELOW **',
                    'B2B',
                    'B2C',
                    'B2C_MARKETPLACE',
                    'B2B_MARKETPLACE',
                ],
                'categories' => [
                    '** CHOOSE ONE OR MORE BELOW **',
                    'BI_ANALYTICS',
                    'CUSTOMER',
                    'LOYALTY',
                    'PAYMENT',
                    'PRODUCT_INFORMATION_SYSTEM',
                    'SEARCH',
                    'USER_GENERATED_CONTENT',
                ],
                'pages' => [
                    'Overview' => [
                        'MoreContent_ThisIsOptional' => [
                            'title' => 'A text section with more content about the app',
                            'type' => 'text',
                            'data' => 'Free text with more content about the app. It will be shown below the long description section.',
                        ],
                        'Advantages_ThisIsOptional' => [
                            'title' => 'You can also create a item list section',
                            'type' => 'list',
                            'data' => [
                                'Your app advantage #1',
                                'Your app advantage #2',
                                'Your app advantage #3',
                            ],
                        ],
                        'AsMuchAsYouNeed_ThisIsOptional' => [
                            'title' => 'The title of another text section',
                            'type' => 'text',
                            'data' => 'You can add as much content as you need. It can also be a list, just change the \'type\' property and use an array here as shown above.',
                        ],
                    ],
                    'Legal' => [
                        'LegalText_ThisIsOptional' => [
                            'title' => 'A text with legal content',
                            'type' => 'text',
                            'data' => 'Free text with legal content.',
                        ],
                        'AsMuchAsYouNeedToo_ThisIsOptional' => [
                            'title' => 'The title of another section',
                            'type' => 'text',
                            'data' => 'You can add as much content as you need. It can also be a list, just change the \'type\' property and use an array here as shown above.',
                        ],
                    ],
                ],
                'assets' => [
                    [
                        'type' => 'icon',
                        'url' => '/assets/images/app_name/logo.svg',
                    ],
                    [
                        'type' => 'image',
                        'url' => '/assets/images/app_name/gallery/app_picture_1.jpeg',
                    ],
                    [
                        'type' => 'image',
                        'url' => '/assets/images/app_name/gallery/app_picture_1.png',
                    ],
                    [
                        'type' => 'video',
                        'url' => 'https://wistia.com/only-support-wistia-videos',
                    ],
                ],
                'label' => [
                    '** CHOOSE ONE OR MORE BELOW **',
                    'Silver Partner',
                    'Gold Partner',
                    'New',
                    'Popular',
                    'Free Trial',
                ],
                'resources' => [
                    [
                        'title' => 'Homepage',
                        'url' => 'https://url-to-app-homepage.com',
                        'type' => 'homepage',
                    ],
                    [
                        'title' => 'The \'type\' property changes the resource icon on AppCatalog',
                        'url' => 'https://url-to-app-homepage.com/user-documentation',
                        'type' => 'user-documentation',
                    ],
                    [
                        'title' => 'A PDF file (the optional \'fileType\' property makes it open inside AppCatalog without redirect)',
                        'url' => 'https://url-to-app-homepage.com/its-possible-to-use-pdf-files.pdf',
                        'type' => 'developer-documentation',
                        'fileType' => 'pdf',
                    ],
                    [
                        'title' => 'A Markdown file (the optional \'fileType\' property makes it open inside AppCatalog without redirect)',
                        'url' => 'https://url-to-app-homepage.com/its-possible-to-use-md-files.md',
                        'type' => 'release-notes',
                        'fileType' => 'markdown',
                    ],
                    [
                        'title' => 'App internal doc (MAY be a pdf or markdown with the \'fileType\' property)',
                        'url' => 'https://url-to-app-homepage.com/internal-documentation',
                        'type' => 'internal-documentation',
                        'fileType' => 'pdf',
                    ],
                ],
            ]),
        );
    }
}
