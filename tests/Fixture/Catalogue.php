<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture;

use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\Contains;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints\Keys;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The attributes stay on one line each: PHP 7.4 reads such a line as a comment.
 */
final class Catalogue
{
    /**
     * @var array<string, int>
     */
    #[Keys([new Assert\Regex('/^x-/')])]
    private array $extra;

    /**
     * @var list<string>
     */
    #[Contains([new Assert\IdenticalTo('admin')])]
    private array $roles;

    /**
     * @param array<string, int> $extra
     * @param list<string> $roles
     */
    public function __construct(array $extra = [], array $roles = ['admin'])
    {
        $this->extra = $extra;
        $this->roles = $roles;
    }

    /**
     * @return array<string, int>
     */
    public function getExtra(): array
    {
        return $this->extra;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }
}
