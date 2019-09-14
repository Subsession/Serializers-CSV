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

use Subsession\Serializers\Abstraction\DecoderInterface;
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
class CsvEncoder extends AbstractCsvHandler implements DecoderInterface
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
    public function decode($data, array $context = [])
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $data);
        rewind($handle);

        $headers = null;
        $nbHeaders = 0;
        $headerCount = [];
        $result = [];

        list($delimiter, $enclosure, $escapeChar, $keySeparator) = $this->getCsvOptions($context);

        while (false !== ($cols = fgetcsv($handle, 0, $delimiter, $enclosure, $escapeChar))) {
            $nbCols = \count($cols);

            if (null === $headers) {
                $nbHeaders = $nbCols;

                if ($context[self::NO_HEADERS_KEY] ?? false) {
                    for ($i = 0; $i < $nbCols; ++$i) {
                        $headers[] = [$i];
                    }
                    $headerCount = array_fill(0, $nbCols, 1);
                } else {
                    foreach ($cols as $col) {
                        $header = explode($keySeparator, $col);
                        $headers[] = $header;
                        $headerCount[] = \count($header);
                    }

                    continue;
                }
            }

            $item = [];
            for ($i = 0; ($i < $nbCols) && ($i < $nbHeaders); ++$i) {
                $depth = $headerCount[$i];
                $arr = &$item;
                for ($j = 0; $j < $depth; ++$j) {
                    // Handle nested arrays
                    if ($j === ($depth - 1)) {
                        $arr[$headers[$i][$j]] = $cols[$i];

                        continue;
                    }

                    if (!isset($arr[$headers[$i][$j]])) {
                        $arr[$headers[$i][$j]] = [];
                    }

                    $arr = &$arr[$headers[$i][$j]];
                }
            }

            $result[] = $item;
        }
        fclose($handle);

        if ($context[self::AS_COLLECTION_KEY] ?? false) {
            return $result;
        }

        if (empty($result) || isset($result[1])) {
            return $result;
        }

        // If there is only one data line in the document, return it (the line),
        // the result is not considered as a collection
        return $result[0];
    }
}
