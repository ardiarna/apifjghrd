<?php

namespace App\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class EnumType extends Type
{
    const ENUM = 'enum'; // Nama tipe kustom

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        // Periksa apakah 'allowed' ada dan merupakan array
        if (!isset($fieldDeclaration['allowed']) || !is_array($fieldDeclaration['allowed'])) {
            throw new \InvalidArgumentException("Allowed values for enum must be defined as an array.");
        }

        // Konversi nilai enum menjadi string SQL
        $values = array_map(function($val) {
            return "'" . $val . "'";
        }, $fieldDeclaration['allowed']);

        return "ENUM(" . implode(", ", $values) . ")";
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    public function getName()
    {
        return self::ENUM;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
