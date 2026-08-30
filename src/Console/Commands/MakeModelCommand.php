<?php

declare(strict_types=1);

namespace Veldora\Framework\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Veldora\Framework\Foundation\Application;

class MakeModelCommand extends Command
{
    protected static ?string $defaultName = 'make:model';

    protected function configure(): void
    {
        $this
            ->setName('make:model')
            ->setDescription('Create a new Database Model class')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the Model class')
            ->addOption('migration', 'm', InputOption::VALUE_NONE, 'Create a new migration file for the model');
    }

    public function executeDirect(string $name, bool $withMigration = false): void
    {
        $app = Application::getInstance();
        $modelFile = $app->basePath("app/Models/{$name}.php");

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Models;

use Veldora\Framework\Database\Model;

class {$name} extends Model
{
    // protected ?string \$table = null;
}
PHP;

        $dir = dirname($modelFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($modelFile)) {
            fwrite(STDERR, "\033[31mError:\033[0m Model already exists: app/Models/{$name}.php\n");
            exit(1);
        }

        file_put_contents($modelFile, $content);
        echo "\033[32m✔ Created Model:\033[0m app/Models/{$name}.php\n";

        if ($withMigration) {
            $pluralName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
            if (str_ends_with($pluralName, 'y')) {
                $pluralName = substr($pluralName, 0, -1) . 'ies';
            } else {
                $pluralName .= 's';
            }

            $migrationName = "create_{$pluralName}_table";
            (new MakeMigrationCommand())->executeDirect($migrationName);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = (string) $input->getArgument('name');
        $withMigration = (bool) $input->getOption('migration');
        $this->executeDirect($name, $withMigration);
        return Command::SUCCESS;
    }
}
