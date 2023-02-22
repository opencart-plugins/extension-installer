<?php

namespace Opencart\Extension;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class InstallerPlugin implements PluginInterface
{
    protected $packageType = 'opencart-extension';

    public function activate(Composer $composer, IOInterface $io)
    {
        $io->write("opencart-extension-installer <info>activate method</info>");
        $installer = new Installer($io, $composer, $this->packageType);
        $composer->getInstallationManager()->addInstaller($installer);
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        $io->write("opencart-extension-installer <info>deactivate method</info>");
        $installer = new Installer($io, $composer, $this->packageType);
        $composer->getInstallationManager()->removeInstaller($installer);
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        $io->write("opencart-extension-installer <info>uninstall method</info>");
        $installer = new Installer($io, $composer, $this->packageType);
        $composer->getInstallationManager()->removeInstaller($installer);
    }
}