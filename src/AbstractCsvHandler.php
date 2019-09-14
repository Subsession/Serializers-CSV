<?php
/**
 * PHP Version 7
 *
 * LICENSE:
 * Copyright 2019 Subsession
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge,
 * publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
 * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @category Serializers
 * @package  Subsession\Serializers
 * @author   Cristian Moraru <cristian.moraru@live.com>
 * @license  Proprietary
 * @version  GIT: &Id&
 * @link     https://github.com/Subsession/Serializers
 */

namespace Subsession\Serializers\Csv;

/**
 * Undocumented class
 *
 * @category Serializers
 * @package  Subsession\Serializers
 * @author   Cristian Moraru <cristian.moraru@live.com>
 * @license  Proprietary
 * @version  Release: 1.0.0
 * @link     https://github.com/Subsession/Serializers
 */
abstract class AbstractCsvHandler
{
    const DELIMITER_KEY = 'csv_delimiter';
    const ENCLOSURE_KEY = 'csv_enclosure';
    const ESCAPE_CHAR_KEY = 'csv_escape_char';
    const KEY_SEPARATOR_KEY = 'csv_key_separator';
    const HEADERS_KEY = 'csv_headers';
    const ESCAPE_FORMULAS_KEY = 'csv_escape_formulas';
    const AS_COLLECTION_KEY = 'as_collection';
    const NO_HEADERS_KEY = 'no_headers';

    protected $formulasStartCharacters = ['=', '-', '+', '@'];
    protected $defaultContext = [
        self::DELIMITER_KEY => ',',
        self::ENCLOSURE_KEY => '"',
        self::ESCAPE_CHAR_KEY => '\\',
        self::ESCAPE_FORMULAS_KEY => false,
        self::HEADERS_KEY => [],
        self::KEY_SEPARATOR_KEY => '.',
        self::NO_HEADERS_KEY => false,
    ];

    /**
     * Flattens an array and generates keys including the path.
     */
    protected function flatten(iterable $array, array &$result, string $keySeparator, string $parentKey = '', bool $escapeFormulas = false)
    {
        foreach ($array as $key => $value) {
            if (is_iterable($value)) {
                $this->flatten($value, $result, $keySeparator, $parentKey . $key . $keySeparator, $escapeFormulas);
            } else {
                if ($escapeFormulas && \in_array(substr((string) $value, 0, 1), $this->formulasStartCharacters, true)) {
                    $result[$parentKey . $key] = "\t" . $value;
                } else {
                    // Ensures an actual value is used when dealing with true and false
                    $result[$parentKey . $key] = false === $value ? 0 : (true === $value ? 1 : $value);
                }
            }
        }
    }

    /**
     * @return string[]
     */
    protected function extractHeaders(iterable $data): array
    {
        $headers = [];
        $flippedHeaders = [];

        foreach ($data as $row) {
            $previousHeader = null;

            foreach ($row as $header => $_) {
                if (isset($flippedHeaders[$header])) {
                    $previousHeader = $header;
                    continue;
                }

                if (null === $previousHeader) {
                    $n = \count($headers);
                } else {
                    $n = $flippedHeaders[$previousHeader] + 1;

                    for ($j = \count($headers); $j > $n; --$j) {
                        ++$flippedHeaders[$headers[$j] = $headers[$j - 1]];
                    }
                }

                $headers[$n] = $header;
                $flippedHeaders[$header] = $n;
                $previousHeader = $header;
            }
        }

        return $headers;
    }

    protected function getCsvOptions(array $context): array
    {
        $delimiter = $context[self::DELIMITER_KEY] ?? $this->defaultContext[self::DELIMITER_KEY];
        $enclosure = $context[self::ENCLOSURE_KEY] ?? $this->defaultContext[self::ENCLOSURE_KEY];
        $escapeChar = $context[self::ESCAPE_CHAR_KEY] ?? $this->defaultContext[self::ESCAPE_CHAR_KEY];
        $keySeparator = $context[self::KEY_SEPARATOR_KEY] ?? $this->defaultContext[self::KEY_SEPARATOR_KEY];
        $headers = $context[self::HEADERS_KEY] ?? $this->defaultContext[self::HEADERS_KEY];
        $escapeFormulas = $context[self::ESCAPE_FORMULAS_KEY] ?? $this->defaultContext[self::ESCAPE_FORMULAS_KEY];

        if (!\is_array($headers)) {
            throw new ArgumentException(sprintf('The "%s" context variable must be an array or null, given "%s".', self::HEADERS_KEY, \gettype($headers)));
        }

        return [$delimiter, $enclosure, $escapeChar, $keySeparator, $headers, $escapeFormulas];
    }
}
