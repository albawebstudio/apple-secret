<?php

namespace Albawebstudio\AppleSecretGenerator\Console;

use Albawebstudio\AppleSecretGenerator\Services\AppleToken;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class AppleSecretGeneratorCommand extends Command
{
    use ConfirmableTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apple:secret';

    /**
     * The console command description.
     * [client-secret-for-sign-in](https://bannister.me/blog/generating-a-client-secret-for-sign-in-with-apple-on-each-request)
     *
     * @var string
     */
    protected $description = 'Generate Apple client secret. Need to rename downloaded `*.p8` key file to `key.txt` and
    place in ./docker/sign-in-with-apple/ directory.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        if ( ! file_exists(base_path() . '/key.txt') ){
            throw new Exception("Unable to find Apple Login Secret `/key.txt`.");
        }
        app()->bind(Configuration::class, fn () => Configuration::forSymmetricSigner(
            new Sha256(),
            LocalFileReference::file(base_path() . '/key.txt')
        ));
    }

    /**
     * @param AppleToken $appleToken
     * @return int
     */
    public function handle(AppleToken $appleToken): int
    {
        $token = $appleToken->generate();

        if ( ! $this->setAppleClientSecretInEnvironmentFile($token) ) {
            return 1;
        }

        $this->laravel['config']['services.client_secret'] = $token;
        $this->info("Apple client secret has been set successfully: [$token]");
        return 0;
    }

    /**
     * @param $token
     * @return bool
     */
    protected function setAppleClientSecretInEnvironmentFile($token): bool
    {
        $currentSecret = $this->laravel['config']['services.client_secret'] ?: env('APPLE_CLIENT_SECRET');

        if ( strlen($currentSecret) !== 0 && (! $this->confirmToProceed())) {
            return false;
        }

        $this->writeNewEnvironmentFileWith($token);

        return true;
    }

    /**
     * @param $token
     */
    protected function writeNewEnvironmentFileWith($token)
    {
        file_put_contents($this->laravel->basePath('.env'), preg_replace(
            $this->tokenReplacementPattern($token),
            'APPLE_CLIENT_SECRET='.$token,
            file_get_contents($this->laravel->basePath('.env'))
        ));
    }

    /**
     * @return string
     */
    protected function tokenReplacementPattern(): string
    {
        $currentKey = $this->laravel['config']['services.client_secret'] ?: env('APPLE_CLIENT_SECRET');
        $escaped = preg_quote('='.$currentKey, '/');

        return "/^APPLE_CLIENT_SECRET{$escaped}/m";
    }
}