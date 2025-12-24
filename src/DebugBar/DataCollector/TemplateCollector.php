<?php

declare(strict_types=1);

namespace DebugBar\DataCollector;

use DebugBar\DataFormatter\SimpleFormatter;

class TemplateCollector extends DataCollector implements Renderable, AssetProvider
{
    protected $name;
    protected $templates = [];
    protected $collect_data;
    protected $exclude_paths;
    protected $group;
    protected $timeCollector;

    /**
     * Create a ViewCollector
     *
     * @param bool|string $collectData Collects view data when true
     * @param string[] $excludePaths Paths to exclude from collection
     * @param int|bool $group Group the same templates together
     * @param TimeDataCollector|null $timeCollector
     * */
    public function __construct($collectData = true, $excludePaths = [], $group = true, ?TimeDataCollector $timeCollector = null)
    {
        $this->setDataFormatter(new SimpleFormatter());
        $this->collect_data = $collectData;
        $this->templates = [];
        $this->exclude_paths = $excludePaths;
        $this->group = $group;
        $this->timeCollector = $timeCollector;
    }

    public function getName()
    {
        return 'templates';
    }

    public function getWidgets()
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

    /**
     * @return array
     */
    public function getAssets()
    {
        return [
            'css' => 'widgets/templates/widget.css',
            'js' => 'widgets/templates/widget.js',
        ];
    }

    public function addTemplate(string $name, array $data, ?string $type, ?string $path)
    {
        // Prevent duplicates
        $hash = $type . $path . $name . ($this->collect_data ? implode(array_keys($data)) : '');

        if ($this->collect_data === 'keys') {
            $params = array_keys($data);
        } elseif ($this->collect_data) {
            $params = array_map(
                fn($value) => $this->getDataFormatter()->formatVar($value),
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

        $this->templates[] = $template;
    }

    public function collect()
    {
        if ($this->group === true || count($this->templates) > $this->group) {
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
