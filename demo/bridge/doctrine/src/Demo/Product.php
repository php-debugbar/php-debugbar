<?php

namespace Demo;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\GeneratedValue;

/** @Entity @Table(name="products") */
#[Entity]
#[Table(name: "products")]
class Product
{
    /** @Id @Column(type="integer") @GeneratedValue **/
    #[Id, Column(type: "integer"), GeneratedValue]
    private int $id;

    /** @Column(type="string") **/
    #[Column(type: "string")]
    private string $name;

    /** @Column(type="datetime", nullable=true) **/
    #[Column(type: "datetime", nullable: true)]
    private ?\DateTime $updated = null;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setUpdated(): void
    {
        // will NOT be saved in the database
        $this->updated = new \DateTime('now');
    }
}
