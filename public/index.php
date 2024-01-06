<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yafai\API;
use Parsedown;

require __DIR__ . "/../vendor/autoload.php";

date_default_timezone_set("Europe/London");

$app = \DI\Bridge\Slim\Bridge::create();

require __DIR__ . "/../src/classes/Middleware/View.php";

$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$app->get("/test", function (Request $request, Response $response) {
    $API = new Yafai\API(new Parsedown());
    return $request->getAttribute("view")->render($response, "test.phtml", [
        "status" => $API->getMastodonStatus(true, null, true),
    ]);
});

$app->post("/statuslol", [Yafai\API::class, "statuslol"]);

$app->get("/rss[.xml]", [Yafai\Pages::class, "rss"]);
$app->get("/feed[.xml]", [Yafai\Pages::class, "rss"]);

$app->get("/{page:.{1,}}", [Yafai\Pages::class, "renderPage"]);

$app->get("/", [Yafai\Pages::class, "renderHomePage"]);

$app->run();
