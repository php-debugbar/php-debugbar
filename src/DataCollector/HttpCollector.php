<?php

declare(strict_types=1);

namespace DebugBar\DataCollector;

class HttpCollector extends DataCollector implements Renderable, AssetProvider
{
    protected string $name;
    protected array $requests = [];
    protected ?TimeDataCollector $timeCollector;

    public function __construct(string $name = 'http', ?TimeDataCollector $timeCollector = null)
    {
        $this->name = $name;
        $this->timeCollector = $timeCollector;
        $this->addMaskedKeys(['Authorization']);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getWidgets(): array
    {
        $name = $this->getName();
        return [
            $name => [
                'icon' => 'mobiledata',
                'widget' => 'PhpDebugBar.Widgets.HttpWidget',
                'map' => $name . '.requests',
                'default' => '[]',
            ],
            "$name:badge" => [
                'map' => $name . '.nb_requests',
                'default' => 0,
            ],
        ];
    }

    public function getAssets(): array
    {
        return [
            'js' => 'widgets/http/widget.js',
        ];
    }

    public function addRequest(string $method, string $url, ?int $status, ?float $duration, array $details = []): void
    {
        $details = $this->hideMaskedValues($details);
        foreach ($details as $key => $value) {
            $details[$key] = $this->getDataFormatter()->formatVar($value);
        }

        $this->requests[] = [
            'method' => $method,
            'url' => $url,
            'status' => $status,
            'duration' => $duration ? $this->getDataFormatter()->formatDuration($duration) : null,
            'details' => $details,
        ];

        if ($this->timeCollector) {
            $end = microtime(true);
            $start = $duration ? $end - $duration : $end;
            $this->timeCollector->addMeasure('GET ' . $url, $start, $end, [], $this->getName());
        }
    }

    public function collect(): array
    {
        return [
            'nb_requests' => count($this->requests),
            'requests' => $this->requests,
        ];
    }
}
