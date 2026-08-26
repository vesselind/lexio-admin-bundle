<?php

namespace Lexio\AdminBundle\Contract\Seeder;

interface SeedersRegistryInterface
{
    /**
     * @return array<int, class-string>
     */
    public function getRegistry(): array;

}