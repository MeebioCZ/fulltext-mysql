<?php

namespace Ypsylon\Propel\Behavior\Fulltext;

use Propel\Generator\Model\Index;
use Propel\Generator\Model\VendorInfo;

class Fulltext extends Index
{
    public function __construct($name = null)
    {
        parent::__construct($name);
    }

    public function getVendorInfoForType(string $type): VendorInfo
    {
        $result = parent::getVendorInfoForType($type);
        if ($type === 'mysql') {
            $result->setParameter('Index_type', 'FULLTEXT');
        }

        return $result;
    }

}