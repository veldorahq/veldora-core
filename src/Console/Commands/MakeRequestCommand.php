<?php

declare(strict_types=1);

namespace Veldora\Framework\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Veldora\Framework\Foundation\Application;

class MakeRequestCommand extends Command
{
    protected static ?string $defaultName = 'make:request';

    protected function configure(): void
    {
        $this
            ->setName('make:request')
            ->setDescription('Create a new Form Request (Validation) class')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the Request class');
    }

    public function executeDirect(string $name): void
    {
        if (!str_ends_with($name, 'Request')) {
            $name .= 'Request';
        }

        $app  = Application::getInstance();
        $file = $app->basePath("app/Http/Requests/{$name}.php");

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Veldora\Framework\Http\FormRequest;

class {$name} extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string>|string>
     */
    public function rules(): array
    {
        return [
            // 'title' => 'required|string|max:255',
            // 'body'  => 'required|string',
        ];
    }
}
PHP;

        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($file)) {
            fwrite(STDERR, "\033[31mError:\033[0m Request already exists: app/Http/Requests/{$name}.php\n");
            exit(1);
        }

        file_put_contents($file, $content);
        echo "\033[32m✔ Created Form Request:\033[0m app/Http/Requests/{$name}.php\n";
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = (string) $input->getArgument('name');
        $this->executeDirect($name);
        return Command::SUCCESS;
    }
}
