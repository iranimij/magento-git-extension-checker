<?php

declare(strict_types=1);

namespace Iranimij\GitExtensionChecker\Console\Command;

use Magento\Framework\Filesystem\DirectoryList;
use SebastianFeldmann\Git\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScanCommand extends Command
{
    public function __construct(
        private readonly DirectoryList $dir,
        $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('Iranimij_GitExtensionChecker:scan')
            ->setDescription('Scan a specific Magento module');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $gitRepository  = new Repository($this->dir->getRoot());
        $currentBranch  = $gitRepository->getInfoOperator()->getCurrentBranch();
        $diff           = $gitRepository->getDiffOperator()->compare('main', $currentBranch);
        $changedModules = [];
        $moduleNames    = [];
        foreach ($diff as $item) {
            $explodedName = explode('/', $item->getName());
            $namePath     = $explodedName[0] . '/' . $explodedName[1];

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

        $greetInput = new ArrayInput([
            'command'           => 'yireo_extensionchecker:scan',
            '--module'          => implode(',', $moduleNames),
            '--hide-needless'   => '1',
            '--hide-deprecated' => '1',
        ]);
        $this->getApplication()->doRun($greetInput, $output);

        return 1;
    }

    private function getModuleRegistrationFilePath($filePath)
    {
        if (file_exists($filePath . '/registration.php')) {
            return $filePath . '/registration.php';
        }

        $explodedPath = explode('/', $filePath);
        array_pop($explodedPath);

        if (file_exists($this->dir->getRoot() . '/' . implode('/', $explodedPath) . '/registration.php')) {
            return $this->dir->getRoot() . '/' . implode('/', $explodedPath) . '/registration.php';
        }

        $this->getModuleRegistrationFilePath(implode('/', $explodedPath));
    }
}
