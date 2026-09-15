<?php

declare(strict_types=1);

namespace Veldora\Framework\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ConnectCommand
 *
 * Installs a Veldora Connect integration package and publishes its config.
 *
 * Usage:
 *   php veldora connect stripe        — install Veldora Stripe integration
 *   php veldora connect               — list all available integrations
 */
class ConnectCommand extends Command
{
    protected static ?string $defaultName = 'connect';

    private string $projectRoot;

    public function __construct(?string $projectRoot = null)
    {
        parent::__construct();
        $this->projectRoot = $projectRoot ?? getcwd();
    }

    protected function configure(): void
    {
        $this
            ->setName('connect')
            ->setDescription('Install a Veldora Connect integration (e.g. stripe, sslcommerz)')
            ->setHelp("Install a Veldora Connect integration package.\n\n  php veldora connect stripe\n")
            ->addArgument('integration', InputArgument::OPTIONAL, 'The integration to install (e.g. stripe)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $integration = strtolower(trim((string) ($input->getArgument('integration') ?? '')));

        if ($integration === '') {
            $this->printAvailable($output);
            return Command::SUCCESS;
        }

        $registry = $this->registry();

        if (!isset($registry[$integration])) {
            $output->writeln('');
            $output->writeln("  <fg=red>✗</> Integration [<fg=yellow>{$integration}</>] not found.");
            $output->writeln('');
            $this->printAvailable($output);
            return Command::FAILURE;
        }

        $info = $registry[$integration];
        $package = $info['package'];

        $output->writeln('');
        $output->writeln("  <fg=magenta>Veldora Connect</> — Installing <fg=cyan>{$integration}</> integration...");
        $output->writeln('');

        // Step 1: composer require
        $output->writeln("  <fg=blue>→</> Running: <fg=white>composer require {$package}</>");
        $composerResult = 0;
        passthru("composer require {$package} --no-interaction", $composerResult);

        if ($composerResult !== 0) {
            $output->writeln('');
            $output->writeln("  <fg=red>✗</> Composer installation failed. Check your internet connection and try again.");
            return Command::FAILURE;
        }

        // Step 2: publish config
        if (!empty($info['config_src']) && !empty($info['config_dest'])) {
            $dest = $this->projectRoot . '/config/' . $info['config_dest'];
            if (file_exists($dest)) {
                $output->writeln("  <fg=yellow>!</>  Config [config/{$info['config_dest']}] already exists — skipped.");
            } else {
                $src = $this->resolveConfigSrc($info['config_src'], $package);
                if ($src && file_exists($src)) {
                    if (!is_dir(dirname($dest))) {
                        mkdir(dirname($dest), 0755, true);
                    }
                    copy($src, $dest);
                    $output->writeln("  <fg=green>✓</>  Published: <fg=white>config/{$info['config_dest']}</>");
                } else {
                    $output->writeln("  <fg=yellow>!</>  Could not locate config template — publish manually.");
                }
            }
        }

        // Step 3: print next steps
        $output->writeln('');
        $output->writeln("  <fg=green>✓</> <fg=cyan>{$integration}</> integration installed successfully!");
        $output->writeln('');
        $output->writeln('  <fg=magenta>Next steps:</>');
        foreach ($info['next_steps'] as $step) {
            $output->writeln("    · {$step}");
        }
        $output->writeln('');

        return Command::SUCCESS;
    }

    private function printAvailable(OutputInterface $output): void
    {
        $output->writeln('  <fg=magenta>Veldora Connect</> — Available integrations:');
        $output->writeln('');
        foreach ($this->registry() as $name => $info) {
            $status = $info['status'] === 'active' ? '<fg=green>Active</>' : '<fg=yellow>Upcoming</>';
            $output->writeln("    <fg=cyan>{$name}</>\t{$status}\t{$info['description']}");
        }
        $output->writeln('');
        $output->writeln('  Usage: <fg=white>php veldora connect <integration></>');
        $output->writeln('');
    }

    private function resolveConfigSrc(string $filename, string $package): ?string
    {
        $packageDir = str_replace('/', DIRECTORY_SEPARATOR, $package);
        $candidates = [
            $this->projectRoot . '/vendor/' . $packageDir . '/config/' . $filename,
            $this->projectRoot . '/vendor/veldora/connect/packages/stripe/config/' . $filename,
        ];
        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        return null;
    }

    /**
     * @return array<string, array{package: string, status: string, description: string, config_src: string, config_dest: string, next_steps: array<string>}>
     */
    private function registry(): array
    {
        return [
            'stripe' => [
                'package'     => 'veldora/connect',
                'status'      => 'active',
                'description' => 'Stripe payment gateway (Checkout, PaymentIntents, Webhooks, Customers)',
                'config_src'  => 'stripe.php',
                'config_dest' => 'stripe.php',
                'next_steps'  => [
                    'Add to your <fg=white>.env</>: STRIPE_SECRET_KEY=sk_test_...',
                    'Add to your <fg=white>.env</>: STRIPE_PUBLIC_KEY=pk_test_...',
                    'Add to your <fg=white>.env</>: STRIPE_WEBHOOK_SECRET=whsec_...',
                    'Register provider in <fg=white>bootstrap/app.php</>: $app->registerProvider(\\Veldora\\Connect\\Stripe\\StripeServiceProvider::class);',
                    'Use: <fg=white>Stripe::checkout()->create([...])</> or <fg=white>stripe()->checkout()->create([...])</>',
                    'Docs: https://veldora.modrao.com/docs/connect/stripe',
                ],
            ],
            'sslcommerz' => [
                'package'     => 'veldora/connect-sslcommerz',
                'status'      => 'upcoming',
                'description' => 'SSLCommerz payment gateway integration',
                'config_src'  => '',
                'config_dest' => '',
                'next_steps'  => [],
            ],
            'resend' => [
                'package'     => 'veldora/connect-resend',
                'status'      => 'upcoming',
                'description' => 'Resend transactional email service',
                'config_src'  => '',
                'config_dest' => '',
                'next_steps'  => [],
            ],
            's3' => [
                'package'     => 'veldora/connect-s3',
                'status'      => 'upcoming',
                'description' => 'AWS S3 / S3-compatible cloud storage driver',
                'config_src'  => '',
                'config_dest' => '',
                'next_steps'  => [],
            ],
            'sentry' => [
                'package'     => 'veldora/connect-sentry',
                'status'      => 'upcoming',
                'description' => 'Sentry real-time crash reporting and telemetry',
                'config_src'  => '',
                'config_dest' => '',
                'next_steps'  => [],
            ],
        ];
    }
}
