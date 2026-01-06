<?php

declare(strict_types=1);

namespace DebugBar;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * HTTP driver for Symfony Request/Session
 *
 */
class SymfonyHttpDriver implements HttpDriverInterface
{
    protected SessionInterface $session;

    protected ?Response $response;

    public function __construct(SessionInterface $session, ?Response $response = null)
    {
        $this->session = $session;
        $this->response = $response;
    }

    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }

    /**
     * {@inheritDoc}
     */
    public function setHeaders(array $headers): void
    {
        if (!is_null($this->response)) {
            $this->response->headers->add($headers);
        }
    }

    public function output(string $content): void
    {
        if (!is_null($this->response)) {
            $existingContent = $this->response->getContent();
            $content = $existingContent ? $existingContent . $content : $content;
            $this->response->setContent($content);
        }
    }

    public function isSessionStarted(): bool
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        return $this->session->isStarted();
    }

    public function setSessionValue(string $name, mixed $value): void
    {
        $this->session->set($name, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function hasSessionValue(string $name): bool
    {
        return $this->session->has($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getSessionValue(string $name): mixed
    {
        return $this->session->get($name);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteSessionValue(string $name): void
    {
        $this->session->remove($name);
    }
}
