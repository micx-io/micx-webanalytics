<?php
namespace App;



use Brace\Core\AppLoader;
use Brace\Core\BraceApp;
use Brace\Core\Helper\Cookie;
use http\Message\Body;
use Lack\OidServer\Base\Ctrl\AuthorizeCtrl;
use Lack\OidServer\Base\Ctrl\SignInCtrl;
use Lack\OidServer\Base\Ctrl\LogoutCtrl;
use Lack\OidServer\Base\Ctrl\TokenCtrl;
use Lack\OidServer\Base\Tpl\HtmlTemplate;
use Lack\Subscription\Type\T_Subscription;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Micx\FormMailer\Config\T_Analytics;
use Micx\FormMailer\Config\TAnalytics;
use Phore\Mail\PhoreMailer;
use Psr\Http\Message\ServerRequestInterface;
use function Sodium\add;

AppLoader::extend(function (BraceApp $app) {


    $app->router->on("GET@/v1/webanalytics/player", function (ServerRequestInterface $request) use($app) {
        switch ($request->getUri()->getQuery()) {
            case "css":
                return $app->responseFactory->createResponseWithBody(
                    file_get_contents(__DIR__ . "/../src/player.css"),
                    200, ["content-type" => "text/css"]
                );
        }
    });


    $app->router->on("GET@/v1/webanalytics/wa.js", function (BraceApp $app, T_Subscription $subscription, ServerRequestInterface $request) {
        $subscriptionId = addslashes($subscription->subscription_id);
        $config = $subscription->getClientPrivateConfig(null, T_Analytics::class);
        assert ($config instanceof T_Analytics);

        $origin = $request->getHeader("referer")[0] ?? "";
        if ( ! origin_match($origin, $subscription->allow_origins)) {
            $origin = addslashes($origin);

            return $app->responseFactory->createResponseWithBody(
                "console.log('Webanalytics: Invalid origin $origin for subscriptionId $subscriptionId')",
                403, ["content-type"=>"text/javascript"]
            );
        }

        if (isset ($request->getQueryParams()["player"])) {
            $jsText = file_get_contents(__DIR__ . "/../src/wa_player.js");
        } elseif (isset($request->getQueryParams()["analytics"])) {
            $jsText = file_get_contents(__DIR__ . "/../src/webanalytics.js");
        } else {

            $jsText = file_get_contents(__DIR__ . "/../src/cookie-consent.js");
        }


        $rand = phore_random_str(6);
        $endpointKey = sha1($subscriptionId . $rand . FE_SECRET);
        $jsText = str_replace(
            ["%%ENDPOINT_URL%%", "%%RAND%%", "%%SERVER_DATE%%", "%%SUBSCRIPTION_ID%%", "%%ENDPOINT_KEY%%", "%%CONFIG%%"],
            [
                "//" . $app->request->getUri()->getHost() . "/v1/webanalytics/",
                $rand,
                gmdate("Y-m-d H:i:s"),
                $subscriptionId,
                $endpointKey,
                json_encode(["autostart" => $config->autostart])
            ],
            $jsText
        );

        $response = $app->responseFactory->createResponseWithBody($jsText, 200, ["Content-Type" => "application/javascript"]);
        return $response;
    });

    $app->router->on("GET@/v1/webanalytics/send", function (BraceApp $app) {
        $app->command->runCommand("send");
        return ["ok"];
    });


    $app->router->on("GET@/v1/webanalytics/emit", function (ServerRequest $request, T_Subscription $subscription) {
        $session_id = $request->getQueryParams()["session_id"] ?? null;
        $session_seq = (int)($request->getQueryParams()["session_seq"] ?? -1);
        $endpoint_key = (string)($request->getQueryParams()["endpoint_key"] ?? "");

        if ($endpoint_key !== sha1(FE_SECRET . $session_id))
            throw new \InvalidArgumentException("Invalid endpoint key.");

        $logfile = phore_file(DATA_PATH . "/" . $subscription->subscription_id . ".track");

        $fp = $logfile->fopen("r");
        while ( ! $fp->feof()) {
            $data = json_decode($fp->fgets(), true);

            if ( ! is_array($data))
                continue;
            if (($data["session_id"] ?? null) === $session_id && ($data["session_seq"] ?? null) === $session_seq) {
                unset($data["ip"], $data["host"], $data["ts"]);
                return $data;
            }
        }
        return ["sequence_end" => true];
    });

    $app->router->on("POST@/v1/webanalytics/emit", function(string $body, T_Subscription $subscription, ServerRequest $request) use ($app) {
        $endpoint_key = (string)($request->getQueryParams()["endpoint_key"] ?? "");
        $data = json_decode($body, true);

        if ($endpoint_key !== sha1($subscription->subscription_id . $data["session_id"] . FE_SECRET))
            throw new \InvalidArgumentException("Endpoint key invalid");

        $logfile = phore_file(DATA_PATH . "/" . $subscription->subscription_id . ".track");

        if (is_array($data)) {
            $data["ts"] = time();
            $data["ip"] = anonymize_host_ip($request->getHeader("X-Real-IP")[0] ?? "unset x-real-ip");
            $data["anon_ip"] = substr(sha1($request->getHeader("X-Real-IP")[0] ?? "127.0.0.1"), 0, 8);
            $data["host"] = anonymize_host_ip(gethostbyaddr($request->getHeader("X-Real-IP")[0] ?? "127.0.0.1"));
            $data["referer"] = $request->getHeader("Referer")[0] ?? "unset";


            // Download
            if (origin_match($data["href"], $subscription->allow_origins)) {
                $logfile->append_content(json_encode($data) . "\n");
                $siteConfigFile = getSiteDataStorePath($subscription->subscription_id, $data["href"], $data["page_id"] ?? "missing");
                if ( ! $siteConfigFile->exists()) {
                    $siteConfigFile->createPath()->set_contents(
                       phore_http_request($data["href"])->send()->getBody()
                    );
                }
            }

        }

        $response = new JsonResponse(["ok"]);
        return $response;
    });

    $app->router->on("POST@/v1/webanalytics/log", function(T_Subscription $subscription, string $body, ServerRequest $request) {
        $logfile = phore_file(DATA_PATH . "/" . $subscription->subscription_id . ".log");
        $data = phore_json_decode($body);
        $data["time"] = date("Y-m-d H:i:s");
        $data["ts"] = time();
        $data["ip"] = anonymize_host_ip($request->getHeader("X-Real-IP")[0] ?? "unset x-real-ip");
        $data["host"] = anonymize_host_ip(gethostbyaddr($request->getHeader("X-Real-IP")[0] ?? "127.0.0.1"));
        $logfile->append_content(phore_json_encode($data) . "\n");
        return ["ok"];
    });


    $app->router->on("GET@/v1/webanalytics/", function() {
        return ["system" => "micx webanalytics", "status" => "ok"];
    });

});
