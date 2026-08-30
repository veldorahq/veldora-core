<?php

declare(strict_types=1);

namespace Veldora\Framework\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Veldora\Framework\Foundation\Application;

class MakeMigrationCommand extends Command
{
    protected static ?string $defaultName = 'make:migration';

    protected function configure(): void
    {
        $this
            ->setName('make:migration')
            ->setDescription('Create a new migration file')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the migration');
    }

    public function executeDirect(string $name): void
    {
        $snakeName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));

        // Detect table name from migration description (e.g. create_users_table => users)
        $tableName = 'table_name';
        if (preg_match('/^create_(.*)_table$/i', $snakeName, $matches)) {
            $tableName = $matches[1];
        }

        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_{$snakeName}.php";

        $app = Application::getInstance();
        $migrationFile = $app->basePath("database/migrations/{$filename}");

        $content = <<<PHP
<?php

declare(strict_types=1);

use Veldora\Framework\Database\Schema\Blueprint;
use Veldora\Framework\Database\Schema\Migration;
use Veldora\Framework\Database\Schema\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();

            \$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};
PHP;

        $dir = dirname($migrationFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($migrationFile, $content);

        echo "\033[32m✔ Created Migration:\033[0m database/migrations/{$filename}\n";
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = (string) $input->getArgument('name');
        $this->executeDirect($name);
        return Command::SUCCESS;
    }
}
