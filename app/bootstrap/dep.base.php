<?php
/** @var \Slim\Container $container slim container */

$container['logger'] = function () {
    $config = \App\AppContainer::config('logger');
    $logger = new \Monolog\Logger($config['name']);
    $file = new \Monolog\Handler\RotatingFileHandler($config['path'], $config['level']);
    $logger->pushHandler($file);

    return $logger;
};

$container['errorHandler'] = function (\Slim\Container $container) {

    return function (\Slim\Http\Request $request, \Slim\Http\Response $response, $exception) use ($container) {

        $config = \App\AppContainer::config('logger');

        /** @var \Exception $exception */
        $traceData = [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];
        $container->get('logger')->addError($exception->getMessage(), $traceData);

        $err['status'] = 'error';
        $err['message'] = 'Something went wrong, please try again later';

        if ($config['err_msg']) {
            $err['message'] = $exception->getMessage();
        }
        if ($config['err_trace'] == '1') {
            $traceData['message'] = $exception->getMessage();
            $err['trace'] = $traceData;
        }
        $statusCode = [
            "100", "101", "200", "201", "202", "203", "204", "205", "206", "300", "301", "302", "303",
            "304", "305", "306", "307", "400", "401", "402", "403", "404", "405", "406", "407", "408", "409", "410",
            "411", "412", "413", "414", "415", "416", "417", "500", "501", "502", "503", "504", "505"
        ];
        $exCode = $exception->getCode();
        if (!in_array($exCode, $statusCode)) {
            $exCode = 500;
        }
        return $response->withJson($err, $exCode);
    };
};

$container['notFoundHandler'] = function ($c) {
    return function (\Slim\Http\Request $request, \Slim\Http\Response $response) use ($c) {
        return $response->withJson(['status' => 'error', 'message' => 'Not found'], 404);
    };
};

$container['notAllowedHandler'] = function ($c) {
    return function (\Slim\Http\Request $request, \Slim\Http\Response $response, $methods) use ($c) {
        return $response->withJson(['status' => 'error', 'message' => 'Method not allowed ' . implode(', ', $methods)], 405);
    };
};

$container['phpErrorHandler'] = function (\Slim\Container $container) {
    return $container->get('errorHandler');
};

require_once __DIR__ . '/dep.app.php';