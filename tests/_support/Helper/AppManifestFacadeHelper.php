<?php

namespace SprykerSdkTest\Helper;

use Codeception\Module;

class AppManifestFacadeHelper extends Module
{
    use AcpHelperTrait;

    /**
     * @return void
     */
    public function haveInvalidJsonStructure(): void
    {
        $structure = [
            'config' => [
                'app' => [
                    'translation.json' => file_get_contents(codecept_data_dir('invalid/translation/invalid.json')),
                    'configuration.json' => file_get_contents(codecept_data_dir('invalid/configuration/configuration.json')),
                    'manifest' => [
                        'en_US.json' => file_get_contents(codecept_data_dir('invalid/manifest/invalid.json')),
                    ],
                ],
            ],
        ];

        $this->getAcpHelper()->mockDirectoryStructure($structure);
    }
}
