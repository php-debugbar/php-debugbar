<?php

declare(strict_types=1);

namespace DebugBar\Bridge\Symfony;

use DebugBar\DataCollector\RequestDataCollector;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * Based on \Symfony\Component\HttpKernel\DataCollector\RequestDataCollector by Fabien Potencier <fabien@symfony.com>
 *
 */
class SymfonyRequestCollector extends RequestDataCollector
{
    protected Request $request;
    protected ?Response $response;

    public function __construct(
        Request $request,
        ?Response $response = null
    ) {
        $this->request = $request;
        $this->response = $response;
        parent::__construct();
    }

    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(): array
    {
        $request = $this->request;
        $response = $this->response ?: new Response();

        // attributes are serialized and as they can be anything, they need to be converted to strings.
        $attributes = [];
        $route = '';
        foreach ($request->attributes->all() as $key => $value) {
            if ('_route' === $key) {
                $route = (\is_object($value) && method_exists($value, 'getPath')) ? $value->getPath() : $value;
                $attributes[$key] = $route;
            } else {
                $attributes[$key] = $value;
            }
        }

        $sessionMetadata = [];
        $sessionAttributes = [];
        $flashes = [];
        if (!$request->attributes->getBoolean('_stateless') && $request->hasSession()) {
            $session = $request->getSession();
            if ($session->isStarted()) {
                $sessionAttributes = $session->all();
            }
        }

        $responseCookies = [];
        foreach ($response->headers->getCookies() as $cookie) {
            $responseCookies[] = $this->getCookieHeader(
                $cookie->getName(),
                $cookie->getValue(),
                $cookie->getExpiresTime(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->isSecure(),
                $cookie->isHttpOnly(),
            );
        }

        $statusCode = $response->getStatusCode();

        $uri = rtrim(preg_replace('/\?.*/', '', $request->getRequestUri()), '/');

        $data = [
            'uri' => strlen($uri) > 100 ? [$uri] : $uri,
            'method' => $request->getMethod(),
            'format' => $request->getRequestFormat(),
            'content_type' => $response->headers->get('Content-Type', 'text/html'),
            'status_text' => Response::$statusTexts[$statusCode] ?? '',
            'status_code' => $statusCode,
        ];

        if ($response instanceof RedirectResponse) {
            $data['response'] = 'Redirect to ' . $response->getTargetUrl();
        }

        $data += [
            'request_query' => $request->query->all(),
            'request_request' => $request->request->all(),
            'request_files' => $request->files->all(),
            'request_headers' => $request->headers->all(),
            'request_server' => $request->server->all(),
            'request_cookies' => $request->cookies->all(),
            'request_attributes' => $attributes,
            'route' => $route,
            'response_headers' => $response->headers->all(),
            'response_cookies' => $responseCookies,
            'session_metadata' => $sessionMetadata,
            'session_attributes' => $sessionAttributes,
            'flashes' => $flashes,
            'path_info' => $request->getPathInfo(),
            'controller' => 'n/a',
            'locale' => $request->getLocale(),
        ];

        if (isset($data['request_headers']['authorization'][0])) {
            $data['request_headers']['authorization'][0] = substr($data['request_headers']['authorization'][0], 0, 12) . '******';
        }

        $tooltip = [
            'status' => $statusCode . ' ' . $data['status_text'],
        ];

        $data = $this->hideMaskedValues($data);
        foreach ($data as $key => $var) {
            if (!is_string($data[$key])) {
                if ($this->isHtmlVarDumperUsed()) {
                    $data[$key] = $this->getVarDumper()->renderVar($var);
                } else {
                    $data[$key] = $this->getDataFormatter()->formatVar($var);
                }
            }
        }

        return [
            'data' => $data,
            'tooltip' => $tooltip,
            'badge' => $statusCode >= 300 ? $statusCode : null,
        ];
    }

    protected function getCookieHeader(string $name, ?string $value, int $expires, string $path, ?string $domain, bool $secure, bool $httponly): string
    {
        $cookie = sprintf('%s=%s', $name, urlencode($value ?? ''));

        if (0 !== $expires) {
            $dt = \DateTime::createFromFormat('U', (string) $expires, new \DateTimeZone('UTC'));
            if ($dt) {
                $cookie .= '; expires=' . substr($dt->format('D, d-M-Y H:i:s T'), 0, -5);
            }
        }

        if ($domain) {
            $cookie .= '; domain=' . $domain;
        }

        $cookie .= '; path=' . $path;

        if ($secure) {
            $cookie .= '; secure';
        }

        if ($httponly) {
            $cookie .= '; httponly';
        }

        return $cookie;
    }
}
