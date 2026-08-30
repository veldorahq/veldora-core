<?php

declare(strict_types=1);

namespace Veldora\Framework\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Veldora\Framework\Foundation\Application;

class MakeMiddlewareCommand extends Command
{
    protected static ?string $defaultName = 'make:middleware';

    protected function configure(): void
    {
        $this
            ->setName('make:middleware')
            ->setDescription('Create a new Middleware class')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the Middleware class');
    }

    public function executeDirect(string $name): void
    {
        if (!str_ends_with($name, 'Middleware')) {
            $name .= 'Middleware';
        }

        $app  = Application::getInstance();
        $file = $app->basePath("app/Http/Middleware/{$name}.php");

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Veldora\Framework\Http\MiddlewareInterface;
use Veldora\Framework\Http\Request;
use Veldora\Framework\Http\Response;

class {$name} implements MiddlewareInterface
{
    /**
     * Handle the incoming request.
     */
    public function handle(Request \$request, Closure \$next): Response
    {
        // Your logic before the request is handled
        \$response = \$next(\$request);
        // Your logic after the request is handled
        return \$response;
    }
}
PHP;

        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($file)) {
            fwrite(STDERR, "\033[31mError:\033[0m Middleware already exists: app/Http/Middleware/{$name}.php\n");
            exit(1);
        }

        file_put_contents($file, $content);
        echo "\033[32m✔ Created Middleware:\033[0m app/Http/Middleware/{$name}.php\n";
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = (string) $input->getArgument('name');
        $this->executeDirect($name);
        return Command::SUCCESS;
    }
}
