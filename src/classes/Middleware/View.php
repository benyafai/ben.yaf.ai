<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Views\PhpRenderer as View;

$app->add(function (Request $request, RequestHandler $handler) {
    $view = new View();
    $view->setTemplatePath(__DIR__ . '/../../views/');
    $view->setLayout("layout.phtml");
    $request = $request->withAttribute('view', $view);
    $response = $handler->handle($request);
    return $response;
});
