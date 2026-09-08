<?php

declare(strict_types=1);

namespace Veldora\Framework\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Veldora\Framework\Foundation\Application;

class MakeObserverCommand extends Command
{
    protected static ?string $defaultName = 'make:observer';

    protected function configure(): void
    {
        $this
            ->setName('make:observer')
            ->setDescription('Create a new model observer class')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the Observer class')
            ->addOption('model', 'm', InputOption::VALUE_OPTIONAL, 'The model that the observer applies to');
    }

    public function executeDirect(string $name, ?string $model = null): void
    {
        if (!str_ends_with($name, 'Observer')) {
            $name .= 'Observer';
        }

        $app  = Application::getInstance();
        $file = $app->basePath("app/Observers/{$name}.php");

        $modelClass     = $model ? "App\\Models\\{$model}" : 'App\\Models\\YourModel';
        $modelShort     = $model ?? 'Model';
        $modelParam     = '\\' . $modelClass . ' $' . lcfirst($modelShort);
        $modelUse       = $model ? "\nuse {$modelClass};" : '';

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Observers;
{$modelUse}

class {$name}
{
    /**
     * Handle the {$modelShort} "creating" event.
     */
    public function creating({$modelParam}): void
    {
        //
    }

    /**
     * Handle the {$modelShort} "created" event.
     */
    public function created({$modelParam}): void
    {
        //
    }

    /**
     * Handle the {$modelShort} "updating" event.
     */
    public function updating({$modelParam}): void
    {
        //
    }

    /**
     * Handle the {$modelShort} "updated" event.
     */
    public function updated({$modelParam}): void
    {
        //
    }

    /**
     * Handle the {$modelShort} "saving" event.
     */
    public function saving({$modelParam}): void
    {
        //
    }

    /**
     * Handle the {$modelShort} "saved" event.
     */
    public function saved({$modelParam}): void
    {
        //
    }

    /**
     * Handle the {$modelShort} "deleting" event.
     */
    public function deleting({$modelParam}): void
    {
        //
    }

    /**
     * Handle the {$modelShort} "deleted" event.
     */
    public function deleted({$modelParam}): void
    {
        //
    }

    /**
     * Handle the {$modelShort} "restored" event.
     */
    public function restored({$modelParam}): void
    {
        //
    }

    /**
     * Handle the {$modelShort} "forceDeleted" event.
     */
    public function forceDeleted({$modelParam}): void
    {
        //
    }
}
PHP;

        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($file)) {
            fwrite(STDERR, "\033[31mError:\033[0m Observer already exists: app/Observers/{$name}.php\n");
            exit(1);
        }

        file_put_contents($file, $content);
        echo "\033[32m✔ Created Observer:\033[0m app/Observers/{$name}.php\n";

        if ($model) {
            echo "\033[33m→ Register in a service provider:\033[0m {$model}::observe({$name}::class);\n";
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name  = (string) $input->getArgument('name');
        $model = $input->getOption('model');
        $this->executeDirect($name, is_string($model) ? $model : null);
        return Command::SUCCESS;
    }
}
