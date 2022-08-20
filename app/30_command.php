<?php
namespace App;


use Brace\Core\AppLoader;
use Brace\Core\BraceApp;
use Lack\Subscription\SubscriptionManagerInterface;
use Micx\FormMailer\Stats\FileStatsRunner;

AppLoader::extend(function (BraceApp $app) {


    $app->command->addCommand("send", function(array $argv, SubscriptionManagerInterface $subscriptionManager) {
        $runner = new FileStatsRunner($subscriptionManager);
        $runner->runAll($argv[0] ?? 0);
    });

    // Send yesterdays report at 00:05:00 (specified by 1)
    $app->command->addInterval("5 0 * * *", "send", [1], true);

});
