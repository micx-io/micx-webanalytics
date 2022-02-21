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
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Micx\FormMailer\Config\Config;
use Phore\Mail\PhoreMailer;
use Psr\Http\Message\ServerRequestInterface;
use function Sodium\add;

AppLoader::extend(function (BraceApp $app) {


    $app->router->on("GET@/v1/webanalytics/wa.js", function (BraceApp $app, string $subscriptionId, Config $config, ServerRequestInterface $request) {

        $origin = $request->getHeader("referer")[0] ?? "";
        if ( ! origin_match($origin, $config->allow_origins)) {
            $origin = addslashes($origin);
            $subscriptionId = addslashes($subscriptionId);
            return $app->responseFactory->createResponseWithBody(
                "console.log('Webanalytics: Invalid origin $origin for subscriptionId $subscriptionId')",
                403, ["content-type"=>"text/javascript"]
            );
        }

        $jsText = file_get_contents(__DIR__ . "/../src/webanalytics.js");
        $jsText .= file_get_contents(__DIR__ . "/../src/wa_player.js");
        $rand = phore_random_str(6);
        $endpointKey = sha1($subscriptionId . $rand . FE_SECRET);
        $jsText = str_replace(
            ["%%ENDPOINT_URL%%", "%%RAND%%", "%%SERVER_DATE%%", "%%SUBSCRIPTION_ID%%", "%%ENDPOINT_KEY%%"],
            [
                "//" . $app->request->getUri()->getHost() . "/v1/webanalytics/emit?subscription_id=$subscriptionId",
                $rand,
                gmdate("Y-m-d H:i:s"),
                $subscriptionId,
                $endpointKey
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


    $app->router->on("GET@/v1/webanalytics/emit", function (ServerRequest $request, Config $config) {
        $session_id = $request->getQueryParams()["session_id"] ?? null;
        $session_seq = (int)($request->getQueryParams()["session_seq"] ?? -1);
        $endpoint_key = (string)($request->getQueryParams()["endpoint_key"] ?? "");

        if ($endpoint_key !== sha1(FE_SECRET . $session_id))
            throw new \InvalidArgumentException("Invalid endpoint key.");

        $logfile = phore_file(DATA_PATH . "/" . $config->subscription_id);

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

    $app->router->on("POST@/v1/webanalytics/emit", function(string $body, Config $config, ServerRequest $request) use ($app) {
        $endpoint_key = (string)($request->getQueryParams()["endpoint_key"] ?? "");
        $data = json_decode($body, true);

        if ($endpoint_key !== sha1($config->subscription_id . $data["session_id"] . FE_SECRET))
            throw new \InvalidArgumentException("Endpoint key invalid");

        $logfile = phore_file(DATA_PATH . "/" . $config->subscription_id);

        if (is_array($data)) {
            $data["ts"] = time();
            $data["ip"] = $request->getHeader("X-Real-IP")[0] ?? "unset x-real-ip";
            $data["host"] = gethostbyaddr($request->getHeader("X-Real-IP")[0] ?? "127.0.0.1");
            $data["referer"] = $request->getHeader("Referer")[0] ?? "unset";
            $logfile->append_content(json_encode($data) . "\n");
        }


        $response = new JsonResponse(["ok"]);
        return $response;
    });


    $app->router->on("GET@/v1/webanalytics/", function() {
        return ["system" => "micx webanalytics", "status" => "ok"];
    });

});
