<?php

declare(strict_types=1);

use App\Console\Commands\AboutCommand;

// The commands bin/console.php runs, by the name they're run with. `list` is built into Console
return [
    'about' => AboutCommand::class,
];
