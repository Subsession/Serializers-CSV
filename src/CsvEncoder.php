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

use Subsession\Serializers\Abstraction\EncoderInterface;
use Subsession\Serializers\Csv\AbstractCsvHandler;

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
class CsvEncoder extends AbstractCsvHandler implements EncoderInterface
{
    /**
     * @param array $defaultContext
     */
    public function __construct($defaultContext = [])
    {
        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
    }

    /**
     * {@inheritdoc}
     */
    public function encode($data, array $context = [])
    {
        $handle = fopen('php://temp,', 'w+');

        if (!is_iterable($data)) {
            $data = [[$data]];
        } elseif (empty($data)) {
            $data = [[]];
        } else {
            // Sequential arrays of arrays are considered as collections
            $i = 0;
            foreach ($data as $key => $value) {
                if ($i !== $key || !\is_array($value)) {
                    $data = [$data];
                    break;
                }

                ++$i;
            }
        }

        list(
            $delimiter,
            $enclosure,
            $escapeChar,
            $keySeparator,
            $headers,
            $escapeFormulas
        ) = $this->getCsvOptions($context);

        foreach ($data as &$value) {
            $flattened = [];
            $this->flatten($value, $flattened, $keySeparator, '', $escapeFormulas);
            $value = $flattened;
        }
        unset($value);

        $headers = array_merge(array_values($headers), array_diff($this->extractHeaders($data), $headers));

        if (!($context[self::NO_HEADERS_KEY] ?? false)) {
            fputcsv($handle, $headers, $delimiter, $enclosure, $escapeChar);
        }

        $headers = array_fill_keys($headers, '');
        foreach ($data as $row) {
            fputcsv($handle, array_replace($headers, $row), $delimiter, $enclosure, $escapeChar);
        }

        rewind($handle);
        $value = stream_get_contents($handle);
        fclose($handle);

        return $value;
    }
}
