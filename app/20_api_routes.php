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

AppLoader::extend(function (BraceApp $app) {


    $app->router->on("GET@/webanalytics.js", function (BraceApp $app, string $subscriptionId, Config $config, ServerRequestInterface $request) {

        $origin = $request->getHeader("referer")[0] ?? null;
        if ($origin !== null && ! in_array(substr($origin, 0, -1), $config->allow_origins, true)) {
            $origin = substr($origin, 0, -1);
            $error = "Invalid origin: '$origin' - not allowed for subscription_id '$subscriptionId'";
        }

        $jsText = file_get_contents(__DIR__ . "/../src/webanalytics.js");
        $jsText .= file_get_contents(__DIR__ . "/../src/wa_player.js");
        $jsText = str_replace(
            ["%%ENDPOINT_URL%%", "%%RAND%%", "%%SERVER_DATE%%", "%%SUBSCRIPTION_ID%%"],
            [
                "//" . $app->request->getUri()->getHost() . "/analytics/emit?subscription_id=$subscriptionId",
                phore_random_str(6),
                gmdate("Y-m-d H:i:s"),
                $subscriptionId
            ],
            $jsText
        );

        $response = $app->responseFactory->createResponseWithBody($jsText, 200, ["Content-Type" => "application/javascript"]);
        return $response;
    });

    $app->router->on("GET@/analytics/emit", function (ServerRequest $request, Config $config) {
        $session_id = $request->getQueryParams()["session_id"] ?? null;
        $session_seq = (int)($request->getQueryParams()["session_seq"] ?? -1);

        $logfile = phore_file(DATA_PATH . "/" . $config->subscription_id);

        $fp = $logfile->fopen("r");
        while ( ! $fp->feof()) {
            $data = json_decode($fp->fgets(), true);
            out($data);
            if ( ! is_array($data))
                continue;
            if (($data["session_id"] ?? null) === $session_id && ($data["session_seq"] ?? null) === $session_seq) {
                unset($data["ip"], $data["host"], $data["ts"]);
                return $data;
            }
        }
        return ["sequence_end" => true];
    });

    $app->router->on("POST@/analytics/emit", function(string $body, Config $config, ServerRequest $request) use ($app) {
        $logfile = phore_file(DATA_PATH . "/" . $config->subscription_id);

        $data = json_decode($body, true);
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


    $app->router->on("GET@/", function() {
        return ["system" => "micx webanalytics", "status" => "ok"];
    });

});
