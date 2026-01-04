<?php

declare(strict_types=1);

namespace DebugBar\Bridge\Symfony;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\DataCollectorInterface;
use DebugBar\DataCollector\Renderable;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SymfonySessionCollector extends DataCollector implements DataCollectorInterface, Renderable
{
    protected SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(): array
    {
        $data = $this->session->all();

        foreach ($data as $key => $value) {
            if ($this->isMaskedKey($key)) {
                $data[$key] = '***';
            } else {
                $data[$key] = is_string($value) ? $value : $this->getDataFormatter()->formatVar($value);
            }
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'session';
    }

    /**
     * {@inheritDoc}
     */
    public function getWidgets(): array
    {
        return [
            "session" => [
                "icon" => "archive",
                "widget" => "PhpDebugBar.Widgets.VariableListWidget",
                "map" => "session",
                "default" => "{}",
            ],
        ];
    }
}
