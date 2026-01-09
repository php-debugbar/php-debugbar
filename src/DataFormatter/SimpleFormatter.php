<?php

declare(strict_types=1);

namespace DebugBar\DataFormatter;

#[\AllowDynamicProperties]
class SimpleFormatter extends DataFormatter
{
    protected static array $defaultClonerOptions = [
        'max_string' => 1000,
        'max_items' => 100,
        'min_depth' => 1,
    ];

    public function formatVar(mixed $data): string
    {
        if ($data instanceof \__PHP_Incomplete_Class) {
            return sprintf('__PHP_Incomplete_Class(%s)', $this->getClassNameFromIncomplete($data));
        }

        if (is_object($data)) {
            if ($data instanceof \DateTimeInterface) {
                return sprintf('Object(%s) - %s', get_class($data), $data->format(\DateTime::ATOM));
            }

            return sprintf('Object(%s)', get_class($data));
        }

        return parent::formatVar($data);
    }

    /**
     * @author Bernhard Schussek <bschussek@gmail.com>
     */
    private function getClassNameFromIncomplete(\__PHP_Incomplete_Class $value): string
    {
        $array = new \ArrayObject($value);

        return $array['__PHP_Incomplete_Class_Name'];
    }
}
