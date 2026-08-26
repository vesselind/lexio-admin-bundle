<?php

namespace Lexio\AdminBundle\Entity;

use Lexio\AdminBundle\Repository\FooterMenuRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FooterMenuRepository::class)]
class FooterMenu extends SortableMenu
{
}
