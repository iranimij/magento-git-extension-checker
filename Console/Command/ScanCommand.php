<?php

declare(strict_types=1);

namespace Iranimij\GitExtensionChecker\Console\Command;

use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Module\FullModuleList;
use SebastianFeldmann\Git\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScanCommand extends Command
{
    public function __construct(
        private readonly DirectoryList $dir,
        private readonly FullModuleList $fullModuleList,
        $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('Iranimij_GitExtensionChecker:scan')
            ->setDescription('Scan a specific Magento module');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $gitRepository  = new Repository($this->dir->getRoot());
        $currentBranch  = $gitRepository->getInfoOperator()->getCurrentBranch();
        $diff           = $gitRepository->getDiffOperator()->compare('main', $currentBranch);
        $changedModules = [];
        $moduleNames    = [];
        foreach ($diff as $item) {
            $explodedName = explode(DIRECTORY_SEPARATOR, $item->getName());
            $namePath     = $explodedName[0];

            if(isset($explodedName[1])) {
                $namePath .= DIRECTORY_SEPARATOR . $explodedName[1];
            }

            if(isset($explodedName[2])) {
                $namePath .= DIRECTORY_SEPARATOR . $explodedName[2];
            }

            if (in_array($namePath, $changedModules)) {
                continue;
            }

            $module = $this->getModuleRegistrationFilePath($item->getName());

            if (
                $module === null
                || !file_exists($module)
            ) {
                continue;
            }

            $content          = file_get_contents($module);
            $changedModules[] = $namePath;

            if (empty($content)) {
                continue;
            }

            preg_match("/'([^']+)'/", $content, $matches);
            $moduleName = $matches[1] ?: '';
            $moduleNames[] = $moduleName;
        }

        $allModules  = array_keys($this->fullModuleList->getAll());
        $moduleNames = array_intersect($moduleNames, $allModules);

        $input = new ArrayInput([
            'command'           => 'yireo_extensionchecker:scan',
            '--module'          => implode(',', $moduleNames),
            '--hide-needless'   => '1',
            '--hide-deprecated' => '1',
        ]);
        $this->getApplication()->doRun($input, $output);
    }

    private function getModuleRegistrationFilePath($filePath)
    {
        if (file_exists($filePath . '/registration.php')) {
            return $filePath . '/registration.php';
        }

        $explodedPath = explode('/', $filePath);

        if (count($explodedPath) < 2) {
            return null;
        }

        array_pop($explodedPath);

        if (file_exists($this->dir->getRoot() . '/' . implode('/', $explodedPath) . '/registration.php')) {
            return $this->dir->getRoot() . '/' . implode('/', $explodedPath) . '/registration.php';
        }

        return $this->getModuleRegistrationFilePath(implode('/', $explodedPath));
    }
}
