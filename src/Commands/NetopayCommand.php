<?php

namespace MarianDumitru\Netopay\Commands;

use Illuminate\Console\Command;

class NetopayCommand extends Command
{
    public $signature = 'netopay';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
