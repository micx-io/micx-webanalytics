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

        $msId = $request->getCookieParams()["MSID"] ?? null;
        if($msId === null || strlen($msId) !== 6) {
            $msId = phore_random_str(6);
        }

        $origin = $request->getHeader("referer")[0] ?? null;
        if ($origin !== null && ! in_array(substr($origin, 0, -1), $config->allow_origins, true)) {
            $origin = substr($origin, 0, -1);
            $error = "Invalid origin: '$origin' - not allowed for subscription_id '$subscriptionId'";
        }

        $jsText = file_get_contents(__DIR__ . "/../src/webanalytics.js");
        $jsText = str_replace(
            ["%%ENDPOINT_URL%%", "%%SESSION_ID%%"],
            [
                "//" . $app->request->getUri()->getHost() . "/analytics/emit?subscription_id=$subscriptionId",
                $msId
            ],
            $jsText
        );

        $response = $app->responseFactory->createResponseWithBody($jsText, 200, ["Content-Type" => "application/javascript"]);
        Cookie::setCookie($response, "MSID", $msId);
        return $response;
    });

    $app->router->on("POST@/analytics/emit", function(string $body, Config $config, ServerRequest $request) use ($app) {
        $mailer = new PhoreMailer();

        $data = json_decode($body, true);


        $response = new JsonResponse(["ok"]);

        $mailer->setSmtpDirectConnect("webanalytics.micx.io");
        $mailer->send(file_get_contents(__DIR__ ."/../src/mail.txt"), [
            "email" => $config->report_email,
            "session_id" => $data["session_id"] ?? '000000',
            "referer" => $request->getHeader("Referer")[0] ?? "unset",
            "ip" => $request->getHeader("X-Real-IP")[0] ?? "unset x-real-ip",
            "host" => gethostbyaddr($request->getHeader("X-Real-IP")[0] ?? "127.0.0.1"),
            "href" => $data["href"] ?? "*undefined href*",
            "data" => yaml_emit($data)
        ]);



        return $response;
    });


    $app->router->on("GET@/", function() {
        return ["system" => "micx webanalytics", "status" => "ok"];
    });

});
