<?php

declare(strict_types=1);

namespace Veldora\Framework\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Veldora\Framework\Foundation\Application;

class MakePolicyCommand extends Command
{
    protected static ?string $defaultName = 'make:policy';

    protected function configure(): void
    {
        $this
            ->setName('make:policy')
            ->setDescription('Create a new authorization Policy class')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the Policy class')
            ->addOption('model', 'm', InputOption::VALUE_OPTIONAL, 'The model that the policy applies to');
    }

    public function executeDirect(string $name, ?string $model = null): void
    {
        if (!str_ends_with($name, 'Policy')) {
            $name .= 'Policy';
        }

        $app  = Application::getInstance();
        $file = $app->basePath("app/Policies/{$name}.php");

        $modelParam = $model ? "\\App\\Models\\{$model} \$" . strtolower($model) : '';
        $modelDoc = $model ? " * @param \\App\\Models\\{$model} \${$model}\n" : '';

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class {$name}
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User \$user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
{$modelDoc}     */
    public function view(User \$user{$modelParam}): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User \$user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
{$modelDoc}     */
    public function update(User \$user{$modelParam}): bool
    {
        return \$user->id === \${$model}->user_id;
    }

    /**
     * Determine whether the user can delete the model.
{$modelDoc}     */
    public function delete(User \$user{$modelParam}): bool
    {
        return \$user->id === \${$model}->user_id;
    }
}
PHP;

        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($file)) {
            fwrite(STDERR, "\033[31mError:\033[0m Policy already exists: app/Policies/{$name}.php\n");
            exit(1);
        }

        file_put_contents($file, $content);
        echo "\033[32m✔ Created Policy:\033[0m app/Policies/{$name}.php\n";
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = (string) $input->getArgument('name');
        $model = $input->getOption('model');
        $this->executeDirect($name, is_string($model) ? $model : null);
        return Command::SUCCESS;
    }
}
