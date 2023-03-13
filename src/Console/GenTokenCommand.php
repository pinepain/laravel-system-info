<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Console;


use Illuminate\Console\Command;
use Illuminate\Support\Str;


class GenTokenCommand extends Command
{

    protected $signature = 'system-info:gen-auth-token';

    protected $description = 'Generate auth token for system-info endpoint access';

    public function handle()
    {
        $this->info(Str::random(40), 'quiet');

        return 0;
    }
}
