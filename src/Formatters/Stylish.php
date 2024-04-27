<?php

namespace Differ\Differ\Stylish;

use Exception;

const INDENT_LENGTH = 4;

function getIndent($depth)
{
    return str_repeat(' ', INDENT_LENGTH * $depth);
}

function toString($value)
{
    if ($value === null) {
        return 'null';
    }
    return trim(var_export($value, true), "'");
}

function stringify($value, $depth)
{
    if (!is_object($value)) {
        return toString($value);
    }

    $stringifyValue = function ($currentValue, $depth) {
        $indent = getIndent($depth);
        $iter = function ($value, $key) use ($depth, $indent) {
            $formattedValue = stringify($value, $depth);
            return "{$indent}    {$key}: {$formattedValue}";
        };

        $stringifiedValue = array_map($iter, (array) $currentValue, array_keys((array) $currentValue));
        return implode("\n", ["{", ...$stringifiedValue, "{$indent}}"]);
    };
    return $stringifyValue($value, $depth + 1);
}

function format($tree, $depth = 0)
{
    $indent = getIndent($depth);
    $lines = array_map(function ($item) use ($indent, $depth) {
        $key = $item['key'];
        switch ($item['type']) {
            case 'deleted':
                $value = stringify($item['value'], $depth);
                $line = "{$indent}  - {$key}: {$value}";
                break;
            case 'unchanged':
                $value = stringify($item['value'], $depth);
                $line = "{$indent}    {$key}: {$value}";
                break;
            case 'added':
                $value = stringify($item['value'], $depth);
                $line = "{$indent}  + {$key}: {$value}";
                break;
            case 'changed':
                $oldValue = stringify($item['oldValue'], $depth);
                $newValue = stringify($item['newValue'], $depth);
                $line = "{$indent}  - {$key}: {$oldValue}\n{$indent}  + {$key}: {$newValue}";
                break;
            case 'parent':
                $value = format($item['children'], $depth + 1);
                $line = "{$indent}    {$key}: {$value}";
                break;
            default:
                throw new Exception('Unknown type of item');
        }
        return $line;
    }, $tree);
    return implode("\n", ["{", ...$lines, "{$indent}}"]);
}
