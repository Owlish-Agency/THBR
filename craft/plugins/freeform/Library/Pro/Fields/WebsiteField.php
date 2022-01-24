<?php

namespace Solspace\Freeform\Library\Pro\Fields;

use Solspace\Freeform\Library\Composer\Components\Fields\TextField;
use Solspace\Freeform\Library\Composer\Components\Validation\Constraints\WebsiteConstraint;

class WebsiteField extends TextField
{
    /**
     * Return the field TYPE
     *
     * @return string
     */
    public function getType()
    {
        return self::TYPE_WEBSITE;
    }

    /**
     * @inheritDoc
     */
    public function getConstraints()
    {
        return [
            new WebsiteConstraint($this->translate('Website not valid')),
        ];
    }
}
