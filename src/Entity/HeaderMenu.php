<?php

namespace Lexio\AdminBundle\Entity;

use Lexio\AdminBundle\Repository\HeaderMenuRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HeaderMenuRepository::class)]
class HeaderMenu extends SortableMenu
{
}
