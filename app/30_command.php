<?php
namespace App;


use Brace\Core\AppLoader;
use Brace\Core\BraceApp;
use Micx\FormMailer\Stats\FileStatsRunner;

AppLoader::extend(function (BraceApp $app) {


    $app->command->addCommand("send", function(array $argv) {
        //$runner = new FileStatsRunner();
        //$runner->runAll($argv[0] ?? 0);
    });

    // Send yesterdays report (specified by 1)
    $app->command->addInterval("5 0 * * *", "send", [1], true);

});
