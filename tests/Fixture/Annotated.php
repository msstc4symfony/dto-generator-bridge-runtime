<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture;

use Msstc4Symfony\DtoGeneratorBridge\Runtime\Constraints as RuntimeAssert;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The constraints as annotations, which the generator writes for PHP 7.4.
 *
 * @RuntimeAssert\DependentRequired(requirements={"card"={"holder"}})
 */
final class Annotated
{
    private ?string $card;

    private ?string $holder;

    /**
     * @var list<string>
     *
     * @RuntimeAssert\Contains(constraints={@Assert\IdenticalTo(value="admin")}, max=1)
     */
    private array $roles;

    /**
     * @var array<string, int>
     *
     * @RuntimeAssert\Keys(constraints={@Assert\Length(max=3)})
     */
    private array $extra;

    /**
     * @param list<string> $roles
     * @param array<string, int> $extra
     */
    public function __construct(?string $card, ?string $holder, array $roles, array $extra)
    {
        $this->card = $card;
        $this->holder = $holder;
        $this->roles = $roles;
        $this->extra = $extra;
    }

    public function getCard(): ?string
    {
        return $this->card;
    }

    public function getHolder(): ?string
    {
        return $this->holder;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @return array<string, int>
     */
    public function getExtra(): array
    {
        return $this->extra;
    }
}
