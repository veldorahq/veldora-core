<?php

declare(strict_types=1);

namespace Veldora\Framework\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Veldora\Framework\Foundation\Application;

class MakeSeederCommand extends Command
{
    protected static ?string $defaultName = 'make:seeder';

    protected function configure(): void
    {
        $this
            ->setName('make:seeder')
            ->setDescription('Create a new Database Seeder class')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the Seeder class');
    }

    public function executeDirect(string $name): void
    {
        if (!str_ends_with($name, 'Seeder')) {
            $name .= 'Seeder';
        }

        $app  = Application::getInstance();
        $file = $app->basePath("database/seeders/{$name}.php");

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace Database\Seeders;

use Veldora\Framework\Database\Seeder;

class {$name} extends Seeder
{
    /**
     * Seed the database with sample data.
     */
    public function run(): void
    {
        // \$this->db->table('users')->insert([...]);
    }
}
PHP;

        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($file)) {
            fwrite(STDERR, "\033[31mError:\033[0m Seeder already exists: database/seeders/{$name}.php\n");
            exit(1);
        }

        file_put_contents($file, $content);
        echo "\033[32m✔ Created Seeder:\033[0m database/seeders/{$name}.php\n";
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = (string) $input->getArgument('name');
        $this->executeDirect($name);
        return Command::SUCCESS;
    }
}
