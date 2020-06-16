<?php

namespace Ypsylon\Propel\Behavior\Fulltext;

use Propel\Generator\Model\Index;

class Fulltext extends Index
{
    public function __construct($name = null)
    {
        parent::__construct($name);
    }

    public function getVendorInfoForType($type)
    {
        $result = parent::getVendorInfoForType($type);
        if ($type === 'mysql') {
            $result->setParameter('Index_type', 'FULLTEXT');
        }
    }

}