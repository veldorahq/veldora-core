<?php

declare(strict_types=1);

namespace Veldora\Framework\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Veldora\Framework\Foundation\Application;

class UpCommand extends Command
{
    protected static ?string $defaultName = 'up';

    protected function configure(): void
    {
        $this
            ->setName('up')
            ->setDescription('Bring the application out of maintenance mode');
    }

    public function executeDirect(): void
    {
        $app = Application::getInstance();
        $downFile = $app->storagePath('framework/down');

        if (file_exists($downFile)) {
            unlink($downFile);
            echo "\n\033[32m✔ Application is now live.\033[0m\n\n";
        } else {
            echo "\n\033[33mApplication is already live.\033[0m\n\n";
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->executeDirect();
        return Command::SUCCESS;
    }
}
