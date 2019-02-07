<?php
namespace ActivityPub\Database;

use Doctrine\ORM\Mapping\NamingStrategy;

class PrefixNamingStrategy implements NamingStrategy
{
    protected $prefix;

    public function __construct( $prefix )
    {
        $this->prefix = $prefix;
    }

    public function classToTableName($className)
    {
        return $this->prefix . substr($className, strrpos($className, '\\') + 1);
    }

    public function propertyToColumnName($propertyName, $className = null)
    {
        return $propertyName;
    }

    public function referenceColumnName()
    {
        return 'id';
    }

    public function joinColumnName($propertyName, $className = null)
    {
        return $propertyName . '_' . $this->referenceColumnName();
    }

    public function joinTableName($sourceEntity, $targetEntity, $propertyName = null)
    {
        return strtolower($this->classToTableName($sourceEntity) . '_' .
                $this->classToTableName($targetEntity));
    }

    public function joinKeyColumnName($entityName, $referencedColumnName = null)
    {
        return strtolower($this->classToTableName($entityName) . '_' .
                ($referencedColumnName ?: $this->referenceColumnName()));
    }

    public function embeddedFieldToColumnName($propertyName, $embeddedColumnName, $className = null, $embeddedClassName = null)
    {
        return $propertyName.'_'.$embeddedColumnName;
    }
}

