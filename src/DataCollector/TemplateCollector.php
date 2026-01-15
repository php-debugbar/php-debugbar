<?php

declare(strict_types=1);

namespace DebugBar\DataCollector;

class TemplateCollector extends DataCollector implements Renderable, AssetProvider, Resettable
{
    use HasTimeDataCollector;

    protected string $name;
    protected array $templates = [];
    protected bool|string $collect_data;
    /** @var array<string> */
    protected array $exclude_paths;
    protected int|bool $group;

    /**
     *
     * @param string[] $excludePaths Paths to exclude from collection
     */
    public function __construct(bool|string $collectData = true, array $excludePaths = [], int|bool $group = true)
    {
        $this->collect_data = $collectData;
        $this->templates = [];
        $this->exclude_paths = $excludePaths;
        $this->group = $group;
    }

    public function reset(): void
    {
        $this->templates = [];
    }

    public function getName(): string
    {
        return 'templates';
    }

    public function getWidgets(): array
    {
        $name = $this->getName();
        return [
            $name => [
                'icon' => 'file-code',
                'widget' => 'PhpDebugBar.Widgets.TemplatesWidget',
                'map' => $name,
                'default' => '[]',
            ],
            "$name:badge" => [
                'map' => $name . '.nb_templates',
                'default' => 0,
            ],
        ];
    }

    public function getAssets(): array
    {
        return [
            'css' => 'widgets/templates/widget.css',
            'js' => 'widgets/templates/widget.js',
        ];
    }

    public function addTemplate(string $name, array $data, ?string $type, ?string $path): void
    {
        // Prevent duplicates
        $hash = $type . $path . $name . ($this->collect_data ? implode(array_keys($data)) : '');

        if ($this->collect_data === 'keys') {
            $params = array_keys($data);
        } elseif ($this->collect_data) {
            $params = array_map(
                fn($value) => $this->getDataFormatter()->formatVar($value, false),
                $data,
            );
        } else {
            $params = [];
        }

        $template = [
            'name' => $name,
            'param_count' => $this->collect_data ? count($params) : null,
            'params' => $params,
            'start' => microtime(true),
            'type' => $type,
            'hash' => $hash,
        ];

        if ($path && $this->getXdebugLinkTemplate()) {
            $template['xdebug_link'] = $this->getXdebugLink($path);
        }

        if ($this->hasTimeDataCollector()) {
            $this->addTimeMeasure($name, $template['start']);
        }

        $this->templates[] = $template;
    }

    public function collect(): array
    {
        if ($this->group === true || ($this->group !== false && count($this->templates) > $this->group)) {
            $templates = [];
            foreach ($this->templates as $template) {
                $hash = $template['hash'];
                if (!isset($templates[$hash])) {
                    $template['render_count'] = 0;
                    $template['name_original'] = $template['name'];
                    $templates[$hash] = $template;
                }

                $templates[$hash]['render_count']++;
                $templates[$hash]['name'] = $templates[$hash]['render_count'] . 'x ' . $templates[$hash]['name_original'];
            }
            $templates = array_values($templates);
        } else {
            $templates = $this->templates;
        }

        return [
            'count' => count($this->templates),
            'nb_templates' => count($this->templates),
            'templates' => $templates,
        ];
    }
}
