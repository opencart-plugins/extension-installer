<?php

namespace Opencart\Extension\Tests;

use Opencart\Extension\Installer;
use Composer\Composer;
use Composer\Config;
use Composer\Package\RootPackage;
use PHPUnit\Framework\TestCase;

class InstallerTest extends TestCase
{
    /**
     * @var Installer
     */
    protected Installer $extensionInstaller;

    protected function setUp(): void
    {
        $composer = new Composer();
        $package = new RootPackage("test", "1", "1");
        $package->setExtra([
            'opencart-install-dir' => 'tests/resources/sampleocdir'
        ]);

        $composer->setPackage($package);
        $composer->setConfig(new Config());

        $this->extensionInstaller = new Installer(
            $this->getMockBuilder('Composer\IO\IOInterface')->getMock(),
            $composer
        );
    }

    public function test_php_oc_installer()
    {
        $this->extensionInstaller->runPhpExtensionInstaller("tests/resources/sampleinstaller/installer.php");
        $this->assertTrue($_ENV['installer_called']);
    }

    public function test_xml_oc_installer()
    {
        $this->extensionInstaller->runXmlExtensionInstaller("tests/resources/sampleinstaller/installer.xml", "test/a-b-c");
        $this->assertTrue(is_file('tests/resources/sampleocdir/system/test_a_b_c.ocmod.xml'));
        unlink('tests/resources/sampleocdir/system/test_a_b_c.ocmod.xml');
    }

    public function test_retrieving_oc_dir()
    {
        $this->assertEquals("tests/resources/sampleocdir", $this->extensionInstaller->getOpenCartDir());
    }

    public function test_file_copying()
    {
        mkdir('tests/tocopy');

        $this->extensionInstaller->copyFiles('tests/resources', 'tests/tocopy', ['mappings' => ['sampledir/samplefile.txt']]);
        $this->assertTrue(is_file('tests/tocopy/sampledir/samplefile.txt'));

        unlink('tests/tocopy/sampledir/samplefile.txt');
        rmdir('tests/tocopy/sampledir');
        rmdir('tests/tocopy');
    }

    public function test_src_dir()
    {
        $srcDir = $this->extensionInstaller->getSrcDir('vendor/vendor-name/project', ['src-dir' => 'src/main/upload']);
        $this->assertEquals('vendor/vendor-name/project/src/main/upload', $srcDir);
    }
}