<?php

namespace Opencart\Extension;

use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Symfony\Component\Filesystem\Filesystem;

class Installer extends LibraryInstaller
{
    public function getOpenCartDir()
    {
        $extra = $this->composer->getPackage()->getExtra();

        if (isset($extra['opencart-install-dir'])) {
            return $extra['opencart-install-dir'];
        }

        // OC directory "upload" is root dir
        return 'upload';
    }

    /**
     * Get src path of module
     */
    public function getSrcDir($installPath, array $extra)
    {
        if (isset($extra['src-dir']) && is_string($extra['src-dir'])) {
            $installPath .= "/" . $extra['src-dir'];
        } else { // default
            $installPath .= "/src/upload";
        }

        return $installPath;
    }

    /**
     * @param array $extra extra array
     */
    public function copyFiles($sourceDir, $targetDir, array $extra)
    {
        $filesystem = new Filesystem();


        if (isset($extra['mappings']) && is_array($extra['mappings'])) {
            foreach($extra['mappings'] as $mapping) {
                $source = $sourceDir . "/" . $mapping;
                $target = $targetDir . "/" . $mapping;

                $filesystem->copy($source, $target, true);
            }
        }
    }
    /**
     * @param array $extra extra array
     */
    public function removeFiles($targetDir, array $extra) {
	     $filesystem = new Filesystem();

	    if (isset($extra['mappings']) && is_array($extra['mappings'])) {
		    foreach($extra['mappings'] as $mapping) {
			    $target = $targetDir . "/" . $mapping;
			    $filesystem->remove($target);
		    }
	    }
    }

    /**
     * @param string $srcDir Src Directory of installed package
     * @param string $name Name of installed package
     * @param array $extra extra array
     */
    public function runExtensionInstaller($srcDir, $name, array $extra)
    {
        $xml = (isset($extra['installers']) && isset($extra['installers']['xml'])) ? $extra['installers']['xml'] : '';
        $php = (isset($extra['installers']) && isset($extra['installers']['php'])) ? $extra['installers']['php'] : '';

        if (!empty($php)) {
            $this->io->write("    <info>Start running php installer.</info>");
            try {
                if ($this->runPhpExtensionInstaller($srcDir ."/". $php)) {
	                $this->io->write("    <info>Successfully runned php installer.</info>");
                }
				else {
					$this->io->write("    <error>Php installer not runned!</error>");
				}

            } catch (\Exception $e) {
                $this->io->write("    <error>Error while running php extension installer. " . $e->getMessage() . "</error>");
            }
        }

        if (!empty($xml)) {
            $this->io->write("    <info>Start running xml installer.</info>");
            try {
                $this->runXmlExtensionInstaller($srcDir ."/". $xml, $name);
                $this->io->write("    <info>Successfully runned xml installer.</info>");
            } catch (\Exception $e) {
                $this->io->write("    <error>Error while running xml extension installer. " . $e->getMessage() . "</error>");
            }
        }
    }

    public function runPhpExtensionInstaller($file) {
    	$file = str_replace('\\', '/', $file); // Windows systems address fix

        $registry = null;
        $openCartDir = $this->getOpenCartDir();

        // opencart not yet available
        if (!is_dir($openCartDir)) {
            return;
        }

        $tmpDir = getcwd();
        chdir($openCartDir);

        // only trigger install iff config is available
        if (is_file('admin/config.php') && filesize('admin/config.php') > 0) { // jd todo filesize(config.php) > 0, else cancel extension install https://app.clickup.com/t/e50d7e
        	if(!function_exists('modification')) {
		        $_SERVER['SERVER_PORT'] = 80;
		        $_SERVER['SERVER_PROTOCOL'] = 'CLI';
		        $_SERVER['REQUEST_METHOD'] = 'GET';
		        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		        ob_start();
		        require_once('admin/config.php');
		        include('system/startup.php');
		        $application_config = 'admin';
		        include('system/framework.php');
		        ob_end_clean();

		        chdir($tmpDir);

		        // $registry comes from system/framework.php
		        NaivePhpInstaller::$registry = $registry;
	        }
	        $installer = new NaivePhpInstaller();
	        $installer->install($file);
			$installed = true;
	    }
	    chdir($tmpDir);
		if(!empty($installed)) return true;
		else return false;
    }

    public function runXmlExtensionInstaller($src, $name) {
    	$this->io->write("<info>XML installer name - {$name}</info>");
        $name = strtolower(str_replace(array("/","-"),"_",$name));
        $filesystem = new Filesystem();

        $target = $this->getOpenCartDir() . "/system/" . $name . ".ocmod.xml";

        $filesystem->copy($src, $target, true);
    }

    /**
     * { @inheritDoc }
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        parent::install($repo, $package);

        $srcDir = $this->getSrcDir($this->getInstallPath($package), $package->getExtra());
        $openCartDir = $this->getOpenCartDir();

        $this->copyFiles($srcDir, $openCartDir, $package->getExtra());
        $this->runExtensionInstaller($this->getInstallPath($package), $package->getName(), $package->getExtra());
    }

    /**
     * { @inheritDoc }
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        parent::update($repo, $initial, $target);

        $srcDir = $this->getSrcDir($this->getInstallPath($target), $target->getExtra());
        $openCartDir = $this->getOpenCartDir();

        $this->copyFiles($srcDir, $openCartDir, $target->getExtra());
        $this->runExtensionInstaller($this->getInstallPath($target), $target->getName(), $target->getExtra());
    }

    /**
     * { @inheritDoc }
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {

        parent::uninstall($repo, $package);

        // TODO: remove files from opencart

    }

}