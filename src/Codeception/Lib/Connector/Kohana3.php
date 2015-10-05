<?php
namespace Codeception\Lib\Connector;

use Symfony\Component\BrowserKit\Response;
use Symfony\Component\BrowserKit\Client;

class Kohana3 extends Client
{

    /**
     * @param SymfonyRequest $request
     * @return Response
     */
    protected function doRequest($request)
    {

        $_COOKIE = $request->getCookies();
        $_SERVER = $request->getServer();
        $_FILES = $request->getFiles();

        $uri = str_replace('http://localhost', '', $request->getUri());

        $_SERVER['KOHANA_ENV'] = 'testing';
        $_SERVER['REQUEST_METHOD'] = strtoupper($request->getMethod());
        $_SERVER['REQUEST_URI'] = strtoupper($uri);

        $kohanaRequest = \Request::factory($uri);
        $kohanaRequest->method($_SERVER['REQUEST_METHOD']);
        if (strtoupper($request->getMethod()) == 'GET') {
            $kohanaRequest->query($request->getParameters());
        }
        if (strtoupper($request->getMethod()) == 'POST') {
            $kohanaRequest->body($request->getContent());
            $kohanaRequest->post($request->getParameters());
        }

        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            $kohanaRequest->headers('Authorization', 'Basic '.base64_encode($_SERVER['PHP_AUTH_USER'].':'.$_SERVER['PHP_AUTH_PW']));
        }
        $kohanaRequest->cookie($_COOKIE);
        $kohanaRequest::$initial = $kohanaRequest;
        $kohanaResponse = $kohanaRequest->execute();
        $content = $kohanaResponse->body();
        $headers = (array) $kohanaResponse->headers();
        $status = $kohanaResponse->status();
        $response = new Response($content, $status, $headers);
        return $response;

    }

}
