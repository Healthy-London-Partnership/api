<?php

namespace App\Docs\Paths\Organisations;

use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use App\Docs\Operations\Organisations\ImportOrganisationsOperation;

class OrganisationsImportPath extends PathItem
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/organisations/import')
            ->operations(
                ImportOrganisationsOperation::create()
            );
    }
}
