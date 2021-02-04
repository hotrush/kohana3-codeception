<?php
namespace Codeception\Lib\Connector;

use Symfony\Component\BrowserKit\Response;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\AbstractBrowser as Client;
use Request as KohanaRequest;
use HTTP_Request;

class Kohana3 extends Client
{
    /**
     * @param Request $request
     * @return Response
     */
    protected function doRequest($request)
    {
        $_COOKIE = $request->getCookies();
        $_SERVER = $request->getServer();
        $_FILES = $request->getFiles();

        $method = strtoupper($request->getMethod());
        $uri = str_replace('http://localhost', '', $request->getUri());

        $_SERVER['KOHANA_ENV'] = 'testing';
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = strtoupper($uri);

        KohanaRequest::$initial = null;
        $kohanaRequest = KohanaRequest::factory($uri);
        $kohanaRequest->method($_SERVER['REQUEST_METHOD']);
        if (strtoupper($request->getMethod()) === HTTP_Request::GET) {
            $kohanaRequest->query($request->getParameters());
        } else {
            if (strpos($request->getUri(), '?') !== false) {
                $parse = parse_url($request->getUri());
                if ($parse['query']) {
                    parse_str($parse['query'], $queryParams);
                    if ($queryParams) {
                        $kohanaRequest->query($queryParams);
                    }
                }
            }
        }
        if (in_array($method, [HTTP_Request::POST, HTTP_Request::PUT])) {
            if ($request->getContent()) {
                $kohanaRequest->headers('Content-Type','application/json');
                $kohanaRequest->body($request->getContent());
            }
            $kohanaRequest->post($request->getParameters());
        }

        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            $kohanaRequest->headers('Authorization', 'Basic '.base64_encode($_SERVER['PHP_AUTH_USER'].':'.$_SERVER['PHP_AUTH_PW']));
        }
        $kohanaRequest->cookie($_COOKIE);
        $kohanaRequest::$initial = $kohanaRequest;
        $kohanaResponse = $kohanaRequest->execute();

        return new Response(
            $kohanaResponse->body(),
            $kohanaResponse->status(),
            (array) $kohanaResponse->headers()
        );

    }

    protected function createKohanaRequest(Request $request): KohanaRequest
    {
        $_COOKIE = $request->getCookies();
        $_SERVER = $request->getServer();
        $_FILES = $request->getFiles();

        $method = strtoupper($request->getMethod());
        $uri = $this->getUri($request);

        $_SERVER['KOHANA_ENV'] = 'testing';
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = strtoupper($uri);

        KohanaRequest::$initial = null;
        $kohanaRequest = KohanaRequest::factory($uri);
        $kohanaRequest->method($method);

        $kohanaRequest->query($this->getQueryParams($request));

        if (in_array($method, [HTTP_Request::POST, HTTP_Request::PUT])) {
            if ($request->getContent()) {
                $kohanaRequest->headers('Content-Type','application/json');
                $kohanaRequest->body($request->getContent());
            }
            $kohanaRequest->post($request->getParameters());
        }

        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            $kohanaRequest->headers('Authorization', 'Basic '.base64_encode($_SERVER['PHP_AUTH_USER'].':'.$_SERVER['PHP_AUTH_PW']));
        }
        $kohanaRequest->cookie($_COOKIE);
        $kohanaRequest::$initial = $kohanaRequest;
    }

    protected function getUri(Request $request): string
    {
        return str_replace('http://localhost', '', $request->getUri());
    }

    protected function getQueryParams(Request $request): array
    {
        return strtoupper($request->getMethod()) === HTTP_Request::GET
            ? $request->getParameters()
            : $this->parseRequestQueryParams($request);
    }

    protected function parseRequestQueryParams(Request $request): array
    {
        if (strpos($request->getUri(), '?') !== false) {
            $parse = parse_url($request->getUri());
            if ($parse['query']) {
                parse_str($parse['query'], $queryParams);
            }
        }

        return $queryParams ?? [];
    }
}
