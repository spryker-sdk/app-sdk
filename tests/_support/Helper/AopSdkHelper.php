<?php

/**
 * Copyright © 2019-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerSdkTest\Helper;

use Codeception\Module;
use Codeception\Stub;
use Codeception\TestInterface;
use org\bovigo\vfs\vfsStream;
use SprykerSdk\Zed\AopSdk\AopSdkConfig;
use SprykerSdk\Zed\AopSdk\Business\AopSdkFacade;
use SprykerSdk\Zed\AopSdk\Business\AopSdkFacadeInterface;

class AopSdkHelper extends Module
{
    /**
     * @var string|null
     */
    protected ?string $rootPath = null;

    /**
     * @codeCoverageIgnore
     *
     * @param \Codeception\TestInterface $test
     *
     * @return void
     */
    public function _before(TestInterface $test): void
    {
        parent::_before($test);

        // Ensure we are always using the virtual filesystem even if none of the have* methods was called.
        $this->rootPath = vfsStream::setup('root')->url();
    }

    /**
     * @codeCoverageIgnore
     *
     * @param \Codeception\TestInterface $test
     *
     * @return void
     */
    public function _after(TestInterface $test): void
    {
        $this->rootPath = null;
    }

    /**
     * @return \SprykerSdk\Zed\AopSdk\Business\AopSdkFacadeInterface
     */
    public function getFacade(): AopSdkFacadeInterface
    {
        return new AopSdkFacade();
    }

    /**
     * @return \SprykerSdk\Zed\AopSdk\AopSdkConfig
     */
    public function getConfig(): AopSdkConfig
    {
        return Stub::make(AopSdkConfig::class, [
            'getProjectRootPath' => function () {
                return $this->rootPath;
            },
        ]);
    }

    /**
     * @return string
     */
    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    /**
     * This will ensure that the AopSdkConfig::getProjectRootPath() will return the passed path.
     *
     * @param string $rootPath
     *
     * @return void
     */
    public function mockRoot(string $rootPath): void
    {
        $this->rootPath = $rootPath;
    }

    /**
     * Sets up an expected directory structure in the virtual filesystem
     *
     * @param array $structure
     *
     * @return void
     */
    public function mockDirectoryStructure(array $structure): void
    {
        // Set up the virtual filesystem structure
        $root = vfsStream::setup('root', null, $structure);
        $this->mockRoot($root->url());
    }
}
