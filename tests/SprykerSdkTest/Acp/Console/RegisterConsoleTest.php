<?php

/**
 * Copyright © 2019-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerSdkTest\Acp\Console;

use Codeception\Test\Unit;
use SprykerSdk\Acp\Console\RegisterConsole;
use SprykerSdkTest\Acp\Tester;

/**
 * @group SprykerSdkTest
 * @group Acp
 * @group Console
 * @group RegisterConsoleTest
 */
class RegisterConsoleTest extends Unit
{
    /**
     * @var \SprykerSdkTest\Acp\Tester
     */
    protected Tester $tester;

    /**
     * @return void
     */
    public function testRegisterAppReturnsSuccessfulResponseWhenAppWasRegisteredInAcp(): void
    {
        // Arrange
        $this->tester->haveValidConfigurations();
        $registerConsole = $this->tester->getRegisterConsoleWithAtrsSuccessResponse();

        // Act
        $commandTester = $this->tester->getConsoleTester($registerConsole);
        $commandTester->execute([
            '--appIdentifier' => '1234-5678-9012-3456',
            '--authorizationToken' => '1234-5678-9012-3456',
        ]);

        // Assert
        $this->assertSame(RegisterConsole::CODE_SUCCESS, $commandTester->getStatusCode());
        $this->assertSame('', $commandTester->getDisplay());
    }
}