<?php

declare(strict_types=1);

namespace Iranimij\GitExtensionChecker\Console\Command;

use Magento\Framework\Filesystem\DirectoryList;
use SebastianFeldmann\Git\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Iranimij\GitExtensionChecker\Model\Scan;

class ScanCommand extends Command
{
    private Scan $scan;

    private SerializerInterface $serializer;

    public function __construct(
        Scan $scan,
        SerializerInterface $serializer,
        private readonly DirectoryList $dir,
        $name = null,
    ) {
        parent::__construct($name);
        $this->scan       = $scan;
        $this->serializer = $serializer;
    }

    protected function configure()
    {
        $this->setName('Iranimij_GitExtensionChecker:scan')
            ->setDescription('Scan a specific Magento module');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $gitRepository = new Repository($this->dir->getRoot());
        $currentBranch = $gitRepository->getInfoOperator()->getCurrentBranch();
        $diff          = $gitRepository->getDiffOperator()->compare('main', $currentBranch);

        foreach ($diff as $item) {
            $explodedItem = explode('/', $item->getName());

            if (empty($explodedItem[0]) || empty($explodedItem[1]) || empty($explodedItem[2])) {
                continue;
            }

            $appModule    = $this->dir->getRoot()
                . "/$explodedItem[0]/$explodedItem[1]/$explodedItem[2]/$explodedItem[3]/registration.php";
            $customModule = $this->dir->getRoot()
                . "/$explodedItem[0]/$explodedItem[1]/$explodedItem[2]/registration.php";
            if (
                !file_exists($appModule)
                && !file_exists($customModule)
            ) {
                continue;
            }
            $module  = file_exists($appModule) ? $appModule : $customModule;
            $content = file_get_contents($module);

            if (empty($content)) {
                continue;
            }

            preg_match("/'([^']+)'/", $content, $matches);
            $moduleName = $matches[1] ?: '';
            $greetInput = new ArrayInput([
                'command'  => 'yireo_extensionchecker:scan',
                '--module' => $moduleName,
            ]);
            $this->getApplication()->doRun($greetInput, $output);
        }

        return 1;
    }
}
