<?php

declare(strict_types=1);

namespace Veldora\Framework\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Veldora\Framework\Foundation\Application;

class DownCommand extends Command
{
    protected static ?string $defaultName = 'down';

    protected function configure(): void
    {
        $this
            ->setName('down')
            ->setDescription('Put the application into maintenance mode')
            ->addOption('secret', null, InputOption::VALUE_OPTIONAL, 'Secret token to bypass maintenance mode');
    }

    public function executeDirect(?string $secret = null): void
    {
        $app = Application::getInstance();
        $downFile = $app->storagePath('framework/down');

        $dir = dirname($downFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $payload = [
            'down_at' => date('Y-m-d H:i:s'),
            'secret'  => $secret,
        ];

        file_put_contents($downFile, json_encode($payload, JSON_PRETTY_PRINT));

        echo "\n\033[33mApplication is now in maintenance mode.\033[0m\n";
        if ($secret) {
            echo "  Bypass via: \033[32m?secret={$secret}\033[0m\n";
        }
        echo "\n";
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->executeDirect($input->getOption('secret'));
        return Command::SUCCESS;
    }
}
