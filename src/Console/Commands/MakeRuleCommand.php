<?php

declare(strict_types=1);

namespace Veldora\Framework\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Veldora\Framework\Foundation\Application;

class MakeRuleCommand extends Command
{
    protected static ?string $defaultName = 'make:rule';

    protected function configure(): void
    {
        $this
            ->setName('make:rule')
            ->setDescription('Create a new custom validation rule class')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the Rule class');
    }

    public function executeDirect(string $name): void
    {
        $app  = Application::getInstance();
        $file = $app->basePath("app/Rules/{$name}.php");

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Rules;

use Veldora\Framework\Validation\Rule;

class {$name} implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param string \$attribute  The field name being validated.
     * @param mixed  \$value      The field value.
     */
    public function passes(string \$attribute, mixed \$value): bool
    {
        // TODO: Implement your validation logic here.
        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return "The :attribute field is invalid.";
    }
}
PHP;

        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($file)) {
            fwrite(STDERR, "\033[31mError:\033[0m Rule already exists: app/Rules/{$name}.php\n");
            exit(1);
        }

        file_put_contents($file, $content);
        echo "\033[32m✔ Created Rule:\033[0m app/Rules/{$name}.php\n";
        echo "\033[33m→ Usage:\033[0m  'field' => [new \\App\\Rules\\{$name}()]\n";
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = (string) $input->getArgument('name');
        $this->executeDirect($name);
        return Command::SUCCESS;
    }
}
