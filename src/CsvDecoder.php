<?php
/**
 * PHP Version 7
 *
 * LICENSE:
 * Proprietary, see the LICENSE file that was provided with the software.
 *
 * Copyright (c) 2019 - present Comertis <info@comertis.com>
 *
 * @category Serializers
 * @package  Comertis\Serializers
 * @author   Cristian Moraru <cristian@comertis.com>
 * @license  Proprietary
 * @version  GIT: &Id&
 * @link     https://github.com/Comertis/Serializers
 */

namespace Comertis\Serializers\Csv;

use Comertis\Serializers\Abstraction\DecoderInterface;
use Comertis\Serializers\Csv\AbstractCsvHandler;

/**
 * Undocumented class
 *
 * @category Serializers
 * @package  Comertis\Serializers
 * @author   Cristian Moraru <cristian@comertis.com>
 * @license  Proprietary
 * @version  Release: 1.0.0
 * @link     https://github.com/Comertis/Serializers
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
