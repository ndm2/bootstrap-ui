<?php
declare(strict_types=1);

namespace BootstrapUI\Test\TestCase\Command;

use BootstrapUI\Command\InstallCommand;
use Cake\Command\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\Exception\StopException;
use Cake\Core\Plugin;
use Cake\Filesystem\File;
use Cake\Filesystem\Folder;
use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\Stub\ConsoleOutput;
use Cake\TestSuite\TestCase;

class InstallCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->useCommandRunner();
    }

    public function testInstall()
    {
        $pluginWebrootPath = Plugin::path('BootstrapUI') . 'webroot' . DS;
        $appWebrootPath = WWW_ROOT;
        $appWebrootPluginPath = WWW_ROOT . 'bootstrap_u_i' . DS;

        $appWebrootFolder = new Folder($appWebrootPath);
        $appWebrootFolder->delete();

        $this->assertDirectoryNotExists($appWebrootPath);

        $this->exec('bootstrap install');

        $this->assertDirectoryExists($appWebrootPath);
        $this->assertDirectoryExists($appWebrootPluginPath . 'css');
        $this->assertDirectoryExists($appWebrootPluginPath . 'js');

        $cssAssets = [
            'bootstrap.css',
            'bootstrap.min.css',
            'cover.css',
            'dashboard.css',
            'signin.css',
        ];
        $jsAssets = [
            'bootstrap.js',
            'bootstrap.min.js',
            'jquery.js',
            'jquery.min.js',
            'popper.js',
            'popper.min.js',
        ];

        foreach ($cssAssets as $asset) {
            $this->assertFileExists($pluginWebrootPath . 'css' . DS . $asset);
            $this->assertFileExists($appWebrootPluginPath . 'css' . DS . $asset);
        }
        foreach ($jsAssets as $asset) {
            $this->assertFileExists($pluginWebrootPath . 'js' . DS . $asset);
            $this->assertFileExists($appWebrootPluginPath . 'js' . DS . $asset);
        }

        $sourceFiles = (new Folder($pluginWebrootPath))->findRecursive();
        $targetFiles = (new Folder($appWebrootPluginPath))->findRecursive();
        $this->assertEquals(count($sourceFiles), count($targetFiles));
    }

    public function testReInstall()
    {
        $pluginWebrootPath = Plugin::path('BootstrapUI') . 'webroot' . DS;
        $appWebrootPath = WWW_ROOT;
        $appWebrootPluginPath = WWW_ROOT . 'bootstrap_u_i' . DS;

        $this->assertDirectoryExists($appWebrootPath);
        $this->assertDirectoryExists($appWebrootPluginPath . 'css');
        $this->assertDirectoryExists($appWebrootPluginPath . 'js');

        $cssAssets = [
            'bootstrap.css',
            'bootstrap.min.css',
            'cover.css',
            'dashboard.css',
            'signin.css',
        ];
        $jsAssets = [
            'bootstrap.js',
            'bootstrap.min.js',
            'jquery.js',
            'jquery.min.js',
            'popper.js',
            'popper.min.js',
        ];

        foreach ($cssAssets as $asset) {
            $this->assertFileExists($pluginWebrootPath . 'css' . DS . $asset);
            $this->assertFileExists($appWebrootPluginPath . 'css' . DS . $asset);
        }
        foreach ($jsAssets as $asset) {
            $this->assertFileExists($pluginWebrootPath . 'js' . DS . $asset);
            $this->assertFileExists($appWebrootPluginPath . 'js' . DS . $asset);
        }

        $this->exec('bootstrap install');

        foreach ($cssAssets as $asset) {
            $this->assertFileExists($pluginWebrootPath . 'css' . DS . $asset);
            $this->assertFileExists($appWebrootPluginPath . 'css' . DS . $asset);
        }
        foreach ($jsAssets as $asset) {
            $this->assertFileExists($pluginWebrootPath . 'js' . DS . $asset);
            $this->assertFileExists($appWebrootPluginPath . 'js' . DS . $asset);
        }

        $sourceFiles = (new Folder($pluginWebrootPath))->findRecursive();
        $targetFiles = (new Folder($appWebrootPluginPath))->findRecursive();
        $this->assertEquals(count($sourceFiles), count($targetFiles));

        $folder = new Folder(WWW_ROOT);
        $folder->delete();
    }

    public function testNPMNotAvailable()
    {
        /** @var \BootstrapUI\Command\InstallCommand|\PHPUnit\Framework\MockObject\MockObject $command */
        $command = $this
            ->getMockBuilder(InstallCommand::class)
            ->onlyMethods(['_isNPMAvailable'])
            ->getMock();

        $command
            ->expects($this->once())
            ->method('_isNPMAvailable')
            ->willReturn(false);

        $out = new ConsoleOutput();
        $err = new ConsoleOutput();
        $io = new ConsoleIo($out, $err);

        try {
            $result = $command->installPackages($io);
        } catch (StopException $exception) {
            $result = $exception->getCode();
        }

        $this->assertEquals(Command::CODE_ERROR, $result);
        $this->assertEmpty($out->messages());
        $this->assertEquals(
            ['<error>NPM (https://www.npmjs.com/) is required, but not installed. Aborting.</error>'],
            $err->messages()
        );
    }

    public function testDeleteNodeModulesFailure()
    {
        /** @var \BootstrapUI\Command\InstallCommand|\PHPUnit\Framework\MockObject\MockObject $command */
        $command = $this
            ->getMockBuilder(InstallCommand::class)
            ->onlyMethods(['_deleteNodeModules'])
            ->getMock();

        $command
            ->expects($this->once())
            ->method('_deleteNodeModules')
            ->willReturn(false);

        $out = new ConsoleOutput();
        $err = new ConsoleOutput();
        $io = new ConsoleIo($out, $err);

        try {
            $result = $command->installPackages($io);
        } catch (StopException $exception) {
            $result = $exception->getCode();
        }

        $this->assertEquals(Command::CODE_ERROR, $result);
        $this->assertEquals(
            ['<info>Clearing `node_modules` folder (this can take a while)...</info>'],
            $out->messages()
        );
        $this->assertEquals(
            ['<error>Could not clear `node_modules` folder.</error>'],
            $err->messages()
        );
    }

    public function testChangeWorkingDirectoryFailure()
    {
        /** @var \BootstrapUI\Command\InstallCommand|\PHPUnit\Framework\MockObject\MockObject $command */
        $command = $this
            ->getMockBuilder(InstallCommand::class)
            ->onlyMethods(['_deleteNodeModules', '_changeWorkingDirectory'])
            ->getMock();

        $command
            ->expects($this->once())
            ->method('_deleteNodeModules')
            ->willReturn(true);
        $command
            ->expects($this->once())
            ->method('_changeWorkingDirectory')
            ->willReturn(false);

        $out = new ConsoleOutput();
        $err = new ConsoleOutput();
        $io = new ConsoleIo($out, $err);

        try {
            $result = $command->installPackages($io);
        } catch (StopException $exception) {
            $result = $exception->getCode();
        }

        $pluginPath = Plugin::path('BootstrapUI');

        $this->assertEquals(Command::CODE_ERROR, $result);
        $this->assertEquals(
            [
                '<info>Clearing `node_modules` folder (this can take a while)...</info>',
                '<success>Cleared `node_modules` folder.</success>',
                '<info>Installing packages...</info>',
            ],
            $out->messages()
        );
        $this->assertEquals(
            ["<error>Could not change into plugin directory `$pluginPath`.</error>"],
            $err->messages()
        );
    }

    public function testNPMInstallFailure()
    {
        /** @var \BootstrapUI\Command\InstallCommand|\PHPUnit\Framework\MockObject\MockObject $command */
        $command = $this
            ->getMockBuilder(InstallCommand::class)
            ->onlyMethods(['_deleteNodeModules', '_runNPMInstall'])
            ->getMock();

        $command
            ->expects($this->once())
            ->method('_deleteNodeModules')
            ->willReturn(true);
        $command
            ->expects($this->once())
            ->method('_runNPMInstall')
            ->will($this->returnCallback(function (&$out, &$return) {
                $out = [
                    'installer output',
                ];
                $return = 1234;
            }));

        $out = new ConsoleOutput();
        $err = new ConsoleOutput();
        $io = new ConsoleIo($out, $err);

        try {
            $result = $command->installPackages($io);
        } catch (StopException $exception) {
            $result = $exception->getCode();
        }

        $this->assertEquals(1234, $result);
        $this->assertEquals(
            [
                '<info>Clearing `node_modules` folder (this can take a while)...</info>',
                '<success>Cleared `node_modules` folder.</success>',
                '<info>Installing packages...</info>',
                'installer output',
            ],
            $out->messages()
        );
        $this->assertEquals(
            ['<error>Package installation failed.</error>'],
            $err->messages()
        );
    }

    public function testDeleteBufferedPackageAssetsFailure()
    {
        /** @var \BootstrapUI\Command\InstallCommand|\PHPUnit\Framework\MockObject\MockObject $command */
        $command = $this
            ->getMockBuilder(InstallCommand::class)
            ->onlyMethods(['_findBufferedPackageAssets'])
            ->getMock();

        file_put_contents(TMP . 'style.css', '');
        file_put_contents(TMP . 'script.js', '');

        $command
            ->expects($this->once())
            ->method('_findBufferedPackageAssets')
            ->willReturn([
                new File(TMP . 'style.css'),
                new File(TMP . 'non-existent.file'),
                new File(TMP . 'script.js'),
            ]);

        $out = new ConsoleOutput();
        $err = new ConsoleOutput();
        $io = new ConsoleIo($out, $err);

        try {
            $result = $command->refreshAssetBuffer($io);
        } catch (StopException $exception) {
            $result = $exception->getCode();
        }

        $this->assertEquals(Command::CODE_ERROR, $result);
        $this->assertEquals(
            [
                '<info>Refreshing package asset buffer...</info>',
                '<success>`style.css` successfully deleted.</success>',
                '<success>`script.js` successfully deleted.</success>',
            ],
            $out->messages()
        );
        $this->assertEquals(
            [
                '<warning>`non-existent.file` could not be deleted.</warning>',
                '<error>Could not clear all buffered files.</error>',
            ],
            $err->messages()
        );
    }

    public function testBufferPackageAssetsSkipUnsupportedFileTypes()
    {
        /** @var \BootstrapUI\Command\InstallCommand|\PHPUnit\Framework\MockObject\MockObject $command */
        $command = $this
            ->getMockBuilder(InstallCommand::class)
            ->onlyMethods(['_deleteBufferedPackageAssets', '_findPackageAssets'])
            ->getMock();

        $command
            ->expects($this->once())
            ->method('_deleteBufferedPackageAssets')
            ->willReturn(true);

        file_put_contents(TMP . 'style.css', '');
        file_put_contents(TMP . 'script.js', '');

        $command
            ->expects($this->once())
            ->method('_findPackageAssets')
            ->willReturn([
                new File(TMP . 'style.css'),
                new File(TMP . 'unsupported.file'),
                new File(TMP . 'script.js'),
            ]);

        $out = new ConsoleOutput();
        $err = new ConsoleOutput();
        $io = new ConsoleIo($out, $err);

        try {
            $result = $command->refreshAssetBuffer($io);
        } catch (StopException $exception) {
            $result = $exception->getCode();
        }

        $this->assertEquals(Command::CODE_SUCCESS, $result);
        $this->assertEquals(
            [
                '<info>Refreshing package asset buffer...</info>',
                '<success>All buffered files cleared.</success>',
                '<success>`style.css` successfully copied.</success>',
                '<success>`script.js` successfully copied.</success>',
                '<success>All files buffered.</success>',
            ],
            $out->messages()
        );
        $this->assertEquals(
            [
                '<warning>Skipped `unsupported.file`.</warning>',
            ],
            $err->messages()
        );
    }

    public function testBufferPackageAssetsFailure()
    {
        /** @var \BootstrapUI\Command\InstallCommand|\PHPUnit\Framework\MockObject\MockObject $command */
        $command = $this
            ->getMockBuilder(InstallCommand::class)
            ->onlyMethods(['_deleteBufferedPackageAssets', '_findPackageAssets'])
            ->getMock();

        $command
            ->expects($this->once())
            ->method('_deleteBufferedPackageAssets')
            ->willReturn(true);

        $command
            ->expects($this->once())
            ->method('_findPackageAssets')
            ->willReturn([
                new File(TMP . 'non-existent.css'),
            ]);

        $out = new ConsoleOutput();
        $err = new ConsoleOutput();
        $io = new ConsoleIo($out, $err);

        try {
            $result = $command->refreshAssetBuffer($io);
        } catch (StopException $exception) {
            $result = $exception->getCode();
        }

        $this->assertEquals(Command::CODE_ERROR, $result);
        $this->assertEquals(
            [
                '<info>Refreshing package asset buffer...</info>',
                '<success>All buffered files cleared.</success>',
            ],
            $out->messages()
        );
        $this->assertEquals(
            [
                '<warning>`non-existent.css` could not be copied.</warning>',
                '<error>Could not buffer all files.</error>',
            ],
            $err->messages()
        );
    }

    public function testRemovePluginAssetsFailure()
    {
        /** @var \BootstrapUI\Command\InstallCommand|\PHPUnit\Framework\MockObject\MockObject $command */
        $command = $this
            ->getMockBuilder(InstallCommand::class)
            ->onlyMethods(['executeCommand'])
            ->getMock();

        $command
            ->expects($this->once())
            ->method('executeCommand')
            ->willReturn(1234);

        $out = new ConsoleOutput();
        $err = new ConsoleOutput();
        $io = new ConsoleIo($out, $err);

        try {
            $result = $command->removePluginAssets($io);
        } catch (StopException $exception) {
            $result = $exception->getCode();
        }

        $this->assertEquals(1234, $result);
        $this->assertEquals(
            ['<info>Removing possibly existing plugin assets...</info>'],
            $out->messages()
        );
        $this->assertEquals(
            ['<error>Removing plugin assets failed.</error>'],
            $err->messages()
        );
    }

    public function testLinkPluginAssetsFailure()
    {
        /** @var \BootstrapUI\Command\InstallCommand|\PHPUnit\Framework\MockObject\MockObject $command */
        $command = $this
            ->getMockBuilder(InstallCommand::class)
            ->onlyMethods(['executeCommand'])
            ->getMock();

        $command
            ->expects($this->once())
            ->method('executeCommand')
            ->willReturn(1234);

        $out = new ConsoleOutput();
        $err = new ConsoleOutput();
        $io = new ConsoleIo($out, $err);

        try {
            $result = $command->linkPluginAssets($io);
        } catch (StopException $exception) {
            $result = $exception->getCode();
        }

        $this->assertEquals(1234, $result);
        $this->assertEquals(
            ['<info>Linking plugin assets...</info>'],
            $out->messages()
        );
        $this->assertEquals(
            ['<error>Linking plugin assets failed.</error>'],
            $err->messages()
        );
    }

    public function testHelp()
    {
        $this->exec('bootstrap install --help');

        $this->assertEquals(
            ["Installs Bootstrap dependencies and links the assets to the
application's webroot.

<info>Usage:</info>
cake bootstrap install [-h] [-q] [-v]

<info>Options:</info>

--help, -h     Display this help.
--quiet, -q    Enable quiet output.
--verbose, -v  Enable verbose output.
"],
            $this->_out->messages()
        );
        $this->assertErrorEmpty();
    }
}
